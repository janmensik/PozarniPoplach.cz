<?php

require_once __DIR__ . '/../../include/class.Vehicle.php';

use PozarniPoplach\Vehicle;
use Janmensik\Jmlib\Database;

// Subclass to bypass mysqli dependencies in unit tests
class VehicleTestable extends Vehicle {
    public function sanitize($value = null, $type = 'text', $required = false, $extra_data = null) {
        return $value;
    }

    /**
     * Overridden to avoid mysqli_real_escape_string during unit tests
     */
    public function setter(?int $int = null): bool|int {
        $set = [];
        foreach ($this->elements as $el) {
            if (isset($this->data[$el])) {
                $value = $this->data[$el];
                $set[$el] = $value === null ? 'NULL' : '"' . addslashes((string)$value) . '"';
            }
        }
        return ($this->set($set, $int));
    }
}

uses(\Tests\TestCase::class);

beforeEach(function () {
    $this->db = $this->createMock(Database::class);
    // Dummy mysqli for type hinting
    $this->db->db = $this->getMockBuilder('mysqli')
                         ->disableOriginalConstructor()
                         ->getMock();
                         
    $this->vehicle = new Vehicle($this->db);
    $this->testableVehicle = new VehicleTestable($this->db);
});

test('Vehicle validation fails if required fields are empty', function () {
    $this->vehicle->data = [];
    $errors = $this->vehicle->validate();
    
    expect($errors)->toHaveKey('callsign');
    expect($errors)->toHaveKey('name');
    expect($errors)->toHaveKey('vehicle_type_id');
    expect($errors)->toHaveKey('unit_id');
});

test('Vehicle validation passes with valid data', function () {
    $this->vehicle->data = [
        'callsign' => 'VOL 123',
        'name' => 'Liaz',
        'vehicle_type_id' => 1,
        'unit_id' => 10
    ];
    $errors = $this->vehicle->validate();
    expect($errors)->toBeEmpty();
});

test('Vehicle maps data from post correctly', function () {
    $postData = [
        'callsign' => 'CAS 20',
        'name' => 'Tatra',
        'vehicle_type_id' => 5,
        'unit_id' => 2
    ];
    
    $this->testableVehicle->mapFromPost($postData);
    
    expect($this->testableVehicle->data['callsign'])->toBe('CAS 20');
    expect($this->testableVehicle->data['name'])->toBe('Tatra');
    expect($this->testableVehicle->data['vehicle_type_id'])->toBe(5);
    expect($this->testableVehicle->data['unit_id'])->toBe(2);
});

test('Vehicle delete calls DB query', function () {
    $this->db->expects($this->once())
             ->method('query')
             ->with($this->stringContains('DELETE FROM unit_vehicles WHERE id = "123"'))
             ->willReturn(true);
             
    $result = $this->vehicle->delete(123);
    expect($result)->toBeTrue();
});
