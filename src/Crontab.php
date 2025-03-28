<?php

namespace Wyue;

use DateTime;

/**
 * Extended Contab Validator.
 */
final class Crontab
{
    /**
     * Check if expression matches datetime.
     */
    final public static function match(string $expression, ?\DateTime $datetime): bool
    {
        if (!$datetime) {
            $datetime = new \DateTime();
        }

        $reserved = [
            '@yearly' => '0 0 1 1 *',
            '@yearl' => '0 0 1 1 *',
            '@annually' => '0 0 1 1 *',
            '@annual' => '0 0 1 1 *',
            '@monthly' => '0 0 1 * *',
            '@month' => '0 0 1 * *',
            '@weekly' => '0 0 * * 0',
            '@week' => '0 0 * * 0',
            '@daily' => '0 0 * * *',
            '@day' => '0 0 * * *',
            '@midnight' => '0 0 * * *',
            '@nightly' => '0 0 * * *',
            '@night' => '0 0 * * *',
            '@hourly' => '0 * * * *',
            '@hour' => '0 * * * *',
            '@minutely' => '* * * * *',
            '@minute' => '* * * * *',
            '@secondly' => '* * * * * *',
            '@second' => '* * * * * *',
        ];

        $srvt = strtolower(trim($expression));
        if (isset($reserved[$srvt])) {
            $expression = $reserved[$srvt];
        }

        $ex = preg_replace('/\s+/', ' ', trim($expression));
        $ea = explode(' ', $ex);
        $ea = array_filter($ea, fn ($v) => '' !== $v);

        $dt_Y = (int) $datetime->format('Y');
        $dt_m = (int) $datetime->format('m');
        $dt_d = (int) $datetime->format('d');
        $dt_H = (int) $datetime->format('H');
        $dt_i = (int) $datetime->format('i');
        $dt_s = (int) $datetime->format('s');
        $dt_w = (int) $datetime->format('N');
        $dt_w = (7 === $dt_w ? 1 : $dt_w + 1) - 1;
        $dt_m_max = (int) $datetime->format('t');

        $bmin = 5 === count($ea);
        $bsec = 6 === count($ea);
        $byear = 7 === count($ea);

        if (!$bsec && !$bmin && !$byear) {
            return self::matchdate($expression, $datetime);
        }

        if ($bmin) {
            if (!self::pexc($ea[4], $dt_w, 0, 6)) {
                return false;
            }
            if (!self::pexc($ea[3], $dt_m, 1, 12)) {
                return false;
            }
            if (!self::pexc($ea[2], $dt_d, 1, $dt_m_max)) {
                return false;
            }
            if (!self::pexc($ea[1], $dt_H, 0, 23)) {
                return false;
            }
            if (!self::pexc($ea[0], $dt_i, 0, 59)) {
                return false;
            }
            if (0 !== $dt_s) {
                return false;
            }

            return true;
        }
        if ($bsec) {
            if (!self::pexc($ea[5], $dt_w, 0, 6)) {
                return false;
            }
            if (!self::pexc($ea[4], $dt_m, 1, 12)) {
                return false;
            }
            if (!self::pexc($ea[3], $dt_d, 1, $dt_m_max)) {
                return false;
            }
            if (!self::pexc($ea[2], $dt_H, 0, 23)) {
                return false;
            }
            if (!self::pexc($ea[1], $dt_i, 0, 59)) {
                return false;
            }
            if (!self::pexc($ea[0], $dt_s, 0, 59)) {
                return false;
            }

            return true;
        }
        if ($byear) {
            $cy = (int) date('Y');
            if (!self::pexc($ea[6], $dt_Y, $cy, false)) {
                return false;
            }
            if (!self::pexc($ea[5], $dt_w, 0, 6)) {
                return false;
            }
            if (!self::pexc($ea[4], $dt_m, 1, 12)) {
                return false;
            }
            if (!self::pexc($ea[3], $dt_d, 1, $dt_m_max)) {
                return false;
            }
            if (!self::pexc($ea[2], $dt_H, 0, 23)) {
                return false;
            }
            if (!self::pexc($ea[1], $dt_i, 0, 59)) {
                return false;
            }
            if (!self::pexc($ea[0], $dt_s, 0, 59)) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Evaludate date expression.
     */
    private static function matchdate(string $date, \DateTime $datetime): bool
    {
        $allowed = [
            'Y-m-d H:i:s',
            \DateTime::ATOM,
            \DateTime::COOKIE,
            \DateTime::ISO8601,
            \DateTime::ISO8601_EXPANDED,
            \DateTime::RFC822,
            \DateTime::RFC850,
            \DateTime::RFC1036,
            \DateTime::RFC1123,
            \DateTime::RFC2822,
            \DateTime::RFC3339,
            \DateTime::RFC3339_EXTENDED,
            \DateTime::RSS,
            \DateTime::W3C,
        ];

        foreach ($allowed as $format) {
            $dt = \DateTime::createFromFormat($format, $date);
            if ($dt && $dt->format($format) === $date) {
                return $dt->getTimestamp() === $datetime->getTimestamp();
            }
        }

        return false;
    }

    /**
     * Evaluate expression with comma separated values.
     */
    private static function pexc(string $expression, int $value, int $min, int $max): bool
    {
        $exs = explode(',', $expression);
        foreach ($exs as $ex) {
            if (self::pexr($ex, $value, $min, $max)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Evaluate expression.
     */
    private static function pexr(string $expression, int $value, int $min, false|int $max): bool
    {
        if ('*' === $expression) {
            return true;
        }
        if (preg_match('/^\*\/(\d+)$/', $expression, $matches)) {
            $n = (int) $matches[1];
            if ($n < 1) {
                return false;
            }
            if (0 !== $value % $n) {
                return false;
            }

            return true;
        }
        if (preg_match('/^(\d+)$/', $expression, $matches)) {
            $n = (int) $matches[1];
            if (false === $max && $n < $min) {
                return false;
            }
            if (false !== $max && ($n < $min || $n > $max)) {
                return false;
            }
            if ($n !== $value) {
                return false;
            }

            return true;
        }
        if (preg_match('/^(\d+)-(\d+)$/', $expression, $matches)) {
            $n1 = (int) $matches[1];
            $n2 = (int) $matches[2];
            if (false === $max && $n1 < $min) {
                return false;
            }
            if (false !== $max && ($n1 < $min || $n1 > $max)) {
                return false;
            }
            if (false === $max && $n2 < $min) {
                return false;
            }
            if (false !== $max && ($n2 < $min || $n2 > $max)) {
                return false;
            }
            if ($n1 > $n2) {
                return false;
            }
            if ($value < $n1 || $value > $n2) {
                return false;
            }

            return true;
        }

        return false;
    }
}
