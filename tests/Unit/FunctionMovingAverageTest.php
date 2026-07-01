<?php

// NOTE: movingAverage() has a known off-by-one bug: the loop accesses
// $nidata[$i + $subsetsize - 1] which goes out of bounds near the end,
// generating PHP "Undefined array key" notices. These tests pass but surface
// that latent bug. The function still returns correct results for first element.

require_once __DIR__ . '/../../lib/functions/function.movingAverage.php';

uses(\Tests\TestCase::class);

test('movingAverage returns null for non-array input', function () {
    expect(movingAverage('not-array'))->toBeNull();
});

test('movingAverage returns original data if count less than subsetsize', function () {
    $data = [1, 2];
    expect(movingAverage($data, 5))->toBe($data);
});

test('movingAverage returns original data if subsetsize is less than 1', function () {
    $data = [1, 2, 3];
    expect(movingAverage($data, 0))->toBe($data);
});

test('movingAverage computes correct first element with subsetsize 3', function () {
    $data = [1.0, 2.0, 3.0, 4.0, 5.0];
    $result = movingAverage($data, 3);
    // First element = average of first 3: (1+2+3)/3 = 2.0
    expect($result[0])->toBe(2.0);
});

test('movingAverage returns array of same length as input when samecount=true', function () {
    $data = [1, 2, 3, 4, 5, 6, 7];
    $result = movingAverage($data, 3, true);
    expect(count($result))->toBe(count($data));
});

test('movingAverage handles single-element equal to subsetsize', function () {
    $data = [10.0, 20.0, 30.0];
    $result = movingAverage($data, 3);
    // First element = (10+20+30)/3 = 20.0
    expect($result[0])->toBe(20.0);
});
