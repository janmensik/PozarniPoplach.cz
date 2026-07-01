<?php

use Janmensik\Jmlib\AppData;
use Janmensik\Jmlib\Database;
use PozarniPoplach\EventType;

uses(\Tests\TestCase::class);

beforeEach(function () {
    $this->db = $this->createMock(Database::class);
    $this->db->db = $this->getMockBuilder('mysqli')
                         ->disableOriginalConstructor()
                         ->getMock();

    // Mock AppData
    $this->appd = AppData::getInstance();
    $this->appd->setData('CONFIG', [
        'event_types_url' => 'typy-udalosti'
    ]);
    $this->appd->setData('BASE_URL', 'http://localhost');

    // Mock User
    $this->user = $this->createMock(\PozarniPoplach\User::class);
    $this->user->method('hasPermission')->willReturn(true);

    // Mock Smarty
    $this->smarty = $this->createMock(\Smarty\Smarty::class);

    // Make them global
    $GLOBALS['DB'] = $this->db;
    $GLOBALS['User'] = $this->user;
    $GLOBALS['Smarty'] = $this->smarty;
    $GLOBALS['APPD'] = $this->appd;
});

test('event-type-edit.php handles GET for new event type', function () {
    $id = 'new';

    $this->smarty->expects($this->exactly(2))
                 ->method('assign');

    $eventTypeMock = $this->createMock(EventType::class);
    $eventTypeMock->method('get')->willReturn([]);

    // Set local variables
    $EventType = $eventTypeMock;
    $DB = $this->db;
    $User = $this->user;
    $Smarty = $this->smarty;
    $APPD = $this->appd;

    ob_start();
    include __DIR__ . '/../../view/page/event-type-edit.php';
    ob_end_clean();

    expect($APPD->getData('PAGE'))->toBe('event-type-edit');
});

test('event-type-edit.php handles GET for existing event type', function () {
    $id = 456;

    $eventTypeMock = $this->createMock(EventType::class);
    $eventTypeMock->method('getId')->with(456)->willReturn(['id' => 456, 'name' => 'Existing Type']);
    $eventTypeMock->method('get')->willReturn([]);

    $EventType = $eventTypeMock;
    $DB = $this->db;
    $User = $this->user;
    $Smarty = $this->smarty;
    $APPD = $this->appd;

    ob_start();
    include __DIR__ . '/../../view/page/event-type-edit.php';
    ob_end_clean();

    expect($APPD->getData('PAGE'))->toBe('event-type-edit');
});
