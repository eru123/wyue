<?php

namespace Wyue\Commands;

use Exception;
use Throwable;
use Wyue\Venv;

class CLI
{
    use IO;

    const COLOR_RED = "\e[31m";
    const COLOR_GREEN = "\e[32m";
    const COLOR_YELLOW = "\e[33m";
    const COLOR_BLUE = "\e[34m";
    const COLOR_MAGENTA = "\e[35m";
    const COLOR_CYAN = "\e[36m";
    const COLOR_RESET = "\e[0m";

    /**
     * @var array The command instances
     */
    private static $intances = [];

    /**
     * @var int The start of the index on the argv, and the index of the entry
     */
    public static $idx = 1;

    /**
     * Get the argv array
     * @return array
     */
    public static function args()
    {
        $args =  isset($argv) ? $argv : ($_SERVER['argv'] ?? []);
        if (count($args) < static::$idx) {
            return [];
        }

        return array_slice($args, static::$idx);
    }

    /**
     * Get the entry name
     * @return string
     */
    private static function getEntryName()
    {
        return strtolower(trim(strval(@static::args()[0] ?? '')));
    }

    /**
     * Get the command instance
     * @return AbstractCommand
     * @throws Exception
     */
    public static function getEntryInstance(): AbstractCommand
    {
        $name = static::getEntryName();
        if (empty($name) || !isset(static::$intances[$name])) {
            throw new Exception('Command \'' . implode(' ', static::args()) . '\' is not a registered command.');
        }

        return static::$intances[$name];
    }

    /**
     * Get the entry 
     */

    /**
     * Register a CLI command
     * @param string|array $className The class name/s' and is a child of AbstractCommand
     * @return void
     */
    public static function register(string|array $className)
    {
        try {
            if (is_string($className)) {
                $className = [$className];
            }

            foreach ($className as $class) {
                if (!is_string($class)) {
                    continue;
                }


                $instance = new $class();
                if (class_exists($class) && $instance instanceof AbstractCommand) {

                    if (!isset(static::$intances[$instance->getEntry()])) {
                        $name = strtolower(trim($instance->getEntry()));
                        static::$intances[$name] = $instance;
                        continue;
                    }

                    throw new Exception('Command entry \'' . $instance->getEntry() . '\' already exists');
                }
            }
        } catch (Throwable $e) {
            static::error($e->getMessage());
            exit(1);
        }
    }

    public static function listen()
    {
        try {
            if (php_sapi_name() !== 'cli') {
                throw new Exception(get_class() . '::listen() can only be used in CLI mode');
                exit(1);
            }

            static::getEntryInstance()->handle();
            exit(0);
        } catch (Throwable $e) {
            static::error($e->getMessage());
            exit(1);
        }
    }
}
