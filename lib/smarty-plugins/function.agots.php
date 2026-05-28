<?php

/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 *
 * @param ts - timestamp
 * @param period - number of seconds
 * @param lang - language of output [en, cs, cs2]
 * @param justone - output only one datetime type (the biggest) ie. 13 hours | 43 minutes | 3 days etc
 * @param empty - what to show in case time is 0 (default "")
 */
function smarty_function_agots($params, &$smarty) {

    if (!isset($params['ts']) && !isset($params['period'])) {
            //$smarty->trigger_error("no time data");
            return;
    }
    //return $params['ts'];
    // array of time period chunks
    $chunks = array(
            array(60 * 60 * 24 * 365 , 'year'),
            array(60 * 60 * 24 * 30 , 'month'),
            array(60 * 60 * 24 * 7, 'week'),
            array(60 * 60 * 24 , 'day'),
            array(60 * 60 , 'hour'),
            array(60 , 'min'),
            array(1 , 'sec'),
    );

    $chunks_langs = array(
        'en' => array (
            array(60 * 60 * 24 * 365 , 'year', 'years'),
            array(60 * 60 * 24 * 30 , 'month', 'months'),
            array(60 * 60 * 24 * 7, 'week', 'weeks'),
            array(60 * 60 * 24 , 'day', 'days'),
            array(60 * 60 , 'hour', 'hours'),
            array(60 , 'min', 'mins'),
            array(1 , 'sec', 'secs')
            ),
        'cs' => array (
            array(60 * 60 * 24 * 365 , 'rok', 'roky', 'let'),
            array(60 * 60 * 24 * 30 , 'měsíc', 'měsíce', 'měsíců'),
            array(60 * 60 * 24 * 7, 'týden', 'týdny', 'týdnů'),
            array(60 * 60 * 24 , 'den', 'dny', 'dnů'),
            array(60 * 60 , 'hodina', 'hodiny', 'hodin'),
            array(60 , 'minuta', 'minuty', 'minut'),
            array(1 , 'sekunda', 'sekundy', 'sekund')
            ),
        'cs2' => array (
            array(60 * 60 * 24 * 365 , 'rokem', 'roky', 'lety'),
            array(60 * 60 * 24 * 30 , 'měsícem', 'měsíci', 'měsíci'),
            array(60 * 60 * 24 * 7, 'týdnem', 'týdny', 'týdny'),
            array(60 * 60 * 24 , 'dnem', 'dny', 'dny'),
            array(60 * 60 , 'hodinou', 'hodinami', 'hodinami'),
            array(60 , 'minutou', 'minutami', 'minutami'),
            array(1 , 'sekundou', 'sekundami', 'sekundami')
            ),
        );
    $chunks = is_array($chunks_langs[$params['lang']]) ? $chunks_langs[$params['lang']] : $chunks_langs['en'];

    $today = time(); /* Current unix time  */

    if ($params['period']) {
        $since = $params['period'];
    } elseif ($params['ts']) {
        $since = $today > $params['ts'] ? $today - $params['ts'] : $params['ts'] - $today;
    }

    if (!(int) $since) {
        return $params['empty'] ?: '';
    }

    // $j saves performing the count function each time around the loop
    for ($i = 0, $j = count($chunks); $i < $j; $i++) {
            $seconds = $chunks[$i][0];
            $name = $chunks[$i][1];
            $name24 = $chunks[$i][2] ? $chunks[$i][2] : $name;
            $name5 = $chunks[$i][3] ? $chunks[$i][3] : $name24;

            // finding the biggest chunk (if the chunk fits, break)
        if (($count = floor($since / $seconds)) != 0) {
                break;
        }
    }

    //require_once $smarty->_get_plugin_filepath('modifier','czech_num_items');

    //$print = ($count == 1) ? '1 '.$name : "$count {$name}s";
    $print = $count . ' ' . smarty_modifier_czech_num_items($count, $name, $name24, $name5);

    if ($i + 1 < $j && !$params['justone']) {
            // now getting the second item
            $seconds2 = $chunks[$i + 1][0];
            //$name2 = $chunks[$i + 1][1];
            $name2 = $chunks[$i + 1][1];
            $name224 = $chunks[$i + 1][2] ? $chunks[$i + 1][2] : $name2;
            $name25 = $chunks[$i + 1][3] ? $chunks[$i + 1][3] : $name224;
            // add second item if its greater than 0
        if (($count2 = floor(($since - ($seconds * $count)) / $seconds2)) != 0) {
                //$print .= ($count2 == 1) ? ', 1 '.$name2 : " $count2 {$name2}s";
                $print .= smarty_modifier_czech_num_items($count2, ', ' . $count2 . ' ' . $name2, ' ' . $count2 . ' ' . $name224, ' ' . $count2 . ' ' . $name25);
        }
    }
    return $print;
}
