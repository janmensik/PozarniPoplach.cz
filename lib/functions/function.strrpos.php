<?php

# nahrazka funkci stripos() a strripos()

if (!function_exists("stripos")) {
    function stripos(string $str, string $needle, int $offset = 0): int|false {
        return strpos(strtolower($str), strtolower($needle), $offset);
    }
}

if (!function_exists("strripos")) {
    function strripos(string $haystack, string $needle, int $offset = 0): int|false {
        if (!is_string($needle)) {
            $needle = chr(intval($needle));
        }
        if ($offset < 0) {
            $temp_cut = strrev(substr($haystack, 0, abs($offset)));
        } else {
            $temp_cut = strrev(substr($haystack, 0, max(( strlen($haystack) - $offset ), 0)));
        }
        if ((  $found = stripos($temp_cut, strrev($needle))  ) === false) {
            return false;
        }
        $pos = (   strlen($haystack) - (  $found + $offset + strlen($needle)  )   );
        return $pos;
    }
}
