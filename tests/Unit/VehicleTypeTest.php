<?php

require_once __DIR__ . '/../../include/class.VehicleType.php';

use PozarniPoplach\VehicleType;
use Janmensik\Jmlib\Database;

uses(\Tests\TestCase::class);

beforeEach(function () {
    $this->db = $this->createMock(Database::class);
    // We still need a dummy mysqli for type hinting
    $this->db->db = $this->getMockBuilder('mysqli')
                         ->disableOriginalConstructor()
                         ->getMock();

    $this->vehicleType = new VehicleType($this->db);
});

test('VehicleType validation fails if type is empty', function () {
    $this->vehicleType->data = ['type' => '', 'code' => 'CAS'];
    $errors = $this->vehicleType->validate();

    expect($errors)->toHaveKey('type');
    expect($errors['type'])->toBe('Type is required');
});

test('VehicleType validation fails if code is empty', function () {
    $this->vehicleType->data = ['type' => 'Cisternová stříkačka', 'code' => ''];
    $errors = $this->vehicleType->validate();

    expect($errors)->toHaveKey('code');
    expect($errors['code'])->toBe('Code is required');
});

test('VehicleType validation passes with valid data', function () {
    $this->vehicleType->data = [
        'type' => 'Cisternová stříkačka',
        'code' => 'CAS 20',
        'icon' => 'truck-icon'
    ];
    $errors = $this->vehicleType->validate();
    expect($errors)->toBeEmpty();
});
