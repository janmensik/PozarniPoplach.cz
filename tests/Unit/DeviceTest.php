<?php

require_once __DIR__ . '/../../include/class.Device.php';

use PozarniPoplach\Device;
use Janmensik\Jmlib\Database;

// Subclass to bypass mysqli dependencies in unit tests
class DeviceTestable extends Device {
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
    // We still need a dummy mysqli for type hinting if any other method calls it
    $this->db->db = $this->getMockBuilder('mysqli')
                         ->disableOriginalConstructor()
                         ->getMock();
                         
    $this->device = new Device($this->db);
    $this->testableDevice = new DeviceTestable($this->db);
});

test('Device validation fails if device_name is empty', function () {
    $this->device->data = ['device_name' => ''];
    $errors = $this->device->validate();
    
    expect($errors)->toHaveKey('device_name');
    expect($errors['device_name'])->toBe('Device name is required');
});

test('Device validation fails if ad_probability is out of range', function () {
    $this->device->data = ['device_name' => 'Test', 'ad_probability' => 150];
    $errors = $this->device->validate();
    expect($errors)->toHaveKey('ad_probability');
    expect($errors['ad_probability'])->toBe('Ad probability must be a number 0-100');
    
    $this->device->data = ['device_name' => 'Test', 'ad_probability' => -5];
    $errors = $this->device->validate();
    expect($errors)->toHaveKey('ad_probability');
});

test('Device validation fails if ad_sticky_duration is out of range', function () {
    $this->device->data = ['device_name' => 'Test', 'ad_sticky_duration' => 10000];
    $errors = $this->device->validate();
    expect($errors)->toHaveKey('ad_sticky_duration');
    expect($errors['ad_sticky_duration'])->toBe('Ad sticky duration must be a number 0-9999');
});

test('Device validation passes if all fields are valid', function () {
    $this->device->data = [
        'device_name' => 'Valid Device',
        'ad_probability' => 50,
        'ad_sticky_duration' => 60
    ];
    $errors = $this->device->validate();
    expect($errors)->toBeEmpty();
});

test('Device maps data from post correctly', function () {
    $postData = [
        'device_name' => 'My Tablet',
        'ad_probability' => 25,
        'ad_sticky_duration' => 120
    ];
    
    $this->testableDevice->mapFromPost($postData);
    
    expect($this->testableDevice->data['device_name'])->toBe('My Tablet');
    expect($this->testableDevice->data['ad_probability'])->toBe(25);
    expect($this->testableDevice->data['ad_sticky_duration'])->toBe(120);
});

test('Device delete calls DB query', function () {
    $this->db->expects($this->once())
             ->method('query')
             ->with($this->stringContains('DELETE FROM alarm_device_authorized WHERE id = "123"'))
             ->willReturn(true);
             
    $result = $this->device->delete(123);
    expect($result)->toBeTrue();
});

test('Device setter calls set method', function () {
    $this->testableDevice->data = [
        'device_name' => 'Setter Test',
        'ad_probability' => 10
    ];
    
    $this->db->expects($this->once())->method('query');
    $this->db->method('getNumAffected')->willReturn(1);
    $this->db->method('getId')->willReturn(789);
    
    $result = $this->testableDevice->setter();
    expect($result)->toBe(789);
});
