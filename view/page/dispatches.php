<?php

use Janmensik\Jmlib\AppData;
use Janmensik\Jmlib\Database;

/** @var Database $DB */

# *******************************************************************
# NEEDS + Global
# *******************************************************************

$APPD = AppData::getInstance();
$APPD->setData('PAGE', 'dispatches');

# ...................................................................
# Access control READ
if (!$User->hasPermission('dispatches', 'read')) {
	header('HTTP/1.1 403 Forbidden');
	$APPD->setData('ERROR', '403');
	return;
}

# ...................................................................
# load up
require_once(__DIR__ . '/../../include/class.Dispatch.php');

if (!isset($Dispatch)) {
	$Dispatch = new \PozarniPoplach\Dispatch($DB);
}

# ...................................................................
# PageSchema I/O
$_GET = $User->setPageSchema('dispatches', $_GET);

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
$data = $Dispatch->get($where, (isset($_GET['order']) ? $_GET['order'] : null), $APPD->data['APP']['DEFAULT_ITEMS_PER_PAGE'], (isset($_GET['p']) && intval($_GET['p'])) ? (int) $_GET['p'] : null);
$group_data = $Dispatch->getGroupTotal($where);

$Smarty->assign('data', $data);
$Smarty->assign('data_total', $Dispatch->getTotal($data, array('id' => 'count')));
$Smarty->assign('data_group_total', $group_data);
$Smarty->assign('data_count', $Dispatch->getRowsCount());
if (isset($_GET['order']))
	$Smarty->assign('data_extra', $Dispatch->getExtra($_GET['order']));

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