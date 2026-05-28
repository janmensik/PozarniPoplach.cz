<?php

/**
 * Smarty plugin
 *
 * Type:     modifier<br>
 * Name:     nl2br<br>
 * Purpose:  convert \r\n, \r or \n to <<br>>
 * @param string
 * @return string
 */
function smarty_modifier_nl2br($string) {
    return nl2br($string);
}
