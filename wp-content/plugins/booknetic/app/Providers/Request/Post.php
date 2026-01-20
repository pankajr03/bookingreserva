<?php

namespace BookneticApp\Providers\Request;

//That's why generics...
class Post implements Request
{
    public static function string(string $key, string $default = '', array $whiteList = []): string
    {
        if (empty($_POST[ $key ])) {
            return $default;
        }

        $field = $_POST[ $key ];

        if (! is_string($field)) {
            return $default;
        }

        $field = trim(stripslashes_deep($field));

        if (! empty($whiteList) && ! in_array($field, $whiteList)) {
            return $default;
        }

        return $field;
    }

    public static function int(string $key, int $default = 0, array $whiteList = []): int
    {
        if (!isset($_POST[ $key ])) {
            return $default;
        }

        $field = $_POST[ $key ];

        if (! is_numeric($field)) {
            return $default;
        }

        if (! empty($whiteList) && ! in_array($field, $whiteList)) {
            return $default;
        }

        return (int) $field;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        return ! empty($_POST[ $key ]) ?: $default;
    }

    public static function boolString(string $key, $default = false): bool
    {
        return ! empty($_POST[ $key ]) && $_POST[ $key ] !== 'false' ?: $default;
    }

    public static function array(string $key, array $default = [], array $whiteList = []): array
    {
        if (empty($_POST[ $key ])) {
            return $default;
        }

        $field = $_POST[ $key ];

        if (! is_array($field)) {
            return $default;
        }

        $field = stripslashes_deep($field);

        if (! empty($whiteList) && ! in_array($field, $whiteList)) {
            return $default;
        }

        return $field;
    }

    public static function json(string $key, array $default = []): array
    {
        if (! isset($_POST[ $key ])) {
            return $default;
        }

        $field = json_decode(trim(stripslashes_deep($_POST[ $key ])), true);

        return is_array($field) ? $field : $default;
    }

    public static function float(string $key, float $default = 0.00): float
    {
        if (!isset($_POST[ $key ])) {
            return $default;
        }

        $field = $_POST[ $key ];

        return is_numeric($field) ? (float) $field : $default;
    }

    public static function email(string $key, string $default = ''): string
    {
        if (!isset($_POST[ $key ])) {
            return $default;
        }

        $field = $_POST[ $key ];

        if (!is_string($field) || filter_var($field, FILTER_VALIDATE_EMAIL) === false) {
            return $default;
        }

        return trim($field);
    }
}
