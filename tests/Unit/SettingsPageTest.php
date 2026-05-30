<?php

use Janmensik\Jmlib\AppData;
use Janmensik\Jmlib\Database;
use PozarniPoplach\User;

uses(\Tests\TestCase::class);

beforeEach(function () {
    $this->db = $this->createMock(Database::class);
    $this->db->db = $this->getMockBuilder('mysqli')
                         ->disableOriginalConstructor()
                         ->getMock();

    // Mock AppData
    $this->appd = AppData::getInstance();
    $this->appd->setData('BASE_URL', 'http://localhost');

    // Mock User
    $this->user = $this->createMock(User::class);
    $this->user->method('hasPermission')->willReturn(true);
    $this->user->method('getUser')->willReturn(['id' => 1, 'name' => 'Me', 'status' => 'admin']);

    // Mock Smarty
    $this->smarty = $this->createMock(\Smarty\Smarty::class);

    // Make them global
    $GLOBALS['DB'] = $this->db;
    $GLOBALS['User'] = $this->user;
    $GLOBALS['Smarty'] = $this->smarty;
    $GLOBALS['APPD'] = $this->appd;
});

test('settings.php handles GET for own settings', function () {
    $id = null;
    
    $this->smarty->expects($this->atLeastOnce())
                 ->method('assign');

    // Set local variables
    $DB = $this->db;
    $User = $this->user;
    $Smarty = $this->smarty;
    $APPD = $this->appd;

    ob_start();
    include __DIR__ . '/../../view/page/settings.php';
    ob_end_clean();
    
    expect($APPD->getData('PAGE'))->toBe('settings');
});

test('settings.php handles GET for other user settings', function () {
    $id = 2;
    
    $this->user->method('getId')->with(2)->willReturn(['id' => 2, 'name' => 'Other User', 'status' => 'manager']);
    
    $DB = $this->db;
    $User = $this->user;
    $Smarty = $this->smarty;
    $APPD = $this->appd;

    ob_start();
    include __DIR__ . '/../../view/page/settings.php';
    ob_end_clean();
    
    expect($APPD->getData('PAGE'))->toBe('settings');
});
