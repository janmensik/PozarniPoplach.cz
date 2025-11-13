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
require_once(__DIR__ . '/../../include/class.Reservation.php');
require_once(__DIR__ . '/../../include/class.Pricelist.php');
require_once(__DIR__ . '/../../include/class.Gatecode.php');

if (!isset($Reservation))
	$Reservation = new Reservation($DB);

if (!isset($Pricelist))
	$Pricelist = new Pricelist($DB);

if (!isset($Gatecode))
	$Gatecode = new Gatecode($DB);

# ...................................................................
# PageSchema I/O
$_GET = $User->setPageSchema('dashboard', $_GET);

# *******************************************************************
# PROGRAM
# *******************************************************************


# *******************************************************************
# OUTPUT
# *******************************************************************

# global SQL time condition to get results for next 24 hours stating today or last 4 hours
$where_time_condition = 'BETWEEN IF(HOUR(NOW()) < 4, NOW() - INTERVAL 4 HOUR, CURDATE()) AND NOW() + INTERVAL 24 HOUR';

# get current parking occupancy (default pricelist capacity - current parking)
$capacity['max'] = $Pricelist->getMaxCapacity();
$capacity['used'] = $Reservation->getCurrentParked();
$capacity['free'] = $capacity['max'] - $capacity['used'];
$capacity['used_percent'] = floor($capacity['used'] / $capacity['max'] * 100);

$Smarty->assign('capacity', $capacity);

# ...................................................................
# Occupancy chart data
$Smarty->assign('occupancyOutlook', $Reservation->getOccupancyDailyOutlook($capacity['used']));

# ...................................................................
# get parked (for today)
$where = null;
$where[] = 'r.status IN ("parked")';
$where[] = 'r.checkin ' . $where_time_condition;
$parked = $Reservation->get($where, -6, -1);

$Smarty->assign('parked_count', $Reservation->getRowsCount());
$Smarty->assign('parked', $parked);

# ...................................................................
# get check-ins (for today)
$where = null;
$where[] = 'r.status IN ("new", "pending", "paid")';
$where[] = 'r.checkin ' . $where_time_condition;
$checkins = $Reservation->get($where, 6, -1);

$Smarty->assign('checkins_count', $Reservation->getRowsCount());
$Smarty->assign('checkins', $checkins);

# ...................................................................
# get noshows (for today)
$where = null;
$where[] = 'r.status="noshow"';
$where[] = 'r.checkin ' . $where_time_condition;
$noshows = $Reservation->get($where, 6, -1);

$Smarty->assign('noshows_count', $Reservation->getRowsCount());
$Smarty->assign('noshows', $noshows);

# ...................................................................
# get closed (for today)
$where = null;
$where[] = 'r.status="closed"';
$where[] = 'r.checkout ' . $where_time_condition;
$closed = $Reservation->get($where, -7, -1);

$Smarty->assign('closed_count', $Reservation->getRowsCount());
$Smarty->assign('closed', $closed);

# ...................................................................
# get check-outs (for today)
$where = null;
$where[] = 'r.status="parked"';
$where[] = 'r.checkout < NOW() + INTERVAL 24 HOUR';
$checkouts = $Reservation->get($where, 7, -1);

$Smarty->assign('checkouts_count', $Reservation->getRowsCount());
$Smarty->assign('checkouts', $checkouts);

# ...................................................................
# get canceled (for today)
$where = null;
$where[] = 'r.status="canceled"';
$where[] = 'r.last_change '. $where_time_condition;
$canceled = $Reservation->get($where, -3, -1);

$Smarty->assign('canceled_count', $Reservation->getRowsCount());
$Smarty->assign('canceled', $canceled);

# ...................................................................
# get recent - new (for today)
$where = null;
$where[] = 'r.created '. $where_time_condition;
$recent = $Reservation->get($where, -2, -1);

$Smarty->assign('recent_count', $Reservation->getRowsCount());
$Smarty->assign('recent', $recent);

# ...................................................................
# get active Gate code and next
$Smarty->assign('gatecode', $Gatecode->getCodeActivated());
$Smarty->assign('gatecode_next', $Gatecode->getNextPending());

# ...................................................................

$Smarty->assign('reservation_texts', $Reservation->text);