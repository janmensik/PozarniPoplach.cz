<?php

require_once __DIR__ . '/../../lib/functions/function.parseFloat.php';

uses(\Tests\TestCase::class);

test('parseFloat parses plain float string', function () {
    expect(parseFloat('3.14'))->toBe(3.14);
});

test('parseFloat converts comma decimal separator to dot', function () {
    expect(parseFloat('3,14'))->toBe(3.14);
});

test('parseFloat removes spaces', function () {
    expect(parseFloat('1 234.56'))->toBe(1234.56);
});

test('parseFloat handles integer string', function () {
    expect(parseFloat('42'))->toBe(42.0);
});

test('parseFloat handles negative number', function () {
    expect(parseFloat('-12.5'))->toBe(-12.5);
});

test('parseFloat returns 0.0 for non-numeric string', function () {
    expect(parseFloat('abc'))->toBe(0.0);
});

test('parseFloat parses mixed string extracting number', function () {
    $result = parseFloat('Price: 99.90 EUR');
    expect($result)->toBe(99.9);
});
