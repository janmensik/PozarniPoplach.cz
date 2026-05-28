<?php

/**
 * Smarty nice_num modifier plugin
 *
 * Type:     modifier<br>
 * Name:     nice_num<br>
 * Purpose:  Format number with Czech language specific rules
 *
 * @param int|float|string $number The number to format
 * @param string $thousandsSeparator The separator to use for thousands
 * @param int $decimalPlaces The number of decimal places to display
 * @param string $decimalSeparator The separator to use for decimals
 * @return string The formatted number
 */
function smarty_modifier_nice_num($number, $thousandsSeparator = ' ', $decimalPlaces = 0, $decimalSeparator = ',') {
    if (!is_numeric($number)) {
        return $number;
    }
    return number_format(
        $number,
        $decimalPlaces,
        $decimalSeparator,
        $thousandsSeparator
    );
}
