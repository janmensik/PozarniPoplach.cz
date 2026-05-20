<?php

namespace PozarniPoplach;

use Janmensik\Jmlib\Modul;
use Janmensik\Jmlib\Database;

class VehicleType extends Modul {
    protected $sql_base = 'SELECT SQL_CALC_FOUND_ROWS vt.id, vt.type, vt.code, vt.icon FROM vehicle_type vt GROUP BY vt.id'; # zaklad SQL dotazu
    protected $sql_update = 'UPDATE vehicle_type vt'; # zaklad SQL dotazu - UPDATE
    protected $sql_insert = 'INSERT INTO vehicle_type vt'; # zaklad SQL dotazu - INSERT
    protected $sql_table = 'vt';
    protected $order = 3;

    //protected $fulltext_columns = array('vt.type', 'vt.code');
    protected $limit = -1;

    protected $elements = [
        'type',
        'code',
        'icon'
    ];

    public $data = [];

    public $cache;

    # ...................................................................
    public function __construct(Database &$database) {
        parent::__construct($database);
    }

    # ...................................................................
    public function fillData(?int $id = null): bool {
        if (!$id)
            return false;

        $item = $this->getId($id);

        foreach ($this->elements as $el)
            if (isset($item[$el]))
                $this->data[$el] = @$item[$el];

        return true;
    }

    # ...................................................................
    /**
     * Map POST data to internal data array
     * @param array $post $_POST data
     * @param array $customMap Optional custom mapping [postKey => dbKey]
     */
    public function mapFromPost(array $post, ?array $customMap = []): void {
        $map = !empty($customMap) ? $customMap : [
            'type' => 'type',
            'code' => 'code',
            'icon' => 'icon'
        ];

        foreach ($map as $postKey => $dbKey) {
            if (isset($post[$postKey])) {
                $this->data[$dbKey] = $this->sanitize($post[$postKey]);
            }
        }
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

    # ...................................................................
    # include all this->data into classic Modul set($set)
    public function setter(?int $id = null): bool|int {
        $set = [];
        foreach ($this->elements as $el) {
            if (isset($this->data[$el])) {
                $value = $this->data[$el];
                if ($value === null) {
                    $set[$el] = 'NULL';
                } else {
                    $set[$el] = '"' . mysqli_real_escape_string($this->DB->db, $value) . '"';
                }
            }
        }

        return ($this->set($set, $id));
    }
}
