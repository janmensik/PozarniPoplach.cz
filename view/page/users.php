<?php

# *******************************************************************
# NEEDS + Global
# *******************************************************************

$APPD = AppData::getInstance();
$APPD->setData('PAGE', 'users');

# ...................................................................
# Access control READ
if (!$User->hasPermission('users', 'read')) {
	header('HTTP/1.1 403 Forbidden');
	$APPD->setData('ERROR', '403');
	return;
}

# ...................................................................
# PageSchema I/O
$_GET = $User->setPageSchema('users', $_GET);

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

# fulltext
if (isset($_GET['q']))
	$where[] = $User->createFulltextSubquery(mysqli_real_escape_string($DB->db, $_GET['q']));

# FILTER status
if (isset($_GET['status']) && in_array($_GET['status'], array('disabled', 'deleted', 'admin', 'partner', 'visitor', 'operator', 'servis')))
	$where[] = 'u.status="' . $_GET['status'] . '"';
elseif (isset($_GET['status']) && $_GET['status'] == 'ok')
	$where[] = 'u.status NOT IN ("disabled","deleted")';
*/

# *******************************************************************
# OUTPUT
# *******************************************************************

# nacteni
$data = $User->getWithLastLogin($where, (isset($_GET['order']) ? $_GET['order'] : null), $APPD->data['APP']['DEFAULT_ITEMS_PER_PAGE'], (isset($_GET['p']) && intval($_GET['p'])) ? (int) $_GET['p'] : null);
$group_data = $User->getGroupTotal($where);

$Smarty->assign('data', $data);
$Smarty->assign('data_total', $User->getTotal($data, array('id' => 'count')));
$Smarty->assign('data_group_total', $group_data);
$Smarty->assign('data_count', $User->getRowsCount());
if (isset($_GET['order']))
	$Smarty->assign('data_extra', $User->getExtra($_GET['order']));

# access to create new?
$Smarty->assign('create_new', $User->hasPermission('users', 'create'));

# zpracovani pagination
$Smarty->assign(
	'pagination',
	pagination(
		$APPD->data['APP']['DEFAULT_ITEMS_PER_PAGE'],
		$User->getRowsCount(),
		(isset($_GET['p']) && intval($_GET['p'])) ? (int) $_GET['p'] : 1,
		$APPD->data['APP']['DEFAULT_ITEMS_PER_PAGE_DOTS']
	)
);