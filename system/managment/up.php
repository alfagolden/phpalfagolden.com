<?php
/**
 * معالج رفع الصور المطور - up.php
 * نظام ألفا الذهبية لإدارة المنتجات
 * 
 * الميزات:
 * - نظام رفع آمن ومحسن
 * - تشخيص مفصل للأخطاء
 * - دعم أنواع الصور المختلفة
 * - حماية ضد الملفات الضارة
 * - إنشاء مجلدات تلقائي مع إصلاح الصلاحيات
 */

// إعدادات الرفع
define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_TYPES', [
    'image/jpeg' => 'jpg',
    'image/png' => 'png', 
    'image/gif' => 'gif',
    'image/webp' => 'webp'
]);

// تفعيل عرض الأخطاء للتشخيص المطور
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

/**
 * دالة إنشاء وإعداد مجلد الرفع المطورة
 */
function createUploadDirectory() {
    $dir = UPLOAD_DIR;
    $success = false;
    $details = [];
    
    try {
        // التحقق من وجود المجلد
        if (!is_dir($dir)) {
            $details[] = "المجلد غير موجود: $dir";
            
            // الحصول على معلومات المجلد الأب
            $parentDir = dirname($dir);
            $details[] = "المجلد الأب: $parentDir";
            $details[] = "المجلد الأب موجود: " . (is_dir($parentDir) ? 'نعم' : 'لا');
            $details[] = "المجلد الأب قابل للكتابة: " . (is_writable($parentDir) ? 'نعم' : 'لا');
            
            // محاولة إنشاء المجلد مع صلاحيات متدرجة
            $permissions = [0755, 0775, 0777];
            
            foreach ($permissions as $perm) {
                if (@mkdir($dir, $perm, true)) {
                    $details[] = "تم إنشاء المجلد بصلاحيات: " . decoct($perm);
                    $success = true;
                    break;
                }
            }
            
            if (!$success) {
                $details[] = "فشل في إنشاء المجلد مع جميع الصلاحيات المحاولة";
                return ['success' => false, 'message' => 'فشل في إنشاء مجلد الرفع', 'details' => $details];
            }
        } else {
            $details[] = "المجلد موجود: $dir";
            $success = true;
        }
        
        // التحقق من صلاحيات الكتابة
        if (!is_writable($dir)) {
            $details[] = "المجلد غير قابل للكتابة";
            $currentPerms = fileperms($dir);
            $details[] = "الصلاحيات الحالية: " . substr(sprintf('%o', $currentPerms), -4);
            
            // محاولة إصلاح الصلاحيات
            $permissions = [0755, 0775, 0777];
            $fixed = false;
            
            foreach ($permissions as $perm) {
                if (@chmod($dir, $perm) && is_writable($dir)) {
                    $details[] = "تم إصلاح الصلاحيات إلى: " . decoct($perm);
                    $fixed = true;
                    break;
                }
            }
            
            if (!$fixed) {
                $details[] = "فشل في إصلاح صلاحيات المجلد";
                return ['success' => false, 'message' => 'مجلد الرفع غير قابل للكتابة', 'details' => $details];
            }
        } else {
            $details[] = "المجلد قابل للكتابة";
        }
        
        return ['success' => true, 'message' => 'مجلد الرفع جاهز', 'details' => $details];
        
    } catch (Exception $e) {
        $details[] = "خطأ في الاستثناء: " . $e->getMessage();
        return ['success' => false, 'message' => 'خطأ في إعداد مجلد الرفع', 'details' => $details, 'exception' => $e->getMessage()];
    }
}

/**
 * دالة رفع وحفظ الصورة المطورة
 */
