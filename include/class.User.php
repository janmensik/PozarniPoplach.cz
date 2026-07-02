<?php

namespace PozarniPoplach;

use Janmensik\Jmlib\Modul;
use Janmensik\Jmlib\Database;
use Casbin\Enforcer;

class User extends Modul {
    protected ?string $sql_base = 'SELECT SQL_CALC_FOUND_ROWS u.id, u.name, u.email, u.status, u.page_schema, u.password FROM user u GROUP BY u.id'; # zaklad SQL dotazu
    protected ?string $sql_update = 'UPDATE user u'; # zaklad SQL dotazu - UPDATE
    protected ?string $sql_insert = 'INSERT INTO user'; # zaklad SQL dotazu - INSERT
    protected ?string $sql_table = 'u';
    protected int|string $order = 2;
    protected $user = array();
    protected ?Enforcer $CASBIN = null;
    protected $page_schema = array('order' => 'p', 'status' => 'p', 'date_text' => 'g', 'date_range' => 'p', 'date_type' => 'p', 'currency' => 'g', 'history' => 'g', 'filter' => 'g', 'items_per_page' => 'g', 'q' => 'p', 'type' => 'p', 'stats' => 'p', 'newsletter' => 'p', 'important' => 'p', 'smart_status' => 'p'); # seznam co ukladam (hodnota 'g' pro globalni promenou, 'p' pro promenou pro stranku

    protected ?array $fulltext_columns = array('u.name', 'u.email', 'u.note');
    protected int $limit = -1;

    protected array $elements = [
        'name',
        'email',
        'note',
        'status',
        'password'
    ];

    public array $text = array(
        'cs' => array(
            'status' =>
            array('admin' => 'Administrátor', 'manager' => 'Správce', 'partner' => 'Partner', 'driver' => 'Řidič', 'disabled' => 'Zmražený', 'deleted' => 'Smazaný')
        ),
        'color' => array(
            'status' =>
            array(
                'admin' => 'warning',
                'manager' => 'secondary',
                'partner' => 'primary',
                'driver' => 'secondary',
                'disabled' => 'dark',
                'deleted' => 'light'
            )
        )
    );

    # ...................................................................
    # KONSTRUKTOR
    public function __construct(Database &$database, ?Enforcer &$casbin) {
        $this->CASBIN = &$casbin;
        return (parent::__construct($database));
    }

    # ...................................................................
    public function getWithLastLogin(array|string|null $where = null, string|null $order = null, int|null $limit = null, int|null $limit_from = null): array|bool|null {
        $temp = $this->sql_base;

        $this->sql_base = str_replace('FROM user u', ', temp1.last_login, temp1.ip FROM user u', $this->sql_base);
        $this->sql_base = str_replace('GROUP BY u.id', 'LEFT JOIN (SELECT UNIX_TIMESTAMP(MAX(upr.date)) AS last_login, upr.user_id, INET_NTOA(upr.ip) AS ip FROM user_login upr GROUP BY upr.user_id) temp1 ON temp1.user_id=u.id GROUP BY u.id', $this->sql_base);

        $data = $this->get($where, $order, $limit, $limit_from);

        $this->sql_base = $temp;

        return ($data);
    }

    # ...................................................................
    # vrati normalne + vsechny weby (ne deleted), atd.
    public function getComplete(array|string|null $where = null, string|null $order = null, int|null $limit = null, int|null $limit_from = null): array|bool|null {
        return ($this->get($where, $order, $limit, $limit_from));
    }

    # ...................................................................
    public function hasPermission(string|null $page = null, string|null $action = null): bool {
        if (!isset($this->user['status'])) {
            return false;
        }

        if (!isset($page) || !isset($action)) {
            return false;
        }

        return ($this->CASBIN->enforce($this->getUser('status'), $page, $action));
    }

