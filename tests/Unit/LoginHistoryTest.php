<?php

require_once __DIR__ . '/../../include/class.LoginHistory.php';

use PozarniPoplach\LoginHistory;
use Janmensik\Jmlib\Database;

uses(\Tests\TestCase::class);

beforeEach(function () {
    $this->db = $this->createMock(Database::class);
    $this->loginHistory = new LoginHistory($this->db);
});

test('LoginHistory instantiates correctly', function () {
    expect($this->loginHistory)->toBeInstanceOf(LoginHistory::class);
});

test('LoginHistory has expected SQL base', function () {
    $ref = new ReflectionProperty(LoginHistory::class, 'sql_base');
    $sql = $ref->getValue($this->loginHistory);

    expect($sql)->toContain('FROM user_login upr JOIN user u ON u.id = upr.user_id');
});
