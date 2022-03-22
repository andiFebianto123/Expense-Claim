<?php

if (!function_exists('formatNumber')) {
    function formatNumber($number, $decimal = '.', $thousand = ',')
    {
        $number = (string) $number;
        if ($number === null || strlen($number) == 0 || (strlen($number) == 1 && $number[0] == '-')) {
            return '-';
        }
        $negative = $number[0] == '-' ? '-' : '';
        if ($negative == '-') {
            $number = substr($number, 1);
        }
        $broken_number = explode(/*$decimal*/ '.', $number);
        $broken_number_length = strlen($broken_number[0]) - 1;
        $formatted_number = '';
        while ($broken_number_length >= 0) {
            $prefix = $thousand;
            $indexStart = $broken_number_length - 2;
            $number_length = 3;
            if ($indexStart <= 0) {
                $prefix = '';
                if ($indexStart < 0) {
                    $number_length += $indexStart;
                    $indexStart = 0;
                }
            }
            $formatted_number = substr_replace($formatted_number, $prefix . substr($broken_number[0], $indexStart, $number_length), 0, 0);
            $broken_number_length -= 3;
        }
        $final_formatted_number = $formatted_number . (isset($broken_number[1]) ? ($decimal . $broken_number[1]) : '');
        return $negative . removeTrailingZeroAfterComa($final_formatted_number);
    }
}
if (!function_exists('removeTrailingZeroAfterComa')) {
    function removeTrailingZeroAfterComa($number, $coma = '.')
    {
        if (!isset($number)) {
            return '';
        }
        $number = (string) $number;
        if (false !== strpos($number, $coma)) {
            $number = rtrim(rtrim($number, '0'), $coma);
        }

        return $number;
    }
}

if (!function_exists('formatDate')) {
    function formatDate($dateString, $fromFormat = null, $toFormat = 'd M Y', $useTranslate = false)
    {
        if ($dateString === null || strlen($dateString) == 0) {
            return '-';
        }
        if($fromFormat === null){
            $dateCarbon = Carbon\Carbon::parse($dateString);
        }
        else{
            $dateCarbon = Carbon\Carbon::createFromFormat($fromFormat, $dateString);
        }
        $method = 'format';
        if($useTranslate){
            $method = 'translatedFormat';
        }
        return $dateCarbon->{$method}($toFormat);
    }
}
