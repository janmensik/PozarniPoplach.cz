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

test('getDailyStats executes correct query with default days and returns stats', function () {
    $expectedQueryReturn = 'mock_query_result';
    $expectedRows = [
        [
            'day' => '2023-10-10',
            'total_runs' => 10,
            'success_runs' => 8,
            'error_runs' => 2,
            'emails_processed' => 100,
            'dispatches_created' => 5,
            'avg_duration' => 12.5,
            'max_duration' => 30
        ]
    ];

    $this->db->expects($this->once())
        ->method('query')
        ->with(
            $this->stringContains('INTERVAL 7 DAY'),
            $this->stringContains('getDailyStats')
        )
        ->willReturn($expectedQueryReturn);

    $this->db->expects($this->once())
        ->method('getAllRows')
        ->with($expectedQueryReturn)
        ->willReturn($expectedRows);

    $result = $this->importLog->getDailyStats();

    expect($result)->toBe($expectedRows);
});

test('getDailyStats executes correct query with custom days parameter', function () {
    $expectedQueryReturn = 'mock_query_result_14_days';
    $expectedRows = [
        [
            'day' => '2023-10-10',
            'total_runs' => 15
        ]
    ];

    $this->db->expects($this->once())
        ->method('query')
        ->with(
            $this->stringContains('INTERVAL 14 DAY'),
            $this->stringContains('getDailyStats')
        )
        ->willReturn($expectedQueryReturn);

    $this->db->expects($this->once())
        ->method('getAllRows')
        ->with($expectedQueryReturn)
        ->willReturn($expectedRows);

    $result = $this->importLog->getDailyStats(14);

    expect($result)->toBe($expectedRows);
});

test('getDailyStats returns empty array when no rows found', function () {
    $expectedQueryReturn = 'mock_empty_query_result';

    $this->db->expects($this->once())
        ->method('query')
        ->willReturn($expectedQueryReturn);

    $this->db->expects($this->once())
        ->method('getAllRows')
        ->with($expectedQueryReturn)
        ->willReturn(null);

    $result = $this->importLog->getDailyStats();

    expect($result)->toBeArray()
        ->toBeEmpty();
});
