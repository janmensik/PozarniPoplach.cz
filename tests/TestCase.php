<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    //
}

// Define global functions that page controllers expect
if (!function_exists('pagination')) {
    function pagination($items_per_page, $total_items, $current_page, $dots) {
        return [];
    }
}
