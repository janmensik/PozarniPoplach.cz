<?php

use Janmensik\Jmlib\AppData;
use Janmensik\Jmlib\Database;

/** @var Database $DB */

# *******************************************************************
# NEEDS + Global
# *******************************************************************

require_once(__DIR__ . '/../../include/class.Ad.php');

if (!isset($Ad)) {
    $Ad = new \PozarniPoplach\Ad($DB);
}

$APPD = AppData::getInstance();
$APPD->setData('PAGE', 'ad-edit');

# ...................................................................
# Access control READ
if (!$User->hasPermission('ads', 'read')) {
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
    $data = $Ad->getId($id);
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
    $errors = [];

    # 1. Initialize data (if editing)
    if ($id != 'new') {
        $Ad->fillData($id);
    }

    # 2. Map from POST
    $Ad->mapFromPost($_POST);

    # --- File upload handling ---
    if (!empty($_FILES['banner_image']['name']) && $_FILES['banner_image']['error'] == UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../../upload/ads/';
        $extension = strtolower(pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['banner_image']['tmp_name']);
        finfo_close($finfo);

        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];

        if (in_array($extension, $allowedExtensions) && in_array($mime, $allowedMimeTypes)) {
            $filename = uniqid('ad_') . '.' . $extension;
            $uploadFile = $uploadDir . $filename;

            if (move_uploaded_file($_FILES['banner_image']['tmp_name'], $uploadFile)) {
                $baseUrl = $APPD->getData('BASE_URL');
                $Ad->data['banner_image_url'] = $baseUrl . '/upload/ads/' . $filename;
            } else {
                $errors['banner_image'] = 'Chyba při nahrávání obrázku.';
            }
        } else {
            $errors['banner_image'] = 'Nepodporovaný formát souboru.';
        }
    }
    # ----------------------------

    # 3. Validate
    $errors = array_merge($errors, $Ad->validate());

    # error in validation
    if ($errors) {
        $APPD->MESSAGES['error'] = $errors;
    } else {
        # 4. Save
        $item_id = $Ad->setter($id == 'new' ? null : $id);

        if ($item_id) {
            $APPD->MESSAGES['saved']['ad'] = $Ad->data['title'] ?? 'ad';
            $APPD->MESSAGES['saved']['id'] = $item_id;

            $APPD->hibernateMessages();
            header('Location: ' . $APPD->getData('BASE_URL') . '/' . $APPD->data['CONFIG']['ads_url']);
            header("Connection: close");
            return;
        } else {
            $APPD->MESSAGES['error']['ad'] = 'not saved';
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
    foreach ($Ad->data as $key => $val) {
        $data[$key] = $val;
    }
}

$Smarty->assign('data', $data);
$Smarty->assign('Ad', $Ad);

# load all top level event types for parent selection
require_once(__DIR__ . '/../../include/class.Advertiser.php');

if (!isset($Advertiser)) {
    $Advertiser = new \PozarniPoplach\Advertiser($DB);
}
$Smarty->assign('advertisers', $Advertiser->get(null, "name ASC"));
