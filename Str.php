<?php

namespace Wyue;

class Str
{
    public static function camel_case_to_snake_case(string $string): string
    {
        $string = preg_replace_callback('/[A-Z]/', function ($match) {
            return '_' . strtolower($match[0]);
        }, $string);
        $string = ltrim($string, '_');
        return $string;
    }

    public static function snake_case_to_camel_case(string $string): string
    {
        $string = preg_replace_callback('/_[a-z]/', function ($match) {
            return strtoupper($match[0][1]);
        }, $string);
        return $string;
    }

    public static function snake_case_to_pascal_case(string $string): string
    {
        $string = static::snake_case_to_camel_case($string);
        $string = ucfirst($string);
        return $string;
    }

    public static function camel_case_to_pascal_case(string $string): string
    {
        $string = ucfirst($string);
        return $string;
    }

    public static function pascal_case_to_snake_case(string $string): string
    {
        $string = static::pascal_case_to_camel_case($string);
        $string = static::camel_case_to_snake_case($string);
        return $string;
    }

    public static function pascal_case_to_camel_case(string $string): string
    {
        $string = lcfirst($string);
        return $string;
    }

    public static function isCamelCase(string $string): bool
    {
        return preg_match('/[a-z]([a-zA-Z0-9]+)?/', $string);
    }

    public static function isPascalCase(string $string): bool
    {
        return preg_match('/[A-Z]([a-zA-Z0-9]+)?/', $string);
    }

    public static function isSnakeCase(string $string): bool
    {
        return preg_match('/[a-z]([a-z0-9_]+)?/', $string);
    }
}
