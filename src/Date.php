<?php

namespace Wyue;

use DateTime;

define('DATE_TYPE_UNIT', 0);
define('DATE_TYPE_FORMAT', 1);
define('DATE_UNIT_MILLISECOND', 'ms');
define('DATE_UNIT_SECOND', 's');
define('DATE_UNIT_MINUTE', 'm');
define('DATE_UNIT_HOUR', 'h');
define('DATE_UNIT_DAY', 'd');
define('DATE_UNIT_WEEK', 'w');
define('DATE_UNIT_MONTH', 'M');
define('DATE_UNIT_YEAR', 'y');
define('DATE_UNIT_LEAP_YEAR', 'ly');

class Date
{
    /**
     * @var int static time value for time sensitive functions and simulations
     */
    private static $global_time;

    /**
     * Set global time.
     *
     * @param int $time
     */
    public static function setTime($time = null)
    {
        static::$global_time = $time ?? time();
    }

    /**
     * Get global time.
     *
     * @return int
     */
    public static function now()
    {
        if (is_null(static::$global_time)) {
            static::setTime();
        }

        return static::$global_time;
    }

    /**
     * translate unit name to unit keyword.
     *
     * @param string $unit Unit name
     *
     * @return bool|string Returns unit keyword or false if unit name is invalid
     */
    public static function unit($unit)
    {
        $rgx_unit = [
            'ms' => '/^(\s+)?(ms|mils?|milliseconds?)?(\s+)?$/',
            's' => '/^(\s+)?(s|secs?|seconds?|timestamps?|ts)(\s+)?$/',
            'm' => '/^(\s+)?(m(in)?|mins?|minutes?)(\s+)?$/',
            'h' => '/^(\s+)?(h|hrs?|hours?)(\s+)?$/',
            'd' => '/^(\s+)?(d|days?)(\s+)?$/',
            'w' => '/^(\s+)?(w|weeks?)(\s+)?$/',
            'M' => '/^(\s+)?(M|months?)(\s+)?$/',
            'y' => '/^(\s+)?(y|years?)(\s+)?$/',
            'ly' => '/^(\s+)?(ly?|leaps?|leaps?\s?years?)(\s+)?$/',
        ];

        $unit = is_string($unit) && !empty(trim($unit)) ? trim($unit) : false;
        if ($unit) {
            foreach ($rgx_unit as $key => $rgx) {
                if (preg_match($rgx, $unit)) {
                    return $key;
                }
            }
        }

        return false;
    }

    /**
     * Translate time to given unit.
     *
     * @param string $query Time to translate
     * @param string $out   Unit to translate to
     *
     * @return float|int time in $out
     */
    public static function translate($query, $out = 'ms')
    {
        $out = static::unit($out);

        $rgx_time = [
            'ms' => '/^(\s+)?(\d+)(\s+)?(ms|mils?|milliseconds?)?(\s+)?$/',
            's' => '/^(\s+)?(\d+)(\s+)?(s|secs?|seconds?|timestamps?|ts)(\s+)?$/',
            'm' => '/^(\s+)?(\d+)(\s+)?(m(in)?|mins?|minutes?)(\s+)?$/',
            'h' => '/^(\s+)?(\d+)(\s+)?(h|hrs?|hours?)(\s+)?$/',
            'd' => '/^(\s+)?(\d+)(\s+)?(d|days?)(\s+)?$/',
            'w' => '/^(\s+)?(\d+)(\s+)?(w|weeks?)(\s+)?$/',
            'M' => '/^(\s+)?(\d+)(\s+)?(M|months?)(\s+)?$/',
            'y' => '/^(\s+)?(\d+)(\s+)?(y|years?)(\s+)?$/',
            'ly' => '/^(\s+)?(\d+)(\s+)?(ly?|leaps?|leaps?\s?years?)(\s+)?$/',
        ];

        $time = 0;
        $query = trim($query);
        foreach ($rgx_time as $key => $rgx) {
            if (preg_match($rgx, $query, $matches)) {
                $time = (float) $matches[2];
                $time = match ($key) {
                    's' => $time * 1000,
                    'm' => $time * 60000,
                    'h' => $time * 3600000,
                    'd' => $time * 86400000,
                    'w' => $time * 604800000,
                    'M' => $time * 2592000000,
                    'y' => $time * 31536000000,
                    'ly' => $time * 31622400000,
                    default => $time,
                };
            }
        }

        return static::ms_to($time, $out, DATE_TYPE_UNIT);
    }

    /**
     * Convert ms to other time unit.
     *
     * @param int    $ms   Milliseconds to convert
     * @param string $out  Output unit to translate to
     * @param int    $type Output type (`UNIT`|`FORMAT`)
     *
     * @return float|int|string time in $out
     */
    public static function ms_to($ms, $out = 'ms', $type = DATE_TYPE_UNIT)
    {
        $f_out = static::unit($out);
        $out = DATE_TYPE_UNIT === $type ? ($f_out ? $f_out : $out) : $out;

        $ms = floatval($ms);
        $opts = [
            'ms' => 1,
            's' => 1000,
            'm' => 60000,
            'h' => 3600000,
            'd' => 86400000,
            'w' => 604800000,
            'M' => 2592000000,
            'y' => 31536000000,
            'ly' => 31622400000,
        ];

        $dt = new \DateTime();

        if (isset($opts[$out]) && DATE_TYPE_UNIT === $type) {
            return $ms / $opts[$out];
        }
        if ('datetime' == $out) {
            return $dt->setTimestamp($ms / 1000)->format('Y-m-d H:i:s');
        }
        if ('date' == $out) {
            return $dt->setTimestamp($ms / 1000)->format('Y-m-d');
        }
        if ('time' == $out) {
            return $dt->setTimestamp($ms / 1000)->format('H:i:s');
        }
        if (DATE_TYPE_UNIT === $type) {
            throw new \Exception('Invalid output unit');
        }

        if (DATE_TYPE_FORMAT !== $type) {
            throw new \Exception('Invalid output type');
        }

        return $dt->setTimestamp($ms / 1000)->format($out);
    }

