<?php

namespace Wyue\Commands;

use Exception;
use Wyue\Format;

class MakeCommand extends AbstractCommand
{
    protected string $entry = 'make:command';

    protected array $arguments = [
        'name' => 'The Command class name. Must be in PascalCase format.',
    ];

    static $dir = 'App/Commands';
    static $namespace = 'App\Commands';

    public function handle()
    {
        $classname = $this->arg('name');

        if (!$classname) {
            throw new Exception('Make Command Error: Class name is required');
        }

        if (!is_dir(static::$dir)) {
            if (!mkdir(static::$dir, 0777, true)) {
                throw new Exception('Make Command Error: Can\'t create command directory');
            }
        }

        if (!is_writable(static::$dir)) {
            throw new Exception('Make Command Error: Can\'t write to command directory');
        }

        $dir = realpath(static::$dir);

        if (!$dir || !is_dir(static::$dir)) {
            throw new Exception('Make Command Error: Failed to get absolute path of commands directory or \'' . static::$dir  . '\' is not a directory');
        }

        $file = $dir . DIRECTORY_SEPARATOR . $classname . ".php";

        if (file_exists($file)) {
            throw new Exception("Command already exists");
        }

        $tpl = <<<PHP
        <?php

        namespace {Namespace};

        use Wyue\Commands\AbstractCommand;

        class {Name} extends AbstractCommand
        {
            protected string \$entry = 'command';

            protected array \$arguments = [];

            protected array \$options = [];

            protected array \$flags = [];

            public function handle()
            {
                // TODO: Implement handle() method.
            }
        }
         
        PHP;

        $content = Format::template($tpl, [
            'Namespace' => static::$namespace,
            'Name' => $classname,
        ], FORMAT_TEMPLATE_CURLY);

        $success = file_put_contents($file, $content);

        if ($success) {
            CLI::success("SUCCESS: Command file created");
            CLI::success("Class: " . static::$namespace . '\\' . $classname);
            CLI::success("File: " . $file);
            exit(0);
        } else {
            throw new Exception('Make Command Error: Failed to create command file');
        }
    }
}
