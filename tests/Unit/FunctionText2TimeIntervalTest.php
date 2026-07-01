<?php

require_once __DIR__ . '/../../lib/functions/function.text2timeinterval.php';

uses(\Tests\TestCase::class);

// Use a fixed reference timestamp: 2023-06-15 (Thursday, mid-year)
$now = mktime(12, 0, 0, 6, 15, 2023);

test('text2timeinterval today returns correct from/till for today', function () use ($now) {
    $result = text2timeinterval('today', $now);
    expect($result['from'])->toBe(mktime(0, 0, 0, 6, 15, 2023));
    expect($result['till'])->toBe(mktime(23, 59, 59, 6, 15, 2023));
});

test('text2timeinterval yesterday returns correct from/till for previous day', function () use ($now) {
    $result = text2timeinterval('yesterday', $now);
    expect($result['from'])->toBe(mktime(0, 0, 0, 6, 14, 2023));
    expect($result['till'])->toBe(mktime(23, 59, 59, 6, 14, 2023));
});

test('text2timeinterval last7 spans 7 days ending today', function () use ($now) {
    $result = text2timeinterval('last7', $now);
    $from = mktime(0, 0, 0, 6, 9, 2023); // 6 days before today
    $till = mktime(23, 59, 59, 6, 15, 2023);
    expect($result['from'])->toBe($from);
    expect($result['till'])->toBe($till);
});

test('text2timeinterval month returns current month boundaries', function () use ($now) {
    $result = text2timeinterval('month', $now);
    expect($result['from'])->toBe(mktime(0, 0, 0, 6, 1, 2023));
    expect($result['till'])->toBe(mktime(23, 59, 59, 6, 30, 2023));
});

test('text2timeinterval tomorrow returns correct from/till for next day', function () use ($now) {
    $result = text2timeinterval('tomorrow', $now);
    expect($result['from'])->toBe(mktime(0, 0, 0, 6, 16, 2023));
    expect($result['till'])->toBe(mktime(23, 59, 59, 6, 16, 2023));
});

test('text2timeinterval thisyear returns full current year', function () use ($now) {
    $result = text2timeinterval('thisyear', $now);
    expect($result['from'])->toBe(mktime(0, 0, 0, 1, 1, 2023));
    expect($result['till'])->toBe(mktime(23, 59, 59, 12, 31, 2023));
});

test('text2timeinterval lastyear returns full previous year', function () use ($now) {
    $result = text2timeinterval('lastyear', $now);
    expect($result['from'])->toBe(mktime(0, 0, 0, 1, 1, 2022));
    expect($result['till'])->toBe(mktime(23, 59, 59, 12, 31, 2022));
});

test('text2timeinterval from is always less than or equal to till', function () {
    $now = mktime(12, 0, 0, 6, 15, 2023);
    foreach (['today', 'yesterday', 'last7', 'lastweek', 'month', 'lastmonth', 'tomorrow', 'nextmonth', 'next7', 'thisyear', 'lastyear', 'last6months', 'last3months'] as $interval) {
        $result = text2timeinterval($interval, $now);
        if (isset($result['from'], $result['till'])) {
            expect($result['from'])->toBeLessThanOrEqual($result['till'], "Failed for interval: $interval");
        }
    }
});
