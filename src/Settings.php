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
    public function getSystemKey(string $key)
    {
        return $this->getKey($key, 'system');
    }

    public function getTenantKey(string $key)
    {
        return $this->getKey($key, 'tenant');
    }

    public function setSystemKey(string $key, $value): bool
    {
        return $this->setKey($key, $value, 'system');
    }

    public function setTenantKey(string $key, $value): bool
    {
        return $this->setKey($key, $value, 'tenant');
    }

    public function setKey(string $key, $value, string $connection): bool
    {
        $serializable = SerializerFactory::createFromValue($value);

        $connection = config("settings.{$connection}_connection");
        $table = config('settings.table_name');

        $data = (new SettingsModel)
            ->setConnection($connection)
            ->setTable($table)
            ->where('key', $key)
            ->first();

        // Update key
        if (!is_null($data)) {
            // This setTable() must be set here even if already set above.
            // @see https://github.com/laravel/framework/issues/2318
            $data->setTable($table);

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

        // Create key
        $model = (new SettingsModel)
            ->setConnection($connection)
            ->setTable($table);
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

    public function getKey(string $key, string $connection)
    {
        $model = new SettingsModel;
        $model->setConnection(config("settings.{$connection}_connection"));
        $model->setTable(config('settings.table_name'));
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
     * Add a new serizlizer to the available serializers
     * @param string $serializer Serializer's FQCN
     */
    public function pushSerializer(string $serializer)
    {
        $serializers = config('settings.serializers');
        $serializers[] = $serializer;
        config(['settings.serializers' => $serializers]);
    }

    public function getSerializers():array
    {
        return config('settings.serializers');
    }
}
