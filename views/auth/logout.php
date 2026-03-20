<?php
session_start();
session_unset();
session_destroy();
header("Location: /views/auth/login.php");
exit();
?>