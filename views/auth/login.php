<?php
require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../../models/User.php';

if (isLoggedIn()) {
    redirect('/block/index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = sanitizeInput($_POST['login']);
    $password = $_POST['password'];

    if (empty($login) || empty($password)) {
        $error = 'Both fields are required.';
    }
    else {
        $userModel = new User($pdo);
        $user = $userModel->login($login, $password);
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_avatar'] = $user['avatar'];
            redirect('/block/index.php');
        }
        else {
            $error = 'Invalid credentials.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BLOCKNET</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL?>/public/css/style.css">
    <link rel="icon" type="image/png" href="<?= BASE_URL?>/public/Block.png">
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
                    <img src="<?= BASE_URL?>/public/Block.png" alt="BLOCKNET Logo"
                        style="width: 4.5rem; height: 4.5rem; object-fit: contain; display: block; margin: 0 auto;">
                    <div
                        style="font-family: 'Space Grotesk', sans-serif; font-size: 1.25rem; font-weight: 800; letter-spacing: 0.15em; margin-top: 0.5rem; text-transform: uppercase;">
                        BLOCKNET</div>
                </div>
                <h1
                    style="font-family: 'Space Grotesk', sans-serif; font-size: 1.875rem; font-weight: 800; letter-spacing: -0.03em; margin-bottom: 0.375rem;">
                    Welcome back</h1>
                <p class="text-muted" style="font-size: 0.9375rem;">Sign in to your BLOCKNET account</p>
            </div>

            <?php if ($error): ?>
            <div class="text-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?= htmlspecialchars($error)?>
            </div>
            <?php
endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Username or Email</label>
                    <div class="auth-input-group">
                        <i class="fa-solid fa-at"></i>
                        <input type="text" name="login" required placeholder="Enter your username or email"
                            autocomplete="username">
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <div
                        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                        <label style="margin-bottom: 0;">Password</label>
                        <a href="forgot_password.php"
                            style="color: var(--primary); font-size: 0.85rem; font-weight: 500; text-decoration: none; transition: all 0.2s ease;">Forgot
                            password?</a>
                    </div>
                    <div class="auth-input-group">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" name="password" required placeholder="••••••••"
                            autocomplete="current-password">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-block"
                    style="padding: 0.875rem; font-size: 1rem; font-weight: 700; letter-spacing: 0.02em;">
                    <i class="fa-solid fa-arrow-right-to-bracket"></i>
                    Sign In
                </button>
            </form>

            <div
                style="margin-top: 1.75rem; padding-top: 1.5rem; border-top: 1px solid rgba(255,255,255,0.06); text-align: center;">
                <p class="text-muted" style="font-size: 0.9rem;">
                    Don't have an account?
                    <a href="register.php" style="color: var(--white); font-weight: 600; margin-left: 0.25rem;">Create
                        one →</a>
                </p>
            </div>

        </div>
    </div>
</body>

</html>