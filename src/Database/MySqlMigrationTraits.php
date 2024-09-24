<?php

namespace Wyue\Database;

use Exception;
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
        $table = $this->opt('t|table', MySql::getMigrationsTable());

        if (empty($table)) {
            $table = 'migrations';
        }

        return $table;
    }
}
