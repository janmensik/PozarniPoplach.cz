<?php

# *******************************************************************
# NEEDS + Global
# *******************************************************************

$APPD = AppData::getInstance();

$APPD->setData('API', 'true');
$APPD->setData('PAGE', 'get-price');

# ...................................................................
# Access control READ
if (!$User->hasPermission('get_price', 'read')) {
	header('HTTP/1.1 403 Forbidden');
	$APPD->setData('ERROR', '403');
	return;
}

# ...................................................................
# load up

require_once(__DIR__ . '/../../include/class.Pricelist.php');
require_once(__DIR__ . '/../../include/class.ParkClosure.php');

require_once(__DIR__ . "/../../lib/functions/function.countdays.php");
require_once(__DIR__ . "/../../lib/functions/function.parsedate.php");

use Nette\Utils\Validators;
use Nette\Utils\DateTime;

if (!isset($Pricelist))
	$Pricelist = new Pricelist($DB);

if (!isset($ParkClosure))
	$ParkClosure = new ParkClosure($DB);


# *******************************************************************
# PROGRAM
# *******************************************************************

# validation
$error = false;

if (!empty($_GET['checkin_ts']) && (int) $_GET['checkin_ts']) {
	$checkin = (int) $_GET['checkin_ts'];
} else {
	$data['error'][] = 'checkin_ts wrong or missing';
	$error = true;
}

if (!empty($_GET['checkout_ts']) && (int) $_GET['checkout_ts']) {
	$checkout = (int) $_GET['checkout_ts'];
} else {
	$data['error'][] = 'checkout_ts wrong or missing';
	$error = true;
}

if (!empty($_GET['dynamic']) && ($_GET['dynamic'] == 'true' || $_GET['dynamic'] == '1'))
	$dynamic_price = true;
else
	$dynamic_price = false;

if (!empty($_GET['pricelist_id']) && (int) $_GET['pricelist_id'] && $Pricelist->getBasic(array('p.id="' . (int) $_GET['pricelist_id'] . '"', 'p.status="ok"'), null, null, null, true)) {
	$pricelist_id = (int) $_GET['pricelist_id'];
} else {
	$data['error'][] = 'pricelist_id wrong or missing';
	$error = true;
}

if (!empty($_GET['respect_closures']) && ($_GET['respect_closures'] == 'true' || $_GET['respect_closures'] == '1'))
	$respect_closures = true;
else
	$respect_closures = false;

$data = array();

if (!$error)
	$data = $Pricelist->getPrice(
		$_GET['checkout_ts'],
		$_GET['checkin_ts'],
		null,
		$dynamic_price,
		$pricelist_id
	);


# check if not closed 
if (
	isset($_GET['respect_closures']) &&
	(
		$_GET['respect_closures'] == 'true' ||
		$_GET['respect_closures'] == 1
	) &&
	(
		!$ParkClosure->testMinLead($data['checkin_ts']) ||
		!$ParkClosure->testCheckin($_GET['checkin_ts']) ||
		!$ParkClosure->testCheckout($_GET['checkout_ts'])
	)
) {
	$data['capacity'] = 0;
}

# *******************************************************************
# OUTPUT
# *******************************************************************

echo (json_encode($data));
