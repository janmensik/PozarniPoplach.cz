<?php

namespace PozarniPoplach {
    if (!function_exists('PozarniPoplach\mysqli_real_escape_string')) {
        function mysqli_real_escape_string($mysqli, $string) {
            return addslashes($string ?? '');
        }
    }
}

namespace Tests\Unit {

require_once __DIR__ . '/../../include/class.DeviceAuth.php';

use PozarniPoplach\DeviceAuth;
use Janmensik\Jmlib\Database;

uses(\Tests\TestCase::class);

beforeEach(function () {
    $this->db = $this->createMock(Database::class);
    // Dummy mysqli for type hinting
    $this->db->db = $this->getMockBuilder('mysqli')
                         ->disableOriginalConstructor()
                         ->getMock();

    $this->deviceAuth = new DeviceAuth($this->db);
});

test('generateUserFriendlyCode returns string of requested length', function () {
    $ref = new \ReflectionMethod(DeviceAuth::class, 'generateUserFriendlyCode');

    $code = $ref->invoke($this->deviceAuth, 10);
    expect(strlen($code))->toBe(10);
});

test('generateUserFriendlyCode excludes ambiguous characters', function () {
    $ref = new \ReflectionMethod(DeviceAuth::class, 'generateUserFriendlyCode');

    $excluded = ['0', 'O', '1', 'l', 'I'];
    for ($i = 0; $i < 100; $i++) {
        $code = $ref->invoke($this->deviceAuth, 8);
        foreach ($excluded as $char) {
            expect($code)->not->toContain($char);
        }
    }
});

test('getRequestCredentials extracts info from SERVER and REQUEST', function () {
    $_SERVER['HTTP_X_DEVICE_UUID'] = 'uuid-123';
    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer token-456';

    $creds = $this->deviceAuth->getRequestCredentials();

    expect($creds['uuid'])->toBe('uuid-123');
    expect($creds['token'])->toBe('token-456');

    unset($_SERVER['HTTP_X_DEVICE_UUID']);
    unset($_SERVER['HTTP_AUTHORIZATION']);
});

test('getRequestCredentials handles X-Device-Token header', function () {
    $_SERVER['HTTP_X_DEVICE_TOKEN'] = 'token-789';
    $_REQUEST['uuid'] = 'uuid-abc';

    $creds = $this->deviceAuth->getRequestCredentials();

    expect($creds['uuid'])->toBe('uuid-abc');
    expect($creds['token'])->toBe('token-789');

    unset($_SERVER['HTTP_X_DEVICE_TOKEN']);
    unset($_REQUEST['uuid']);
});

test('getVerificationUrl uses ABSOLUTE_URL env', function () {
    $_ENV['ABSOLUTE_URL'] = 'https://pp.cz';

    $ref = new \ReflectionMethod(DeviceAuth::class, 'getVerificationUrl');

    $url = $ref->invoke($this->deviceAuth, 'ABC123');
    expect($url)->toBe('https://pp.cz/activate?code=ABC123');

    unset($_ENV['ABSOLUTE_URL']);
});

test('authorizeDevice returns null when device code is empty', function () {
    $result = $this->deviceAuth->authorizeDevice('');
    expect($result)->toBeNull();
});

test('authorizeDevice returns null when session is not found', function () {
    $this->db->expects($this->once())
        ->method('query')
        ->willReturn(false);

    $this->db->expects($this->once())
        ->method('getRow')
        ->willReturn(null);

    $result = $this->deviceAuth->authorizeDevice('INVALID');
    expect($result)->toBeNull();
});

test('authorizeDevice returns null when session status is not linked', function () {
    $this->db->expects($this->once())
        ->method('query')
        ->willReturn(true);

    $this->db->expects($this->once())
        ->method('getRow')
        ->willReturn([
            'status' => 'pending',
            'unit_id' => 123,
            'device_uuid' => 'uuid-123',
            'device_name' => 'Device'
        ]);

    $result = $this->deviceAuth->authorizeDevice('VALIDCODE');
    expect($result)->toBeNull();
});

test('authorizeDevice returns null when unit_id is empty', function () {
    $this->db->expects($this->once())
        ->method('query')
        ->willReturn(true);

    $this->db->expects($this->once())
        ->method('getRow')
        ->willReturn([
            'status' => 'linked',
            'unit_id' => null,
            'device_uuid' => 'uuid-123',
            'device_name' => 'Device'
        ]);

    $result = $this->deviceAuth->authorizeDevice('VALIDCODE');
    expect($result)->toBeNull();
});

test('authorizeDevice returns null if insert fails', function () {
    $this->db->expects($this->exactly(2))
        ->method('query')
        ->willReturnCallback(function($query) {
            if (strpos($query, 'SELECT status, unit_id') !== false) {
                return true;
            }
            if (strpos($query, 'INSERT INTO alarm_device_authorized') !== false) {
                return false; // Simulate insert failure
            }
            return true;
        });

    $this->db->expects($this->once())
        ->method('getRow')
        ->willReturn([
            'status' => 'linked',
            'unit_id' => 123,
            'device_uuid' => 'uuid-123',
            'device_name' => 'Device'
        ]);

    $result = $this->deviceAuth->authorizeDevice('VALIDCODE');
    expect($result)->toBeNull();
});

test('authorizeDevice successfully authorizes and returns tokens', function () {
    $this->db->expects($this->exactly(3))
        ->method('query')
        ->willReturnCallback(function($query) {
            if (strpos($query, 'SELECT status, unit_id') !== false) {
                return true;
            }
            if (strpos($query, 'INSERT INTO alarm_device_authorized') !== false) {
                return true; // Simulate insert success
            }
            if (strpos($query, 'DELETE FROM alarm_device_session') !== false) {
                return true; // Simulate delete success
            }
            return false;
        });

    $this->db->expects($this->once())
        ->method('getRow')
        ->willReturn([
            'status' => 'linked',
            'unit_id' => 123,
            'device_uuid' => 'uuid-123',
            'device_name' => 'Test Device'
        ]);

    $result = $this->deviceAuth->authorizeDevice('VALIDCODE');

    expect($result)->toBeArray();
    expect($result)->toHaveKeys(['refresh_token', 'unit_id']);
    expect($result['unit_id'])->toBe(123);
    expect(strlen($result['refresh_token']))->toBe(64); // hex encoded 32 bytes
});

}