function uploadImage($file) {
    $response = [
        'success' => false,
        'message' => '',
        'url' => '',
        'details' => []
    ];
    
    try {
        // التحقق من وجود الملف
        if (!isset($file) || !is_array($file)) {
            throw new Exception('لم يتم إرسال ملف');
        }
        
        $response['details'][] = "اسم الملف: " . ($file['name'] ?? 'غير محدد');
        $response['details'][] = "حجم الملف: " . ($file['size'] ?? 0) . " بايت (" . round(($file['size'] ?? 0)/1024, 2) . " كيلو بايت)";
        $response['details'][] = "نوع الملف: " . ($file['type'] ?? 'غير محدد');
        $response['details'][] = "الملف المؤقت: " . ($file['tmp_name'] ?? 'غير محدد');
        
        // التحقق من أخطاء الرفع
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'حجم الملف أكبر من upload_max_filesize',
                UPLOAD_ERR_FORM_SIZE => 'حجم الملف أكبر من MAX_FILE_SIZE في النموذج',
                UPLOAD_ERR_PARTIAL => 'تم رفع الملف جزئياً فقط',
                UPLOAD_ERR_NO_FILE => 'لم يتم رفع أي ملف',
                UPLOAD_ERR_NO_TMP_DIR => 'مجلد مؤقت مفقود على الخادم',
                UPLOAD_ERR_CANT_WRITE => 'فشل في كتابة الملف على القرص',
                UPLOAD_ERR_EXTENSION => 'امتداد PHP أوقف رفع الملف'
            ];
            
            $errorMsg = $errorMessages[$file['error']] ?? 'خطأ غير معروف (' . $file['error'] . ')';
            throw new Exception($errorMsg);
        }
        
        // التحقق من حجم الملف
        if ($file['size'] === 0) {
            throw new Exception('الملف فارغ');
        }
        
        if ($file['size'] > MAX_FILE_SIZE) {
            $sizeMB = round($file['size'] / 1024 / 1024, 2);
            $maxMB = round(MAX_FILE_SIZE / 1024 / 1024, 2);
            throw new Exception("حجم الملف ({$sizeMB} ميجا) أكبر من الحد المسموح ({$maxMB} ميجا)");
        }
        
        // التحقق من نوع الملف
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $response['details'][] = "نوع MIME المكتشف: " . $mimeType;
        
        if (!isset(ALLOWED_TYPES[$mimeType])) {
            $allowedList = implode(', ', array_keys(ALLOWED_TYPES));
            throw new Exception("نوع الملف ($mimeType) غير مدعوم. الأنواع المسموحة: $allowedList");
        }
        
        // التحقق من أن الملف صورة فعلية
        $imageInfo = getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            throw new Exception('الملف ليس صورة صالحة');
        }
        
        $response['details'][] = "أبعاد الصورة: {$imageInfo[0]}x{$imageInfo[1]} بكسل";
        $response['details'][] = "نوع الصورة: " . $imageInfo['mime'];
        
        // إعداد مجلد الرفع
        $dirResult = createUploadDirectory();
        $response['details'] = array_merge($response['details'], $dirResult['details']);
        
        if (!$dirResult['success']) {
            throw new Exception($dirResult['message']);
        }
        
        // إنشاء اسم ملف فريد وآمن
        $extension = ALLOWED_TYPES[$mimeType];
        $filename = 'img_' . uniqid() . '_' . time() . '.' . $extension;
        $filepath = UPLOAD_DIR . $filename;
        
        $response['details'][] = "اسم الملف المولد: $filename";
        $response['details'][] = "مسار الحفظ: $filepath";
        
        // التحقق النهائي من الملف المؤقت
        if (!file_exists($file['tmp_name'])) {
            throw new Exception('الملف المؤقت غير موجود');
        }
        
        if (!is_readable($file['tmp_name'])) {
            throw new Exception('الملف المؤقت غير قابل للقراءة');
        }
        
        // رفع الملف
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            $lastError = error_get_last();
            $response['details'][] = "آخر خطأ PHP: " . ($lastError['message'] ?? 'غير محدد');
            throw new Exception('فشل في نقل الملف إلى المكان النهائي');
        }
        
        // التحقق من نجاح الرفع
        if (!file_exists($filepath)) {
            throw new Exception('الملف لم يتم إنشاؤه في المكان المحدد');
        }
        
        $uploadedSize = filesize($filepath);
        if ($uploadedSize !== $file['size']) {
            @unlink($filepath); // حذف الملف المعطوب
            throw new Exception("حجم الملف المرفوع ($uploadedSize) لا يطابق الأصلي ({$file['size']})");
        }
        
        // إنشاء الرابط المطلق
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
        $scriptDir = $scriptDir === '/' ? '' : $scriptDir;
        
        $fullUrl = $protocol . '://' . $host . $scriptDir . '/' . $filepath;
        
        $response['details'][] = "البروتوكول: $protocol";
        $response['details'][] = "المضيف: $host";
        $response['details'][] = "مجلد السكريبت: $scriptDir";
        $response['details'][] = "الرابط النهائي: $fullUrl";
        $response['details'][] = "✅ تم الرفع بنجاح";
        
        // النتيجة النهائية
        $response['success'] = true;
        $response['message'] = 'تم رفع الصورة بنجاح';
        $response['url'] = $fullUrl;
        $response['filename'] = $filename;
        $response['size'] = $file['size'];
        $response['type'] = $mimeType;
        $response['dimensions'] = $imageInfo[0] . 'x' . $imageInfo[1];
        
        // معلومات إضافية للتشخيص
        $response['upload_info'] = [
            'original_name' => $file['name'],
            'generated_name' => $filename,
            'upload_time' => date('Y-m-d H:i:s'),
            'file_size_kb' => round($file['size'] / 1024, 2),
            'mime_type' => $mimeType,
            'image_dimensions' => $imageInfo[0] . 'x' . $imageInfo[1]
        ];
        
    } catch (Exception $e) {
        $response['success'] = false;
        $response['message'] = $e->getMessage();
        $response['details'][] = "❌ خطأ: " . $e->getMessage();
        
        // إضافة معلومات تشخيصية إضافية
        $response['debug_info'] = [
            'php_version' => phpversion(),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_execution_time' => ini_get('max_execution_time'),
            'memory_limit' => ini_get('memory_limit'),
            'temp_dir' => sys_get_temp_dir(),
            'upload_tmp_dir' => ini_get('upload_tmp_dir') ?: 'افتراضي',
            'current_dir' => getcwd(),
            'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'غير محدد',
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'غير محدد',
            'disk_free_space' => is_dir(UPLOAD_DIR) ? disk_free_space(UPLOAD_DIR) : 'غير متاح'
        ];
        
        // إضافة تفاصيل إضافية للملف إذا كان متاحاً
        if (isset($file) && is_array($file)) {
            $response['file_debug'] = [
                'file_exists_tmp' => isset($file['tmp_name']) ? file_exists($file['tmp_name']) : false,
                'file_readable_tmp' => isset($file['tmp_name']) ? is_readable($file['tmp_name']) : false,
                'upload_dir_exists' => is_dir(UPLOAD_DIR),
                'upload_dir_writable' => is_writable(UPLOAD_DIR),
                'upload_dir_permissions' => is_dir(UPLOAD_DIR) ? substr(sprintf('%o', fileperms(UPLOAD_DIR)), -4) : 'غير متاح'
            ];
        }
    }
    
    return $response;
}

