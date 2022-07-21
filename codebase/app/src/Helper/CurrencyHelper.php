<?php


namespace App\Helper;


class CurrencyHelper
{
    public static function euro($num, $sign = true, $decimalSeparator = ',', $thousandSeparator = '.') {
        $num = number_format($num/100, 2, $decimalSeparator, $thousandSeparator);
        if ($sign) $num .= " €";
        return $num;
    }
}