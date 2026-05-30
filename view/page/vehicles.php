<?php

use Janmensik\Jmlib\AppData;
use Janmensik\Jmlib\Database;

/** @var Database $DB */

# *******************************************************************
# NEEDS + Global
# *******************************************************************

$APPD = AppData::getInstance();
$APPD->setData('PAGE', 'vehicles');

# ...................................................................
# Access control READ
if (!$User->hasPermission('vehicles', 'read')) {
    header('HTTP/1.1 403 Forbidden');
    $APPD->setData('ERROR', '403');
    return;
}

# ...................................................................
# load up
require_once(__DIR__ . '/../../include/class.Vehicle.php');

if (!isset($Vehicle)) {
    $Vehicle = new \PozarniPoplach\Vehicle($DB);
}

# ...................................................................
# PageSchema I/O
$_GET = $User->setPageSchema('vehicles', $_GET);

# *******************************************************************
# PROGRAM
# *******************************************************************

$where = null;

# *******************************************************************
# OUTPUT
# *******************************************************************

# nacteni
$data = $Vehicle->get($where, (isset($_GET['order']) ? $_GET['order'] : null), $APPD->data['APP']['DEFAULT_ITEMS_PER_PAGE'], (isset($_GET['p']) && intval($_GET['p'])) ? (int) $_GET['p'] : null);
$group_data = $Vehicle->getGroupTotal($where);

$Smarty->assign('data', $data);
$Smarty->assign('data_total', $Vehicle->getTotal($data, array('id' => 'count')));
$Smarty->assign('data_group_total', $group_data);
$Smarty->assign('data_count', $Vehicle->getRowsCount());
if (isset($_GET['order'])) {
    $Smarty->assign('data_extra', $Vehicle->getExtra($_GET['order']));
}

# access to create new?
$Smarty->assign('create_new', $User->hasPermission('vehicles', 'create'));

# zpracovani pagination
$Smarty->assign(
    'pagination',
    pagination(
        $APPD->data['APP']['DEFAULT_ITEMS_PER_PAGE'],
        $Vehicle->getRowsCount(),
        (isset($_GET['p']) && intval($_GET['p'])) ? (int) $_GET['p'] : 1,
        $APPD->data['APP']['DEFAULT_ITEMS_PER_PAGE_DOTS']
    )
);
