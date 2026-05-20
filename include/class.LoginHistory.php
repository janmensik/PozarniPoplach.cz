<?php

namespace PozarniPoplach;

use Janmensik\Jmlib\Modul;
use Janmensik\Jmlib\Database;

class LoginHistory extends Modul {
    protected $sql_base = 'SELECT SQL_CALC_FOUND_ROWS u.id, UNIX_TIMESTAMP(upr.date) AS date, INET_NTOA(upr.ip) AS ip, u.name, u.email, u.note, u.status FROM user_login upr JOIN user u ON u.id = upr.user_id'; # zaklad SQL dotazu
    protected $sql_update = 'UPDATE user_login upr'; # zaklad SQL dotazu - UPDATE
    protected $sql_insert = 'INSERT INTO user_login'; # zaklad SQL dotazu - INSERT
    protected $sql_table = 'upr';
    protected $order = -2;
    protected $fulltext_columns = array ('u.name', 'u.email', 'u.note', 'INET_NTOA(upr.ip)');

    public $text = array(
        'cs' => array(
            'status' =>
            array('admin' => 'Administrátor', 'keeper_admin' => 'Správce', 'keeper_solver' => 'Pracovník', 'disabled' => 'Zmražený', 'deleted' => 'Smazaný')
        )
    );

    # ...................................................................
    public function __construct(Database &$database) {
        parent::__construct($database);
    }
}
