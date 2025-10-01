<?php
// المفتاح السري اللي بتستخدمه مع n8n
define('API_KEY', 'f3b1a9d84e2c6f7b8d0e1a3c4f5b6e7d9a0b1c2d3e4f5061728394a5b6c7d8e9');

// الملفات المسموح بتعديلها فقط
$allowedFiles = ['c.html', 'h.html', 'index.html', 'p.html'];

// التحقق من الهيدر Authorization
$headers = apache_request_headers();
if (!isset($headers['Authorization']) || $headers['Authorization'] !== 'Bearer ' . API_KEY) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// التحقق من نوع الطلب
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// استلام البيانات
$filename = null;
$content = null;

if (isset($_POST['filename']) && isset($_POST['content'])) {
    $filename = $_POST['filename'];
    $content = $_POST['content'];
} else {
    $rawData = file_get_contents('php://input');
    if ($rawData) {
        $jsonData = json_decode($rawData, true);
        if ($jsonData && isset($jsonData['filename']) && isset($jsonData['content'])) {
            $filename = $jsonData['filename'];
            $content = $jsonData['content'];
        }
    }
}

// تحقق من وجود اسم الملف والمحتوى
if (!$filename || !$content) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing filename or content']);
    exit;
}

$filename = basename($filename);

// تحقق من أن الملف مسموح
if (!in_array($filename, $allowedFiles)) {
    http_response_code(403);
    echo json_encode(['error' => 'File not allowed']);
    exit;
}

// مسار واحد فقط: نفس مجلد update.php
$filePath = __DIR__ . '/' . $filename;

// محاولة كتابة المحتوى
$result = @file_put_contents($filePath, $content);

if ($result !== false) {
    echo json_encode([
        'success' => true, 
        'message' => "$filename updated successfully",
        'file_path' => $filePath,
        'bytes_written' => $result
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'ما قدر']);
}
?>