<?php
require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../../models/User.php';

if (isLoggedIn()) {
    redirect(BASE_URL . 'index.php');
}

$error = '';
$success = '';
$token = $_GET['token'] ?? '';
$userModel = new User($pdo);

if (empty($token)) {
    redirect(BASE_URL . 'views/auth/login.php');
}

$user = $userModel->findByResetToken($token);

if (!$user) {
    $error = 'Invalid or expired password reset token.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($password) || empty($confirm_password)) {
        $error = 'Both password fields are required.';
    }
    elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    }
    elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    }
    else {
        if ($userModel->updatePassword($user['id'], $password)) {
            $success = 'Password successfully reset. You can now login with your new password.';
        }
        else {
            $error = 'Failed to reset password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - BLOCKNET</title>
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
                    Reset Password</h1>
                <p class="text-muted" style="font-size: 0.9375rem;">Choose a new password for your account</p>
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
            <div class="text-center">
                <a href="login.php" class="btn btn-primary"
                    style="padding: 0.875rem 2rem; font-size: 1rem; font-weight: 700;">
                    Go to Login
                </a>
            </div>
            <?php
else: ?>

            <?php if ($user): ?>
            <form method="POST">
                <div class="form-group">
                    <label>New Password</label>
                    <div class="auth-input-group">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" name="password" required placeholder="••••••••" minlength="6">
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label>Confirm Password</label>
                    <div class="auth-input-group">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" name="confirm_password" required placeholder="••••••••" minlength="6">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-block"
                    style="padding: 0.875rem; font-size: 1rem; font-weight: 700; letter-spacing: 0.02em;">
                    <i class="fa-solid fa-key mr-2"></i>
                    Reset Password
                </button>
            </form>
            <?php
    endif; ?>

            <div
                style="margin-top: 1.75rem; padding-top: 1.5rem; border-top: 1px solid rgba(255,255,255,0.06); text-align: center;">
                <p class="text-muted" style="font-size: 0.9rem;">
                    Back to
                    <a href="login.php" style="color: var(--white); font-weight: 600; margin-left: 0.25rem;">Sign In
                        →</a>
                </p>
            </div>
            <?php
endif; ?>

        </div>
    </div>
</body>

</html>