<?php

use Janmensik\Jmlib\AppData;
use Janmensik\Jmlib\Database;

/** @var Database $DB */

# *******************************************************************
# NEEDS + Global
# *******************************************************************

require_once(__DIR__ . '/../../include/class.Dispatch.php');

if (!isset($Dispatch)) {
    $Dispatch = new \PozarniPoplach\Dispatch($DB);
}

$APPD = AppData::getInstance();
$APPD->setData('PAGE', 'dispatch');

# ...................................................................
# Access control READ
if (!$User->hasPermission('dispatches', 'read')) {
    header('HTTP/1.1 403 Forbidden');
    $APPD->setData('ERROR', '403');
    return;
}

# *******************************************************************
# PROGRAM
# *******************************************************************

# Initialize ID safely
$id = $id ?? null;

# data load
if (!empty($id) && $id != 'new') {
    $data = $Dispatch->getId($id);
} else {
    $data = null;
}

# not found
if (!$data && (!empty($id) && $id != 'new')) {
    header('HTTP/1.1 404 Not Found');
    $APPD->setData('ERROR', '404');
    $APPD->setData('PAGE', '404');
    return;
}

# *******************************************************************
# FORM Sanitation & Validation
# *******************************************************************


# *******************************************************************
# OUTPUT
# *******************************************************************

$Smarty->assign('data', $data);
$Smarty->assign('data_b', $Dispatch->beautifulLastDispatch($Dispatch->getDispatch($id)));
//$Smarty->assign('Dispatch', $Dispatch);
