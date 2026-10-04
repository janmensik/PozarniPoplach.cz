<?php

use Janmensik\Jmlib\AppData;

/** @var \Janmensik\Jmlib\Database $DB */
/** @var \PozarniPoplach\User $User */

# *******************************************************************
# NEEDS + Global
# *******************************************************************

$APPD = AppData::getInstance();
$APPD->setData('PAGE', 'activate');

# *******************************************************************
# PROGRAM
# *******************************************************************

// 1. Retrieve device code from route capture or POST fallback
$device_code = $APPD->getData('DEVICE_CODE') ?? $_GET['code'] ?? $_POST['code'] ?? null;
if (!empty($device_code)) {
    $device_code = strtoupper(trim($device_code));
}

// 2. Auth guard — must be logged in
if (!$User->getUser()) {
    $redirectTarget = '/activate' . (!empty($device_code) ? '/' . urlencode($device_code) : '');
    $_SESSION['login_redirect'] = $redirectTarget;
    header('Location: ' . $APPD->getData('BASE_URL') . '/login');
    header('Connection: close');
    return;
}

$currentUser = $User->getUser();

if (!isset($DeviceAuth)) {
    $DeviceAuth = new \PozarniPoplach\DeviceAuth($DB);
}

// 3. CSRF token
if (empty($_SESSION['csrf_token_device'])) {
    $_SESSION['csrf_token_device'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token_device'];

$error   = null;
$session = null;

// 4. Validate device session
if (!empty($device_code)) {
    $session = $DeviceAuth->checkSessionStatus($device_code);
    if (!$session || $session['status'] !== 'pending') {
        $error   = 'Neplatný nebo prošlý aktivační kód.';
        $session = null;
    }
}

// 5. Load units the logged-in user is allowed to assign
$units = $User->getUnitsForUser();

// 6. Handle POST submission
if ($session && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token_device']) || !hash_equals($_SESSION['csrf_token_device'], (string)$_POST['csrf_token_device'])) {
        $error = 'Neplatný bezpečnostní token (CSRF). Zkuste to prosím znovu.';
    } elseif (!empty($_POST['unit_id'])) {
        $unitId     = (int)$_POST['unit_id'];
        $allowedIds = array_map('intval', array_column($units, 'id'));

        if (!in_array($unitId, $allowedIds, true)) {
            $error = 'Tato jednotka vám není přiřazena.';
        } else {
            if ($DeviceAuth->linkSessionToUnit($device_code, $unitId, $_POST['device_name'] ?? null, (int)$currentUser['id'])) {
                // Rotate CSRF token after success
                unset($_SESSION['csrf_token_device']);
                $Smarty->assign('success', true);
            } else {
                $error = 'Nepodařilo se autorizovat zařízení. Kód mohl vypršet — zkuste to znovu.';
            }
        }
    }
}

# *******************************************************************
# OUTPUT
# *******************************************************************

$Smarty->assign('units', $units);
$Smarty->assign('device_code', $device_code);
$Smarty->assign('session_data', $session);
$Smarty->assign('error', $error);
$Smarty->assign('current_user', $currentUser);
$Smarty->assign('csrf_token_device', $csrf_token);

// Note: index.php renders page.activate.html automatically
