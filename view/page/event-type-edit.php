<?php

use Janmensik\Jmlib\AppData;
use Janmensik\Jmlib\Database;

/** @var Database $DB */

# *******************************************************************
# NEEDS + Global
# *******************************************************************

require_once(__DIR__ . '/../../include/class.EventType.php');

if (!isset($EventType)) {
    $EventType = new \PozarniPoplach\EventType($DB);
}

$APPD = AppData::getInstance();
$APPD->setData('PAGE', 'event-type-edit');

# ...................................................................
# Access control READ
if (!$User->hasPermission('event-types', 'read')) {
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
    $data = $EventType->getId($id);
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

if ($_POST) {
    # 1. Initialize data (if editing)
    if ($id != 'new') {
        $EventType->fillData($id);
    }

    # 2. Map from POST
    $EventType->mapFromPost($_POST);

    # 3. Validate
    $errors = $EventType->validate();

    # error in validation
    if ($errors) {
        $APPD->MESSAGES['stop'] = $errors;
    } else {
        # 4. Save
        $item_id = $EventType->setter($id == 'new' ? null : $id);

        if ($item_id) {
            $APPD->MESSAGES['saved']['event_type'] = $EventType->data['type'];
            $APPD->MESSAGES['saved']['id'] = $item_id;

            $APPD->hibernateMessages();
            header('Location: ' . $APPD->getData('BASE_URL') . '/' . $APPD->data['CONFIG']['event_types_url']);
            header("Connection: close");
            exit();
        } else {
            $APPD->MESSAGES['stop']['event_type'] = 'not saved';
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
    foreach ($EventType->data as $key => $val) {
        $data[$key] = $val;
    }
}

$Smarty->assign('data', $data);

# load all top level event types for parent selection
$Smarty->assign('parent_event_types', $EventType->get("et.parent_id IS NULL", "name ASC"));
