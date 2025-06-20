<?php
// CRON job script to send XKCD comics to subscribers
require_once __DIR__ . '/functions.php';

// Log the cron job execution
$logFile = __DIR__ . '/cron.log';
$timestamp = date('Y-m-d H:i:s');

try {
    // Send XKCD updates to all subscribers
    $result = sendXKCDUpdatesToSubscribers();
    
    if ($result) {
        $logMessage = "[$timestamp] SUCCESS: XKCD comics sent to subscribers\n";
    } else {
        $logMessage = "[$timestamp] ERROR: Failed to send XKCD comics\n";
    }
    
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    
} catch (Exception $e) {
    $logMessage = "[$timestamp] EXCEPTION: " . $e->getMessage() . "\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

// Output for cron job debugging
echo "XKCD cron job executed at $timestamp\n";
?>