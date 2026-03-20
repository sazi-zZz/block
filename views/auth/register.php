<?php
require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../../models/User.php';

if (isLoggedIn()) {
    redirect(BASE_URL . 'index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $terms = isset($_POST['terms_and_conditions']);
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required.';
    }
    elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    }
    elseif (!$terms) {
        $error = 'You must agree to the Terms and Conditions.';
    }
    else {
        $userModel = new User($pdo);
        if ($userModel->register($username, $email, $password)) {
            // Send welcome email
            require_once '../../includes/mailer.php';
            sendWelcomeEmail($email, $username);

            $user = $userModel->login($username, $password);
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_avatar'] = $user['avatar'];
                redirect(BASE_URL . 'index.php');
            }
            else {
                redirect(BASE_URL . 'views/auth/login.php');
            }
        }
        else {
            $error = 'Registration failed. Username or email may already be taken.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - BLOCKNET</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL?>public/css/style.css">
    <link rel="icon" type="image/png" href="<?= BASE_URL?>public/Block.png">
    <style>
        body {
            background: #080808;
            overflow-y: auto;
        }

        .custom-checkbox input[type="checkbox"] {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            width: 1.25rem;
            height: 1.25rem;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 6px;
            cursor: pointer;
            position: relative;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin: 0;
        }

        .custom-checkbox input[type="checkbox"]:checked {
            background: var(--white);
            border-color: var(--white);
        }

        .custom-checkbox input[type="checkbox"]:checked::after {
            content: '\f00c';
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            color: var(--black);
            font-size: 0.75rem;
        }

        .custom-checkbox input[type="checkbox"]:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.4);
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
                    Create account</h1>
                <p class="text-muted" style="font-size: 0.9375rem;">Join the BLOCKNET community today</p>
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
                    <label>Username</label>
                    <div class="auth-input-group">
                        <i class="fa-solid fa-user"></i>
                        <input type="text" name="username" class="js-char-limit" data-limit="50" required
                            placeholder="Choose a username" autocomplete="username">
                    </div>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <div class="auth-input-group">
                        <i class="fa-solid fa-envelope"></i>
                        <input type="email" name="email" class="js-char-limit" data-limit="100" required
                            placeholder="your@email.com" autocomplete="email">
                    </div>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <div class="auth-input-group">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" name="password" required placeholder="Create a strong password"
                            autocomplete="new-password">
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label>Confirm Password</label>
                    <div class="auth-input-group">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" name="confirm_password" required placeholder="Confirm your password"
                            autocomplete="new-password">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 2rem;">
                    <label class="custom-checkbox"
                        style="display: flex; align-items: start; gap: 0.85rem; cursor: pointer; user-select: none;">
                        <input type="checkbox" name="terms_and_conditions" required>
                        <span style="font-size: 0.875rem; color: rgba(255,255,255,0.7); line-height: 1.5; padding-top: 1px;">
                            I agree to the <a href="<?= BASE_URL?>views/terms.php" target="_blank"
                                style="color: var(--white); text-decoration: underline; font-weight: 600;">Terms &
                                Conditions</a>
                        </span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-block"
                    style="padding: 0.875rem; font-size: 1rem; font-weight: 700; letter-spacing: 0.02em;">
                    <i class="fa-solid fa-user-plus"></i>
                    Create Account
                </button>
            </form>

            <div
                style="margin-top: 1.75rem; padding-top: 1.5rem; border-top: 1px solid rgba(255,255,255,0.06); text-align: center;">
                <p class="text-muted" style="font-size: 0.9rem;">
                    Already have an account?
                    <a href="login.php" style="color: var(--white); font-weight: 600; margin-left: 0.25rem;">Sign in
                        →</a>
                </p>
            </div>

        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.js-char-limit').forEach(input => {
                const limit = parseInt(input.getAttribute('data-limit'));
                const counter = document.createElement('div');
                counter.className = 'char-counter';
                counter.style.cssText = 'color: rgba(255,255,255,0.5); font-size: 0.8rem; text-align: right; margin-top: 4px;';

                let attachPoint = input;
                if (input.parentNode.classList.contains('auth-input-group')) {
                    attachPoint = input.parentNode;
                }
                attachPoint.parentNode.insertBefore(counter, attachPoint.nextSibling);

                const updateCounter = () => {
                    const current = input.value.length;
                    const form = input.closest('form');
                    const submitBtn = form ? form.querySelector('button[type="submit"]') : null;

                    if (current > limit) {
                        counter.innerHTML = `${current} / ${limit} <span style="color:var(--danger)">⚠️ Exceeded limit</span>`;
                        counter.style.color = 'var(--danger)';
                        input.style.borderColor = 'var(--danger)';
                        if (submitBtn) submitBtn.disabled = true;
                    } else {
                        counter.textContent = `${current} / ${limit}`;
                        counter.style.color = 'rgba(255,255,255,0.5)';
                        input.style.borderColor = '';

                        if (submitBtn) {
                            const allLimits = Array.from(form.querySelectorAll('.js-char-limit'));
                            const anyExceeded = allLimits.some(inp => inp.value.length > parseInt(inp.getAttribute('data-limit')));
                            submitBtn.disabled = anyExceeded;
                        }
                    }
                };
                input.addEventListener('input', updateCounter);
                updateCounter();
            });
        });
    </script>
</body>

</html>