<?php

/**
 * Namespace shims for PozarniPoplach domain classes.
 *
 * Domain classes (class.User.php, class.Ad.php, class.DeviceAuth.php, etc.)
 * live in the PozarniPoplach namespace and call mysqli_real_escape_string()
 * as an unqualified function name. PHP resolves unqualified function calls by
 * first looking in the current namespace, then falling back to the global
 * namespace.
 *
 * In unit tests, $this->DB->db is a PHPUnit mock object — not a real mysqli
 * connection. Calling the built-in mysqli_real_escape_string() with a mock
 * throws "object is already closed". This shim provides a safe fallback using
 * addslashes() so all test files are hermetic, regardless of load order.
 */

namespace PozarniPoplach {
    if (!function_exists('PozarniPoplach\\mysqli_real_escape_string')) {
        function mysqli_real_escape_string($mysqli, string $string): string
        {
            return addslashes($string);
        }
    }
}
