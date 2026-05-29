<?php

use Janmensik\Jmlib\AppData;
use Janmensik\Jmlib\Database;
use PozarniPoplach\Ad;

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
    $this->appd->setData('CONFIG', [
        'ads_url' => 'reklamy'
    ]);

    // Mock User
    $this->user = $this->createMock(\PozarniPoplach\User::class);
    $this->user->method('hasPermission')->willReturn(true);
    $this->user->method('setPageSchema')->willReturnArgument(1);

    // Mock Smarty
    $this->smarty = $this->createMock(\Smarty\Smarty::class);

    // Make them global as the page scripts expect them
    $GLOBALS['DB'] = $this->db;
    $GLOBALS['User'] = $this->user;
    $GLOBALS['Smarty'] = $this->smarty;
    $GLOBALS['APPD'] = $this->appd;
    
    // We need to define some functions that might be called
    if (!function_exists('pagination')) {
        function pagination($a, $b, $c, $d) { return []; }
    }
});

test('ads.php assigns data to smarty', function () {
    $this->smarty->expects($this->atLeastOnce())
                 ->method('assign');

    $adMock = $this->getMockBuilder(Ad::class)
                   ->setConstructorArgs([$this->db])
                   ->onlyMethods(['get', 'getGroupTotal', 'getTotal', 'getRowsCount', 'getExtra'])
                   ->getMock();
    
    $adMock->method('get')->willReturn([]);
    $adMock->method('getRowsCount')->willReturn(0);
    $adMock->method('getGroupTotal')->willReturn([]);
    $adMock->method('getTotal')->willReturn([]);
    $adMock->method('getExtra')->willReturn([]);
    
    // Set local variables that include will pick up
    $Ad = $adMock;
    $DB = $this->db;
    $User = $this->user;
    $Smarty = $this->smarty;
    $APPD = $this->appd;

    ob_start();
    include __DIR__ . '/../../view/page/ads.php';
    ob_end_clean();
    
    expect(true)->toBeTrue();
});
