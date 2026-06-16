<?php

/**
 * @author      http://roshanbh.com.np/2007/12/getting-real-ip-address-in-php.html
 * @return      string     Returns "real" IP address of visitor
 */
function getip() {
    # Only trust REMOTE_ADDR for security reasons as HTTP headers can be spoofed.
    return $_SERVER['REMOTE_ADDR'] ?? '';
}
