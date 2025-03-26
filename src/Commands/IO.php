<?php

namespace Wyue\Commands;

trait IO
{
    /**
     * Print message to the console.
     *
     * @param string      $message The message
     * @param null|string $color   The color of the message. In escape sequence format.
     * @param bool        $return  If return the message, just like print_r() function
     *
     * @return string|void
     */
    public static function print(string $message, ?string $color = null, bool $return = false)
    {
        if ($color) {
            switch (strtolower($color)) {
                case 'black':
                    $color = "\e[30m";

                    break;

                case 'red':
                case 'danger':
                case 'error':
                    $color = "\e[31m";

                    break;

                case 'green':
                case 'success':
                    $color = "\e[32m";

                    break;

                case 'yellow':
                case 'warning':
                    $color = "\e[33m";

                    break;

                case 'blue':
                case 'primary':
                    $color = "\e[34m";

                    break;

                case 'magenta':
                    $color = "\e[35m";

                    break;

                case 'cyan':
                case 'info':
                    $color = "\e[36m";

                    break;

                case 'white':
                    $color = "\e[37m";

                    break;

                case 'default':
                    $color = "\e[39m";

                    break;

                default:
                    break;
            }
            $message = ($color ?? '').$message."\e[0m";
        }

        if ($return) {
            return $message;
        }

        fwrite(STDOUT, $message);
    }

    /**
     * Print message to the console with new line.
     *
     * @param string      $message The message
     * @param null|string $color   The color of the message. In escape sequence format.
     */
    public static function println(string $message = '', ?string $color = null)
    {
        fwrite(STDOUT, static::print($message.PHP_EOL, $color, true));
    }

    /**
     * Print info message to the console.
     *
     * @param string      $message The message
     * @param null|string $color   The color of the message. In escape sequence format.
     */
    public static function info(string $message, ?string $color = 'info')
    {
        fwrite(STDOUT, static::print(rtrim($message).PHP_EOL, $color, true));
    }

    /**
     * Print error message to the console.
     *
     * @param string      $message The message
     * @param null|string $color   The color of the message. In escape sequence format.
     */
    public static function error(string $message, string $color = 'error')
    {
        fwrite(STDERR, static::print(rtrim($message).PHP_EOL, $color, true));
    }

    /**
     * Print success message to the console.
     *
     * @param string $message The message
     */
    public static function success(string $message)
    {
        fwrite(STDOUT, static::print(rtrim($message).PHP_EOL, 'success', true));
    }

    /**
     * Print warning message to the console.
     *
     * @param string $message The message
     */
    public static function warning(string $message)
    {
        fwrite(STDOUT, static::print(rtrim($message).PHP_EOL, 'warning', true));
    }

    /**
     * Print question message to the console and accept Yes or No input.
     *
     * @param string $message The message
     *
     * @return bool
     */
    public static function confirm(string $message)
    {
        $answer = null;
        while (!in_array(trim(strtolower(strval($answer))), ['y', 'yes', 'n', 'no'])) {
            $answer = static::prompt($message.' [Y/n]: ');

            if (empty(trim(strval($answer)))) {
                $answer = 'y';
            }

            if (!in_array(trim(strtolower(strval($answer))), ['y', 'yes', 'n', 'no'])) {
                static::error('Invalid input. Please try again.');
            }
        }

        return 'y' === $answer || 'yes' === $answer;
    }

    /**
     * Get user input.
     *
     * @param string $message The message
     *
     * @return string
     */
    public static function prompt(string $message)
    {
        return readline($message);
    }
}
