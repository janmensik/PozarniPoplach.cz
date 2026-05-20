<?php

# *******************************************************************
# NEEDS + Global
# *******************************************************************

$APPD = AppData::getInstance();
$APPD->setData('PAGE', 'settings');

# ...................................................................
# Access control READ
if (
	(empty($id) && !$User->hasPermission('settings', 'read')) ||
	(!empty($id) && !$User->hasPermission('users', 'read')) ||
	(empty($id) && !$User->hasPermission('settings', 'write')) ||
	(!empty($id) && !$User->hasPermission('users', 'write'))
) {
	header('HTTP/1.1 403 Forbidden');
	$APPD->setData('ERROR', '403');
	return;
}

# ...................................................................
# load up
require_once(__DIR__ . '/../../include/class.User.php');

if (!isset($User))
	$User = new \Pozarnipoplach\User($DB, $CASBIN);

# *******************************************************************
# PROGRAM
# *******************************************************************

# Initialize ID safely
$id = $id ?? null;

# data load
$data = (empty($id) || $id == 'new') ? $User->getUser() : $User->getId($id);
if (empty($id))
	$id = $data['id'];

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

# ...................................................................
#  Reset Pageschema
if (!empty($_POST['reset']) && $data['id']) {
	$User->clearPageSchema($data['id']);

	$APPD->hibernateMessages();

	if ($data['id'] == $User->getUser('id'))
		header('Location: ' . $APPD->getData('BASE_URL') .  '/' . $APPD->data['CONFIG']['settings_url']);
	else
		header('Location: ' . $APPD->getData('BASE_URL') .  '/' . $APPD->data['CONFIG']['users_url'] . '/' . $data['id']);
	header("Connection: close");
	exit();
}


# ...................................................................
#  Other changes
elseif (!empty($_POST)) {
	$error = false;

	# 1. Initialize data (if editing)
	if ($id != 'new') {
		$User->fillData($id);
	}

	# 2. Map from POST (standard fields)
	$User->mapFromPost($_POST);

	# 3. Custom Logic / Validation

	# editing wrong user (admin)
	if (!empty($data) && $data['status'] == 'admin' && $User->getUser('status') != 'admin') {
		$APPD->MESSAGES['error']['status'] = 'unauthorized';
		$error = true;
	}

	# ID integrity check (from original view logic)
	if (!empty($_POST['id']) && intval($_POST['id']) && $_POST['id'] != $data['id']) {
		$APPD->MESSAGES['error']['settings'] = 'wrong';
		$error = true;
	}

	# 4. Password Logic (Keep special custom functions)
	$generated_password = null;
	if (!empty($_POST['new_password'])) {
		if ($_POST['new_password'] != $_POST['new_password2']) {
			$APPD->MESSAGES['error']['new_password'] = 'mismatch';
			$error = true;
		}

		# Old password check for non-admins
		if ($id != 'new' && $User->getUser('status') != 'admin' && empty($_POST['old_password'])) {
			$APPD->MESSAGES['error']['old_password'] = 'empty';
			$error = true;
		} elseif ($id != 'new' && $User->getUser('status') != 'admin' && $data['password'] != $User->getPasswordHash($_POST['old_password'])) {
			$APPD->MESSAGES['error']['old_password'] = 'wrong';
			$error = true;
		}

		if (!$error) {
			$User->data['password'] = $User->getPasswordHash($User->sanitize($_POST['new_password']));
		}
	} elseif ($id == 'new') {
		$generated_password = $User->generatePassword();
		$User->data['password'] = $User->getPasswordHash($generated_password);
		$APPD->MESSAGES['special']['generated_password'] = $generated_password;
	} else {
		# Ensure we don't overwrite password with empty string if not provided in POST
		unset($User->data['password']);
	}

	# 5. Model Validation
	$errors = $User->validate($id);
	if ($errors) {
		foreach ($errors as $key => $val) {
			$APPD->MESSAGES['error'][$key] = $val;
		}
		$error = true;
	}

	# -----------------------------------------------------------------

	# if ok, save
	if (!$error) {
		# save
		$user_id = $User->setter(intval($id) ? $id : null);

		if ($user_id) {
			$APPD->MESSAGES['saved']['user'] =  $User->data['email'];
			$APPD->MESSAGES['saved']['id'] = $user_id;
			if ($generated_password)
				$APPD->MESSAGES['saved']['password'] = $generated_password;

			$APPD->hibernateMessages();


			if ($user_id == $User->getUser('id'))
				header('Location: ' . $APPD->getData('BASE_URL') .  '/' . $APPD->data['CONFIG']['settings_url']);
			else
				header('Location: ' . $APPD->getData('BASE_URL') .  '/' . $APPD->data['CONFIG']['users_url'] . '/' . $user_id);
			header("Connection: close");
			exit();
		} else
			$APPD->MESSAGES['error']['settings'] = 'not saved';
	}
}

# ...................................................................
# error handling - keep proposed data for output
if (!empty($_POST) && !isset($user_id)) {
	if (!is_array($data))
		$data = array();
	foreach ($User->data as $key => $value)
		$data[$key] = $value;
}

# *******************************************************************
# OUTPUT
# *******************************************************************

$Smarty->assign('user_text', $User->text);

$Smarty->assign('data', $data);
