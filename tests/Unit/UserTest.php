<?php

require_once __DIR__ . '/../../include/class.User.php';

use PozarniPoplach\User;
use Janmensik\Jmlib\Database;
use Casbin\Enforcer;

// Subclass to bypass mysqli dependencies in unit tests
class UserTestable extends User
{
    public function sanitize($value = null, $type = 'text', $required = false, $extra_data = null)
    {
        return $value;
    }

    /**
     * Overridden to avoid mysqli_real_escape_string during unit tests
     */
    public function setter(?int $int = null): bool|int
    {
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

    $this->enforcer = $this->getMockBuilder(Enforcer::class)
                             ->disableOriginalConstructor()
                             ->getMock();

    // Create a concrete instance for testing
    $this->user = new User($this->db, $this->enforcer);
    $this->testableUser = new UserTestable($this->db, $this->enforcer);
});

test('User validation fails if name is empty', function () {
    $this->user->data = ['name' => '', 'email' => 'test@example.com', 'status' => 'admin'];
    $errors = $this->user->validate();

    expect($errors)->toHaveKey('name');
    expect($errors['name'])->toBe('empty');
});

test('User validation fails if email is empty', function () {
    $this->user->data = ['name' => 'John Doe', 'email' => '', 'status' => 'admin'];
    $errors = $this->user->validate();

    expect($errors)->toHaveKey('email');
    expect($errors['email'])->toBe('empty');
});

test('User validation fails if status is invalid', function () {
    $this->user->data = ['name' => 'John Doe', 'email' => 'test@example.com', 'status' => 'invalid_status'];
    $errors = $this->user->validate();

    expect($errors)->toHaveKey('status');
    expect($errors['status'])->toBe('wrong');
});

test('User validation passes if all fields are valid', function () {
    $this->user->data = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'status' => 'admin'
    ];
    $errors = $this->user->validate();
    expect($errors)->toBeEmpty();
});

test('User password hash uses secure password_hash', function () {
    $password = 'secret123';
    $hash = $this->user->getPasswordHash($password);
    expect(password_verify($password, $hash))->toBeTrue();
});

test('User verifyPassword handles legacy sha1 and secure hashes', function () {
    $password = 'secret123';
    $secureHash = $this->user->getPasswordHash($password);
    $legacyHash = sha1($password);

    expect($this->user->verifyPassword($password, $secureHash))->toBeTrue();
    expect($this->user->verifyPassword($password, $legacyHash))->toBeTrue();
    expect($this->user->verifyPassword('wrong', $secureHash))->toBeFalse();
    expect($this->user->verifyPassword('wrong', $legacyHash))->toBeFalse();
});

test('User logout clears user data', function () {
    // We can't easily set the protected 'user' property without reflection
    // or calling a method that sets it. Let's use reflection but be more careful.
    $ref = new ReflectionProperty(User::class, 'user');
    $ref->setValue($this->user, ['id' => 123, 'page_schema' => null]);

    // Use assertEquals to be less strict about types if it's 123 vs "123"
    expect($this->user->getUser('id'))->toEqual(123);

    $this->user->logout();

    expect($this->user->getUser())->toBeEmpty();
});

test('User generatePassword returns string of requested length', function () {
    $password = $this->user->generatePassword(12);
    expect(strlen($password))->toBe(12);
});

test('User hasPermission returns false if no user is loaded', function () {
    expect($this->user->hasPermission('dashboard', 'view'))->toBeFalse();
});

// ----------------------------------------------------------------
// getPermanentHash
// ----------------------------------------------------------------

test('getPermanentHash returns false when user_id is null', function () {
    expect($this->user->getPermanentHash(null))->toBeFalse();
    expect($this->user->getPermanentHash(0))->toBeFalse();
});

test('getPermanentHash returns null when user not found in DB', function () {
    $this->db->method('query')->willReturn(true);
    $this->db->method('getResult')->willReturn(0);
    $this->db->method('getRow')->willReturn(false);

    $result = $this->user->getPermanentHash(999);
    expect($result)->toBeNull();
});

