<?php

namespace Poisa\Settings\Tests;

use Illuminate\Foundation\Application;
use Poisa\Settings\Facades\Settings;
use Poisa\Settings\SettingsServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.connections.system', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        $app['config']->set('database.connections.tenant', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    /**
     * Load package service provider
     * @param  Application $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [SettingsServiceProvider::class];
    }

    /**
     * Load package alias
     * @param  Application $app
     * @return array
     */
    protected function getPackageAliases($app)
    {
        return [
            'Settings' => Settings::class,
        ];
    }

    public function setUp()
    {
        parent::setUp();

        $this->artisan('migrate', ['--database' => 'system']);
        $this->artisan('migrate', ['--database' => 'tenant']);
    }
}