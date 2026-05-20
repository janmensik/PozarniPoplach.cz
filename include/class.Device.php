<?php

namespace PozarniPoplach;

use Janmensik\Jmlib\Modul;
use Janmensik\Jmlib\Database;

class Device extends Modul {
    protected $sql_base = 'SELECT SQL_CALC_FOUND_ROWS dev.id, dev.device_uuid, dev.device_name, dev.last_seen,  UNIX_TIMESTAMP(dev.last_seen) AS last_seen_ts, dev.created_at, UNIX_TIMESTAMP(dev.created_at) AS created_at_ts, dev.ad_probability, dev.ad_sticky_duration, ut.fullname AS unit_fullname FROM alarm_device_authorized dev JOIN unit ut ON dev.unit_id = ut.id WHERE 1 GROUP BY dev.id'; # zaklad SQL dotazu
    protected $sql_update = 'UPDATE alarm_device_authorized dev'; # zaklad SQL dotazu - UPDATE
    protected $sql_insert = 'INSERT INTO alarm_device_authorized dev'; # zaklad SQL dotazu - INSERT
    protected $sql_table = 'dev';
    protected $order = 2;

    //protected $fulltext_columns = array('vt.type', 'vt.code');
    protected $limit = -1;

    protected $elements = [
        'device_name',
        'ad_probability',
        'ad_sticky_duration'
    ];

    public $data = [];

    public $cache;

    # ...................................................................
    public function __construct(Database &$database) {
        parent::__construct($database);
    }

    # ...................................................................
    public function fillData(?int $id = null): bool {
        if (!$id) {
            return false;
        }

        $item = $this->getId($id);

        foreach ($this->elements as $el) {
            if (isset($item[$el])) {
                $this->data[$el] = @$item[$el];
            }
        }
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
            'device_name' => 'device_name',
            'ad_probability' => 'ad_probability',
            'ad_sticky_duration' => 'ad_sticky_duration'
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

        # device_name
        if (empty($this->data['device_name'])) {
            $errors['device_name'] = "Device name is required";
        }

        # ad_probability
        if (!empty($this->data['ad_probability']) && !is_numeric($this->data['ad_probability']) && ($this->data['ad_probability'] < 0 || $this->data['ad_probability'] > 100)) {
            $errors['ad_probability'] = "Ad probability must be a number 0-100";
        }

        # ad_sticky_duration
        if (!empty($this->data['ad_sticky_duration']) && !is_numeric($this->data['ad_sticky_duration']) && ($this->data['ad_sticky_duration'] < 0 || $this->data['ad_sticky_duration'] > 9999)) {
            $errors['ad_sticky_duration'] = "Ad sticky duration must be a number 0-9999";
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
