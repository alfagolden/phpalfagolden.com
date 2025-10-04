<?php
// Configuration
const API_TOKEN = 'h5qAt85gtiJDAzpH51WrXPywhmnhrPWy';
const TABLE_ID = 698; // جدول الكتلوجات
const BASE_URL = 'https://base.alfagolden.com/api/database/rows/table/';
const UPLOAD_DIR = 'uploads/';
const UPLOAD_URL = 'https://alfagolden.com/system/managment/up.php';

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
$locations = ['كتلوجات', 'سلايدر العملاء', 'سلايدر الهيدر'];

// =============== Handle Order Change ===============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_order'])) {
    $catalog_id = (int)$_POST['catalog_id'];
    $direction = $_POST['direction'] ?? 'down';

    // Fetch all items in the same location
    $ch = curl_init(BASE_URL . TABLE_ID . '/?filter__field_6756__contains=' . urlencode($selected_location) . '&user_field_names=false');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Token ' . API_TOKEN]);
    $response = curl_exec($ch);
    curl_close($ch);
    $all = json_decode($response, true)['results'] ?? [];

    // Sort by order as number
    usort($all, function($a, $b) {
        $oa = is_numeric($a['field_6759']) ? (float)$a['field_6759'] : 999999;
        $ob = is_numeric($b['field_6759']) ? (float)$b['field_6759'] : 999999;
        return $oa <=> $ob;
    });

    // Find current index
    $currentIndex = null;
    foreach ($all as $index => $item) {
        if ($item['id'] == $catalog_id) {
            $currentIndex = $index;
            break;
        }
    }

    if ($currentIndex === null) {
        $message = 'العنصر غير موجود.';
        $message_type = 'error';
        goto skip_order;
    }

    $targetIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;
    if ($targetIndex < 0 || $targetIndex >= count($all)) {
        $message = 'لا يمكن التحرك أكثر من ذلك.';
        $message_type = 'error';
        goto skip_order;
    }

    // Swap using midpoint
    $currentOrder = is_numeric($all[$currentIndex]['field_6759']) ? (float)$all[$currentIndex]['field_6759'] : 999999;
    $targetOrder = is_numeric($all[$targetIndex]['field_6759']) ? (float)$all[$targetIndex]['field_6759'] : 999999;

    if ($direction === 'up') {
        $newOrder = $targetOrder - 10;
        if ($newOrder <= 0) {
            // Re-normalize all
            foreach ($all as $i => $item) {
                $newVal = ($i + 1) * 10;
                $patch = ['field_6759' => (string)$newVal];
                $ch = curl_init(BASE_URL . TABLE_ID . '/' . $item['id'] . '/');
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($patch));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Token ' . API_TOKEN,
                    'Content-Type: application/json'
                ]);
                curl_exec($ch);
                curl_close($ch);
            }
            $message = 'تم تحديث الترتيب بنجاح!';
            $message_type = 'success';
            goto skip_order;
        }
    } else {
        $newOrder = $targetOrder + 10;
    }

    // Update only the moved item
    $patch = ['field_6759' => (string)$newOrder];
    $ch = curl_init(BASE_URL . TABLE_ID . '/' . $catalog_id . '/');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($patch));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Token ' . API_TOKEN,
        'Content-Type: application/json'
    ]);
    curl_exec($ch);
    curl_close($ch);
    $message = 'تم تحديث الترتيب بنجاح!';
    $message_type = 'success';
}
skip_order:

// =============== Handle Add Catalog ===============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_catalog'])) {
    $location = $_POST['location'] ?? 'كتلوجات';
    $name_ar = '';
    $name_en = '';
    $link = '';
    $order = '';
    $catalog_image = '';

    if ($location === 'كتلوجات') {
        $name_ar = $_POST['name_ar'] ?? '';
        if (!$name_ar) {
            $message = 'اسم الكتالوج (عربي) مطلوب.';
            $message_type = 'error';
        }
        $name_en = $_POST['name_en'] ?? '';
    } elseif ($location === 'سلايدر الهيدر') {
        $link = $_POST['link'] ?? '';
        $order = $_POST['order'] ?? '1';
    }

    if (!$message) {
        if (isset($_FILES['catalog_image']) && $_FILES['catalog_image']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = uploadImageExternal($_FILES['catalog_image']);
            if ($uploadResult['success']) {
                $catalog_image = $uploadResult['url'];
            } else {
                $message = $uploadResult['message'];
                $message_type = 'error';
            }
        } else {
            $message = 'الصورة مطلوبة.';
            $message_type = 'error';
        }
    }

    if (!$message) {
        $data = [
            'field_6754' => $name_ar,
            'field_6755' => $catalog_image,
            'field_6756' => $location,
            'field_6757' => $link,
            'field_6759' => $order,
            'field_6760' => '',
            'field_6758' => '',
            'field_6761' => '',
            'field_6762' => $name_en,
            'field_7072' => '',
            'field_7075' => '',
            'field_7076' => '',
            'field_7077' => ''
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
        error_log("📤 إضافة: HTTP $http_code, البيانات: " . json_encode($data));
        if ($curl_error) error_log("❌ خطأ cURL: $curl_error");
        if ($http_code === 200) {
            $message = 'تم الإضافة بنجاح!';
            $message_type = 'success';
        } else {
            $message = 'فشل الإضافة.';
            $message_type = 'error';
            error_log("❌ فشل الإضافة: HTTP $http_code, الاستجابة: $response");
        }
    }
}

