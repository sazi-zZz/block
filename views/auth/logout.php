<?php
session_start();
session_unset();
session_destroy();
header("Location: /block/views/auth/login.php");
exit();
?>