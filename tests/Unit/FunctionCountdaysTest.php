<?php

require_once __DIR__ . '/../../lib/functions/function.countdays.php';

uses(\Tests\TestCase::class);

test('countdays returns 0 when from is 0', function () {
    expect(countdays(0, time()))->toBe(0);
});

test('countdays returns 0 when till is 0', function () {
    expect(countdays(time(), 0))->toBe(0);
});

test('countdays returns 0 for same timestamps', function () {
    $ts = mktime(12, 0, 0, 1, 1, 2023);
    expect(countdays($ts, $ts))->toEqual(0);
});

test('countdays returns 1 for one day difference', function () {
    $from = mktime(0, 0, 0, 1, 1, 2023);
    $till = mktime(23, 59, 59, 1, 2, 2023);
    expect(countdays($from, $till))->toEqual(1);
});

test('countdays returns 7 for one week difference', function () {
    $from = mktime(0, 0, 0, 1, 1, 2023);
    $till = mktime(0, 0, 0, 1, 8, 2023);
    expect(countdays($from, $till))->toEqual(7);
});

test('countdays returns 365 for full year 2022', function () {
    $from = mktime(0, 0, 0, 1, 1, 2022);
    $till = mktime(0, 0, 0, 12, 31, 2022);
    expect(countdays($from, $till))->toEqual(364);
});
