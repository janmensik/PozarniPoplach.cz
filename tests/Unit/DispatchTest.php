<?php

require_once __DIR__ . '/../../include/class.Dispatch.php';

use PozarniPoplach\Dispatch;
use Janmensik\Jmlib\Database;

uses(\Tests\TestCase::class);

beforeEach(function () {
    $this->db = $this->createMock(Database::class);
    // We still need a dummy mysqli for type hinting
    $this->db->db = $this->getMockBuilder('mysqli')
                         ->disableOriginalConstructor()
                         ->getMock();

    $this->dispatch = new Dispatch($this->db);
    $_ENV['GOOGLE_MAPS_API_KEY'] = 'test-key';
    $_ENV['MAPBOX_API_KEY'] = 'test-key';
});

afterEach(function () {
    unset($_ENV['GOOGLE_MAPS_API_KEY']);
    unset($_ENV['MAPBOX_API_KEY']);
});

test('beautifulLastDispatch returns null for empty input', function () {
    expect($this->dispatch->beautifulLastDispatch(null))->toBeNull();
    expect($this->dispatch->beautifulLastDispatch([]))->toBeNull();
});

test('beautifulLastDispatch formats unit and event names correctly', function () {
    $data = [
        'id' => 1,
        'unit_fullname' => 'Test Unit Name',
        'event_name' => 'Fire',
        'event_subtype_name' => 'House Fire',
        'event_icon' => 'fire-icon',
        'has_streetview' => 1,
        'directions_distance' => 10.5,
        'directions_duration' => 600,
        'directions_polyline' => 'abc',
        'gps_latitude' => 50.1,
        'gps_longitude' => 14.2
    ];

    $result = $this->dispatch->beautifulLastDispatch($data);

    expect($result['unit'])->toBe('Test Unit Name');
    expect($result['event'])->toBe('Fire');
    expect($result['event_subtype'])->toBe('House Fire');
    expect($result['event_icon'])->toBe('fire-icon');
});

test('beautifulLastDispatch handles city_part if same as city', function () {
    $data = [
        'id' => 2,
        'address_city' => 'Prague',
        'address_city_part' => 'Prague',
        'has_streetview' => 0,
        'gps_latitude' => 50.1,
        'gps_longitude' => 14.2
    ];

    $result = $this->dispatch->beautifulLastDispatch($data);

    expect($result['address_city_part'])->toBeNull();
});

test('beautifulLastDispatch handles city_part if different from city', function () {
    $data = [
        'id' => 3,
        'address_city' => 'Prague',
        'address_city_part' => 'Zizkov',
        'has_streetview' => 0,
        'gps_latitude' => 50.1,
        'gps_longitude' => 14.2
    ];

    $result = $this->dispatch->beautifulLastDispatch($data);

    expect($result['address_city_part'])->toBe('Zizkov');
});
