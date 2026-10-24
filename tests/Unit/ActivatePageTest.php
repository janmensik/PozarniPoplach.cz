<?php

use Janmensik\Jmlib\AppData;
use Janmensik\Jmlib\Database;
use PozarniPoplach\DeviceAuth;
use PozarniPoplach\User;

uses(\Tests\TestCase::class);

beforeEach(function () {
    $this->db = $this->createMock(Database::class);
    $this->db->db = $this->getMockBuilder('mysqli')
                         ->disableOriginalConstructor()
                         ->getMock();

    $this->appd = AppData::getInstance();
    $this->appd->setData('BASE_URL', 'http://localhost');
    $this->appd->setData('DEVICE_CODE', null);

    $this->user = $this->createMock(User::class);
    $this->deviceAuth = $this->createMock(DeviceAuth::class);
    $this->smarty = $this->createMock(\Smarty\Smarty::class);

    $this->smartyAssigned = [];
    $this->smarty->method('assign')->willReturnCallback(function ($key, $value = null) {
        if (is_array($key)) {
            $this->smartyAssigned = array_merge($this->smartyAssigned, $key);
        } else {
            $this->smartyAssigned[$key] = $value;
        }
    });

    $GLOBALS['DB'] = $this->db;
    $GLOBALS['User'] = $this->user;
    $GLOBALS['DeviceAuth'] = $this->deviceAuth;
    $GLOBALS['Smarty'] = $this->smarty;
    $GLOBALS['APPD'] = $this->appd;

    $_POST = [];
    $_GET = [];
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SESSION = [];
});

afterEach(function () {
    $_POST = [];
    $_GET = [];
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SESSION = [];
    $this->appd->setData('DEVICE_CODE', null);
});

test('activate.php sets PAGE to activate', function () {
    $this->user->method('getUser')->willReturn(['id' => 1, 'name' => 'Admin', 'email' => 'admin@test.cz']);

    $DB = $this->db;
    $User = $this->user;
    $DeviceAuth = $this->deviceAuth;
    $Smarty = $this->smarty;
    $APPD = $this->appd;

    ob_start();
    include __DIR__ . '/../../view/page/activate.php';
    ob_end_clean();

    expect($APPD->getData('PAGE'))->toBe('activate');
});

test('activate.php redirects to login and stores redirect target with code when user is not logged in', function () {
    $this->user->method('getUser')->willReturn(null);
    $this->appd->setData('DEVICE_CODE', 'ABCD1234');

    $DB = $this->db;
    $User = $this->user;
    $DeviceAuth = $this->deviceAuth;
    $Smarty = $this->smarty;
    $APPD = $this->appd;

    ob_start();
    include __DIR__ . '/../../view/page/activate.php';
    ob_end_clean();

    expect($_SESSION['login_redirect'] ?? null)->toBe('/activate/ABCD1234');
});

test('activate.php redirects to login and stores redirect target without code when user is not logged in', function () {
    $this->user->method('getUser')->willReturn(null);

    $DB = $this->db;
    $User = $this->user;
    $DeviceAuth = $this->deviceAuth;
    $Smarty = $this->smarty;
    $APPD = $this->appd;

    ob_start();
    include __DIR__ . '/../../view/page/activate.php';
    ob_end_clean();

    expect($_SESSION['login_redirect'] ?? null)->toBe('/activate');
});

test('activate.php initializes CSRF token in session if not present', function () {
    $this->user->method('getUser')->willReturn(['id' => 1, 'name' => 'Admin', 'email' => 'admin@test.cz']);

    $DB = $this->db;
    $User = $this->user;
    $DeviceAuth = $this->deviceAuth;
    $Smarty = $this->smarty;
    $APPD = $this->appd;

    ob_start();
    include __DIR__ . '/../../view/page/activate.php';
    ob_end_clean();

    expect($_SESSION['csrf_token_device'] ?? null)->not->toBeEmpty();
    expect($this->smartyAssigned['csrf_token_device'] ?? null)->toBe($_SESSION['csrf_token_device']);
});

test('activate.php assigns error when device code is not found or expired', function () {
    $this->user->method('getUser')->willReturn(['id' => 1, 'name' => 'Admin', 'email' => 'admin@test.cz']);
    $this->appd->setData('DEVICE_CODE', 'EXPIRED1');
    $this->deviceAuth->method('checkSessionStatus')->with('EXPIRED1')->willReturn(null);

    $DB = $this->db;
    $User = $this->user;
    $DeviceAuth = $this->deviceAuth;
    $Smarty = $this->smarty;
    $APPD = $this->appd;

    ob_start();
    include __DIR__ . '/../../view/page/activate.php';
    ob_end_clean();

    expect($this->smartyAssigned['error'])->toBe('Neplatný nebo prošlý aktivační kód.');
    expect($this->smartyAssigned['session_data'])->toBeNull();
});

