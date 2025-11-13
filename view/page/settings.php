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
	$User = new User($DB, $CASBIN);

# *******************************************************************
# PROGRAM
# *******************************************************************

# data load
$data = empty($id) ? $User->getUser() : $User->getId($id);
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
	$form_clean = null;

	# editing wrong user (admin)
	if (!empty($data) && $data['status'] == 'admin' && $User->getUser('status') != 'admin') {
		$APPD->MESSAGES['error']['status'] = 'unauthorized';
		$error = true;
	}

	# id
	if (!empty($_POST['id']) && intval($_POST['id']) && $_POST['id'] == $data['id'])
		$form_clean['id'] = (int) $_POST['id'];
	elseif ($id != 'new')
		$APPD->MESSAGES['error']['settings'] = 'wrong';

	# name
	if (!empty($_POST['name']))
		$form_clean['name'] = $User->sanitize($_POST['name']);
	else {
		$APPD->MESSAGES['error']['name'] = 'empty';
		$error = true;
	}

	# email
	if (!empty($_POST['email']))
		$form_clean['email'] = $User->sanitize($_POST['email']);
	else {
		$APPD->MESSAGES['error']['email'] = 'empty';
		$error = true;
	}

	# note
	if (isset($_POST['note']) && !empty($_POST['note']))
		$form_clean['note'] = $User->sanitize($_POST['note']);
	else
		$form_clean['note'] = "";

	# status
	if (isset($_POST['status']) && $User->text['cs']['status'][$_POST['status']])
		$form_clean['status'] = $User->sanitize($_POST['status']);
	else {
		$APPD->MESSAGES['error']['status'] = 'wrong';
		$error = true;
	}

	# password
	if (!empty($_POST['new_password'])) {
		if ($_POST['new_password'] != $_POST['new_password2']) {
			$APPD->MESSAGES['error']['new_password'] = 'mismatch';
			$error = true;
		}
		if ($id != 'new' && $User->getUser('status')!='admin' && empty($_POST['old_password'])) {
			$APPD->MESSAGES['error']['old_password'] = 'empty';
			$error = true;
		} elseif ($id != 'new' && $User->getUser('status')!='admin' && $data['password'] != $User->getPasswordHash($_POST['old_password'])) {
			$APPD->MESSAGES['error']['old_password'] = 'wrong';
			$error = true;
		}

		$form_clean['password'] = $User->getPasswordHash($User->sanitize($_POST['new_password']));
	} elseif ($id == 'new') {
		$generated_password = $User->generatePassword();
		$form_clean['password'] = $User->getPasswordHash($generated_password);
		$APPD->MESSAGES['special']['generated_password'] = $generated_password;
	}

	# -----------------------------------------------------------------

	# if ok, populate to_save and save
	if (!$error && is_array($form_clean) && ($id == $form_clean['id'] || $id == 'new')) {

		# to_save population
		if ($form_clean['name'])
			$to_save['name'] = '"' . $form_clean['name'] . '"';
		if ($form_clean['email'])
			$to_save['email'] = '"' . $form_clean['email'] . '"';
		if ($form_clean['password'])
			$to_save['password'] = '"' . $form_clean['password'] . '"';
		$to_save['note'] = '"' . $form_clean['note'] . '"';
		if ($form_clean['status'])
			$to_save['status'] = '"' . $form_clean['status'] . '"';

		# save
		$user_id = $User->set($to_save, intval($id) ? $id : null);
		if ($user_id) {
			$APPD->MESSAGES['saved']['user'] =  $form_clean['email'];
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
# doslo k chybe pri ukladani - ponecham si post data
if (isset($form_clean) && is_array($form_clean) && !isset($user_id)) {
	if (!is_array($data))
		$data = array();
	foreach ($form_clean as $key => $value)
		$data[$key] = $value;
}

# *******************************************************************
# OUTPUT
# *******************************************************************

$Smarty->assign('user_text', $User->text);

$Smarty->assign('data', $data);
