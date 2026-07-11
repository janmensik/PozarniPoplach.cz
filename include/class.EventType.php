<?php

namespace PozarniPoplach;

use Janmensik\Jmlib\Modul;
use Janmensik\Jmlib\Database;

class EventType extends Modul {
    protected ?string $sql_base = 'SELECT SQL_CALC_FOUND_ROWS et.id, et.name, et.icon, et.level, et.parent_id, etp.name as parent_name, etp.icon as parent_icon FROM event_type et LEFT JOIN event_type etp ON et.parent_id = etp.id GROUP BY et.id'; # zaklad SQL dotazu
    protected ?string $sql_update = 'UPDATE event_type et'; # zaklad SQL dotazu - UPDATE
    protected ?string $sql_insert = 'INSERT INTO event_type et'; # zaklad SQL dotazu - INSERT
    protected ?string $sql_table = 'et';
    protected int|string $order = 2;

    //protected ?array $fulltext_columns = array('vt.type', 'vt.code');
    protected int $limit = -1;

    protected array $elements = [
        'name',
        'icon',
        'level',
        'parent_id'
    ];

    # ...................................................................
    public function __construct(Database &$database) {
        parent::__construct($database);
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

    public function getMissingIcons(): array {
        return $this->DB->getAllRows($this->DB->query("
            SELECT id, name, icon FROM event_type WHERE icon IS NULL OR icon = ''
        ", __METHOD__)) ?: [];
    }
}
