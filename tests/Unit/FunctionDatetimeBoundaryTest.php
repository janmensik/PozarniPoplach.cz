<?php

require_once __DIR__ . '/../../lib/functions/function.datetimeboundary.php';

uses(\Tests\TestCase::class);

// Use a fixed reference timestamp: 2023-06-15 14:37:22 (Thursday)
$refTs = mktime(14, 37, 22, 6, 15, 2023);

test('datetimeboundary day start returns start of day', function () use ($refTs) {
    $result = datetimeboundary('day', $refTs, false);
    expect(date('H:i:s', $result))->toBe('00:00:00');
    expect(date('Y-m-d', $result))->toBe('2023-06-15');
});

test('datetimeboundary day end returns end of day', function () use ($refTs) {
    $result = datetimeboundary('day', $refTs, true);
    expect(date('H:i:s', $result))->toBe('23:59:59');
    expect(date('Y-m-d', $result))->toBe('2023-06-15');
});

test('datetimeboundary month start returns first day of month', function () use ($refTs) {
    $result = datetimeboundary('month', $refTs, false);
    expect(date('d', $result))->toBe('01');
    expect(date('H:i:s', $result))->toBe('00:00:00');
    expect(date('Y-m', $result))->toBe('2023-06');
});

test('datetimeboundary month end returns last day of month', function () use ($refTs) {
    $result = datetimeboundary('month', $refTs, true);
    expect(date('d', $result))->toBe('30');
    expect(date('H:i:s', $result))->toBe('23:59:59');
});

test('datetimeboundary year start returns January 1st', function () use ($refTs) {
    $result = datetimeboundary('year', $refTs, false);
    expect(date('m-d', $result))->toBe('01-01');
    expect(date('H:i:s', $result))->toBe('00:00:00');
});

test('datetimeboundary year end returns December 31st', function () use ($refTs) {
    $result = datetimeboundary('year', $refTs, true);
    expect(date('m-d', $result))->toBe('12-31');
    expect(date('H:i:s', $result))->toBe('23:59:59');
});

test('datetimeboundary hour start returns start of hour', function () use ($refTs) {
    $result = datetimeboundary('hour', $refTs, false);
    expect(date('H', $result))->toBe('14');
    expect(date('i:s', $result))->toBe('00:00');
});

test('datetimeboundary hour end returns end of hour', function () use ($refTs) {
    $result = datetimeboundary('hour', $refTs, true);
    expect(date('H', $result))->toBe('14');
    expect(date('i:s', $result))->toBe('59:59');
});
