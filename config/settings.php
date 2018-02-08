<?php

return [
    /*
    |--------------------------------------------------------------------------
    | System database connection
    |--------------------------------------------------------------------------
    |
    | The name of the database connection for the system settings.
    | You can find this in config/database.php under the "connections" key.
    | NOTE: If you are NOT using a multi-tenant system this will be the
    | default connection the package will use if you don't specify one.
    |
    */
    'system_connection' => 'system',

    /*
    |--------------------------------------------------------------------------
    | Tenant database connection
    |--------------------------------------------------------------------------
    |
    | The name of the database connection for the tenant settings.
    | You can find this in config/database.php under the "connections" key.
    |
    */
    'tenant_connection' => 'tenant',

    /*
    |--------------------------------------------------------------------------
    | Table name
    |--------------------------------------------------------------------------
    |
    | The name of the table where you will be storing the settings.
    | This must be the same for the system and the tenants.
    |
    */
    'table_name' => 'settings',

    /*
    |--------------------------------------------------------------------------
    | Throw an exception if key not found
    |--------------------------------------------------------------------------
    |
    | When getting a key from the settings, they key itself might not exist
    | because it hasn't been set yet. This controls what happens in that case.
    | If this is set to true then a Poisa\Settings\Exceptions\KeyNotFoundException
    | will be thrown. If this is set to false, then null will be returned.
    |
    */
    'exception_if_key_not_found' => true,

    /*
    |--------------------------------------------------------------------------
    | Encrypt known types
    |--------------------------------------------------------------------------
    |
    | Any known types will be encrypted by default using Laravel's encrypt()
    | function. For this to work Laravel encryption should be configured.
    | @see https://laravel.com/docs/master/encryption#configuration
    |
    | List of known types: integer, double, string, boolean, and array.
    |
    | Objects are not considered to be known types since they can take any
    | shape or form. Encryption for objects has to be configured on a case
    | by case basis in your own Serializable classes.
    |
    */
    'encrypt_known_types' => true,

    /*
    |--------------------------------------------------------------------------
    | Serializer classes
    |--------------------------------------------------------------------------
    |
    | This is where you register all the classes that will be used to serialize
    | data. There are no built-in serializers but some come registered by default.
    | These are what the documentation refers to as "known types" (because the
    | package already "knows" how to serialize them if you don't change the defaults).
    |
    */
    'serializers' => [
        Poisa\Settings\Serializers\ScalarString::class,
        Poisa\Settings\Serializers\ScalarBoolean::class,
        Poisa\Settings\Serializers\ScalarDouble::class,
        Poisa\Settings\Serializers\ScalarInteger::class,
        Poisa\Settings\Serializers\ScalarNull::class,
        Poisa\Settings\Serializers\ArrayType::class,
    ],
];