test('activate.php assigns error when device code status is not pending', function () {
    $this->user->method('getUser')->willReturn(['id' => 1, 'name' => 'Admin', 'email' => 'admin@test.cz']);
    $this->appd->setData('DEVICE_CODE', 'LINKED12');
    $this->deviceAuth->method('checkSessionStatus')->with('LINKED12')->willReturn(['status' => 'linked']);

    $DB = $this->db;
    $User = $this->user;
    $DeviceAuth = $this->deviceAuth;
    $Smarty = $this->smarty;
    $APPD = $this->appd;

    ob_start();
    include __DIR__ . '/../../view/page/activate.php';
    ob_end_clean();

    expect($this->smartyAssigned['error'])->toBe('Neplatný nebo prošlý aktivační kód.');
    expect($this->smartyAssigned['session_data'])->toBeNull();
});

test('activate.php loads allowed units and assigns template variables on valid GET', function () {
    $currentUser = ['id' => 1, 'name' => 'Jan Novák', 'email' => 'jan@test.cz'];
    $allowedUnits = [
        ['id' => 10, 'fullname' => 'JSDH Brno'],
        ['id' => 20, 'fullname' => 'JSDH Praha'],
    ];
    $sessionData = [
        'status' => 'pending',
        'device_code' => 'VALID123',
        'device_uuid' => 'uuid-123',
    ];

    $this->user->method('getUser')->willReturn($currentUser);
    $this->user->method('getUnitsForUser')->willReturn($allowedUnits);
    $this->appd->setData('DEVICE_CODE', 'VALID123');
    $this->deviceAuth->method('checkSessionStatus')->with('VALID123')->willReturn($sessionData);

    $DB = $this->db;
    $User = $this->user;
    $DeviceAuth = $this->deviceAuth;
    $Smarty = $this->smarty;
    $APPD = $this->appd;

    ob_start();
    include __DIR__ . '/../../view/page/activate.php';
    ob_end_clean();

    expect($this->smartyAssigned['session_data'])->toBe($sessionData);
    expect($this->smartyAssigned['units'])->toBe($allowedUnits);
    expect($this->smartyAssigned['device_code'])->toBe('VALID123');
    expect($this->smartyAssigned['current_user'])->toBe($currentUser);
    expect($this->smartyAssigned['error'])->toBeNull();
});

test('activate.php accepts manual code entry via GET parameter and normalizes to uppercase', function () {
    $currentUser = ['id' => 1, 'name' => 'Jan Novák', 'email' => 'jan@test.cz'];
    $sessionData = [
        'status' => 'pending',
        'device_code' => 'MANU4567',
        'device_uuid' => 'uuid-456',
    ];

    $_GET['code'] = '  manu4567 ';
    $this->user->method('getUser')->willReturn($currentUser);
    $this->user->method('getUnitsForUser')->willReturn([['id' => 10, 'fullname' => 'JSDH Brno']]);
    $this->deviceAuth->method('checkSessionStatus')->with('MANU4567')->willReturn($sessionData);

    $DB = $this->db;
    $User = $this->user;
    $DeviceAuth = $this->deviceAuth;
    $Smarty = $this->smarty;
    $APPD = $this->appd;

    ob_start();
    include __DIR__ . '/../../view/page/activate.php';
    ob_end_clean();

    expect($this->smartyAssigned['device_code'])->toBe('MANU4567');
    expect($this->smartyAssigned['session_data'])->toBe($sessionData);
    expect($this->smartyAssigned['error'])->toBeNull();
});

