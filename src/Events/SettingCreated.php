<?php

namespace Poisa\Settings\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;

class SettingCreated
{
    use Dispatchable, SerializesModels;

    /**
     * The setting's key
     * @var string
     */
    public $key;

    /**
     * The setting's value (not encrypted)
     * @var mixed
     */
    public $value;

    /**
     * Database connection
     * @var string
     */
    public $connection;

    /**
     * @param string $key
     * @param mixed  $value
     * @param string $connection
     */
    public function __construct(string $key, $value, string $connection)
    {
        $this->key = $key;
        $this->value = $value;
        $this->connection = $connection;
    }
}
