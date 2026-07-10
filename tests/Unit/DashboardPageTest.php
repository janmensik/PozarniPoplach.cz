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

    $this->appd = AppData::getInstance();
    $this->appd->setData('BASE_URL', 'http://localhost');
 
    $this->user = $this->createMock(User::class);
    $this->smarty = $this->createMock(\Smarty\Smarty::class);
 
    $GLOBALS['DB'] = $this->db;
    $GLOBALS['User'] = $this->user;
    $GLOBALS['Smarty'] = $this->smarty;
    $GLOBALS['APPD'] = $this->appd;
 
    $_GET = [];
});
 
afterEach(function () {
    $_GET = [];
});
 
test('dashboard.php sets PAGE to dashboard', function () {
    $this->user->method('hasPermission')->willReturn(true);
    $this->user->method('setPageSchema')->willReturn([]);
 
    $this->db->method('getResult')->willReturn(0);
    $this->db->method('getAllRows')->willReturn([]);
    $this->db->method('getRow')->willReturn(['total_views' => 0, 'total_clicks' => 0]);

    $DB = $this->db;
    $User = $this->user;
    $Smarty = $this->smarty;
    $APPD = $this->appd;
 
    ob_start();
    include __DIR__ . '/../../view/page/dashboard.php';
    ob_end_clean();
 
    expect($APPD->getData('PAGE'))->toBe('dashboard');
});
 
test('dashboard.php sets ERROR to 403 when permission denied', function () {
    $this->user->method('hasPermission')->willReturn(false);
 
    $DB = $this->db;
    $User = $this->user;
    $Smarty = $this->smarty;
    $APPD = $this->appd;
 
    ob_start();
    include __DIR__ . '/../../view/page/dashboard.php';
    ob_end_clean();
 
    expect($APPD->getData('ERROR'))->toBe('403');
});
 
test('dashboard.php calls Smarty assign when permission granted', function () {
    $this->user->method('hasPermission')->willReturn(true);
    $this->user->method('setPageSchema')->willReturn([]);
 
    $this->smarty->expects($this->atLeastOnce())->method('assign');
 
    $this->db->method('getResult')->willReturn(0);
    $this->db->method('getAllRows')->willReturn([]);
    $this->db->method('getRow')->willReturn(['total_views' => 0, 'total_clicks' => 0]);

    $DB = $this->db;
    $User = $this->user;
    $Smarty = $this->smarty;
    $APPD = $this->appd;
 
    ob_start();
    include __DIR__ . '/../../view/page/dashboard.php';
    ob_end_clean();
 
    expect(true)->toBeTrue();
});
