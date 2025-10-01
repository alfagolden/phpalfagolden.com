<?php
session_start();

// --- [ بداية التحقق من تسجيل الدخول ] ---

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header('Location: https://alfagolden.com/system/login.php');
    exit;
}

