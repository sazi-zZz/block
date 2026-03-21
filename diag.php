<?php
/**
 * BLOCKNET — Diagnostic Tool
 * Visit: https://blocknet.online/diag.php
 * DELETE THIS FILE after diagnosis is complete!
 */

// Catch ALL errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain; charset=utf-8');

$pass = '[ OK ]';
$fail = '[FAIL]';
$warn = '[WARN]';

$lines = [];
$allOk = true;

function check($label, $ok, $detail = '', $warnOnly = false) {
    global $pass, $fail, $warn, $lines, $allOk;
    $status = $ok ? $pass : ($warnOnly ? $warn : $fail);
    if (!$ok && !$warnOnly) $allOk = false;
    $lines[] = "$status  $label" . ($detail ? " — $detail" : '');
}

// ──────────────────────────────────────────────
echo "============================================================\n";
echo "  BLOCKNET DIAGNOSTIC REPORT\n";
echo "  " . date('Y-m-d H:i:s') . " (server time)\n";
echo "============================================================\n\n";

// ── 1. PHP ────────────────────────────────────
echo "── PHP ──────────────────────────────────────────────────\n";
$phpVersion = phpversion();
check('PHP version (' . $phpVersion . ')', version_compare($phpVersion, '7.4.0', '>='), 'need >= 7.4');
check('PDO extension', extension_loaded('pdo'));
check('PDO MySQL driver', extension_loaded('pdo_mysql'));
check('mbstring extension', extension_loaded('mbstring'));
check('json extension', extension_loaded('json'));
check('session extension', extension_loaded('session'));
check('fileinfo extension', extension_loaded('fileinfo'));
check('openssl extension', extension_loaded('openssl'));
foreach ($lines as $l) echo "$l\n";
$lines = [];

// ── 2. DB CONFIG ──────────────────────────────
echo "\n── DB CONFIG (config/db.php) ────────────────────────────\n";
$dbFile = __DIR__ . '/config/db.php';
check('config/db.php exists', file_exists($dbFile));

if (file_exists($dbFile)) {
    $dbContent = file_get_contents($dbFile);
    $hasPlaceholder = strpos($dbContent, 'PLACEHOLDER') !== false;
    check('No PLACEHOLDER strings remain', !$hasPlaceholder,
        $hasPlaceholder ? 'Secrets were NOT injected by GitHub Actions!' : '');

    // Load constants safely
    try {
        // Capture define calls without executing PDO connect
        $safeContent = preg_replace('/try\s*\{.*?\}\s*catch.*?\}/s', '', $dbContent);
        eval('?>' . $safeContent);
    } catch (Throwable $e) {}

    $host  = defined('DB_HOST') ? DB_HOST : '(not defined)';
    $user  = defined('DB_USER') ? DB_USER : '(not defined)';
    $name  = defined('DB_NAME') ? DB_NAME : '(not defined)';
    $pass_ = defined('DB_PASS') ? (strlen(DB_PASS) > 0 ? str_repeat('*', strlen(DB_PASS)) : '(empty)') : '(not defined)';

    check('DB_HOST defined', defined('DB_HOST') && DB_HOST !== '', 'value: ' . $host);
    check('DB_USER defined', defined('DB_USER') && DB_USER !== '', 'value: ' . $user);
    check('DB_NAME defined', defined('DB_NAME') && DB_NAME !== '', 'value: ' . $name);
    check('DB_PASS defined', defined('DB_PASS'), 'value: ' . $pass_, true); // warn only (empty pass is valid locally)
}
foreach ($lines as $l) echo "$l\n";
$lines = [];

// ── 3. DB CONNECTION ──────────────────────────
echo "\n── DATABASE CONNECTION ──────────────────────────────────\n";
$pdo = null;
try {
    if (defined('DB_HOST') && defined('DB_USER') && defined('DB_NAME')) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, defined('DB_PASS') ? DB_PASS : '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        check('Database connection', true, 'Connected to ' . DB_NAME . ' on ' . DB_HOST);
    } else {
        check('Database connection', false, 'DB constants not defined — check secrets injection');
    }
} catch (PDOException $e) {
    check('Database connection', false, $e->getMessage());
}
foreach ($lines as $l) echo "$l\n";
$lines = [];

