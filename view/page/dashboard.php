<?php

use Janmensik\Jmlib\AppData;
use Janmensik\Jmlib\Database;

/** @var Database $DB */

# *******************************************************************
# NEEDS + Global
# *******************************************************************

$APPD = AppData::getInstance();
$APPD->setData('PAGE', 'dashboard');

# ...................................................................
# Access control READ
if (!$User->hasPermission('dashboard', 'read')) {
    header('HTTP/1.1 403 Forbidden');
    $APPD->setData('ERROR', '403');
    return;
}

# ...................................................................
# load up
require_once(__DIR__ . '/../../include/class.Dispatch.php');
require_once(__DIR__ . '/../../include/class.ImportLog.php');
require_once(__DIR__ . '/../../include/class.EventType.php');
require_once(__DIR__ . '/../../include/class.Ad.php');

if (!isset($Dispatch)) {
    $Dispatch = new \PozarniPoplach\Dispatch($DB);
}
if (!isset($ImportLog)) {
    $ImportLog = new \PozarniPoplach\ImportLog($DB);
}
if (!isset($EventType)) {
    $EventType = new \PozarniPoplach\EventType($DB);
}
if (!isset($Ad)) {
    $Ad = new \PozarniPoplach\Ad($DB);
}

# ...................................................................
# PageSchema I/O
$_GET = $User->setPageSchema('dashboard', $_GET);

# *******************************************************************
# PROGRAM
# *******************************************************************

# 1. Dispatch flow & counts
$dispatch_stats = $Dispatch->getStats();

# Ingest / Import logs stats
$import_stats = $ImportLog->getStats();

# Get last 5 import logs
$import_logs = $ImportLog->getRecentLogs(5);

# 2. Dispatches with unregistered vehicles
$unregistered_vehicles = $Dispatch->getUnregisteredVehicles();

# 3. Event types without proper icons / registration
$missing_event_icons = $EventType->getMissingIcons();

$dispatches_bad_events = $Dispatch->getDispatchesWithBadEvents();

# 4. Ads views & clicks stats
$ad_totals = $Ad->getAdTotals();

if ($ad_totals['total_views'] > 0) {
    $ad_totals['ctr'] = round(($ad_totals['total_clicks'] / $ad_totals['total_views']) * 100, 2);
} else {
    $ad_totals['ctr'] = 0;
}

$ad_campaigns = $Ad->getActiveReport();

# *******************************************************************
# OUTPUT
# *******************************************************************
$Smarty->assign('dispatch_stats', $dispatch_stats);
$Smarty->assign('import_stats', $import_stats);
$Smarty->assign('import_logs', $import_logs);
$Smarty->assign('unregistered_vehicles', $unregistered_vehicles);
$Smarty->assign('missing_event_icons', $missing_event_icons);
$Smarty->assign('dispatches_bad_events', $dispatches_bad_events);
$Smarty->assign('ad_totals', $ad_totals);
$Smarty->assign('ad_campaigns', $ad_campaigns);
