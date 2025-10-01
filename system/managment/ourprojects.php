<?php
// Configuration (reusing constants from the original code)
const API_TOKEN = 'h5qAt85gtiJDAzpH51WrXPywhmnhrPWy';
const TABLE_ID = 716; // Updated for مشاريعي table
const BASE_URL = 'https://base.alfagolden.com/api/database/rows/table/';
const UPLOAD_DIR = 'uploads/';
const UPLOAD_URL = 'https://alfagolden.com/system/m/up.php';

// Initialize upload directory (reusing from original code)
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

// External image upload function (reusing from original code)
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
            CURLOPT_SSL_VERIFYPEER => false // Enable in production with proper SSL certificates
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
        return uploadImageDirect($file); // Fallback to direct upload
    }
}

// Direct image upload function (reusing from original code)
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
$projects = [];
$total_count = 0;
$next_page_url = null;
$previous_page_url = null;

// Handle form submission for adding a project
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_project'])) {
    $order = $_POST['order'] ?? '';
    $title_ar = $_POST['title_ar'] ?? '';
    $title_en = $_POST['title_en'] ?? '';
    $status = $_POST['status'] ?? '';
    $description_ar = $_POST['description_ar'] ?? '';
    $description_en = $_POST['description_en'] ?? '';
    $project_images = '';

    // Handle image upload
    if (isset($_FILES['project_images']) && $_FILES['project_images']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadImageExternal($_FILES['project_images']);
        if ($uploadResult['success']) {
            $project_images = $uploadResult['url'];
        } else {
            $message = $uploadResult['message'];
            $message_type = 'error';
        }
    }

    if (!$message && $title_ar) { // Ensure title_ar is not empty
        $data = [
            'field_7084' => $order, // ترتيب
            'field_7080' => $project_images, // صور
            'field_7086' => $title_ar, // العنوان
            'field_7088' => $status, // الحالة
            'field_7090' => $title_en, // العنوان-en
            'field_7091' => $description_ar, // نص
            'field_7092' => $description_en // نص-en
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
        error_log("📤 إضافة مشروع: HTTP $http_code, البيانات: " . json_encode($data));
        if ($curl_error) error_log("❌ خطأ cURL: $curl_error");
        if ($http_code === 200) {
            $message = 'تم إضافة المشروع بنجاح!';
            $message_type = 'success';
        } else {
            $message = 'فشل إضافة المشروع. تحقق من البيانات أو الاتصال.';
            $message_type = 'error';
            error_log("❌ فشل إضافة المشروع: HTTP $http_code, الاستجابة: $response");
        }
    } else if (!$title_ar) {
        $message = 'عنوان المشروع (بالعربية) مطلوب.';
        $message_type = 'error';
    }
}

// Handle project update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_project'])) {
    $project_id = (int)$_POST['project_id'];
    $order = $_POST['order'] ?? '';
    $title_ar = $_POST['title_ar'] ?? '';
    $title_en = $_POST['title_en'] ?? '';
    $status = $_POST['status'] ?? '';
    $description_ar = $_POST['description_ar'] ?? '';
    $description_en = $_POST['description_en'] ?? '';
    $project_images = $_POST['current_images'] ?? '';

    // Handle image upload
    if (isset($_FILES['project_images']) && $_FILES['project_images']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadImageExternal($_FILES['project_images']);
        if ($uploadResult['success']) {
            $project_images = $uploadResult['url'];
        } else {
            $message = $uploadResult['message'];
            $message_type = 'error';
        }
    }

    if (!$message && $title_ar) {
        $data = [
            'field_7084' => $order,
            'field_7080' => $project_images,
            'field_7086' => $title_ar,
            'field_7088' => $status,
            'field_7090' => $title_en,
            'field_7091' => $description_ar,
            'field_7092' => $description_en
        ];
        $ch = curl_init(BASE_URL . TABLE_ID . '/' . $project_id . '/?user_field_names=true');
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
        error_log("📤 تحديث مشروع: HTTP $http_code, البيانات: " . json_encode($data));
        if ($curl_error) error_log("❌ خطأ cURL: $curl_error");
        if ($http_code === 200) {
            $message = 'تم تحديث المشروع بنجاح!';
            $message_type = 'success';
        } else {
            $message = 'فشل تحديث المشروع. تحقق من البيانات أو الاتصال.';
            $message_type = 'error';
            error_log("❌ فشل تحديث المشروع: HTTP $http_code, الاستجابة: $response");
        }
    } else if (!$title_ar) {
        $message = 'عنوان المشروع (بالعربية) مطلوب.';
        $message_type = 'error';
    }
}

