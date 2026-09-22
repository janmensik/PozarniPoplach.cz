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

test('extractUnitRegistration returns null for empty or invalid input', function () {
    expect($this->dispatch->extractUnitRegistration(null))->toBeNull();
    expect($this->dispatch->extractUnitRegistration(''))->toBeNull();
    expect($this->dispatch->extractUnitRegistration([]))->toBeNull();
    expect($this->dispatch->extractUnitRegistration(['']))->toBeNull();
});

test('extractUnitRegistration extracts registration from a valid string', function () {
    expect($this->dispatch->extractUnitRegistration('notifikace.123456@pozarnipoplach.cz'))->toBe('123456');
    expect($this->dispatch->extractUnitRegistration('NOTIFIKACE.ABCDEF@POZARNIPOPLACH.CZ'))->toBe('ABCDEF');
});

test('extractUnitRegistration returns null for invalid string format', function () {
    expect($this->dispatch->extractUnitRegistration('invalid@pozarnipoplach.cz'))->toBeNull();
    expect($this->dispatch->extractUnitRegistration('notifikace.12345@pozarnipoplach.cz'))->toBeNull(); // Too short
    expect($this->dispatch->extractUnitRegistration('notifikace.1234567@pozarnipoplach.cz'))->toBeNull(); // Too long
    expect($this->dispatch->extractUnitRegistration('notifikace.123456@otherdomain.cz'))->toBeNull(); // Wrong domain
});

test('extractUnitRegistration extracts registration from an array', function () {
    $emails = [
        'test@test.com',
        'notifikace.654321@pozarnipoplach.cz',
        'another@domain.com'
    ];
    expect($this->dispatch->extractUnitRegistration($emails))->toBe('654321');
});

test('extractUnitRegistration returns null for array with no matching email', function () {
    $emails = [
        'test@test.com',
        'invalid@pozarnipoplach.cz',
        'another@domain.com'
    ];
    expect($this->dispatch->extractUnitRegistration($emails))->toBeNull();
});

test('extractUnitRegistration extracts the first matching registration from an array', function () {
    $emails = [
        'notifikace.111111@pozarnipoplach.cz',
        'notifikace.222222@pozarnipoplach.cz'
    ];
    expect($this->dispatch->extractUnitRegistration($emails))->toBe('111111');
});

// ----------------------------------------------------------------
// getLastDispatch
// ----------------------------------------------------------------

test('getLastDispatch returns null when no dispatch found (lightweight)', function () {
    $this->db->expects($this->once())->method('query')->willReturn(true);
    $this->db->expects($this->once())->method('getRow')->willReturn(null);

    $result = $this->dispatch->getLastDispatch(null, false);
    expect($result)->toBeNull();
});

test('getLastDispatch lightweight returns row when found', function () {
    $row = ['id' => 5, 'dispatched_at_ts' => 1700000000, 'unit_fullname' => 'Brno'];

    $this->db->expects($this->once())->method('query')
        ->with($this->stringContains('ORDER BY dis.dispatched_at DESC LIMIT 1'))
        ->willReturn(true);
    $this->db->expects($this->once())->method('getRow')->willReturn($row);

    $result = $this->dispatch->getLastDispatch(null, false);
    expect($result)->toBe($row);
});

test('getLastDispatch lightweight includes unit_id filter when provided', function () {
    $this->db->expects($this->once())->method('query')
        ->with($this->callback(function ($sql) {
            return str_contains($sql, 'dis.unit_id = "42"');
        }))
        ->willReturn(true);
    $this->db->expects($this->once())->method('getRow')->willReturn(null);

    $this->dispatch->getLastDispatch(42, false);
    expect(true)->toBeTrue();
});

test('getLastDispatch full_data returns null when no rows in DB', function () {
    // Full data path uses get() which calls query + getResult + getRow in a while loop
    $this->db->method('query')->willReturn(true);
    $this->db->method('getResult')->willReturn(0);
    $this->db->method('getRow')->willReturn(false);

    $result = $this->dispatch->getLastDispatch(null, true);
    expect($result)->toBeNull();
});

