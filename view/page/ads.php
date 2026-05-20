<?php

use Janmensik\Jmlib\AppData;
use Janmensik\Jmlib\Database;

/** @var Database $DB */

# *******************************************************************
# NEEDS + Global
# *******************************************************************

$APPD = AppData::getInstance();
$APPD->setData('PAGE', 'ads');

# ...................................................................
# Access control READ
if (!$User->hasPermission('ads', 'read')) {
    header('HTTP/1.1 403 Forbidden');
    $APPD->setData('ERROR', '403');
    return;
}

# ...................................................................
# load up
require_once(__DIR__ . '/../../include/class.Ad.php');

if (!isset($Ad)) {
    $Ad = new \PozarniPoplach\Ad($DB);
}

# ...................................................................
# PageSchema I/O
$_GET = $User->setPageSchema('ads', $_GET);

# *******************************************************************
# PROGRAM
# *******************************************************************

$where = null;

# *******************************************************************
# OUTPUT
# *******************************************************************

# nacteni
$data = $Ad->get($where, (isset($_GET['order']) ? $_GET['order'] : null), $APPD->data['APP']['DEFAULT_ITEMS_PER_PAGE'], (isset($_GET['p']) && intval($_GET['p'])) ? (int) $_GET['p'] : null);
$group_data = $Ad->getGroupTotal($where);

$Smarty->assign('data', $data);
$Smarty->assign('data_total', $Ad->getTotal($data, array('id' => 'count')));
$Smarty->assign('data_group_total', $group_data);
$Smarty->assign('data_count', $Ad->getRowsCount());
if (isset($_GET['order'])) {
    $Smarty->assign('data_extra', $Ad->getExtra($_GET['order']));
}

# access to create new?
$Smarty->assign('create_new', $User->hasPermission('ads', 'create'));

# zpracovani pagination
$Smarty->assign(
    'pagination',
    pagination(
        $APPD->data['APP']['DEFAULT_ITEMS_PER_PAGE'],
        $Ad->getRowsCount(),
        (isset($_GET['p']) && intval($_GET['p'])) ? (int) $_GET['p'] : 1,
        $APPD->data['APP']['DEFAULT_ITEMS_PER_PAGE_DOTS']
    )
);
