<?php


namespace Poisa\Settings;

use Poisa\Settings\Events\SettingCreated;
use Poisa\Settings\Events\SettingRead;
use Poisa\Settings\Events\SettingUpdated;
use Poisa\Settings\Exceptions\KeyNotFoundException;
use Poisa\Settings\Models\Settings as SettingsModel;
use Poisa\Settings\Serializers\Serializer;
use Poisa\Settings\Serializers\SerializerFactory;

class Settings
{
    /**
     * Shortcut for getKey using the system connection.
     * @param string $key
     * @return null
     */
    public function getSystemKey(string $key)
    {
        return $this->getKey($key, 'system');
    }

    /**
     * Shortcut for getKey using the tenant connection.
     * @param string $key
     * @return null
     */
    public function getTenantKey(string $key)
    {
        return $this->getKey($key, 'tenant');
    }

    /**
     * Shortcut for setKey using the system connection.
     * @param string $key
     * @param        $value
     * @return bool
     */
    public function setSystemKey(string $key, $value): bool
    {
        return $this->setKey($key, $value, 'system');
    }

    /**
     * Shortcut for getKey using the tenant connection.
     * @param string $key
     * @param        $value
     * @return bool
     */
    public function setTenantKey(string $key, $value): bool
    {
        return $this->setKey($key, $value, 'tenant');
    }

    /**
     * Sets a key using a connection. If the key doesn't exist, it will create it.
     * @param string $key
     * @param        $value
     * @param string $connection
     * @return bool
     */
    public function setKey(string $key, $value, $connection = null): bool
    {
        if (is_null($connection)) {
            $connection = $this->getDefaultConnection();
        }

        if ($this->hasKey($key, $connection)) {
            return $this->updateKey($key, $value, $connection);
        }
        return $this->createKey($key, $value, $connection);
    }

    public function getDefaultConnection(): string
    {
        return config('settings.system_connection');
    }
    /**
     * Checks whether a key exists or not using a connection.
     * @param string $key
     * @param string $connection
     * @return bool
     */
    public function hasKey(string $key, $connection = null): bool
    {
        if (is_null($connection)) {
            $connection = $this->getDefaultConnection();
        }

        $data = $this->getConfiguredModel($connection)
            ->where('key', $key)
            ->first();

        if (is_null($data)) {
            return false;
        }

        return true;
    }

    /**
     * Creates a key using a connection. Assumes the key doesn't already exist.
     * @param string $key
     * @param        $value
     * @param string $connection
     * @return bool
     */
    public function createKey(string $key, $value, $connection = null): bool
    {
        if (is_null($connection)) {
            $connection = $this->getDefaultConnection();
        }

        $serializable = SerializerFactory::createFromValue($value);

        $model = $this->getConfiguredModel($connection);
        $model->key = $key;

        if ($serializable->shouldEncryptData()) {
            $model->value = encrypt($serializable->serialize($value));
        } else {
            $model->value = $serializable->serialize($value);
        }

        $model->type_alias = $serializable->getTypeAlias();
        $success = $model->save();

        if ($success) {
            event(new SettingCreated($key, $value, $connection));
        }

        return $success;
    }

    /**
     * Updates a key using a connection. Assumes the key already exists.
     * @param string $key
     * @param        $value
     * @param string $connection
     * @return bool
     */
    public function updateKey(string $key, $value, $connection = null): bool
    {
        if (is_null($connection)) {
            $connection = $this->getDefaultConnection();
        }

        $serializable = SerializerFactory::createFromValue($value);

        $model = $this->getConfiguredModel($connection);
        $data = $model->where('key', $key)->first();

        $data->setTable(config('settings.table_name'));
        $data->type_alias = $serializable->getTypeAlias();

        if ($serializable->shouldEncryptData()) {
            $data->value = encrypt($serializable->serialize($value));
        } else {
            $data->value = $serializable->serialize($value);
        }

        $success = $data->save();

        if ($success) {
            event(new SettingUpdated($key, $value, $connection));
        }

        return $success;
    }

    /**
     * Get a key from a connection.
     * @param string $key
     * @param string $connection
     * @return null
     */
    public function getKey(string $key, $connection = null)
    {
        if (is_null($connection)) {
            $connection = $this->getDefaultConnection();
        }

        $model = $this->getConfiguredModel($connection);
        $data = $model->where('key', $key)->first();

        if (is_null($data)) {
            if (config('settings.exception_if_key_not_found')) {
                throw new KeyNotFoundException($key);
            }
            return null;
        }

        $serializable = SerializerFactory::createFromTypeAlias($data->type_alias);

        if ($serializable->shouldEncryptData()) {
            $value = $serializable->unserialize(decrypt($data->value));
        } else {
            $value = $serializable->unserialize($data->value);
        }

        event(new SettingRead($key, $value, $connection));

        return $value;
    }

    /**
     * Add a new serizlizer to the available serializers.
     * @param string $serializer Serializer's FQCN
     */
    public function pushSerializer(string $serializer)
    {
        $serializers = config('settings.serializers');
        $serializers[] = $serializer;
        config(['settings.serializers' => $serializers]);
    }

    /**
     * Return an array with all the currently available serializers.
     * @return array
     */
    public function getSerializers(): array
    {
        return config('settings.serializers');
    }

    /**
     * Get a fully configured Settings model.
     * @param string $connection
     * @return SettingsModel
     */
    public function getConfiguredModel($connection = null): SettingsModel
    {
        if (is_null($connection)) {
            $connection = $this->getDefaultConnection();
        }

        $model = new SettingsModel;
        $model->setConnection(config("settings.{$connection}_connection"));
        $model->setTable(config('settings.table_name'));
        return $model;
    }
}
