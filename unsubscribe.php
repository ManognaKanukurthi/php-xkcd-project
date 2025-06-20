<?php
require_once 'functions.php';

$message = '';
$showVerificationForm = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['unsubscribe_email']) && !isset($_POST['verification_code'])) {
        // Handle unsubscribe email submission
        $email = filter_var($_POST['unsubscribe_email'], FILTER_VALIDATE_EMAIL);
        
        if ($email) {
            // Check if email exists in registered emails
            $file = __DIR__ . '/registered_emails.txt';
            if (file_exists($file)) {
                $emails = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                if (in_array($email, $emails)) {
                    $code = generateVerificationCode();
                    $_SESSION['unsubscribe_codes'][$email] = $code;
                    $_SESSION['pending_unsubscribe_email'] = $email;
                    
                    if (sendUnsubscribeVerificationEmail($email, $code)) {
                        $message = 'Unsubscribe verification code sent to your email!';
                        $showVerificationForm = true;
                    } else {
                        $message = 'Failed to send verification email. Please try again.';
                    }
                } else {
                    $message = 'Email not found in our subscription list.';
                }
            } else {
                $message = 'Email not found in our subscription list.';
            }
        } else {
            $message = 'Please enter a valid email address.';
        }
    } elseif (isset($_POST['verification_code'])) {
        // Handle unsubscribe verification code submission
        $code = $_POST['verification_code'];
        $email = $_SESSION['pending_unsubscribe_email'] ?? '';
        
        if ($email && verifyUnsubscribeCode($email, $code)) {
            if (unsubscribeEmail($email)) {
                $message = 'Successfully unsubscribed from XKCD updates!';
                unset($_SESSION['pending_unsubscribe_email']);
            } else {
                $message = 'Failed to unsubscribe. Please try again.';
            }
        } else {
            $message = 'Invalid verification code. Please try again.';
            $showVerificationForm = true;
        }
    }
}

// Check if we have a pending unsubscribe verification
if (isset($_SESSION['pending_unsubscribe_email']) && empty($message)) {
    $showVerificationForm = true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsubscribe - XKCD Email Subscription</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 40px;
            max-width: 600px;
            width: 100%;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        h1 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 10px;
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .subtitle {
            text-align: center;
            color: #7f8c8d;
            margin-bottom: 40px;
            font-size: 1.1rem;
            font-weight: 300;
        }

        .warning-box {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            border: 1px solid #ffc107;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: center;
        }

        .warning-box h3 {
            color: #856404;
            margin-bottom: 10px;
            font-size: 1.2rem;
        }

        .warning-box p {
            color: #856404;
            margin: 0;
        }

        .form-section {
            margin-bottom: 35px;
            padding: 30px;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
        }

        .form-section:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .form-section h2 {
            color: #34495e;
            margin-bottom: 20px;
            font-size: 1.4rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-section h2::before {
            content: '';
            width: 4px;
            height: 25px;
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            border-radius: 2px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        input[type="email"], input[type="text"] {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e0e6ed;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
            color: #2c3e50;
        }

        input[type="email"]:focus, input[type="text"]:focus {
            border-color: #ff6b6b;
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.1);
            transform: translateY(-1px);
        }

        input[type="email"]:hover, input[type="text"]:hover {
            border-color: #bdc3c7;
        }

        button {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
        }

        button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        button:hover::before {
            left: 100%;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 107, 107, 0.3);
        }

        button:active {
            transform: translateY(0);
        }

        .message {
            padding: 20px;
            margin: 25px 0;
            border-radius: 12px;
            text-align: center;
            font-weight: 500;
            border-left: 5px solid;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border-left-color: #28a745;
        }

        .error {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border-left-color: #dc3545;
        }

        .info {
            background: linear-gradient(135deg, #cce7ff, #b3d9ff);
            color: #004085;
            border-left-color: #007bff;
        }

        .footer-links {
            text-align: center;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
        }

        .footer-links a {
            color: #ff6b6b;
            text-decoration: none;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 25px;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .footer-links a:hover {
            background: rgba(255, 107, 107, 0.1);
            transform: translateY(-2px);
        }

        .sad-emoji {
            font-size: 4rem;
            text-align: center;
            margin: 20px 0;
            opacity: 0.7;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                margin: 10px;
                padding: 25px;
            }

            h1 {
                font-size: 2rem;
            }

            .form-section {
                padding: 20px;
            }

            button {
                padding: 12px 25px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚫 Unsubscribe</h1>
        <p class="subtitle">We're sorry to see you go!</p>
        
        <div class="warning-box">
            <h3>⚠️ Are you sure?</h3>
            <p>You'll no longer receive daily XKCD comics in your inbox.</p>
        </div>

        <div class="sad-emoji">😢</div>

        <?php if ($message): ?>
            <div class="message <?php echo (strpos($message, 'Successfully') !== false || strpos($message, 'sent') !== false) ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="form-section">
            <h2>📧 Enter Your Email</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="unsubscribe_email">📧 Email Address</label>
                    <input type="email" name="unsubscribe_email" id="unsubscribe_email" placeholder="Enter your email address" required>
                </div>
                <button type="submit" id="submit-unsubscribe">
                    Send Unsubscribe Code
                </button>
            </form>
        </div>

        <div class="form-section">
            <h2>🔐 Verify Unsubscription</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="verification_code">🔢 Verification Code</label>
                    <input type="text" name="verification_code" id="verification_code" maxlength="6" placeholder="Enter 6-digit code" required>
                </div>
                <button type="submit" id="submit-verification">
                    Confirm Unsubscribe
                </button>
            </form>
        </div>

        <div class="footer-links">
            <a href="index.php">🔙 Back to subscription</a>
        </div>
    </div>
</body>
</html>