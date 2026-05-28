<?php

/**
 * Smarty plugin
 *
 * @package Smarty
 * @subpackage plugins
 */

/**
 * Smarty {randomize} function plugin
 *
 * Type:     function<br>
 * Name:     randomize<br>
 * Date:     April 23, 2004<br>
 * Purpose:  randomize through given values<br>
 * Input:
 *         - values = comma separated list of values to cycle,
 *                    or an array of values to cycle
 *                    (this can be left out for subsequent calls)
 *         - delimiter = the value delimiter, default is ","
 *         - assign = boolean, assigns to template var instead of
 *                    printed.
 *
 * Examples:<br>
 * <pre>
 * {randomize values="one,two,three,four,five,six,seven,eight,nine,ten"}
 * </pre>
 *
 * @author Hermawan Haryanto <hermawan@codewalkers.com>
 * @version 1.0
 * @param array $params parameters
 * @param Smarty_Internal_Template $smarty template object
 * @return string|null
 */
function smarty_function_randomize(array $params, $smarty)
{
    static $cycle_vars;
    $name = '';
    $print = (isset($params['print'])) ? (bool)$params['print'] : true;
    if (!isset($params['values'])) {
        if (!isset($cycle_vars[$name]['values'])) {
            $smarty->trigger_error('randomize: missing \'values\' parameter');
            return null;
        }
    } else {
        if (isset($cycle_vars[$name]['values']) && $cycle_vars[$name]['values'] != $params['values']) {
            $cycle_vars[$name]['index'] = 0;
        }
        $cycle_vars[$name]['values'] = $params['values'];
    }

    $delimiter = $params['delimiter'] ?? $params['delimeter'] ?? ',';
    $cycle_vars[$name]['delimiter'] = $delimiter;

    if (is_array($cycle_vars[$name]['values'])) {
        $cycle_array = $cycle_vars[$name]['values'];
    } else {
        $cycle_array = explode($delimiter, $cycle_vars[$name]['values']);
    }

    $rand_keys = array_rand($cycle_array, 2);
    $retval = $cycle_array[$rand_keys[0]];

    if (isset($params['assign'])) {
        $print = false;
        $smarty->assign($params['assign'], $retval);
    }

    if (!$print) {
        $retval = null;
    }

    return $retval;
}
