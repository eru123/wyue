<?php

namespace Wyue\Database;

use Exception;
use Wyue\Date;
use Wyue\Str;
use Wyue\Commands\CLI;
use Wyue\Commands\AbstractCommand;
use Wyue\Database\AbstractMigration;
use Wyue\Format;

class MySqlMakeMigration extends AbstractCommand
{
    use MySqlMigrationTraits;

    protected string $entry = 'make:migration';

    protected array $arguments = [
        'name' => 'The migration class name. Must be in PascalCase format.',
    ];

    protected array $options = [
        'm|model' => 'Create migration with model.',
        'd|dir' => 'The directory where the migration file will be created.',
        't|table' => 'The table name for the migration.',
    ];

    protected array $flags = [
        'f|force' => 'Force override existing migration file if happens to have a same class name.',
        'V|verbose' => 'Verbose output.',
    ];


    public function handle()
    {
        $timestamp = Date::getTimestamp();
        $classname = $this->arg('name');
        $fclassnme = "Wyue\Migrations\\" . $classname;
        $filemname = Str::pascal_case_to_snake_case($classname);
        $cabstract = AbstractMigration::class;
        $eabstract = explode('\\', $cabstract);
        $nabstract = end($eabstract);

        if (!$classname) {
            throw new Exception('Make Migration Error: Class name is required');
        }

        if (!Str::isPascalCase($classname)) {
            throw new Exception('Make Migration Error: Class name must be in PascalCase format');
        }

        $dir = $this->getMigrationsDirectory();
        $file = $dir . DIRECTORY_SEPARATOR . "{$timestamp}_{$filemname}.php";

        if ($this->flag('V|verbose')) {
            CLI::info("Checking new migration file if exists: " . $file);
        }

        if (file_exists($file) && !$this->flag('f|force')) {
            throw new Exception('Make Migration Error: Migration file already exists');
        }

        if ($this->flag('V|verbose')) {
            CLI::info("Checking migrations folder: " . $dir);
        }

        $files = glob($dir . DIRECTORY_SEPARATOR . '*.php');
        foreach ($files as $f) {
            $fn = basename($f);
            $rgx = preg_match("/^([0-9]+)_([a-z_]+)\.php$/", $fn, $matches);
            if ($rgx) {
                $cn = $matches[2];
                if ($cn == Str::pascal_case_to_snake_case($classname)) {
                    throw new Exception('Make Migration Error: Class name already exists: ' . $fn);
                }
                require_once $f;
            }
        }

        if ($this->flag('V|verbose')) {
            CLI::info("Migration class checking: " . $fclassnme);
        }

        $content = <<<PHP
        <?php

        namespace Wyue\Migrations;

        use {UseAbstractMigration};

        class {ClassName} extends {AbstractMigration}
        {
            public function up()
            {
                // TODO: Implement up() method.
            }

            public function down()
            {
                // TODO: Implement down() method.
            }
        }

        PHP;

        $success = file_put_contents($file, Format::template($content, [
            'ClassName' => $classname,
            'UseAbstractMigration' => $cabstract,
            'AbstractMigration' => $nabstract
        ], FORMAT_TEMPLATE_CURLY));

        if ($success) {
            CLI::success("SUCCESS: Migration file created");
            CLI::success("Class: " . $fclassnme);
            CLI::success("File: " . $file);
            exit(0);
        } else {
            throw new Exception('Make Migration Error: Failed to create migration file');
        }
    }
}
