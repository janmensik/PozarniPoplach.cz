<?php

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
    $ref = new ReflectionMethod(DeviceAuth::class, 'generateUserFriendlyCode');

    $code = $ref->invoke($this->deviceAuth, 10);
    expect(strlen($code))->toBe(10);
});

test('generateUserFriendlyCode excludes ambiguous characters', function () {
    $ref = new ReflectionMethod(DeviceAuth::class, 'generateUserFriendlyCode');

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

    $ref = new ReflectionMethod(DeviceAuth::class, 'getVerificationUrl');

    $url = $ref->invoke($this->deviceAuth, 'ABC123');
    expect($url)->toBe('https://pp.cz/activate?code=ABC123');

    unset($_ENV['ABSOLUTE_URL']);
});
