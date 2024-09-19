<?php

namespace Wyue\Commands;

trait IO
{
    public static function print(string $message, ?string $color = null, bool $return = false)
    {
        if ($color) {
            $message = ($color ??  '') . $message . "\e[0m";
        }

        if ($return) {
            return $message;
        }

        fwrite(STDOUT, $message);
    }

    public static function println(string $message, ?string $color = null)
    {
        fwrite(STDOUT, static::print($message . PHP_EOL, $color, true));
    }

    public static function info(string $message, ?string $color = null)
    {
        fwrite(STDOUT, static::print($message, $color, true));
    }

    public static function error(string $message, string $color = "\e[31m")
    {
        fwrite(STDERR, static::print($message . PHP_EOL, $color, true));
    }

    public static function confirm(string $message)
    {
        $answer = null;
        while (!in_array(trim(strtolower(strval($answer))), ['y', 'yes', 'n', 'no'])) {
            $answer = static::prompt($message . ' [Y/n] ');

            if (empty(trim(strval($answer)))) {
                $answer = 'y';
            }

            if (!in_array(trim(strtolower(strval($answer))), ['y', 'yes', 'n', 'no'])) {
                static::error('Invalid input');
            }
        }

        return $answer === 'y' || $answer === 'yes';
    }

    public static function prompt(string $message)
    {
        return readline($message . ' ');
    }
}
