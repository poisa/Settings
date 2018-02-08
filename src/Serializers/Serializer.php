<?php

namespace Poisa\Settings\Serializers;

interface Serializer
{
    /**
     * Return all the types that the serializer will be able to serialize. When you give Settings a value to store, it
     * will look in the getTypes() output of all its registered serializers to know if it can serialize the value or
     * not.
     * For example, if you have written a serializer that knows how to serialize \My\FooClass then this method would
     * return \My\FooClass::class.
     *
     * @return array of strings
     */
    public function getTypes(): array;

    /**
     * What to call the type when storing it in the database. In other words, what goes in the "type_alias" field.
     * When deserializing a value from the database, Settings will look for the serializer that knows how to work with
     * a particular type alias and use it to deserialize a value.
     * @return string
     */
    public function getTypeAlias(): string;

    /**
     * Should this data be encrypted at rest or not?
     * @return bool
     */
    public function shouldEncryptData(): bool;

    /**
     * Take whatever comes in $data and serialize it to a string.
     * @param $data
     * @return string
     */
    public function serialize($data): string;

    /**
     * Take the output of serialize() and convert it into whatever the original form was.
     * @param $data
     * @return mixed
     */
    public function unserialize($data);
}
