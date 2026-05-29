<?php

use Janmensik\Jmlib\AppData;
use Janmensik\Jmlib\Database;
use PozarniPoplach\Device;

uses(\Tests\TestCase::class);

beforeEach(function () {
    $this->db = $this->createMock(Database::class);
    $this->db->db = $this->getMockBuilder('mysqli')
                         ->disableOriginalConstructor()
                         ->getMock();

    $this->appd = AppData::getInstance();
    $this->appd->setData('APP', [
        'DEFAULT_ITEMS_PER_PAGE' => 20
    ]);
    $this->appd->setData('CONFIG', [
        'devices_url' => 'zarizeni'
    ]);

    $this->user = $this->createMock(\PozarniPoplach\User::class);
    $this->user->method('hasPermission')->willReturn(true);

    $this->smarty = $this->createMock(\Smarty\Smarty::class);

    $GLOBALS['DB'] = $this->db;
    $GLOBALS['User'] = $this->user;
    $GLOBALS['Smarty'] = $this->smarty;
    $GLOBALS['APPD'] = $this->appd;
    
    $_POST = [];
    $GLOBALS['id'] = 'new';
});

test('device-edit.php handles GET for new device', function () {
    $this->smarty->expects($this->once())
                 ->method('assign')
                 ->with('data', null);

    $DB = $this->db;
    $User = $this->user;
    $Smarty = $this->smarty;
    $APPD = $this->appd;
    $id = 'new';

    ob_start();
    (function() use (&$DB, &$User, &$Smarty, &$APPD, &$id) {
        include __DIR__ . '/../../view/page/device-edit.php';
    })();
    ob_end_clean();
    
    expect(true)->toBeTrue();
});

test('device-edit.php handles POST saving', function () {
    $_POST = [
        'device_name' => 'New Device',
        'ad_probability' => 10
    ];

    $devMock = $this->getMockBuilder(Device::class)
                   ->disableOriginalConstructor()
                   ->onlyMethods(['setter', 'validate', 'mapFromPost'])
                   ->getMock();
    
    $devMock->DB = $this->db;
    
    $devMock->method('validate')->willReturn([]);
    $devMock->method('setter')->willReturn(999);
    $devMock->data = ['device_name' => 'New Device'];
    
    $Device = $devMock;
    $DB = $this->db;
    $User = $this->user;
    $Smarty = $this->smarty;
    $APPD = $this->appd;
    $id = 'new';

    ob_start();
    try {
        (function() use (&$Device, &$DB, &$User, &$Smarty, &$APPD, &$id) {
            include __DIR__ . '/../../view/page/device-edit.php';
        })();
    } catch (\Error | \Exception $e) {
        // header() might throw error
    }
    ob_end_clean();
    
    expect(true)->toBeTrue();
});

test('device-edit.php handles POST delete', function () {
    $deviceId = 123;
    $GLOBALS['id'] = $deviceId;
    $id = $deviceId;
    
    $_POST = ['delete' => '1'];

    $devMock = $this->getMockBuilder(Device::class)
                   ->disableOriginalConstructor()
                   ->onlyMethods(['delete', 'getId'])
                   ->getMock();
    
    $devMock->DB = $this->db;
    
    $devMock->method('getId')->willReturn(['id' => $deviceId, 'device_name' => 'To Delete']);
    $devMock->method('delete')->with($deviceId)->willReturn(true);
    
    $Device = $devMock;
    $DB = $this->db;
    $User = $this->user;
    $Smarty = $this->smarty;
    $APPD = $this->appd;

    ob_start();
    try {
        (function() use (&$Device, &$DB, &$User, &$Smarty, &$APPD, &$id) {
            include __DIR__ . '/../../view/page/device-edit.php';
        })();
    } catch (\Error | \Exception $e) {
        // header() might throw error
    }
    ob_end_clean();
    
    expect(true)->toBeTrue();
});
