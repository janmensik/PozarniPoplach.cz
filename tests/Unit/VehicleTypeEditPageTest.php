<?php

use Janmensik\Jmlib\AppData;
use Janmensik\Jmlib\Database;
use PozarniPoplach\VehicleType;

uses(\Tests\TestCase::class);

beforeEach(function () {
    $this->db = $this->createMock(Database::class);
    $this->db->db = $this->getMockBuilder('mysqli')
                         ->disableOriginalConstructor()
                         ->getMock();

    // Mock AppData
    $this->appd = AppData::getInstance();
    $this->appd->setData('CONFIG', [
        'vehicle_types_url' => 'typy-vozidel'
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

test('vehicle-type-edit.php handles GET for new vehicle type', function () {
    $id = 'new';

    $this->smarty->expects($this->once())
                 ->method('assign');

    $vehicleTypeMock = $this->createMock(VehicleType::class);

    // Set local variables
    $VehicleType = $vehicleTypeMock;
    $DB = $this->db;
    $User = $this->user;
    $Smarty = $this->smarty;
    $APPD = $this->appd;

    ob_start();
    include __DIR__ . '/../../view/page/vehicle-type-edit.php';
    ob_end_clean();

    expect($APPD->getData('PAGE'))->toBe('vehicle-type-edit');
});

test('vehicle-type-edit.php handles GET for existing vehicle type', function () {
    $id = 101;

    $vehicleTypeMock = $this->createMock(VehicleType::class);
    $vehicleTypeMock->method('getId')->with(101)->willReturn(['id' => 101, 'type' => 'Existing Type']);

    $VehicleType = $vehicleTypeMock;
    $DB = $this->db;
    $User = $this->user;
    $Smarty = $this->smarty;
    $APPD = $this->appd;

    ob_start();
    include __DIR__ . '/../../view/page/vehicle-type-edit.php';
    ob_end_clean();

    expect($APPD->getData('PAGE'))->toBe('vehicle-type-edit');
});
