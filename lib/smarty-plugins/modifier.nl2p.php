<?php

/**
 * Smarty plugin
 *
 * Type:     modifier<br>
 * Name:     nl2p<br>
 * Purpose:  convert \r\n, \r or \n to paragraphs in <p> CONTENT </p>
 * @param string
 * @return string
 */
function smarty_modifier_nl2p($string) {
    return preg_replace('#<p>[\n\r\s]*?</p>#m', '', '<p>' . preg_replace('#(<br\s*?/?>){2,}#m', '</p><p>', nl2br($string)) . '</p>');
}