// =============== Handle Update Catalog ===============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_catalog'])) {
    $catalog_id = (int)$_POST['catalog_id'];
    $location = $_POST['location'] ?? 'كتلوجات';
    $name_ar = '';
    $name_en = '';
    $link = '';
    $order = '';
    $catalog_image = $_POST['current_image'] ?? '';

    if ($location === 'كتلوجات') {
        $name_ar = $_POST['name_ar'] ?? '';
        if (!$name_ar) {
            $message = 'اسم الكتالوج (عربي) مطلوب.';
            $message_type = 'error';
        }
        $name_en = $_POST['name_en'] ?? '';
    } elseif ($location === 'سلايدر الهيدر') {
        $link = $_POST['link'] ?? '';
        $order = $_POST['order'] ?? '1';
    }

    if (isset($_FILES['catalog_image']) && $_FILES['catalog_image']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadImageExternal($_FILES['catalog_image']);
        if ($uploadResult['success']) {
            $catalog_image = $uploadResult['url'];
        } else {
            $message = $uploadResult['message'];
            $message_type = 'error';
        }
    }

    if (!$message) {
        $data = [
            'field_6754' => $name_ar,
            'field_6755' => $catalog_image,
            'field_6756' => $location,
            'field_6757' => $link,
            'field_6759' => $order,
            'field_6760' => '',
            'field_6758' => '',
            'field_6761' => '',
            'field_6762' => $name_en,
            'field_7072' => '',
            'field_7075' => '',
            'field_7076' => '',
            'field_7077' => ''
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
        error_log("📤 تحديث: HTTP $http_code, البيانات: " . json_encode($data));
        if ($curl_error) error_log("❌ خطأ cURL: $curl_error");
        if ($http_code === 200) {
            $message = 'تم التحديث بنجاح!';
            $message_type = 'success';
        } else {
            $message = 'فشل التحديث.';
            $message_type = 'error';
            error_log("❌ فشل التحديث: HTTP $http_code, الاستجابة: $response");
        }
    }
}

// =============== Handle Delete Catalog ===============
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
    error_log("📤 حذف: ID $catalog_id, HTTP $http_code");
    if ($curl_error) error_log("❌ خطأ cURL: $curl_error");
    if ($http_code === 204) {
        $message = 'تم الحذف بنجاح!';
        $message_type = 'success';
    } else {
        $message = 'فشل الحذف.';
        $message_type = 'error';
        error_log("❌ فشل الحذف: HTTP $http_code, الاستجابة: $response");
    }
}

// =============== Fetch Catalogs ===============
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
    // Sort by order
    usort($catalogs, function($a, $b) {
        $oa = is_numeric($a['field_6759']) ? (float)$a['field_6759'] : 999999;
        $ob = is_numeric($b['field_6759']) ? (float)$b['field_6759'] : 999999;
        return $oa <=> $ob;
    });
    $total_count = $data['count'] ?? 0;
    $next_page_url = $data['next'] ?? null;
    $previous_page_url = $data['previous'] ?? null;
} else {
    $message = 'فشل جلب البيانات من Baserow.';
    $message_type = 'error';
    error_log("❌ فشل جلب البيانات: HTTP $http_code, الاستجابة: $response");
    if ($curl_error) error_log("❌ خطأ cURL: $curl_error");
}
curl_close($ch);

