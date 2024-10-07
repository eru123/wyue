<?php

namespace Wyue\Database;

use Exception;
use Wyue\Commands\CLI;
use Wyue\Commands\AbstractCommand;
use Wyue\MySql;

class MySqlRollback extends AbstractCommand
{
    use MySqlMigrationTraits;

    protected string $entry = 'rollback';

    protected array $options = [
        't|time' => 'Timestamp to return back to.',
    ];

    protected array $flags = [
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

        usort($files, fn($a, $b)  => strcmp(basename($b), basename($a)));

        $skipped = 0;
        $processed = 0;

        $time = intval($this->opt('t|time', -1));

        foreach ($files as $f) {
            $fn = basename($f);

            !$this->flag('V|verbose') || CLI::info("Migration: " . $fn);

            $rgx = preg_match("/^([0-9]+)_([a-z_]+)\.php$/", $fn, $matches);
            if ($rgx) {
                $ts = intval($matches[1]);

                if ($time >= $ts) {
                    break;
                }

                $classes = get_declared_classes();
                require_once $f;
                $classes = array_diff(get_declared_classes(), $classes);
                if (count($classes)) {
                    $classes = array_values($classes);
                    $class = $classes[0];
                    if ($class) {
                        $className = explode("\\", $class);
                        $className = end($className);

                        if (!MySql::raw("SELECT * FROM ? WHERE `name` = ? OR `version` = ?", [MySql::raw("`" . $migrations_table . "`"), $className, $ts])->exec()?->fetch(\PDO::FETCH_ASSOC)) {
                            $skipped++;
                            !$this->flag('V|verbose') || CLI::info("Skipped migration file: " . $f);
                            continue;
                        }

                        $class = new $class(MySql::pdo(), $this->flag('x|dryrun'));
                        $this->flag('x|dryrun') || MySql::pdo()->beginTransaction();

                        try {
                            $class->down();
                            $this->flag('x|dryrun') || MySql::delete($migrations_table, [
                                'version' => $ts,
                                'filename' => $fn,
                                'name' => $className,
                            ])->exec();
                            $this->flag('x|dryrun') || (MySql::pdo()->inTransaction() && MySql::pdo()->commit());
                            $processed++;
                            CLI::info("Rollback: " . $f);

                            if ($time < 0) {
                                break;
                            }
                        } catch (\Throwable $e) {
                            $this->flag('x|dryrun') || (MySql::pdo()->inTransaction() && MySql::pdo()->rollBack());
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
        CLI::success("Processed rollback: " . $processed);
        CLI::success("Rollback done!");
    }
}
