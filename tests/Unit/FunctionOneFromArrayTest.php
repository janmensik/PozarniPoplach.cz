<?php

require_once __DIR__ . '/../../lib/functions/function.oneFromArray.php';

uses(\Tests\TestCase::class);

test('oneFromArray returns null for non-array input', function () {
    expect(oneFromArray('not-an-array', 'key'))->toBeNull();
});

test('oneFromArray returns null for empty key', function () {
    expect(oneFromArray([['id' => 1]], ''))->toBeNull();
    expect(oneFromArray([['id' => 1]], null))->toBeNull();
});

test('oneFromArray extracts column from array of arrays', function () {
    $data = [
        ['id' => 1, 'name' => 'Alice'],
        ['id' => 2, 'name' => 'Bob'],
    ];
    expect(oneFromArray($data, 'name'))->toBe([0 => 'Alice', 1 => 'Bob']);
});

test('oneFromArray extracts id column', function () {
    $data = [
        ['id' => 10, 'name' => 'Alice'],
        ['id' => 20, 'name' => 'Bob'],
    ];
    expect(oneFromArray($data, 'id'))->toBe([0 => 10, 1 => 20]);
});

test('oneFromArray preserves original array keys', function () {
    $data = [
        5 => ['id' => 10, 'name' => 'Alice'],
        9 => ['id' => 20, 'name' => 'Bob'],
    ];
    $result = oneFromArray($data, 'name');
    expect($result)->toHaveKey(5);
    expect($result)->toHaveKey(9);
    expect($result[5])->toBe('Alice');
});
