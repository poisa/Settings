<?php

namespace Poisa\Settings\Tests;

use Poisa\Settings\Events\SettingCreated;
use Poisa\Settings\Events\SettingRead;
use Poisa\Settings\Events\SettingUpdated;
use Poisa\Settings\Models\Settings as SettingsModel;
use Poisa\Settings\Tests\Serializers\FooSerializer;
use Illuminate\Support\Str;
use Settings;
use DB;
use Illuminate\Support\Facades\Event;

/**
 * @coversDefaultClass \Poisa\Settings\Settings
 */
class SettingsTest extends TestCase
{
    /**
     * @covers ::getConfiguredModel
     */
    public function testGetConfiguredModel()
    {
        $connection = 'system';
        $table = config('settings.table_name');
        $model = Settings::getConfiguredModel($connection);
        $this->assertInstanceOf(SettingsModel::class, $model);
        $this->assertEquals($connection, $model->getConnectionName());
        $this->assertEquals($table, $model->getTable());
    }

    /**
     * @dataProvider knownTypesProvider
     * @covers ::getKey
     * @covers ::setKey
     * @covers ::getSystemKey
     * @covers ::setSystemKey
     * @param bool $shouldEncrypt
     * @param      $insertValue
     * @param      $updateValue
     */
    public function testSystemGetterSetter(bool $shouldEncrypt, $insertValue, $updateValue)
    {
        config(['settings.encrypt_known_types' => $shouldEncrypt]);
        $key = Str::random(10);
        $this->assertTrue(Settings::setSystemKey($key, $insertValue));
        $this->assertSame($insertValue, Settings::getSystemKey($key));

        // Update an existing key
        $this->assertTrue(Settings::setSystemKey($key, $updateValue));
        $this->assertSame($updateValue, Settings::getSystemKey($key));
    }

    /**
     * @dataProvider knownTypesProvider
     * @covers ::getKey
     * @covers ::setKey
     * @covers ::getTenantKey
     * @covers ::setTenantKey
     * @param bool $shouldEncrypt
     * @param      $insertValue
     * @param      $updateValue
     */
    public function testTenantGetterSetter(bool $shouldEncrypt, $insertValue, $updateValue)
    {
        config(['settings.encrypt_known_types' => $shouldEncrypt]);
        $key = Str::random(10);
        $this->assertTrue(Settings::setTenantKey($key, $insertValue));
        $this->assertSame($insertValue, Settings::getTenantKey($key));

        // Update an existing key
        $this->assertTrue(Settings::setTenantKey($key, $updateValue));
        $this->assertSame($updateValue, Settings::getTenantKey($key));
    }

    /**
     * @covers ::getKey
     */
    public function testGetKeyReturnsNull()
    {
        config(['settings.exception_if_key_not_found' => false]);
        $this->assertNull(Settings::getKey('foo', 'system'));
    }

    /**
     * @covers ::getKey
     * @expectedException \Poisa\Settings\Exceptions\KeyNotFoundException
     */
    public function testGetKeyThrowsException()
    {
        config(['settings.exception_if_key_not_found' => true]);
        $this->assertNull(Settings::getKey('foo', 'system'));
    }

    /**
     * @dataProvider knownTypesProvider
     * @covers ::getKey
     * @covers ::setKey
     * @covers ::getSystemKey
     * @covers ::setSystemKey
     */
    public function testEncryptKnownTypes($insertValue, $updateValue)
    {
        config(['settings.encrypt_known_types' => true]);
        $key = 'foo';
        Settings::setSystemKey($key, $insertValue);

        $row = DB::connection('system')
            ->table(config('settings.table_name'))
            ->where('key', $key)
            ->first();

        decrypt($row->value);

        // We don't care that the decrypted value is valid; that's taken care in another test.
        // Right now we know that if decrypt() fails it will throw
        // Illuminate\Contracts\Encryption\DecryptException. So if it doesn't, then the test passed.
        $this->assertTrue(true);
    }

    /**
     * @dataProvider knownTypesProvider
     * @covers ::getKey
     * @covers ::setKey
     * @covers ::getSystemKey
     * @covers ::setSystemKey
     * @expectedException \Illuminate\Contracts\Encryption\DecryptException
     */
    public function testKnownTypesAreNotEncrypted($insertValue, $updateValue)
    {
        config(['settings.encrypt_known_types' => false]);
        $key = 'foo';
        Settings::setSystemKey($key, $insertValue);

        $row = DB::connection('system')
            ->table(config('settings.table_name'))
            ->where('key', $key)
            ->first();

        decrypt($row->value);
    }

    /**
     * @covers ::pushSerializer
     */
    public function testPushSerializer()
    {
        $initialSerializers = Settings::getSerializers();
        Settings::pushSerializer(FooSerializer::class);
        $this->assertCount(count($initialSerializers) + 1, Settings::getSerializers());
        $this->assertContains(FooSerializer::class, Settings::getSerializers());
    }
    /**
     * @covers ::getSerializers
     */
    public function testGetSerializers()
    {
        $serializers = Settings::getSerializers();
        $this->assertInternalType('array', $serializers);
        $this->assertCount(count(config('settings.serializers')), $serializers);
    }

