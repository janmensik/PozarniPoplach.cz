<?php

function filemtime_remote($string) {
    static $cache;

    if ($last[$string]) {
        return ($last[$string]);
    }

    $uri = parse_url($string);
    $uri['port'] = isset($uri['port']) ? $uri['port'] : 80;
    $handle = @fsockopen($uri['host'], $uri['port']);
    if (!$handle) {
        return 0;
    }

    fputs($handle, "HEAD $uri[path] HTTP/1.1\r\nHost: $uri[host]\r\n\r\n");
    $result = 0;
    while (!feof($handle)) {
        $line = fgets($handle, 1024);
        if (!trim($line)) {
            break;
        }

        $col = strpos($line, ':');
        if ($col !== false) {
            $header = trim(substr($line, 0, $col));
            $value = trim(substr($line, $col + 1));
            if (strtolower($header) == 'last-modified') {
                $result = strtotime($value);
                $last[$string] = $result;
                break;
            }
        }
    }
    fclose($handle);
    return $result;
}
