<?php
# *******************************************************************
# NEEDS
# *******************************************************************

require_once(__DIR__ . '/inc.startup.php');

error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

require_once(__DIR__ . '/lib/functions/function.parseFloat.php'); # prevod "minuly mesic" na time interval

require_once(__DIR__ . '/lib/class.Database.php'); # SQL handler
require_once(__DIR__ . '/lib/class.Modul.php'); # Base modul

require_once(__DIR__ . '/include/class.Dispatch.php');

# *******************************************************************
# DEFINICE, INICIALIZACE
# *******************************************************************

date_default_timezone_set('Europe/Prague');
mb_internal_encoding("UTF-8");

use PhpImap\Mailbox;
use PhpImap\Exceptions\ConnectionException;

# spusteni tridy Database
$DB = new Database($_ENV['SQL_HOST'], $_ENV['SQL_DATABASE'], $_ENV['SQL_USER'], $_ENV['SQL_PASSWORD']);
$DB->query('SET CHARACTER SET utf8;');


// Create PhpImap\Mailbox instance for all further actions
$mailbox = new PhpImap\Mailbox(
	$_ENV['IMAP_HOSTNAME'], // IMAP server and mailbox folder
	$_ENV['IMAP_USERNAME'], // Username for the before configured mailbox
	$_ENV['IMAP_PASSWORD'], // Password for the before configured username
	false,
	'UTF-8'
);


if (!isset($Dispatch))
	$Dispatch = new Dispatch($DB);

# *******************************************************************
# PROGRAM
# *******************************************************************

$mailbox->setAttachmentsIgnore(true);

try {
	$mail_ids = $mailbox->searchMailbox('ALL');
} catch (ConnectionException $ex) {
	die("IMAP connection failed: " . $ex->getMessage());
} catch (Exception $ex) {
	die("An error occurred: " . $ex->getMessage());
}


$counter = $saved = 0;
$timer = microtime(true);
$total_getmail_time = 0;

$output = array();

foreach ($mail_ids as $mail_id) {
	$set = null;
	$data = null;
	$unit_registration = null;
	$id = null;

	$iteration_timer = microtime(true);

	$email = $mailbox->getMail(
		$mail_id, // ID of the email, you want to get
		false // Do NOT mark emails as seen (optional)
	);

	$getmail_time = microtime(true) - $iteration_timer;
	$total_getmail_time += $getmail_time;

	// $data = $Dispatch->parseDispatchHtml (iconv('Windows-1250', 'UTF-8', $email->textHtml));
	$unit_registration = $Dispatch->extractUnitRegistration($email->toString);

	$data = $Dispatch->linkParsedDispatch($Dispatch->parseDispatchHtml($email->textHtml), $unit_registration);

	$iteration_total_time = microtime(true) - $iteration_timer;

	$set = $Dispatch->prepareSave($data);

//	print_r ($set);
//echo ("\n\n<hr>******************************************<br>\n\n");
//print_r ($DB->messages);
//echo ("\n\n<hr>__________________________________________<br>\n\n");

	
	$id = $Dispatch->set($set, null, 'IODU');

	$output[] = array(
		'id' => $id,
		'data' => $data,
		'set' => $set,
		'email' => str_replace('width: 650px;', '', $email->textHtml),
		'email_to' => $email->toString,
		'mail_id' => $mail_id,
		'getmail_time' => round($getmail_time, 4),
		'iteration_total_time' => round($iteration_total_time, 4)
	);

	$counter++;
}

$total_time = microtime(true) - $timer;
/*
echo ('<hr>Emails processed: ' . $counter . ' - Saved: ' . $saved . "<br>\n");
echo ('Total time for getMail() calls: ' . round($total_getmail_time, 4) . "s<br>\n");
echo ('Total execution time: ' . round($total_time, 4) . "s<br>\n");
*/
// $mailbox->setFlag($mail_id, '\Seen');

?>


<!DOCTYPE html>
<html lang="cs">

<head>
	<!-- Required meta tags -->
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />

	<!-- Bootstrap CSS -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous" />

	<title>Požární poplach</title>
	<link rel="icon" href="./favicon.svg" type="image/svg+xml" />
</head>

<body class="bg-dark">
	<div class="container">
		<?php
		foreach ($output as $item) {
		?>
			<div class="card my-3">
				<div class="card-body">
					<div class="row">
						<div class="col-6">
							<?php echo ($item['email']); ?>
						</div>
						<div class="col-6">
							<dl class="row">
								<?php
								foreach ($item['data'] as $key => $value) {
									if ($key == 'plaindata')
										continue;
									elseif (!is_array($value)) {
										echo ("<dt class='col-4'>$key</dt><dd class='col-8'>$value</dd>");
									} else {
										echo ("<dt class='col-3'>$key</dt>");
										echo ('<dd class="col-9">' . nl2br(print_r($value, true)) . '</dd>');
									}
								}
								//echo (nl2br(print_r ($item['data'], true)));

								?>
							</dl>
						</div>
					</div>
				</div>
				<div class="card-footer text-muted">
					Mail ID: <?php echo ($item['mail_id']); ?> - getMail() time: <?php echo ($item['getmail_time']); ?>s - Total iteration time: <?php echo ($item['iteration_total_time']); ?>s
				</div>
			</div>
		<?php
		}
		?>
	</div>

	<!-- Bootstrap JS Bundle with Popper -->
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>

</html>