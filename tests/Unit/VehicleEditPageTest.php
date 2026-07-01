<?php

use Janmensik\Jmlib\AppData;
use Janmensik\Jmlib\Database;
use PozarniPoplach\Vehicle;
use PozarniPoplach\Unit;
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
        'vehicles_url' => 'vozidla'
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

test('vehicle-edit.php handles GET for new vehicle', function () {
    $id = 'new';

    $this->smarty->expects($this->atLeastOnce())
                 ->method('assign');

    $vehicleMock = $this->createMock(Vehicle::class);
    $unitMock = $this->createMock(Unit::class);
    $vehicleTypeMock = $this->createMock(VehicleType::class);

    $unitMock->method('get')->willReturn([]);
    $vehicleTypeMock->method('get')->willReturn([]);

    // Set local variables
    $Vehicle = $vehicleMock;
    $Unit = $unitMock;
    $VehicleType = $vehicleTypeMock;
    $DB = $this->db;
    $User = $this->user;
    $Smarty = $this->smarty;
    $APPD = $this->appd;

    ob_start();
    include __DIR__ . '/../../view/page/vehicle-edit.php';
    ob_end_clean();

    expect($APPD->getData('PAGE'))->toBe('vehicle-edit');
});

test('vehicle-edit.php handles GET for existing vehicle', function () {
    $id = 10;

    $vehicleMock = $this->createMock(Vehicle::class);
    $vehicleMock->method('getId')->with(10)->willReturn(['id' => 10, 'name' => 'Existing Vehicle']);

    $unitMock = $this->createMock(Unit::class);
    $vehicleTypeMock = $this->createMock(VehicleType::class);

    $unitMock->method('get')->willReturn([]);
    $vehicleTypeMock->method('get')->willReturn([]);

    $Vehicle = $vehicleMock;
    $Unit = $unitMock;
    $VehicleType = $vehicleTypeMock;
    $DB = $this->db;
    $User = $this->user;
    $Smarty = $this->smarty;
    $APPD = $this->appd;

    ob_start();
    include __DIR__ . '/../../view/page/vehicle-edit.php';
    ob_end_clean();

    expect($APPD->getData('PAGE'))->toBe('vehicle-edit');
});
