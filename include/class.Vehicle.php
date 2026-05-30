<?php

namespace PozarniPoplach;

use Janmensik\Jmlib\Modul;
use Janmensik\Jmlib\Database;

class Vehicle extends Modul {
    protected ?string $sql_base = 'SELECT SQL_CALC_FOUND_ROWS uv.id, uv.unit_id, uv.vehicle_type_id, uv.callsign, uv.name, ut.fullname AS unit_fullname, vt.type AS vehicle_type, vt.code AS vehicle_type_code, vt.icon AS vehicle_type_icon FROM unit_vehicle uv JOIN unit ut ON uv.unit_id = ut.id JOIN vehicle_type vt ON uv.vehicle_type_id = vt.id WHERE 1 GROUP BY uv.id'; # zaklad SQL dotazu
    protected ?string $sql_update = 'UPDATE unit_vehicle uv'; # zaklad SQL dotazu - UPDATE
    protected ?string $sql_insert = 'INSERT INTO unit_vehicle uv'; # zaklad SQL dotazu - INSERT
    protected ?string $sql_table = 'uv';
    protected int|string $order = 4;

    //protected ?array $fulltext_columns = array('vt.type', 'vt.code');
    protected int $limit = -1;

    protected array $elements = [
        'unit_id',
        'vehicle_type_id',
        'callsign',
        'name'
    ];

    # ...................................................................
    public function __construct(Database &$database) {
        parent::__construct($database);
    }

    # ...................................................................
    public function validate(): array {
        $errors = [];

        # callsign
        if (empty($this->data['callsign'])) {
            $errors['callsign'] = "Callsign is required";
        }

        # name
        if (empty($this->data['name'])) {
            $errors['name'] = "Name is required";
        }

        # vehicle_type_id
        if (empty($this->data['vehicle_type_id'])) {
            $errors['vehicle_type_id'] = "Vehicle type is required";
        }

        # unit_id
        if (empty($this->data['unit_id'])) {
            $errors['unit_id'] = "Unit is required";
        }

        return $errors;
    }

    # ...................................................................
    public function delete(int $id): bool {
        if ($this->DB->query('DELETE FROM unit_vehicles WHERE id = "' . (int) $id . '";')) {
            return true;
        }
        return false;
    }
}
