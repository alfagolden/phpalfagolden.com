<?php
// Configuration
const API_TOKEN = 'h5qAt85gtiJDAzpH51WrXPywhmnhrPWy';
const TABLE_ID = 696;
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

// External image upload function (to up.php)
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

// Direct image upload function (fallback)
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
$products = [];
$total_count = 0;
$next_page_url = null;
$previous_page_url = null;

// Handle form submission for adding a product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $product_code = $_POST['product_code'] ?? '';
    $product_type = $_POST['product_type'] ?? 'الكبــائن';
    $product_image = '';

    // Handle image upload
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadImageExternal($_FILES['product_image']);
        if ($uploadResult['success']) {
            $product_image = $uploadResult['url'];
        } else {
            $message = $uploadResult['message'];
            $message_type = 'error';
        }
    }

    if (!$message && $product_code) { // Ensure product_code is not empty
        $data = [
            'الاسم' => $product_code,
            'الصورة' => $product_image,
            'القسم' => [$product_type]
        ];
        $ch = curl_init(BASE_URL . TABLE_ID . '/?user_field_names=true');
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

        error_log("📤 إضافة منتج: HTTP $http_code, البيانات: " . json_encode($data));
        if ($curl_error) error_log("❌ خطأ cURL: $curl_error");

        if ($http_code === 200) {
            $message = 'تم إضافة المنتج بنجاح!';
            $message_type = 'success';
        } else {
            $message = 'فشل إضافة المنتج. تحقق من البيانات أو الاتصال.';
            $message_type = 'error';
            error_log("❌ فشل إضافة المنتج: HTTP $http_code, الاستجابة: $response");
        }
    } else if (!$product_code) {
        $message = 'كود المنتج مطلوب.';
        $message_type = 'error';
    }
}

// Handle product update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $product_id = (int)$_POST['product_id'];
    $product_code = $_POST['product_code'] ?? '';
    $product_type = $_POST['product_type'] ?? 'الكبــائن';
    $product_image = $_POST['current_image'] ?? '';

    // Handle image upload
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadImageExternal($_FILES['product_image']);
        if ($uploadResult['success']) {
            $product_image = $uploadResult['url'];
        } else {
            $message = $uploadResult['message'];
            $message_type = 'error';
        }
    }

    if (!$message && $product_code) {
        $data = [
            'الاسم' => $product_code,
            'الصورة' => $product_image,
            'القسم' => [$product_type]
        ];
        $ch = curl_init(BASE_URL . TABLE_ID . '/' . $product_id . '/?user_field_names=true');
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

        error_log("📤 تحديث منتج: HTTP $http_code, البيانات: " . json_encode($data));
        if ($curl_error) error_log("❌ خطأ cURL: $curl_error");

        if ($http_code === 200) {
            $message = 'تم تحديث المنتج بنجاح!';
            $message_type = 'success';
        } else {
            $message = 'فشل تحديث المنتج. تحقق من البيانات أو الاتصال.';
            $message_type = 'error';
            error_log("❌ فشل تحديث المنتج: HTTP $http_code, الاستجابة: $response");
        }
    } else if (!$product_code) {
        $message = 'كود المنتج مطلوب.';
        $message_type = 'error';
    }
}

// Handle product deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    $product_id = (int)$_POST['product_id'];
    $ch = curl_init(BASE_URL . TABLE_ID . '/' . $product_id . '/');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Token ' . API_TOKEN
    ]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    error_log("📤 حذف منتج: ID $product_id, HTTP $http_code");
    if ($curl_error) error_log("❌ خطأ cURL: $curl_error");

    if ($http_code === 204) {
        $message = 'تم حذف المنتج بنجاح!';
        $message_type = 'success';
    } else {
        $message = 'فشل حذف المنتج. تحقق من الاتصال.';
        $message_type = 'error';
        error_log("❌ فشل حذف المنتج: HTTP $http_code, الاستجابة: $response");
    }
}

// Fetch products from Baserow
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
    $products = $data['results'] ?? [];
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

