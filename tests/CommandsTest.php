<?php

namespace Poisa\Settings\Tests;

use Poisa\Settings\Models\Settings as SettingsModel;
use Poisa\Settings\Tests\Serializers\FooSerializer;
use Settings;
use DB;
use Artisan;

class CommandsTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        Settings::pushSerializer(FooSerializer::class);
    }

    /**
     * @covers \Poisa\Settings\Commands\SetCommand::handle
     * @dataProvider keyValueProvider
     */
    public function testSetCommandWithCustomDatabase($key, $value, $database)
    {
        $this->artisan('settings:set', ['key' => $key, 'value' => $value, '--database' => $database]);
        $this->assertEquals($value, Settings::getKey($key, $database));
    }

    /**
     * @covers \Poisa\Settings\Commands\GetCommand::handle
     * @dataProvider keyValueProvider
     */
    public function testGetCommand($key, $value, $database)
    {
        Settings::setKey($key, $value);
        $exitCode = $this->artisan('settings:get', ['key' => $key, '--database' => $database]);
        $this->assertEquals(0, $exitCode);

        $expectedOutput = trim(print_r($value, true));
        $actualOutput = trim(Artisan::output());
        $this->assertEquals($expectedOutput, $actualOutput);
    }

    /**
     * @return array key, value, database connection
     */
    public function keyValueProvider()
    {
        $object = new \stdClass;
        $object->foo = 'bar';

        return [
            ['key', 'value', null],
            ['key', 'value', 'system'],
            ['key', $object, null],
        ];
    }
}
