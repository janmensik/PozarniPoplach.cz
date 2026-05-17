<?php

# *******************************************************************
# NEEDS + Global
# *******************************************************************

$APPD = AppData::getInstance();
$APPD->setData('PAGE', 'advertisers');

# ...................................................................
# Access control READ
if (!$User->hasPermission('advertisers', 'read')) {
	header('HTTP/1.1 403 Forbidden');
	$APPD->setData('ERROR', '403');
	return;
}

# ...................................................................
# load up
require_once(__DIR__ . '/../../include/class.Advertiser.php');

if (!isset($Advertiser)) {
	$Advertiser = new \PozarniPoplach\Advertiser($DB);
}

# ...................................................................
# PageSchema I/O
$_GET = $User->setPageSchema('advertisers', $_GET);

# *******************************************************************
# PROGRAM
# *******************************************************************

$where = null;

# *******************************************************************
# OUTPUT
# *******************************************************************

# nacteni
$data = $Advertiser->get($where, (isset($_GET['order']) ? $_GET['order'] : null), $APPD->data['APP']['DEFAULT_ITEMS_PER_PAGE'], (isset($_GET['p']) && intval($_GET['p'])) ? (int) $_GET['p'] : null);
$group_data = $Advertiser->getGroupTotal($where);

$Smarty->assign('data', $data);
$Smarty->assign('data_total', $Advertiser->getTotal($data, array('id' => 'count')));
$Smarty->assign('data_group_total', $group_data);
$Smarty->assign('data_count', $Advertiser->getRowsCount());
if (isset($_GET['order']))
	$Smarty->assign('data_extra', $Advertiser->getExtra($_GET['order']));

# access to create new?
$Smarty->assign('create_new', $User->hasPermission('advertisers', 'create'));

# zpracovani pagination
$Smarty->assign(
	'pagination',
	pagination(
		$APPD->data['APP']['DEFAULT_ITEMS_PER_PAGE'],
		$Advertiser->getRowsCount(),
		(isset($_GET['p']) && intval($_GET['p'])) ? (int) $_GET['p'] : 1,
		$APPD->data['APP']['DEFAULT_ITEMS_PER_PAGE_DOTS']
	)
);