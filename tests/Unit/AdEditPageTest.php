<?php

use Janmensik\Jmlib\AppData;
use Janmensik\Jmlib\Database;
use PozarniPoplach\Ad;
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
        'DEFAULT_ITEMS_PER_PAGE' => 20
    ]);
    $this->appd->setData('CONFIG', [
        'ads_url' => 'reklamy'
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

test('ad-edit.php handles GET for new ad', function () {
    $id = 'new';
    
    $this->smarty->expects($this->exactly(3))
                 ->method('assign');

    $adMock = $this->createMock(Ad::class);
    $advertiserMock = $this->createMock(Advertiser::class);
    $advertiserMock->method('get')->willReturn([]);

    // Set local variables
    $Ad = $adMock;
    $Advertiser = $advertiserMock;
    $DB = $this->db;
    $User = $this->user;
    $Smarty = $this->smarty;
    $APPD = $this->appd;

    ob_start();
    include __DIR__ . '/../../view/page/ad-edit.php';
    ob_end_clean();
    
    expect($APPD->getData('PAGE'))->toBe('ad-edit');
});

test('ad-edit.php handles GET for existing ad', function () {
    $id = 123;
    
    $adMock = $this->createMock(Ad::class);
    $adMock->method('getId')->with(123)->willReturn(['id' => 123, 'title' => 'Existing Ad']);
    
    $advertiserMock = $this->createMock(Advertiser::class);
    
    $Ad = $adMock;
    $Advertiser = $advertiserMock;
    $DB = $this->db;
    $User = $this->user;
    $Smarty = $this->smarty;
    $APPD = $this->appd;

    ob_start();
    include __DIR__ . '/../../view/page/ad-edit.php';
    ob_end_clean();
    
    expect($APPD->getData('PAGE'))->toBe('ad-edit');
});
