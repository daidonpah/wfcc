<?php


namespace App\Helper;


class RandomStringGenerator
{
    public const LOWERCASE_VOWELS = 'lv';
    public const UPPERCASE_VOWELS = 'uv';
    public const LOWERCASE_CONSONANTS = 'lc';
    public const UPPERCASE_CONSONANTS = 'uc';
    public const DIGITS = 'd';
    public const SPECIAL_CHARACTERS = 'sc';

    public static function generate(
        int $length = 10,
        bool $avoidConfusableChars = false,
        array $selectedSets = []
    ): string
    {
        $availableSets = [
            'lv' => 'aeiu' . ($avoidConfusableChars ? '' : 'o'),
            'uv' => 'AEU' . ($avoidConfusableChars ? '' : 'IO'),
            'lc' => 'bcdfghjkmnpqrstvwxyz' . ($avoidConfusableChars ? '' : 'l'),
            'uc' => 'BCDFGHJKMNPQRSTVWXYZ' . ($avoidConfusableChars ? '' : 'L'),
            'd'  => '23456789' . ($avoidConfusableChars ? '' : '10'),
            'sc' => '!@#$%&*?=_-'
        ];
        if (count($selectedSets) === 0) {
            $selectedSets = array_keys($availableSets);
        }
        $all = '';
        $generatedString = '';
        foreach($selectedSets as $set)
        {
            $generatedString .= $availableSets[$set][array_rand(str_split($availableSets[$set]))];
            $all .= $availableSets[$set];
        }
        $all = str_split($all);
        for ($i = 0; $i < $length - count($selectedSets); $i++) {
            $generatedString .= $all[array_rand($all)];
        }
        return substr(str_shuffle($generatedString), 0, $length);
    }
}