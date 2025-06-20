<?php
require_once 'functions.php';

$message = '';
$showVerificationForm = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['email']) && !isset($_POST['verification_code'])) {
        // Handle email submission
        $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
        
        if ($email) {
            $code = generateVerificationCode();
            $_SESSION['verification_codes'][$email] = $code;
            $_SESSION['pending_email'] = $email;
            
            if (sendVerificationEmail($email, $code)) {
                $message = 'Verification code sent to your email!';
                $showVerificationForm = true;
            } else {
                $message = 'Failed to send verification email. Please try again.';
            }
        } else {
            $message = 'Please enter a valid email address.';
        }
    } elseif (isset($_POST['verification_code'])) {
        // Handle verification code submission
        $code = $_POST['verification_code'];
        $email = $_SESSION['pending_email'] ?? '';
        
        if ($email && verifyCode($email, $code)) {
            if (registerEmail($email)) {
                $message = 'Email successfully registered for XKCD updates!';
                unset($_SESSION['pending_email']);
            } else {
                $message = 'Email is already registered!';
            }
        } else {
            $message = 'Invalid verification code. Please try again.';
            $showVerificationForm = true;
        }
    }
}

// Check if we have a pending email verification
if (isset($_SESSION['pending_email']) && empty($message)) {
    $showVerificationForm = true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XKCD Email Subscription</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            background: linear-gradient(135deg, #667eea, #764ba2);
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
            background: linear-gradient(135deg, #667eea, #764ba2);
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
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: translateY(-1px);
        }

        input[type="email"]:hover, input[type="text"]:hover {
            border-color: #bdc3c7;
        }

        button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
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
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 25px;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .footer-links a:hover {
            background: rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }

        .comic-preview {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 15px;
            border: 2px dashed #667eea;
        }

        .comic-preview img {
            max-width: 100px;
            opacity: 0.7;
            filter: grayscale(50%);
        }

        .comic-preview p {
            margin-top: 10px;
            color: #7f8c8d;
            font-style: italic;
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

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
            margin-left: 10px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Icon styles */
        .icon {
            display: inline-block;
            width: 20px;
            height: 20px;
            margin-right: 8px;
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 XKCD Daily Comics</h1>
        <p class="subtitle">Get your daily dose of geeky humor delivered straight to your inbox!</p>
        
        <div class="comic-preview">
            <p>🎭 Random XKCD comics delivered daily at 9:00 AM</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo (strpos($message, 'successfully') !== false || strpos($message, 'sent') !== false) ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="form-section">
            <h2>📬 Subscribe to Daily Comics</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="email">📧 Email Address</label>
                    <input type="email" name="email" id="email" placeholder="Enter your email address" required>
                </div>
                <button type="submit" id="submit-email">
                    Subscribe Now
                </button>
            </form>
        </div>

        <div class="form-section">
            <h2>🔐 Verify Your Email</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="verification_code">🔢 Verification Code</label>
                    <input type="text" name="verification_code" id="verification_code" maxlength="6" placeholder="Enter 6-digit code" required>
                </div>
                <button type="submit" id="submit-verification">
                    Verify Email
                </button>
            </form>
        </div>

        <div class="footer-links">
            <a href="unsubscribe.php">🚫 Want to unsubscribe?</a>
        </div>
    </div>
</body>
</html>