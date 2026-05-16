<?php

# *******************************************************************
# NEEDS + Global
# *******************************************************************

require_once(__DIR__ . '/../include/class.Hub.php');

if (!isset($Hub))
	$Hub = new Hub($DB);

$APPD = AppData::getInstance();
$APPD->setData('PAGE', 'hub-edit');

# ...................................................................
# Access control READ
if (!$User->hasPermission('hub', 'read')) {
	header('HTTP/1.1 403 Forbidden');
	$APPD->setData('ERROR', '403');
	return;
}

# *******************************************************************
# PROGRAM
# *******************************************************************

# data load
$data = $Hub->getId($Hub->findPincode($pincode));

# not found
if (!$data) {
	header('HTTP/1.1 404 Not Found');
	$APPD->setData('ERROR', '404');
	$APPD->setData('PAGE', '404');
	return;
}

# Access control
if (!$User->hasPermission('hub', 'read') || (!$User->hasPermission('hub', 'all') && $data['keeper_id'] != $User->getUser('keeper_id'))) {
	header('HTTP/1.1 403 Forbidden');
	$APPD->setData('ERROR', '403');
	return;
}

# ...................................................................
#  ukladam 	
if ($_POST && $data['id']) {
	$stop = false;

	$Hub->fillData($data['id']);

	# Sanitize data (prevent XSS, SQL injection)	
	$Hub->data['title'] = $Hub->sanitize(@$_POST['title']);
	$Hub->data['description'] = $Hub->sanitize(@$_POST['description']);
	$Hub->data['collection'] = $Hub->sanitize(@$_POST['collection']);
	$Hub->data['street'] = $Hub->sanitize(@$_POST['street']);
	$Hub->data['city'] = $Hub->sanitize(@$_POST['city']);
	$Hub->data['title'] = $Hub->sanitize(@$_POST['title']);

	$Hub->data['latitude'] = $Hub->sanitize(@$_POST['latitude'], 'float');
	$Hub->data['longitude'] = $Hub->sanitize(@$_POST['longitude'], 'float');

	$Hub->data['status'] = $Hub->sanitize(@$_POST['status'], 'inarray', true, array_keys($Hub->text['cs']['status']));

	if (empty($Hub->data['pincode']))
		$Hub->data['pincode'] = $Hub->createPincode();

	# validation
	$errors = $Hub->validate($data['id']);

	/*
	var_dump($_POST);
	echo ('<hr>');
	print_r($Hub->data);
	echo ('<hr>');
	print_r($errors);
	exit('!!!');
	*/

	# error in validation
	if ($errors) {
		$APPD->MESSAGES['stop'] = $errors;
	}
	# all good, saving
	else {
		$item_id = $Hub->setter($data['id']);

		if ($item_id) {
			$MESSAGES['hub']['saved_id'] = $item_id;

			$APPD->hibernateMessages();
			header('Location: ' . $APPD->getData('BASE_URL') . '/' . $APPD->data['APP']['hubs_url']);
			header("Connection: close");
			exit();
		} else
			$APPD->MESSAGES['stop']['hub'] = 'not saved';
	}
} elseif ($_POST)
	$APPD->MESSAGES['stop']['hub_wrong'] = 'wrong data';

# ...................................................................
/*
$editdata = $data;
foreach ($_POST as $key => $value)
	$editdata[$key] = $value;
*/
# ...................................................................

$Smarty->assign('data', $data);
