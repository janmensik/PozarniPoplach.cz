<?php

namespace PozarniPoplach;

use Janmensik\Jmlib\Modul;
use Janmensik\Jmlib\Database;

class ImportLog extends Modul {
    protected ?string $sql_base = 'SELECT SQL_CALC_FOUND_ROWS il.id, UNIX_TIMESTAMP(il.started_at) AS started_at_ts, UNIX_TIMESTAMP(il.finished_at) AS finished_at_ts, il.duration, il.emails_processed, il.dispatches_created, il.status FROM import_log il'; # zaklad SQL dotazu
    protected ?string $sql_update = 'UPDATE import_log il'; # zaklad SQL dotazu - UPDATE
    protected ?string $sql_insert = 'INSERT INTO import_log'; # zaklad SQL dotazu - INSERT
    protected ?string $sql_table = 'il';
    protected int|string $order = -2;
    protected ?array $fulltext_columns = array('il.status');

    # ...................................................................
    public function __construct(Database &$database) {
        parent::__construct($database);
    }

    public function getStats(): array {
        $row = $this->DB->getRow($this->DB->query("
            SELECT
                COUNT(*)                                    AS total_runs,
                SUM(IF(status = 'success', 1, 0))          AS success_runs,
                SUM(IF(status = 'error',   1, 0))          AS error_runs,
                IFNULL(SUM(emails_processed), 0)           AS emails_processed,
                IFNULL(SUM(dispatches_created), 0)         AS dispatches_created
            FROM import_log
        ", __METHOD__)) ?: [];

        return [
            'total_runs'        => (int)($row['total_runs']        ?? 0),
            'success_runs'      => (int)($row['success_runs']      ?? 0),
            'error_runs'        => (int)($row['error_runs']        ?? 0),
            'emails_processed'  => (int)($row['emails_processed']  ?? 0),
            'dispatches_created'=> (int)($row['dispatches_created']?? 0),
        ];
    }

    public function getDailyStats(int $days = 7): array {
        return $this->DB->getAllRows($this->DB->query("
            SELECT
                DATE(started_at)                            AS day,
                COUNT(*)                                    AS total_runs,
                SUM(IF(status = 'success', 1, 0))          AS success_runs,
                SUM(IF(status = 'error',   1, 0))          AS error_runs,
                IFNULL(SUM(emails_processed), 0)           AS emails_processed,
                IFNULL(SUM(dispatches_created), 0)         AS dispatches_created,
                ROUND(AVG(duration), 1)                    AS avg_duration,
                MAX(duration)                              AS max_duration
            FROM import_log
            WHERE started_at >= NOW() - INTERVAL " . intval($days) . " DAY
            GROUP BY DATE(started_at)
            ORDER BY day DESC
        ", __METHOD__)) ?: [];
    }
}