    /**
     * Date Time Magic Parser.
     *
     * @param string $query Date time to parse
     * @param string $out   Unit or format to translate to
     * @param mixed  $type
     *
     * @return float|int|string time in $out
     */
    public static function parse(string $query, $out = 's', $type = DATE_TYPE_UNIT)
    {
        $now = static::now();

        $dt = new \DateTime();
        $dt->setTimestamp($now);

        $rgx_parser = [
            'tr_time' => [
                'rgx' => '/(\d+)(\s+)?([a-zM]+)(\s+)?/',
                'cb' => function ($matches) {
                    return static::translate($matches[0], 's');
                },
            ],
            'datetime' => [
                'rgx' => '/(\d{4})-(\d{2})-(\d{2})\s(\d{2}):(\d{2}):(\d{2})/',
                'cb' => function ($matches) {
                    return strtotime($matches[0]);
                },
            ],
            'datetime_ns' => [
                'rgx' => '/(\d{4})-(\d{2})-(\d{2})\s(\d{2}):(\d{2})/',
                'cb' => function ($matches) {
                    return strtotime($matches[0].':00');
                },
            ],
            'datetime_nm' => [
                'rgx' => '/(\d{4})-(\d{2})-(\d{2})\s(\d{2})/',
                'cb' => function ($matches) {
                    return strtotime($matches[0].':00:00');
                },
            ],
            'date' => [
                'rgx' => '/(\d{4})-(\d{2})-(\d{2})/',
                'cb' => function ($matches) {
                    return strtotime($matches[0].' 00:00:00');
                },
            ],
            'month' => [
                'rgx' => '/(\d{4})-(\d{2})/',
                'cb' => function ($matches) {
                    return strtotime($matches[0].'-01 00:00:00');
                },
            ],
            'time' => [
                'rgx' => '/(\d{2}):(\d{2}):(\d{2})/',
                'cb' => function ($matches) use ($dt) {
                    $nd = $dt->format('Y-m-d').' '.$matches[0];

                    return strtotime($nd);
                },
            ],
            'time_ns' => [
                'rgx' => '/(\d{2}):(\d{2})/',
                'cb' => function ($matches) use ($dt) {
                    $nd = $dt->format('Y-m-d').' '.$matches[0].':00';

                    return strtotime($nd);
                },
            ],
            'now' => [
                'rgx' => '/(now|time)/',
                'cb' => function () use ($now) {
                    return $now;
                },
            ],
            'today' => [
                'rgx' => '/(today|date)/',
                'cb' => function () use ($dt) {
                    return $dt->format('Y-m-d');
                },
            ],
            'yesterday' => [
                'rgx' => '/yesterday/',
                'cb' => function () use ($dt) {
                    return $dt->modify('-1 day')->format('Y-m-d');
                },
            ],
            'tomorrow' => [
                'rgx' => '/(tomorrow|tom)/',
                'cb' => function () use ($dt) {
                    return $dt->modify('+1 day')->format('Y-m-d');
                },
            ],
            'after' => [
                'rgx' => '/after/',
                'cb' => function ($matches) {
                    return ' + ';
                },
            ],
            'before' => [
                'rgx' => '/before/',
                'cb' => function ($matches) {
                    return ' - ';
                },
            ],
            'ago' => [
                'rgx' => '/(\d+)(\s+)?ago/',
                'cb' => function ($matches) use ($now) {
                    return $now - $matches[1];
                },
            ],
        ];

        foreach ($rgx_parser as $rgx) {
            $query = preg_replace_callback($rgx['rgx'], $rgx['cb'], $query);
        }

        // check if safe to eval
        if (!preg_match('/^[\d\s\+\-\*\/\%\(\)]+$/', $query)) {
            throw new \Exception('Invalid query');
        }

        $ts = eval('return '.$query.';') * 1000;

        return static::ms_to($ts, $out, $type);
    }

    /**
     * Get a timestamp in milliseconds.
     *
     * @param null|int|string $timestamp or DateTime format string
     *
     * @return int timestamp
     *
     * @see https://www.php.net/manual/en/datetime.formats.php
     */
    public static function getTimestamp(null|int|string $timestamp = null)
    {
        $dt = new \DateTime();
        if (is_null($timestamp)) {
            if (static::$global_time) {
                $dt->setTimestamp(static::$global_time);
            }
        } elseif (!is_int($timestamp)) {
            $dt->setTimestamp($timestamp);
        } else {
            $dt = new \DateTime($timestamp);
        }

        return intval($dt->format('Uv'));
    }
}
