<?php

namespace Main;

class HelperMethods
{
    public static function examineMoneyValue($value, $allowNull = false)
    {
        $cleanValue = $value;

        if (strpos($cleanValue, 'CHF') !== false) {
            $cleanValue = str_replace('CHF', '', $cleanValue);
        }

        if (strpos($cleanValue, '.') !== false) {
            $cleanValue = floatval($cleanValue) * 100;
        }

        $cleanValue = intval($cleanValue);

        if ($cleanValue === 0) {
            return $allowNull ? null : 0;
        } else {
            return $cleanValue;
        }
    }

    public static function printWithNewLine($message)
    {
        print($message . "\n");
    }
}
