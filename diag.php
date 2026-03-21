<?php
/**
 * BLOCKNET — Diagnostic v3 (mail + error focus)
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
echo "  BLOCKNET DIAGNOSTIC v3\n";
echo "  " . date('Y-m-d H:i:s') . " (server time)\n";
echo "============================================================\n\n";

// ── 1. RECENT ERROR LOG ───────────────────────
echo "── RECENT PHP ERRORS (last 40 lines) ───────────────────\n";
$logPath = __DIR__ . '/error_log';
if (file_exists($logPath) && is_readable($logPath)) {
    $lines = file($logPath);
    $last  = array_slice($lines, -40);
    echo "$ok  Error log found (" . count($lines) . " total lines)\n\n";
    foreach ($last as $line) echo "  " . rtrim($line) . "\n";
} else {
    echo "$warn  No error_log in document root\n";
}

// ── 2. SYNTAX CHECK ───────────────────────────
echo "\n── PHP SYNTAX CHECK (key files) ─────────────────────────\n";
$files = [
    'views/auth/forgot_password.php',
    'includes/mailer.php',
    'views/auth/register.php',
];
foreach ($files as $f) {
    $full = __DIR__ . '/' . $f;
    if (!file_exists($full)) { echo "$fail  $f — MISSING\n"; continue; }
    $out = shell_exec('php -l ' . escapeshellarg($full) . ' 2>&1');
    $ok2 = strpos($out, 'No syntax errors') !== false;
    echo ($ok2 ? $ok : $fail) . "  $f";
    if (!$ok2) echo "\n       ↳ " . trim($out);
    echo "\n";
}

// ── 3. forgot_password.php USE STATEMENT ORDER ────────────
echo "\n── FORGOT_PASSWORD.PHP STRUCTURE CHECK ─────────────────\n";
$fp = file_get_contents(__DIR__ . '/views/auth/forgot_password.php');
$lines = explode("\n", $fp);
// Find line numbers of use statements and require_once statements
$useLines     = [];
$requireLines = [];
foreach ($lines as $i => $line) {
    $trimmed = trim($line);
    if (strpos($trimmed, 'use PHPMailer') === 0) $useLines[]     = ($i + 1) . ': ' . $trimmed;
    if (strpos($trimmed, 'require_once')  === 0) $requireLines[] = ($i + 1) . ': ' . $trimmed;
}
$firstUse     = $useLines     ? (int)explode(':', $useLines[0])[0]     : 0;
$firstRequire = $requireLines ? (int)explode(':', $requireLines[0])[0] : 0;
$useBeforeRequire = $firstUse > 0 && $firstUse < $firstRequire;
echo ($useBeforeRequire ? $ok : $fail) . "  'use' before 'require_once': " . ($useBeforeRequire ? 'YES (correct)' : "NO — 'use' is on line $firstUse, first require_once on line $firstRequire") . "\n";
foreach ($useLines     as $l) echo "  use     → $l\n";
foreach ($requireLines as $l) echo "  require → $l\n";

// ── 4. PHP MAIL() FUNCTION ────────────────────
echo "\n── PHP mail() TEST ──────────────────────────────────────\n";
$mailEnabled = function_exists('mail');
echo ($mailEnabled ? $ok : $fail) . "  mail() function exists: " . ($mailEnabled ? 'YES' : 'NO — disabled on this server') . "\n";

// Check if sendmail is configured
$sendmailPath = ini_get('sendmail_path');
echo "$info  sendmail_path: " . ($sendmailPath ?: '(not set)') . "\n";
echo "$info  SMTP (Windows): " . (ini_get('SMTP') ?: '(not set)') . "\n";
echo "$info  smtp_port: " . (ini_get('smtp_port') ?: '(not set)') . "\n";

// Try actually sending a test mail (to a dummy address just to test the call)
if ($mailEnabled) {
    $testResult = @mail(
        'test@example.com',
        'BLOCKNET mail() test',
        'This is a test',
        "From: no-reply@blocknet.online\r\nContent-Type: text/plain"
    );
    echo ($testResult ? $ok : $fail) . "  mail() call returned: " . ($testResult ? 'TRUE (accepted by server)' : 'FALSE (server rejected)') . "\n";
    echo "$warn  Note: TRUE means the server ACCEPTED the mail — not that it was delivered\n";
}

// ── 5. PHPMAILER TEST ─────────────────────────
echo "\n── PHPMAILER isMail() TEST ──────────────────────────────\n";
require_once __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);
try {
    $mail->isMail();
    $mail->setFrom('no-reply@blocknet.online', 'BLOCKNET');
    $mail->addAddress('test@example.com', 'Test');
    $mail->Subject = 'PHPMailer test';
    $mail->Body    = 'Test from BLOCKNET diag';
    $mail->send();
    echo "$ok  PHPMailer isMail() send() returned TRUE\n";
} catch (Exception $e) {
    echo "$fail  PHPMailer isMail() FAILED: " . $mail->ErrorInfo . "\n";
    echo "$info  This means PHP mail() is disabled or sendmail is not configured\n";
}

// ── 6. ALTERNATIVE: LOCALHOST SMTP TEST ───────
echo "\n── LOCALHOST SMTP TEST (port 25) ────────────────────────\n";
$sock = @fsockopen('127.0.0.1', 25, $errno, $errstr, 3);
if ($sock) {
    fclose($sock);
    echo "$ok  localhost:25 is reachable — can use SMTP on localhost\n";
} else {
    echo "$fail  localhost:25 unreachable ($errno: $errstr)\n";
}

$sock2 = @fsockopen('127.0.0.1', 587, $errno2, $errstr2, 3);
echo ($sock2 ? $ok : $fail) . "  localhost:587 " . ($sock2 ? "reachable\n" : "unreachable ($errno2: $errstr2)\n");
if ($sock2) fclose($sock2);

// External SMTP test
$sock3 = @fsockopen('smtp.gmail.com', 587, $errno3, $errstr3, 5);
echo ($sock3 ? $ok : $warn) . "  smtp.gmail.com:587 " . ($sock3 ? "reachable (external SMTP works!)\n" : "BLOCKED ($errno3: $errstr3)\n");
if ($sock3) fclose($sock3);

// ── 7. ENVIRONMENT ────────────────────────────
echo "\n── ENVIRONMENT ──────────────────────────────────────────\n";
echo "$info  PHP: " . phpversion() . "\n";
echo "$info  Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'unknown') . "\n";
echo "$info  HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'unknown') . "\n";

echo "\n============================================================\n";
echo "  !! DELETE diag.php after you are done !!\n";
echo "============================================================\n";
