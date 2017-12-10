<?php

namespace Poisa\Settings\Tests\Serializers;

use Poisa\Settings\Serializers\Serializer;

class FooSerializer implements Serializer
{
    public function getTypes(): array
    {
        return [\stdClass::class];
    }

    public function getTypeAlias(): string
    {
        return 'stdClass';
    }

    public function shouldEncryptData(): bool
    {
        return false;
    }

    public function serialize($data): string
    {
        if (!$data instanceof \stdClass) {
            throw new \RuntimeException('Not an instance of stdClass');
        }
        return json_encode($data);
    }

    public function unserialize($data)
    {
        return json_decode($data);
    }
}
