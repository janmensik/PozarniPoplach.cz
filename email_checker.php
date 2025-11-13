<?php

// --- CONFIGURATION ---
$lockFile = __DIR__ . '/email_checker.lock';
$runDuration = 55; // Run for 55 seconds to avoid overlapping with the next cron job
$checkInterval = 10; // Check for emails every 10 seconds

$imapConfig = [
    'host' => '{your-imap-server.com:993/imap/ssl/novalidate-cert}',
    'user' => 'your-email@example.com',
    'pass' => 'your-password',
    'mailbox' => 'INBOX'
];

// --- LOCK MECHANISM ---
// Check if another instance is already running to prevent overlaps.
$lockHandle = fopen($lockFile, 'w');
if (!$lockHandle || !flock($lockHandle, LOCK_EX | LOCK_NB)) {
    echo "Another instance is already running. Exiting.\n";
    exit;
}

// --- MAIN LOOP ---
$startTime = time();
$endTime = $startTime + $runDuration;

echo "Starting email checker. Will run until " . date('Y-m-d H:i:s', $endTime) . "\n";

while (time() < $endTime) {
    echo "Checking for new emails at " . date('Y-m-d H:i:s') . "...\n";

    // --- Email Checking Logic ---
    processNewEmails($imapConfig);

    // Wait for the next interval
    $timeLeft = $endTime - time();
    if ($timeLeft < $checkInterval) {
        // Don't sleep if the remaining time is less than the interval
        break;
    }

    echo "Sleeping for {$checkInterval} seconds...\n";
    sleep($checkInterval);
}

// --- CLEANUP ---
// Release the lock and delete the lock file before exiting.
flock($lockHandle, LOCK_UN);
fclose($lockHandle);
unlink($lockFile);

echo "Script finished successfully.\n";

/**
 * Connects to the IMAP server and processes new/unseen emails.
 *
 * @param array $config IMAP connection configuration.
 */
function processNewEmails(array $config) {
    // Suppress errors from imap_open, we will handle them manually
    $inbox = @imap_open($config['host'] . $config['mailbox'], $config['user'], $config['pass']);

    if (!$inbox) {
        echo "IMAP connection failed: " . imap_last_error() . "\n";
        return;
    }

    // Search for all unseen emails
    $emails = imap_search($inbox, 'UNSEEN');

    if ($emails) {
        echo "Found " . count($emails) . " new email(s).\n";

        // For each new email
        foreach ($emails as $email_number) {
            $header = imap_headerinfo($inbox, $email_number);
            $from = $header->from; // ->from addr;
            $subject = htmlspecialchars($header->subject);

            echo "  - From: {$from}, Subject: {$subject}\n";

            // TODO: Add your logic here to process the email.
            // For example, parse the content, save to a database, trigger another action, etc.

            // IMPORTANT: Mark the email as seen so it's not processed again in the next run.
            // imap_setflag_full($inbox, $email_number, "\\Seen");
        }
    } else {
        echo "No new emails found.\n";
    }

    // Close the connection
    imap_close($inbox);
}