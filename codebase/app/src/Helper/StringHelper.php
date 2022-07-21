<?php

namespace App\Helper;

class StringHelper
{
    public static function camelCaseToSnakeCase(string $string) : string
    {
        return strtolower(preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1_$2', $string));
    }

    public static function containsCapitalLetter(string $string): bool
    {
        return preg_match('/[A-Z]/', $string);
    }

    public static function containsLowercaseLetter(string $string): bool
    {
        return preg_match('/[a-z]/', $string);
    }

    public static function containsDigit(string $string): bool
    {
        return preg_match('/\d/', $string);
    }

    public static function nullForEmpty(string $string): ?string
    {
        return $string === '' ? null : $string;
    }
}