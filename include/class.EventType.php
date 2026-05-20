<?php

namespace PozarniPoplach;

use Janmensik\Jmlib\Modul;
use Janmensik\Jmlib\Database;

class EventType extends Modul {
    protected $sql_base = 'SELECT SQL_CALC_FOUND_ROWS et.id, et.name, et.icon, et.level, et.parent_id, etp.name as parent_name, etp.icon as parent_icon FROM event_type et LEFT JOIN event_type etp ON et.parent_id = etp.id GROUP BY et.id'; # zaklad SQL dotazu
    protected $sql_update = 'UPDATE event_type et'; # zaklad SQL dotazu - UPDATE
    protected $sql_insert = 'INSERT INTO event_type et'; # zaklad SQL dotazu - INSERT
    protected $sql_table = 'et';
    protected $order = 2;

    //protected $fulltext_columns = array('vt.type', 'vt.code');
    protected $limit = -1;

    protected $elements = [
        'name',
        'icon',
        'level',
        'parent_id'
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
            'name' => 'name',
            'icon' => 'icon',
            'level' => 'level',
            'parent_id' => 'parent_id'
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

        # name
        if (empty($this->data['name'])) {
            $errors['name'] = "Name is required";
        }

        # level
        if (empty($this->data['level'])) {
            $this->data['level'] = 1;
        } elseif (!is_numeric($this->data['level']) || $this->data['level'] < 1) {
            $errors['level'] = "Level must be a positive number or empty";
        }

        # parent_id
        if (empty($this->data['parent_id'])) {
            $this->data['parent_id'] = null;
        } elseif (!is_numeric($this->data['parent_id']) || $this->data['parent_id'] < 1) {
            $errors['parent_id'] = "Parent ID must be a positive number or empty";
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