test('activate.php rejects POST when CSRF token is invalid', function () {
    $currentUser = ['id' => 1, 'name' => 'Admin', 'email' => 'admin@test.cz'];
    $this->user->method('getUser')->willReturn($currentUser);
    $this->user->method('getUnitsForUser')->willReturn([['id' => 10, 'fullname' => 'JSDH Brno']]);
    $this->appd->setData('DEVICE_CODE', 'VALID123');
    $this->deviceAuth->method('checkSessionStatus')->willReturn(['status' => 'pending', 'device_code' => 'VALID123']);

    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SESSION['csrf_token_device'] = 'real_csrf_token';
    $_POST['csrf_token_device'] = 'wrong_csrf_token';
    $_POST['unit_id'] = 10;

    $DB = $this->db;
    $User = $this->user;
    $DeviceAuth = $this->deviceAuth;
    $Smarty = $this->smarty;
    $APPD = $this->appd;

    ob_start();
    include __DIR__ . '/../../view/page/activate.php';
    ob_end_clean();

    expect($this->smartyAssigned['error'])->toBe('Neplatný bezpečnostní token (CSRF). Zkuste to prosím znovu.');
});

test('activate.php rejects POST when unit_id is not in allowed units', function () {
    $currentUser = ['id' => 2, 'name' => 'Manager', 'email' => 'mgr@test.cz'];
    $this->user->method('getUser')->willReturn($currentUser);
    // User only has access to unit 10
    $this->user->method('getUnitsForUser')->willReturn([['id' => 10, 'fullname' => 'JSDH Brno']]);
    $this->appd->setData('DEVICE_CODE', 'VALID123');
    $this->deviceAuth->method('checkSessionStatus')->willReturn(['status' => 'pending', 'device_code' => 'VALID123']);

    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SESSION['csrf_token_device'] = 'valid_token';
    $_POST['csrf_token_device'] = 'valid_token';
    $_POST['unit_id'] = 999; // Unit 999 is NOT allowed!

    $DB = $this->db;
    $User = $this->user;
    $DeviceAuth = $this->deviceAuth;
    $Smarty = $this->smarty;
    $APPD = $this->appd;

    ob_start();
    include __DIR__ . '/../../view/page/activate.php';
    ob_end_clean();

    expect($this->smartyAssigned['error'])->toBe('Tato jednotka vám není přiřazena.');
});

test('activate.php successfully links session on valid POST and clears CSRF token', function () {
    $currentUser = ['id' => 5, 'name' => 'Velitel', 'email' => 'velitel@test.cz'];
    $this->user->method('getUser')->willReturn($currentUser);
    $this->user->method('getUnitsForUser')->willReturn([['id' => 10, 'fullname' => 'JSDH Brno']]);
    $this->appd->setData('DEVICE_CODE', 'VALID123');
    $this->deviceAuth->method('checkSessionStatus')->willReturn(['status' => 'pending', 'device_code' => 'VALID123']);

    $this->deviceAuth->expects($this->once())
        ->method('linkSessionToUnit')
        ->with('VALID123', 10, 'Velín stanice', 5)
        ->willReturn(true);

    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SESSION['csrf_token_device'] = 'valid_token';
    $_POST['csrf_token_device'] = 'valid_token';
    $_POST['unit_id'] = 10;
    $_POST['device_name'] = 'Velín stanice';

    $DB = $this->db;
    $User = $this->user;
    $DeviceAuth = $this->deviceAuth;
    $Smarty = $this->smarty;
    $APPD = $this->appd;

    ob_start();
    include __DIR__ . '/../../view/page/activate.php';
    ob_end_clean();

    expect($this->smartyAssigned['success'] ?? null)->toBeTrue();
    expect(isset($_SESSION['csrf_token_device']))->toBeFalse();
});

test('activate.php assigns error when linkSessionToUnit fails', function () {
    $currentUser = ['id' => 5, 'name' => 'Velitel', 'email' => 'velitel@test.cz'];
    $this->user->method('getUser')->willReturn($currentUser);
    $this->user->method('getUnitsForUser')->willReturn([['id' => 10, 'fullname' => 'JSDH Brno']]);
    $this->appd->setData('DEVICE_CODE', 'VALID123');
    $this->deviceAuth->method('checkSessionStatus')->willReturn(['status' => 'pending', 'device_code' => 'VALID123']);

    $this->deviceAuth->expects($this->once())
        ->method('linkSessionToUnit')
        ->willReturn(false);

    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SESSION['csrf_token_device'] = 'valid_token';
    $_POST['csrf_token_device'] = 'valid_token';
    $_POST['unit_id'] = 10;

    $DB = $this->db;
    $User = $this->user;
    $DeviceAuth = $this->deviceAuth;
    $Smarty = $this->smarty;
    $APPD = $this->appd;

    ob_start();
    include __DIR__ . '/../../view/page/activate.php';
    ob_end_clean();

    expect($this->smartyAssigned['error'])->toContain('Nepodařilo se autorizovat zařízení');
});

