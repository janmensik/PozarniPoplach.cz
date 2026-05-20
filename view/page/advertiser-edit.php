<?php

use Janmensik\Jmlib\AppData;
use Janmensik\Jmlib\Database;

/** @var Database $DB */

# *******************************************************************
# NEEDS + Global
# *******************************************************************

require_once(__DIR__ . '/../../include/class.Advertiser.php');

if (!isset($Advertiser))
	$Advertiser = new \PozarniPoplach\Advertiser($DB);

$APPD = AppData::getInstance();
$APPD->setData('PAGE', 'advertiser-edit');

# ...................................................................
# Access control READ
if (!$User->hasPermission('advertisers', 'read')) {
	header('HTTP/1.1 403 Forbidden');
	$APPD->setData('ERROR', '403');
	return;
}

# *******************************************************************
# PROGRAM
# *******************************************************************

# Initialize ID safely
$id = $id ?? null;

# data load
if (!empty($id) && $id != 'new') {
	$data = $Advertiser->getId($id);
} else {
	$data = null;
}

# not found
if (!$data && (!empty($id) && $id != 'new')) {
	header('HTTP/1.1 404 Not Found');
	$APPD->setData('ERROR', '404');
	$APPD->setData('PAGE', '404');
	return;
}

# *******************************************************************
# FORM Sanitation & Validation
# *******************************************************************

if ($_POST) {
	# 1. Initialize data (if editing)
	if ($id != 'new') {
		$Advertiser->fillData($id);
	}

	# 2. Map from POST
	$Advertiser->mapFromPost($_POST);

	# 3. Validate
	$errors = $Advertiser->validate();

	# error in validation
	if ($errors) {
		$APPD->MESSAGES['stop'] = $errors;
	}
	# all good, saving
	else {
		# 4. Save
		$item_id = $Advertiser->setter($id == 'new' ? null : $id);

		if ($item_id) {
			$APPD->MESSAGES['saved']['advertiser'] = $Advertiser->data['type'];
			$APPD->MESSAGES['saved']['id'] = $item_id;

			$APPD->hibernateMessages();
			header('Location: ' . $APPD->getData('BASE_URL') . '/' . $APPD->data['CONFIG']['advertisers_url']);
			header("Connection: close");
			exit();
		} else {
			$APPD->MESSAGES['stop']['advertiser'] = 'not saved';
		}
	}
}

# *******************************************************************
# OUTPUT
# *******************************************************************

# If we have errors, merge POST data back into $data so the form isn't empty
if ($_POST && isset($errors)) {
	if (!is_array($data)) $data = [];
	foreach ($Advertiser->data as $key => $val) {
		$data[$key] = $val;
	}
}

$Smarty->assign('data', $data);
