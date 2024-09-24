<?php

namespace Wyue\Database;

use Exception;
use Wyue\Date;
use Wyue\Str;
use Wyue\Commands\CLI;
use Wyue\Commands\AbstractCommand;
use Wyue\Database\AbstractMigration;
use Wyue\Format;

class MySqlMigrate extends AbstractCommand
{
    use MySqlMigrationTraits;

    protected string $entry = 'migrate';

    protected array $options = [
        'm|model' => 'Create migration with model.',
        'd|dir' => 'The directory where the migration file will be created.',
        't|table' => 'The table name for the migration.',
    ];

    protected array $flags = [
        'f|force' => 'Force override existing migration file if happens to have a same class name.',
    ];

    public function handle()
    {
        $timestamp = Date::getTimestamp();
        $classname = $this->arg('name');
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

        if (file_exists($file) && !$this->flag('f|force')) {
            throw new Exception('Make Migration Error: Migration file already exists');
        }

        $files = glob($dir . DIRECTORY_SEPARATOR . '*.php');
        foreach ($files as $f) {
            require_once $f;
        }

        if (class_exists($classname) && !is_subclass_of($classname, AbstractMigration::class) && !$this->flag('f|force')) {
            throw new Exception('Make Migration Error: Migration class already exists');
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
            CLI::success("File: {$file}");
            exit(0);
        } else {
            throw new Exception('Make Migration Error: Failed to create migration file');
        }
    }
}
