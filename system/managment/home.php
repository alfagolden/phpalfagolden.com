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
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Cairo', sans-serif;
        }
        :root {
            --gold-light: <?php echo COLOR_GOLD_LIGHT; ?>;
            --gold: <?php echo COLOR_GOLD; ?>;
            --gold-dark: <?php echo COLOR_GOLD_DARK; ?>;
            --white: <?php echo COLOR_WHITE; ?>;
            --light-bg: <?php echo COLOR_LIGHT_BG; ?>;
            --light-card: <?php echo COLOR_LIGHT_CARD; ?>;
            --text-dark: <?php echo COLOR_TEXT_DARK; ?>;
            --text-gray: <?php echo COLOR_TEXT_GRAY; ?>;
            --transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        body {
            background: linear-gradient(145deg, var(--light-bg), #e8ecef, #ffffff);
            color: var(--text-dark);
            min-height: 100vh;
        }
        .container {
            max-width: 80rem;
            margin: 0 auto;
            padding: 1.5rem;
        }
        .card {
            background: var(--light-card);
            border-radius: 18px;
            padding: 30px;
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.1);
            transition: var(--transition);
            border: 1px solid rgba(<?php echo COLOR_GOLD; ?>, 0.1);
        }
        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 18px 35px rgba(<?php echo COLOR_GOLD; ?>, 0.2);
            border: 1px solid rgba(<?php echo COLOR_GOLD; ?>, 0.4);
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .btn-primary {
            background: var(--gold);
            color: var(--white);
        }
        .btn-primary:hover {
            background: var(--gold-dark);
        }
        .btn-secondary {
            background: var(--white);
            color: var(--text-dark);
            border: 1px solid var(--gold);
        }
        .btn-secondary:hover {
            background: rgba(<?php echo COLOR_GOLD; ?>, 0.1);
        }
        .btn-danger {
            background: #dc2626;
            color: var(--white);
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
            background: var(--white);
            border-radius: 18px;
            padding: 30px;
            max-width: 90%;
            width: 40rem;
            box-shadow: 0 8px 30px rgba(<?php echo COLOR_GOLD; ?>, 0.2);
        }
        .input, .select, .textarea {
            border: 1px solid rgba(<?php echo COLOR_GOLD; ?>, 0.3);
            border-radius: 8px;
            padding: 12px;
            font-size: 15px;
            transition: var(--transition);
            width: 100%;
        }
        .input:focus, .select:focus, .textarea:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(<?php echo COLOR_GOLD; ?>, 0.2);
            outline: none;
        }
        .drop-zone {
            border: 2px dashed rgba(<?php echo COLOR_GOLD; ?>, 0.3);
            padding: 1.5rem;
            text-align: center;
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition);
        }
        .drop-zone.dragover {
            border-color: var(--gold);
            background: rgba(<?php echo COLOR_GOLD; ?>, 0.1);
        }
        .image-preview {
            max-width: 120px;
            max-height: 120px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid rgba(<?php echo COLOR_GOLD; ?>, 0.3);
        }
        .toast {
            position: fixed;
            bottom: 1.5rem;
            right: 1.5rem;
            padding: 12px 24px;
            border-radius: 8px;
            color: var(--white);
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }
        .toast.show {
            opacity: 1;
        }
        .toast.success {
            background: var(--gold);
        }
        .toast.error {
            background: #dc2626;
        }
        .tab {
            padding: 12px 24px;
            font-weight: 600;
            color: var(--text-gray);
            border-bottom: 2px solid transparent;
            transition: var(--transition);
            text-decoration: none;
        }
        .tab.active {
            color: var(--gold);
            border-bottom: 2px solid var(--gold);
        }
        .tab:hover {
            color: var(--gold-dark);
        }
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 8px;
            overflow: hidden;
        }
        th, td {
            padding: 12px;
            text-align: right;
        }
        th {
            background: var(--gold);
            color: var(--white);
            font-weight: 600;
        }
        tr {
            background: var(--white);
        }
        tr:hover {
            background: rgba(<?php echo COLOR_GOLD; ?>, 0.1);
        }
        .pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 16px;
        }
        @media (max-width: 640px) {
            .container {
                padding: 1rem;
            }
            .modal-content {
                width: 95%;
            }
            .btn {
                padding: 8px 16px;
            }
            .input, .select, .textarea {
                font-size: 14px;
            }
            th, td {
                padding: 8px;
                font-size: 14px;
            }
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 style="font-size: 32px; font-weight: 700; background: linear-gradient(to right, var(--gold-light), var(--gold)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; letter-spacing: 1px; text-align: center; margin-bottom: 40px;">إدارة الصفحة الرئيسية</h1>
        <!-- Tabs for Locations -->
        <div style="display: flex; flex-wrap: wrap; gap: 16px; margin-bottom: 24px; border-bottom: 1px solid rgba(<?php echo COLOR_GOLD; ?>, 0.1);">
            <?php foreach ($locations as $loc): ?>
                <a href="?location=<?= urlencode($loc) ?>&page=1&page_size=<?= $page_size ?>" class="tab <?= $selected_location === $loc ? 'active' : '' ?>">
                    <?= htmlspecialchars($loc) ?>
                </a>
            <?php endforeach; ?>
        </div>
        <!-- Messages -->
        <?php if ($message): ?>
            <div class="toast show <?php echo $message_type; ?>" style="width: auto; max-width: 90%;"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <!-- Add catalog form -->
        <div class="card">
            <h2 style="font-size: 22px; font-weight: 600; color: var(--text-dark); margin-bottom: 24px;">إضافة كتالوج جديد</h2>
            <form id="addCatalogForm" method="POST" enctype="multipart/form-data">
                <div class="form-grid">
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
                    <div style="grid-column: span 2;">
                        <div class="drop-zone" id="addDropZone">اسحب الصورة هنا أو انقر لاختيار ملف</div>
                        <input id="addCatalogImage" name="catalog_image" type="file" accept="image/*" class="hidden">
                        <div id="addImagePreview" class="hidden" style="margin-top: 16px;">
                            <img src="" alt="معاينة الصورة" class="image-preview">
                            <button type="button" onclick="clearAddImage()" class="btn btn-danger" style="margin-top: 8px;">إزالة الصورة</button>
                        </div>
                    </div>
                    <input name="link" type="url" placeholder="الرابط" class="input">
                    <input name="file_id" type="text" placeholder="معرف الملف" class="input">
                    <textarea name="description_ar" placeholder="نص الوصف (بالعربية)" class="textarea" style="grid-column: span 2;"></textarea>
                    <textarea name="description_en" placeholder="نص الوصف (بالإنجليزية)" class="textarea" style="grid-column: span 2;"></textarea>
                </div>
                <button type="submit" name="add_catalog" class="btn btn-primary" style="margin-top: 24px;">إضافة الكتالوج</button>
            </form>
        </div>
        <!-- Pagination -->
        <div class="pagination">
            <a href="?location=<?= urlencode($selected_location) ?>&page=<?= $page - 1 ?>&page_size=<?= $page_size ?>" class="btn btn-primary <?= $previous_page_url ? '' : 'opacity-50 pointer-events-none' ?>">الصفحة السابقة</a>
            <div style="display: flex; align-items: center; gap: 16px;">
                <form method="GET" style="display: flex; align-items: center;">
                    <input type="hidden" name="location" value="<?= htmlspecialchars($selected_location) ?>">
                    <label for="page_size" style="color: var(--text-gray); font-weight: 600; margin-left: 8px;">عدد الكتالوجات في الصفحة:</label>
                    <select name="page_size" onchange="this.form.submit()" class="select">
                        <option value="10" <?= $page_size == 10 ? 'selected' : '' ?>>10</option>
                        <option value="20" <?= $page_size == 20 ? 'selected' : '' ?>>20</option>
                        <option value="50" <?= $page_size == 50 ? 'selected' : '' ?>>50</option>
                        <option value="100" <?= $page_size == 100 ? 'selected' : '' ?>>100</option>
                    </select>
                </form>
                <span style="color: var(--text-gray); font-weight: 600;">الصفحة <?= $page ?> من <?= $total_pages ?> (إجمالي الكتالوجات: <?= $total_count ?>)</span>
            </div>
            <a href="?location=<?= urlencode($selected_location) ?>&page=<?= $page + 1 ?>&page_size=<?= $page_size ?>" class="btn btn-primary <?= $next_page_url ? '' : 'opacity-50 pointer-events-none' ?>">الصفحة التالية</a>
        </div>
        <!-- Delete Modal -->
        <div id="deleteModal" class="modal hidden">
            <div class="modal-content">
                <h3 style="font-size: 20px; font-weight: 600; color: var(--text-dark); margin-bottom: 16px;">تأكيد الحذف</h3>
                <p style="margin-bottom: 24px; color: var(--text-gray);">هل أنت متأكد من حذف هذا الكتالوج؟</p>
                <form id="deleteForm" method="POST">
                    <input type="hidden" name="catalog_id" id="deleteCatalogId">
                    <div style="display: flex; justify-content: end; gap: 12px;">
                        <button type="button" onclick="closeDeleteModal()" class="btn btn-secondary">إلغاء</button>
                        <button type="submit" name="delete_catalog" class="btn btn-danger">تأكيد</button>
                    </div>
                </form>
            </div>
        </div>
        <!-- Update Modal -->
        <div id="updateModal" class="modal hidden">
            <div class="modal-content" style="max-width: 48rem;">
                <h3 style="font-size: 20px; font-weight: 600; color: var(--text-dark); margin-bottom: 16px;">تحديث الكتالوج</h3>
                <form id="updateForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="catalog_id" id="updateCatalogId">
                    <input type="hidden" name="current_image" id="currentImage">
                    <div class="form-grid">
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
                        <div style="grid-column: span 2;">
                            <div class="drop-zone" id="updateDropZone">اسحب الصورة هنا أو انقر لاختيار ملف</div>
                            <input id="updateCatalogImage" name="catalog_image" type="file" accept="image/*" class="hidden">
                            <div id="updateImagePreview" class="hidden" style="margin-top: 16px;">
                                <img src="" alt="معاينة الصورة" class="image-preview">
                                <button type="button" onclick="clearUpdateImage()" class="btn btn-danger" style="margin-top: 8px;">إزالة الصورة</button>
                            </div>
                        </div>
                        <input name="link" id="updateLink" type="url" placeholder="الرابط" class="input">
                        <input name="file_id" id="updateFileId" type="text" placeholder="معرف الملف" class="input">
                        <textarea name="description_ar" id="updateDescriptionAr" placeholder="نص الوصف (بالعربية)" class="textarea" style="grid-column: span 2;"></textarea>
                        <textarea name="description_en" id="updateDescriptionEn" placeholder="نص الوصف (بالإنجليزية)" class="textarea" style="grid-column: span 2;"></textarea>
                    </div>
                    <div style="display: flex; justify-content: end; gap: 12px; margin-top: 24px;">
                        <button type="button" onclick="closeUpdateModal()" class="btn btn-secondary">إلغاء</button>
                        <button type="submit" name="update_catalog" class="btn btn-primary">تحديث</button>
                    </div>
                </form>
            </div>
        </div>
        <!-- Catalogs table -->
        <div class="card" style="margin-top: 24px;">
            <h2 style="font-size: 22px; font-weight: 600; color: var(--text-dark); margin-bottom: 24px;"><?php echo htmlspecialchars($selected_location); ?></h2>
            <div style="overflow-x: auto;">
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
                            <tr><td colspan="8" style="text-align: center; color: var(--text-gray); padding: 16px;">لا توجد بيانات متاحة لموقع "<?php echo htmlspecialchars($selected_location); ?>"</td></tr>
                        <?php else: ?>
                            <?php foreach ($catalogs as $catalog): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($catalog['field_6759'] ?? 'غير متوفر'); ?></td>
                                    <td><?php echo htmlspecialchars($catalog['field_6754'] ?? 'غير متوفر'); ?></td>
                                    <td><?php echo htmlspecialchars($catalog['field_6762'] ?? 'غير متوفر'); ?></td>
                                    <td><?php echo htmlspecialchars($catalog['field_6756'] ?? 'غير متوفر'); ?></td>
                                    <td><?php echo htmlspecialchars($catalog['field_7072'] ?? 'غير متوفر'); ?></td>
                                    <td>
                                        <?php if (!empty($catalog['field_6755'])): ?>
                                            <img src="<?php echo htmlspecialchars($catalog['field_6755']); ?>" alt="<?php echo htmlspecialchars($catalog['field_6754'] ?? 'كتالوج'); ?>" style="width: 48px; height: 48px; object-fit: cover; border-radius: 8px;">
                                        <?php else: ?>
                                            غير متوفر
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($catalog['field_6757'])): ?>
                                            <a href="<?php echo htmlspecialchars($catalog['field_6757']); ?>" target="_blank" style="color: var(--gold); text-decoration: none;">عرض الرابط</a>
                                        <?php else: ?>
                                            غير متوفر
                                        <?php endif; ?>
                                    </td>
                                    <td style="display: flex; gap: 8px;">
                                        <button type="button" onclick="openUpdateModal(<?php echo $catalog['id']; ?>, '<?php echo htmlspecialchars($catalog['field_6759'] ?? ''); ?>', '<?php echo htmlspecialchars($catalog['field_6760'] ?? ''); ?>', '<?php echo htmlspecialchars($catalog['field_6754'] ?? ''); ?>', '<?php echo htmlspecialchars($catalog['field_6762'] ?? ''); ?>', '<?php echo htmlspecialchars($catalog['field_6761'] ?? ''); ?>', '<?php echo htmlspecialchars($catalog['field_7075'] ?? ''); ?>', '<?php echo htmlspecialchars($catalog['field_7072'] ?? ''); ?>', '<?php echo htmlspecialchars($catalog['field_6755'] ?? ''); ?>', '<?php echo htmlspecialchars($catalog['field_6757'] ?? ''); ?>', '<?php echo htmlspecialchars($catalog['field_6758'] ?? ''); ?>', '<?php echo htmlspecialchars($catalog['field_7076'] ?? ''); ?>', '<?php echo htmlspecialchars($catalog['field_7077'] ?? ''); ?>', '<?php echo htmlspecialchars($catalog['field_6756'] ?? ''); ?>')" class="btn btn-primary">تحرير</button>
                                        <button type="button" onclick="openDeleteModal(<?php echo $catalog['id']; ?>)" class="btn btn-danger">حذف</button>
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
            showToast('<?php echo htmlspecialchars($message); ?>', '<?php echo $message_type; ?>');
        <?php endif; ?>
    </script>
</body>
</html>