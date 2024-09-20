<?php

namespace Wyue\Database;

use Exception;
use Wyue\Date;
use Wyue\MySql;
use Wyue\Str;
use Wyue\Commands\AbstractCommand;
use Wyue\Database\AbstractMigration;

class MySqlMakeMigration extends AbstractCommand
{
    protected string $entry = 'make:migration';

    protected array $arguments = [
        'name' => 'The migration class name. Must be in PascalCase format.',
    ];

    protected array $options = [
        'f|force' => 'Force override existing migration file if happens to have a same class name.',
        'm|model' => 'Create migration with model.',
        'd|dir' => 'The directory where the migration file will be created.',
        't|table' => 'The table name for the migration.',
    ];

    private function getMigrationsDirectory(): string
    {
        $path = $this->opt('d|dir', MySql::getMigrationsPath());

        if (empty($path)) {
            throw new Exception('Make Migration Error: Invalid migration path');
        }

        if (!is_dir($path)) {
            if (!mkdir($path, 0777, true)) {
                throw new Exception('Make Migration Error: Can\'t create migration directory');
            }
        }

        if (!is_writable($path)) {
            throw new Exception('Make Migration Error: Can\'t write to migration directory');
        }

        $dpath = realpath($path);

        if (!$dpath || !is_dir($dpath)) {
            throw new Exception('Make Migration Error: Failed to get absolute path of migration directory or \'' . $path . '\' is not a directory');
        }

        return $dpath;
    }

    private function getMigrationsTable(): string
    {
        $table = $this->opt('t|table', MySql::getMigrationsTable());

        if (empty($table)) {
            $table = 'migrations';
        }

        return $table;
    }

    public function handle()
    {
        $timestamp = Date::getTimestamp();
        $classname = $this->arg('name');
        $filemname = Str::pascal_case_to_snake_case($classname);
        $migstable = $this->getMigrationsTable();
        $cabstract = AbstractMigration::class;
        $nabstract = 'AbstractMigration';

        if (!$classname) {
            throw new Exception('Make Migration Error: Class name is required');
        }

        if (!Str::isPascalCase($classname)) {
            throw new Exception('Make Migration Error: Class name must be in PascalCase format');
        }

        $dir = $this->getMigrationsDirectory();
        $file = $dir . DS . "{$timestamp}_{$filemname}.php";

        if (file_exists($file) && !$this->flag('f|force')) {
            throw new Exception('Make Migration Error: Migration file already exists');
        }
    }
}
