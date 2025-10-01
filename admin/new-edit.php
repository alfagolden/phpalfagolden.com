<?php
/**
 * Secure Webhook Bridge - Tally to n8n
 * تحويل البيانات من Tally إلى n8n بشكل آمن مع تحميل الصور
 */

// إعدادات الأمان والتكوين
define('ALLOWED_IPS', [
    '34.102.136.180',  // Tally IP
    '34.147.162.110',  // Tally IP backup
    // يمكن إضافة IPs أخرى حسب الحاجة
]);

define('SECRET_KEY', 'your_secret_key_here'); // غير هذا المفتاح
define('N8N_WEBHOOK_URL', 'http://206.189.204.207:5678/webhook-test/alfa');

// إعدادات الصور
define('IMAGES_FOLDER', __DIR__ . '/images/'); // مجلد الصور المحلي
define('IMAGES_URL_BASE', 'https://alfagolden.com/admin/images/'); // رابط الموقع - غير هذا برابط موقعك
define('MAX_IMAGE_SIZE', 10 * 1024 * 1024); // 10MB كحد أقصى لحجم الصورة
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// تسجيل الأخطاء
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/webhook_errors.log');

// إنشاء مجلد الصور إذا لم يكن موجوداً
if (!is_dir(IMAGES_FOLDER)) {
    if (!mkdir(IMAGES_FOLDER, 0755, true)) {
        die('Failed to create images directory');
    }
}

/**
 * تسجيل الأنشطة
 */
function logActivity($message, $data = null) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message";
    if ($data) {
        $logMessage .= " | Data: " . json_encode($data, JSON_UNESCAPED_UNICODE);
    }
    file_put_contents(__DIR__ . '/webhook_activity.log', $logMessage . PHP_EOL, FILE_APPEND | LOCK_EX);
}

/**
 * التحقق من الأمان البسيط
 */
function isRequestValid() {
    // التحقق من طريقة الطلب
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return false;
    }
    
    // التحقق من Content-Type
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'application/json') === false) {
        return false;
    }
    
    // التحقق من وجود البيانات
    $input = file_get_contents('php://input');
    if (empty($input)) {
        return false;
    }
    
    return true;
}

/**
 * تحميل صورة من رابط Tally وحفظها محلياً
 */
function downloadImage($imageData) {
    try {
        $url = $imageData['url'];
        $originalName = $imageData['name'];
        $mimeType = $imageData['mimeType'];
        $size = $imageData['size'];
        
        // التحقق من نوع الملف المسموح
        if (!in_array($mimeType, ALLOWED_IMAGE_TYPES)) {
            throw new Exception("نوع الملف غير مسموح: $mimeType");
        }
        
        // التحقق من حجم الملف
        if ($size > MAX_IMAGE_SIZE) {
            throw new Exception("حجم الملف كبير جداً: " . formatBytes($size));
        }
        
        // إنشاء اسم ملف فريد
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $uniqueName = date('Y-m-d_H-i-s') . '_' . uniqid() . '.' . $extension;
        $localPath = IMAGES_FOLDER . $uniqueName;
        
        // تحميل الصورة
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'Tally-Image-Downloader/1.0'
        ]);
        
        $imageContent = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $downloadedSize = curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("خطأ في تحميل الصورة: $error");
        }
        
        if ($httpCode !== 200) {
            throw new Exception("فشل تحميل الصورة، كود HTTP: $httpCode");
        }
        
        if (empty($imageContent)) {
            throw new Exception("الصورة المحملة فارغة");
        }
        
        // حفظ الصورة محلياً
        if (file_put_contents($localPath, $imageContent) === false) {
            throw new Exception("فشل في حفظ الصورة محلياً");
        }
        
        // التحقق من حفظ الملف بنجاح
        if (!file_exists($localPath)) {
            throw new Exception("الملف لم يحفظ بشكل صحيح");
        }
        
        $localUrl = IMAGES_URL_BASE . $uniqueName;
        
        logActivity('تم تحميل الصورة بنجاح', [
            'original_name' => $originalName,
            'new_name' => $uniqueName,
            'size' => $downloadedSize,
            'local_url' => $localUrl
        ]);
        
        return [
            'success' => true,
            'original_name' => $originalName,
            'local_name' => $uniqueName,
            'local_path' => $localPath,
            'local_url' => $localUrl,
            'size' => $downloadedSize,
            'mime_type' => $mimeType
        ];
        
    } catch (Exception $e) {
        logActivity('فشل تحميل الصورة', [
            'error' => $e->getMessage(),
            'image_name' => $originalName ?? 'unknown'
        ]);
        
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'original_name' => $originalName ?? 'unknown'
        ];
    }
}

/**
 * معالجة الحقول وتحميل الصور
 */
