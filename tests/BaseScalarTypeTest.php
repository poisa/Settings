<?php

namespace Poisa\Settings\Tests;

use Settings;
use DB;
use Poisa\Settings\Models\Settings as SettingsModel;
use Poisa\Settings\Settings as SettingsClass;

use Poisa\Settings\Serializers\{
    ScalarBoolean, ScalarNull, ScalarString, ScalarDouble, ScalarInteger, ArrayType, Serializer
};

/**
 * @coversDefaultClass \Poisa\Settings\Serializers\BaseScalarType
 */
class BaseScalarTypeTest extends TestCase
{

    /**
     * @covers ::getTypeAlias
     * @covers \Poisa\Settings\Serializers\ArrayType::getTypeAlias
     * @covers \Poisa\Settings\Serializers\ScalarNull::getTypeAlias
     * @dataProvider keyNameProvider
     * @param Serializer $serializable
     * @param string     $keyName
     */
    public function testGetTypeAlias(Serializer $serializable, string $keyName)
    {
        $this->assertEquals($keyName, $serializable->getTypeAlias());
    }

    /**
     * @covers ::getTypes
     * @covers \Poisa\Settings\Serializers\ArrayType::getTypes
     * @covers \Poisa\Settings\Serializers\ScalarNull::getTypes
     * @dataProvider keyNameProvider
     * @param Serializer $serializable
     * @param string     $keyName
     */
    public function testGetType(Serializer $serializable, string $keyName)
    {
        // Since the Scalar and Array types have matching types and type aliases
        // we can use the keyNameProvider() as a provider for this test too.
        $this->assertEquals([$keyName], $serializable->getTypes());
    }

    /**
     * @covers ::shouldEncryptData
     * @dataProvider allSerializablesProvider
     * @param Serializer $serializable
     */
    public function testShouldEncryptData(Serializer $serializable)
    {
        config(['settings.encrypt_known_types' => false]);
        $this->assertFalse($serializable->shouldEncryptData());
    }

    /**
     * @covers ::serialize
     * @dataProvider allSerializablesProvider
     * @param Serializer $serializable
     */
    public function testDataIsEncryptedAtRest(Serializer $serializable)
    {
        config(['settings.encrypt_known_types' => true]);

        $settings = resolve(SettingsClass::class);
        $settings->setSystemKey('foo', 'foo');

        $model = $this->getConfiguredModel()->first();
        decrypt($model->value);

        // We don't care that the decrypted value is valid; that's taken care in another test.
        // Right now we know that if decrypt() fails it will throw
        // Illuminate\Contracts\Encryption\DecryptException. So if it doesn't, then the test passed.
        $this->assertTrue(true);
    }

    /**
     * @covers ::unserialize
     * @dataProvider allSerializablesProvider
     * @param Serializer $serializable
     */
    public function testUnserializeWithoutEncryption(Serializer $serializable)
    {
        $serialized = 'i:1;';
        config(['settings.encrypt_known_types' => false]);
        $this->assertEquals(1, $serializable->unserialize($serialized));
    }

    public function allSerializablesProvider()
    {
        return [
            [new ScalarNull],
            [new ScalarBoolean],
            [new ScalarString],
            [new ScalarDouble],
            [new ScalarInteger],
            [new ArrayType],
        ];
    }

    public function keyNameProvider()
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

    public function getConfiguredModel(): SettingsModel
    {
        $model = new SettingsModel;
        $model->setConnection(config("settings.system_connection"));
        $model->setTable(config('settings.table_name'));
        return $model;
    }
}