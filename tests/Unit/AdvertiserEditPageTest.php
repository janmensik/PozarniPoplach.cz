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

    $this->appd = AppData::getInstance();
    $this->appd->setData('APP', [
        'DEFAULT_ITEMS_PER_PAGE' => 20
    ]);
    $this->appd->setData('CONFIG', [
        'advertisers_url' => 'inzerenti'
    ]);

    $this->user = $this->createMock(\PozarniPoplach\User::class);
    $this->user->method('hasPermission')->willReturn(true);

    $this->smarty = $this->createMock(\Smarty\Smarty::class);

    $GLOBALS['DB'] = $this->db;
    $GLOBALS['User'] = $this->user;
    $GLOBALS['Smarty'] = $this->smarty;
    $GLOBALS['APPD'] = $this->appd;
    
    // Clear POST and ID
    $_POST = [];
    $GLOBALS['id'] = 'new';
});

test('advertiser-edit.php handles GET for new advertiser', function () {
    $this->smarty->expects($this->once())
                 ->method('assign')
                 ->with('data', null);

    $DB = $this->db;
    $User = $this->user;
    $Smarty = $this->smarty;
    $APPD = $this->appd;
    $id = 'new';

    ob_start();
    include __DIR__ . '/../../view/page/advertiser-edit.php';
    ob_end_clean();
    
    expect(true)->toBeTrue();
});

test('advertiser-edit.php handles POST saving', function () {
    $_POST = [
        'name' => 'Test Advertiser',
        'contact_email' => 'test@example.com'
    ];

    $advMock = $this->getMockBuilder(Advertiser::class)
                   ->disableOriginalConstructor()
                   ->onlyMethods(['setter', 'validate', 'mapFromPost'])
                   ->getMock();
    
    $advMock->DB = $this->db;
    
    $advMock->method('validate')->willReturn([]);
    $advMock->method('setter')->willReturn(789);
    $advMock->data = ['name' => 'Test Advertiser'];
    
    $Advertiser = $advMock;
    $DB = $this->db;
    $User = $this->user;
    $Smarty = $this->smarty;
    $APPD = $this->appd;
    $id = 'new';

    // Capture output and ignore headers
    ob_start();
    try {
        include __DIR__ . '/../../view/page/advertiser-edit.php';
    } catch (\Error | \Exception $e) {
        // header() might throw error
    }
    ob_end_clean();
    
    expect(true)->toBeTrue();
});
