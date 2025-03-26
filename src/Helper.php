<?php

namespace Wyue;

/**
 * Wyue Helper class.
 */
class Helper
{
    /**
     * Converts argument to a callable.
     *
     * @param mixed $cb Callback
     */
    final public static function MakeCallable($cb): callable
    {
        if (is_callable($cb)) {
            return $cb;
        }

        if (is_string($cb)) {
            $rgx = '/^([a-zA-Z0-9_\\\]+)(::|@)([a-zA-Z0-9_]+)$/';
            if (preg_match($rgx, $cb, $matches)) {
                $classname = $matches[1];
                $method = $matches[3];
                if (class_exists($classname)) {
                    $obj = new $classname();
                    if (method_exists($obj, $method)) {
                        return [$obj, $method];
                    }
                }
            }
        }

        if (is_array($cb) && 2 == count($cb)) {
            if (is_object($cb[0]) && is_string($cb[1])) {
                return $cb;
            }
            if (is_string($cb[0]) && is_string($cb[1])) {
                $classname = $cb[0];
                $method = $cb[1];
                if (class_exists($classname)) {
                    $obj = new $classname();
                    if (method_exists($obj, $method)) {
                        return [$obj, $method];
                    }
                    if (method_exists($classname, $method)) {
                        return $cb;
                    }
                }
            }
        }

        throw new \Exception('Invalid callback');
    }

    /**
     * Calls a callable with arguments.
     *
     * @param mixed $cb Callback
     *
     * @return mixed
     */
    final public static function Callback($cb, array $args = [])
    {
        return call_user_func_array(static::MakeCallable($cb), $args);
    }

    /**
     * Combines one or more URL paths.
     */
    final public static function CombineUrlPaths(string ...$paths): string
    {
        $paths = array_filter($paths, function ($path) {
            return !empty(trim($path, " \n\r\t\v\0\\/")) && (bool) preg_match('//u', $path);
        });

        $paths = array_map(function ($path) {
            return trim($path, " \n\r\t\v\0\\/");
        }, $paths);

        $path = implode('/', $paths);

        if ('/' == substr($path, -1)) {
            $path = substr($path, 0, -1);
        }

        return '/'.$path;
    }
}
