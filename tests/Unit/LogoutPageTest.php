<?php

use Janmensik\Jmlib\AppData;
use PozarniPoplach\User;

uses(\Tests\TestCase::class);

beforeEach(function () {
    $this->appd = AppData::getInstance();
    $this->appd->setData('BASE_URL', 'http://localhost');

    $this->user = $this->createMock(User::class);

    $GLOBALS['User'] = $this->user;
    $GLOBALS['APPD'] = $this->appd;

    $_SESSION = ['user_id' => 99, 'user' => ['id' => 99]];
    $_COOKIE = [];
});

afterEach(function () {
    $_SESSION = [];
    $_COOKIE = [];
});

test('logout.php calls User::logout()', function () {
    $this->user->expects($this->once())->method('logout');

    $User = $this->user;
    $APPD = $this->appd;

    ob_start();
    include __DIR__ . '/../../view/page/logout.php';
    ob_end_clean();

    expect(true)->toBeTrue(); // reached here without error
});

test('logout.php clears session user_id', function () {
    $this->user->method('logout')->willReturn(true);

    $User = $this->user;
    $APPD = $this->appd;

    ob_start();
    include __DIR__ . '/../../view/page/logout.php';
    ob_end_clean();

    expect(isset($_SESSION['user_id']))->toBeFalse();
});

test('logout.php sets logout message', function () {
    $this->user->method('logout')->willReturn(true);

    $User = $this->user;
    $APPD = $this->appd;

    ob_start();
    include __DIR__ . '/../../view/page/logout.php';
    ob_end_clean();

    expect($APPD->MESSAGES['logout']['result'] ?? null)->toBe('logout');
});
