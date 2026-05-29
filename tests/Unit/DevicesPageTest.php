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
        'DEFAULT_ITEMS_PER_PAGE' => 20,
        'DEFAULT_ITEMS_PER_PAGE_DOTS' => 3
    ]);
    $this->appd->setData('CONFIG', [
        'devices_url' => 'zarizeni'
    ]);

    $this->user = $this->createMock(\PozarniPoplach\User::class);
    $this->user->method('hasPermission')->willReturn(true);
    $this->user->method('setPageSchema')->willReturnArgument(1);

    $this->smarty = $this->createMock(\Smarty\Smarty::class);

    $GLOBALS['DB'] = $this->db;
    $GLOBALS['User'] = $this->user;
    $GLOBALS['Smarty'] = $this->smarty;
    $GLOBALS['APPD'] = $this->appd;
    
    if (!function_exists('pagination')) {
        function pagination($a, $b, $c, $d) { return []; }
    }
});

test('devices.php assigns data to smarty', function () {
    $this->smarty->expects($this->atLeastOnce())
                 ->method('assign');

    $devMock = $this->getMockBuilder(Device::class)
                   ->disableOriginalConstructor()
                   ->onlyMethods(['get', 'getGroupTotal', 'getTotal', 'getRowsCount', 'getExtra'])
                   ->getMock();
    
    $devMock->DB = $this->db;
    
    $devMock->method('get')->willReturn([]);
    $devMock->method('getRowsCount')->willReturn(0);
    $devMock->method('getGroupTotal')->willReturn([]);
    $devMock->method('getTotal')->willReturn([]);
    $devMock->method('getExtra')->willReturn([]);
    
    $Device = $devMock;
    $DB = $this->db;
    $User = $this->user;
    $Smarty = $this->smarty;
    $APPD = $this->appd;

    ob_start();
    (function() use (&$Device, &$DB, &$User, &$Smarty, &$APPD) {
        include __DIR__ . '/../../view/page/devices.php';
    })();
    ob_end_clean();
    
    expect(true)->toBeTrue();
});
