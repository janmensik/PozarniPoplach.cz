<?php

namespace PozarniPoplach;

use Janmensik\Jmlib\Modul;
use Janmensik\Jmlib\Database;

class LoginHistory extends Modul
{
    protected ?string $sql_base = 'SELECT SQL_CALC_FOUND_ROWS u.id, UNIX_TIMESTAMP(upr.date) AS date, INET_NTOA(upr.ip) AS ip, u.name, u.email, u.note, u.status FROM user_login upr JOIN user u ON u.id = upr.user_id'; # zaklad SQL dotazu
    protected ?string $sql_update = 'UPDATE user_login upr'; # zaklad SQL dotazu - UPDATE
    protected ?string $sql_insert = 'INSERT INTO user_login'; # zaklad SQL dotazu - INSERT
    protected ?string $sql_table = 'upr';
    protected int|string $order = -2;
    protected ?array $fulltext_columns = array ('u.name', 'u.email', 'u.note', 'INET_NTOA(upr.ip)');

    public array $text = array(
        'cs' => array(
            'status' =>
            array('admin' => 'Administrátor', 'keeper_admin' => 'Správce', 'keeper_solver' => 'Pracovník', 'disabled' => 'Zmražený', 'deleted' => 'Smazaný')
        )
    );

    # ...................................................................
    public function __construct(Database &$database)
    {
        parent::__construct($database);
    }
}
