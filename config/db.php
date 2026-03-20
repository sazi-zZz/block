<?php

define('DB_HOST', 'DB_HOST_PLACEHOLDER');
define('DB_USER', 'DB_USER_PLACEHOLDER');
define('DB_PASS', 'DB_PASS_PLACEHOLDER');
define('DB_NAME', 'DB_NAME_PLACEHOLDER');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
}
catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>