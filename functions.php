<?php
session_start();

function generateVerificationCode() {
    // Generate and return a 6-digit numeric code
    return sprintf('%06d', rand(100000, 999999));
}

function registerEmail($email) {
    $file = __DIR__ . '/registered_emails.txt';
    // Save verified email to registered_emails.txt
    if (!file_exists($file)) {
        touch($file);
    }
    
    // Check if email already exists
    $emails = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!in_array($email, $emails)) {
        file_put_contents($file, $email . PHP_EOL, FILE_APPEND | LOCK_EX);
        return true;
    }
    return false;
}

function unsubscribeEmail($email) {
    $file = __DIR__ . '/registered_emails.txt';
    // Remove email from registered_emails.txt
    if (!file_exists($file)) {
        return false;
    }
    
    $emails = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $emails = array_filter($emails, function($line) use ($email) {
        return trim($line) !== $email;
    });
    
    file_put_contents($file, implode(PHP_EOL, $emails) . (count($emails) > 0 ? PHP_EOL : ''));
    return true;
}

function sendVerificationEmail($email, $code) {
    // Send an email containing the verification code
    $subject = 'Your Verification Code';
    $message = '<p>Your verification code is: <strong>' . $code . '</strong></p>';
    $headers = 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
    $headers .= 'From: no-reply@example.com' . "\r\n";
    
    return mail($email, $subject, $message, $headers);
}

function sendUnsubscribeVerificationEmail($email, $code) {
    // Send an email containing the unsubscribe verification code
    $subject = 'Confirm Un-subscription';
    $message = '<p>To confirm un-subscription, use this code: <strong>' . $code . '</strong></p>';
    $headers = 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
    $headers .= 'From: no-reply@example.com' . "\r\n";
    
    return mail($email, $subject, $message, $headers);
}

function verifyCode($email, $code) {
    // Check if the provided code matches the sent one
    if (!isset($_SESSION['verification_codes'][$email])) {
        return false;
    }
    
    $storedCode = $_SESSION['verification_codes'][$email];
    if ($storedCode === $code) {
        unset($_SESSION['verification_codes'][$email]);
        return true;
    }
    
    return false;
}

function verifyUnsubscribeCode($email, $code) {
    // Check if the provided unsubscribe code matches the sent one
    if (!isset($_SESSION['unsubscribe_codes'][$email])) {
        return false;
    }
    
    $storedCode = $_SESSION['unsubscribe_codes'][$email];
    if ($storedCode === $code) {
        unset($_SESSION['unsubscribe_codes'][$email]);
        return true;
    }
    
    return false;
}

function fetchAndFormatXKCDData() {
    // Fetch latest data from XKCD API and format as HTML
    // First get the latest comic number
    $latestUrl = 'https://xkcd.com/info.0.json';
    $latestData = @file_get_contents($latestUrl);
    
    if ($latestData === false) {
        return false;
    }
    
    $latest = json_decode($latestData, true);
    if (!$latest || !isset($latest['num'])) {
        return false;
    }
    
    // Get a random comic between 1 and latest
    $randomComicId = rand(1, $latest['num']);
    $randomUrl = "https://xkcd.com/{$randomComicId}/info.0.json";
    
    $comicData = @file_get_contents($randomUrl);
    if ($comicData === false) {
        return false;
    }
    
    $comic = json_decode($comicData, true);
    if (!$comic) {
        return false;
    }
    
    // Format as HTML
    $unsubscribeUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/unsubscribe.php';
    
    $html = '<h2>XKCD Comic</h2>';
    $html .= '<img src="' . htmlspecialchars($comic['img']) . '" alt="XKCD Comic">';
    $html .= '<p><a href="' . $unsubscribeUrl . '" id="unsubscribe-button">Unsubscribe</a></p>';
    
    return $html;
}

function sendXKCDUpdatesToSubscribers() {
    $file = __DIR__ . '/registered_emails.txt';
    // Send formatted XKCD data to all registered emails
    
    if (!file_exists($file)) {
        return false;
    }
    
    $emails = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (empty($emails)) {
        return false;
    }
    
    $xkcdContent = fetchAndFormatXKCDData();
    if ($xkcdContent === false) {
        return false;
    }
    
    $subject = 'Your XKCD Comic';
    $headers = 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
    $headers .= 'From: no-reply@example.com' . "\r\n";
    
    $success = true;
    foreach ($emails as $email) {
        $email = trim($email);
        if (!empty($email)) {
            if (!mail($email, $subject, $xkcdContent, $headers)) {
                $success = false;
            }
        }
    }
    
    return $success;
}
?>