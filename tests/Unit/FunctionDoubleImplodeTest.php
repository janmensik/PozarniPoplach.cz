<?php

require_once __DIR__ . '/../../lib/functions/function.doubleimplode.php';

uses(\Tests\TestCase::class);

test('doubleimplode returns scalar input unchanged', function () {
    expect(doubleimplode(',', ';', 'scalar'))->toBe('scalar');
});

test('doubleimplode joins flat array with separator1', function () {
    expect(doubleimplode(',', ';', ['a', 'b', 'c']))->toBe('a,b,c');
});

test('doubleimplode joins 2D array with both separators', function () {
    $data = [['a', 'b'], ['c', 'd']];
    // inner arrays joined by separator1, outer by separator2
    expect(doubleimplode(',', ';', $data))->toBe('a,b;c,d');
});

test('doubleimplode handles array with a single scalar value', function () {
    expect(doubleimplode(',', ';', ['lone']))->toBe('lone');
});

test('doubleimplode handles single element flat array', function () {
    expect(doubleimplode(',', ';', ['only']))->toBe('only');
});

test('doubleimplode handles single element nested array', function () {
    $data = [['a', 'b']];
    expect(doubleimplode(',', ';', $data))->toBe('a,b');
});