    # ...................................................................
    public function generatePassword(?int $length = 8): string {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, strlen($characters) - 1)];
        }
        return $randomString;
    }

    # ...................................................................
    public function getPasswordHash(?string $password = null): string {
        return (sha1($password));
    }

    # ...................................................................
    public function verify(?string $user = null, ?string $password = null): array|bool|null {
        $user = $this->getComplete(array($this->sql_table . '.email = "' . mysqli_real_escape_string($this->DB->db, $user) . '"', $this->sql_table . '.status NOT IN( "deleted","disabled")'), null, 1);


        if (is_array($user)) {
            $this->user = reset($user);
            $this->processUser();
        } else {
            return (null);
        }

        if ($this->getPasswordHash($password) != $this->user['password']) {
            unset($this->user);
            return (false);
        }

        # overeni, pak predelat
        if ($this->user['status'] != 'deleted') {
            # aktualizace posledniho prihlaseni
            $this->updateLastLogin();

            return ($this->user['id']);
        } else {
            unset($this->user);
            return (false);
        }
    }

    # ...................................................................
    public function verifyPermanent(?string $hash = null): int|bool|null {
        $user = $this->getComplete(array('SHA1(CONCAT(' . $this->sql_table . '.id, ' . $this->sql_table . '.email, ' . $this->sql_table . '.password)) = "' . mysqli_real_escape_string($this->DB->db, $hash) . '"', $this->sql_table . '.status NOT IN("deleted","disabled")'), null, 1);

        if (is_array($user)) {
            $this->user = reset($user);
            $this->processUser();
        } else {
            return (null);
        }

        # overeni, pak predelat
        if ($this->user['status'] != 'deleted') {
            # aktualizace posledniho prihlaseni
            $this->updateLastLogin();

            return ($this->user['id']);
        } else {
            unset($this->user);
            return (false);
        }
    }

    # ...................................................................
    public function getPermanentHash(?int $user_id = null): string|bool|null {
        if (!$user_id) {
            return (false);
        }

        $user = null;
        $user = $this->getId($user_id);

        if (is_array($user)) {
            return (sha1($user['id'] . $user['email'] . $user['password']));
        } else {
            return (null);
        }
    }

    # ...................................................................
    public function updateLastLogin(?int $id = null, ?string $ip = null): bool {
        # aktualizace posledniho prihlaseni
        $ipToUse = $ip ? $ip : getip();

        // Ensure the IP address is strictly valid before passing to INET_ATON
        if (!filter_var($ipToUse, FILTER_VALIDATE_IP)) {
            $ipToUse = '127.0.0.1'; // Fallback for safety
        }

        $this->DB->query('INSERT INTO user_login (user_id, date, ip) VALUES (' . ((int) $id ? (int) $id : $this->user['id']) . ', NOW(), INET_ATON("' . $ipToUse . '"));');

        return (true);
    }

    # ...................................................................
    public function load(?int $user_id = null): array|bool|null {
        unset($this->cache);

        $user = $this->getComplete(array($this->sql_table . '.id = "' . ($user_id ? $user_id : $this->user['id']) . '"'), null, 1);
        if (is_array($user)) {
            $this->user = reset($user);
            $this->processUser();
        } else {
            unset($this->user);
        }

        return ($this->user);
    }

    # ...................................................................
    private function processUser(): void {
        if (isset($this->user['page_schema']) && is_string($this->user['page_schema'])) {
            $this->user['page_schema'] = @unserialize(stripslashes($this->user['page_schema']), ['allowed_classes' => false]);
        }
        if (!isset($this->user['page_schema']) || !is_array($this->user['page_schema'])) {
            $this->user['page_schema'] = array('global' => array(), 'pages' => array());
        }
    }

    # ...................................................................
    public function validate(?int $id = null): array {
        $errors = [];

        # name
        if (empty($this->data['name'])) {
            $errors['name'] = 'empty';
        }

        # email
        if (empty($this->data['email'])) {
            $errors['email'] = 'empty';
        }

        # status
        if (empty($this->data['status']) || !isset($this->text['cs']['status'][$this->data['status']])) {
            $errors['status'] = 'wrong';
        }

        return $errors;
    }

    # ...................................................................
    public function set(array|false|null $set = null, array|int|null $ids = null, string|null $special = null): int|false {
        # vycistim cache
        unset($this->cache);


        # ulozeni normalnich udaju
        $temp = parent::set($set, $ids, $special);
        $ids = $temp ? $temp : $ids;

        $this->load();

        return ($ids);
    }

    # ...................................................................
    public function getUser(?string $what = null): array|bool|null {
        if (!isset($this->user)) {
            return ($what ? null : []);
        }

        if (isset($this->user) && count($this->user) == 1 && $this->user['page_schema']) {
            return (null);
        }

        if ($what) {
            return ($this->user[$what]);
        } else {
            return ($this->user);
        }
    }

    # ...................................................................
    public function logout(): bool {
        unset($this->user);
        return (true);
    }

    # ...................................................................
    public function setPageSchema(?string $page = null, ?array $data = null): array|bool|null {
        if (!$page) {
            return (false);
        }

        # zajisteni pole
        if (!isset($this->user['page_schema']) || !is_array($this->user['page_schema'])) {
            $this->processUser();
        }

        foreach ($this->page_schema as $value => $type) {
            if (isset($data[$value])) {
                # globalni
                if ($type == 'g') {
                    $this->user['page_schema']['global'][$value] = $data[$value];
                } else {
                    # lokalni (pages) 'p'
                    $this->user['page_schema']['pages'][$page][$value] = $data[$value];
                }
                $save2sql = true;
            } else {
                if ($type == 'g' && isset($this->user['page_schema']['global'][$value])) {
                    $data[$value] = $this->user['page_schema']['global'][$value];
                } elseif ($type == 'p' && isset($page) && isset($this->user['page_schema']['pages'][$page][$value])) {
                    $data[$value] = $this->user['page_schema']['pages'][$page][$value];
                }

                //$data[$value] = ($type == 'g' ? $this->user['page_schema']['global'][$value] : $this->user['page_schema']['pages'][$page][$value]);
            }
        }
        # ulozeni do sql
        if ($this->user['id'] && isset($save2sql)) {
            $this->set(array('page_schema' => '"' . addslashes(serialize($this->user['page_schema'])) . '"'), $this->user['id']);
        }

        return ($data);
    }

    # ...................................................................
    public function clearPageSchema(?int $user_id = null): bool {
        if ((int) $user_id && !$this->user['id']) {
            return (false);
        }

        $this->set(array('page_schema' => 'null'), ((int) $user_id ? (int) $user_id : $this->user['id']));

        return (true);
    }

    # ...................................................................
    public function getPageSchema(?string $page = null): array|bool|null {
        if (!$page) {
            return (false);
        }

        # zajisteni pole
        if (!isset($this->user['page_schema']) || !is_array($this->user['page_schema'])) {
            $this->processUser();
        }

        if (is_array($this->user['page_schema']['global']) && is_array($this->user['page_schema']['pages'])) {
            if (is_array($this->user['page_schema']['pages'][$page])) {
                return (array_merge($this->user['page_schema']['pages'][$page], $this->user['page_schema']['global']));
            } else {
                return ($this->user['page_schema']['global']);
            }
        } elseif (is_array($this->user['page_schema']['pages'][$page])) {
            return ($this->user['page_schema']['pages'][$page]);
        } elseif (is_array($this->user['page_schema']['global'])) {
            return ($this->user['page_schema']['global']);
        } else {
            return (null);
        }
    }
}
