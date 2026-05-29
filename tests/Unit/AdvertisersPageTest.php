<?php

use Janmensik\Jmlib\AppData;
use Janmensik\Jmlib\Database;
use PozarniPoplach\Advertiser;

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
        'advertisers_url' => 'inzerenti'
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
    
    if (!function_exists('pagination')) {
        function pagination($a, $b, $c, $d) { return []; }
    }
});

test('advertisers.php assigns data to smarty', function () {
    $this->smarty->expects($this->atLeastOnce())
                 ->method('assign');

    $advMock = $this->getMockBuilder(Advertiser::class)
                   ->disableOriginalConstructor()
                   ->onlyMethods(['get', 'getGroupTotal', 'getTotal', 'getRowsCount', 'getExtra'])
                   ->getMock();

    $advMock->DB = $this->db;

    $advMock->method('get')->willReturn([]);
    $advMock->method('getRowsCount')->willReturn(0);
    $advMock->method('getGroupTotal')->willReturn([]);
    $advMock->method('getTotal')->willReturn([]);
    $advMock->method('getExtra')->willReturn([]);

    $Advertiser = $advMock;
    $DB = $this->db;
    $User = $this->user;
    $Smarty = $this->smarty;
    $APPD = $this->appd;

    ob_start();
    include __DIR__ . '/../../view/page/advertisers.php';
    ob_end_clean();

    expect(true)->toBeTrue();
});
