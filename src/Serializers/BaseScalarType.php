<?php

namespace Poisa\Settings\Serializers;

use Illuminate\Support\Str;

/**
 * Base scalar type for the package's classes.
 * NOTE: Userland classes SHOULD NOT extend this class. Instead, they shold implement
 * the Serializable interface.
 * Class BaseScalarType
 * @package Poisa\Settings\Serializables
 */
abstract class BaseScalarType implements Serializer
{
    public function getTypes(): array
    {
        return [$this->getTypeAlias()];
    }

    public function getTypeAlias(): string
    {
        $classTokens = explode('\\', static::class);

        // $className will now be Scalar<data type>, eg. ScalarInteger, ScalarFloat, etc...
        $className = array_pop($classTokens);

        $scalarType = Str::after($className, 'Scalar');

        return strtolower($scalarType);
    }

    public function shouldEncryptData(): bool
    {
        return config('settings.encrypt_known_types');
    }

    public function serialize($data): string
    {
        return serialize($data);
    }

    public function unserialize($data)
    {
        return unserialize($data);
    }
}