// Categories list for the form
$categories = [
    'الكبــائن',
    'لوحات الطلبات',
    'كبائن فوجي ثلاثية الأبعاد',
    'المكائن',
    'رخام الكبائن'
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الكبائن والمنتجات - Baserow</title>
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
        <h1 class="text-3xl font-bold text-teal-800 text-center mb-8">إدارة الكبائن والمنتجات</h1>
        <!-- Messages -->
        <?php if ($message): ?>
            <div class="p-4 mb-6 rounded-lg shadow-md <?= $message_type === 'success' ? 'bg-teal-100 text-teal-800' : 'bg-red-100 text-red-800' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        <!-- Add product form -->
        <div class="bg-white p-6 rounded-xl shadow-lg mb-8 card">
            <h2 class="text-xl font-semibold text-teal-700 mb-4">إضافة منتج جديد</h2>
            <form id="addProductForm" method="POST" enctype="multipart/form-data">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <input name="product_code" type="text" placeholder="كود المنتج (مثل c/001)" class="border border-gray-300 p-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500" required>
                    <div>
                        <div class="drop-zone" id="addDropZone">اسحب الصورة هنا أو انقر لاختيار ملف</div>
                        <input id="addProductImage" name="product_image" type="file" accept="image/*" class="hidden">
                        <div id="addImagePreview" class="hidden mt-2">
                            <img src="" alt="معاينة الصورة" class="image-preview">
                            <button type="button" onclick="clearAddImage()" class="mt-2 bg-red-500 text-white px-3 py-1 rounded-lg hover:bg-red-600 btn">إزالة الصورة</button>
                        </div>
                    </div>
                    <select name="product_type" class="border border-gray-300 p-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500">
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= htmlspecialchars($category) ?>"><?= htmlspecialchars($category) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="add_product" class="mt-4 bg-teal-600 text-white px-6 py-3 rounded-lg hover:bg-teal-700 btn">إضافة المنتج</button>
            </form>
        </div>
        <!-- Pagination -->
        <div class="flex justify-between mb-6 items-center">
            <a href="?page=<?= $page - 1 ?>&page_size=<?= $page_size ?>" class="bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 btn <?= $previous_page_url ? '' : 'opacity-50 pointer-events-none' ?>">الصفحة السابقة</a>
            <div class="flex items-center gap-4">
                <form method="GET" class="inline">
                    <label for="page_size" class="text-teal-800 font-medium ml-2">عدد المنتجات في الصفحة:</label>
                    <select name="page_size" onchange="this.form.submit()" class="border border-gray-300 p-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500">
                        <option value="10" <?= $page_size == 10 ? 'selected' : '' ?>>10</option>
                        <option value="20" <?= $page_size == 20 ? 'selected' : '' ?>>20</option>
                        <option value="50" <?= $page_size == 50 ? 'selected' : '' ?>>50</option>
                        <option value="100" <?= $page_size == 100 ? 'selected' : '' ?>>100</option>
                    </select>
                </form>
                <span class="text-teal-800 font-medium">الصفحة <?= $page ?> من <?= $total_pages ?> (إجمالي المنتجات: <?= $total_count ?>)</span>
            </div>
            <a href="?page=<?= $page + 1 ?>&page_size=<?= $page_size ?>" class="bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 btn <?= $next_page_url ? '' : 'opacity-50 pointer-events-none' ?>">الصفحة التالية</a>
        </div>
        <!-- Delete Modal -->
        <div id="deleteModal" class="fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center hidden modal">
            <div class="bg-white p-6 rounded-xl shadow-2xl max-w-md w-full">
                <h3 class="text-lg font-semibold text-teal-800 mb-4">تأكيد الحذف</h3>
                <p class="mb-6 text-gray-600">هل أنت متأكد من حذف هذا المنتج؟</p>
                <form id="deleteForm" method="POST">
                    <input type="hidden" name="product_id" id="deleteProductId">
                    <div class="flex justify-end gap-3">
                        <button type="button" onclick="closeDeleteModal()" class="bg-gray-300 text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-400 btn">إلغاء</button>
                        <button type="submit" name="delete_product" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 btn">تأكيد</button>
                    </div>
                </form>
            </div>
        </div>
        <!-- Update Modal -->
        <div id="updateModal" class="fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center hidden modal">
            <div class="bg-white p-6 rounded-xl shadow-2xl max-w-md w-full">
                <h3 class="text-lg font-semibold text-teal-800 mb-4">تحديث المنتج</h3>
                <form id="updateForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="product_id" id="updateProductId">
                    <input type="hidden" name="current_image" id="currentImage">
                    <div class="grid grid-cols-1 gap-4">
                        <input name="product_code" id="updateProductCode" type="text" placeholder="كود المنتج" class="border border-gray-300 p-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500" required>
                        <div>
                            <div class="drop-zone" id="updateDropZone">اسحب الصورة هنا أو انقر لاختيار ملف</div>
                            <input id="updateProductImage" name="product_image" type="file" accept="image/*" class="hidden">
                            <div id="updateImagePreview" class="hidden mt-2">
                                <img src="" alt="معاينة الصورة" class="image-preview">
                                <button type="button" onclick="clearUpdateImage()" class="mt-2 bg-red-500 text-white px-3 py-1 rounded-lg hover:bg-red-600 btn">إزالة الصورة</button>
                            </div>
                        </div>
                        <select name="product_type" id="updateProductType" class="border border-gray-300 p-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500">
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= htmlspecialchars($category) ?>"><?= htmlspecialchars($category) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" onclick="closeUpdateModal()" class="bg-gray-300 text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-400 btn">إلغاء</button>
                        <button type="submit" name="update_product" class="bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700 btn">تحديث</button>
                    </div>
                </form>
            </div>
        </div>
        <!-- Products table -->
        <div class="bg-white p-6 rounded-xl shadow-lg card">
            <h2 class="text-xl font-semibold text-teal-700 mb-4">قائمة المنتجات</h2>
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-teal-100">
                        <th class="border-b border-gray-200 p-3 text-teal-800 font-semibold">كود المنتج</th>
                        <th class="border-b border-gray-200 p-3 text-teal-800 font-semibold">الصورة</th>
                        <th class="border-b border-gray-200 p-3 text-teal-800 font-semibold">نوع المنتج</th>
                        <th class="border-b border-gray-200 p-3 text-teal-800 font-semibold">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr><td colspan="4" class="border-b border-gray-200 p-3 text-center text-gray-600">لا توجد بيانات متاحة</td></tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <tr class="hover:bg-teal-50">
                                <td class="border-b border-gray-200 p-3"><?= htmlspecialchars($product['field_6747'] ?? 'غير متوفر') ?></td>
                                <td class="border-b border-gray-200 p-3">
                                    <?php if (!empty($product['field_6748'])): ?>
                                        <img src="<?= htmlspecialchars($product['field_6748']) ?>" alt="<?= htmlspecialchars($product['field_6747'] ?? 'منتج') ?>" class="w-16 h-16 object-cover rounded-lg">
                                    <?php else: ?>
                                        غير متوفر
                                    <?php endif; ?>
                                </td>
                                <td class="border-b border-gray-200 p-3"><?= htmlspecialchars($product['field_7126'][0]['value'] ?? 'غير متوفر') ?></td>
                                <td class="border-b border-gray-200 p-3 flex gap-2">
                                    <button type="button" onclick="openUpdateModal(<?= $product['id'] ?>, '<?= htmlspecialchars($product['field_6747'] ?? '') ?>', '<?= htmlspecialchars($product['field_6748'] ?? '') ?>', '<?= htmlspecialchars($product['field_7126'][0]['value'] ?? '') ?>')" class="bg-indigo-600 text-white px-3 py-1 rounded-lg hover:bg-indigo-700 btn">تحرير</button>
                                    <button type="button" onclick="openDeleteModal(<?= $product['id'] ?>)" class="bg-red-600 text-white px-3 py-1 rounded-lg hover:bg-red-700 btn">حذف</button>
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
        function openDeleteModal(productId) {
            document.getElementById('deleteProductId').value = productId;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        // Close delete modal
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
            document.getElementById('deleteProductId').value = '';
        }

        // Open update modal
        function openUpdateModal(productId, productCode, productImage, productType) {
            document.getElementById('updateProductId').value = productId;
            document.getElementById('updateProductCode').value = productCode;
            document.getElementById('currentImage').value = productImage;
            document.getElementById('updateProductType').value = productType;
            const preview = document.getElementById('updateImagePreview');
            if (productImage) {
                preview.querySelector('img').src = productImage;
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
            document.getElementById('updateProductImage').value = '';
        }

        // Clear add image
        function clearAddImage() {
            document.getElementById('addProductImage').value = '';
            document.getElementById('addImagePreview').classList.add('hidden');
        }

        // Clear update image
        function clearUpdateImage() {
            document.getElementById('updateProductImage').value = '';
            document.getElementById('updateImagePreview').classList.add('hidden');
        }

        // Image preview and drag-and-drop for add form
        const addDropZone = document.getElementById('addDropZone');
        const addFileInput = document.getElementById('addProductImage');
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
        const updateFileInput = document.getElementById('updateProductImage');
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