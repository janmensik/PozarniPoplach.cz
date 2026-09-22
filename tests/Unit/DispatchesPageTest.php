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
    $this->appd->setData('APP', ['DEFAULT_ITEMS_PER_PAGE' => 20, 'DEFAULT_ITEMS_PER_PAGE_DOTS' => 5]);

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

test('dispatches.php sets PAGE to dispatches', function () {
    $this->user->method('hasPermission')->willReturn(true);
    $this->user->method('setPageSchema')->willReturn([]);

    $this->dispatch->method('get')->willReturn([]);
    $this->dispatch->method('getGroupTotal')->willReturn([]);
    $this->dispatch->method('getTotal')->willReturn(['id' => 0]);
    $this->dispatch->method('getRowsCount')->willReturn(0);

    $DB       = $this->db;
    $User     = $this->user;
    $Smarty   = $this->smarty;
    $APPD     = $this->appd;
    $Dispatch = $this->dispatch;

    ob_start();
    include __DIR__ . '/../../view/page/dispatches.php';
    ob_end_clean();

    expect($APPD->getData('PAGE'))->toBe('dispatches');
});

test('dispatches.php sets ERROR 403 when permission denied', function () {
    $this->user->method('hasPermission')->willReturn(false);

    $DB       = $this->db;
    $User     = $this->user;
    $Smarty   = $this->smarty;
    $APPD     = $this->appd;
    $Dispatch = $this->dispatch;

    ob_start();
    include __DIR__ . '/../../view/page/dispatches.php';
    ob_end_clean();

    expect($APPD->getData('ERROR'))->toBe('403');
});

test('dispatches.php calls Smarty assign with data when permission granted', function () {
    $this->user->method('hasPermission')->willReturn(true);
    $this->user->method('setPageSchema')->willReturn([]);

    $this->dispatch->method('get')->willReturn([['id' => 1, 'event' => 'Fire']]);
    $this->dispatch->method('getGroupTotal')->willReturn([]);
    $this->dispatch->method('getTotal')->willReturn(['id' => 1]);
    $this->dispatch->method('getRowsCount')->willReturn(1);

    $this->smarty->expects($this->atLeastOnce())->method('assign');

    $DB       = $this->db;
    $User     = $this->user;
    $Smarty   = $this->smarty;
    $APPD     = $this->appd;
    $Dispatch = $this->dispatch;

    ob_start();
    include __DIR__ . '/../../view/page/dispatches.php';
    ob_end_clean();

    expect(true)->toBeTrue();
});

test('dispatches.php passes order param to Dispatch::get when present', function () {
    $_GET['order'] = 3;

    $this->user->method('hasPermission')->willReturn(true);
    $this->user->method('setPageSchema')->willReturn(['order' => 3]);

    $this->dispatch->expects($this->once())->method('get')
        ->with($this->anything(), 3)
        ->willReturn([]);
    $this->dispatch->method('getGroupTotal')->willReturn([]);
    $this->dispatch->method('getTotal')->willReturn([]);
    $this->dispatch->method('getRowsCount')->willReturn(0);
    $this->dispatch->method('getExtra')->willReturn([]);

    $DB       = $this->db;
    $User     = $this->user;
    $Smarty   = $this->smarty;
    $APPD     = $this->appd;
    $Dispatch = $this->dispatch;

    ob_start();
    include __DIR__ . '/../../view/page/dispatches.php';
    ob_end_clean();

    expect(true)->toBeTrue();
});

