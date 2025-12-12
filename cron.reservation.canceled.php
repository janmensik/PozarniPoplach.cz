<?php
# *******************************************************************
# NEEDS
# *******************************************************************

require_once(__DIR__ . '/inc.startup.php');

require_once(__DIR__ . '/lib/class.Database.php'); # SQL handler
require_once(__DIR__ . '/lib/class.Modul.php'); # Base modul

require_once(__DIR__ . '/include/class.Reservation.php');
require_once(__DIR__ . '/include/class.MailSchedule.php');

# *******************************************************************
# DEFINICE, INICIALIZACE
# *******************************************************************

date_default_timezone_set('Europe/Prague');

mb_internal_encoding("UTF-8");

# spusteni tridy Database
$DB = new Database($_ENV['SQL_HOST'], $_ENV['SQL_DATABASE'], $_ENV['SQL_USER'], $_ENV['SQL_PASSWORD']);
$DB->query('SET CHARACTER SET utf8;');

if (!isset($Reservation))
    $Reservation = new Reservation($DB);

if (!isset($MailSchedule))
    $MailSchedule = new MailSchedule($DB);

# number of emails per cycle
$max_reservations = 20;

# number of hours after checkin before switching to noshow
$after_checkin = 24;

$output = 0;
$canceled = 0;
$emails_canceled = 0;

$where = array();

# *******************************************************************
# PROGRAM
# *******************************************************************

# get reservation in need of change to noshow
# rules: 
# > 6 hours after checkin 
$where[] = 'r.checkin < NOW() - INTERVAL ' . $after_checkin . ' HOUR';
# new not paid (noshow)
$where[] = 'r.status IN ("noshow")';


//$data = $Reservation->get($where, null, $max_reservations);

if (is_array($data) && count($data) > 0) {
    foreach ($data as $res) {
        if ($Reservation->set(array('status' => '"canceled"'), $res['id']))
            $canceled++;

        $emails_canceled += $MailSchedule->cancelAllMailsByReservation($res['id']);

        $output++;
    }
}

# *******************************************************************
# OUTPUT
# *******************************************************************

// print_r($DB->messages);

echo ('Loaded ' . $output . ' reservations, ' . $canceled . ' set to CANCELED. Canceled ' . $emails_canceled . ' emails.');
