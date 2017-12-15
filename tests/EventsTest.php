<?php

namespace Poisa\Settings\Tests;

use Poisa\Settings\Events\SettingCreated;
use Poisa\Settings\Events\SettingRead;
use Poisa\Settings\Events\SettingUpdated;

class EventsTest extends TestCase
{
    /**
     * @covers \Poisa\Settings\Events\SettingCreated
     * @covers \Poisa\Settings\Events\SettingRead
     * @covers \Poisa\Settings\Events\SettingUpdated
     * @dataProvider eventProvider
     */
    public function testEventPublicProperties($eventClass)
    {
        $key = 'foo';
        $value = 'bar';
        $connection = 'system';
        $event = new $eventClass($key, $value, $connection);
        $this->assertSame($key, $event->key);
        $this->assertSame($value, $event->value);
        $this->assertSame($connection, $event->connection);
    }

    public function eventProvider()
    {
        return [
            [SettingCreated::class],
            [SettingRead::class],
            [SettingUpdated::class],
        ];
    }
}
