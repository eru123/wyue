<?php

namespace Wyue\Commands;

use Exception;
use Wyue\Venv;

abstract class AbstractCommand
{
    /**
     * @var string The command entry, this will be use to look up the command and must be unique.
     */
    protected string $entry = '';

    /**
     * @var string The command description
     */
    protected string $description = 'No Description';

    /**
     * @var array The command arguments and it's description. [argument => description]
     */
    protected array $arguments = [];

    /**
     * @var array The command options (pipe separated) and it's description. [option => description]
     * 
     * Example: ['n|name' => 'The name of the user'] - This accepts both -n and --name and is case insensitive
     * 
     * Usage: php {filename} {entry} -n {name}
     */
    protected array $options = [];

    /**
     * @var array The command flags (pipe separated) and it's description. [flag => description]
     * 
     * Example: ['y|yes' => 'Yes to all confirmations'] - This accepts both -y or --y and is case insensitive
     * 
     * Usage: php {filename} {entry} -y
     */
    protected array $flags = [];

    /**
     * @var string Handle the command
     */
    abstract public function handle();

    /**
     * Get arguments
     * @param null|string $key The name of the argument
     * @param mixed $default The default value to return if key not found
     * @return mixed
     */
    public function args(null|string $key = null, $default = null)
    {
        $args = CLI::args();
        array_shift($args);

        $res = [];
        $t_con = false;

        foreach ($args as $v) {
            if ($t_con) {
                $t_con = false;
                continue;
            }

            if (preg_match('/^--?(.*)$/', $v)) {
                $t_con = true;
                continue;
            }

            $res[] = $v;
        }

        return Venv::_get($res, $key, $default);
    }

    /**
     * Get named arguments
     * @param null|string $key The name of the argument
     * @param mixed $default The default value to return if key not found
     * @return mixed
     */
    public function arg(null|string $key = null, $default = null)
    {
        $args = CLI::args();
        array_shift($args);

        $res = [];
        $t_con = false;

        $ctr = 0;
        $tmp = array_keys($this->arguments);
        $max = count($this->arguments) - 1;

        foreach ($args as $v) {
            if ($ctr > $max) {
                break;
            }

            if ($t_con) {
                $t_con = false;
                continue;
            }

            if (preg_match('/^--?(.*)$/', $v)) {
                $t_con = true;
                continue;
            }

            $tk = $tmp[$ctr];
            $res[$tk] = $v;
            $ctr++;
        }

        return Venv::_get($res, $key, $default);
    }

    /**
     * Check if argument has flag
     * @param string $flag The name of the flag separated by pipe
     * @return bool
     */
    public function flag(string $flag): bool
    {
        $flags = explode('|', $flag);
        foreach ($flags as $f) {
            if (!preg_match('/^[a-zA-Z]([a-zA-Z0-9]+)?$/', $f)) {
                return false;
            }
        }

        $args = CLI::args();
        array_shift($args);

        foreach ($args as $v) {
            if (preg_match('/^--?(' . $flag . ')$/', trim(strval($v)))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get Options
     * @param null|string $key The name of the option
     * @param mixed $default The default value to return if key not found
     * @return mixed
     */
    public function opt(null|string $key = null, $default = null)
    {
        $flags = is_string($key) ? explode('|', $key) : [];
        foreach ($flags as $f) {
            if (!preg_match('/^[a-zA-Z]([a-zA-Z0-9]+)?$/', $f)) {
                return false;
            }
        }

        $args = CLI::args();
        array_shift($args);

        $opts = [];
        $t_con = false;
        $l_key = null;

        foreach ($args as $v) {
            if ($t_con) {
                if (is_null($key) || in_array($l_key, $flags)) {
                    if (isset($opts[$l_key]) && is_array($opts[$l_key])) {
                        $opts[$l_key][] = $v;
                    } else if (isset($opts[$l_key])) {
                        $opts[$l_key] = [$opts[$l_key], $v];
                    } else {
                        $opts[$l_key] = $v;
                    }
                }

                $t_con = false;
                $l_key = null;
                continue;
            }

            if (preg_match('/^--?(.*)$/', $v, $m)) {
                $t_con = true;
                $l_key = $m[1];
                continue;
            }
        }

        return Venv::_get($opts, is_null($key) ? null : $flags, $default);
    }


    /**
     * @return string The command entry
     */
    public function getEntry()
    {
        if (is_string($this->entry) && !empty(trim($this->entry))) {
            return $this->entry;
        }

        throw new Exception('Invalid command entry for ' . get_class($this));
    }

    /**
     * @return string The command description
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return array The command arguments
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * @return array The command options
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return array The command flags
     */
    public function getFlags()
    {
        return $this->flags;
    }

    /**
     * @param array $haystack Array of flags or options
     * @return array Translate keys
     */
    public function translateKeyFlags(array $haystack): array
    {
        $res = [];
        foreach ($haystack as $k => $v) {
            $kas = explode("|", $k);
            $kat = array_map(fn ($i) => strlen($i) == 1 ? "-$i" : "--$i", $kas);
            $kak = implode("|", $kat);
            $res[$kak] = $v;
        }
        return $res;
    }

    /**
     * Display the help for the command
     * @return void
     */
    public function help(): void
    {
        $entry = $this->entry;
        $description = $this->description;
        $args = array_map(fn($v) => "<$v>", array_keys($this->getArguments()));

        CLI::println("$entry " . implode(" ", $args), CLI::COLOR_BLUE);
        CLI::println("\tDescription", CLI::COLOR_YELLOW);
        CLI::println("\t\t$description");

        if (count($this->getArguments())) {
            CLI::println("\tArguments:", CLI::COLOR_YELLOW);
            foreach ($this->getArguments() as $k => $v) {
                CLI::print("\t\t<$k> ", CLI::COLOR_BLUE);
                CLI::println($v);
            }
        }
        
        if (count($this->getOptions())) {
            CLI::println("\tOptions:", CLI::COLOR_YELLOW);
            foreach ($this->translateKeyFlags($this->getOptions()) as $k => $v) {
                CLI::print("\t\t$k ", CLI::COLOR_BLUE);
                CLI::println($v);
            }
        }
        
        if (count($this->getFlags())) {
            CLI::println("\tFlags:", CLI::COLOR_YELLOW);
            foreach ($this->translateKeyFlags($this->getFlags()) as $k => $v) {
                CLI::print("\t\t$k ", CLI::COLOR_BLUE);
                CLI::println($v);
            }
        }
        
        CLI::print("\n");
    }
}
