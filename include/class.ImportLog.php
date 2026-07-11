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
}
