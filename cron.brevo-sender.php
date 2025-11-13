<?php
# *******************************************************************
# NEEDS
# *******************************************************************

require_once(__DIR__ . '/local.php'); # hesla, atd.


require_once(__DIR__ . '/lib/class.Database.php'); # SQL handler
require_once(__DIR__ . '/lib/class.Modul.php'); # Base modul

require_once(__DIR__ . '/include/class.Reservation.php');
require_once(__DIR__ . '/include/class.Gatecode.php');
require_once(__DIR__ . '/include/class.MailSchedule.php');

# composer autoloader
require 'vendor/autoload.php';

# *******************************************************************
# DEFINICE, INICIALIZACE
# *******************************************************************

date_default_timezone_set('Europe/Prague');

mb_internal_encoding("UTF-8");

# spusteni tridy Database
$DB = new Database($LOCAL['SQL']['HOST'], $LOCAL['SQL']['DATABASE'], $LOCAL['SQL']['USER'], $LOCAL['SQL']['PASSWORD']);
$DB->query('SET CHARACTER SET utf8;');

if (!isset($Reservation))
    $Reservation = new Reservation($DB);

if (!isset($Gatecode))
    $Gatecode = new Gatecode($DB);

if (!isset($MailSchedule))
    $MailSchedule = new MailSchedule($DB);

# number of emails per cycle
$max_emails = 20;

$output = 0;
$error = 0;

# Brevo
$brevo_config = Brevo\Client\Configuration::getDefaultConfiguration()->setApiKey('api-key', $LOCAL['BREVO_API_KEY']);
$Brevo = new Brevo\Client\Api\TransactionalEmailsApi(new GuzzleHttp\Client(), $brevo_config);

# *******************************************************************
# PROGRAM
# *******************************************************************

# get emails
$emails = $MailSchedule->get(array('ms.status="scheduled"', 'ms.plan <= NOW()'), null, $max_emails);

if (is_array($emails)) {
    foreach ($emails as $email) {
        $data = null;
        $email_brevo = null;

        $data = $Reservation->getId($email['reservation_id']);

        $gatecode_future = $Gatecode->getCode($data['checkin']);

        # Brevo API
        $email_brevo = new \Brevo\Client\Model\SendSmtpEmail([
            'to' => [['name' => $data['name'], 'email' => $data['email']]],
            'bcc' => [['name' => 'ROBOT Holiday Parking', 'email' => $LOCAL['MASTER_EMAIL']]],
            'templateId' => (int) $email['template'],
            'params' => [
                "RESERVATION_ID" => $data['id'],
                "CLIENT_NAME" => $data['name'],
                "CLIENT_EMAIL" => $data['email'],
                "CLIENT_PHONE" => $data['phone'],
                "CLIENT_CAR" => $data['car'],
                "CLIENT_LICENSEPLATE" => $data['license_plate'],
                "ARRIVAL_FULL" => date('j. n. Y H:i', $data['checkin']),
                "DEPARTURE_FULL" => date('j. n. Y H:i', $data['checkout']),
                "DAYS" => $data['days'],
                "RESERVATIONDATE_FULL" => date('j. n. Y H:i'),
                "RESERVATION_GATECODE" => ($gatecode_future ? $gatecode_future : '159') . " a šipka nahoru",
                "PROMOCODE" => $data['promo'],
                "PRICE" => $data['price'],
                "PERSONS" => $data['persons'],
                "CHILD_SEAT" => $data['child_seat'],
                "PAYLINK" => "https://example.com/paylink"
            ]
        ]);

        try {
            $result = $Brevo->sendTransacEmail($email_brevo);
            $output++;

            # updated mail as sent
            $MailSchedule->setSentMail($email['id']);
        } catch (Exception $e) {
            $error++;
            echo 'Exception when calling TransactionalEmailsApi->sendTransacEmail: ', $e->getMessage(), PHP_EOL;

        }
    }
}

# *******************************************************************
# OUTPUT
# *******************************************************************

echo ('Loaded '.(is_array ($emails) ? count($emails) : '0') . ' emails out of max ' . $max_emails . '. Sent '.$output.' emails, ' . $error . ' ended with errors.');