<?php

/**
 * @author      http://roshanbh.com.np/2007/12/getting-real-ip-address-in-php.html
 * @return      string     Returns "real" IP address of visitor
 */
function getip() {
    $ip = '';
    # check ip from share internet
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    # to check ip is pass from proxy
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // HTTP_X_FORWARDED_FOR can contain a comma-separated list of IPs.
        // We take the first valid IP from the list.
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        foreach ($ips as $extracted_ip) {
            $extracted_ip = trim($extracted_ip);
            if (filter_var($extracted_ip, FILTER_VALIDATE_IP)) {
                $ip = $extracted_ip;
                break;
            }
        }
    }

    # fallback to remote addr if not found or invalid
    if (empty($ip) || !filter_var($ip, FILTER_VALIDATE_IP)) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    }

    return $ip;
}
