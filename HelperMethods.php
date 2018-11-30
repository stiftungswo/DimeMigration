<?php

class HelperMethods
{
    public static function examineMoneyValue($value)
    {
        $cleanValue = $value;

        if (strpos($cleanValue, 'CHF') !== false) {
            $cleanValue = str_replace('CHF', '', $cleanValue);
        }

        if (strpos($cleanValue, '.') !== false) {
            $cleanValue = floatval($cleanValue) * 100;
        }

        return intval($cleanValue);
    }

    public static function printWithNewLine($message)
    {
        print($message . "\n");
    }
}
