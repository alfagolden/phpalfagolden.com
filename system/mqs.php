<?php
// بدء الجلسة والتحقق من تسجيل الدخول
session_start();

// التحقق من تسجيل الدخول

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true || !isset($_SESSION['user_id'])) {
    // المستخدم غير مسجل دخوله، إعادة توجيه لصفحة تسجيل الدخول
    header('Location: https://alfagolden.com/system/login.php');
    exit;
}

