<?php

use Janmensik\Jmlib\AppData;
use Janmensik\Jmlib\Database;
use PozarniPoplach\Unit;

uses(\Tests\TestCase::class);

beforeEach(function () {
    $this->db = $this->createMock(Database::class);
    $this->db->db = $this->getMockBuilder('mysqli')
                         ->disableOriginalConstructor()
                         ->getMock();

    // Mock AppData
    $this->appd = AppData::getInstance();
    $this->appd->setData('CONFIG', [
        'units_url' => 'jednotky'
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

test('unit-edit.php handles GET for new unit', function () {
    $id = 'new';
    
    $this->smarty->expects($this->atLeastOnce())
                 ->method('assign');

    $unitMock = $this->createMock(Unit::class);
    $unitMock->method('getRegions')->willReturn([]);

    // Set local variables
    $Unit = $unitMock;
    $DB = $this->db;
    $User = $this->user;
    $Smarty = $this->smarty;
    $APPD = $this->appd;

    ob_start();
    include __DIR__ . '/../../view/page/unit-edit.php';
    ob_end_clean();
    
    expect($APPD->getData('PAGE'))->toBe('unit-edit');
});

test('unit-edit.php handles GET for existing unit', function () {
    $id = 789;
    
    $unitMock = $this->createMock(Unit::class);
    $unitMock->method('getId')->with(789)->willReturn(['id' => 789, 'fullname' => 'Existing Unit']);
    $unitMock->method('getRegions')->willReturn([]);
    
    $Unit = $unitMock;
    $DB = $this->db;
    $User = $this->user;
    $Smarty = $this->smarty;
    $APPD = $this->appd;

    ob_start();
    include __DIR__ . '/../../view/page/unit-edit.php';
    ob_end_clean();
    
    expect($APPD->getData('PAGE'))->toBe('unit-edit');
});
