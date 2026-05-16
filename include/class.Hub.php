<?php
# ěščřžýáíéů
# PINCODE CHARS: A, C, D, E, F, H, J, K, L, M, N, P, Q, R, T, U, V, W, X, Y, Z, 2, 3, 4, 7, 9

class Hub extends Modul {
	var $sql_base = 'SELECT SQL_CALC_FOUND_ROWS hub.id, hub.title, hub.status, hub.pincode, hub.street, hub.city, hub.latitude, hub.longitude, hub.description, hub.collection, kpr.title AS keeper_title, rgn.title AS region_title, hub.keeper_id, COUNT(rpt.id) AS report_count, SUM(IF(rpt.status="new",1,0)) AS report_new, SUM(IF(rpt.status NOT IN ("closed","rejected"),1,0)) AS report_active, UNIX_TIMESTAMP(hub.last_update) AS last_update FROM hub JOIN keeper kpr ON hub.keeper_id=kpr.id JOIN region rgn ON kpr.region_id=rgn.id LEFT JOIN report rpt ON hub.id=rpt.hub_id GROUP BY hub.id'; # zaklad SQL dotazu
	var $sql_update = 'UPDATE hub'; # zaklad SQL dotazu - UPDATE
	var $sql_insert = 'INSERT INTO hub'; # zaklad SQL dotazu - INSERT
	var $sql_table = 'hub';
	var $order = -6;

	var $fulltext_columns = array('hub.id', 'hub.title', 'hub.pincode');
	var $limit = -1;

	var $text = array(
		'cs' => array(
			'status' =>
			array(
				'ok' => 'V provozu',
				'closed' => 'Uzavřené',
				'hidden' => 'Skryté',
				'abolished' => 'Zrušené'
			)
		)
	);

	private $elements = array(
		'keeper_id',
		'status',
		'pincode',
		'title',
		'street',
		'city',
		'latitude',
		'longitude',
		'description',
		'collection'
	);

	private $pincode_length = 5;
	private $pincode_chars = array('A', 'C', 'D', 'E', 'F', 'H', 'J', 'K', 'L', 'M', 'N', 'P', 'Q', 'R', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 2, 3, 4, 7, 9);

	public $data;

	# ...................................................................
	# KONSTRUKTOR
	public function __construct(&$database) {
		return ($this->Modul($database));
	}

	# ...................................................................
	# vrati definovane agregovane vysledky, tedy soucet, prumer, pocet atd vsech vysledku bez ohledu na limit
	# bere se podle posledniho get!!!
	public function getGroupTotal($where = null) {
		# sub dotaz dle posledniho get!!!
		$sub_sql = str_replace('SQL_CALC_FOUND_ROWS', '', $this->cache_sql);
		$sub_sql = preg_replace('/LIMIT [0-9]+(, *[0-9]+)?/i', '', $sub_sql);
		$sub_sql = str_replace(';', '', $sub_sql);

		$sql = 'SELECT COUNT(gt.id) AS id, SUM(gt.report_count) AS report_count FROM (' . $sub_sql . ') gt;';

		# SQL dotaz
		$this->DB->query($sql, get_class($this) . ' -> getGroupTotal');
		return ($this->DB->getRow());
	}

	# ...................................................................
	# 
	public function findPincode($pincode = null) {
		return ($this->findId('hub.pincode="' . mysqli_real_escape_string($this->DB->db, trim($pincode)) . '"', true));
	}

	# ...................................................................
	# return all waste bins allocated to hub by hub_id and status
	public function getWasteBins($hub_id = null, $status = 'nothhiden') {
		if (!(int)$hub_id)
			return (null);

		switch ($status) {
			case 'all':
				$status_where = null;
				break;
			case 'ok':
				$status_where = 'hwb.status="ok"';
				break;
			case 'nothidden':
			default:
				$status_where = 'hwb.status<>"hidden"';
		}


		$data = $this->getCustom(
			'SELECT hwb.amount, hwb.status, hwb.hub_id, hwb.wastetype_id, hwb.bintype_id, bnt.status AS bin_status, bnt.title AS bin_title, bnt.capacity AS bin_capacity, bnt.mobile AS bnt_mobile,  wst.status AS waste_status, wst.title AS waste_title, wst.description AS waste_description, wst.colortype AS waste_colortype, wst.recyclable AS waste_recyclable, wst.icon AS waste_icon, hub.status, hub.title 
			FROM hub2waste2bin hwb 
			JOIN hub ON hub.id=hwb.hub_id 
			JOIN bintype bnt ON bnt.id=hwb.bintype_id 
			JOIN wastetype wst ON wst.id=hwb.wastetype_id',
			array('hwb.hub_id="' . (int) $hub_id . '"', $status_where),
			'11,-14',
			-1
		);

		return ($data);
	}

	# ................................................................... 
	public function fillData($id = null) {
		if (!$id)
			return false;

		$item = $this->getId($id);

		foreach ($this->elements as $el)
			if (isset($item[$el]))
				$this->data[$el] = @$item[$el];
	}

	# ................................................................... 
	public function createPincode() {
		while (1) {
			$string = "";

			# create random pincode
			for ($i = 0; $i < $this->pincode_length; $i++)
				$string .= $this->pincode_chars[array_rand($this->pincode_chars)];

			# check if it does not exist
			if (!$this->findPincode($string))
				return $string;
		}
	}

	# ................................................................... 
	public function validate($id = null) {
		$errors = [];

		# keeper_id
		if (empty($this->data['keeper_id']))
			$errors['keeper_id'] = "Keeper is required";

		# pincode
		if (empty($this->data['pincode']))
			$errors['pincode'] = "pincode is required";
		elseif (!preg_match('/[' . implode($this->pincode_chars) . ']{' . $this->pincode_length . '}/', $this->data['pincode']))
			$errors['pincode'] = "pincode does not match allowed format";
		elseif (!empty($id) && $this->findPincode($this->data['pincode']) != $id)
			$errors['pincode'] = "pincode change is not allowed";
		elseif (empty($id) && $this->findPincode($this->data['pincode']))
			$errors['pincode'] = "unique pincode is required";

		return $errors;
	}

	# ................................................................... 
	# include all this->data into classic Modul set($set)
	public function setter($id = null) {
		foreach ($this->elements as $el) {
			$set[$el] = '"'.$this->data[$el].'"';
		}

		# mandatory last update
		$set['last_update'] = 'NOW()';

		return ($this->set ($set, $id));
	}
}