// ── 4. TABLES ─────────────────────────────────
echo "\n── DATABASE TABLES ──────────────────────────────────────\n";
$requiredTables = [
    'users', 'posts', 'blocks', 'block_members', 'comments',
    'likes', 'followers', 'messages', 'notifications',
    'group_chats', 'group_chat_members', 'group_messages'
];
if ($pdo) {
    try {
        $existing = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($requiredTables as $table) {
            check("Table `$table` exists", in_array($table, $existing));
        }

        // Quick row counts on key tables
        foreach (['users', 'blocks', 'posts'] as $t) {
            if (in_array($t, $existing)) {
                $count = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
                $lines[] = "  ...  `$t` has $count row(s)";
            }
        }
    } catch (PDOException $e) {
        $lines[] = "$fail  Could not query tables — " . $e->getMessage();
        $allOk = false;
    }
} else {
    $lines[] = "$warn  Skipped — no DB connection";
}
foreach ($lines as $l) echo "$l\n";
$lines = [];

// ── 5. BASE_URL ───────────────────────────────
echo "\n── BASE_URL DETECTION ───────────────────────────────────\n";
$funcFile = __DIR__ . '/includes/functions.php';
if (file_exists($funcFile)) {
    // Extract the isLocalEnvironment logic manually
    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'unknown';
    $strippedHost = strtolower(explode(':', $host)[0]);
    $isLocal = in_array($strippedHost, ['localhost', '127.0.0.1', '::1']);
    $expectedBase = $isLocal ? '/blocknet/' : '/';
    $lines[] = "$pass  HTTP_HOST: $host";
    $lines[] = "$pass  isLocalEnvironment(): " . ($isLocal ? 'true (local)' : 'false (production)');
    $lines[] = "$pass  BASE_URL will be: '$expectedBase'";
} else {
    check('includes/functions.php exists', false);
}
foreach ($lines as $l) echo "$l\n";
$lines = [];

// ── 6. FILE SYSTEM ────────────────────────────
echo "\n── FILE SYSTEM & UPLOAD DIRECTORIES ────────────────────\n";
$dirs = [
    'public/images/avatars'       => true,
    'public/images/post_images'   => true,
    'public/images/block_icons'   => true,
    'public/images/comment_media' => true,
    'public/images/chat_media'    => true,
    'public/images/cover_photos'  => true,
    'public/seoImage'             => false,
    'views/layouts'               => false,
    'config'                      => false,
    'models'                      => false,
    'vendor'                      => false,
];
foreach ($dirs as $dir => $needsWrite) {
    $full = __DIR__ . '/' . $dir;
    $exists = is_dir($full);
    if ($needsWrite) {
        $writable = $exists && is_writable($full);
        check("$dir/ (exists + writable)", $exists && $writable,
            !$exists ? 'MISSING directory' : (!$writable ? 'NOT writable — upload will fail!' : ''));
    } else {
        check("$dir/ exists", $exists);
    }
}

// Key files
$keyFiles = [
    'index.php', 'config/db.php', 'includes/functions.php',
    'includes/mailer.php', 'models/User.php', 'models/Post.php',
    'models/Block.php', 'views/layouts/header.php',
    'views/layouts/footer.php', 'views/auth/login.php',
    'views/auth/register.php', 'public/css/style.css',
    'public/Block.png', 'vendor/autoload.php',
];
foreach ($keyFiles as $f) {
    check("$f exists", file_exists(__DIR__ . '/' . $f));
}
foreach ($lines as $l) echo "$l\n";
$lines = [];

// ── 7. SESSION ────────────────────────────────
echo "\n── SESSION ──────────────────────────────────────────────\n";
try {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['__diag_test'] = 'ok';
    check('Session start', true, 'session_id: ' . session_id());
    check('Session write/read', $_SESSION['__diag_test'] === 'ok');
    unset($_SESSION['__diag_test']);
} catch (Throwable $e) {
    check('Session', false, $e->getMessage());
}
foreach ($lines as $l) echo "$l\n";
$lines = [];

// ── 8. PHP SETTINGS ───────────────────────────
echo "\n── PHP SETTINGS ─────────────────────────────────────────\n";
$uploadMax  = ini_get('upload_max_filesize');
$postMax    = ini_get('post_max_size');
$memLimit   = ini_get('memory_limit');
$maxExec    = ini_get('max_execution_time');
$lines[] = "  ...  upload_max_filesize : $uploadMax";
$lines[] = "  ...  post_max_size       : $postMax";
$lines[] = "  ...  memory_limit        : $memLimit";
$lines[] = "  ...  max_execution_time  : {$maxExec}s";
$lines[] = "  ...  display_errors      : " . ini_get('display_errors');
$lines[] = "  ...  error_reporting     : " . ini_get('error_reporting');
foreach ($lines as $l) echo "$l\n";
$lines = [];

// ── SUMMARY ───────────────────────────────────
echo "\n============================================================\n";
if ($allOk) {
    echo "  RESULT: ALL CHECKS PASSED ✓\n";
} else {
    echo "  RESULT: SOME CHECKS FAILED — see [FAIL] lines above\n";
}
echo "============================================================\n";
echo "\n!! DELETE diag.php after you are done !!\n";
