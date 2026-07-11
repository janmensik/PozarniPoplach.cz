<?php

use Janmensik\Jmlib\AppData;
use Janmensik\Jmlib\Database;

/** @var Database $DB */

# *******************************************************************
# NEEDS + Global
# *******************************************************************

$APPD = AppData::getInstance();
$APPD->setData('PAGE', 'import-log');

# ...................................................................
# Access control READ
if (!$User->hasPermission('import-log', 'read')) {
    header('HTTP/1.1 403 Forbidden');
    $APPD->setData('ERROR', '403');
    return;
}

# ...................................................................
# load up
require_once(__DIR__ . '/../../include/class.ImportLog.php');

if (!isset($ImportLog)) {
    $ImportLog = new \PozarniPoplach\ImportLog($DB);
}

# ...................................................................
# PageSchema I/O
$_GET = $User->setPageSchema('import-log', $_GET);

# *******************************************************************
# PROGRAM
# *******************************************************************

$where = null;

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
if (isset($_GET['q'])) {
    $where[] = $User->createFulltextSubquery(mysqli_real_escape_string($DB->db, $_GET['q']));
}

# FILTER status
if (isset($_GET['status']) && in_array($_GET['status'], array('success', 'error'))) {
    $where[] = 'il.status="' . mysqli_real_escape_string($DB->db, $_GET['status']) . '"';
}

# *******************************************************************
# OUTPUT
# *******************************************************************

# nacteni
$data = $ImportLog->get($where, (isset($_GET['order']) ? $_GET['order'] : null), $APPD->data['APP']['DEFAULT_ITEMS_PER_PAGE'], (isset($_GET['p']) && intval($_GET['p'])) ? (int) $_GET['p'] : null);
$group_data = $ImportLog->getGroupTotal($where);

$Smarty->assign('data', $data);
$Smarty->assign('data_total', $ImportLog->getTotal($data, array('id' => 'count')));
$Smarty->assign('data_group_total', $group_data);
$Smarty->assign('data_count', $ImportLog->getRowsCount());
if (isset($_GET['order'])) {
    $Smarty->assign('data_extra', $ImportLog->getExtra($_GET['order']));
}

# zpracovani pagination
$Smarty->assign(
    'pagination',
    pagination(
        $APPD->data['APP']['DEFAULT_ITEMS_PER_PAGE'],
        $ImportLog->getRowsCount(),
        (isset($_GET['p']) && intval($_GET['p'])) ? (int) $_GET['p'] : 1,
        $APPD->data['APP']['DEFAULT_ITEMS_PER_PAGE_DOTS']
    )
);