function processFields($fields) {
    $processedFields = [];
    
    foreach ($fields as $field) {
        if (empty($field['value'])) {
            continue; // تخطي الحقول الفارغة
        }
        
        $processedField = [
            'label' => $field['label'],
            'type' => $field['type'],
            'value' => $field['value']
        ];
        
        // إذا كان الحقل عبارة عن رفع ملف
        if ($field['type'] === 'FILE_UPLOAD' && is_array($field['value'])) {
            $processedImages = [];
            
            foreach ($field['value'] as $file) {
                // التحقق من كون الملف صورة
                if (isset($file['mimeType']) && strpos($file['mimeType'], 'image/') === 0) {
                    $downloadResult = downloadImage($file);
                    
                    if ($downloadResult['success']) {
                        $processedImages[] = [
                            'id' => $file['id'],
                            'original_name' => $file['name'],
                            'local_name' => $downloadResult['local_name'],
                            'local_url' => $downloadResult['local_url'],
                            'size' => $downloadResult['size'],
                            'mime_type' => $file['mimeType']
                        ];
                    } else {
                        // في حالة فشل التحميل، احتفظ بالرابط الأصلي
                        $processedImages[] = [
                            'id' => $file['id'],
                            'original_name' => $file['name'],
                            'original_url' => $file['url'],
                            'size' => $file['size'],
                            'mime_type' => $file['mimeType'],
                            'download_error' => $downloadResult['error']
                        ];
                    }
                } else {
                    // ملف غير صورة، احتفظ بالبيانات الأصلية
                    $processedImages[] = $file;
                }
            }
            
            $processedField['value'] = $processedImages;
        }
        
        $processedFields[] = $processedField;
    }
    
    return $processedFields;
}

/**
 * تنسيق حجم الملف
 */
function formatBytes($size, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    
    return round($size, $precision) . ' ' . $units[$i];
}

/**
 * إرسال البيانات إلى n8n
 */
function sendToN8n($data) {
    // جرب POST أولاً
    $postResult = sendPostRequest($data);
    if ($postResult['success']) {
        return $postResult;
    }
    
    // إذا فشل POST، جرب GET
    logActivity('POST failed, trying GET method');
    return sendGetRequest($data);
}

/**
 * إرسال POST request
 */
function sendPostRequest($data) {
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => N8N_WEBHOOK_URL,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json; charset=utf-8',
            'User-Agent: Tally-to-N8N-Bridge/1.0'
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($error) {
        logActivity('POST cURL Error', ['error' => $error]);
        return ['success' => false, 'error' => $error];
    }
    
    if ($httpCode >= 200 && $httpCode < 400) {
        logActivity('POST Success', ['http_code' => $httpCode]);
        return [
            'success' => true,
            'method' => 'POST',
            'http_code' => $httpCode,
            'response' => $response
        ];
    }
    
    logActivity('POST Failed', ['http_code' => $httpCode, 'response' => substr($response, 0, 200)]);
    return ['success' => false, 'http_code' => $httpCode, 'response' => $response];
}

/**
 * إرسال GET request مع JSON في Body
 */
function sendGetRequest($data) {
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => N8N_WEBHOOK_URL,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_POSTFIELDS => json_encode($data, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json; charset=utf-8',
            'User-Agent: Tally-to-N8N-Bridge/1.0'
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($error) {
        throw new Exception("GET cURL Error: $error");
    }
    
    if ($httpCode >= 400) {
        throw new Exception("GET HTTP Error: $httpCode - Response: " . substr($response, 0, 200));
    }
    
    logActivity('GET Success', ['http_code' => $httpCode]);
    return [
        'success' => true,
        'method' => 'GET',
        'http_code' => $httpCode,
        'response' => $response
    ];
}

/**
 * معالجة الاستجابة
 */
function sendResponse($success, $message, $data = null) {
    http_response_code($success ? 200 : 400);
    header('Content-Type: application/json; charset=utf-8');
    
    $response = [
        'success' => $success,
        'message' => $message,
        'timestamp' => date('c')
    ];
    
    if ($data) {
        $response['data'] = $data;
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// بداية المعالجة الرئيسية
try {
    // التحقق من صحة الطلب
    if (!isRequestValid()) {
        logActivity('Invalid request received', [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        sendResponse(false, 'Invalid request format');
    }
    
    // قراءة البيانات الواردة
    $rawInput = file_get_contents('php://input');
    $inputData = json_decode($rawInput, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        logActivity('Invalid JSON received', ['raw_input' => substr($rawInput, 0, 500)]);
        sendResponse(false, 'Invalid JSON format');
    }
    
    // تسجيل البيانات الواردة
    logActivity('Data received from Tally', [
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'data_size' => strlen($rawInput)
    ]);
    
    // إضافة معلومات مفيدة فقط للبيانات
    $formData = $inputData['data'] ?? [];
    
    // معالجة حقول النموذج مع تحميل الصور
    $processedFields = [];
    if (isset($formData['fields'])) {
        $processedFields = processFields($formData['fields']);
    }
    
    // البيانات المنظمة والمفيدة فقط
    $cleanData = [
        'response_id' => $formData['responseId'] ?? null,
        'form_id' => $formData['formId'] ?? null,
        'form_name' => $formData['formName'] ?? null,
        'submitted_at' => $formData['createdAt'] ?? null,
        'fields' => $processedFields,
        'event_type' => $inputData['eventType'] ?? null,
        'processed_at' => date('c')
    ];
    
    // إرسال البيانات إلى n8n
    $result = sendToN8n($cleanData);
    
    // تسجيل النجاح
    logActivity('Data successfully forwarded to n8n', [
        'http_code' => $result['http_code'],
        'response_size' => strlen($result['response'])
    ]);
    
    // إرسال رد نجاح
    sendResponse(true, 'Data successfully forwarded to n8n', [
        'forwarded_at' => date('c'),
        'n8n_response_code' => $result['http_code']
    ]);
    
} catch (Exception $e) {
    // تسجيل الخطأ
    logActivity('Error occurred', [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    
    // إرسال رد خطأ
    sendResponse(false, 'Internal server error occurred');
}
?>