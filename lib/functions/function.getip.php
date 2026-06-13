<?php

/**
 * @author      http://roshanbh.com.np/2007/12/getting-real-ip-address-in-php.html
 * @return      string     Returns "real" IP address of visitor
 */
function getip() {
    # check ip from share internet
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    # to check ip is pass from proxy
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // X-Forwarded-For can contain multiple IPs separated by commas
        $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ipList[0]);
    }
    # regular ip
    else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    }

    // Validate the IP address
    if (filter_var($ip, FILTER_VALIDATE_IP)) {
        return $ip;
    }

    return '0.0.0.0'; // Fallback if no valid IP is found
}
