<?php

namespace Wyue\Database;

use Exception;
use Wyue\Date;
use Wyue\Str;
use Wyue\Commands\CLI;
use Wyue\Commands\AbstractCommand;
use Wyue\Database\AbstractMigration;
use Wyue\Format;
use Wyue\MySql;

class MySqlMakeMigration extends AbstractCommand
{
    use MySqlMigrationTraits;

    protected string $entry = 'make:migration';

    protected array $arguments = [
        'name' => 'The migration class name. Must be in PascalCase format.',
    ];

    protected array $options = [
        'd|dir' => 'The directory where the migration file will be created.',
        't|table' => 'The table name for the model. Use only if -m flag is used.',
    ];

    protected array $flags = [
        'm|model' => 'Create migration with model with same class name.',
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

            if ($this->flag('m|model')) {
                $this->createModel($classname, $this->opt('t|table', $filemname));
            }
            exit(0);
        } else {
            throw new Exception('Make Migration Error: Failed to create migration file');
        }
    }

    public function createModel(string $classname, string $table)
    {
        $namespace = MySql::getModelsNamespace();
        $dir = MySql::getModelsPath();
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0777, true)) {
                throw new Exception('Make Model Error: Can\'t create model directory');
            }
        }

        if (!is_writable($dir)) {
            throw new Exception('Make Model Error: Can\'t write to model directory');
        }

        $dir = realpath($dir);

        if (!$dir || !is_dir($dir)) {
            throw new Exception('Make Model Error: Failed to get absolute path of models directory or \'' . MySql::getModelsPath() . '\' is not a directory');
        }

        $file = $dir . DIRECTORY_SEPARATOR . $classname . ".php";

        $tpl = <<<PHP
        <?php

        namespace {ModelNamespace};

        use Wyue\Database\AbstractModel;

        class {ModelName} extends AbstractModel
        {
            protected null|string \$table = '{ModelTable}';
            protected null|array \$fillable = null;
            protected null|array \$hidden = null;
            protected null|string|int \$primaryKey = null;
        }
         
        PHP;

        $content = Format::template($tpl, [
            'ModelNamespace' => $namespace,
            'ModelName' => $classname,
            'ModelTable' => $table
        ], FORMAT_TEMPLATE_CURLY);

        $success = file_put_contents($file, $content);

        if ($success) {
            CLI::success("\nSUCCESS: Model file created");
            CLI::success("Class: $namespace\\$classname");
            CLI::success("File: " . $file);
            exit(0);
        } else {
            throw new Exception('Make Model Error: Failed to create model file');
        }
    }
}
