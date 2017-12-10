<?php

namespace Poisa\Settings\Facades;

use Illuminate\Support\Facades\Facade;

class Settings extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Poisa\Settings\Settings::class;
    }
}
