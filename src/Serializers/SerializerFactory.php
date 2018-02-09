<?php


namespace Poisa\Settings\Serializers;

use Poisa\Settings\Exceptions\ClassDoesNotExist;
use Poisa\Settings\Exceptions\NotASerializableClass;
use Poisa\Settings\Exceptions\UnknownDataType;

class SerializerFactory
{
    /**
     * Given a value, detect its type and return an instance that was registered
     * to serialize it.
     * @param $value
     * @return Serializer
     */
    public static function createFromValue($value): Serializer
    {
        if (is_scalar($value) || is_array($value) || is_null($value)) {
            $className = static::getSerializableClass(gettype($value));
            return new $className;
        }

        if (is_object($value)) {
            $className = static::getSerializableClass(get_class($value));
            return new $className;
        }

        throw new UnknownDataType(gettype($value));
    }

    /**
     * Given a type alias, return an instance of a class that was registered
     * to serialize it.
     * @param string $typeAlias
     * @return Serializer
     */
    public static function createFromTypeAlias(string $typeAlias): Serializer
    {
        foreach (config('settings.serializers') as $serializable) {
            if (!class_exists($serializable)) {
                throw new ClassDoesNotExist($serializable);
            }

            $instance = new $serializable;

            if (!$instance instanceof Serializer) {
                throw new NotASerializableClass($serializable);
            }

            if ($instance->getTypeAlias() == $typeAlias) {
                return $instance;
            }
        }
        throw new UnknownDataType("No serializable registered to work with $typeAlias");
    }

    /**
     * Traverse all the registered serializers and find the correct one to use for
     * working with the passed dataType.
     * @param string $dataType
     * @return string
     */
    public static function getSerializableClass(string $dataType): string
    {
        foreach (config('settings.serializers') as $serializable) {
            if (!class_exists($serializable)) {
                throw new ClassDoesNotExist($serializable);
            }

            $instance = new $serializable;

            if (!$instance instanceof Serializer) {
                throw new NotASerializableClass($serializable);
            }

            if (in_array($dataType, $instance->getTypes())) {
                return $serializable;
            }
        }
        throw new UnknownDataType("No serializable registered to work with $dataType");
    }
}
