<?php

/**
 * float parseFloat (string)
 *
 *
 * @param string
 * @return float
 */

function parseFloat($str) {
    if (!isset($str)) {
        return null;
    }

    $str = str_replace(" ", "", $str);
    $str = str_replace(",", ".", $str); // replace ',' with '.'

    if (preg_match("#-?([0-9]+\.?[0-9]{0,5})#", $str, $match)) { // search for number that may contain '.'
        return floatval($match[0]);
    } else {
        return floatval($str); // take some last chances with floatval
    }
}