$total_pages = ceil($total_count / $page_size);
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
        .order-buttons {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        @media (max-width: 768px) {
            .container {
                padding: 16px;
            }
            .card {
                border-radius: 0;
                margin-left: -16px;
                margin-right: -16px;
            }
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
        <!-- Header -->
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h1 class="card-title">إدارة الصفحة الرئيسية</h1>
                    <button class="btn btn-primary" onclick="openAddModal('<?= htmlspecialchars($selected_location) ?>')">
                        <i class="fas fa-plus me-2"></i>إضافة جديد
                    </button>
                </div>
            </div>
        </div>

        <!-- Tabs -->
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
                    <label for="page_size" class="form-label me-2">عدد العناصر في الصفحة:</label>
                    <select name="page_size" onchange="this.form.submit()" class="form-control">
                        <option value="10" <?= $page_size == 10 ? 'selected' : '' ?>>10</option>
                        <option value="20" <?= $page_size == 20 ? 'selected' : '' ?>>20</option>
                        <option value="50" <?= $page_size == 50 ? 'selected' : '' ?>>50</option>
                        <option value="100" <?= $page_size == 100 ? 'selected' : '' ?>>100</option>
                    </select>
                </form>
                <span class="text-muted">الصفحة <?= $page ?> من <?= $total_pages ?> (إجمالي: <?= $total_count ?>)</span>
            </div>
            <a href="?location=<?= urlencode($selected_location) ?>&page=<?= $page + 1 ?>&page_size=<?= $page_size ?>" class="btn btn-primary <?= $next_page_url ? '' : 'd-none' ?>">
                الصفحة التالية<i class="fas fa-chevron-left ms-2"></i>
            </a>
        </div>

        <!-- Grid -->
        <div class="card">
            <div class="card-body">
                <?php if (empty($catalogs)): ?>
                    <div class="empty-state">
                        <i class="fas fa-folder-plus"></i>
                        <h3>لا توجد عناصر</h3>
                        <p>ابدأ بإضافة أول عنصر في "<?= htmlspecialchars($selected_location) ?>"</p>
                        <button onclick="openAddModal('<?= htmlspecialchars($selected_location) ?>')" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>إضافة جديد
                        </button>
                    </div>
                <?php else: ?>
                    <div class="gallery-grid">
                        <?php foreach ($catalogs as $catalog): ?>
                            <div class="gallery-item">
                                <?php if (!empty($catalog['field_6755'])): ?>
                                    <img src="<?= htmlspecialchars($catalog['field_6755']) ?>" alt="<?= htmlspecialchars($catalog['field_6754'] ?? 'عنصر') ?>" class="gallery-item-image">
                                <?php else: ?>
                                    <div class="gallery-placeholder"><i class="fas fa-folder"></i></div>
                                <?php endif; ?>
                                <div class="gallery-item-content">
                                    <h3 class="gallery-item-title"><?= htmlspecialchars($catalog['field_6754'] ?? '---') ?></h3>
                                    <div class="gallery-item-actions">
                                        <?php if (in_array($selected_location, ['سلايدر الهيدر', 'سلايدر العملاء'])): ?>
                                            <div class="order-buttons">
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('تحريك لأعلى؟')">
                                                    <input type="hidden" name="catalog_id" value="<?= $catalog['id'] ?>">
                                                    <input type="hidden" name="direction" value="up">
                                                    <input type="hidden" name="change_order" value="1">
                                                    <button type="submit" class="btn btn-secondary btn-sm rounded-circle">
                                                        <i class="fas fa-arrow-up"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('تحريك لأسفل؟')">
                                                    <input type="hidden" name="catalog_id" value="<?= $catalog['id'] ?>">
                                                    <input type="hidden" name="direction" value="down">
                                                    <input type="hidden" name="change_order" value="1">
                                                    <button type="submit" class="btn btn-secondary btn-sm rounded-circle">
                                                        <i class="fas fa-arrow-down"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                        <button onclick="openUpdateModal(<?= $catalog['id'] ?>, '<?= htmlspecialchars($catalog['field_6759'] ?? '') ?>', '<?= htmlspecialchars($catalog['field_6754'] ?? '') ?>', '<?= htmlspecialchars($catalog['field_6762'] ?? '') ?>', '<?= htmlspecialchars($catalog['field_6755'] ?? '') ?>', '<?= htmlspecialchars($catalog['field_6757'] ?? '') ?>', '<?= htmlspecialchars($catalog['field_6756'] ?? '') ?>')" class="btn btn-primary btn-sm rounded-circle">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('هل أنت متأكد من الحذف؟')">
                                            <input type="hidden" name="catalog_id" value="<?= $catalog['id'] ?>">
                                            <input type="hidden" name="delete_catalog" value="1">
                                            <button type="submit" class="btn btn-secondary btn-sm rounded-circle">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Add Modal -->
        <div class="modal" id="addCatalogModal">
            <div class="modal-dialog">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="location" id="addLocationInput">
                    <div class="modal-header">
                        <h5 class="modal-title">إضافة جديد</h5>
                        <button type="button" class="btn-close" onclick="closeAddModal()">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div id="catalogFields" class="d-none">
                            <div class="form-group">
                                <label class="form-label">الاسم (عربي) *</label>
                                <input type="text" name="name_ar" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">الاسم (إنجليزي)</label>
                                <input type="text" name="name_en" class="form-control">
                            </div>
                        </div>
                        <div id="headerSliderFields" class="d-none">
                            <div class="form-group">
                                <label class="form-label">الرابط</label>
                                <input type="url" name="link" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="form-label">الترتيب</label>
                                <input type="number" name="order" class="form-control" value="1">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">الصورة *</label>
                            <input type="file" name="catalog_image" class="form-control" accept="image/*" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeAddModal()">إلغاء</button>
                        <button type="submit" class="btn btn-primary" name="add_catalog">حفظ</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Update Modal -->
        <div class="modal" id="updateCatalogModal">
            <div class="modal-dialog">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="catalog_id" id="updateCatalogId">
                    <input type="hidden" name="current_image" id="updateCurrentImage">
                    <input type="hidden" name="location" id="updateLocationInput">
                    <div class="modal-header">
                        <h5 class="modal-title">تعديل العنصر</h5>
                        <button type="button" class="btn-close" onclick="closeUpdateModal()">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div id="updateCatalogFields" class="d-none">
                            <div class="form-group">
                                <label class="form-label">الاسم (عربي) *</label>
                                <input type="text" name="name_ar" id="updateNameAr" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">الاسم (إنجليزي)</label>
                                <input type="text" name="name_en" id="updateNameEn" class="form-control">
                            </div>
                        </div>
                        <div id="updateHeaderSliderFields" class="d-none">
                            <div class="form-group">
                                <label class="form-label">الرابط</label>
                                <input type="url" name="link" id="updateLink" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="form-label">الترتيب</label>
                                <input type="number" name="order" id="updateOrder" class="form-control">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">الصورة</label>
                            <input type="file" name="catalog_image" class="form-control" accept="image/*">
                            <small class="text-muted">اترك فارغًا للإبقاء على الصورة الحالية</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeUpdateModal()">إلغاء</button>
                        <button type="submit" class="btn btn-primary" name="update_catalog">حفظ</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Toast Container -->
        <div class="toast-container"></div>
    </div>

    <script>
        function showToast(message, type = 'success') {
            const toastContainer = document.querySelector('.toast-container');
            const icon = type === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle';
            const className = type === 'error' ? 'error' : 'success';
            const toastHtml = `
                <div class="toast ${className}">
                    <i class="fas ${icon}"></i>
                    <span>${message}</span>
                    <button type="button" class="btn-close" onclick="this.parentElement.remove()">&times;</button>
                </div>
            `;
            toastContainer.insertAdjacentHTML('beforeend', toastHtml);
            setTimeout(() => {
                const toasts = toastContainer.querySelectorAll('.toast');
                if (toasts.length > 0) toasts[0].remove();
            }, 5000);
        }
function openAddModal(location) {
    document.getElementById('addLocationInput').value = location;
    document.getElementById('catalogFields').classList.add('d-none');
    document.getElementById('headerSliderFields').classList.add('d-none');
    const nameArInput = document.querySelector('#catalogFields input[name="name_ar"]');
    
    if (location === 'كتلوجات') {
        document.getElementById('catalogFields').classList.remove('d-none');
        nameArInput.setAttribute('required', 'required'); // إضافة required عند الظهور
    } else {
        nameArInput.removeAttribute('required'); // إزالة required عند الإخفاء
        if (location === 'سلايدر الهيدر') {
            document.getElementById('headerSliderFields').classList.remove('d-none');
        }
    }
    document.getElementById('addCatalogModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function openUpdateModal(id, order, nameAr, nameEn, image, link, location) {
    document.getElementById('updateCatalogId').value = id;
    document.getElementById('updateCurrentImage').value = image;
    document.getElementById('updateLocationInput').value = location;
    document.getElementById('updateCatalogFields').classList.add('d-none');
    document.getElementById('updateHeaderSliderFields').classList.add('d-none');
    const nameArInput = document.querySelector('#updateCatalogFields input[name="name_ar"]');
    
    if (location === 'كتلوجات') {
        document.getElementById('updateCatalogFields').classList.remove('d-none');
        document.getElementById('updateNameAr').value = nameAr;
        document.getElementById('updateNameEn').value = nameEn;
        nameArInput.setAttribute('required', 'required'); // إضافة required عند الظهور
    } else {
        nameArInput.removeAttribute('required'); // إزالة required عند الإخفاء
        if (location === 'سلايدر الهيدر') {
            document.getElementById('updateHeaderSliderFields').classList.remove('d-none');
            document.getElementById('updateLink').value = link;
            document.getElementById('updateOrder').value = order;
        }
    }
    document.getElementById('updateCatalogModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}
     
        function closeAddModal() {
            document.getElementById('addCatalogModal').classList.remove('show');
            document.body.style.overflow = 'auto';
        }

      
        function closeUpdateModal() {
            document.getElementById('updateCatalogModal').classList.remove('show');
            document.body.style.overflow = 'auto';
        }

        document.addEventListener('DOMContentLoaded', function () {
            <?php if ($message): ?>
                showToast('<?= addslashes($message) ?>', '<?= $message_type ?>');
            <?php endif; ?>
        });
    </script>
</body>
</html>