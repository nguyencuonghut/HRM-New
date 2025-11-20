<?php

namespace App\Helpers;

class NumberToWords
{
    private static $units = ['', 'một', 'hai', 'ba', 'bốn', 'năm', 'sáu', 'bảy', 'tám', 'chín'];
    private static $teens = ['mười', 'mười một', 'mười hai', 'mười ba', 'mười bốn', 'mười lăm', 'mười sáu', 'mười bảy', 'mười tám', 'mười chín'];
    private static $tens = ['', '', 'hai mươi', 'ba mươi', 'bốn mươi', 'năm mươi', 'sáu mươi', 'bảy mươi', 'tám mươi', 'chín mươi'];
    private static $thousands = ['', 'nghìn', 'triệu', 'tỷ', 'nghìn tỷ', 'triệu tỷ'];

    /**
     * Convert number to Vietnamese words
     * Example: 6500000 -> "Sáu triệu năm trăm nghìn đồng"
     */
    public static function convert($number, string $currency = 'đồng'): string
    {
        if (!is_numeric($number)) {
            return '';
        }

        $number = (int) $number;

        if ($number === 0) {
            return 'Không ' . $currency;
        }

        if ($number < 0) {
            return 'Âm ' . self::convert(abs($number), $currency);
        }

        $words = self::convertNumber($number);

        // Capitalize only first letter, rest lowercase
        $words = mb_strtoupper(mb_substr($words, 0, 1)) . mb_substr($words, 1);

        return trim($words) . ' ' . $currency . ' chẵn.';
    }

    private static function convertNumber($number): string
    {
        if ($number < 10) {
            return self::$units[$number];
        }

        if ($number < 20) {
            return self::$teens[$number - 10];
        }

        if ($number < 100) {
            $tens = (int)($number / 10);
            $units = $number % 10;

            $result = self::$tens[$tens];

            if ($units === 1 && $tens > 1) {
                $result .= ' mốt';
            } elseif ($units === 5 && $tens > 1) {
                $result .= ' lăm';
            } elseif ($units > 0) {
                $result .= ' ' . self::$units[$units];
            }

            return $result;
        }

        if ($number < 1000) {
            $hundreds = (int)($number / 100);
            $remainder = $number % 100;

            $result = self::$units[$hundreds] . ' trăm';

            if ($remainder > 0) {
                if ($remainder < 10) {
                    $result .= ' lẻ';
                }
                $result .= ' ' . self::convertNumber($remainder);
            }

            return $result;
        }

        // For numbers >= 1000, split into groups of 3 digits
        $groups = [];
        $groupIndex = 0;

        while ($number > 0) {
            $group = $number % 1000;
            if ($group > 0) {
                $groupWords = self::convertNumber($group);
                if (self::$thousands[$groupIndex]) {
                    $groupWords .= ' ' . self::$thousands[$groupIndex];
                }
                array_unshift($groups, $groupWords);
            }
            $number = (int)($number / 1000);
            $groupIndex++;
        }

        return implode(' ', $groups);
    }
}
