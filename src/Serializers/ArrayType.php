<?php

namespace Poisa\Settings\Serializers;

class ArrayType extends BaseScalarType implements Serializer
{
    public function getTypes(): array
    {
        return ['array'];
    }

    public function getTypeAlias(): string
    {
        return 'array';
    }
}
