<?php
/**
 * BLOCKNET — Diagnostic v2
 * Visit: https://blocknet.online/diag.php
 * DELETE THIS FILE after diagnosis is complete!
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain; charset=utf-8');

$ok   = '[ OK ]';
$fail = '[FAIL]';
$warn = '[WARN]';
$info = '[INFO]';

echo "============================================================\n";
echo "  BLOCKNET DIAGNOSTIC v2\n";
echo "  " . date('Y-m-d H:i:s') . " (server time)\n";
echo "============================================================\n\n";

// ── 1. RECENT PHP ERROR LOG ───────────────────
echo "── RECENT PHP ERRORS (last 30 lines) ───────────────────\n";
$possibleLogs = [
    ini_get('error_log'),
    __DIR__ . '/error_log',
    __DIR__ . '/../error_log',
    __DIR__ . '/../../error_log',
    '/var/log/php_errors.log',
    '/tmp/php_errors.log',
];
$logFound = false;
foreach ($possibleLogs as $logPath) {
    if ($logPath && file_exists($logPath) && is_readable($logPath)) {
        $lines = file($logPath);
        $last  = array_slice($lines, -30);
        echo "$ok  Error log: $logPath\n\n";
        foreach ($last as $line) {
            echo "  " . rtrim($line) . "\n";
        }
        $logFound = true;
        break;
    }
}
if (!$logFound) {
    echo "$warn  No readable error log found. Trying to detect errors inline...\n";
    echo "  Checked paths:\n";
    foreach ($possibleLogs as $p) echo "    - " . ($p ?: '(empty)') . "\n";
}

// ── 2. SYNTAX CHECK KEY FILES ─────────────────
echo "\n── PHP SYNTAX CHECK ─────────────────────────────────────\n";
$filesToCheck = [
    'index.php',
    'includes/functions.php',
    'includes/mailer.php',
    'config/db.php',
    'models/User.php',
    'models/Post.php',
    'models/Block.php',
    'models/Notification.php',
    'views/auth/login.php',
    'views/auth/register.php',
    'views/auth/forgot_password.php',
    'views/auth/reset_password.php',
    'views/auth/logout.php',
    'views/layouts/header.php',
    'views/layouts/footer.php',
];
foreach ($filesToCheck as $f) {
    $full = __DIR__ . '/' . $f;
    if (!file_exists($full)) {
        echo "$fail  $f — FILE MISSING\n";
        continue;
    }
    $output = shell_exec('php -l ' . escapeshellarg($full) . ' 2>&1');
    $syntaxOk = strpos($output, 'No syntax errors') !== false;
    echo ($syntaxOk ? $ok : $fail) . "  $f";
    if (!$syntaxOk) echo "\n       ↳ " . trim($output);
    echo "\n";
}

// ── 3. TRY LOADING CORE FILES ─────────────────
echo "\n── RUNTIME INCLUDE TEST ─────────────────────────────────\n";

// Test db.php
echo "$info  Testing config/db.php ... ";
try {
    ob_start();
    require_once __DIR__ . '/config/db.php';
    ob_end_clean();
    echo "OK — PDO connected\n";
} catch (Throwable $e) {
    ob_end_clean();
    echo "FAILED\n       ↳ " . $e->getMessage() . "\n";
}

// Test functions.php
echo "$info  Testing includes/functions.php ... ";
try {
    ob_start();
    require_once __DIR__ . '/includes/functions.php';
    ob_end_clean();
    echo "OK — BASE_URL = '" . BASE_URL . "'\n";
} catch (Throwable $e) {
    ob_end_clean();
    echo "FAILED\n       ↳ " . $e->getMessage() . "\n";
}

// Test mailer.php
echo "$info  Testing includes/mailer.php ... ";
try {
    ob_start();
    require_once __DIR__ . '/includes/mailer.php';
    ob_end_clean();
    echo "OK — sendWelcomeEmail() exists: " . (function_exists('sendWelcomeEmail') ? 'YES' : 'NO') . "\n";
} catch (Throwable $e) {
    ob_end_clean();
    echo "FAILED\n       ↳ " . $e->getMessage() . "\n";
}

// ── 4. DB CONNECTION ──────────────────────────
echo "\n── DATABASE ─────────────────────────────────────────────\n";
if (isset($pdo) && $pdo) {
    echo "$ok  PDO connected — DB: " . DB_NAME . " on " . DB_HOST . "\n";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "$ok  Tables found: " . implode(', ', $tables) . "\n";
} else {
    echo "$fail  No PDO connection available\n";
}

// ── 5. MAILER CONFIG SANITY ───────────────────
echo "\n── MAILER CONFIG CHECK ──────────────────────────────────\n";
$mailerContent = file_get_contents(__DIR__ . '/includes/mailer.php');
$usesSmtp  = strpos($mailerContent, 'isSMTP') !== false;
$usesMail  = strpos($mailerContent, 'isMail') !== false;
$usesGmail = strpos($mailerContent, 'smtp.gmail.com') !== false;
echo ($usesMail  ? $ok : $fail) . "  isMail() used (native PHP mail)\n";
echo ($usesSmtp  ? $warn : $ok) . "  isSMTP() " . ($usesSmtp ? "STILL PRESENT — may block" : "not used (good)") . "\n";
echo ($usesGmail ? $warn : $ok) . "  smtp.gmail.com " . ($usesGmail ? "STILL REFERENCED" : "not referenced (good)") . "\n";

$forgotContent = file_get_contents(__DIR__ . '/views/auth/forgot_password.php');
$forgotSmtp  = strpos($forgotContent, 'smtp.gmail.com') !== false;
$forgotMail  = strpos($forgotContent, 'isMail') !== false;
echo ($forgotMail  ? $ok : $fail) . "  forgot_password.php uses isMail()\n";
echo ($forgotSmtp  ? $warn : $ok) . "  forgot_password.php " . ($forgotSmtp ? "STILL has smtp.gmail.com" : "no smtp.gmail.com (good)") . "\n";

// ── 6. PHP ENV ────────────────────────────────
echo "\n── ENVIRONMENT ──────────────────────────────────────────\n";
echo "$info  PHP: " . phpversion() . "\n";
echo "$info  Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'unknown') . "\n";
echo "$info  Document root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'unknown') . "\n";
echo "$info  Script path: " . __DIR__ . "\n";
echo "$info  display_errors: " . ini_get('display_errors') . "\n";
echo "$info  error_log path: " . (ini_get('error_log') ?: '(not set)') . "\n";
echo "$info  shell_exec available: " . (function_exists('shell_exec') ? 'YES' : 'NO') . "\n";

echo "\n============================================================\n";
echo "  !! DELETE diag.php after you are done !!\n";
echo "============================================================\n";
