<?php

/**
 * Smarty plugin
 *
 * @package    Smarty
 * @subpackage PluginsModifier
 */

use Machinateur\RomanNumerals\Convert;

/**
 * Smarty roman modifier plugin
 * Type:     modifier
 * Name:     roman
 * Purpose:  convert integer to roman numeral
 *
 * @param int $string input integer
 *
 * @return string
 */
function smarty_modifier_roman($string)
{
    try {
        return Convert::toRomanNumeral((int)$string);
    } catch (Exception $e) {
        return $string;
    }
}
