<?php

require_once __DIR__ . '/../../include/class.ImportLog.php';

use PozarniPoplach\ImportLog;
use Janmensik\Jmlib\Database;

uses(\Tests\TestCase::class);

beforeEach(function () {
    $this->db = $this->createMock(Database::class);
    $this->importLog = new ImportLog($this->db);
});

test('ImportLog instantiates correctly', function () {
    expect($this->importLog)->toBeInstanceOf(ImportLog::class);
});

test('ImportLog has expected SQL base', function () {
    $ref = new ReflectionProperty(ImportLog::class, 'sql_base');
    $sql = $ref->getValue($this->importLog);

    expect($sql)->toContain('FROM import_log il');
});
