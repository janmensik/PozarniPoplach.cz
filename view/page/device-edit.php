<?php

# *******************************************************************
# NEEDS + Global
# *******************************************************************

require_once(__DIR__ . '/../../include/class.Device.php');

if (!isset($Device))
	$Device = new \PozarniPoplach\Device($DB);

$APPD = AppData::getInstance();
$APPD->setData('PAGE', 'device-edit');

# ...................................................................
# Access control READ
if (!$User->hasPermission('devices', 'read')) {
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
	$data = $Device->getId($id);
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
		$Device->fillData($id);
	}

	# 2. Map from POST
	$Device->mapFromPost($_POST);

	# 3. Validate
	$errors = $Device->validate($id);

	# error in validation
	if ($errors) {
		$APPD->MESSAGES['stop'] = $errors;
	}
	# all good, saving
	else {
		# 4. Save
		$item_id = $Device->setter($id == 'new' ? null : $id);

		if ($item_id) {
			$APPD->MESSAGES['saved']['device'] = $Device->data['device_name'] ?? $Device->data['device_uuid'];
			$APPD->MESSAGES['saved']['id'] = $item_id;

			$APPD->hibernateMessages();
			header('Location: ' . $APPD->getData('BASE_URL') . '/' . $APPD->data['CONFIG']['devices_url']);
			header("Connection: close");
			exit();
		} else {
			$APPD->MESSAGES['stop']['device'] = 'not saved';
		}
	}
}

# *******************************************************************
# OUTPUT
# *******************************************************************

# If we have errors, merge POST data back into $data so the form isn't empty
if ($_POST && isset($errors)) {
	if (!is_array($data)) $data = [];
	foreach ($Device->data as $key => $val) {
		$data[$key] = $val;
	}
}

$Smarty->assign('data', $data);
