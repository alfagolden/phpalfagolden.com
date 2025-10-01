<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true || !isset($_SESSION['user_id']) || !isset($_SESSION['login_time'])) {
    header('Location: https://alfagolden.com/system/login.php');
    exit;
}

