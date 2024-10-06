<?php

namespace Wyue\Database;

use Exception;
use Wyue\Commands\CLI;
use Wyue\MySql;

trait MySqlMigrationTraits
{
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
        $table = MySql::getMigrationsTable();

        if (empty($table)) {
            $table = 'migrations';
        }

        return $table;
    }

    private function initMigrationsTable(): null|bool
    {
        $table = $this->getMigrationsTable();
        $dbname = MySql::myConfig(['dbname', 'db_name', 'name', 'db']);

        if (empty($dbname)) {
            throw new Exception("Database name not set");
        }

        if (MySql::raw("SELECT * FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?", [$dbname, $table])->exec()?->fetch(\PDO::FETCH_ASSOC)) {
            return null;
        }

        $sql = "CREATE TABLE IF NOT EXISTS ? (
            `version` BIGINT NOT NULL,
            `filename` VARCHAR(255) NULL DEFAULT NULL,
            `name` VARCHAR(50) NULL DEFAULT NULL,
            `start_at` TIMESTAMP NULL DEFAULT NULL,
            `end_at` TIMESTAMP NULL DEFAULT NULL,
            `breakpoint` INT NULL DEFAULT 0,
            PRIMARY KEY (`version`)
        );";

        return MySql::raw($sql, [MySql::raw("`" . $table . "`")])->exec() !== false;
    }
}
