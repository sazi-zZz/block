<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../../models/User.php';
require_once '../../vendor/autoload.php';

if (isLoggedIn()) {
    redirect(BASE_URL . 'index.php');
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
                $protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $resetLink = $protocol . '://' . $_SERVER['HTTP_HOST'] . BASE_URL . 'views/auth/reset_password.php?token=' . $token;

                $mail = new PHPMailer(true);
                try {
                    // Use PHP's native mail() — works on all shared hosting
                    // (avoids SMTP port 587/465 blocks)
                    $mail->isMail();

                    // Recipients
                    $mail->setFrom('no-reply@blocknet.online', 'BLOCKNET Platform');
                    $mail->addAddress($email, $user['username']);

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Password Reset Request - BLOCKNET';
                    $mail->Body = "
                        <div style='background-color:#080808;color:#ffffff;padding:40px;font-family:sans-serif;max-width:600px;margin:0 auto;border-radius:12px;'>
                            <h1 style='color:#ffffff;letter-spacing:2px;'>BLOCKNET</h1>
                            <h2 style='color:#ffffff;'>Password Reset Request</h2>
                            <p style='color:rgba(255,255,255,0.7);'>Hello {$user['username']},</p>
                            <p style='color:rgba(255,255,255,0.7);'>You recently requested to reset your password. Click the button below to reset it. This link is valid for <strong>60 minutes</strong>.</p>
                            <a href='{$resetLink}' style='display:inline-block;background:#ffffff;color:#080808;padding:12px 28px;border-radius:6px;font-weight:700;text-decoration:none;margin:20px 0;'>Reset My Password</a>
                            <p style='color:rgba(255,255,255,0.5);font-size:13px;'>Or copy this link: {$resetLink}</p>
                            <p style='color:rgba(255,255,255,0.5);font-size:13px;'>If you did not request a password reset, you can safely ignore this email.</p>
                            <p style='color:rgba(255,255,255,0.5);font-size:13px;'>Thanks,<br>The BLOCKNET Team</p>
                        </div>
                    ";
                    $mail->AltBody = "Hello {$user['username']},\n\nReset your password using this link (valid 60 minutes):\n{$resetLink}\n\nIf you didn't request this, ignore this email.\n\nThanks,\nThe BLOCKNET Team";

                    $mail->send();
                    $success = 'If an account with that email exists, we have sent a password reset link.';
                }
                catch (Exception $e) {
                    error_log('BLOCKNET reset email error: ' . $mail->ErrorInfo);
                    $error = 'Failed to send reset email. Please try again later.';
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
            overflow-y: auto;
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
                    <a href="<?= BASE_URL?>views/auth/login.php" style="color: var(--white); font-weight: 600; margin-left: 0.25rem;">Sign In
                        →</a>
                </p>
            </div>

        </div>
    </div>
</body>

</html>