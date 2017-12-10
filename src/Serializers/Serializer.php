<?php

namespace Poisa\Settings\Serializers;

interface Serializer
{
    public function getTypes(): array;

    public function getTypeAlias(): string;

    public function shouldEncryptData(): bool;

    public function serialize($data): string;

    public function unserialize($data);
}
