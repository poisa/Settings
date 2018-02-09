<?php

namespace Poisa\Settings\Commands;

use Illuminate\Console\Command;
use Settings;

class SetCommand extends Command
{
    protected $signature = 'settings:set 
                            {key        : Key }
                            {value      : A string value }
                            {--database= : The database connection to use }';

    protected $description = 'Save a key to the database';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (!$this->option('database')) {
            $database = null;
        } else {
            $database = $this->option('database');
        }

        Settings::setKey($this->argument('key'), $this->argument('value'), $database);
    }
}
