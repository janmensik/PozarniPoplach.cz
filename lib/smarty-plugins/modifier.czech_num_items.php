<?php

/**
 * Smarty czech_num_items modifier plugin
 *
 * Type:     modifier<br>
 * Name:     czech_num_items<br>
 * Purpose:  Return correct czech grammatical form of word after number (1 kus, 2 kusy, 5 kusů, 10 kusů)
 *
 * @param integer $int
 * @param string $count_1
 * @param string $count_2to4
 * @param string $count_5plus
 * @return string
 */
function smarty_modifier_czech_num_items($int, $count_1 = null, $count_2to4 = null, $count_5plus = null) {
    if (!is_numeric($int)) {
        return null;
    }
    switch ((int) substr((string) round($int), -1)) {
        case 1:
            if ((int) $int < 11 || (int) $int > 100) {
                return ($count_1);
            }
            // fall through
        case 2:
        case 3:
        case 4:
            if ((int) $int < 11 || (int) $int > 100) {
                return ($count_2to4);
            }
            // fall through
        case 0:
        case 5:
        case 6:
        case 7:
        case 8:
        case 9:
        default:
            return ($count_5plus);
    }
}
