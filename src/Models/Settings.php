<?php

namespace Poisa\Settings\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Settings
 * @package Poisa\Settings\Models
 * @property $key string
 * @property $value string
 * @property $type_alias string
 */
class Settings extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;
}
