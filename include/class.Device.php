<?php

namespace PozarniPoplach;

use Janmensik\Jmlib\Modul;
use Janmensik\Jmlib\Database;

class Device extends Modul {
    protected ?string $sql_base = 'SELECT SQL_CALC_FOUND_ROWS dev.id, dev.device_uuid, dev.device_name, dev.last_seen,  UNIX_TIMESTAMP(dev.last_seen) AS last_seen_ts, dev.created_at, UNIX_TIMESTAMP(dev.created_at) AS created_at_ts, dev.ad_probability, dev.ad_sticky_duration, ut.fullname AS unit_fullname, ut.calendar_url AS unit_calendar_url, dev.calendar_show FROM alarm_device_authorized dev JOIN unit ut ON dev.unit_id = ut.id WHERE 1 GROUP BY dev.id'; # zaklad SQL dotazu
    protected ?string $sql_update = 'UPDATE alarm_device_authorized dev'; # zaklad SQL dotazu - UPDATE
    protected ?string $sql_insert = 'INSERT INTO alarm_device_authorized dev'; # zaklad SQL dotazu - INSERT
    protected ?string $sql_table = 'dev';
    protected int|string $order = 2;

    //protected ?array $fulltext_columns = array('vt.type', 'vt.code');
    protected int $limit = -1;

    protected array $elements = [
        'device_name',
        'ad_probability',
        'ad_sticky_duration',
        'calendar_show',
    ];

    # ...................................................................
    public function __construct(Database &$database) {
        parent::__construct($database);
    }

    # ...................................................................
    public function validate(): array {
        $errors = [];

        # device_name
        if (empty($this->data['device_name'])) {
            $errors['device_name'] = "Device name is required";
        }

        # ad_probability
        if (!empty($this->data['ad_probability']) && (!is_numeric($this->data['ad_probability']) || $this->data['ad_probability'] < 0 || $this->data['ad_probability'] > 100)) {
            $errors['ad_probability'] = "Ad probability must be a number 0-100";
        }

        # ad_sticky_duration
        if (!empty($this->data['ad_sticky_duration']) && (!is_numeric($this->data['ad_sticky_duration']) || $this->data['ad_sticky_duration'] < 0 || $this->data['ad_sticky_duration'] > 9999)) {
            $errors['ad_sticky_duration'] = "Ad sticky duration must be a number 0-9999";
        }

        # calendar_show
        if (!empty($this->data['calendar_show']) && !in_array((int) $this->data['calendar_show'], [0, 1], true)) {
            $errors['calendar_show'] = "Calendar show must be 0 or 1 (false or true) - ";
        }

        return $errors;
    }

    # ...................................................................
    public function delete(int $id): bool {
        if ($this->DB->query('DELETE FROM alarm_device_authorized WHERE id = "' . (int) $id . '";')) {
            return true;
        }
        return false;
    }
}
