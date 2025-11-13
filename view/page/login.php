<?php

# *******************************************************************
# NEEDS + Global
# *******************************************************************

if (!isset($User))
	$User = new User($DB, $CASBIN);

$APPD = AppData::getInstance();
$APPD->setData('PAGE', 'login');

# *******************************************************************
# PROGRAM
# *******************************************************************

# empty form
if (empty($_POST['email']) || empty($_POST['password'])) {
	$APPD->MESSAGES['error']['login'] = 'empty';
	$APPD->MESSAGES['form_keep']['email'] = $_POST['email'];
	$APPD->MESSAGES['form_keep']['permanent'] = $_POST['permanent'];

	$APPD->hibernateMessages();

	header('Location: ' . $APPD->getData('BASE_URL') . '/login');
	header("Connection: close");
	exit();
} else {
	$user_id = null;
	$user_id = $User->verify($_POST['email'], $_POST['password']);

	# unverified
	if (!$user_id) {

		$APPD->MESSAGES['error']['login'] = 'wrong';
		$APPD->MESSAGES['form_keep']['email'] = $_POST['email'];
		$APPD->MESSAGES['form_keep']['permanent'] = $_POST['permanent'];
		$APPD->hibernateMessages();

		header('Location: ' . $APPD->getData('BASE_URL') . '/login');
		header("Connection: close");
		exit();
	}

	# set permanent login (30 days)
	if ($_POST['permanent']) {
		setcookie("permanent_login", $User->getPermanentHash($User->getUser('id')), time() + 60 * 60 * 24 * 30, '/');	
	}

	# verified	
	$APPD->setData('USER', $User->load($user_id));	
	$_SESSION['user_id'] = $user_id;

	$APPD->MESSAGES['saved']['login'] = 'logged';
	$APPD->hibernateMessages();

	header('Location: ' . $APPD->getData('BASE_URL'));
	header("Connection: close");
	exit();
}

# *******************************************************************
# OUTPUT
# *******************************************************************