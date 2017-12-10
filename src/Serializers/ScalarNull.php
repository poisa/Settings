<?php

namespace Poisa\Settings\Serializers;

class ScalarNull extends BaseScalarType implements Serializer
{
    public function getTypes(): array {
        return ['NULL'];
    }

    public function getTypeAlias(): string {
        return 'NULL';
    }
}
