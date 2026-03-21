<?php
/**
 * BLOCKNET — Diagnostic v4 (SMTP connection test)
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
echo "  BLOCKNET DIAGNOSTIC v4 — SMTP Focus\n";
echo "  " . date('Y-m-d H:i:s') . " (server time)\n";
echo "============================================================\n\n";

// ── 1. RECENT ERROR LOG ───────────────────────
echo "── RECENT ERRORS (last 15 lines) ───────────────────────\n";
$logPath = __DIR__ . '/error_log';
if (file_exists($logPath) && is_readable($logPath)) {
    $lines = file($logPath);
    $last  = array_slice($lines, -15);
    echo "$ok  error_log found (" . count($lines) . " total lines)\n\n";
    foreach ($last as $line) echo "  " . rtrim($line) . "\n";
} else {
    echo "$warn  No error_log found\n";
}

// ── 2. PORT CONNECTIVITY ──────────────────────
echo "\n── SMTP PORT CONNECTIVITY ───────────────────────────────\n";
$hosts = [
    ['127.0.0.1',         25,  'localhost:25 (local relay, no auth)'],
    ['127.0.0.1',         587, 'localhost:587'],
    ['localhost',         25,  'localhost (hostname) :25'],
    ['mail.blocknet.online', 465, 'mail.blocknet.online:465 (cPanel SSL)'],
    ['mail.blocknet.online', 587, 'mail.blocknet.online:587 (cPanel TLS)'],
    ['smtp.gmail.com',    587, 'smtp.gmail.com:587 (external)'],
];
$reachable = [];
foreach ($hosts as [$h, $p, $label]) {
    $sock = @fsockopen($h, $p, $errno, $errstr, 4);
    $up = (bool)$sock;
    if ($sock) { fclose($sock); $reachable[] = [$h, $p, $label]; }
    echo ($up ? $ok : $fail) . "  $label" . ($up ? '' : " — $errno: $errstr") . "\n";
}

// ── 3. PHPMAILER SMTP TEST ────────────────────
echo "\n── PHPMAILER SMTP SEND TEST ─────────────────────────────\n";

require_once __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function testSmtp($host, $port, $auth, $user, $pass, $secure, $autoTls, $label) {
    global $ok, $fail, $info;
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->SMTPDebug  = 0; // suppress debug output
        $mail->Host       = $host;
        $mail->Port       = $port;
        $mail->SMTPAuth   = $auth;
        $mail->SMTPSecure = $secure;
        $mail->SMTPAutoTLS = $autoTls;
        if ($auth) {
            $mail->Username = $user;
            $mail->Password = $pass;
        }
        $mail->setFrom('no-reply@blocknet.online', 'BLOCKNET Test');
        $mail->addAddress('test-diag@blocknet.online');
        $mail->Subject = 'BLOCKNET SMTP Test';
        $mail->Body    = 'SMTP test from diag.php';
        $mail->send();
        echo "$ok  $label — SENT SUCCESSFULLY\n";
        return true;
    } catch (Exception $e) {
        echo "$fail  $label — FAILED: " . $mail->ErrorInfo . "\n";
        return false;
    }
}

// Test 1: localhost:25 no auth
testSmtp('localhost', 25, false, '', '', '', false, 'localhost:25 (no auth)');

// Test 2: 127.0.0.1:25 no auth
testSmtp('127.0.0.1', 25, false, '', '', '', false, '127.0.0.1:25 (no auth)');

// Test 3: mail.blocknet.online:465 SSL (cPanel-style)
// NOTE: We don't know cPanel password yet, so this will fail auth but confirm connectivity
testSmtp('mail.blocknet.online', 465, false, '', '', PHPMailer::ENCRYPTION_SMTPS, false, 'mail.blocknet.online:465 SSL (no auth test)');

// Test 4: mail.blocknet.online:587 STARTTLS
testSmtp('mail.blocknet.online', 587, false, '', '', PHPMailer::ENCRYPTION_STARTTLS, false, 'mail.blocknet.online:587 STARTTLS (no auth test)');

echo "\n$info  NOTE: If any test above shows 'SENT SUCCESSFULLY', that's the method to use.\n";
echo "$info  If mail.blocknet.online tests connect but fail auth, you need cPanel email credentials.\n";

// ── 4. MAILER.PHP CONFIG CHECK ────────────────
echo "\n── CURRENT MAILER.PHP CONFIG ────────────────────────────\n";
$m = file_get_contents(__DIR__ . '/includes/mailer.php');
preg_match("/Host\s*=\s*'([^']+)'/",  $m, $hm);
preg_match("/Port\s*=\s*(\d+)/",      $m, $pm);
preg_match("/SMTPAuth\s*=\s*(\w+)/",  $m, $am);
preg_match("/SMTPSecure\s*=\s*'([^']*)'/", $m, $sm);
echo "$info  Host      : " . ($hm[1] ?? '?') . "\n";
echo "$info  Port      : " . ($pm[1] ?? '?') . "\n";
echo "$info  SMTPAuth  : " . ($am[1] ?? '?') . "\n";
echo "$info  SMTPSecure: '" . ($sm[1] ?? '?') . "'\n";

// ── 5. ENVIRONMENT ────────────────────────────
echo "\n── ENVIRONMENT ──────────────────────────────────────────\n";
echo "$info  PHP: " . phpversion() . "\n";
echo "$info  Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'unknown') . "\n";
echo "$info  mail() enabled: " . (function_exists('mail') ? 'YES' : 'NO') . "\n";
echo "$info  sendmail_path: " . (ini_get('sendmail_path') ?: '(not set)') . "\n";

echo "\n============================================================\n";
echo "  !! DELETE diag.php after you are done !!\n";
echo "============================================================\n";
