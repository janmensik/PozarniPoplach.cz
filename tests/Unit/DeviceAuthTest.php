<?php

namespace PozarniPoplach {
    if (!function_exists('PozarniPoplach\mysqli_real_escape_string')) {
        function mysqli_real_escape_string($mysqli, $string)
        {
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
        ->willReturnCallback(function ($query) {
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
        ->willReturnCallback(function ($query) {
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

    // ----------------------------------------------------------------
    // initSession
    // ----------------------------------------------------------------

    test('initSession returns null when DB insert fails', function () {
        $this->db->expects($this->exactly(2))
            ->method('query')
            ->willReturnCallback(function ($query) {
                if (strpos($query, 'DELETE FROM alarm_device_session') !== false) {
                    return true; // cleanup succeeds
                }
                return false; // insert fails
            });

        $result = $this->deviceAuth->initSession('uuid-test');
        expect($result)->toBeNull();
    });

    test('initSession returns array with device_code and verification_url on success', function () {
        $_ENV['ABSOLUTE_URL'] = 'https://pp.cz';

        $this->db->expects($this->exactly(2))
            ->method('query')
            ->willReturnCallback(function ($query) {
                if (strpos($query, 'DELETE FROM alarm_device_session') !== false) {
                    return true;
                }
                return true; // insert succeeds
            });

        $result = $this->deviceAuth->initSession('uuid-device-1');

        expect($result)->toBeArray();
        expect($result)->toHaveKeys(['device_code', 'expires_at', 'verification_url']);
        expect(strlen($result['device_code']))->toBe(8);
        expect($result['verification_url'])->toContain('https://pp.cz/activate?code=');

        unset($_ENV['ABSOLUTE_URL']);
    });

    test('initSession device_code contains only allowed characters', function () {
        $this->db->method('query')->willReturn(true);

        $result = $this->deviceAuth->initSession('uuid-char-test');
        $excluded = ['0', 'O', '1', 'l', 'I'];
        foreach ($excluded as $char) {
            expect($result['device_code'])->not->toContain($char);
        }
    });

    // ----------------------------------------------------------------
    // checkSessionStatus
    // ----------------------------------------------------------------

    test('checkSessionStatus returns null when session not found or expired', function () {
        $this->db->expects($this->once())->method('query')->willReturn(true);
        $this->db->expects($this->once())->method('getRow')->willReturn(null);

        $result = $this->deviceAuth->checkSessionStatus('NOTFOUND');
        expect($result)->toBeNull();
    });

    test('checkSessionStatus returns session data when found', function () {
        $sessionData = ['status' => 'pending', 'unit_id' => null, 'device_uuid' => 'uuid-abc'];

        $this->db->expects($this->once())->method('query')->willReturn(true);
        $this->db->expects($this->once())->method('getRow')->willReturn($sessionData);

        $result = $this->deviceAuth->checkSessionStatus('PENDINGCODE');
        expect($result)->toBe($sessionData);
    });

    test('checkSessionStatus returns linked session with unit_id', function () {
        $sessionData = ['status' => 'linked', 'unit_id' => 42, 'device_uuid' => 'uuid-xyz'];

        $this->db->expects($this->once())->method('query')->willReturn(true);
        $this->db->expects($this->once())->method('getRow')->willReturn($sessionData);

        $result = $this->deviceAuth->checkSessionStatus('LINKEDCODE');
        expect($result['status'])->toBe('linked');
        expect($result['unit_id'])->toBe(42);
    });

    // ----------------------------------------------------------------
    // linkSessionToUnit
    // ----------------------------------------------------------------

    test('linkSessionToUnit returns true on successful update', function () {
        $this->db->expects($this->once())
            ->method('query')
            ->with($this->stringContains('UPDATE alarm_device_session'))
            ->willReturn(true);

        $result = $this->deviceAuth->linkSessionToUnit('MYCODE', 5, 'Kiosk Brno');
        expect($result)->toBeTrue();
    });

    test('linkSessionToUnit returns false when update affects no rows', function () {
        $this->db->expects($this->once())
            ->method('query')
            ->willReturn(false);

        $result = $this->deviceAuth->linkSessionToUnit('BADCODE', 5, 'Kiosk Brno');
        expect($result)->toBeFalse();
    });

    test('linkSessionToUnit includes unit_id and device_name in query', function () {
        $this->db->expects($this->once())
            ->method('query')
            ->with($this->callback(function ($sql) {
                return str_contains($sql, 'unit_id = 7')
                    && str_contains($sql, 'Kiosk Praha');
            }))
            ->willReturn(true);

        $this->deviceAuth->linkSessionToUnit('CODE7', 7, 'Kiosk Praha');
        expect(true)->toBeTrue();
    });

    test('linkSessionToUnit works with null device name', function () {
        $this->db->expects($this->once())
            ->method('query')
            ->with($this->stringContains('UPDATE alarm_device_session'))
            ->willReturn(true);

        $result = $this->deviceAuth->linkSessionToUnit('CODE8', 3, null);
        expect($result)->toBeTrue();
    });

    // ----------------------------------------------------------------
    // validateDevice
    // ----------------------------------------------------------------

    test('validateDevice returns null when device not found', function () {
        $this->db->expects($this->once())->method('query')->willReturn(true);
        $this->db->expects($this->once())->method('getRow')->willReturn(null);

        $result = $this->deviceAuth->validateDevice('uuid-bad', 'sometoken');
        expect($result)->toBeNull();
    });

    test('validateDevice returns null when token hash does not match', function () {
        $fakeToken  = 'wrongtoken';
        $realToken  = 'correcttoken';
        $correctHash = hash('sha256', $realToken);

        $this->db->expects($this->once())->method('query')->willReturn(true);
        $this->db->expects($this->once())->method('getRow')->willReturn([
            'unit_id'            => 10,
            'refresh_token_hash' => $correctHash,
            'last_seen_ts'       => time() - 1000,
        ]);

        $result = $this->deviceAuth->validateDevice('uuid-ok', $fakeToken);
        expect($result)->toBeNull();
    });

    test('validateDevice returns unit_id when token is valid', function () {
        $token = 'validtoken123';
        $hash  = hash('sha256', $token);

        // First query = SELECT; second = UPDATE last_seen (stale > 300s)
        $this->db->expects($this->exactly(2))->method('query')->willReturn(true);
        $this->db->expects($this->once())->method('getRow')->willReturn([
            'unit_id'            => 42,
            'refresh_token_hash' => $hash,
            'last_seen_ts'       => time() - 400, // stale → triggers UPDATE
        ]);

        $result = $this->deviceAuth->validateDevice('uuid-valid', $token);
        expect($result)->toBe(42);
    });

    test('validateDevice skips last_seen update when seen recently', function () {
        $token = 'freshtoken';
        $hash  = hash('sha256', $token);

        // Only 1 query (SELECT), no UPDATE because last_seen is fresh
        $this->db->expects($this->once())->method('query')->willReturn(true);
        $this->db->expects($this->once())->method('getRow')->willReturn([
            'unit_id'            => 7,
            'refresh_token_hash' => $hash,
            'last_seen_ts'       => time() - 10, // fresh → no UPDATE
        ]);

        $result = $this->deviceAuth->validateDevice('uuid-fresh', $token);
        expect($result)->toBe(7);
    });

}
