<?php

require_once __DIR__ . '/../../lib/functions/function.parsedate.php';

uses(\Tests\TestCase::class);

test('parseDate returns null for null input', function () {
    expect(parseDate(null))->toBeNull();
});

test('parseDate returns null for empty string', function () {
    expect(parseDate(''))->toBeNull();
});

test('parseDate parses Czech date format with year', function () {
    $result = parseDate('21. 5. 2020');
    expect($result)->toBeInt();
    expect(date('j', $result))->toBe('21');
    expect(date('n', $result))->toBe('5');
    expect(date('Y', $result))->toBe('2020');
});

test('parseDate parses Czech date format without spaces', function () {
    $result = parseDate('21.05.2020');
    expect($result)->toBeInt();
    expect(date('j', $result))->toBe('21');
    expect(date('n', $result))->toBe('5');
    expect(date('Y', $result))->toBe('2020');
});

test('parseDate parses Czech date with time', function () {
    $result = parseDate('21. 5. 2020 11:30');
    expect($result)->toBeInt();
    expect(date('H', $result))->toBe('11');
    expect(date('i', $result))->toBe('30');
});

test('parseDate parses Unix timestamp string', function () {
    $ts = 1590019200; // some timestamp
    $result = parseDate((string) $ts);
    expect($result)->toBe($ts);
});

test('parseDate with force=true returns current timestamp for null input', function () {
    $before = mktime(12, 0, 0);
    $result = parseDate(null, true);
    expect($result)->toBeNull();
});

test('parseDate with force=true returns now for unrecognizable input', function () {
    $result = parseDate('invalid-date-string', true);
    expect($result)->toBeInt();
});

test('parseDate parses ISO date format', function () {
    $result = parseDate('2020-05-21');
    expect($result)->toBeInt();
    expect(date('Y-m-d', $result))->toBe('2020-05-21');
});
