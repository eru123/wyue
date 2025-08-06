<?php

namespace Wyue\Tools;

class UrlShortener
{
    public static $chars = 'Js3KZjq5N9gkO8Ln6GVAPIUSuW4XhEH1t0oadCMTfRxpmDivwrQ2clzYeFbBy7_-';

    /**
     * Generates a base N encoded string from the current system time in nanoseconds.
     * 
     * This method uses the system command `date +%s%N` to get the current time in nanoseconds.
     * It then converts this time into a base N string using the defined characters.
     * If the system time is greater than PHP_INT_MAX, it throws an exception.
     * 
     * Note: This method is intended for use in environments where the system command can be executed.
     * It may not work in all PHP environments, especially those with restricted permissions.
     * 
     * @return string The base N encoded string representing the current system time in nanoseconds.
     * @throws \RuntimeException if the system time cannot be retrieved or exceeds PHP_INT_MAX
     * @throws \RuntimeException if the system command fails or returns a non-numeric value.
     * @throws \RuntimeException if the converted time exceeds PHP_INT_MAX.
     * @throws \RuntimeException if the system command returns a value that cannot be converted to an integer.
     */
    public static function t_nanosys()
    {
        $time = system('date +%s%N');
        if ($time === false || !is_numeric($time)) {
            throw new \RuntimeException('Failed to get system time');
        }

        // if $time is greater than PHP_INT_MAX, we need to throw an exception
        if ($time > PHP_INT_MAX) {
            throw new \RuntimeException('System time exceeds PHP_INT_MAX');
        }

        // convert to integer
        $time = intval($time);
        return self::t_num($time);
    }

    /**
     * Generates a base N encoded string using the current microtime.
     *
     * This method retrieves the current microtime using `microtime(true)`, converts it to microseconds,
     * and then encodes it into a base N string using the defined characters.
     * If the microtime is greater than PHP_INT_MAX, it throws an exception.
     * 
     * Note: This method is intended for use in environments where the microtime function is available.
     * It may not work in all PHP environments, especially those with restricted permissions.
     * 
     * @return string The base N encoded string representing the current microtime in microseconds.
     * 
     * @throws \RuntimeException if the microtime cannot be retrieved or exceeds PHP_INT_MAX
     * @throws \RuntimeException if the microtime function fails or returns a non-numeric value.
     * @throws \RuntimeException if the converted time exceeds PHP_INT_MAX.
     * @throws \RuntimeException if the microtime value cannot be converted to an integer.
     * @throws \InvalidArgumentException if the microtime is not a valid integer or is negative.
     */
    public static function t_micro()
    {
        $time = microtime(true);

        if ($time === false || !is_numeric($time)) {
            throw new \RuntimeException('Failed to get system time');
        }

        $time *= 10000; // Convert to microseconds
        $time = intval(explode('.', strval($time))[0]); // Ensure we only take the integer part

        if (!is_int($time) || $time <= 0) {
            throw new \InvalidArgumentException('Microtime must be a non-negative integer');
        }

        // if $time is greater than PHP_INT_MAX, we need to throw an exception
        if ($time > PHP_INT_MAX) {
            throw new \RuntimeException('Microtime exceeds PHP_INT_MAX');
        }

        return self::t_num($time);
    }

    /**
     * Generates a base N encoded string an arbitrary number.
     * 
     * This method takes an integer input, validates it, and converts it into a base N string using the defined characters.
     * It is useful for encoding arbitrary numbers into a short URL format.
     * 
     * @param int $num The number to encode.
     * @throws \InvalidArgumentException if the input is not a valid integer or is negative.
     * @throws \RuntimeException if the conversion fails or the number exceeds PHP_INT_MAX.
     * @return string The base N encoded string representing the given number.
     */
    public static function t_num($num)
    {
        if (!is_int($num) || $num < 0) {
            throw new \InvalidArgumentException('Input must be a non-negative integer');
        }

        if ($num > PHP_INT_MAX) {
            throw new \RuntimeException('Number exceeds PHP_INT_MAX');
        }

        // convert to base n
        $baseN = '';
        $N = strlen(static::$chars);
        while ($num > 0) {
            $baseN = static::$chars[$num % $N] . $baseN;
            $num = (int)($num / $N);
        }

        if ($baseN === '') {
            throw new \RuntimeException('Failed to convert number to base ' . $N);
        }

        return $baseN;
    }

    /**
     * Decode a base N encoded string back to an integer.
     * 
     * This method takes a base N encoded string and converts it back to its original integer value.
     * It is useful for decoding short URLs back to their original numeric identifiers.
     * 
     * @param string $baseN The base N encoded string to decode.
     * @throws \InvalidArgumentException if the input is not a valid base N string.
     * @return int The decoded integer value.
     */
    public static function decode($baseN)
    {
        if (!is_string($baseN) || !preg_match('/^[' . preg_quote(self::$chars, '/') . ']+$/', $baseN)) {
            throw new \InvalidArgumentException('Input must be a valid base N string');
        }

        $num = 0;
        $N = strlen(self::$chars);
        $length = strlen($baseN);
        for ($i = 0; $i < $length; $i++) {
            $num = $num * $N + strpos(static::$chars, $baseN[$i]);
        }

        if ($num > PHP_INT_MAX) {
            throw new \RuntimeException('Decoded number exceeds PHP_INT_MAX');
        }

        return $num;
    }
}