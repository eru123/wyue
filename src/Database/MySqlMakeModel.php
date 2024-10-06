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

class MySqlMakeModel extends AbstractCommand
{
    use MySqlMigrationTraits;

    protected string $entry = 'make:model';

    protected array $arguments = [
        'name' => 'The Model class name. Must be in PascalCase format.',
    ];

    protected array $options = [
        't|table' => 'The table name for the model.',
    ];

    protected array $flags = [
        'f|force' => 'Force override existing model file if happens to have a same class name.',
        'V|verbose' => 'Verbose output.',
    ];

    public function handle()
    {
        $classname = $this->arg('name');
        $table = Str::pascal_case_to_snake_case($classname);

        if (!$classname) {
            throw new Exception('Make Model Error: Class name is required');
        }

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

        if (file_exists($file) && $this->flag('f|force')) {
            unlink($file);
        } else if (file_exists($file)) {
            throw new Exception("Model already exists");
        }

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
            CLI::success("SUCCESS: Model file created");
            CLI::success("Class: $namespace\\$classname");
            CLI::success("File: " . $file);
            exit(0);
        } else {
            throw new Exception('Make Model Error: Failed to create model file');
        }
    }
}
