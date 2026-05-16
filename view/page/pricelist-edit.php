<?php

# *******************************************************************
# NEEDS + Global
# *******************************************************************

$APPD = AppData::getInstance();
$APPD->setData('PAGE', 'pricelist-edit');

# ...................................................................
# Access control READ
if (!$User->hasPermission('pricelists', 'read') && (!$User->hasPermission('pricelists', 'write') && $id == 'new')) {
	header('HTTP/1.1 403 Forbidden');
	$APPD->setData('ERROR', '403');
	return;
}

# ...................................................................
# load up
require_once(__DIR__ . '/../../lib/functions/function.parsedate.php');

require_once(__DIR__ . '/../../include/class.Pricelist.php');

if (!isset($Pricelist))
	$Pricelist = new Pricelist($DB);


# *******************************************************************
# PROGRAM
# *******************************************************************

# creating copy
if (isset($_GET['copy']) && (int) $_GET['copy']) {
	$data = $Pricelist->getId((int) $_GET['copy']);
	$data['id'] = null;
	$data['title'] .= ' (Kopie)';
	if (isset ($data['promos']) &&is_array($data['promos'])) {
		foreach ($data['promos'] as $key => $value)
			$data['promos'][$key] .= '-kopie';
		$data['promo'] = implode(', ', $data['promos']);
	}
}
# regular data load
else
	$data = $Pricelist->getId($id);

# not found
if (!$data && $id != "new") {
	header('HTTP/1.1 404 Not Found');
	$APPD->setData('ERROR', '404');
	$APPD->setData('PAGE', '404');
	return;
}


