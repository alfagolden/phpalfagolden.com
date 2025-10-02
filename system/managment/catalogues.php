<?php
// Configuration
const API_TOKEN = 'h5qAt85gtiJDAzpH51WrXPywhmnhrPWy';
const TABLE_ID = 698; // جدول الكتالوجات
const BASE_URL = 'https://base.alfagolden.com/api/database/rows/table/';
const UPLOAD_DIR = 'uploads/';
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
$selected_location = isset($_GET['location']) ? $_GET['location'] : 'كتالوجات';
$catalogs = [];
$total_count = 0;
$next_page_url = null;
$previous_page_url = null;
$locations = ['كتلوجات', 'سلايدر العملاء','سلايدر الهيدر']; // Dynamic array of locations

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
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            color: #1e293b;
        }
        .container {
            max-width: 80rem;
            margin: 0 auto;
            padding: 1.5rem;
        }
        .card {
            background: #ffffff;
            border-radius: 1rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
        }
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .btn-primary {
            background: #1e40af;
            color: #ffffff;
        }
        .btn-primary:hover {
            background: #1e3a8a;
        }
        .btn-secondary {
            background: #e5e7eb;
            color: #1e293b;
        }
        .btn-secondary:hover {
            background: #d1d5db;
        }
        .btn-danger {
            background: #dc2626;
            color: #ffffff;
        }
        .btn-danger:hover {
            background: #b91c1c;
        }
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease-in-out;
        }
        .modal:not(.hidden) {
            opacity: 1;
            pointer-events: auto;
        }
        .modal-content {
            background: #ffffff;
            border-radius: 1rem;
            padding: 2rem;
            max-width: 90%;
            width: 32rem;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        }
        .input, .select, .textarea {
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 0.75rem;
            font-size: 0.875rem;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .input:focus, .select:focus, .textarea:focus {
            border-color: #1e40af;
            box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1);
            outline: none;
        }
        .drop-zone {
            border: 2px dashed #d1d5db;
            padding: 1.5rem;
            text-align: center;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: border-color 0.3s ease, background-color 0.3s ease;
        }
        .drop-zone.dragover {
            border-color: #1e40af;
            background-color: #eff6ff;
        }
        .image-preview {
            max-width: 120px;
            max-height: 120px;
            object-fit: cover;
            border-radius: 0.5rem;
            border: 1px solid #e5e7eb;
        }
        .toast {
            position: fixed;
            bottom: 1.5rem;
            right: 1.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            color: #ffffff;
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }
        .toast.show {
            opacity: 1;
        }
        .toast.success {
            background: #1e40af;
        }
        .toast.error {
            background: #dc2626;
        }
        .tab {
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            color: #64748b;
            border-bottom: 2px solid transparent;
            transition: color 0.3s ease, border-bottom 0.3s ease;
        }
        .tab.active {
            color: #1e40af;
            border-bottom: 2px solid #1e40af;
        }
        .tab:hover {
            color: #1e3a8a;
        }
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 0.5rem;
            overflow: hidden;
        }
        th, td {
            padding: 1rem;
            text-align: right;
        }
        th {
            background: #f8fafc;
            font-weight: 600;
            color: #1e293b;
        }
        tr:hover {
            background: #f1f5f9;
        }
        @media (max-width: 640px) {
            .container {
                padding: 1rem;
            }
            .modal-content {
                width: 95%;
            }
            .btn {
                padding: 0.5rem 1rem;
            }
            .input, .select, .textarea {
                font-size: 0.85rem;
            }
            th, td {
                padding: 0.75rem;
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-2xl md:text-3xl font-bold text-center mb-8 text-gray-800">إدارة الصفحة الرئيسية</h1>

        <!-- Tabs for Locations -->
        <div class="flex flex-wrap gap-4 mb-6 border-b border-gray-200">
            <?php foreach ($locations as $loc): ?>
                <a href="?location=<?= urlencode($loc) ?>&page=1&page_size=<?= $page_size ?>" class="tab <?= $selected_location === $loc ? 'active' : '' ?>">
                    <?= htmlspecialchars($loc) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
            <div class="p-4 mb-6 rounded-lg bg-opacity-90 <?= $message_type === 'success' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Add catalog form -->
        <div class="card p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-700 mb-6">إضافة كتالوج جديد</h2>
            <form id="addCatalogForm" method="POST" enctype="multipart/form-data">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <input name="order" type="text" placeholder="ترتيب" class="input">
                    <input name="sub_order" type="text" placeholder="ترتيب فرعي" class="input">
                    <input name="name_ar" type="text" placeholder="الاسم (بالعربية)" class="input" required>
                    <input name="name_en" type="text" placeholder="الاسم (بالإنجليزية)" class="input">
                    <input name="sub_name_ar" type="text" placeholder="الاسم الفرعي (بالعربية)" class="input">
                    <input name="sub_name_en" type="text" placeholder="الاسم الفرعي (بالإنجليزية)" class="input">
                    <select name="status" class="select">
                        <?php foreach ($statuses as $status): ?>
                            <option value="<?= htmlspecialchars($status) ?>"><?= htmlspecialchars($status) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="location" class="select">
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?= htmlspecialchars($loc) ?>" <?= $selected_location === $loc ? 'selected' : '' ?>><?= htmlspecialchars($loc) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="lg:col-span-2">
                        <div class="drop-zone" id="addDropZone">اسحب الصورة هنا أو انقر لاختيار ملف</div>
                        <input id="addCatalogImage" name="catalog_image" type="file" accept="image/*" class="hidden">
                        <div id="addImagePreview" class="hidden mt-4">
                            <img src="" alt="معاينة الصورة" class="image-preview">
                            <button type="button" onclick="clearAddImage()" class="mt-2 btn btn-danger">إزالة الصورة</button>
                        </div>
                    </div>
                    <input name="link" type="url" placeholder="الرابط" class="input">
                    <input name="file_id" type="text" placeholder="معرف الملف" class="input">
                    <textarea name="description_ar" placeholder="نص الوصف (بالعربية)" class="textarea col-span-2"></textarea>
                    <textarea name="description_en" placeholder="نص الوصف (بالإنجليزية)" class="textarea col-span-2"></textarea>
                </div>
                <button type="submit" name="add_catalog" class="mt-6 btn btn-primary">إضافة الكتالوج</button>
            </form>
        </div>

        <!-- Pagination -->
        <div class="flex flex-col sm:flex-row justify-between mb-6 items-center gap-4">
            <a href="?location=<?= urlencode($selected_location) ?>&page=<?= $page - 1 ?>&page_size=<?= $page_size ?>" class="btn btn-primary <?= $previous_page_url ? '' : 'opacity-50 pointer-events-none' ?>">الصفحة السابقة</a>
            <div class="flex items-center gap-4">
                <form method="GET" class="inline-flex items-center">
                    <input type="hidden" name="location" value="<?= htmlspecialchars($selected_location) ?>">
                    <label for="page_size" class="text-gray-600 font-medium ml-2">عدد الكتالوجات في الصفحة:</label>
                    <select name="page_size" onchange="this.form.submit()" class="select">
                        <option value="10" <?= $page_size == 10 ? 'selected' : '' ?>>10</option>
                        <option value="20" <?= $page_size == 20 ? 'selected' : '' ?>>20</option>
                        <option value="50" <?= $page_size == 50 ? 'selected' : '' ?>>50</option>
                        <option value="100" <?= $page_size == 100 ? 'selected' : '' ?>>100</option>
                    </select>
                </form>
                <span class="text-gray-600 font-medium">الصفحة <?= $page ?> من <?= $total_pages ?> (إجمالي الكتالوجات: <?= $total_count ?>)</span>
            </div>
            <a href="?location=<?= urlencode($selected_location) ?>&page=<?= $page + 1 ?>&page_size=<?= $page_size ?>" class="btn btn-primary <?= $next_page_url ? '' : 'opacity-50 pointer-events-none' ?>">الصفحة التالية</a>
        </div>

        <!-- Delete Modal -->
        <div id="deleteModal" class="modal hidden">
            <div class="modal-content">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">تأكيد الحذف</h3>
                <p class="mb-6 text-gray-600">هل أنت متأكد من حذف هذا الكتالوج؟</p>
                <form id="deleteForm" method="POST">
                    <input type="hidden" name="catalog_id" id="deleteCatalogId">
                    <div class="flex justify-end gap-3">
                        <button type="button" onclick="closeDeleteModal()" class="btn btn-secondary">إلغاء</button>
                        <button type="submit" name="delete_catalog" class="btn btn-danger">تأكيد</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Update Modal -->
        <div id="updateModal" class="modal hidden">
            <div class="modal-content max-w-2xl">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">تحديث الكتالوج</h3>
                <form id="updateForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="catalog_id" id="updateCatalogId">
                    <input type="hidden" name="current_image" id="currentImage">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <input name="order" id="updateOrder" type="text" placeholder="ترتيب" class="input">
                        <input name="sub_order" id="updateSubOrder" type="text" placeholder="ترتيب فرعي" class="input">
                        <input name="name_ar" id="updateNameAr" type="text" placeholder="الاسم (بالعربية)" class="input" required>
                        <input name="name_en" id="updateNameEn" type="text" placeholder="الاسم (بالإنجليزية)" class="input">
                        <input name="sub_name_ar" id="updateSubNameAr" type="text" placeholder="الاسم الفرعي (بالعربية)" class="input">
                        <input name="sub_name_en" id="updateSubNameEn" type="text" placeholder="الاسم الفرعي (بالإنجليزية)" class="input">
                        <select name="status" id="updateStatus" class="select">
                            <?php foreach ($statuses as $status): ?>
                                <option value="<?= htmlspecialchars($status) ?>"><?= htmlspecialchars($status) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="location" id="updateLocation" class="select">
                            <?php foreach ($locations as $loc): ?>
                                <option value="<?= htmlspecialchars($loc) ?>"><?= htmlspecialchars($loc) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="lg:col-span-2">
                            <div class="drop-zone" id="updateDropZone">اسحب الصورة هنا أو انقر لاختيار ملف</div>
                            <input id="updateCatalogImage" name="catalog_image" type="file" accept="image/*" class="hidden">
                            <div id="updateImagePreview" class="hidden mt-4">
                                <img src="" alt="معاينة الصورة" class="image-preview">
                                <button type="button" onclick="clearUpdateImage()" class="mt-2 btn btn-danger">إزالة الصورة</button>
                            </div>
                        </div>
                        <input name="link" id="updateLink" type="url" placeholder="الرابط" class="input">
                        <input name="file_id" id="updateFileId" type="text" placeholder="معرف الملف" class="input">
                        <textarea name="description_ar" id="updateDescriptionAr" placeholder="نص الوصف (بالعربية)" class="textarea col-span-2"></textarea>
                        <textarea name="description_en" id="updateDescriptionEn" placeholder="نص الوصف (بالإنجليزية)" class="textarea col-span-2"></textarea>
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" onclick="closeUpdateModal()" class="btn btn-secondary">إلغاء</button>
                        <button type="submit" name="update_catalog" class="btn btn-primary">تحديث</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Catalogs table -->
        <div class="card p-6">
            <h2 class="text-xl font-semibold text-gray-700 mb-6"><?= htmlspecialchars($selected_location) ?></h2>
            <div class="overflow-x-auto">
                <table>
                    <thead>
                        <tr>
                            <th>ترتيب</th>
                            <th>الاسم (عربي)</th>
                            <th>الاسم (إنجليزي)</th>
                            <th>الموقع</th>
                            <th>الحالة</th>
                            <th>الصورة</th>
                            <th>الرابط</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($catalogs)): ?>
                            <tr><td colspan="8" class="text-center text-gray-600 py-4">لا توجد بيانات متاحة لموقع "<?= htmlspecialchars($selected_location) ?>"</td></tr>
                        <?php else: ?>
                            <?php foreach ($catalogs as $catalog): ?>
                                <tr>
                                    <td><?= htmlspecialchars($catalog['field_6759'] ?? 'غير متوفر') ?></td>
                                    <td><?= htmlspecialchars($catalog['field_6754'] ?? 'غير متوفر') ?></td>
                                    <td><?= htmlspecialchars($catalog['field_6762'] ?? 'غير متوفر') ?></td>
                                    <td><?= htmlspecialchars($catalog['field_6756'] ?? 'غير متوفر') ?></td>
                                    <td><?= htmlspecialchars($catalog['field_7072'] ?? 'غير متوفر') ?></td>
                                    <td>
                                        <?php if (!empty($catalog['field_6755'])): ?>
                                            <img src="<?= htmlspecialchars($catalog['field_6755']) ?>" alt="<?= htmlspecialchars($catalog['field_6754'] ?? 'كتالوج') ?>" class="w-12 h-12 object-cover rounded-lg">
                                        <?php else: ?>
                                            غير متوفر
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($catalog['field_6757'])): ?>
                                            <a href="<?= htmlspecialchars($catalog['field_6757']) ?>" target="_blank" class="text-blue-600 hover:underline">عرض الرابط</a>
                                        <?php else: ?>
                                            غير متوفر
                                        <?php endif; ?>
                                    </td>
                                    <td class="flex gap-2">
                                        <button type="button" onclick="openUpdateModal(<?= $catalog['id'] ?>, '<?= htmlspecialchars($catalog['field_6759'] ?? '') ?>', '<?= htmlspecialchars($catalog['field_6760'] ?? '') ?>', '<?= htmlspecialchars($catalog['field_6754'] ?? '') ?>', '<?= htmlspecialchars($catalog['field_6762'] ?? '') ?>', '<?= htmlspecialchars($catalog['field_6761'] ?? '') ?>', '<?= htmlspecialchars($catalog['field_7075'] ?? '') ?>', '<?= htmlspecialchars($catalog['field_7072'] ?? '') ?>', '<?= htmlspecialchars($catalog['field_6755'] ?? '') ?>', '<?= htmlspecialchars($catalog['field_6757'] ?? '') ?>', '<?= htmlspecialchars($catalog['field_6758'] ?? '') ?>', '<?= htmlspecialchars($catalog['field_7076'] ?? '') ?>', '<?= htmlspecialchars($catalog['field_7077'] ?? '') ?>', '<?= htmlspecialchars($catalog['field_6756'] ?? '') ?>')" class="btn btn-primary">تحرير</button>
                                        <button type="button" onclick="openDeleteModal(<?= $catalog['id'] ?>)" class="btn btn-danger">حذف</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Toast Notification -->
        <div id="toast" class="toast"></div>
    </div>

    <script>
        // Show toast notification
        function showToast(message, type) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = `toast show ${type}`;
            setTimeout(() => {
                toast.className = 'toast';
            }, 3000);
        }

        // Open delete modal
        function openDeleteModal(catalogId) {
            document.getElementById('deleteCatalogId').value = catalogId;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        // Close delete modal
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
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
            if (catalogImage) {
                preview.querySelector('img').src = catalogImage;
                preview.classList.remove('hidden');
            } else {
                preview.classList.add('hidden');
            }
            document.getElementById('updateModal').classList.remove('hidden');
        }

        // Close update modal
        function closeUpdateModal() {
            document.getElementById('updateModal').classList.add('hidden');
            document.getElementById('updateForm').reset();
            document.getElementById('updateImagePreview').classList.add('hidden');
            document.getElementById('updateCatalogImage').value = '';
        }

        // Clear add image
        function clearAddImage() {
            document.getElementById('addCatalogImage').value = '';
            document.getElementById('addImagePreview').classList.add('hidden');
        }

        // Clear update image
        function clearUpdateImage() {
            document.getElementById('updateCatalogImage').value = '';
            document.getElementById('updateImagePreview').classList.add('hidden');
        }

        // Image preview and drag-and-drop for add form
        const addDropZone = document.getElementById('addDropZone');
        const addFileInput = document.getElementById('addCatalogImage');
        addDropZone.addEventListener('click', () => addFileInput.click());
        addDropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            addDropZone.classList.add('dragover');
        });
        addDropZone.addEventListener('dragleave', () => {
            addDropZone.classList.remove('dragover');
        });
        addDropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            addDropZone.classList.remove('dragover');
            const file = e.dataTransfer.files[0];
            if (file && file.type.startsWith('image/')) {
                addFileInput.files = e.dataTransfer.files;
                const reader = new FileReader();
                reader.onload = (e) => {
                    const preview = document.getElementById('addImagePreview');
                    preview.querySelector('img').src = e.target.result;
                    preview.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            } else {
                showToast('يرجى اختيار صورة صالحة', 'error');
            }
        });
        addFileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            const preview = document.getElementById('addImagePreview');
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    preview.querySelector('img').src = e.target.result;
                    preview.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            } else {
                preview.classList.add('hidden');
                showToast('يرجى اختيار صورة صالحة', 'error');
            }
        });

        // Image preview and drag-and-drop for update form
        const updateDropZone = document.getElementById('updateDropZone');
        const updateFileInput = document.getElementById('updateCatalogImage');
        updateDropZone.addEventListener('click', () => updateFileInput.click());
        updateDropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            updateDropZone.classList.add('dragover');
        });
        updateDropZone.addEventListener('dragleave', () => {
            updateDropZone.classList.remove('dragover');
        });
        updateDropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            updateDropZone.classList.remove('dragover');
            const file = e.dataTransfer.files[0];
            if (file && file.type.startsWith('image/')) {
                updateFileInput.files = e.dataTransfer.files;
                const reader = new FileReader();
                reader.onload = (e) => {
                    const preview = document.getElementById('updateImagePreview');
                    preview.querySelector('img').src = e.target.result;
                    preview.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            } else {
                showToast('يرجى اختيار صورة صالحة', 'error');
            }
        });
        updateFileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            const preview = document.getElementById('updateImagePreview');
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    preview.querySelector('img').src = e.target.result;
                    preview.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            } else {
                preview.classList.add('hidden');
                showToast('يرجى اختيار صورة صالحة', 'error');
            }
        });

        // Show toast for PHP messages
        <?php if ($message): ?>
            showToast('<?= htmlspecialchars($message) ?>', '<?= $message_type ?>');
        <?php endif; ?>
    </script>
</body>
</html>