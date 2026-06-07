<?php

use Janmensik\Jmlib\AppData;
use Janmensik\Jmlib\Database;

/** @var Database $DB */

# *******************************************************************
# NEEDS + Global
# *******************************************************************

require_once(__DIR__ . '/../../include/class.Vehicle.php');

if (!isset($Vehicle)) {
    $Vehicle = new \PozarniPoplach\Vehicle($DB);
}

$APPD = AppData::getInstance();
$APPD->setData('PAGE', 'vehicle-edit');

# ...................................................................
# Access control READ
if (!$User->hasPermission('vehicles', 'read')) {
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
    $data = $Vehicle->getId($id);
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

# ...................................................................
#  Delete (unregister) vehicle
if (!empty($_POST['delete']) && isset($data['id']) && $User->hasPermission('vehicles', 'delete')) {
    if ($Vehicle->delete($data['id'])) {
        $APPD->MESSAGES['deleted']['vehicle'] = $data['name'] ?? $data['callsign'];
        header('Location: ' . $APPD->getData('BASE_URL') .  '/' . $APPD->data['CONFIG']['vehicles_url']);
    } else {
        $APPD->MESSAGES['error']['vehicle'] = 'not deleted';
        header('Location: ' . $APPD->getData('BASE_URL') .  '/' . $APPD->data['CONFIG']['vehicles_url'] . '/' . $data['id']);
    }
    header("Connection: close");
    return;
}

# ...................................................................

if ($_POST) {
    # 1. Initialize data (if editing)
    if ($id != 'new') {
        $Vehicle->fillData($id);
    }

    # 2. Map from POST
    $Vehicle->mapFromPost($_POST);

    # 3. Validate
    $errors = $Vehicle->validate();

    # error in validation
    if ($errors) {
        $APPD->MESSAGES['error'] = $errors;
    } else {
        # 4. Save
        $item_id = $Vehicle->setter($id == 'new' ? null : $id);

        if ($item_id) {
            $APPD->MESSAGES['saved']['vehicle'] = $Vehicle->data['name'] ?? $Vehicle->data['callsign'];
            $APPD->MESSAGES['saved']['id'] = $item_id;

            $APPD->hibernateMessages();
            header('Location: ' . $APPD->getData('BASE_URL') . '/' . $APPD->data['CONFIG']['vehicles_url']);
            header("Connection: close");
            return;
        } else {
            $APPD->MESSAGES['error']['vehicle'] = 'not saved';
        }
    }
}

# *******************************************************************
# OUTPUT
# *******************************************************************

# If we have errors, merge POST data back into $data so the form isn't empty
if ($_POST && isset($errors)) {
    if (!is_array($data)) {
        $data = [];
    }
    foreach ($Vehicle->data as $key => $val) {
        $data[$key] = $val;
    }
}

$Smarty->assign('data', $data);

# Units for select
require_once(__DIR__ . '/../../include/class.Unit.php');
if (!isset($Unit)) {
    $Unit = new \PozarniPoplach\Unit($DB);
}
$Smarty->assign('units', $Unit->get(null, '3'));

# Vehicle Types for select
require_once(__DIR__ . '/../../include/class.VehicleType.php');
if (!isset($VehicleType)) {
    $VehicleType = new \PozarniPoplach\VehicleType($DB);
}
$Smarty->assign('vehicle_types', $VehicleType->get(null, '3,2'));
