<?php

namespace PozarniPoplach;

use Janmensik\Jmlib\Modul;
use Janmensik\Jmlib\Database;

class Advertiser extends Modul {
    protected $sql_base = 'SELECT SQL_CALC_FOUND_ROWS adc.id, adc.name, adc.contact_email, adc.created_at, UNIX_TIMESTAMP(adc.created_at) AS created_at_ts FROM advertiser adc GROUP BY adc.id'; # zaklad SQL dotazu
    protected $sql_update = 'UPDATE advertiser adc'; # zaklad SQL dotazu - UPDATE
    protected $sql_insert = 'INSERT INTO advertiser adc'; # zaklad SQL dotazu - INSERT
    protected $sql_table = 'adc';
    protected $order = 2;

    //protected $fulltext_columns = array('vt.type', 'vt.code');
    protected $limit = -1;

    protected $elements = [
        'name',
        'contact_email'
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
            'contact_email' => 'contact_email'
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

        # contact_email
        if (empty($this->data['contact_email'])) {
            $errors['contact_email'] = "Contact email is required";
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
