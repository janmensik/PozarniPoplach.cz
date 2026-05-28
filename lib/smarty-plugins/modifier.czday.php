<?php

/**
 * Smarty modifier_czday plugin
 *
 * Type:     modifier<br>
 * Name:     czday<br>
 * Purpose:  get the czech text day of the week
 * @param string $unix_timestamp
 * @param int $format
 * @return string
 */
function smarty_modifier_czday($unix_timestamp, $format = 1) {
    //require_once $smarty->_get_plugin_filepath('shared', 'make_timestamp');

    if ((int) $format < 1 || (int) $format > 2) {
        $format = 1;
    }

    $czdny[1] = array(0 => 'neděle', 'pondělí', 'úterý', 'středa', 'čtvrtek', 'pátek', 'sobota');
    $czdny[2] = array(0 => 'neděli', 'pondělí', 'úterý', 'středu', 'čtvrtek', 'pátek', 'sobotu');

    $dny = $czdny[$format];

    return $dny[date('w', $unix_timestamp)];
}
