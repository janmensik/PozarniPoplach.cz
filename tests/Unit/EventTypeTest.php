<?php

require_once __DIR__ . '/../../include/class.EventType.php';

use PozarniPoplach\EventType;
use Janmensik\Jmlib\Database;

uses(\Tests\TestCase::class);

beforeEach(function () {
    $this->db = $this->createMock(Database::class);
    // We still need a dummy mysqli for type hinting
    $this->db->db = $this->getMockBuilder('mysqli')
                         ->disableOriginalConstructor()
                         ->getMock();

    $this->eventType = new EventType($this->db);
});

test('EventType validation fails if name is empty', function () {
    $this->eventType->data = ['name' => ''];
    $errors = $this->eventType->validate();

    expect($errors)->toHaveKey('name');
    expect($errors['name'])->toBe('Name is required');
});

test('EventType validation sets default level if empty', function () {
    $this->eventType->data = ['name' => 'Test Event', 'level' => ''];
    $this->eventType->validate();

    expect($this->eventType->data['level'])->toBe(1);
});

test('EventType validation fails if level is invalid', function () {
    $this->eventType->data = ['name' => 'Test Event', 'level' => -1];
    $errors = $this->eventType->validate();

    expect($errors)->toHaveKey('level');
    expect($errors['level'])->toBe('Level must be a positive number or empty');
});

test('EventType validation handles empty parent_id', function () {
    $this->eventType->data = ['name' => 'Test Event', 'parent_id' => ''];
    $this->eventType->validate();

    expect($this->eventType->data['parent_id'])->toBeNull();
});

test('EventType validation fails if parent_id is invalid', function () {
    $this->eventType->data = ['name' => 'Test Event', 'parent_id' => 'invalid'];
    $errors = $this->eventType->validate();

    expect($errors)->toHaveKey('parent_id');
});

test('EventType validation passes with valid data', function () {
    $this->eventType->data = [
        'name' => 'Valid Event',
        'level' => 2,
        'parent_id' => 10
    ];
    $errors = $this->eventType->validate();
    expect($errors)->toBeEmpty();
});
