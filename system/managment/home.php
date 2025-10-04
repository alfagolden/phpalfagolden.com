
<?php
// Configuration
const API_TOKEN = 'h5qAt85gtiJDAzpH51WrXPywhmnhrPWy';
const TABLE_ID = 698; // جدول الكتلوجات
const CATEGORY_TABLE_ID = 713; // جدول أقسام المنتجات
const PRODUCT_TABLE_ID = 696; // جدول المنتجات
const BASE_URL = 'https://base.alfagolden.com/api/database/rows/table/';
const UPLOAD_DIR = 'Uploads/';
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
$categories = [];
$total_count = 0;
$next_page_url = null;
$previous_page_url = null;
$locations = ['كتلوجات', 'سلايدر العملاء', 'سلايدر الهيدر', 'المنتجات'];
// =============== Handle Order Change for Catalogs ===============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_order'])) {
    $catalog_id = (int)$_POST['catalog_id'];
    $direction = $_POST['direction'] ?? 'down';
    $ch = curl_init(BASE_URL . TABLE_ID . '/?filter__field_6756__contains=' . urlencode($selected_location) . '&user_field_names=false');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Token ' . API_TOKEN]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    if ($curl_error || $http_code !== 200) {
        error_log("❌ فشل جلب البيانات لتغيير الترتيب: HTTP $http_code, خطأ cURL: $curl_error");
        $message = 'فشل جلب البيانات لتغيير الترتيب.';
        $message_type = 'error';
        goto skip_order;
    }
    $all = json_decode($response, true)['results'] ?? [];
    usort($all, function($a, $b) {
        $oa = is_numeric($a['field_6759']) ? (float)$a['field_6759'] : 999999;
        $ob = is_numeric($b['field_6759']) ? (float)$b['field_6759'] : 999999;
        return $oa <=> $ob;
    });
    $currentIndex = null;
    foreach ($all as $index => $item) {
        if ($item['id'] == $catalog_id) {
            $currentIndex = $index;
            break;
        }
    }
    if ($currentIndex === null) {
        error_log("❌ العنصر غير موجود: ID $catalog_id");
        $message = 'العنصر غير موجود.';
        $message_type = 'error';
        goto skip_order;
    }
    $targetIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;
    if ($targetIndex < 0 || $targetIndex >= count($all)) {
        error_log("❌ لا يمكن التحرك أكثر: ID $catalog_id, الاتجاه: $direction");
        $message = 'لا يمكن التحرك أكثر من ذلك.';
        $message_type = 'error';
        goto skip_order;
    }
    $itemToMove = array_splice($all, $currentIndex, 1)[0];
    array_splice($all, $targetIndex, 0, [$itemToMove]);
    foreach ($all as $index => &$item) {
        $newOrder = $index + 1;
        $item['field_6759'] = (string)$newOrder;
        $patchData = ['field_6759' => $item['field_6759']];
        $ch = curl_init(BASE_URL . TABLE_ID . '/' . $item['id'] . '/');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($patchData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Token ' . API_TOKEN,
            'Content-Type: application/json'
        ]);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        if ($curl_error || $http_code !== 200) {
            error_log("❌ فشل تحديث الترتيب للعنصر " . $item['id'] . ": HTTP $http_code, خطأ cURL: $curl_error");
            $message = 'فشل تحديث الترتيب.';
            $message_type = 'error';
            goto skip_order;
        }
    }
    error_log("✅ تم إعادة ترتيب العناصر بنجاح: ID $catalog_id إلى الموقع $targetIndex");
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
        error_log("📤 إضافة كتالوج: HTTP $http_code, البيانات: " . json_encode($data));
        if ($curl_error) error_log("❌ خطأ cURL: $curl_error");
        if ($http_code === 200) {
            $message = 'تم إضافة الكتالوج بنجاح!';
            $message_type = 'success';
        } else {
            $message = 'فشل إضافة الكتالوج.';
            $message_type = 'error';
            error_log("❌ فشل إضافة الكتالوج: HTTP $http_code, الاستجابة: $response");
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
        error_log("📤 تحديث كتالوج: HTTP $http_code, البيانات: " . json_encode($data));
        if ($curl_error) error_log("❌ خطأ cURL: $curl_error");
        if ($http_code === 200) {
            $message = 'تم تحديث الكتالوج بنجاح!';
            $message_type = 'success';
        } else {
            $message = 'فشل تحديث الكتالوج.';
            $message_type = 'error';
            error_log("❌ فشل تحديث الكتالوج: HTTP $http_code, الاستجابة: $response");
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
    error_log("📤 حذف كتالوج: ID $catalog_id, HTTP $http_code");
    if ($curl_error) error_log("❌ خطأ cURL: $curl_error");
    if ($http_code === 204) {
        $message = 'تم حذف الكتالوج بنجاح!';
        $message_type = 'success';
    } else {
        $message = 'فشل حذف الكتالوج.';
        $message_type = 'error';
        error_log("❌ فشل حذف الكتالوج: HTTP $http_code, الاستجابة: $response");
    }
}
// =============== Handle Add Category ===============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = $_POST['name'] ?? '';
    $image = '';
    if (!$name) {
        $message = 'اسم القسم مطلوب.';
        $message_type = 'error';
    }
    if (!$message && isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadImageExternal($_FILES['image']);
        if ($uploadResult['success']) {
            $image = $uploadResult['url'];
        } else {
            $message = $uploadResult['message'];
            $message_type = 'error';
        }
    }
    if (!$message) {
        $data = [
            'field_7001' => $name,
            'field_7002' => $image,
            'field_7003' => '',
            'field_7004' => '',
            'field_7005' => '',
            'field_7006' => '',
            'field_7127' => []
        ];
        $ch = curl_init(BASE_URL . CATEGORY_TABLE_ID . '/?user_field_names=false');
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
        error_log("📤 إضافة قسم: HTTP $http_code, البيانات: " . json_encode($data));
        if ($curl_error) error_log("❌ خطأ cURL: $curl_error");
        if ($http_code === 200) {
            $message = 'تم إضافة القسم بنجاح!';
            $message_type = 'success';
        } else {
            $message = 'فشل إضافة القسم.';
            $message_type = 'error';
            error_log("❌ فشل إضافة القسم: HTTP $http_code, الاستجابة: $response");
        }
    }
}
// =============== Handle Update Category ===============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_category'])) {
    $category_id = (int)$_POST['category_id'];
    $name = $_POST['name'] ?? '';
    $image = $_POST['current_image'] ?? '';
    if (!$name) {
        $message = 'اسم القسم مطلوب.';
        $message_type = 'error';
    }
    if (!$message && isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadImageExternal($_FILES['image']);
        if ($uploadResult['success']) {
            $image = $uploadResult['url'];
        } else {
            $message = $uploadResult['message'];
            $message_type = 'error';
        }
    }
    if (!$message) {
        $data = [
            'field_7001' => $name,
            'field_7002' => $image,
            'field_7003' => '',
            'field_7004' => '',
            'field_7005' => '',
            'field_7006' => ''
        ];
        $ch = curl_init(BASE_URL . CATEGORY_TABLE_ID . '/' . $category_id . '/?user_field_names=true');
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
        error_log("📤 تحديث قسم: HTTP $http_code, البيانات: " . json_encode($data));
        if ($curl_error) error_log("❌ خطأ cURL: $curl_error");
        if ($http_code === 200) {
            $message = 'تم تحديث القسم بنجاح!';
            $message_type = 'success';
        } else {
            $message = 'فشل تحديث القسم.';
            $message_type = 'error';
            error_log("❌ فشل تحديث القسم: HTTP $http_code, الاستجابة: $response");
        }
    }
}
// =============== Handle Delete Category ===============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category'])) {
    $category_id = (int)$_POST['category_id'];
    $ch = curl_init(BASE_URL . CATEGORY_TABLE_ID . '/' . $category_id . '/');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Token ' . API_TOKEN
    ]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    error_log("📤 حذف قسم: ID $category_id, HTTP $http_code");
    if ($curl_error) error_log("❌ خطأ cURL: $curl_error");
    if ($http_code === 204) {
        $message = 'تم حذف القسم بنجاح!';
        $message_type = 'success';
    } else {
        $message = 'فشل حذف القسم.';
        $message_type = 'error';
        error_log("❌ فشل حذف القسم: HTTP $http_code, الاستجابة: $response");
    }
}
// =============== Handle Add Product ===============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $category_id = (int)$_POST['category_id'];
    $name = $_POST['name'] ?? '';
    $image = '';
    if (!$name) {
        $message = 'اسم المنتج مطلوب.';
        $message_type = 'error';
    }
    if (!$message && isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadImageExternal($_FILES['image']);
        if ($uploadResult['success']) {
            $image = $uploadResult['url'];
        } else {
            $message = $uploadResult['message'];
            $message_type = 'error';
        }
    }
    if (!$message) {
        $data = [
            'field_6747' => $name,
            'field_6748' => $image,
            'field_6750' => '',
            'field_7126' => [$category_id]
        ];
        $ch = curl_init(BASE_URL . PRODUCT_TABLE_ID . '/?user_field_names=false');
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
            $message = 'فشل إضافة المنتج.';
            $message_type = 'error';
            error_log("❌ فشل إضافة المنتج: HTTP $http_code, الاستجابة: $response");
        }
    }
}
// =============== Handle Update Product ===============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $product_id = (int)$_POST['product_id'];
    $category_id = (int)$_POST['category_id'];
    $name = $_POST['name'] ?? '';
    $image = $_POST['current_image'] ?? '';
    if (!$name) {
        $message = 'اسم المنتج مطلوب.';
        $message_type = 'error';
    }
    if (!$message && isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadImageExternal($_FILES['image']);
        if ($uploadResult['success']) {
            $image = $uploadResult['url'];
        } else {
            $message = $uploadResult['message'];
            $message_type = 'error';
        }
    }
    if (!$message) {
        $data = [
            'field_6747' => $name,
            'field_6748' => $image,
            'field_6750' => '',
            'field_7126' => [$category_id]
        ];
        $ch = curl_init(BASE_URL . PRODUCT_TABLE_ID . '/' . $product_id . '/?user_field_names=true');
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
            $message = 'فشل تحديث المنتج.';
            $message_type = 'error';
            error_log("❌ فشل تحديث المنتج: HTTP $http_code, الاستجابة: $response");
        }
    }
}
// =============== Handle Delete Product ===============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    $product_id = (int)$_POST['product_id'];
    $ch = curl_init(BASE_URL . PRODUCT_TABLE_ID . '/' . $product_id . '/');
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
        $message = 'فشل حذف المنتج.';
        $message_type = 'error';
        error_log("❌ فشل حذف المنتج: HTTP $http_code, الاستجابة: $response");
    }
}
// =============== Fetch Categories for المنتجات Tab ===============
if ($selected_location === 'المنتجات') {
    $ch = curl_init(BASE_URL . CATEGORY_TABLE_ID . '/?user_field_names=false&size=100');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Token ' . API_TOKEN,
        'Content-Type: application/json'
    ]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    if ($http_code === 200) {
        $data = json_decode($response, true);
        $categories = $data['results'] ?? [];
        $total_count = $data['count'] ?? 0;
        $next_page_url = $data['next'] ?? null;
        $previous_page_url = $data['previous'] ?? null;
    } else {
        $message = 'فشل جلب بيانات الأقسام.';
        $message_type = 'error';
        error_log("❌ فشل جلب الأقسام: HTTP $http_code, الاستجابة: $response");
        if ($curl_error) error_log("❌ خطأ cURL: $curl_error");
    }
}
// =============== Fetch Catalogs for Other Tabs ===============
if ($selected_location !== 'المنتجات') {
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
}
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
        .products-list {
            margin-top: 16px;
        }
        .products-list ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .products-list li {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            font-size: 14px;
            color: var(--dark-gray);
            padding: 10px;
            border-bottom: 1px solid var(--border-color);
        }
        .products-list img {
            width: 50px;
            height: 50px;
            object-fit: contain;
            border: 1px solid var(--border-color);
            border-radius: 4px;
        }
        .products-list .actions {
            margin-left: auto;
            display: flex;
            gap: 8px;
        }
        .spinner {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1200;
            display: none;
        }
        .spinner::after {
            content: '';
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--gold);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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
        <!-- Spinner -->
        <div class="spinner" id="spinner"></div>
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
                <?php if ($selected_location === 'المنتجات'): ?>
                    <?php if (empty($categories)): ?>
                        <div class="empty-state">
                            <i class="fas fa-folder-plus"></i>
                            <h3>لا توجد أقسام</h3>
                            <p>ابدأ بإضافة أول قسم في "المنتجات"</p>
                            <button onclick="openAddCategoryModal()" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>إضافة قسم جديد
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="gallery-grid">
                            <?php foreach ($categories as $category): ?>
                                <div class="gallery-item" onclick="openProductsModal(<?= $category['id'] ?>, '<?= htmlspecialchars($category['field_7001'] ?? '---') ?>')">
                                    <?php if (!empty($category['field_7002'])): ?>
                                        <img src="<?= htmlspecialchars($category['field_7002']) ?>" alt="<?= htmlspecialchars($category['field_7001'] ?? 'قسم') ?>" class="gallery-item-image">
                                    <?php else: ?>
                                        <div class="gallery-placeholder"><i class="fas fa-folder"></i></div>
                                    <?php endif; ?>
                                    <div class="gallery-item-content">
                                        <h3 class="gallery-item-title"><?= htmlspecialchars($category['field_7001'] ?? '---') ?></h3>
                                        <div class="gallery-item-actions">
                                            <button onclick="openUpdateCategoryModal(<?= $category['id'] ?>, '<?= htmlspecialchars($category['field_7001'] ?? '') ?>', '<?= htmlspecialchars($category['field_7002'] ?? '') ?>'); event.stopPropagation();" class="btn btn-primary btn-sm rounded-circle">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" style="display:inline;" onsubmit="openConfirmModal(event, 'هل أنت متأكد من حذف القسم؟', this)">
                                                <input type="hidden" name="category_id" value="<?= $category['id'] ?>">
                                                <input type="hidden" name="delete_category" value="1">
                                                <button type="submit" class="btn btn-secondary btn-sm rounded-circle" onclick="event.stopPropagation();">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
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
                                                    <form method="POST" style="display:inline;" onsubmit="openConfirmModal(event, 'تحريك لأعلى؟', this)">
                                                        <input type="hidden" name="catalog_id" value="<?= $catalog['id'] ?>">
                                                        <input type="hidden" name="direction" value="up">
                                                        <input type="hidden" name="change_order" value="1">
                                                        <button type="submit" class="btn btn-secondary btn-sm rounded-circle">
                                                            <i class="fas fa-arrow-up"></i>
                                                        </button>
                                                    </form>
                                                    <form method="POST" style="display:inline;" onsubmit="openConfirmModal(event, 'تحريك لأسفل؟', this)">
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
                                            <form method="POST" style="display:inline;" onsubmit="openConfirmModal(event, 'هل أنت متأكد من الحذف؟', this)">
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
                <?php endif; ?>
            </div>
        </div>
        <!-- Add Catalog Modal -->
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
        <!-- Update Catalog Modal -->
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
        <!-- Add Category Modal -->
        <div class="modal" id="addCategoryModal">
            <div class="modal-dialog">
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title">إضافة قسم جديد</h5>
                        <button type="button" class="btn-close" onclick="closeAddCategoryModal()">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-label">اسم القسم *</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">الصورة</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeAddCategoryModal()">إلغاء</button>
                        <button type="submit" class="btn btn-primary" name="add_category">حفظ</button>
                    </div>
                </form>
            </div>
        </div>
        <!-- Update Category Modal -->
        <div class="modal" id="updateCategoryModal">
            <div class="modal-dialog">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="category_id" id="updateCategoryId">
                    <input type="hidden" name="current_image" id="updateCategoryImage">
                    <div class="modal-header">
                        <h5 class="modal-title">تعديل القسم</h5>
                        <button type="button" class="btn-close" onclick="closeUpdateCategoryModal()">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-label">اسم القسم *</label>
                            <input type="text" name="name" id="updateCategoryName" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">الصورة</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                            <small class="text-muted">اترك فارغًا للإبقاء على الصورة الحالية</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeUpdateCategoryModal()">إلغاء</button>
                        <button type="submit" class="btn btn-primary" name="update_category">حفظ</button>
                    </div>
                </form>
            </div>
        </div>
        <!-- Products Modal -->
        <div class="modal" id="productsModal">
            <div class="modal-dialog">
                <div class="modal-header">
                    <h5 class="modal-title" id="productsModalTitle">المنتجات</h5>
                    <button type="button" class="btn-close" onclick="closeProductsModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <button class="btn btn-primary mb-3" onclick="openAddProductModal()">إضافة منتج جديد</button>
                    <div id="productsList"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeProductsModal()">إغلاق</button>
                </div>
            </div>
        </div>
        <!-- Add Product Modal -->
        <div class="modal" id="addProductModal">
            <div class="modal-dialog">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="category_id" id="addProductCategoryId">
                    <div class="modal-header">
                        <h5 class="modal-title">إضافة منتج جديد</h5>
                        <button type="button" class="btn-close" onclick="closeAddProductModal()">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-label">اسم المنتج *</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">الصورة</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeAddProductModal()">إلغاء</button>
                        <button type="submit" class="btn btn-primary" name="add_product">حفظ</button>
                    </div>
                </form>
            </div>
        </div>
        <!-- Update Product Modal -->
        <div class="modal" id="updateProductModal">
            <div class="modal-dialog">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="product_id" id="updateProductId">
                    <input type="hidden" name="category_id" id="updateProductCategoryId">
                    <input type="hidden" name="current_image" id="updateProductImage">
                    <div class="modal-header">
                        <h5 class="modal-title">تعديل المنتج</h5>
                        <button type="button" class="btn-close" onclick="closeUpdateProductModal()">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-label">اسم المنتج *</label>
                            <input type="text" name="name" id="updateProductName" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">الصورة</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                            <small class="text-muted">اترك فارغًا للإبقاء على الصورة الحالية</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeUpdateProductModal()">إلغاء</button>
                        <button type="submit" class="btn btn-primary" name="update_product">حفظ</button>
                    </div>
                </form>
            </div>
        </div>
        <!-- Confirmation Modal -->
        <div class="modal" id="confirmModal">
            <div class="modal-dialog">
                <div class="modal-header">
                    <h5 class="modal-title">تأكيد العملية</h5>
                    <button type="button" class="btn-close" onclick="closeConfirmModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <p id="confirmMessage"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeConfirmModal()">إلغاء</button>
                    <button type="button" class="btn btn-primary" onclick="confirmAction()">تأكيد</button>
                </div>
            </div>
        </div>
        <!-- Toast Container -->
        <div class="toast-container"></div>
    </div>
    <script>
        // Show spinner
        function showSpinner() {
            document.getElementById('spinner').style.display = 'flex';
        }
        // Hide spinner
        function hideSpinner() {
            document.getElementById('spinner').style.display = 'none';
        }
        // Attach spinner to form submissions
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function (event) {
                if (!form.classList.contains('confirm-modal-form')) {
                    showSpinner();
                }
            });
        });
        // Variable to store the form to submit
        let formToSubmit = null;
        // Show confirmation modal
        function openConfirmModal(event, message, form) {
            event.preventDefault();
            formToSubmit = form;
            document.getElementById('confirmMessage').textContent = message;
            document.getElementById('confirmModal').classList.add('show');
            document.body.style.overflow = 'hidden';
            form.classList.add('confirm-modal-form');
        }
        // Close confirmation modal
        function closeConfirmModal() {
            document.getElementById('confirmModal').classList.remove('show');
            document.body.style.overflow = 'auto';
            if (formToSubmit) {
                formToSubmit.classList.remove('confirm-modal-form');
            }
            formToSubmit = null;
        }
        // Confirm action and submit form
        function confirmAction() {
            if (formToSubmit) {
                showSpinner();
                formToSubmit.submit();
            }
            closeConfirmModal();
        }
        // Show toast
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
            }, 3000);
        }
        // Show toast based on PHP message
        <?php if ($message): ?>
            showToast('<?= htmlspecialchars($message) ?>', '<?= $message_type ?>');
        <?php endif; ?>
        // Open add modal
                // Open add modal
        function openAddModal(location) {
            document.getElementById('addLocationInput').value = location;
            document.getElementById('catalogFields').classList.toggle('d-none', location !== 'كتلوجات');
            document.getElementById('headerSliderFields').classList.toggle('d-none', location !== 'سلايدر الهيدر');
            document.getElementById('addCatalogModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        // Close add modal
        function closeAddModal() {
            document.getElementById('addCatalogModal').classList.remove('show');
            document.body.style.overflow = 'auto';
            document.querySelector('#addCatalogModal form').reset();
        }
        // Open update modal
        function openUpdateModal(id, order, nameAr, nameEn, image, link, location) {
            document.getElementById('updateCatalogId').value = id;
            document.getElementById('updateCurrentImage').value = image;
            document.getElementById('updateLocationInput').value = location;
            document.getElementById('updateNameAr').value = nameAr;
            document.getElementById('updateNameEn').value = nameEn;
            document.getElementById('updateLink').value = link;
            document.getElementById('updateOrder').value = order;
            document.getElementById('updateCatalogFields').classList.toggle('d-none', location !== 'كتلوجات');
            document.getElementById('updateHeaderSliderFields').classList.toggle('d-none', location !== 'سلايدر الهيدر');
            document.getElementById('updateCatalogModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        // Close update modal
        function closeUpdateModal() {
            document.getElementById('updateCatalogModal').classList.remove('show');
            document.body.style.overflow = 'auto';
            document.querySelector('#updateCatalogModal form').reset();
        }
        // Open add category modal
        function openAddCategoryModal() {
            document.getElementById('addCategoryModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        // Close add category modal
        function closeAddCategoryModal() {
            document.getElementById('addCategoryModal').classList.remove('show');
            document.body.style.overflow = 'auto';
            document.querySelector('#addCategoryModal form').reset();
        }
        // Open update category modal
        function openUpdateCategoryModal(id, name, image) {
            document.getElementById('updateCategoryId').value = id;
            document.getElementById('updateCategoryName').value = name;
            document.getElementById('updateCategoryImage').value = image;
            document.getElementById('updateCategoryModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        // Close update category modal
        function closeUpdateCategoryModal() {
            document.getElementById('updateCategoryModal').classList.remove('show');
            document.body.style.overflow = 'auto';
            document.querySelector('#updateCategoryModal form').reset();
        }
        // Open products modal
        function openProductsModal(categoryId, categoryName) {
            document.getElementById('productsModalTitle').textContent = `المنتجات - ${categoryName}`;
            document.getElementById('productsModal').classList.add('show');
            document.body.style.overflow = 'hidden';
            loadProducts(categoryId);
        }
        // Close products modal
        function closeProductsModal() {
            document.getElementById('productsModal').classList.remove('show');
            document.body.style.overflow = 'auto';
            document.getElementById('productsList').innerHTML = '';
        }
        // Open add product modal
        function openAddProductModal(categoryId) {
            document.getElementById('addProductCategoryId').value = categoryId || document.getElementById('updateProductCategoryId').value;
            document.getElementById('addProductModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        // Close add product modal
        function closeAddProductModal() {
            document.getElementById('addProductModal').classList.remove('show');
            document.body.style.overflow = 'auto';
            document.querySelector('#addProductModal form').reset();
        }
        // Open update product modal
        function openUpdateProductModal(productId, name, image, categoryId) {
            document.getElementById('updateProductId').value = productId;
            document.getElementById('updateProductName').value = name;
            document.getElementById('updateProductImage').value = image;
            document.getElementById('updateProductCategoryId').value = categoryId;
            document.getElementById('updateProductModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        // Close update product modal
        function closeUpdateProductModal() {
            document.getElementById('updateProductModal').classList.remove('show');
            document.body.style.overflow = 'auto';
            document.querySelector('#updateProductModal form').reset();
        }
        // Load products via AJAX
        function loadProducts(categoryId) {
            showSpinner();
            fetch(`?action=get_products&category_id=${categoryId}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) throw new Error('فشل جلب المنتجات');
                return response.json();
            })
            .then(data => {
                const productsList = document.getElementById('productsList');
                if (data.products && data.products.length > 0) {
                    let html = '<ul>';
                    data.products.forEach(product => {
                        html += `
                            <li>
                                ${product.image ? `<img src="${product.image}" alt="${product.name}">` : '<i class="fas fa-image"></i>'}
                                <span>${product.name}</span>
                                <div class="actions">
                                    <button class="btn btn-primary btn-sm rounded-circle" onclick="openUpdateProductModal(${product.id}, '${product.name}', '${product.image}', ${categoryId})">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" style="display:inline;" onsubmit="openConfirmModal(event, 'هل أنت متأكد من حذف المنتج؟', this)">
                                        <input type="hidden" name="product_id" value="${product.id}">
                                        <input type="hidden" name="delete_product" value="1">
                                        <button type="submit" class="btn btn-secondary btn-sm rounded-circle">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </li>
                        `;
                    });
                    html += '</ul>';
                    productsList.innerHTML = html;
                } else {
                    productsList.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-box-open"></i>
                            <h3>لا توجد منتجات</h3>
                            <p>ابدأ بإضافة أول منتج في هذا القسم</p>
                        </div>
                    `;
                }
                hideSpinner();
            })
            .catch(error => {
                showToast('خطأ في جلب المنتجات: ' + error.message, 'error');
                hideSpinner();
            });
        }
    </script>
</body>
</html>
<?php


// Handle AJAX request for products
if (isset($_GET['action']) && $_GET['action'] === 'get_products' && isset($_GET['category_id'])) {
    header('Content-Type: application/json');
    $category_id = (int)$_GET['category_id'];
    $ch = curl_init(BASE_URL . PRODUCT_TABLE_ID . '/?filter__field_7126__link_row_has=' . $category_id . '&user_field_names=false');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Token ' . API_TOKEN,
        'Content-Type: application/json'
    ]);
    echo(API_TOKEN);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    if ($http_code === 200) {
        $data = json_decode($response, true);
        $products = array_map(function($product) {
            return [
                'id' => $product['id'],
                'name' => $product['field_6747'] ?? '---',
                'image' => $product['field_6748'] ?? ''
            ];
        }, $data['results'] ?? []);
        echo json_encode(['products' => $products]);
    } else {
        error_log("❌ فشل جلب المنتجات: HTTP $http_code, الاستجابة: $response");
        if ($curl_error) error_log("❌ خطأ cURL: $curl_error");
        echo json_encode(['error' => 'فشل جلب المنتجات']);
    }
    exit;
}
?>