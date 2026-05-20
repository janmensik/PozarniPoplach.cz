<?php

use Janmensik\Jmlib\AppData;
use Janmensik\Jmlib\Database;

/** @var Database $DB */

# *******************************************************************
# NEEDS + Global
# *******************************************************************

$APPD = AppData::getInstance();
$APPD->setData('PAGE', 'vehicle-types');

# ...................................................................
# Access control READ
if (!$User->hasPermission('vehicle-types', 'read')) {
	header('HTTP/1.1 403 Forbidden');
	$APPD->setData('ERROR', '403');
	return;
}

# ...................................................................
# load up
require_once(__DIR__ . '/../../include/class.VehicleType.php');

if (!isset($VehicleType)) {
	$VehicleType = new \PozarniPoplach\VehicleType($DB);
}

# ...................................................................
# PageSchema I/O
$_GET = $User->setPageSchema('vehicle_types', $_GET);

# *******************************************************************
# PROGRAM
# *******************************************************************

$where = null;

# *******************************************************************
# OUTPUT
# *******************************************************************

# nacteni
$data = $VehicleType->get($where, (isset($_GET['order']) ? $_GET['order'] : null), $APPD->data['APP']['DEFAULT_ITEMS_PER_PAGE'], (isset($_GET['p']) && intval($_GET['p'])) ? (int) $_GET['p'] : null);
$group_data = $VehicleType->getGroupTotal($where);

$Smarty->assign('data', $data);
$Smarty->assign('data_total', $VehicleType->getTotal($data, array('id' => 'count')));
$Smarty->assign('data_group_total', $group_data);
$Smarty->assign('data_count', $VehicleType->getRowsCount());
if (isset($_GET['order']))
	$Smarty->assign('data_extra', $VehicleType->getExtra($_GET['order']));

# access to create new?
$Smarty->assign('create_new', $User->hasPermission('vehicle_types', 'create'));

# zpracovani pagination
$Smarty->assign(
	'pagination',
	pagination(
		$APPD->data['APP']['DEFAULT_ITEMS_PER_PAGE'],
		$VehicleType->getRowsCount(),
		(isset($_GET['p']) && intval($_GET['p'])) ? (int) $_GET['p'] : 1,
		$APPD->data['APP']['DEFAULT_ITEMS_PER_PAGE_DOTS']
	)
);