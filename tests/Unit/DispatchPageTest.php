<?php

use Janmensik\Jmlib\AppData;
use Janmensik\Jmlib\Database;
use PozarniPoplach\Dispatch;
use PozarniPoplach\User;

require_once __DIR__ . '/../../include/class.Dispatch.php';

uses(\Tests\TestCase::class);

beforeEach(function () {
    $this->db = $this->createMock(Database::class);
    $this->db->db = $this->getMockBuilder('mysqli')
                         ->disableOriginalConstructor()
                         ->getMock();

    $this->appd = AppData::getInstance();
    $this->appd->setData('BASE_URL', 'http://localhost');

    $this->user    = $this->createMock(User::class);
    $this->smarty  = $this->createMock(\Smarty\Smarty::class);
    $this->dispatch = $this->createMock(Dispatch::class);

    $GLOBALS['DB']      = $this->db;
    $GLOBALS['User']    = $this->user;
    $GLOBALS['Smarty']  = $this->smarty;
    $GLOBALS['APPD']    = $this->appd;

    $_GET = [];
});

afterEach(function () {
    $_GET = [];
});

test('dispatch.php sets PAGE to dispatch', function () {
    $this->user->method('hasPermission')->willReturn(true);

    $this->dispatch->method('getId')->willReturn(['id' => 1, 'event' => 'Fire']);
    $this->dispatch->method('getDispatch')->willReturn(['id' => 1]);
    $this->dispatch->method('beautifulLastDispatch')->willReturn([]);

    $id       = 1;
    $DB       = $this->db;
    $User     = $this->user;
    $Smarty   = $this->smarty;
    $APPD     = $this->appd;
    $Dispatch = $this->dispatch;

    ob_start();
    include __DIR__ . '/../../view/page/dispatch.php';
    ob_end_clean();

    expect($APPD->getData('PAGE'))->toBe('dispatch');
});

test('dispatch.php sets ERROR 403 when permission denied', function () {
    $this->user->method('hasPermission')->willReturn(false);

    $id       = 1;
    $DB       = $this->db;
    $User     = $this->user;
    $Smarty   = $this->smarty;
    $APPD     = $this->appd;
    $Dispatch = $this->dispatch;

    ob_start();
    include __DIR__ . '/../../view/page/dispatch.php';
    ob_end_clean();

    expect($APPD->getData('ERROR'))->toBe('403');
});

test('dispatch.php sets ERROR 404 when dispatch not found', function () {
    $this->user->method('hasPermission')->willReturn(true);
    $this->dispatch->method('getId')->willReturn(null);

    $id       = 999;
    $DB       = $this->db;
    $User     = $this->user;
    $Smarty   = $this->smarty;
    $APPD     = $this->appd;
    $Dispatch = $this->dispatch;

    ob_start();
    include __DIR__ . '/../../view/page/dispatch.php';
    ob_end_clean();

    expect($APPD->getData('ERROR'))->toBe('404');
    expect($APPD->getData('PAGE'))->toBe('404');
});

test('dispatch.php calls Smarty assign when dispatch found', function () {
    $this->user->method('hasPermission')->willReturn(true);

    $this->dispatch->method('getId')->willReturn(['id' => 5, 'event' => 'Flood']);
    $this->dispatch->method('getDispatch')->willReturn(['id' => 5]);
    $this->dispatch->method('beautifulLastDispatch')->willReturn(['event' => 'Flood']);

    $this->smarty->expects($this->atLeastOnce())->method('assign');

    $id       = 5;
    $DB       = $this->db;
    $User     = $this->user;
    $Smarty   = $this->smarty;
    $APPD     = $this->appd;
    $Dispatch = $this->dispatch;

    ob_start();
    include __DIR__ . '/../../view/page/dispatch.php';
    ob_end_clean();

    expect(true)->toBeTrue();
});

