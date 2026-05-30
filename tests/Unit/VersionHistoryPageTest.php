<?php

use Janmensik\Jmlib\AppData;
use PozarniPoplach\Version;

uses(\Tests\TestCase::class);

beforeEach(function () {
    // Mock AppData
    $this->appd = AppData::getInstance();

    // Mock User
    $this->user = $this->createMock(\PozarniPoplach\User::class);
    $this->user->method('hasPermission')->willReturn(true);

    // Mock Smarty
    $this->smarty = $this->createMock(\Smarty\Smarty::class);

    // Make them global
    $GLOBALS['User'] = $this->user;
    $GLOBALS['Smarty'] = $this->smarty;
    $GLOBALS['APPD'] = $this->appd;
});

test('version-history.php assigns data to smarty', function () {
    $this->smarty->expects($this->once())
                 ->method('assign');

    $versionMock = $this->createMock(Version::class);
    $versionMock->versions = [];

    // Set local variables
    $Version = $versionMock;
    $User = $this->user;
    $Smarty = $this->smarty;
    $APPD = $this->appd;

    ob_start();
    include __DIR__ . '/../../view/page/version-history.php';
    ob_end_clean();
    
    expect($APPD->getData('PAGE'))->toBe('version-history');
});
