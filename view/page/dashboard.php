<?php

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
// require_once(__DIR__ . '/../../include/class.Reservation.php');
// require_once(__DIR__ . '/../../include/class.Pricelist.php');
// require_once(__DIR__ . '/../../include/class.Gatecode.php');

// if (!isset($Reservation))
//  $Reservation = new Reservation($DB);

// if (!isset($Pricelist))
//  $Pricelist = new Pricelist($DB);

// if (!isset($Gatecode))
//  $Gatecode = new Gatecode($DB);

# ...................................................................
# PageSchema I/O
$_GET = $User->setPageSchema('dashboard', $_GET);

# *******************************************************************
# PROGRAM
# *******************************************************************

# 1. Dispatch flow & counts
$dispatch_stats = [];
$dispatch_stats['total'] = (int)$DB->getResult($DB->query("SELECT COUNT(*) FROM dispatch", __METHOD__));
$dispatch_stats['last_7d'] = (int)$DB->getResult($DB->query("SELECT COUNT(*) FROM dispatch WHERE received >= NOW() - INTERVAL 7 DAY", __METHOD__));
$dispatch_stats['last_30d'] = (int)$DB->getResult($DB->query("SELECT COUNT(*) FROM dispatch WHERE received >= NOW() - INTERVAL 30 DAY", __METHOD__));

# Ingest / Import logs stats
$import_stats = [];
$import_stats['total_runs'] = (int)$DB->getResult($DB->query("SELECT COUNT(*) FROM import_log", __METHOD__));
$import_stats['success_runs'] = (int)$DB->getResult($DB->query("SELECT COUNT(*) FROM import_log WHERE status = 'success'", __METHOD__));
$import_stats['error_runs'] = (int)$DB->getResult($DB->query("SELECT COUNT(*) FROM import_log WHERE status = 'error'", __METHOD__));
$import_stats['emails_processed'] = (int)$DB->getResult($DB->query("SELECT IFNULL(SUM(emails_processed), 0) FROM import_log", __METHOD__));
$import_stats['dispatches_created'] = (int)$DB->getResult($DB->query("SELECT IFNULL(SUM(dispatches_created), 0) FROM import_log", __METHOD__));

# Get last 5 import logs
$import_logs = $DB->getAllRows($DB->query("SELECT *, UNIX_TIMESTAMP(started_at) AS started_at_ts, UNIX_TIMESTAMP(finished_at) AS finished_at_ts FROM import_log ORDER BY started_at DESC LIMIT 5", __METHOD__));

# 2. Dispatches with unregistered vehicles
$unregistered_vehicles = $DB->getAllRows($DB->query("
    SELECT d.id AS dispatch_id, d.event, d.received, UNIX_TIMESTAMP(d.received) AS received_ts, duv.fullname AS parsed_car_name, u.fullname AS unit_name, u.id AS unit_id
    FROM dispatch_unit_vehicle duv
    JOIN dispatch d ON duv.dispatch_id = d.id
    JOIN unit u ON d.unit_id = u.id
    WHERE duv.unit_vehicle_id IS NULL
    ORDER BY d.received DESC
", __METHOD__));

# 3. Event types without proper icons / registration
$missing_event_icons = $DB->getAllRows($DB->query("
    SELECT id, name, icon FROM event_type WHERE icon IS NULL OR icon = ''
", __METHOD__));

$dispatches_bad_events = $DB->getAllRows($DB->query("
    SELECT d.id AS dispatch_id, d.event, d.event_subtype, d.received, UNIX_TIMESTAMP(d.received) AS received_ts, u.fullname AS unit_name, u.id AS unit_id
    FROM dispatch d
    LEFT JOIN event_type et ON d.event_id = et.id
    LEFT JOIN unit u ON d.unit_id = u.id
    WHERE d.event_id IS NULL OR et.icon IS NULL OR et.icon = ''
    ORDER BY d.received DESC
", __METHOD__));

# 4. Ads views & clicks stats
$ad_totals = $DB->getRow($DB->query("
    SELECT IFNULL(SUM(display_count), 0) AS total_views, IFNULL(SUM(link_count), 0) AS total_clicks FROM advert_hit
", __METHOD__));

if ($ad_totals['total_views'] > 0) {
    $ad_totals['ctr'] = round(($ad_totals['total_clicks'] / $ad_totals['total_views']) * 100, 2);
} else {
    $ad_totals['ctr'] = 0;
}

$ad_campaigns = $DB->getAllRows($DB->query("
    SELECT ad.id, ad.title, ad.status, adc.name AS advertiser_name, 
           IFNULL(SUM(adh.display_count), 0) AS display_count_total, 
           IFNULL(SUM(adh.link_count), 0) AS link_count_total
    FROM advert ad 
    JOIN advertiser adc ON ad.advertiser_id = adc.id 
    LEFT JOIN advert_hit adh ON ad.id = adh.advert_id 
    GROUP BY ad.id
    ORDER BY display_count_total DESC
", __METHOD__));

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

