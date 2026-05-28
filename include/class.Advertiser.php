<?php

namespace PozarniPoplach;

use Janmensik\Jmlib\Modul;
use Janmensik\Jmlib\Database;

class Advertiser extends Modul {
    protected ?string $sql_base = 'SELECT SQL_CALC_FOUND_ROWS adc.id, adc.name, adc.contact_email, adc.created_at, UNIX_TIMESTAMP(adc.created_at) AS created_at_ts, COUNT(advert.id) AS advert_count, COUNT(IF(advert.status = "active", 1, NULL)) AS active_advert_count FROM advertiser adc LEFT JOIN advert ON adc.id = advert.advertiser_id GROUP BY adc.id'; # zaklad SQL dotazu
    protected ?string $sql_update = 'UPDATE advertiser adc'; # zaklad SQL dotazu - UPDATE
    protected ?string $sql_insert = 'INSERT INTO advertiser adc'; # zaklad SQL dotazu - INSERT
    protected ?string $sql_table = 'adc';
    protected int|string $order = 2;

    //protected ?array $fulltext_columns = array('vt.type', 'vt.code');
    protected int $limit = -1;

    protected array $elements = [
        'name',
        'contact_email'
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

        # contact_email
        if (empty($this->data['contact_email'])) {
            $errors['contact_email'] = "Contact email is required";
        }

        return $errors;
    }
}