# *******************************************************************
# FORM Sanitation & Validation
# *******************************************************************
if (isset($_POST) && is_array($_POST) && $_POST) {
	$error = false;

	# id
	if (isset($_POST['id']) && intval($_POST['id']) > 0 && $_POST['id'] != 'new')
		$form_clean['id'] = (int) $_POST['id'];

	# title
	if (isset($_POST['title']) && !empty($_POST['title']))
		$form_clean['title'] = $Pricelist->sanitize($_POST['title']);
	else {
		$APPD->MESSAGES['error']['title'] = 'empty';
		$error = true;
	}

	# promo
	if (isset($_POST['promo']) && !empty($_POST['promo']))
		$form_clean['promo'] = $Pricelist->sanitize($_POST['promo']);
	else
		$form_clean['promo'] = null;

	# note
	if (isset($_POST['note']) && !empty($_POST['note']))
		$form_clean['note'] = $Pricelist->sanitize($_POST['note']);
	else
		$form_clean['note'] = "";

	# capacity
	if (isset($_POST['capacity']) && intval($_POST['capacity']) > 0)
		$form_clean['capacity'] = $Pricelist->sanitize($_POST['capacity'], 'int');
	else {
		$APPD->MESSAGES['error']['capacity'] = 'wrong';
		$error = true;
	}

	# dynamic
	$form_clean['dynamic'] = isset($_POST['dynamic']) && $Pricelist->sanitize($_POST['dynamic']) == 'on' ? 1 : 0;

	# status
	if (isset($_POST['status']) && in_array($_POST['status'], array('ok', 'deleted')))
		$form_clean['status'] = $Pricelist->sanitize($_POST['status']);
	else {
		$APPD->MESSAGES['error']['status'] = 'wrong';
		$error = true;
	}

	# days
	for ($i = 1; $i <= 32; $i++) {
		$form_clean['day_' . $i] = abs((int) $Pricelist->sanitize($_POST['day_' . $i], 'int'));
	}

	# -----------------------------------------------------------------
	# PERIODS
	if (isset($_POST['period']) && is_array($_POST['period'])) {
		foreach ($_POST['period'] as $key => $value) {
			if ($value['valid_from'] && $value['valid_till'] && parseDate($value['valid_from']) && parseDate($value['valid_till'])) {
				$form_clean['periods'][$key]['valid_from'] =  date('j. n. Y', parseDate($value['valid_from']));
				$form_clean['periods'][$key]['valid_till'] =  date('j. n. Y', parseDate($value['valid_till']));
			} elseif (!$value['valid_from'] && !$value['valid_till']) {
				$value['remove'] = true;
			} elseif ($value['valid_from'] || $value['valid_till']) {
				$APPD->MESSAGES['error']['periods'][$key] = 'wrong';
				$error = true;
			}

			if (isset($value['remove']) && $value['remove'])
				$form_clean['periods'][$key]['remove'] = true;
		}
	}
	# insert
	if (parseDate($_POST['period_new']['valid_from']) && parseDate($_POST['period_new']['valid_till'])) {
		if ($_POST['period_new']['valid_from'] && $_POST['period_new']['valid_till'] && parseDate($_POST['period_new']['valid_from']) && parseDate($_POST['period_new']['valid_till'])) {
			$form_clean['period_new']['valid_from'] =  date('j. n. Y', parseDate($_POST['period_new']['valid_from']));
			$form_clean['period_new']['valid_till'] =  date('j. n. Y', parseDate($_POST['period_new']['valid_till']));
		} elseif ($_POST['period_new']['valid_from'] || $_POST['period_new']['valid_till']) {
			$APPD->MESSAGES['error']['period_new'] = 'wrong';
			$error = true;
		}
	}

	# -----------------------------------------------------------------



	# pokud vse v poradku a mam co ukladat, ulozim
	if (!$error && is_array($form_clean) && ($id == $form_clean['id'] || $id == 'new')) {

		# policy check
		if (!$User->hasPermission('pricelists', 'update') && (!$User->hasPermission('pricelists', 'write') && $id == 'new')) {
			header('HTTP/1.1 403 Forbidden');
			$APPD->setData('ERROR', '403');
			return;
		}

		# to_save population
		$to_save['title'] = '"' . $form_clean['title'] . '"';
		if ($form_clean['promo'] === null)
			$to_save['promo'] = 'null';
		else
			$to_save['promo'] = '"' . $form_clean['promo'] . '"';
		$to_save['note'] = '"' . $form_clean['note'] . '"';
		$to_save['capacity'] = $form_clean['capacity'];
		$to_save['dynamic'] = $form_clean['dynamic'];
		$to_save['status'] = '"' . $form_clean['status'] . '"';

		for ($i = 1; $i <= 32; $i++)
			$to_save['day_' . $i] = $form_clean['day_' . $i];

		// If 'period' fields were part of the submission, we are managing M:N periods.
		// This block ensures that if all periods are removed, an empty array is passed
		// to Modul::set(), signaling that all related records should be deleted.
		if (isset($_POST['period']) || !empty($_POST['period_new']['valid_from'])) {
			$to_save['periods'] = [];
			if (isset($form_clean['periods']) && is_array($form_clean['periods'])) {
				foreach ($form_clean['periods'] as $key => $value) {
					if (empty($value['remove'])) {
						$to_save['periods'][$key] = array('valid_from' => 'FROM_UNIXTIME("' . parseDate($value['valid_from']) . '")', 'valid_till' => 'FROM_UNIXTIME("' . parseDate($value['valid_till']) . '")');
					}
				}
			}
			if (isset($form_clean['period_new']) && is_array($form_clean['period_new'])) {
				$to_save['periods']['new'] = array('valid_from' => 'FROM_UNIXTIME("' . parseDate($form_clean['period_new']['valid_from']) . '")', 'valid_till' => 'FROM_UNIXTIME("' . parseDate($form_clean['period_new']['valid_till']) . '")');
			}
		}

		//print_r ($form_clean);echo('<hr>');
		//print_r ($to_save);echo('<hr>');
		$pricelist_id = $Pricelist->set($to_save, intval($id) ? $id : null);
		//print_r ($Pricelist->DB->messages);
		//exit ('1');
		if ($pricelist_id) {
			$APPD->MESSAGES['saved']['pricelist'] = 'saved';
			$APPD->MESSAGES['saved']['id'] = $pricelist_id;
			$APPD->MESSAGES['saved']['title'] = $form_clean['title'];

			# ulozim do session uzivatele a hlaseni a presmeruji
			$APPD->hibernateMessages();

			//header('Location: ' . $APPD->getData('BASE_URL') .  '/' . $APPD->data['CONFIG']['pricelists_url'] . '/' . $pricelist_id);
			header('Location: ' . $APPD->getData('BASE_URL') .  '/' . $APPD->data['CONFIG']['pricelists_url']);
			header("Connection: close");
			exit();
		} else
			$APPD->MESSAGES['error']['pricelist'] = 'not saved';
	}
} elseif ($_POST)
	$APPD->MESSAGES['error']['pricelist_wrong'] = 'wrong data';


# duplikace
if (isset($_GET['duplikace']) && $_GET['duplikace'] && ($User->getUser('status') == 'admin' || $User->getUser('status') == 'deputy')) {
	$data = $Pricelist->getId((int) $_GET['duplikace'], false);
	unset($data['id']);
	$data['title'] .= ' (Kopie)';
}

# ...................................................................
# doslo k chybe pri ukladani - ponecham si post data
if (isset($form_clean) && is_array($form_clean) && !isset($pricelist_id)) {
	if (!is_array($data))
		$data = array();
	foreach ($form_clean as $key => $value)
		$data[$key] = $value;
}

# *******************************************************************
# OUTPUT
# *******************************************************************

$Smarty->assign('pricelist_text', $Pricelist->text);

$Smarty->assign('data', $data);
