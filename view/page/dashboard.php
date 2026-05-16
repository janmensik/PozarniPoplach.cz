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
// 	$Reservation = new Reservation($DB);

// if (!isset($Pricelist))
// 	$Pricelist = new Pricelist($DB);

// if (!isset($Gatecode))
// 	$Gatecode = new Gatecode($DB);

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
// $capacity['max'] = $Pricelist->getMaxCapacity();
// $capacity['used'] = $Reservation->getCurrentParked();
// $capacity['free'] = $capacity['max'] - $capacity['used'];
// $capacity['used_percent'] = floor($capacity['used'] / $capacity['max'] * 100);
$capacity = null;
$Smarty->assign('capacity', $capacity);

# ...................................................................
# Occupancy chart data
// $Smarty->assign('occupancyOutlook', $Reservation->getOccupancyDailyOutlook($capacity['used']));

# ...................................................................
# get parked (for today)
$where = null;
$where[] = 'r.status IN ("parked")';
$where[] = 'r.checkin ' . $where_time_condition;
// $parked = $Reservation->get($where, -6, -1);
$parked = null;

// $Smarty->assign('parked_count', $Reservation->getRowsCount());
$Smarty->assign('parked', $parked);

# ...................................................................
# get check-ins (for today)
$where = null;
$where[] = 'r.status IN ("new", "pending", "paid")';
$where[] = 'r.checkin ' . $where_time_condition;
// $checkins = $Reservation->get($where, 6, -1);