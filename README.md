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

When running ia multi-tenant system (like in scenario #2 described above), Settings provide the following shortcuts:

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