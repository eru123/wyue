<?php

namespace Wyue\Database;

use Exception;
use Wyue\Commands\CLI;
use Wyue\Commands\AbstractCommand;
use Wyue\MySql;

class MySqlMigrate extends AbstractCommand
{
    use MySqlMigrationTraits;

    protected string $entry = 'migrate';

    protected array $flags = [
        'f|force' => 'Force override existing migration file if happens to have a same class name.',
        'x|dryrun' => 'Dry run.',
    ];

    public function handle()
    {
        $dir = $this->getMigrationsDirectory();
        $files = glob($dir . DIRECTORY_SEPARATOR . '*.php');
        $migration = $this->initMigrationsTable();
        $migrations_table = $this->getMigrationsTable();

        if ($migration === false) {
            throw new Exception("Failed to create migrations table");
        } else if ($migration === true && $this->flag('V|verbose')) {
            CLI::info("Created migrations table: " . $migrations_table);
        }

        usort($files, function ($a, $b) {
            $a = basename($a);
            $b = basename($b);
            return strcmp($a, $b);
        });

        $skipped = 0;
        $processed = 0;

        foreach ($files as $f) {
            $fn = basename($f);

            !$this->flag('V|verbose') || CLI::info("Migration: " . $fn);

            $rgx = preg_match("/^([0-9]+)_([a-z_]+)\.php$/", $fn, $matches);
            if ($rgx) {
                $ts = $matches[1];

                $classes = get_declared_classes();
                require_once $f;
                $classes = array_diff(get_declared_classes(), $classes);
                if (count($classes)) {
                    $classes = array_values($classes);
                    $class = $classes[0];
                    if ($class) {
                        $className = explode("\\", $class);
                        $className = end($className);
                        if (MySql::raw("SELECT * FROM ? WHERE `name` = ? OR `version` = ?", [MySql::raw("`" . $migrations_table . "`"), $className, $ts])->exec()?->fetch(\PDO::FETCH_ASSOC)) {
                            $skipped++;
                            !$this->flag('V|verbose') || CLI::info("Skipped migration file: " . $f);
                            continue;
                        }

                        $class = new $class(MySql::pdo(), $this->flag('x|dryrun'));
                        !$this->flag('x|dryrun') ? MySql::pdo()->beginTransaction() : null;

                        try {
                            $start_at = date("Y-m-d H:i:s");
                            $class->up();
                            $end_at = date("Y-m-d H:i:s");
                            $this->flag('x|dryrun') || MySql::insert($migrations_table, [
                                'version' => $ts,
                                'filename' => $fn,
                                'name' => $className,
                                'start_at' => $start_at,
                                'end_at' => $end_at,
                                'breakpoint' => 0
                            ])->exec();
                            $this->flag('x|dryrun') || MySql::pdo()->commit();
                            $processed++;
                            CLI::info("Migrated: " . $f);
                        } catch (\Throwable $e) {
                            $this->flag('x|dryrun') || MySql::pdo()->rollBack();
                            throw $e;
                        }
                    }
                } else {
                    !$this->flag('V|verbose') || CLI::info("Invalid Migration File");
                }
            } else {
                !$this->flag('V|verbose') || CLI::info("Invalid Migration File");
            }
        }

        CLI::success("\nSkipped migrations: " . $skipped);
        CLI::success("Processed migrations: " . $processed);
        CLI::success("Migration done!");
    }
}
