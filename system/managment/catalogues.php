<?php
// Configuration
const API_TOKEN = 'h5qAt85gtiJDAzpH51WrXPywhmnhrPWy';
const TABLE_ID = 698;
const BASE_URL = 'https://base.alfagolden.com/api/database/rows/table/';
const UPLOAD_DIR = 'uploads/';
const UPLOAD_URL = 'https://alfagolden.com/system/m/up.php';

// ... (وظائف ensureUploadDirectory, uploadImageExternal, uploadImageDirect تبقى كما هي)

// Initialize upload directory
try {
    ensureUploadDirectory();
} catch (Exception $e) {
    error_log("❌ خطأ في إعداد مجلد الرفع: " . $e->getMessage());
}

// =============================
// 1. جلب القيم الفريدة لـ location
// =============================
$locations = [];
$ch = curl_init(BASE_URL . TABLE_ID . '/?user_field_names=false&page_size=200');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Token ' . API_TOKEN,
    'Content-Type: application/json'
]);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200) {
    $data = json_decode($response, true);
    $all_rows = $data['results'] ?? [];
    $location_values = array_unique(array_column($all_rows, 'field_6756'));
    // تنظيف القيم الفارغة أو null
    $locations = array_filter(array_map('trim', $location_values), function($v) {
        return !empty($v);
    });
    // ضمان وجود "كتالوجات" كقيمة افتراضية
    if (!in_array('كتالوجات', $locations)) {
        $locations[] = 'كتالوجات';
    }
} else {
    $locations = ['كتالوجات']; // fallback
}

// =============================
// 2. تحديد location النشط من الرابط
// =============================
$active_location = isset($_GET['location']) ? trim($_GET['location']) : 'كتالوجات';
if (!in_array($active_location, $locations)) {
    $active_location = 'كتالوجات';
}

// =============================
// باقي المتغيرات
// =============================
$message = '';
$message_type = '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$page_size = isset($_GET['page_size']) ? (int)$_GET['page_size'] : 10;
$catalogs = [];
$total_count = 0;
$next_page_url = null;
$previous_page_url = null;

// =============================
// معالجة الإضافة / التعديل / الحذف (بنفس الـ location النشط)
// =============================

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
    $location = $active_location; // ← ديناميكي الآن!
    $catalog_image = '';

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
            'field_6756' => $location, // ← ديناميكي
            'field_6757' => $link,
            'field_6758' => $file_id,
            'field_6761' => $sub_name_ar,
            'field_6762' => $name_en,
            'field_7072' => $status,
            'field_7075' => $sub_name_en,
            'field_7076' => $description_ar,
            'field_7077' => $description_en
        ];
        // ... (بقية كود الإضافة كما هو، نفس الـ curl)
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
        curl_close($ch);

        if ($http_code === 200) {
            $message = 'تم الإضافة بنجاح!';
            $message_type = 'success';
        } else {
            $message = 'فشل الإضافة.';
            $message_type = 'error';
        }
    } else if (!$name_ar) {
        $message = 'اسم الكتالوج (بالعربية) مطلوب.';
        $message_type = 'error';
    }
}

// Handle update (نفس الفكرة)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_catalog'])) {
    // ... نفس الكود، مع تغيير:
    $location = $active_location; // ← ديناميكي
    // ... واستمرار باقي الكود كما هو
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
    $catalog_image = $_POST['current_image'] ?? '';

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
        curl_close($ch);

        if ($http_code === 200) {
            $message = 'تم التحديث بنجاح!';
            $message_type = 'success';
        } else {
            $message = 'فشل التحديث.';
            $message_type = 'error';
        }
    }
}

// Handle deletion (لا يعتمد على location، لكنه يحذف من نفس الجدول)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_catalog'])) {
    $catalog_id = (int)$_POST['catalog_id'];
    $ch = curl_init(BASE_URL . TABLE_ID . '/' . $catalog_id . '/');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Token ' . API_TOKEN]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 204) {
        $message = 'تم الحذف بنجاح!';
        $message_type = 'success';
    } else {
        $message = 'فشل الحذف.';
        $message_type = 'error';
    }
}

// =============================
// جلب السجلات حسب location النشط
// =============================
$filter_param = 'filter__field_6756__equal=' . urlencode($active_location);
$ch = curl_init(BASE_URL . TABLE_ID . '/?' . $filter_param . '&user_field_names=false&size=' . $page_size . '&page=' . $page);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Token ' . API_TOKEN,
    'Content-Type: application/json'
]);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200) {
    $data = json_decode($response, true);
    $catalogs = $data['results'] ?? [];
    $total_count = $data['count'] ?? 0;
    $next_page_url = $data['next'] ?? null;
    $previous_page_url = $data['previous'] ?? null;
} else {
    $message = 'فشل جلب البيانات من Baserow.';
    $message_type = 'error';
}
$total_pages = ceil($total_count / $page_size);
$statuses = ['نشط', 'غير نشط'];
?>