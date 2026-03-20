<?php
session_start();
date_default_timezone_set('Asia/Dhaka');

if (!defined('BASE_URL')) {
    define('BASE_URL', '/');
}

function redirect($url)
{
    header("Location: $url");
    exit();
}

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function requireLogin()
{
    if (!isLoggedIn()) {
        redirect('/views/auth/login.php');
    }
}

function sanitizeInput($data)
{
    return trim($data);
}

function timeElapsedString($datetime, $full = false)
{
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $w = floor($diff->d / 7);
    $d = $diff->d - ($w * 7);

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );

    $values = [
        'y' => $diff->y,
        'm' => $diff->m,
        'w' => $w,
        'd' => $d,
        'h' => $diff->h,
        'i' => $diff->i,
        's' => $diff->s
    ];

    foreach ($string as $k => &$v) {
        if ($values[$k]) {
            $v = $values[$k] . ' ' . $v . ($values[$k] > 1 ? 's' : '');
        }
        else {
            unset($string[$k]);
        }
    }

    if (!$full)
        $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

function getDisplayTime($datetime)
{
    $exactTime = date('M j, Y, g:i A', strtotime($datetime));
    $ago = timeElapsedString($datetime);
    return "$exactTime • $ago";
}

function uploadMedia($fileArray, $targetDir, $default = null, $maxSizeMB = 2)
{
    if (isset($fileArray) && $fileArray['error'] === UPLOAD_ERR_OK) {
        // Enforce limit based on parameter (default 2MB)
        if ($fileArray['size'] > $maxSizeMB * 1024 * 1024) {
            return 'SIZE_EXCEEDED';
        }
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/pjpeg', 'video/mp4', 'video/webm', 'video/ogg'];
        if (in_array(strtolower($fileArray['type']), $allowedTypes)) {
            $extension = pathinfo($fileArray['name'], PATHINFO_EXTENSION);
            $fileName = uniqid() . '.' . $extension;
            $targetFilePath = __DIR__ . '/../public/images/' . $targetDir . '/' . $fileName;

            if (move_uploaded_file($fileArray['tmp_name'], $targetFilePath)) {
                return $fileName;
            }
        }
    }
    return $default;
}

function isVideo($filename)
{
    if (!$filename)
        return false;
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, ['mp4', 'webm', 'ogg']);
}
?>