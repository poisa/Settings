[![Build Status](https://travis-ci.org/poisa/Settings.svg?branch=master)](https://travis-ci.org/poisa/Settings) 
[![codecov](https://codecov.io/gh/poisa/Settings/branch/master/graph/badge.svg)](https://codecov.io/gh/poisa/Settings)
[![Maintainability](https://api.codeclimate.com/v1/badges/2facfc8aaea8faeb5e45/maintainability)](https://codeclimate.com/github/poisa/Settings/maintainability)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/afb3c332-6fc9-4329-a933-2b7c244f467d/small.png)](https://insight.sensiolabs.com/projects/afb3c332-6fc9-4329-a933-2b7c244f467d) 

# Settings: A Laravel 5 multi-tenant settings manager

### Package objective
To be able to store custom configuration items in a database in a single or multi-tenant environment either in plain text, encrypted form, or any other customizable format. By configuration I am not referring to Laravel's configuration but rather to your domain-specific configuration. 

There are 3 specific scenarios where this package might come in handy:

1. Multi-tenant systems where you deploy the code to one server and it connects to a different tenant database depending on domain rules (eg. different users connect to different databases).
2. Same as #1 except you add a main database that you always connect to. Take a CMS for example, where you have the CMS's own database (a.k.a. the `system` database) and then you also connect to each of your client's databases (a.k.a the `tenant` database). In this scenario you work simultaneously with both databases.
3. A single-tenant website with just one database. 

This package really shines when you need to store odd-ball data for which you would not necessarily want to create a separate table.

**Think of this package as a key-value store that knows about data types -even custom ones- that can encrypt data at rest and can also fire events.**

# Installation

Installation can be done automatically using [Composer](https://getcomposer.org).

    composer require poisa/settings

Next you will need to publish the package configuration. 

    php artisan vendor:publish
    
You will be asked to chose a package. Find `Poisa\Settings\SettingsServiceProvider` and select it. This will create a new configuration file in your project: `config/settings.php`. You will need to edit it and choose the config options that suit your project before continuing.

Now you will need to execute the migrations found in the package. These will create the database tables that the package will use to store your settings.

    php artisan migrate
    
> Important: The  migrations will run in your default database connection. This is fine if you are only using one database but if you will be using the package in many databases then you will need to run the migrations in all your databases:

    php artisan migrate --database=<your database>

Alternatively you can use your own SQL manager software to copy the table over to the databases and servers that you need.

# Use

The simplest use case assumes that you only have one database and no custom data types.

```php
<?php
// Include the facade at the top of your file
use Settings;

// Set a key
Settings::setKey('key', 123);

// Get a key
$value = Settings::getKey('key');
```

Just like in any other key-value store, `Settings::setKey()` expects a string key as the first parameter and the value to store as the second.

When running in a multi-tenant system (like in scenario #2 described above), Settings provides the following shortcuts:

```php
<?php
use Settings;

// Using the configured 'system' connection
Settings::setSystemKey('key', 123);
$value = Settings::getSystemKey('key');


// Using the configured 'tenant' connection
Settings::setTenantKey('key', 123);
$value = Settings::getTenantKey('key');
``` 

Alternatively you can pass the connection name as the last parameter:

```php
<?php
use Settings;

Settings::setKey('key', 123, 'sqlite');
$value = Settings::getKey('key', 'sqlite');
```

To check whether a key exists you can use the `hasKey()` method:

```php
<?php
use Settings;

var_dump(Settings::hasKey('key')); // bool(false)
Settings::setKey('key', 123);
var_dump(Settings::hasKey('key')); // bool(true)
```

It may come a point where you may want to query the settings table manually but this might prove difficult because not only the connection name but also the table name can be configured. Settings comes with a method that will give you an Eloquen model properly configured that you may use to query or alter the table manually:

```php
<?php
use Settings;

// Using default connection name
$eloquentModel = Settings::getConfiguredModel();

// Using custom connection name
$eloquentModel = Settings::getConfiguredModel('mysql');
```

# Known data types

By default, Settings can store the following types:

* String
* Boolean
* Double
* Integer
* Null
* Array

This means that Settings will store and retrieve the exact data types that you give it:

```php
<?php
Settings::setKey('key', 123);
var_dump(Settings::getKey('key') === 123);   // bool(true)
var_dump(Settings::getKey('key') === '123'); // bool(false)

Settings::setKey('key', null);
var_dump(Settings::getKey('key') === null); // bool(true)
var_dump(Settings::getKey('key') === '');   // bool(false)
```

# Custom data types

Storing simple types might not be enough in all cases. You can also teach Settings to work with your custom data types. For example, let's say you have a class that you use to store user preferences. Let's just use a very minimalist class with no getters/setters and validation of any kind for the sake of brevity:

```php
<?php

class UserPreferences
{
    public $backgroundColor;
    public $themeName;
}
```

What would it take so that we could do something like this?

```php
<?php
use UserPreferences;
use Settings;

$prefs = new UserPreferences;
$prefs->backgroundColor = '#3226D6';
$prefs->themeName = 'simple';
Settings::setKey('userPrefs', $prefs);

// and then...

$prefs = Settings::getKey('userPrefs');
var_dump(get_class($prefs) == UserPreferences::class); // bool(true)
```

If you do this, you will get an exception `Poisa\Settings\Exceptions\UnknownDataType` with the message `No serializable registered to work with UserPreferences`. This means that we need to create and register a new Serializer so that Settings can know how to work with this class. 

So, to teach Settings how to work with your own data types you need to:

1. Create a Serializer class.
2. Register it with Settings.

###### Create a Serializer class
A Serializer class is just a regular class that implements the methods in the `Poisa\Settings\Serializers\Serializer` interface. It is recommended that this class be a standalone class whose sole purpose is to serialize and unserialize our own data type but in reality it can be any class, even our own data class.

In this example we'll create a dedicated class. Since our data type class is called `UserPreferences`, let's call the Serializer class `UserPreferencesSerializer`. We'll put this in the root namespace but you will want to create a namespace for all your Serializer classes just to keeps things tidy.

> Note: All the methods in the Serializer interface have been thoroughly documented in the source code. For the sake of brevity, all the method comments have been stripped in the following example and only comments relevant to our own implementation were left.

Now our class looks like this:

```php
<?php

use Poisa\Settings\Serializers\Serializer;

class UserPreferencesSerializer implements Serializer
{
    public $backgroundColor;
    public $themeName;

    public function getTypes(): array
    {
        // Return the name of the data type (aka class) that this serializer knows how to serialize. If this serializer
        // is generic in nature and know how to serialize multiple classes then you can return an array with multiple
        // values.
        return [UserPreferences::class];
    }

    public function getTypeAlias(): string
    {
        // This string gets saved in the database so that when we unserialize the row, we know what serializer class
        // to use to unserialize it. You could easily return the same as getType() and
        // it would work fine, except that you will want to decouple your class names from your database as much as
        // possible. If you return the name of the class here and in the future you rename your class to something
        // else, then you'd need to rename all the settings in the database to whatever your class is now named.
        // If you just return a simple string with something representative of what the value is instead of the class
        // name, then renaming the class will incur in no extra work.
        return 'user-preferences';
    }

    public function shouldEncryptData(): bool
    {
        // Yes, we want Settings to encrypt our data at rest.
        return true;
    }

    public function serialize($data): string
    {
        // $data is the instance of UserPreferences we want to serialize.
        // Return a simple string that we can save in the database.
        return json_encode([
            'backgroundColor' => $data->backgroundColor,
            'themeName'       => $data->themeName
        ]);
    }

    public function unserialize($data)
    {
        // Take the string we stored with serialize() and reverse the process.
        $decodedData = json_decode($data);
        $prefs = new UserPreferences;
        $prefs->backgroundColor = $decodedData->backgroundColor;
        $prefs->themeName = $decodedData->themeName;
        return $prefs;
    }
}
```

@TODO serialize() warning

###### Register it with Settings

The last step is to register the new serializer class with the Settings package. To do this, edit `config/settings.php` and add the new serializer to the `serializers` key:

```php
    'serializers' => [
        Poisa\Settings\Serializers\ScalarString::class,
        Poisa\Settings\Serializers\ScalarBoolean::class,
        Poisa\Settings\Serializers\ScalarDouble::class,
        Poisa\Settings\Serializers\ScalarInteger::class,
        Poisa\Settings\Serializers\ScalarNull::class,
        Poisa\Settings\Serializers\ArrayType::class,
        UserPreferencesSerializer::class,
    ],
```

That's it. Settings now knows how to store and retrieve UserPreferences!

As you can see, Settings already knows how to work with many data types. If you would like to change how Settings works with a particular data type, you'd have to de-register its Serializer and register your own.

> Note: If you de-register a default data type and you try to set a key with that data type, Settings will throw an exception. 

# Events

Settings fires different kinds of event depending on what it is doing. You can subscribe to these events to be notified when they happen. This is useful in cases where you need to know what happens in your database. Let's say you need to generate an audit trail of everything that happens in your database (every read, write, and update).

You can listen for Settings events in Laravel's EventServiceProvider:

```php
<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Poisa\Settings\Events\SettingCreated;
use Poisa\Settings\Events\SettingRead;
use Poisa\Settings\Events\SettingUpdated;

class EventServiceProvider extends ServiceProvider
{
    public function boot()
    {
        parent::boot();

        Event::listen(SettingCreated::class, function ($event) {
            // $event->key;
            // $event->value;
            // $event->connection;
        });
        Event::listen(SettingUpdated::class, function ($event) {
            // $event->key;
            // $event->value;
            // $event->connection;
        });
        Event::listen(SettingRead::class, function ($event) {
            // $event->key;
            // $event->value;
            // $event->connection;
        });
    }
}
```

> Note: $event-value can be anything you sent to Settings; a scalar value, array, or any kind of object Settings knows how to work with. Make sure you don't assume anything about the value before working with it. Also note that the value is always unserialized when you receive it in events.

# CLI Commands

Settings comes with a few comands that will help you inspect the settings table from the command line. The reason for having these commands is because looking at the database table directly will not be very useful when the data is encrypted. These commands perform the decryption for you.

To list all available commands for this package you can run:

    php artisan list settings

Currently the following commands are available:

* **settings:get**  Get a key from the database and dump it to stdout (decrypting it if necessary)
* **settings:set**  Save a key to the database

> Note: Saving a key using the CLI will only be able to store strings and numeric values. Storing other types including custom ones will only be possible via code.
