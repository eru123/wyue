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
        'x|dryrun' => 'Dry run.',
    ];

    public function handle()
    {
        $dir = $this->getMigrationsDirectory();
        $files = glob($dir . DIRECTORY_SEPARATOR . '*.php');

        usort($files, function ($a, $b) {
            $a = basename($a);
            $b = basename($b);
            return strcmp($a, $b);
        });

        foreach ($files as $f) {
            $fn = basename($f);
            $rgx = preg_match("/^([0-9]+)_([a-z_]+)\.php$/", $fn, $matches);
            if ($rgx) {
                $ts = $matches[1];
                $cn = $matches[2];

                $classes = get_declared_classes();
                require_once $f;
                $classes = array_diff(get_declared_classes(), $classes);
                if (count($classes) == 1) {
                    $class = reset($classes);
                    $class = new $class(MySql::pdo(), $this->flag('x|dryrun'));
                    $class->up();
                }
            }
        }
    }
}
