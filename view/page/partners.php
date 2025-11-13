<?php

# *******************************************************************
# NEEDS + Global
# *******************************************************************

$APPD = AppData::getInstance();
$APPD->setData('PAGE', 'partners');

# ...................................................................
# Access control READ
if (!$User->hasPermission('partners', 'read')) {
	header('HTTP/1.1 403 Forbidden');
	$APPD->setData('ERROR', '403');
	return;
}

# ...................................................................
# load up
require_once(__DIR__ . '/../../include/class.Partner.php');

if (!isset($Partner))
	$Partner = new Partner($DB);

# ...................................................................
# PageSchema I/O
$_GET = $User->setPageSchema('partners', $_GET);

# *******************************************************************
# PROGRAM
# *******************************************************************

$where = null;

/*
# FILTER register
$FILTERS[$APPD->getData('PAGE')] = array('q', 'status');

# FILTER clear
if (isset($_GET['filter_clear'])) {
	unset($schema);
	$schema = $User->getPageSchema($APPD->getData('PAGE'));

	if (is_array($FILTERS[$APPD->getData('PAGE')])) {
		foreach ($FILTERS[$APPD->getData('PAGE')] as $value) {
			$_GET[$value] = false;
		}
	}
	$_GET = $User->setPageSchema($APPD->getData('PAGE'), $_GET);
}
*/
# ...................................................................



# *******************************************************************
# OUTPUT
# *******************************************************************

# nacteni
$data = $Partner->get($where, (isset($_GET['order']) ? $_GET['order'] : null), $APPD->data['APP']['DEFAULT_ITEMS_PER_PAGE'], (isset($_GET['p']) && intval($_GET['p'])) ? (int) $_GET['p'] : null);

$Smarty->assign('data', $data);
$Smarty->assign('data_total', $Partner->getTotal($data, array('id' => 'count')));
// $Smarty->assign('data_group_total', $group_data);
$Smarty->assign('data_count', $Partner->getRowsCount());
if (isset($_GET['order']))
	$Smarty->assign('data_extra', $Partner->getExtra($_GET['order']));

# access to create new?
$Smarty->assign('create_new', $User->hasPermission('partners', 'create'));

# zpracovani pagination
$Smarty->assign(
	'pagination',
	pagination(
		$APPD->data['APP']['DEFAULT_ITEMS_PER_PAGE'],
		$Partner->getRowsCount(),
		(isset($_GET['p']) && intval($_GET['p'])) ? (int) $_GET['p'] : 1,
		$APPD->data['APP']['DEFAULT_ITEMS_PER_PAGE_DOTS']
	)
);
