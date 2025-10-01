<?php
// upload_pdf.php

$logFile = __DIR__ . '/upload_pdf_debug.log';
function log_debug($msg) {
    global $logFile;
    file_put_contents($logFile, date("Y-m-d H:i:s") . " | " . $msg . "\n", FILE_APPEND);
}

// 1. بدء الاستقبال
log_debug("------ طلب جديد ------");

// 2. استقبال اسم الملف
if (!isset($_GET['filename']) || empty($_GET['filename'])) {
    log_debug("خطأ: filename غير موجود في الرابط");
    http_response_code(400);
    echo json_encode(['error' => 'filename is required']);
    exit;
}

$filename = basename($_GET['filename']);
log_debug("استقبال اسم ملف: $filename");

$uploadPath = __DIR__ . '/' . $filename;

// 3. استقبال البيانات
$input = file_get_contents('php://input');
log_debug("حجم البيانات المستقبلة: " . strlen($input) . " بايت");

if (empty($input)) {
    log_debug("خطأ: لم تصل أي بيانات ملف");
    http_response_code(400);
    echo json_encode(['error' => 'No file data received']);
    exit;
}

// 4. محاولة كتابة الملف
if (file_put_contents($uploadPath, $input) === false) {
    log_debug("خطأ: فشل حفظ الملف في: $uploadPath");
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save file']);
    exit;
} else {
    log_debug("تم حفظ الملف بنجاح في: $uploadPath");
}

// 5. تكوين رابط الملف النهائي
$fileUrl = 'https://alfagolden.com/system/pdf/' . rawurlencode($filename);
log_debug("رابط الملف: $fileUrl");

// 6. إرجاع الناتج
echo json_encode([
    'success' => true,
    'filename' => $filename,
    'url' => $fileUrl
]);

log_debug("تم إنهاء السكربت بنجاح");
// نهاية الكود
