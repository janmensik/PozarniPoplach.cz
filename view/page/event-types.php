<?php

# *******************************************************************
# NEEDS + Global
# *******************************************************************

$APPD = AppData::getInstance();
$APPD->setData('PAGE', 'event-types');

# ...................................................................
# Access control READ
if (!$User->hasPermission('event-types', 'read')) {
	header('HTTP/1.1 403 Forbidden');
	$APPD->setData('ERROR', '403');
	return;
}

# ...................................................................
# load up
require_once(__DIR__ . '/../../include/class.EventType.php');

if (!isset($EventType)) {
	$EventType = new \PozarniPoplach\EventType($DB);
}

# ...................................................................
# PageSchema I/O
$_GET = $User->setPageSchema('event_types', $_GET);

# *******************************************************************
# PROGRAM
# *******************************************************************

$where = null;

# *******************************************************************
# OUTPUT
# *******************************************************************

# nacteni
$data = $EventType->get($where, (isset($_GET['order']) ? $_GET['order'] : null), $APPD->data['APP']['DEFAULT_ITEMS_PER_PAGE'], (isset($_GET['p']) && intval($_GET['p'])) ? (int) $_GET['p'] : null);
$group_data = $EventType->getGroupTotal($where);

$Smarty->assign('data', $data);
$Smarty->assign('data_total', $EventType->getTotal($data, array('id' => 'count')));
$Smarty->assign('data_group_total', $group_data);
$Smarty->assign('data_count', $EventType->getRowsCount());
if (isset($_GET['order']))
	$Smarty->assign('data_extra', $EventType->getExtra($_GET['order']));

# access to create new?
$Smarty->assign('create_new', $User->hasPermission('event_types', 'create'));

# zpracovani pagination
$Smarty->assign(
	'pagination',
	pagination(
		$APPD->data['APP']['DEFAULT_ITEMS_PER_PAGE'],
		$EventType->getRowsCount(),
		(isset($_GET['p']) && intval($_GET['p'])) ? (int) $_GET['p'] : 1,
		$APPD->data['APP']['DEFAULT_ITEMS_PER_PAGE_DOTS']
	)
);