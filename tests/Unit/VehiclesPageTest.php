<?php

use Janmensik\Jmlib\AppData;
use Janmensik\Jmlib\Database;
use PozarniPoplach\Vehicle;

uses(\Tests\TestCase::class);

beforeEach(function () {
    $this->db = $this->createMock(Database::class);
    $this->db->db = $this->getMockBuilder('mysqli')
                         ->disableOriginalConstructor()
                         ->getMock();

    // Mock AppData
    $this->appd = AppData::getInstance();
    $this->appd->setData('APP', [
        'DEFAULT_ITEMS_PER_PAGE' => 20,
        'DEFAULT_ITEMS_PER_PAGE_DOTS' => 3
    ]);

    // Mock User
    $this->user = $this->createMock(\PozarniPoplach\User::class);
    $this->user->method('hasPermission')->willReturn(true);
    $this->user->method('setPageSchema')->willReturnArgument(1);

    // Mock Smarty
    $this->smarty = $this->createMock(\Smarty\Smarty::class);

    // Make them global
    $GLOBALS['DB'] = $this->db;
    $GLOBALS['User'] = $this->user;
    $GLOBALS['Smarty'] = $this->smarty;
    $GLOBALS['APPD'] = $this->appd;
});

test('vehicles.php assigns data to smarty', function () {
    $this->smarty->expects($this->atLeastOnce())
                 ->method('assign');

    $vehicleMock = $this->getMockBuilder(Vehicle::class)
                        ->disableOriginalConstructor()
                        ->onlyMethods(['get', 'getGroupTotal', 'getTotal', 'getRowsCount', 'getExtra'])
                        ->getMock();
    
    $vehicleMock->method('get')->willReturn([]);
    $vehicleMock->method('getRowsCount')->willReturn(0);
    $vehicleMock->method('getGroupTotal')->willReturn([]);
    $vehicleMock->method('getTotal')->willReturn([]);
    $vehicleMock->method('getExtra')->willReturn([]);
    
    // Set local variables
    $Vehicle = $vehicleMock;
    $DB = $this->db;
    $User = $this->user;
    $Smarty = $this->smarty;
    $APPD = $this->appd;

    ob_start();
    include __DIR__ . '/../../view/page/vehicles.php';
    ob_end_clean();
    
    expect($APPD->getData('PAGE'))->toBe('vehicles');
});
