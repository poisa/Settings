<?php

namespace Poisa\Settings\Tests;

use Poisa\Settings\Tests\Serializers\FooSerializer;
use Settings;
use DB;
use Poisa\Settings\Serializers\{
    ScalarBoolean, ScalarNull, ScalarString, ScalarDouble, ScalarInteger, ArrayType, Serializer, SerializerFactory
};

/**
 * @coversDefaultClass \Poisa\Settings\Serializers\SerializerFactory
 */
class SerializerFactoryTest extends TestCase
{

    /**
     * @covers ::createFromValue
     * @dataProvider valueProvider
     * @param Serializer   $serializable
     * @param              $value
     */
    public function testCreateFromValue(Serializer $serializable, $value)
    {
        Settings::pushSerializer(FooSerializer::class);
        $this->assertInstanceOf(get_class($serializable), SerializerFactory::createFromValue($value));
    }

    /**
     * @covers ::createFromValue
     * @expectedException \Poisa\Settings\Exceptions\UnknownDataType
     */
    public function testCreateFromValueThrowsExceptionForUnknownTypes()
    {
        $resource = fopen('.', 'r');
        SerializerFactory::createFromValue($resource);
    }

    /**
     * @covers ::createFromTypeAlias
     * @dataProvider dataTypeProvider
     * @param Serializer $serializable
     * @param string     $dataType
     */
    public function testCreateFromTypeAlias(Serializer $serializable, string $dataType)
    {
        $this->assertInstanceOf(get_class($serializable), SerializerFactory::createFromTypeAlias($dataType));
    }

    /**
     * @covers ::createFromTypeAlias
     * @expectedException \Poisa\Settings\Exceptions\ClassDoesNotExist
     */
    public function testCreateFromTypeAliasThrowsExceptionIfClassDoesNotExist()
    {
        Settings::pushSerializer('Some\\Missing\\Class');
        SerializerFactory::createFromTypeAlias('foo');
    }

    /**
     * @covers ::createFromTypeAlias
     * @expectedException \Poisa\Settings\Exceptions\NotASerializableClass
     */
    public function testCreateFromTypeAliasThrowsExceptionIfClassIsNotSerializable()
    {
        // TestCase is a class that exists and can be resolved but it does not implement Serializer
        Settings::pushSerializer(TestCase::class);
        SerializerFactory::createFromTypeAlias('foo');
    }

    /**
     * @covers ::createFromTypeAlias
     * @expectedException \Poisa\Settings\Exceptions\UnknownDataType
     */
    public function testCreateFromTypeAliasThrowsExceptionIfUnknownDataTypeIsPassed()
    {
        SerializerFactory::createFromTypeAlias('foo');
    }

    /**
     * @covers ::getSerializableClass
     * @dataProvider dataTypeProvider
     */
    public function testGetSerializableClass(Serializer $serializable, string $dataType)
    {
        $this->assertSame(get_class($serializable), SerializerFactory::getSerializableClass($dataType));
    }

    /**
     * @covers ::getSerializableClass
     * @expectedException \Poisa\Settings\Exceptions\ClassDoesNotExist
     */
    public function testGetSerializableClassThrowsExceptionIfClassDoesNotExist()
    {
        Settings::pushSerializer('Some\\Missing\\Class');
        SerializerFactory::getSerializableClass('foo');
    }

    /**
     * @covers ::getSerializableClass
     * @expectedException \Poisa\Settings\Exceptions\NotASerializableClass
     */
    public function testGetSerializableClassThrowsExceptionIfClassIsNotSerializable()
    {
        Settings::pushSerializer(Testcase::class);
        SerializerFactory::getSerializableClass('foo');
    }

    /**
     * @covers ::getSerializableClass
     * @expectedException \Poisa\Settings\Exceptions\UnknownDataType
     */
    public function testGetSerializableClassThrowsExceptionIfUnknownDataTypeIsPassed()
    {
        SerializerFactory::getSerializableClass('foo');
    }

    public function dataTypeProvider()
    {
        return [
            [new ScalarNull, 'NULL'],
            [new ScalarBoolean, 'boolean'],
            [new ScalarString, 'string'],
            [new ScalarDouble, 'double'],
            [new ScalarInteger, 'integer'],
            [new ArrayType, 'array'],
        ];
    }

    public function valueProvider()
    {
        return [
            [new ScalarBoolean, true],
            [new ScalarString, 'string'],
            [new ScalarDouble, 3.14],
            [new ScalarInteger, 89],
            [new ArrayType, []],
            [new FooSerializer, new \stdClass]
        ];
    }
}
