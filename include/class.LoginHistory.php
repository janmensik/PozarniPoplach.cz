<?
class LoginHistory extends Modul {
	var $sqlbase = 'SELECT SQL_CALC_FOUND_ROWS u.id, UNIX_TIMESTAMP(upr.date) AS date, INET_NTOA(upr.ip) AS ip, u.name, u.email, u.note, u.status FROM userlogin upr JOIN user u ON u.id = upr.user_id'; # zaklad SQL dotazu
	var $sqlupdate = 'UPDATE userlogin upr'; # zaklad SQL dotazu - UPDATE
	var $sqlinsert = 'INSERT INTO userlogin'; # zaklad SQL dotazu - INSERT
	var $sqltable = 'upr';
	var $order = -2;
	var $fulltextcolumns = array ('u.name', 'u.email', 'u.note', 'INET_NTOA(upr.ip)');

	//var $limit = 20;
		
	# ...................................................................
	# KONSTRUKTOR
	function LoginHistory (& $database) {
		return ($this->Modul ($database));
		}
	

	# ...................................................................
	function get ($where = null, $order = null, $limit = null, $limit_from = null) {
		$data = parent::get ($where, $order, $limit, $limit_from);
		
		# prepis hodnot statusu na CZ
		$statusy = array ('admin' => 'administrátor', 'user' => 'hotel manažer', 'disabled' => 'zablokován', 'deleted' => 'smazán');
		if (is_array ($data))
			foreach ($data as $key=>$value) {
				if ($statusy[$value['status']])
					$data[$key]['status_cs'] = $statusy[$value['status']];
				else
					$data[$key]['status_cs'] = $data[$key]['status'];
				}
		
		return ($data);
		}
	}
?>