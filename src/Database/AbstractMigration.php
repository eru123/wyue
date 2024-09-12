<?php

namespace Wyue\Database;

use Exception;
use InvalidArgumentException;
use PDO;
use ReflectionClass;

abstract class AbstractMigration {

    use MySqlTraits;

    protected ?PDO $pdo;
    protected bool $dryrun;

    public function __construct(?PDO $pdo = null, bool $dryrun = false) {
        $this->pdo = $pdo;
        $this->dryrun = $dryrun;
    }

    /**
     * Get the full path of the migration file
     * @return string
     */
    public function getPath(): string {
        $class = new ReflectionClass($this);
        $path = realpath($class->getFileName());

        if (!$path) {
            throw new Exception('Migration path not found');
        }

        return $path;
    }

    public function getTimestamp(): string {
        $path = basename($this->getPath());

        $pattern = preg_match('/^([0-9]+)_([a-zA-Z0-9_]+)?\.php$/i', $path, $matches);
        if (!$pattern) {
            throw new InvalidArgumentException('Invalid migration format');
        }

        return strval($matches[1]);
    }

    public function getClassName(): string {
        $segment = explode('\\', $this::class);
        return $segment[count($segment) - 1];
    }

    public function migrate() {
        $timestamp = $this->getTimestamp();
        $className = $this->getClassName();
    }

    /**
     * Migrate up
     * @return void
     * @throws Exception
     */
    abstract public function up();

    /**
     * Migrate down
     * @return void
     * @throws Exception
     */
    abstract public function down();

    /**
     * Get the name of the migration
     * @return string
     */
}