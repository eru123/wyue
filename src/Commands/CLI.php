<?php

namespace Wyue\Commands;

use Wyue\Exceptions\InvalidCommandException;

class CLI
{
    use IO;

    public const COLOR_BLACK = "\e[30m";
    public const COLOR_RED = "\e[31m";
    public const COLOR_GREEN = "\e[32m";
    public const COLOR_YELLOW = "\e[33m";
    public const COLOR_BLUE = "\e[34m";
    public const COLOR_MAGENTA = "\e[35m";
    public const COLOR_CYAN = "\e[36m";
    public const COLOR_WHITE = "\e[37m";
    public const COLOR_BRIGHT_BLACK = "\e[90m";
    public const COLOR_BRIGHT_RED = "\e[91m";
    public const COLOR_BRIGHT_GREEN = "\e[92m";
    public const COLOR_BRIGHT_YELLOW = "\e[93m";
    public const COLOR_BRIGHT_BLUE = "\e[94m";
    public const COLOR_BRIGHT_MAGENTA = "\e[95m";
    public const COLOR_BRIGHT_CYAN = "\e[96m";
    public const COLOR_BRIGHT_WHITE = "\e[97m";
    public const COLOR_DEFAULT = "\e[39m";
    public const COLOR_RESET = "\e[0m";

    /**
     * @var int The start of the index on the argv, and the index of the entry
     */
    public static $idx = 1;

    /**
     * @var array The command instances
     */
    private static $intances = [];

    /**
     * Get the argv array.
     *
     * @return array
     */
    public static function args()
    {
        $args = isset($argv) ? $argv : ($_SERVER['argv'] ?? []);
        if (count($args) < static::$idx) {
            return [];
        }

        return array_slice($args, static::$idx);
    }

    /**
     * Get the command instance.
     *
     * @throws \Exception
     */
    public static function getEntryInstance(): AbstractCommand
    {
        $name = static::getEntryName();
        if (empty($name)) {
            throw new InvalidCommandException('No Command');
        }
        if (!isset(static::$intances[$name])) {
            throw new InvalidCommandException("Command '".implode(' ', static::args())."' is not a registered command.");
        }

        return static::$intances[$name];
    }

    /**
     * Get the entry.
     */

    /**
     * Register a CLI command.
     *
     * @param array|string $className The class name/s' and is a child of AbstractCommand
     */
    public static function register(array|string $className)
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

                    throw new \Exception('Command entry \''.$instance->getEntry().'\' already exists');
                }
            }
        } catch (\Throwable $e) {
            static::error($e->getMessage());

            exit(1);
        }
    }

    /**
     * Display the Help manual.
     */
    public static function help()
    {
        foreach (static::$intances as $instance) {
            $instance->help();
        }
    }

    /**
     * Start the CLI handler.
     */
    public static function listen()
    {
        $verbose = false;
        $cmd = null;

        try {
            if ('cli' !== php_sapi_name()) {
                throw new \Exception(get_class().'::listen() can only be used in CLI mode');

                exit(1);
            }

            $cmd = static::getEntryInstance();
            $verbose = $cmd->flag('V|verbose');
            $cmd->handle();

            exit(0);
        } catch (InvalidCommandException $e) {
            static::error($verbose ? strval($e) : $e->getMessage());
            CLI::println();
            if ($cmd) {
                $cmd->help();
            } else {
                static::help();
            }

            exit(1);
        } catch (\Throwable $e) {
            static::error($verbose ? strval($e) : $e->getMessage());
            CLI::println();

            exit(1);
        }
    }

    /**
     * Get the entry name.
     *
     * @return string
     */
    private static function getEntryName()
    {
        return strtolower(trim(strval(@static::args()[0] ?? '')));
    }
}
