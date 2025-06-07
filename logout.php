<?php
require_once 'config.php';

// تسجيل الخروج
session_destroy();
redirect('login.php');
?>
