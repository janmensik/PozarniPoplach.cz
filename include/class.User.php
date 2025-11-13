<?php
# ěščřžýáíéů
class User extends Modul {
    var $sql_base = 'SELECT SQL_CALC_FOUND_ROWS u.id, u.name, u.email, u.status, u.page_schema, u.password FROM user u GROUP BY u.id'; # zaklad SQL dotazu
    var $sql_update = 'UPDATE user u'; # zaklad SQL dotazu - UPDATE
    var $sql_insert = 'INSERT INTO user'; # zaklad SQL dotazu - INSERT
    var $sql_table = 'u';
    var $order = 2;
    var $user;
    var $CASBIN;
    var $page_schema = array('order' => 'p', 'status' => 'p', 'date_text' => 'g', 'date_range' => 'p', 'date_type' => 'p', 'currency' => 'g', 'history' => 'g', 'filter' => 'g', 'items_per_page' => 'g', 'q' => 'p', 'type' => 'p', 'stats' => 'p', 'newsletter' => 'p', 'important' => 'p', 'smart_status' => 'p'); # seznam co ukladam (hodnota 'g' pro globalni promenou, 'p' pro promenou pro stranku

    var $fulltext_columns = array('u.name', 'u.email', 'u.note');
    var $limit = -1;

    var $text = array(
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
    public function __construct(&$database, &$casbin) {
        $this->CASBIN = &$casbin;
        return (parent::__construct($database));
    }

    # ...................................................................
    # bool setAfterSession ( & object )
    # pokud nacitam z SESSION, musis si poslat odkazem databazi, jinak pracuji se separatni kopii!
    # zamyslet se nad mazanim cache
    /*
    function setAfterSession(&$database, &$casbin) {
        # globalni objekt pro praci s databazi
        $this->DB = &$database;

        # globalni objekt pro praci s rizenim pristupu
        $this->CASBIN = &$casbin;

        if (is_object($this->DB)) {
            $this->load();
            return (true);
        } else
            return (false);
    }
    */

    # ...................................................................
    function get($where = null, $order = null, $limit = null, $limit_from = null, $nocalcrows = false) {
        $data = parent::get($where, $order, $limit, $limit_from, $nocalcrows);

        # prepis hodnot statusu na CZ
        # string 2 array 4 page_schema
        if (is_array($data))
            foreach ($data as $key => $value)
                if (isset($value['page_schema']))
                    $data[$key]['page_schema'] = unserialize(stripslashes($value['page_schema']));


        return ($data);
    }

    # ...................................................................
    function getWithLastLogin($where = null, $order = null, $limit = null, $limit_from = null) {
        $temp = $this->sql_base;

        $this->sql_base = str_replace('FROM user u', ', temp1.last_login, temp1.ip FROM user u', $this->sql_base);
        $this->sql_base = str_replace('GROUP BY u.id', 'LEFT JOIN (SELECT UNIX_TIMESTAMP(MAX(upr.date)) AS last_login, upr.user_id, INET_NTOA(upr.ip) AS ip FROM user_login upr GROUP BY upr.user_id) temp1 ON temp1.user_id=u.id GROUP BY u.id', $this->sql_base);

        $data = $this->get($where, $order, $limit, $limit_from);

        $this->sql_base = $temp;

        return ($data);
    }

    # ...................................................................
    # vrati normalne + vsechny weby (ne deleted), atd.
    function getComplete($where = null, $order = null, $limit = null, $limit_from = null) {
        return ($this->get($where, $order, $limit, $limit_from));
    }

    # ...................................................................
    function hasPermission($page = null, $action = null) {
        if (!isset($this->user['status']))
            return false;

        if (!isset($page) || !isset($action))
            return false;

        return ($this->CASBIN->enforce($this->getUser('status'), $page, $action));
    }

    # ...................................................................
    function generatePassword($length = 8) {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }

    # ...................................................................
    function getPasswordHash($password = null) {
        return (sha1($password));
    }

    # ...................................................................
    function verify($user = null, $password = null) {
        $user = $this->getComplete(array($this->sql_table . '.email = "' . mysqli_real_escape_string($this->DB->db, $user) . '"', $this->sql_table . '.status NOT IN( "deleted","disabled")'), null, 1);


        if (is_array($user))
            $this->user = reset($user);
        else
            return (null);

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
    function verifyPermanent($hash) {
        $user = $this->getComplete(array('SHA1(CONCAT(' . $this->sql_table . '.id, ' . $this->sql_table . '.email)) = "' . mysqli_real_escape_string($this->DB->db, $hash) . '"', $this->sql_table . '.status NOT IN("deleted","disabled")'), null, 1);

        if (is_array($user))
            $this->user = reset($user);
        else
            return (null);

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
    function getPermanentHash($user_id = null) {
        if (!$user_id)
            return (false);

        $user = null;
        $user = $this->getId($user_id);

        if (is_array($user))
            return (sha1($user['id'] . $user['email']));
        else
            return (null);
    }

    # ...................................................................
    function updateLastLogin($id = null, $ip = null) {
        # aktualizace posledniho prihlaseni
        $this->DB->query('INSERT INTO user_login (user_id, date, ip) VALUES (' . ((int) $id ? (int) $id : $this->user['id']) . ', NOW(), INET_ATON("' . ($ip ? $ip : getip()) . '"));');

        return (true);
    }

    # ...................................................................
    function load($user_id = null) {
        unset($this->cache);

        $user = $this->getComplete(array($this->sql_table . '.id = "' . ($user_id ? $user_id : $this->user['id']) . '"'), null, 1);
        if (is_array($user))
            $this->user = reset($user);
        else
            unset($this->user);

        return ($this->user);
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
    function getUser($what = null) {
        if (isset($this->user) && count($this->user) == 1 && $this->user['page_schema'])
            return (null);

        if ($what)
            return ($this->user[$what]);
        else
            return ($this->user);
    }

    # ...................................................................
    function logout() {
        unset($this->user);
        return (true);
    }

    # ...................................................................
    function setPageSchema($page = null, $data = null) {
        if (!$page)
            return (false);

        foreach ($this->page_schema as $value => $type) {

            if (isset($data[$value])) {
                # globalni
                if ($type == 'g')
                    $this->user['page_schema']['global'][$value] = $data[$value];
                # lokalni (pages) 'p'
                else
                    $this->user['page_schema']['pages'][$page][$value] = $data[$value];
                $save2sql = true;
            } else {
                if ($type == 'g' && isset($this->user['page_schema']['global'][$value]))
                    $data[$value] = $this->user['page_schema']['global'][$value];
                elseif ($type == 'p' && isset($page) && isset($this->user['page_schema']['pages'][$page][$value]))
                    $data[$value] = $this->user['page_schema']['pages'][$page][$value];

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
    function clearPageSchema($user_id = null) {
        if ((int) $user_id && !$this->user['id'])
            return (false);

        $this->set(array('page_schema' => 'null'), ((int) $user_id ? (int) $user_id : $this->user['id']));

        return (true);
    }

    # ...................................................................
    function getPageSchema($page = null) {
        if (!$page)
            return (false);

        if (is_array($this->user['page_schema']['global']) && is_array($this->user['page_schema']['pages'])) {
            if (is_array($this->user['page_schema']['pages'][$page]))
                return (array_merge($this->user['page_schema']['pages'][$page], $this->user['page_schema']['global']));
            else
                return ($this->user['page_schema']['global']);
        } elseif (is_array($this->user['page_schema']['pages'][$page]))
            return ($this->user['page_schema']['pages'][$page]);
        elseif (is_array($this->user['page_schema']['global']))
            return ($this->user['page_schema']['global']);
        else
            return (null);
    }
}
