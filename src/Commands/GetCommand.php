<?php

namespace Poisa\Settings\Commands;

use Illuminate\Console\Command;
use Settings;

class GetCommand extends Command
{
    protected $signature = 'settings:get 
                            {key        : Key }
                            {--database= : The database connection to use }';

    protected $description = 'Get a key from the database and dump it to stdout (decrypting it if necessary)';

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

        $value = Settings::getKey($this->argument('key'), $database);
        $this->line(print_r($value, true));
    }
}
