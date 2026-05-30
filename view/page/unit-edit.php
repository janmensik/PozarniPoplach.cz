<?php

use Janmensik\Jmlib\AppData;
use Janmensik\Jmlib\Database;

/** @var Database $DB */

# *******************************************************************
# NEEDS + Global
# *******************************************************************

require_once(__DIR__ . '/../../include/class.Unit.php');

if (!isset($Unit)) {
    $Unit = new \PozarniPoplach\Unit($DB);
}

$APPD = AppData::getInstance();
$APPD->setData('PAGE', 'unit-edit');

# ...................................................................
# Access control READ
if (!$User->hasPermission('units', 'read')) {
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
    $data = $Unit->getId($id);
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
#  Create fake Dispatch Alarm (for testing)
if (!empty($_POST['create_test']) && $data['id'] && $User->hasPermission('dispatches', 'create')) {
    require_once(__DIR__ . '/../../include/class.Dispatch.php');
    if (!isset($Dispatch)) {
        $Dispatch = new \PozarniPoplach\Dispatch($DB);
    }

    if ($Dispatch->createTestAlarm($data['id'])) {
        $APPD->MESSAGES['created']['dispatch'] = 'Test dispatch created';
        header('Location: ' . $APPD->getData('BASE_URL') .  '/' . $APPD->data['CONFIG']['units_url'] . '/' . $data['id']);
    } else {
        $APPD->MESSAGES['created']['dispatch'] = 'Test dispatch NOT created';
        header('Location: ' . $APPD->getData('BASE_URL') .  '/' . $APPD->data['CONFIG']['units_url'] . '/' . $data['id']);
    }
    header("Connection: close");
    return;
}

# ...................................................................

if ($_POST) {
    # 1. Initialize data (if editing)
    if ($id != 'new') {
        $Unit->fillData($id);
    }

    # 2. Map from POST
    $Unit->mapFromPost($_POST);

    # 3. Validate
    $errors = $Unit->validate();

    # error in validation
    if ($errors) {
        $APPD->MESSAGES['error'] = $errors;
    } else {
        # 4. Save
        $item_id = $Unit->setter($id == 'new' ? null : $id);

        if ($item_id) {
            $APPD->MESSAGES['saved']['unit'] = $Unit->data['fullname'];
            $APPD->MESSAGES['saved']['id'] = $item_id;

            $APPD->hibernateMessages();
            header('Location: ' . $APPD->getData('BASE_URL') . '/' . $APPD->data['CONFIG']['units_url']);
            header("Connection: close");
            return;
        } else {
            $APPD->MESSAGES['error']['unit'] = 'not saved';
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
    foreach ($Unit->data as $key => $val) {
        $data[$key] = $val;
    }
}

$Smarty->assign('data', $data);
$Smarty->assign('Unit', $Unit);

$regions = $Unit->getRegions();
$Smarty->assign('regions', $regions);
$Smarty->assign('regions_json', json_encode($regions));
