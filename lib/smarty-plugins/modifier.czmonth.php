<?php

/**
 * Smarty modifier_czmonth plugin
 *
 * Type:     modifier<br>
 * Name:     czday<br>
 * Purpose:  get the czech text month
 * @param string $string
 * @param int $format
 * @return string|void
 */
function smarty_modifier_czmonth($string, $format = 1) {

    //require_once $smarty->_get_plugin_filepath('shared', 'make_timestamp');

    if ((int) $format < 1 || (int) $format > 4) {
        $format = 1;
    }

    $czmesice[1] = array(1 => 'leden', 'únor', 'březen', 'duben', 'květen', 'červen', 'červenec', 'srpen', 'září', 'říjen', 'listopad', 'prosinec');
    $czmesice[2] = array(1 => 'ledna', 'února', 'března', 'dubna', 'května', 'června', 'července', 'srpna', 'září', 'října', 'listopadu', 'prosince');

    $czmesice[3] = array(1 => 'Leden', 'Únor', 'Březen', 'Duben', 'Květen', 'Červen', 'Červenec', 'Srpen', 'Září', 'Říjen', 'Listopad', 'Prosinec');
    $czmesice[4] = array(1 => 'Ledna', 'Února', 'Března', 'Dubna', 'Května', 'Června', 'Července', 'Srpna', 'Září', 'Října', 'Listopadu', 'Prosince');

    $mesice = $czmesice[$format];

    if (0 < (int) $string && (int) $string < 13) {
        return $mesice[(int) $string];
    } elseif ($string != '') {
        return $mesice[date('n', $string)];
    } elseif (isset($default_date) && $default_date != '') {
        return $mesice[date('n', $default_date)];
    } else {
        return;
    }
}

/* vim: set expandtab: */
