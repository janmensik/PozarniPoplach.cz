<?php

require_once __DIR__ . '/../../include/class.User.php';

use PozarniPoplach\User;
use Janmensik\Jmlib\Database;
use Casbin\Enforcer;

// Subclass to bypass mysqli dependencies in unit tests
class UserTestable extends User {
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

test('User password hash returns sha1', function () {
    $password = 'secret123';
    expect($this->user->getPasswordHash($password))->toBe(sha1($password));
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