// معالجة طلبات الرفع
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST');
    header('Access-Control-Allow-Headers: Content-Type');
    
    // كتابة سجل بداية الطلب
    error_log("🚀 بدء معالجة طلب رفع جديد - " . date('Y-m-d H:i:s'));
    error_log("📋 معلومات الطلب: " . json_encode([
        'method' => $_SERVER['REQUEST_METHOD'],
        'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'غير محدد',
        'content_length' => $_SERVER['CONTENT_LENGTH'] ?? 'غير محدد',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'غير محدد'
    ]));
    
    try {
        if (!isset($_FILES['image'])) {
            throw new Exception('لم يتم إرسال ملف صورة');
        }
        
        error_log("📎 تفاصيل الملف المستلم: " . json_encode($_FILES['image']));
        
        $result = uploadImage($_FILES['image']);
        
        // كتابة السجل النهائي
        error_log("📊 نتيجة الرفع: " . json_encode([
            'success' => $result['success'],
            'message' => $result['message'],
            'url' => $result['url'] ?? 'غير متاح',
            'details_count' => count($result['details']),
            'timestamp' => date('Y-m-d H:i:s')
        ]));
        
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        $errorResponse = [
            'success' => false,
            'message' => $e->getMessage(),
            'details' => ["خطأ عام: " . $e->getMessage()],
            'timestamp' => date('Y-m-d H:i:s'),
            'error_type' => 'exception',
            'error_line' => $e->getLine(),
            'error_file' => basename($e->getFile())
        ];
        
        error_log("❌ خطأ في الرفع: " . $e->getMessage());
        echo json_encode($errorResponse, JSON_UNESCAPED_UNICODE);
    }
    
    exit;
}

