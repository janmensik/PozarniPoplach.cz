<?php

require_once __DIR__ . '/../../include/class.Device.php';

use PozarniPoplach\Device;
use Janmensik\Jmlib\Database;

test('Device class exists', function () {
    expect(class_exists('PozarniPoplach\Device'))->toBeTrue();
});
