<?php

namespace Wyue\Database;

use Wyue\Commands\CLI;
use Wyue\Commands\AbstractCommand;

class MySqlMakeMigration extends AbstractCommand
{
    protected string $entry = 'make:migration';
    
    protected array $arguments = [
        'name' => 'The migration class name. Must be in CamelCase format.',
    ];

    protected array $options = [
        'f|force' => 'Force override existing migration file if happens to have a same class name.',
        'm|model' => 'Create migration with model.',
        'd|dir' => 'The directory where the migration file will be created.',
    ];

    public function handle()
    {
        // TODO: Implement handle() method.
    }
}