// إذا تم الوصول للملف مباشرة دون POST - صفحة الحالة
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json; charset=utf-8');
    
    // فحص حالة مجلد الرفع
    $uploadDirStatus = createUploadDirectory();
    
    $status = [
        'service' => 'معالج رفع الصور المطور - ألفا الذهبية',
        'version' => '2.0',
        'status' => 'جاهز ومحسن',
        'upload_dir' => UPLOAD_DIR,
        'upload_dir_status' => $uploadDirStatus,
        'upload_dir_exists' => is_dir(UPLOAD_DIR),
        'upload_dir_writable' => is_writable(UPLOAD_DIR),
        'upload_dir_permissions' => is_dir(UPLOAD_DIR) ? substr(sprintf('%o', fileperms(UPLOAD_DIR)), -4) : 'غير متاح',
        'max_file_size' => MAX_FILE_SIZE,
        'max_file_size_mb' => round(MAX_FILE_SIZE / 1024 / 1024, 2) . ' ميجا بايت',
        'allowed_types' => array_keys(ALLOWED_TYPES),
        'php_settings' => [
            'php_version' => phpversion(),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_file_uploads' => ini_get('max_file_uploads'),
            'file_uploads' => ini_get('file_uploads') ? 'مفعل ✅' : 'معطل ❌',
            'max_execution_time' => ini_get('max_execution_time') . ' ثانية',
            'memory_limit' => ini_get('memory_limit')
        ],
        'server_info' => [
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'غير محدد',
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'غير محدد',
            'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'غير محدد',
            'current_dir' => getcwd(),
            'disk_free_space' => is_dir(UPLOAD_DIR) ? round(disk_free_space(UPLOAD_DIR) / 1024 / 1024 / 1024, 2) . ' جيجا بايت' : 'غير متاح'
        ],
        'timestamp' => date('Y-m-d H:i:s'),
        'features' => [
            '✅ نظام رفع آمن ومحسن',
            '✅ تشخيص مفصل للأخطاء',
            '✅ دعم أنواع الصور المختلفة',
            '✅ حماية ضد الملفات الضارة', 
            '✅ إنشاء مجلدات تلقائي',
            '✅ إصلاح الصلاحيات التلقائي',
            '✅ معلومات تشخيصية شاملة'
        ]
    ];
    
    echo json_encode($status, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// طرق أخرى غير مدعومة
header('HTTP/1.1 405 Method Not Allowed');
header('Allow: GET, POST');
echo json_encode([
    'success' => false,
    'message' => 'طريقة الطلب غير مدعومة. استخدم GET للحالة أو POST للرفع',
    'allowed_methods' => ['GET', 'POST'],
    'received_method' => $_SERVER['REQUEST_METHOD']
], JSON_UNESCAPED_UNICODE);
?>