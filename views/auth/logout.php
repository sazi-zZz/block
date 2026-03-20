<?php
require_once '../../includes/functions.php';
session_unset();
session_destroy();
redirect(BASE_URL . 'views/auth/login.php');
?>