// Handle project deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_project'])) {
    $project_id = (int)$_POST['project_id'];
    $ch = curl_init(BASE_URL . TABLE_ID . '/' . $project_id . '/');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Token ' . API_TOKEN
    ]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    error_log("📤 حذف مشروع: ID $project_id, HTTP $http_code");
    if ($curl_error) error_log("❌ خطأ cURL: $curl_error");
    if ($http_code === 204) {
        $message = 'تم حذف المشروع بنجاح!';
        $message_type = 'success';
    } else {
        $message = 'فشل حذف المشروع. تحقق من الاتصال.';
        $message_type = 'error';
        error_log("❌ فشل حذف المشروع: HTTP $http_code, الاستجابة: $response");
    }
}

// Fetch projects from Baserow
$ch = curl_init(BASE_URL . TABLE_ID . '/?user_field_names=false&size=' . $page_size . '&page=' . $page);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Token ' . API_TOKEN,
    'Content-Type: application/json'
]);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
if ($http_code === 200) {
    $data = json_decode($response, true);
    $projects = $data['results'] ?? [];
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
$statuses = ['قيد التنفيذ', 'مكتمل', 'معلق'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المشاريع - Baserow</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Cairo', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #e6f3fa 0%, #f0f4f8 100%);
        }
       .modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.75);
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
        .card {
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        .btn {
            transition: background-color 0.3s, transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-1px);
        }
        .image-preview {
            max-width: 150px;
            max-height: 150px;
            object-fit: contain;
            margin-top: 10px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
        }
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 16px;
            border-radius: 8px;
            color: white;
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }
        .toast.show {
            opacity: 1;
        }
        .toast.success {
            background-color: #2b6cb0;
        }
        .toast.error {
            background-color: #c53030;
        }
        .drop-zone {
            border: 2px dashed #ccc;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.3s;
        }
        .drop-zone.dragover {
            border-color: #2b6cb0;
            background-color: #e6f3fa;
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container mx-auto p-6 max-w-5xl">
        <h1 class="text-3xl font-bold text-teal-800 text-center mb-8">إدارة المشاريع</h1>
        <!-- Messages -->
        <?php if ($message): ?>
            <div class="p-4 mb-6 rounded-lg shadow-md <?= $message_type === 'success' ? 'bg-teal-100 text-teal-800' : 'bg-red-100 text-red-800' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        <!-- Add project form -->
        <div class="bg-white p-6 rounded-xl shadow-lg mb-8 card">
            <h2 class="text-xl font-semibold text-teal-700 mb-4">إضافة مشروع جديد</h2>
            <form id="addProjectForm" method="POST" enctype="multipart/form-data">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <input name="order" type="text" placeholder="ترتيب المشروع" class="border border-gray-300 p-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500">
                    <input name="title_ar" type="text" placeholder="عنوان المشروع (بالعربية)" class="border border-gray-300 p-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500" required>
                    <input name="title_en" type="text" placeholder="عنوان المشروع (بالإنجليزية)" class="border border-gray-300 p-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500">
                    <select name="status" class="border border-gray-300 p-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500">
                        <?php foreach ($statuses as $status): ?>
                            <option value="<?= htmlspecialchars($status) ?>"><?= htmlspecialchars($status) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div>
                        <div class="drop-zone" id="addDropZone">اسحب الصورة هنا أو انقر لاختيار ملف</div>
                        <input id="addProjectImages" name="project_images" type="file" accept="image/*" class="hidden">
                        <div id="addImagePreview" class="hidden mt-2">
                            <img src="" alt="معاينة الصورة" class="image-preview">
                            <button type="button" onclick="clearAddImage()" class="mt-2 bg-red-500 text-white px-3 py-1 rounded-lg hover:bg-red-600 btn">إزالة الصورة</button>
                        </div>
                    </div>
                    <textarea name="description_ar" placeholder="نص الوصف (بالعربية)" class="border border-gray-300 p-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500"></textarea>
                    <textarea name="description_en" placeholder="نص الوصف (بالإنجليزية)" class="border border-gray-300 p-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500"></textarea>
                </div>
                <button type="submit" name="add_project" class="mt-4 bg-teal-600 text-white px-6 py-3 rounded-lg hover:bg-teal-700 btn">إضافة المشروع</button>
            </form>
        </div>
        <!-- Pagination -->
        <div class="flex justify-between mb-6 items-center">
            <a href="?page=<?= $page - 1 ?>&page_size=<?= $page_size ?>" class="bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 btn <?= $previous_page_url ? '' : 'opacity-50 pointer-events-none' ?>">الصفحة السابقة</a>
            <div class="flex items-center gap-4">
                <form method="GET" class="inline">
                    <label for="page_size" class="text-teal-800 font-medium ml-2">عدد المشاريع في الصفحة:</label>
                    <select name="page_size" onchange="this.form.submit()" class="border border-gray-300 p-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500">
                        <option value="10" <?= $page_size == 10 ? 'selected' : '' ?>>10</option>
                        <option value="20" <?= $page_size == 20 ? 'selected' : '' ?>>20</option>
                        <option value="50" <?= $page_size == 50 ? 'selected' : '' ?>>50</option>
                        <option value="100" <?= $page_size == 100 ? 'selected' : '' ?>>100</option>
                    </select>
                </form>
                <span class="text-teal-800 font-medium">الصفحة <?= $page ?> من <?= $total_pages ?> (إجمالي المشاريع: <?= $total_count ?>)</span>
            </div>
            <a href="?page=<?= $page + 1 ?>&page_size=<?= $page_size ?>" class="bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 btn <?= $next_page_url ? '' : 'opacity-50 pointer-events-none' ?>">الصفحة التالية</a>
        </div>
        <!-- Delete Modal -->
        <div id="deleteModal" class="fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center hidden modal">
            <div class="bg-white p-6 rounded-xl shadow-2xl max-w-md w-full">
                <h3 class="text-lg font-semibold text-teal-800 mb-4">تأكيد الحذف</h3>
                <p class="mb-6 text-gray-600">هل أنت متأكد من حذف هذا المشروع؟</p>
                <form id="deleteForm" method="POST">
                    <input type="hidden" name="project_id" id="deleteProjectId">
                    <div class="flex justify-end gap-3">
                        <button type="button" onclick="closeDeleteModal()" class="bg-gray-300 text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-400 btn">إلغاء</button>
                        <button type="submit" name="delete_project" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 btn">تأكيد</button>
                    </div>
                </form>
            </div>
        </div>
        <!-- Update Modal -->
        <div id="updateModal" class="fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center hidden modal">
            <div class="bg-white p-6 rounded-xl shadow-2xl max-w-md w-full">
                <h3 class="text-lg font-semibold text-teal-800 mb-4">تحديث المشروع</h3>
                <form id="updateForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="project_id" id="updateProjectId">
                    <input type="hidden" name="current_images" id="currentImages">
                    <div class="grid grid-cols-1 gap-4">
                        <input name="order" id="updateOrder" type="text" placeholder="ترتيب المشروع" class="border border-gray-300 p-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500">
                        <input name="title_ar" id="updateTitleAr" type="text" placeholder="عنوان المشروع (بالعربية)" class="border border-gray-300 p-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500" required>
                        <input name="title_en" id="updateTitleEn" type="text" placeholder="عنوان المشروع (بالإنجليزية)" class="border border-gray-300 p-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500">
                        <select name="status" id="updateStatus" class="border border-gray-300 p-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500">
                            <?php foreach ($statuses as $status): ?>
                                <option value="<?= htmlspecialchars($status) ?>"><?= htmlspecialchars($status) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div>
                            <div class="drop-zone" id="updateDropZone">اسحب الصورة هنا أو انقر لاختيار ملف</div>
                            <input id="updateProjectImages" name="project_images" type="file" accept="image/*" class="hidden">
                            <div id="updateImagePreview" class="hidden mt-2">
                                <img src="" alt="معاينة الصورة" class="image-preview">
                                <button type="button" onclick="clearUpdateImage()" class="mt-2 bg-red-500 text-white px-3 py-1 rounded-lg hover:bg-red-600 btn">إزالة الصورة</button>
                            </div>
                        </div>
                        <textarea name="description_ar" id="updateDescriptionAr" placeholder="نص الوصف (بالعربية)" class="border border-gray-300 p-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500"></textarea>
                        <textarea name="description_en" id="updateDescriptionEn" placeholder="نص الوصف (بالإنجليزية)" class="border border-gray-300 p-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500"></textarea>
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" onclick="closeUpdateModal()" class="bg-gray-300 text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-400 btn">إلغاء</button>
                        <button type="submit" name="update_project" class="bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700 btn">تحديث</button>
                    </div>
                </form>
            </div>
        </div>
        <!-- Projects table -->
        <div class="bg-white p-6 rounded-xl shadow-lg card">
            <h2 class="text-xl font-semibold text-teal-700 mb-4">قائمة المشاريع</h2>
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-teal-100">
                        <th class="border-b border-gray-200 p-3 text-teal-800 font-semibold">ترتيب</th>
                        <th class="border-b border-gray-200 p-3 text-teal-800 font-semibold">العنوان (عربي)</th>
                        <th class="border-b border-gray-200 p-3 text-teal-800 font-semibold">العنوان (إنجليزي)</th>
                        <th class="border-b border-gray-200 p-3 text-teal-800 font-semibold">الحالة</th>
                        <th class="border-b border-gray-200 p-3 text-teal-800 font-semibold">الصور</th>
                        <th class="border-b border-gray-200 p-3 text-teal-800 font-semibold">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($projects)): ?>
                        <tr><td colspan="6" class="border-b border-gray-200 p-3 text-center text-gray-600">لا توجد بيانات متاحة</td></tr>
                    <?php else: ?>
                        <?php foreach ($projects as $project): ?>
                            <tr class="hover:bg-teal-50">
                                <td class="border-b border-gray-200 p-3"><?= htmlspecialchars($project['field_7084'] ?? 'غير متوفر') ?></td>
                                <td class="border-b border-gray-200 p-3"><?= htmlspecialchars($project['field_7086'] ?? 'غير متوفر') ?></td>
                                <td class="border-b border-gray-200 p-3"><?= htmlspecialchars($project['field_7090'] ?? 'غير متوفر') ?></td>
                                <td class="border-b border-gray-200 p-3"><?= htmlspecialchars($project['field_7088'] ?? 'غير متوفر') ?></td>
                                <td class="border-b border-gray-200 p-3">
                                    <?php if (!empty($project['field_7080'])): ?>
                                        <img src="<?= htmlspecialchars($project['field_7080']) ?>" alt="<?= htmlspecialchars($project['field_7086'] ?? 'مشروع') ?>" class="w-16 h-16 object-cover rounded-lg">
                                    <?php else: ?>
                                        غير متوفر
                                    <?php endif; ?>
                                </td>
                                <td class="border-b border-gray-200 p-3 flex gap-2">
                                    <button type="button" onclick="openUpdateModal(<?= $project['id'] ?>, '<?= htmlspecialchars($project['field_7084'] ?? '') ?>', '<?= htmlspecialchars($project['field_7086'] ?? '') ?>', '<?= htmlspecialchars($project['field_7090'] ?? '') ?>', '<?= htmlspecialchars($project['field_7088'] ?? '') ?>', '<?= htmlspecialchars($project['field_7080'] ?? '') ?>', '<?= htmlspecialchars($project['field_7091'] ?? '') ?>', '<?= htmlspecialchars($project['field_7092'] ?? '') ?>')" class="bg-indigo-600 text-white px-3 py-1 rounded-lg hover:bg-indigo-700 btn">تحرير</button>
                                    <button type="button" onclick="openDeleteModal(<?= $project['id'] ?>)" class="bg-red-600 text-white px-3 py-1 rounded-lg hover:bg-red-700 btn">حذف</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
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
        function openDeleteModal(projectId) {
            document.getElementById('deleteProjectId').value = projectId;
            document.getElementById('deleteModal').classList.remove('hidden');
        }
        // Close delete modal
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
            document.getElementById('deleteProjectId').value = '';
        }
        // Open update modal
        function openUpdateModal(projectId, order, titleAr, titleEn, status, projectImages, descriptionAr, descriptionEn) {
            document.getElementById('updateProjectId').value = projectId;
            document.getElementById('updateOrder').value = order;
            document.getElementById('updateTitleAr').value = titleAr;
            document.getElementById('updateTitleEn').value = titleEn;
            document.getElementById('updateStatus').value = status;
            document.getElementById('currentImages').value = projectImages;
            document.getElementById('updateDescriptionAr').value = descriptionAr;
            document.getElementById('updateDescriptionEn').value = descriptionEn;
            const preview = document.getElementById('updateImagePreview');
            if (projectImages) {
                preview.querySelector('img').src = projectImages;
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
            document.getElementById('updateProjectImages').value = '';
        }
        // Clear add image
        function clearAddImage() {
            document.getElementById('addProjectImages').value = '';
            document.getElementById('addImagePreview').classList.add('hidden');
        }
        // Clear update image
        function clearUpdateImage() {
            document.getElementById('updateProjectImages').value = '';
            document.getElementById('updateImagePreview').classList.add('hidden');
        }
        // Image preview and drag-and-drop for add form
        const addDropZone = document.getElementById('addDropZone');
        const addFileInput = document.getElementById('addProjectImages');
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
        const updateFileInput = document.getElementById('updateProjectImages');
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