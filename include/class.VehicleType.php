<?php

namespace PozarniPoplach;

use Janmensik\Jmlib\Modul;
use Janmensik\Jmlib\Database;

class VehicleType extends Modul {
    protected ?string $sql_base = 'SELECT SQL_CALC_FOUND_ROWS vt.id, vt.type, vt.code, vt.icon FROM vehicle_type vt GROUP BY vt.id'; # zaklad SQL dotazu
    protected ?string $sql_update = 'UPDATE vehicle_type vt'; # zaklad SQL dotazu - UPDATE
    protected ?string $sql_insert = 'INSERT INTO vehicle_type vt'; # zaklad SQL dotazu - INSERT
    protected ?string $sql_table = 'vt';
    protected int|string $order = 3;

    //protected ?array $fulltext_columns = array('vt.type', 'vt.code');
    protected int $limit = -1;

    protected array $elements = [
        'type',
        'code',
        'icon'
    ];

    # ...................................................................
    public function __construct(Database &$database) {
        parent::__construct($database);
    }

    # ...................................................................
    public function validate(): array {
        $errors = [];

        # type
        if (empty($this->data['type'])) {
            $errors['type'] = "Type is required";
        }

        # code
        if (empty($this->data['code'])) {
            $errors['code'] = "Code is required";
        }

        return $errors;
    }
}