test('getPermanentHash returns sha1 hash of id+email+password', function () {
    $this->db->expects($this->once())->method('query')->willReturn(true);
    // getId() uses getRow() directly (no getResult), return row then false to end iteration
    $this->db->expects($this->exactly(2))->method('getRow')->willReturnOnConsecutiveCalls(
        [
            'id'          => 5,
            'email'       => 'test@test.cz',
            'password'    => 'hashedpw',
            'name'        => 'Test',
            'status'      => 'admin',
            'page_schema' => null,
        ],
        false // terminates the while loop
    );

    $result = $this->user->getPermanentHash(5);
    expect($result)->toBe(sha1('5test@test.czhashedpw'));
});

// ----------------------------------------------------------------
// verifyPermanent
// ----------------------------------------------------------------

test('verifyPermanent returns null when user not found', function () {
    $this->db->method('query')->willReturn(true);
    $this->db->method('getResult')->willReturn(0);
    $this->db->method('getRow')->willReturn(false);

    $result = $this->user->verifyPermanent('badhash');
    expect($result)->toBeNull();
});

// ----------------------------------------------------------------
// updateLastLogin
// ----------------------------------------------------------------

test('updateLastLogin executes INSERT INTO user_login', function () {
    // Set a minimal user state so user['id'] exists
    $ref = new ReflectionProperty(User::class, 'user');
    $ref->setValue($this->user, ['id' => 10, 'page_schema' => null]);

    $this->db->expects($this->once())
        ->method('query')
        ->with($this->callback(function ($sql) {
            return str_contains($sql, 'INSERT INTO user_login')
                && str_contains($sql, 'INET_ATON');
        }));

    $this->user->updateLastLogin(10, '127.0.0.1');
    expect(true)->toBeTrue();
});

test('updateLastLogin falls back to 127.0.0.1 for invalid IP', function () {
    $ref = new ReflectionProperty(User::class, 'user');
    $ref->setValue($this->user, ['id' => 1, 'page_schema' => null]);

    $this->db->expects($this->once())
        ->method('query')
        ->with($this->stringContains('INET_ATON("127.0.0.1")'));

    $this->user->updateLastLogin(1, 'not-an-ip');
    expect(true)->toBeTrue();
});

// ----------------------------------------------------------------
// getComplete
// ----------------------------------------------------------------

test('getComplete returns null when no users match', function () {
    $this->db->method('query')->willReturn(true);
    $this->db->method('getResult')->willReturn(0);
    $this->db->method('getRow')->willReturn(false);

    $result = $this->user->getComplete(['u.id = "0"']);
    expect($result)->toBeFalsy();
});

// ----------------------------------------------------------------
// getPageSchema / clearPageSchema
// ----------------------------------------------------------------

test('getPageSchema returns false when page is null', function () {
    expect($this->user->getPageSchema(null))->toBeFalse();
    expect($this->user->getPageSchema(''))->toBeFalse();
});

test('getPageSchema returns merged global and page schema', function () {
    $ref = new ReflectionProperty(User::class, 'user');
    $ref->setValue($this->user, [
        'id'          => 1,
        'page_schema' => [
            'global' => ['order' => 2],
            'pages'  => ['dispatches' => ['q' => 'fire']],
        ],
    ]);

    $result = $this->user->getPageSchema('dispatches');
    expect($result)->toHaveKey('order');
    expect($result)->toHaveKey('q');
    expect($result['q'])->toBe('fire');
    expect($result['order'])->toBe(2);
});

test('getPageSchema returns only global schema when page has no entries', function () {
    $ref = new ReflectionProperty(User::class, 'user');
    $ref->setValue($this->user, [
        'id'          => 1,
        'page_schema' => [
            'global' => ['order' => 5],
            'pages'  => ['units' => null], // page exists but is not an array → falls to global
        ],
    ]);

    $result = $this->user->getPageSchema('units');
    expect($result)->toBe(['order' => 5]);
});

test('clearPageSchema returns false when user_id provided but no user loaded', function () {
    // With an empty array user, user['id'] is not set, so (int)user_id && !user['id'] → false
    $ref = new ReflectionProperty(User::class, 'user');
    $ref->setValue($this->user, []); // no id

    $result = $this->user->clearPageSchema(99);
    expect($result)->toBeFalse();
});

