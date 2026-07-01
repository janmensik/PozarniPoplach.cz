<?php

use Janmensik\Jmlib\AppData;
use PozarniPoplach\User;

uses(\Tests\TestCase::class);

beforeEach(function () {
    $this->appd = AppData::getInstance();
    $this->appd->setData('BASE_URL', 'http://localhost');

    $this->user = $this->createMock(User::class);
    $this->smarty = $this->createMock(\Smarty\Smarty::class);

    $GLOBALS['User'] = $this->user;
    $GLOBALS['Smarty'] = $this->smarty;
    $GLOBALS['APPD'] = $this->appd;

    // Reset POST and SESSION
    $_POST = [];
    $_SESSION = [];
});

afterEach(function () {
    $_POST = [];
    $_SESSION = [];
});

test('login.php sets PAGE to login', function () {
    $_POST = [];

    $User = $this->user;
    $APPD = $this->appd;
    $Smarty = $this->smarty;

    ob_start();
    include __DIR__ . '/../../view/page/login.php';
    ob_end_clean();

    expect($APPD->getData('PAGE'))->toBe('login');
});

test('login.php redirects and sets empty error when credentials are missing', function () {
    $_POST = ['email' => '', 'password' => '', 'permanent' => false];

    $User = $this->user;
    $APPD = $this->appd;
    $Smarty = $this->smarty;

    ob_start();
    include __DIR__ . '/../../view/page/login.php';
    ob_end_clean();

    expect($APPD->MESSAGES['error']['login'] ?? null)->toBe('empty');
});

test('login.php sets wrong error when credentials are invalid', function () {
    $_POST = ['email' => 'test@example.com', 'password' => 'wrongpassword', 'permanent' => false];

    $this->user->method('verify')->willReturn(null);

    $User = $this->user;
    $APPD = $this->appd;
    $Smarty = $this->smarty;

    ob_start();
    include __DIR__ . '/../../view/page/login.php';
    ob_end_clean();

    expect($APPD->MESSAGES['error']['login'] ?? null)->toBe('wrong');
});

test('login.php stores user_id in session on successful login', function () {
    // permanent is not set — no cookie path
    $_POST = ['email' => 'test@example.com', 'password' => 'correctpassword'];

    // verify() declared return type is array|bool|null — use true (truthy)
    // login.php does: $user_id = $User->verify(...); if (!$user_id) {...} else { ... $_SESSION['user_id'] = $user_id; }
    $this->user->method('verify')->willReturn(true);
    $this->user->method('load')->willReturn(['id' => 1, 'email' => 'test@example.com']);

    $User = $this->user;
    $APPD = $this->appd;
    $Smarty = $this->smarty;

    ob_start();
    include __DIR__ . '/../../view/page/login.php';
    ob_end_clean();

    // session should have been set (true is truthy)
    expect(isset($_SESSION['user_id']))->toBeTrue();
});
