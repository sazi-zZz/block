<?php
require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../../models/User.php';
require_once '../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email']);

    if (empty($email)) {
        $error = 'Email is required.';
    }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    }
    else {
        $userModel = new User($pdo);
        $user = $userModel->findByEmail($email);

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            if ($userModel->setResetToken($user['id'], $token, $expires)) {
                $resetLink = 'http://' . $_SERVER['HTTP_HOST'] . BASE_URL . 'views/auth/reset_password.php?token=' . $token;

                $mail = new PHPMailer(true);
                try {
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'sazedurrahman707@gmail.com'; // SMTP username
                    $mail->Password = 'dgkv xsoq xxea twmq'; // SMTP password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    // Recipients
                    $mail->setFrom('sazedurrahman707@gmail.com', 'BLOCKNET Platform');
                    $mail->addAddress($email, $user['username']);

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Password Reset Request - BLOCKNET';
                    $mail->Body = "
                        <h2>Password Reset Request</h2>
                        <p>Hello {$user['username']},</p>
                        <p>You recently requested to reset your password for your BLOCKNET account. Click the link below to reset it.</p>
                        <p><a href='{$resetLink}'>{$resetLink}</a></p>
                        <p>If you did not request a password reset, please ignore this email.</p>
                        <p>This password reset is only valid for the next 60 minutes.</p>
                        <p>Thanks,<br>The BLOCKNET Team</p>
                    ";
                    $mail->AltBody = "Hello {$user['username']},\n\nYou recently requested to reset your password for your BLOCKNET account. Copy and paste the link below to reset it:\n{$resetLink}\n\nIf you did not request a password reset, please ignore this email.\nThis password reset is only valid for the next 60 minutes.\n\nThanks,\nThe BLOCKNET Team";

                    $mail->send();
                    $success = 'If an account with that email exists, we have sent a password reset link.';
                }
                catch (Exception $e) {
                    $error = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                }
            }
            else {
                $error = 'An error occurred while generating the reset token. Please try again.';
            }
        }
        else {
            // Do not reveal that the email doesn't exist for security purposes
            $success = 'If an account with that email exists, we have sent a password reset link.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - BLOCKNET</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL?>public/css/style.css">
    <link rel="icon" type="image/png" href="<?= BASE_URL?>public/Block.png">
    <style>
        body {
            background: #080808;
            overflow: hidden;
        }
    </style>
</head>

<body>
    <div class="auth-container">
        <div class="auth-card">

            <!-- Logo -->
            <div class="text-center mb-4">
                <div class="auth-logo" style="margin-bottom: 1rem;">
                    <img src="<?= BASE_URL?>public/Block.png" alt="BLOCKNET Logo"
                        style="width: 4.5rem; height: 4.5rem; object-fit: contain; display: block; margin: 0 auto;">
                    <div
                        style="font-family: 'Space Grotesk', sans-serif; font-size: 1.25rem; font-weight: 800; letter-spacing: 0.15em; margin-top: 0.5rem; text-transform: uppercase;">
                        BLOCKNET</div>
                </div>
                <h1
                    style="font-family: 'Space Grotesk', sans-serif; font-size: 1.875rem; font-weight: 800; letter-spacing: -0.03em; margin-bottom: 0.375rem;">
                    Forgot Password</h1>
                <p class="text-muted" style="font-size: 0.9375rem;">Enter your email to receive a reset link</p>
            </div>

            <?php if ($error): ?>
            <div class="text-error"
                style="margin-bottom: 1.25rem; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); padding: 0.75rem; border-radius: 8px; color: #ef4444; font-size: 0.9rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?= htmlspecialchars($error)?>
            </div>
            <?php
endif; ?>

            <?php if ($success): ?>
            <div class="text-success"
                style="margin-bottom: 1.25rem; background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); padding: 0.75rem; border-radius: 8px; color: #10b981; font-size: 0.9rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fa-solid fa-check-circle"></i>
                <?= htmlspecialchars($success)?>
            </div>
            <?php
endif; ?>

            <form method="POST">
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label>Email Address</label>
                    <div class="auth-input-group">
                        <i class="fa-solid fa-envelope"></i>
                        <input type="email" name="email" required placeholder="Enter your email address"
                            autocomplete="email">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-block"
                    style="padding: 0.875rem; font-size: 1rem; font-weight: 700; letter-spacing: 0.02em;">
                    <i class="fa-solid fa-paper-plane mr-2"></i>
                    Send Reset Link
                </button>
            </form>

            <div
                style="margin-top: 1.75rem; padding-top: 1.5rem; border-top: 1px solid rgba(255,255,255,0.06); text-align: center;">
                <p class="text-muted" style="font-size: 0.9rem;">
                    Remembered your password?
                    <a href="login.php" style="color: var(--white); font-weight: 600; margin-left: 0.25rem;">Sign In
                        →</a>
                </p>
            </div>

        </div>
    </div>
</body>

</html>