    /**
     * @covers ::hasKey
     */
    public function testHasKeyReturnsFalse()
    {
        $this->assertFalse(Settings::hasKey('foo', 'system'));
    }

    /**
     * @covers ::hasKey
     */
    public function testHasKeyReturnsTrue()
    {
        Settings::setKey('foo', 'bar', 'system');
        $this->assertTrue(Settings::hasKey('foo', 'system'));
    }

    /**
     * @covers ::createKey
     */
    public function testCreateKeyReturnsTrueWithEncryption()
    {
        config(['settings.encrypt_known_types' => true]);
        $key = 'foo';
        $value = 'bar';
        Settings::createKey($key, $value, 'system');

        $row = DB::connection('system')
            ->table(config('settings.table_name'))
            ->where('key', $key)
            ->first();

        $this->assertNotEmpty($row->value);
    }

    /**
     * @covers ::createKey
     */
    public function testCreateKeyReturnsTrueWithoutEncryption()
    {
        config(['settings.encrypt_known_types' => false]);
        $key = 'foo';
        $value = 'bar';
        Settings::createKey($key, $value, 'system');

        $row = DB::connection('system')
            ->table(config('settings.table_name'))
            ->where('key', $key)
            ->first();

        $this->assertNotEmpty($row->value);
    }

    /**
     * @covers ::updateKey
     */
    public function testUpdateKeyReturnsTrueWithEncryption()
    {
        config(['settings.encrypt_known_types' => true]);
        Settings::createKey('foo', 'original value', 'system');
        Settings::updateKey('foo', 'updated value', 'system');

        $this->assertEquals('updated value', Settings::getKey('foo', 'system'));
    }

    /**
     * @covers ::updateKey
     */
    public function testUpdateKeyReturnsTrueWithoutEncryption()
    {
        config(['settings.encrypt_known_types' => false]);
        Settings::createKey('foo', 'original value', 'system');
        Settings::updateKey('foo', 'updated value', 'system');

        $this->assertEquals('updated value', Settings::getKey('foo', 'system'));
    }

    /**
     * @covers ::createKey
     */
    public function testEventSettingCreatedIsDispatched()
    {
        Event::fake();

        $key = 'foo';
        $value = 'bar';
        $connection = 'system';

        Settings::setKey($key, $value, $connection);

        Event::assertDispatched(SettingCreated::class, function($e) use ($key, $value, $connection) {
            return $e->key == $key
                && $e->value == $value
                && $e->connection == $connection
                && $e instanceof SettingCreated;
        });
    }

    /**
     * @covers ::createKey
     */
    public function testEventSettingUpdatedIsDispatched()
    {
        Event::fake();

        $key = 'foo';
        $value = 'bar';
        $connection = 'system';

        Settings::setKey($key, $value, $connection); // create
        Settings::setKey($key, $value, $connection); // update

        Event::assertDispatched(SettingUpdated::class, function($e) use ($key, $value, $connection) {
            return $e->key == $key
                && $e->value == $value
                && $e->connection == $connection
                && $e instanceof SettingUpdated;
        });
    }

    /**
     * @covers ::createKey
     */
    public function testEventSettingReadIsDispatched()
    {
        Event::fake();

        $key = 'foo';
        $value = 'bar';
        $connection = 'system';

        Settings::setKey($key, $value, $connection); // create
        Settings::getKey($key, $connection); // read

        Event::assertDispatched(SettingRead::class, function($e) use ($key, $value, $connection) {
            return $e->key == $key
                && $e->value == $value
                && $e->connection == $connection
                && $e instanceof SettingRead;
        });
    }

    public function knownTypesProvider()
    {
        return [
            [true, null, null],
            [false, null, null],
            [true, $this->getRandomInt(), $this->getRandomInt()],
            [true, $this->getRandomDouble(), $this->getRandomDouble()],
            [true, $this->getRandomString(), $this->getRandomString()],
            [true, $this->getRandomBool(), $this->getRandomBool()],
            [true, $this->getRandomArray(), $this->getRandomArray()],
            [false, $this->getRandomInt(), $this->getRandomInt()],
            [false, $this->getRandomDouble(), $this->getRandomDouble()],
            [false, $this->getRandomString(), $this->getRandomString()],
            [false, $this->getRandomBool(), $this->getRandomBool()],
            [false, $this->getRandomArray(), $this->getRandomArray()],
        ];
    }

    private function getRandomInt(): int
    {
        return mt_rand(0, 100000);
    }

    private function getRandomDouble(): float
    {
        return mt_rand() / mt_getrandmax();
    }

    private function getRandomString($chars = 10): string
    {
        return Str::random($chars);
    }

    private function getRandomBool(): bool
    {
        return (bool)random_int(0, 1);
    }

    /**
     * Return an array with some random data.
     * @return array
     */
    private function getRandomArray()
    {
        $array = [];

        for ($i = 1; $i <= random_int(1, 3); $i++) {
            $array[Str::random(10)] = Str::random(10);
        }

        return $array;
    }
}