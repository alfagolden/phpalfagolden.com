<?php
// Configuration
const API_TOKEN = 'h5qAt85gtiJDAzpH51WrXPywhmnhrPWy';
const TABLE_ID = 698; // جدول الكتالوجات
const BASE_URL = 'https://base.alfagolden.com/api/database/rows/table/';
const UPLOAD_DIR = 'Uploads/';
const UPLOAD_URL = 'https://alfagolden.com/system/m/up.php';
// Initialize upload directory
function ensureUploadDirectory() {
    $dir = UPLOAD_DIR;
    if (!is_dir($dir)) {
        $permissions = [0755, 0775, 0777];
        foreach ($permissions as $perm) {
            if (mkdir($dir, $perm, true)) {
                error_log("✅ تم إنشاء مجلد الرفع: $dir بصلاحيات " . decoct($perm));
                break;
            }
        }
    }
    if (!is_writable($dir)) {
        $permissions = [0755, 0775, 0777];
        foreach ($permissions as $perm) {
            if (chmod($dir, $perm) && is_writable($dir)) {
                error_log("✅ تم إصلاح صلاحيات المجلد: $dir إلى " . decoct($perm));
                break;
            }
        }
        if (!is_writable($dir)) {
            error_log("❌ المجلد $dir غير قابل للكتابة");
            throw new Exception('مجلد الرفع غير قابل للكتابة');
        }
    }
    return true;
}
try {
    ensureUploadDirectory();
} catch (Exception $e) {
    error_log("❌ خطأ في إعداد مجلد الرفع: " . $e->getMessage());
}
// External image upload function
function uploadImageExternal($file) {
    try {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('ملف غير صالح: ' . ($file['error'] ?? 'غير محدد'));
        }
        if (!file_exists($file['tmp_name']) || !is_readable($file['tmp_name'])) {
            throw new Exception('الملف المؤقت غير موجود أو غير قابل للقراءة');
        }
        $postData = ['image' => new CURLFile($file['tmp_name'], $file['type'], $file['name'])];
        $uploadUrl = UPLOAD_URL;
        error_log("📤 بدء رفع الصورة إلى: $uploadUrl");
        error_log("📎 تفاصيل الملف: " . json_encode(['name' => $file['name'], 'size' => $file['size'], 'type' => $file['type']]));
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $uploadUrl,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);
        error_log("🌐 استجابة HTTP: $httpCode");
        if ($curlError) {
            error_log("❌ خطأ cURL: $curlError");
            throw new Exception("خطأ في الاتصال بخدمة الرفع: $curlError");
        }
        if ($httpCode !== 200) {
            error_log("❌ فشل الرفع، كود HTTP: $httpCode");
            throw new Exception("خطأ في خدمة الرفع - كود: $httpCode");
        }
        $data = json_decode($response, true);
        if (!$data || !isset($data['success']) || !$data['success']) {
            $errorMsg = isset($data['message']) ? $data['message'] : 'فشل في الرفع';
            error_log("❌ استجابة الخدمة: " . json_encode($data));
            throw new Exception($errorMsg);
        }
        error_log("✅ تم رفع الصورة بنجاح: " . $data['url']);
        return ['success' => true, 'url' => $data['url'], 'message' => 'تم الرفع بنجاح'];
    } catch (Exception $e) {
        error_log("❌ خطأ أثناء رفع الصورة: " . $e->getMessage());
        return uploadImageDirect($file);
    }
}
// Direct image upload function
function uploadImageDirect($file) {
    try {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('ملف غير صالح: ' . ($file['error'] ?? 'غير محدد'));
        }
        if (!file_exists($file['tmp_name']) || !is_readable($file['tmp_name'])) {
            throw new Exception('الملف المؤقت غير موجود أو غير قابل للقراءة');
        }
        $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!isset($allowedTypes[$mimeType])) throw new Exception('نوع الملف غير مدعوم');
        if ($file['size'] > 5 * 1024 * 1024) throw new Exception('حجم الملف كبير جدًا');
        $filename = 'img_' . uniqid() . '_' . time() . '.' . $allowedTypes[$mimeType];
        $filepath = UPLOAD_DIR . $filename;
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception('فشل في نقل الملف');
        }
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $fullUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/' . $filepath;
        error_log("✅ تم الرفع الاحتياطي بنجاح: $fullUrl");
        return ['success' => true, 'url' => $fullUrl];
    } catch (Exception $e) {
        error_log("❌ خطأ في الرفع الاحتياطي: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
// Initialize variables
$message = '';
$message_type = ''; // 'success' or 'error'
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$page_size = isset($_GET['page_size']) ? (int)$_GET['page_size'] : 10;
$selected_location = isset($_GET['location']) ? $_GET['location'] : 'كتلوجات';
$catalogs = [];
$total_count = 0;
$next_page_url = null;
$previous_page_url = null;
$locations = ['كتلوجات', 'سلايدر العملاء', 'سلايدر الهيدر']; // Dynamic array of locations
// Handle form submission for adding a catalog
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_catalog'])) {
    $order = $_POST['order'] ?? '';
    $sub_order = $_POST['sub_order'] ?? '';
    $name_ar = $_POST['name_ar'] ?? '';
    $name_en = $_POST['name_en'] ?? '';
    $sub_name_ar = $_POST['sub_name_ar'] ?? '';
    $sub_name_en = $_POST['sub_name_en'] ?? '';
    $status = $_POST['status'] ?? '';
    $description_ar = $_POST['description_ar'] ?? '';
    $description_en = $_POST['description_en'] ?? '';
    $link = $_POST['link'] ?? '';
    $file_id = $_POST['file_id'] ?? '';
    $location = $_POST['location'] ?? 'كتالوجات';
    $catalog_image = '';
    // Handle image upload
    if (isset($_FILES['catalog_image']) && $_FILES['catalog_image']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadImageExternal($_FILES['catalog_image']);
        if ($uploadResult['success']) {
            $catalog_image = $uploadResult['url'];
        } else {
            $message = $uploadResult['message'];
            $message_type = 'error';
        }
    }
    if (!$message && $name_ar) {
        $data = [
            'field_6759' => $order,
            'field_6760' => $sub_order,
            'field_6754' => $name_ar,
            'field_6755' => $catalog_image,
            'field_6756' => $location,
            'field_6757' => $link,
            'field_6758' => $file_id,
            'field_6761' => $sub_name_ar,
            'field_6762' => $name_en,
            'field_7072' => $status,
            'field_7075' => $sub_name_en,
            'field_7076' => $description_ar,
            'field_7077' => $description_en
        ];
        $ch = curl_init(BASE_URL . TABLE_ID . '/?user_field_names=false');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Token ' . API_TOKEN,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        error_log("📤 إضافة كتالوج: HTTP $http_code, البيانات: " . json_encode($data));
        if ($curl_error) error_log("❌ خطأ cURL: $curl_error");
        if ($http_code === 200) {
            $message = 'تم إضافة الكتالوج بنجاح!';
            $message_type = 'success';
        } else {
            $message = 'فشل إضافة الكتالوج. تحقق من البيانات أو الاتصال.';
            $message_type = 'error';
            error_log("❌ فشل إضافة الكتالوج: HTTP $http_code, الاستجابة: $response");
        }
    } else if (!$name_ar) {
        $message = 'اسم الكتالوج (بالعربية) مطلوب.';
        $message_type = 'error';
    }
}
// Handle catalog update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_catalog'])) {
    $catalog_id = (int)$_POST['catalog_id'];
    $order = $_POST['order'] ?? '';
    $sub_order = $_POST['sub_order'] ?? '';
    $name_ar = $_POST['name_ar'] ?? '';
    $name_en = $_POST['name_en'] ?? '';
    $sub_name_ar = $_POST['sub_name_ar'] ?? '';
    $sub_name_en = $_POST['sub_name_en'] ?? '';
    $status = $_POST['status'] ?? '';
    $description_ar = $_POST['description_ar'] ?? '';
    $description_en = $_POST['description_en'] ?? '';
    $link = $_POST['link'] ?? '';
    $file_id = $_POST['file_id'] ?? '';
    $location = $_POST['location'] ?? 'كتالوجات';
    $catalog_image = $_POST['current_image'] ?? '';
    // Handle image upload
    if (isset($_FILES['catalog_image']) && $_FILES['catalog_image']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadImageExternal($_FILES['catalog_image']);
        if ($uploadResult['success']) {
            $catalog_image = $uploadResult['url'];
        } else {
            $message = $uploadResult['message'];
            $message_type = 'error';
        }
    }
    if (!$message && $name_ar) {
        $data = [
            'field_6759' => $order,
            'field_6760' => $sub_order,
            'field_6754' => $name_ar,
            'field_6755' => $catalog_image,
            'field_6756' => $location,
            'field_6757' => $link,
            'field_6758' => $file_id,
            'field_6761' => $sub_name_ar,
            'field_6762' => $name_en,
            'field_7072' => $status,
            'field_7075' => $sub_name_en,
            'field_7076' => $description_ar,
            'field_7077' => $description_en
        ];
        $ch = curl_init(BASE_URL . TABLE_ID . '/' . $catalog_id . '/?user_field_names=true');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Token ' . API_TOKEN,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        error_log("📤 تحديث البيانات: HTTP $http_code, البيانات: " . json_encode($data));
        if ($curl_error) error_log("❌ خطأ cURL: $curl_error");
        if ($http_code === 200) {
            $message = 'تم تحديث البيانات بنجاح!';
            $message_type = 'success';
        } else {
            $message = 'فشل تحديث البيانات. تحقق من البيانات أو الاتصال.';
            $message_type = 'error';
            error_log("❌ فشل تحديث البيانات: HTTP $http_code, الاستجابة: $response");
        }
    } else if (!$name_ar) {
        $message = 'اسم الكتالوج (بالعربية) مطلوب.';
        $message_type = 'error';
    }
}
// Handle catalog deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_catalog'])) {
    $catalog_id = (int)$_POST['catalog_id'];
    $ch = curl_init(BASE_URL . TABLE_ID . '/' . $catalog_id . '/');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Token ' . API_TOKEN
    ]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    error_log("📤 حذف البيانات: ID $catalog_id, HTTP $http_code");
    if ($curl_error) error_log("❌ خطأ cURL: $curl_error");
    if ($http_code === 204) {
        $message = 'تم حذف البيانات بنجاح!';
        $message_type = 'success';
    } else {
        $message = 'فشل حذف البيانات. تحقق من الاتصال.';
        $message_type = 'error';
        error_log("❌ فشل حذف البيانات: HTTP $http_code, الاستجابة: $response");
    }
}
// Fetch catalogs from Baserow with filter on selected location
$filter_param = 'filter__field_6756__contains=' . urlencode($selected_location);
$ch = curl_init(BASE_URL . TABLE_ID . '/?' . $filter_param . '&user_field_names=false&size=' . $page_size . '&page=' . $page);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Token ' . API_TOKEN,
    'Content-Type: application/json'
]);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
if ($http_code < 210) {
    $data = json_decode($response, true);
    $catalogs = $data['results'] ?? [];
    $total_count = $data['count'] ?? 0;
    $next_page_url = $data['next'] ?? null;
    $previous_page_url = $data['previous'] ?? null;
} else {
    $message = 'فشل جلب البيانات من Baserow. تحقق من التوكن أو الاتصال.';
    $message_type = 'error';
    error_log("❌ فشل جلب البيانات: HTTP $http_code, الاستجابة: $response");
    if ($curl_error) error_log("❌ خطأ cURL: $curl_error");
}
curl_close($ch);
// Calculate total pages
$total_pages = ceil($total_count / $page_size);
// Status options for the form
$statuses = ['نشط', 'غير نشط'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الصفحة الرئيسية - Baserow</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --gold: #977e2b;
            --gold-hover: #b89635;
            --gold-light: rgba(151, 126, 43, 0.1);
            --dark-gray: #2c2c2c;
            --medium-gray: #666;
            --light-gray: #f8f9fa;
            --white: #ffffff;
            --border-color: #e5e7eb;
            --success: #28a745;
            --error: #dc3545;
        }
        body {
            font-family: 'Cairo', sans-serif;
            font-size: 16px;
            direction: rtl;
            background: var(--light-gray);
            color: var(--dark-gray);
            margin: 0;
            padding: 0;
        }
        .container {
            padding: 24px;
            max-width: 1400px;
            margin: 0 auto;
        }
        .card {
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid var(--border-color);
            margin-bottom: 24px;
            transition: all 0.3s ease;
        }
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }
        .card-header {
            padding: 24px 28px;
            border-bottom: 1px solid var(--border-color);
            background: var(--light-gray);
            border-radius: 12px 12px 0 0;
        }
        .card-title {
            font-size: 22px;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--dark-gray);
        }
        .btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            font-family: inherit;
        }
        .btn-primary {
            background: var(--gold);
            color: var(--white);
        }
        .btn-primary:hover {
            background: var(--gold-hover);
            transform: translateY(-1px);
        }
        .btn-secondary {
            background: var(--medium-gray);
            color: var(--white);
        }
        .btn-secondary:hover {
            background: #555;
            transform: translateY(-1px);
        }
        .btn-sm {
            padding: 10px 20px;
            font-size: 14px;
        }
        .btn.rounded-circle {
            width: 40px;
            height: 40px;
            padding: 0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .form-group {
            margin-bottom: 24px;
        }
        .form-label {
            display: block;
            font-size: 16px;
            font-weight: 500;
            color: var(--dark-gray);
            margin-bottom: 8px;
        }
        .form-control {
            width: 100%;
            padding: 14px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            font-family: inherit;
            box-sizing: border-box;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--gold);
            box-shadow: 0 0 0 3px var(--gold-light);
        }
        .breadcrumb {
            display: flex;
            align-items: center;
            margin: 0;
            padding: 0;
            list-style: none;
            font-size: 14px;
        }
        .breadcrumb-link {
            color: var(--medium-gray);
            text-decoration: none;
            transition: color 0.2s ease;
        }
        .breadcrumb-link:hover {
            color: var(--gold);
        }
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-top: 20px;
        }
        .gallery-item {
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid var(--border-color);
            overflow: hidden;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .gallery-item:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .gallery-item-image {
            width: 100%;
            height: 200px;
            object-fit: contain;
            background: var(--light-gray);
            border-bottom: 1px solid var(--border-color);
        }
        .gallery-item-content {
            padding: 20px;
            position: relative;
        }
        .gallery-item-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark-gray);
            margin: 0 0 16px 0;
            text-align: center;
        }
        .gallery-item-actions {
            position: absolute;
            top: -50px;
            left: 16px;
            display: flex;
            gap: 8px;
            opacity: 0;
            transition: all 0.3s ease;
        }
        .gallery-item:hover .gallery-item-actions {
            opacity: 1;
            top: 16px;
        }
        .gallery-placeholder {
            width: 100%;
            height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--light-gray);
            border-bottom: 1px solid var(--border-color);
        }
        .gallery-placeholder i {
            font-size: 48px;
            color: var(--gold);
        }
        .image-preview {
            width: 120px;
            height: 120px;
            object-fit: contain;
            border: 1px solid var(--border-color);
            border-radius: 8px;
        }
        .spinner {
            width: 24px;
            height: 24px;
            border: 2px solid var(--border-color);
            border-top: 2px solid var(--gold);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.4);
        }
        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .modal-dialog {
            background: var(--white);
            border-radius: 12px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-header {
            padding: 24px 28px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-title {
            font-size: 20px;
            font-weight: 600;
            margin: 0;
        }
        .btn-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--medium-gray);
        }
        .modal-body {
            padding: 28px;
        }
        .modal-footer {
            padding: 24px 28px;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: 16px;
        }
        .toast-container {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1100;
        }
        .toast {
            background: var(--white);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 350px;
            font-size: 16px;
        }
        .toast.success {
            border-color: var(--success);
            color: var(--success);
        }
        .toast.error {
            border-color: var(--error);
            color: var(--error);
        }
        .image-upload-area {
            border: 2px dashed var(--border-color);
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
            background: var(--light-gray);
            cursor: pointer;
        }
        .image-upload-area:hover {
            border-color: var(--gold);
            background: var(--gold-light);
        }
        .image-upload-text {
            font-size: 16px;
            margin-bottom: 8px;
        }
        .image-upload-hint {
            font-size: 14px;
            color: var(--medium-gray);
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        .empty-state i {
            font-size: 64px;
            color: var(--medium-gray);
            margin-bottom: 20px;
        }
        .empty-state h3 {
            font-size: 20px;
            color: var(--dark-gray);
            margin-bottom: 12px;
        }
        .empty-state p {
            font-size: 16px;
            color: var(--medium-gray);
            margin-bottom: 24px;
        }
        .d-none {
            display: none !important;
        }
        .d-flex {
            display: flex;
        }
        .align-items-center {
            align-items: center;
        }
        .justify-content-between {
            justify-content: space-between;
        }
        .text-center {
            text-align: center;
        }
        .text-muted {
            color: var(--medium-gray);
        }
        .me-1 {
            margin-right: 4px;
        }
        .me-2 {
            margin-right: 8px;
        }
        .ms-2 {
            margin-left: 8px;
        }
        .mt-2 {
            margin-top: 8px;
        }
        .mt-3 {
            margin-top: 16px;
        }
        .mb-0 {
            margin-bottom: 0;
        }
        .mb-2 {
            margin-bottom: 8px;
        }
        .mb-3 {
            margin-bottom: 16px;
        }
        .mx-2 {
            margin-left: 8px;
            margin-right: 8px;
        }
        .tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 24px;
        }
        .tab {
            padding: 12px 24px;
            font-size: 16px;
            font-weight: 600;
            color: var(--medium-gray);
            background: var(--white);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        .tab.active {
            background: var(--gold);
            color: var(--white);
            border-color: var(--gold);
        }
        .tab:hover {
            background: var(--gold-light);
            color: var(--gold);
        }
        @media (max-width: 768px) {
            .container {
                padding: 16px;
            }
            .card {
                border-radius: 0;
                margin-left: -16px;
                margin-right: -16px;
            сначала
            .gallery-grid {
                gap: 16px;
            }
            .card-title {
                font-size: 18px;
            }
            .btn {
                font-size: 14px;
            }
            .modal-dialog {
                width: 95%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Breadcrumb and Header -->
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-2">
                                <li class="breadcrumb-item">
                                    <a href="#" class="breadcrumb-link">
                                        <i class="fas fa-layer-group me-1"></i>الكتالوجات
                                    </a>
                                </li>
                            </ol>
                        </nav>
                        <h1 class="card-title">إدارة الصفحة الرئيسية</h1>
                    </div>
                    <div>
                        <button id="addCatalogBtn" class="btn btn-primary" onclick="openAddModal()">
                            <i class="fas fa-plus me-2"></i>إضافة كتالوج جديد
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Tabs for Locations -->
        <div class="tabs">
            <?php foreach ($locations as $loc): ?>
                <a href="?location=<?= urlencode($loc) ?>&page=1&page_size=<?= $page_size ?>" class="tab <?= $selected_location === $loc ? 'active' : '' ?>">
                    <?= htmlspecialchars($loc) ?>
                </a>
            <?php endforeach; ?>
        </div>
        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <a href="?location=<?= urlencode($selected_location) ?>&page=<?= $page - 1 ?>&page_size=<?= $page_size ?>" class="btn btn-primary <?= $previous_page_url ? '' : 'd-none' ?>">
                <i class="fas fa-chevron-right me-2"></i>الصفحة السابقة
            </a>
            <div class="d-flex align-items-center gap-4">
                <form method="GET" class="d-flex align-items-center">
                    <input type="hidden" name="location" value="<?= htmlspecialchars($selected_location) ?>">
                    <label for="page_size" class="form-label me-2">عدد الكتالوجات في الصفحة:</label>
                    <select name="page_size" onchange="this.form.submit()" class="form-control">
                        <option value="10" <?= $page_size == 10 ? 'selected' : '' ?>>10</option>
                        <option value="20" <?= $page_size == 20 ? 'selected' : '' ?>>20</option>
                        <option value="50" <?= $page_size == 50 ? 'selected' : '' ?>>50</option>
                        <option value="100" <?= $page_size == 100 ? 'selected' : '' ?>>100</option>
                    </select>
                </form>
                <span class="text-muted">الصفحة <?= $page ?> من <?= $total_pages ?> (إجمالي الكتالوجات: <?= $total_count ?>)</span>
            </div>
            <a href="?location=<?= urlencode($selected_location) ?>&page=<?= $page + 1 ?>&page_size=<?= $page_size ?>" class="btn btn-primary <?= $next_page_url ? '' : 'd-none' ?>">
                الصفحة التالية<i class="fas fa-chevron-left ms-2"></i>
            </a>
        </div>
        <!-- Catalogs Grid -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><?= htmlspecialchars($selected_location) ?></h2>
            </div>
            <div class="card-body">
                <?php if (empty($catalogs)): ?>
                    <div class="empty-state">
                        <i class="fas fa-folder-plus"></i>
                        <h3>لا توجد كتالوجات</h3>
                        <p>ابدأ بإضافة أول كتالوج في "<?= htmlspecialchars($selected_location) ?>"</p>
                        <button onclick="openAddModal()" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>إضافة أول كتالوج
                        </button>
                    </div>
                <?php else: ?>
                    <div class="gallery-grid">
                        <?php foreach ($catalogs as $catalog): ?>
                            <div class="gallery-item">
                                <?php if (!empty($catalog['field_6755'])): ?>
                                    <img src="<?= htmlspecialchars($catalog['field_6755']) ?>" alt="<?= htmlspecialchars($catalog['field_6754'] ?? 'كتالوج') ?>" class="gallery-item-image">
                                <?php else: ?>
                                    <div class="gallery-placeholder"><i class="fas fa-folder"></i></div>
                                <?php endif; ?>
                                <div class="gallery-item-content">
                                    <h3 class="gallery-item-title"><?= htmlspecialchars($catalog['field_6754'] ?? 'غير متوفر') ?></h3>
                                    <div class="gallery-item-actions">
                                        <button onclick="openUpdateModal(<?= $catalog['id'] ?>, '<?= htmlspecialchars($catalog['field_6759'] ?? '') ?>', '<?= htmlspecialchars($catalog['field_6760'] ?? '') ?>', '<?= htmlspecialchars($catalog['field_6754'] ?? '') ?>', '<?= htmlspecialchars($catalog['field_6762'] ?? '') ?>', '<?= htmlspecialchars($catalog['field_6761'] ?? '') ?>', '<?= htmlspecialchars($catalog['field_7075'] ?? '') ?>', '<?= htmlspecialchars($catalog['field_7072'] ?? '') ?>', '<?= htmlspecialchars($catalog['field_6755'] ?? '') ?>', '<?= htmlspecialchars($catalog['field_6757'] ?? '') ?>', '<?= htmlspecialchars($catalog['field_6758'] ?? '') ?>', '<?= htmlspecialchars($catalog['field_7076'] ?? '') ?>', '<?= htmlspecialchars($catalog['field_7077'] ?? '') ?>', '<?= htmlspecialchars($catalog['field_6756'] ?? '') ?>')" class="btn btn-primary btn-sm rounded-circle">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="openDeleteModal(<?= $catalog['id'] ?>)" class="btn btn-secondary btn-sm rounded-circle">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <!-- Add Catalog Modal -->
        <div class="modal" id="addModal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">إضافة كتالوج جديد</h5>
                        <button type="button" class="btn-close" onclick="closeAddModal()">&times;</button>
                    </div>
                    <form id="addCatalogForm" enctype="multipart/form-data" method="POST">
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="addOrder" class="form-label">ترتيب</label>
                                <input type="text" class="form-control" id="addOrder" name="order">
                            </div>
                            <div class="form-group">
                                <label for="addSubOrder" class="form-label">ترتيب فرعي</label>
                                <input type="text" class="form-control" id="addSubOrder" name="sub_order">
                            </div>
                            <div class="form-group">
                                <label for="addNameAr" class="form-label">الاسم (بالعربية)</label>
                                <input type="text" class="form-control" id="addNameAr" name="name_ar" required>
                            </div>
                            <div class="form-group">
                                <label for="addNameEn" class="form-label">الاسم (بالإنجليزية)</label>
                                <input type="text" class="form-control" id="addNameEn" name="name_en">
                            </div>
                            <div class="form-group">
                                <label for="addSubNameAr" class="form-label">الاسم الفرعي (بالعربية)</label>
                                <input type="text" class="form-control" id="addSubNameAr" name="sub_name_ar">
                            </div>
                            <div class="form-group">
                                <label for="addSubNameEn" class="form-label">الاسم الفرعي (بالإنجليزية)</label>
                                <input type="text" class="form-control" id="addSubNameEn" name="sub_name_en">
                            </div>
                            <div class="form-group">
                                <label for="addStatus" class="form-label">الحالة</label>
                                <select class="form-control" id="addStatus" name="status">
                                    <?php foreach ($statuses as $status): ?>
                                        <option value="<?= htmlspecialchars($status) ?>"><?= htmlspecialchars($status) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="addLocation" class="form-label">الموقع</label>
                                <select class="form-control" id="addLocation" name="location">
                                    <?php foreach ($locations as $loc): ?>
                                        <option value="<?= htmlspecialchars($loc) ?>" <?= $selected_location === $loc ? 'selected' : '' ?>><?= htmlspecialchars($loc) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="addCatalogImage" class="form-label">صورة الكتالوج</label>
                                <div class="image-upload-area" id="addDropZone">
                                    <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                    <p class="image-upload-text text-muted mb-0">انقر هنا لاختيار صورة</p>
                                    <small class="image-upload-hint">أو اسحب الصورة هنا (الحد الأقصى 5 ميجا بايت)</small>
                                </div>
                                <input type="file" class="form-control d-none" id="addCatalogImage" name="catalog_image" accept="image/*">
                                <div id="addImagePreview" class="mt-3 d-none text-center">
                                    <img class="image-preview" alt="معاينة الصورة">
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="removeAddImagePreview()">
                                            <i class="fas fa-times me-1"></i>إزالة الصورة
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="addLink" class="form-label">الرابط</label>
                                <input type="url" class="form-control" id="addLink" name="link">
                            </div>
                            <div class="form-group">
                                <label for="addFileId" class="form-label">معرف الملف</label>
                                <input type="text" class="form-control" id="addFileId" name="file_id">
                            </div>
                            <div class="form-group">
                                <label for="addDescriptionAr" class="form-label">نص الوصف (بالعربية)</label>
                                <textarea class="form-control" id="addDescriptionAr" name="description_ar"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="addDescriptionEn" class="form-label">نص الوصف (بالإنجليزية)</label>
                                <textarea class="form-control" id="addDescriptionEn" name="description_en"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="closeAddModal()">إلغاء</button>
                            <button type="submit" class="btn btn-primary" name="add_catalog">
                                <span class="button-text">حفظ البيانات</span>
                                <span class="spinner d-none ms-2"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Update Catalog Modal -->
        <div class="modal" id="updateModal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">تحديث الكتالوج</h5>
                        <button type="button" class="btn-close" onclick="closeUpdateModal()">&times;</button>
                    </div>
                    <form id="updateCatalogForm" enctype="multipart/form-data" method="POST">
                        <div class="modal-body">
                            <input type="hidden" id="updateCatalogId" name="catalog_id">
                            <input type="hidden" id="currentImage" name="current_image">
                            <div class="form-group">
                                <label for="updateOrder" class="form-label">ترتيب</label>
                                <input type="text" class="form-control" id="updateOrder" name="order">
                            </div>
                            <div class="form-group">
                                <label for="updateSubOrder" class="form-label">ترتيب فرعي</label>
                                <input type="text" class="form-control" id="updateSubOrder" name="sub_order">
                            </div>
                            <div class="form-group">
                                <label for="updateNameAr" class="form-label">الاسم (بالعربية)</label>
                                <input type="text" class="form-control" id="updateNameAr" name="name_ar" required>
                            </div>
                            <div class="form-group">
                                <label for="updateNameEn" class="form-label">الاسم (بالإنجليزية)</label>
                                <input type="text" class="form-control" id="updateNameEn" name="name_en">
                            </div>
                            <div class="form-group">
                                <label for="updateSubNameAr" class="form-label">الاسم الفرعي (بالعربية)</label>
                                <input type="text" class="form-control" id="updateSubNameAr" name="sub_name_ar">
                            </div>
                            <div class="form-group">
                                <label for="updateSubNameEn" class="form-label">الاسم الفرعي (بالإنجليزية)</label>
                                <input type="text" class="form-control" id="updateSubNameEn" name="sub_name_en">
                            </div>
                            <div class="form-group">
                                <label for="updateStatus" class="form-label">الحالة</label>
                                <select class="form-control" id="updateStatus" name="status">
                                    <?php foreach ($statuses as $status): ?>
                                        <option value="<?= htmlspecialchars($status) ?>"><?= htmlspecialchars($status) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="updateLocation" class="form-label">الموقع</label>
                                <select class="form-control" id="updateLocation" name="location">
                                    <?php foreach ($locations as $loc): ?>
                                        <option value="<?= htmlspecialchars($loc) ?>"><?= htmlspecialchars($loc) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="updateCatalogImage" class="form-label">صورة الكتالوج</label>
                                <div class="image-upload-area" id="updateDropZone">
                                    <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                    <p class="image-upload-text text-muted mb-0">انقر هنا لاختيار صورة</p>
                                    <small class="image-upload-hint">أو اسحب الصورة هنا (الحد الأقصى 5 ميجا بايت)</small>
                                </div>
                                <input type="file" class="form-control d-none" id="updateCatalogImage" name="catalog_image" accept="image/*">
                                <div id="updateImagePreview" class="mt-3 d-none text-center">
                                    <img class="image-preview" alt="معاينة الصورة">
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="removeUpdateImagePreview()">
                                            <i class="fas fa-times me-1"></i>إزالة الصورة
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="updateLink" class="form-label">الرابط</label>
                                <input type="url" class="form-control" id="updateLink" name="link">
                            </div>
                            <div class="form-group">
                                <label for="updateFileId" class="form-label">معرف الملف</label>
                                <input type="text" class="form-control" id="updateFileId" name="file_id">
                            </div>
                            <div class="form-group">
                                <label for="updateDescriptionAr" class="form-label">نص الوصف (بالعربية)</label>
                                <textarea class="form-control" id="updateDescriptionAr" name="description_ar"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="updateDescriptionEn" class="form-label">نص الوصف (بالإنجليزية)</label>
                                <textarea class="form-control" id="updateDescriptionEn" name="description_en"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="closeUpdateModal()">إلغاء</button>
                            <button type="submit" class="btn btn-primary" name="update_catalog">
                                <span class="button-text">حفظ البيانات</span>
                                <span class="spinner d-none ms-2"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Delete Catalog Modal -->
        <div class="modal" id="deleteModal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">تأكيد الحذف</h5>
                        <button type="button" class="btn-close" onclick="closeDeleteModal()">&times;</button>
                    </div>
                    <form id="deleteCatalogForm" method="POST">
                        <div class="modal-body">
                            <p>هل أنت متأكد من حذف هذا الكتالوج؟</p>
                            <input type="hidden" id="deleteCatalogId" name="catalog_id">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">إلغاء</button>
                            <button type="submit" class="btn btn-primary" name="delete_catalog">
                                <span class="button-text">تأكيد</span>
                                <span class="spinner d-none ms-2"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Toast Container -->
        <div class="toast-container"></div>
    </div>
    <script>
        // Show toast notification
        function showToast(message, type = 'success') {
            const toastContainer = document.querySelector('.toast-container');
            const icon = type === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle';
            const className = type === 'error' ? 'error' : 'success';
            const toastHtml = `
                <div class="toast ${className}">
                    <i class="fas ${icon}"></i>
                    <span>${message}</span>
                    <button type="button" class="btn-close" onclick="this.parentElement.remove()" style="margin-right: auto; background: none; border: none; font-size: 18px; cursor: pointer;">&times;</button>
                </div>
            `;
            toastContainer.insertAdjacentHTML('beforeend', toastHtml);
            setTimeout(() => {
                const toasts = toastContainer.querySelectorAll('.toast');
                if (toasts.length > 0) toasts[0].remove();
            }, 5000);
        }
        // Show loading state
        function showLoading(button) {
            const text = button.querySelector('.button-text');
            const spinner = button.querySelector('.spinner');
            if (text) text.textContent = 'جاري الحفظ...';
            if (spinner) spinner.classList.remove('d-none');
            button.disabled = true;
        }
        // Hide loading state
        function hideLoading(button) {
            const text = button.querySelector('.button-text');
            const spinner = button.querySelector('.spinner');
            if (text) text.textContent = button.getAttribute('name') === 'delete_catalog' ? 'تأكيد' : 'حفظ البيانات';
            if (spinner) spinner.classList.add('d-none');
            button.disabled = false;
        }
        // Open add modal
        function openAddModal() {
            document.getElementById('addModal').classList.add('show');
            document.body.style.overflow = 'hidden';
            document.getElementById('addCatalogForm').reset();
            document.getElementById('addImagePreview').classList.add('d-none');
            document.getElementById('addDropZone').innerHTML = `
                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                <p class="image-upload-text text-muted mb-0">انقر هنا لاختيار صورة</p>
                <small class="image-upload-hint">أو اسحب الصورة هنا (الحد الأقصى 5 ميجا بايت)</small>
            `;
        }
        // Close add modal
        function closeAddModal() {
            document.getElementById('addModal').classList.remove('show');
            document.body.style.overflow = 'auto';
            document.getElementById('addCatalogForm').reset();
            document.getElementById('addImagePreview').classList.add('d-none');
            document.getElementById('addCatalogImage').value = '';
        }
        // Open delete modal
        function openDeleteModal(catalogId) {
            document.getElementById('deleteCatalogId').value = catalogId;
            document.getElementById('deleteModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        // Close delete modal
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('show');
            document.body.style.overflow = 'auto';
            document.getElementById('deleteCatalogId').value = '';
        }
        // Open update modal
        function openUpdateModal(catalogId, order, subOrder, nameAr, nameEn, subNameAr, subNameEn, status, catalogImage, link, fileId, descriptionAr, descriptionEn, location) {
            document.getElementById('updateCatalogId').value = catalogId;
            document.getElementById('updateOrder').value = order;
            document.getElementById('updateSubOrder').value = subOrder;
            document.getElementById('updateNameAr').value = nameAr;
            document.getElementById('updateNameEn').value = nameEn;
            document.getElementById('updateSubNameAr').value = subNameAr;
            document.getElementById('updateSubNameEn').value = subNameEn;
            document.getElementById('updateStatus').value = status;
            document.getElementById('updateLocation').value = location;
            document.getElementById('currentImage').value = catalogImage;
            document.getElementById('updateLink').value = link;
            document.getElementById('updateFileId').value = fileId;
            document.getElementById('updateDescriptionAr').value = descriptionAr;
            document.getElementById('updateDescriptionEn').value = descriptionEn;
            const preview = document.getElementById('updateImagePreview');
            const dropZone = document.getElementById('updateDropZone');
            if (catalogImage) {
                preview.querySelector('img').src = catalogImage;
                preview.classList.remove('d-none');
                dropZone.innerHTML = `
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <p class="image-upload-text text-success mb-0">تم اختيار الصورة بنجاح</p>
                    <small class="image-upload-hint">الصورة الحالية</small>
                `;
            } else {
                preview.classList.add('d-none');
                dropZone.innerHTML = `
                    <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                    <p class="image-upload-text text-muted mb-0">انقر هنا لاختيار صورة</p>
                    <small class="image-upload-hint">أو اسحب الصورة هنا (الحد الأقصى 5 ميجا بايت)</small>
                `;
            }
            document.getElementById('updateModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        // Close update modal
        function closeUpdateModal() {
            document.getElementById('updateModal').classList.remove('show');
            document.body.style.overflow = 'auto';
            document.getElementById('updateCatalogForm').reset();
            document.getElementById('updateImagePreview').classList.add('d-none');
            document.getElementById('updateCatalogImage').value = '';
        }
        // Remove image preview for add form
        function removeAddImagePreview() {
            const preview = document.getElementById('addImagePreview');
            const input = document.getElementById('addCatalogImage');
            const uploadArea = document.getElementById('addDropZone');
            preview.classList.add('d-none');
            input.value = '';
            uploadArea.innerHTML = `
                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                <p class="image-upload-text text-muted mb-0">انقر هنا لاختيار صورة</p>
                <small class="image-upload-hint">أو اسحب الصورة هنا (الحد الأقصى 5 ميجا بايت)</small>
            `;
        }
        // Remove image preview for update form
        function removeUpdateImagePreview() {
            const preview = document.getElementById('updateImagePreview');
            const input = document.getElementById('updateCatalogImage');
            const uploadArea = document.getElementById('updateDropZone');
            preview.classList.add('d-none');
            input.value = '';
            uploadArea.innerHTML = `
                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                <p class="image-upload-text text-muted mb-0">انقر هنا لاختيار صورة</p>
                <small class="image-upload-hint">أو اسحب الصورة هنا (الحد الأقصى 5 ميجا بايت)</small>
            `;
        }
        // Handle image upload
        function handleImageUpload(file, preview, uploadArea) {
            if (file) {
                if (!file.type.startsWith('image/')) {
                    showToast('يرجى اختيار ملف صورة صالح', 'error');
                    return;
                }
                if (file.size > 5 * 1024 * 1024) {
                    showToast('حجم الملف كبير جداً. الحد الأقصى 5 ميجا بايت', 'error');
                    return;
                }
                const reader = new FileReader();
                reader.onload = e => {
                    preview.querySelector('img').src = e.target.result;
                    preview.classList.remove('d-none');
                    uploadArea.innerHTML = `
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <p class="image-upload-text text-success mb-0">تم اختيار الصورة بنجاح</p>
                        <small class="image-upload-hint">${file.name} (${Math.round(file.size/1024)} كيلو بايت)</small>
                    `;
                };
                reader.readAsDataURL(file);
            }
        }
        // Setup image upload
        function setupImageUpload(inputId, previewId, dropZoneId) {
            const input = document.getElementById(inputId);
            const preview = document.getElementById(previewId);
            const uploadArea = document.getElementById(dropZoneId);
            input.addEventListener('change', e => handleImageUpload(e.target.files[0], preview, uploadArea));
            uploadArea.addEventListener('click', () => input.click());
            uploadArea.addEventListener('dragover', e => {
                e.preventDefault();
                uploadArea.style.borderColor = 'var(--gold)';
            });
            uploadArea.addEventListener('dragleave', e => {
                e.preventDefault();
                uploadArea.style.borderColor = 'var(--border-color)';
            });
            uploadArea.addEventListener('drop', e => {
                e.preventDefault();
                uploadArea.style.borderColor = 'var(--border-color)';
                const file = e.dataTransfer.files[0];
                if (file) {
                    input.files = e.dataTransfer.files;
                    handleImageUpload(file, preview, uploadArea);
                }
            });
        }
        // Form submission handling
        document.addEventListener('DOMContentLoaded', function() {
            setupImageUpload('addCatalogImage', 'addImagePreview', 'addDropZone');
            setupImageUpload('updateCatalogImage', 'updateImagePreview', 'updateDropZone');
            // Handle add form submission
            document.getElementById('addCatalogForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const submitButton = this.querySelector('button[type="submit"]');
                showLoading(submitButton);
                const formData = new FormData(this);
                formData.append('add_catalog', '1');
                fetch('', { method: 'POST', body: formData })
                    .then(response => response.text())
                    .then(() => {
                        hideLoading(submitButton);
                        showToast('تم إضافة الكتالوج بنجاح', 'success');
                        closeAddModal();
                        window.location.reload();
                    })
                    .catch(error => {
                        hideLoading(submitButton);
                        showToast('خطأ في الشبكة', 'error');
                    });
            });
            // Handle update form submission
            document.getElementById('updateCatalogForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const submitButton = this.querySelector('button[type="submit"]');
                showLoading(submitButton);
                const formData = new FormData(this);
                formData.append('update_catalog', '1');
                fetch('', { method: 'POST', body: formData })
                    .then(response => response.text())
                    .then(() => {
                        hideLoading(submitButton);
                        showToast('تم تحديث الكتالوج بنجاح', 'success');
                        closeUpdateModal();
                        window.location.reload();
                    })
                    .catch(error => {
                        hideLoading(submitButton);
                        showToast('خطأ في الشبكة', 'error');
                    });
            });
            // Handle delete form submission
            document.getElementById('deleteCatalogForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const submitButton = this.querySelector('button[type="submit"]');
                showLoading(submitButton);
                const formData = new FormData(this);
                formData.append('delete_catalog', '1');
                fetch('', { method: 'POST', body: formData })
                    .then(response => response.text())
                    .then(() => {
                        hideLoading(submitButton);
                        showToast('تم حذف الكتالوج بنجاح', 'success');
                        closeDeleteModal();
                        window.location.reload();
                    })
                    .catch(error => {
                        hideLoading(submitButton);
                        showToast('خطأ في الشبكة', 'error');
                    });
            });
            // Close modals on click outside or ESC key
            document.addEventListener('click', e => {
                if (e.target.classList.contains('modal')) {
                    document.querySelectorAll('.modal.show').forEach(modal => {
                        modal.classList.remove('show');
                        document.body.style.overflow = 'auto';
                    });
                }
            });
            document.addEventListener('keydown', e => {
                if (e.key === 'Escape') {
                    document.querySelectorAll('.modal.show').forEach(modal => {
                        modal.classList.remove('show');
                        document.body.style.overflow = 'auto';
                    });
                }
            });
            // Show PHP messages as toasts
            <?php if ($message): ?>
                showToast('<?= htmlspecialchars($message) ?>', '<?= $message_type ?>');
            <?php endif; ?>
        });
    </script>
</body>
</html>