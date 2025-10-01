<?php
/**
 * نظام إدارة المنتجات - ألفا الذهبية (النسخة المختصرة)
 */

// إعدادات Baserow
define('BASEROW_TOKEN', 'h5qAt85gtiJDAzpH51WrXPywhmnhrPWy');
define('BASEROW_URL', 'https://base.alfagolden.com/api');
define('CATEGORIES_TABLE_ID', '713');
define('PRODUCTS_TABLE_ID', '696');
define('UPLOAD_DIR', 'uploads/');

// إنشاء مجلد الرفع
function ensureUploadDirectory() {
    $dir = UPLOAD_DIR;
    if (!is_dir($dir)) {
        $permissions = [0755, 0775, 0777];
        foreach ($permissions as $perm) {
            if (mkdir($dir, $perm, true)) break;
        }
    }
    if (!is_writable($dir)) {
        $permissions = [0755, 0775, 0777];
        foreach ($permissions as $perm) {
            if (chmod($dir, $perm) && is_writable($dir)) break;
        }
    }
    return true;
}

ensureUploadDirectory();

// دالة Baserow
function makeBaserowRequest($endpoint, $method = 'GET', $data = null) {
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => BASEROW_URL . $endpoint,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Token ' . BASEROW_TOKEN, 'Content-Type: application/json'],
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30
    ]);
    
    if ($data && in_array($method, ['POST', 'PATCH'])) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    if ($response === false || $httpCode < 200 || $httpCode >= 300) {
        return ['error' => "خطأ في الخادم - كود: $httpCode"];
    }
    
    return json_decode($response, true) ?: ['error' => 'خطأ في تحليل البيانات'];
}

// جلب السجلات مع pagination
function getAllBaserowRecords($tableId, $filters = '') {
    $allResults = [];
    $page = 1;
    
    do {
        $endpoint = "/database/rows/table/$tableId/?page=$page&size=100" . ($filters ? "&$filters" : "");
        $response = makeBaserowRequest($endpoint);
        
        if (isset($response['error'])) return $response;
        
        if (isset($response['results']) && is_array($response['results'])) {
            $allResults = array_merge($allResults, $response['results']);
            $hasMore = isset($response['next']) && $response['next'] !== null;
            $page++;
        } else {
            $hasMore = false;
        }
    } while ($hasMore);
    
    return ['results' => $allResults, 'count' => count($allResults)];
}

// رفع الصور عبر up.php
function uploadImageExternal($file) {
    try {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('ملف غير صالح');
        }
        
        $postData = ['image' => new CURLFile($file['tmp_name'], $file['type'], $file['name'])];
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $uploadUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/up.php';
        
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
        curl_close($curl);
        
        if ($httpCode !== 200) throw new Exception("خطأ في خدمة الرفع - كود: $httpCode");
        
        $data = json_decode($response, true);
        if (!$data['success']) throw new Exception($data['message'] ?? 'فشل في الرفع');
        
        return ['success' => true, 'url' => $data['url'], 'message' => 'تم الرفع بنجاح'];
        
    } catch (Exception $e) {
        return uploadImageDirect($file); // طريقة احتياطية
    }
}

// رفع مباشر (احتياطي)
function uploadImageDirect($file) {
    try {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('ملف غير صالح');
        }
        
        $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!isset($allowedTypes[$mimeType])) throw new Exception('نوع الملف غير مدعوم');
        if ($file['size'] > 5 * 1024 * 1024) throw new Exception('حجم الملف كبير جداً');
        
        $filename = 'img_' . uniqid() . '_' . time() . '.' . $allowedTypes[$mimeType];
        $filepath = UPLOAD_DIR . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception('فشل في رفع الملف');
        }
        
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $fullUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/' . $filepath;
        
        return ['success' => true, 'url' => $fullUrl];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// معالجة الطلبات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add_category':
                $imageUrl = '';
                if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                    $result = uploadImageExternal($_FILES['image']);
                    if (!$result['success']) throw new Exception($result['message']);
                    $imageUrl = $result['url'];
                }
                
                $data = [
                    'field_7001' => $_POST['name'],
                    'field_7002' => $imageUrl,
                    'field_7003' => uniqid('cat_'),
                ];
                $result = makeBaserowRequest("/database/rows/table/" . CATEGORIES_TABLE_ID . "/", 'POST', $data);
                if (isset($result['error'])) throw new Exception($result['error']);
                echo json_encode(['success' => true, 'data' => $result]);
                break;
                
            case 'update_category':
                $imageUrl = $_POST['current_image'] ?? '';
                if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                    $result = uploadImageExternal($_FILES['image']);
                    if ($result['success']) $imageUrl = $result['url'];
                }
                
                $data = ['field_7001' => $_POST['name'], 'field_7002' => $imageUrl];
                $result = makeBaserowRequest("/database/rows/table/" . CATEGORIES_TABLE_ID . "/" . $_POST['id'] . "/", 'PATCH', $data);
                echo json_encode(['success' => true, 'data' => $result]);
                break;
                
            case 'delete_category':
                $data = ['field_7005' => '1'];
                $result = makeBaserowRequest("/database/rows/table/" . CATEGORIES_TABLE_ID . "/" . $_POST['id'] . "/", 'PATCH', $data);
                echo json_encode(['success' => true, 'data' => $result]);
                break;
                
            case 'add_product':
                $imageUrl = '';
                if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                    $result = uploadImageExternal($_FILES['image']);
                    if (!$result['success']) throw new Exception($result['message']);
                    $imageUrl = $result['url'];
                }
                
                $data = [
                    'field_6746' => $_POST['category_name'],
                    'field_6747' => $_POST['name'],
                    'field_6748' => $imageUrl,
                    'field_6749' => $_POST['category_id'],
                ];
                $result = makeBaserowRequest("/database/rows/table/" . PRODUCTS_TABLE_ID . "/", 'POST', $data);
                if (isset($result['error'])) throw new Exception($result['error']);
                echo json_encode(['success' => true, 'data' => $result]);
                break;
                
            case 'update_product':
                $imageUrl = $_POST['current_image'] ?? '';
                if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                    $result = uploadImageExternal($_FILES['image']);
                    if ($result['success']) $imageUrl = $result['url'];
                }
                
                $data = ['field_6747' => $_POST['name'], 'field_6748' => $imageUrl];
                $result = makeBaserowRequest("/database/rows/table/" . PRODUCTS_TABLE_ID . "/" . $_POST['id'] . "/", 'PATCH', $data);
                echo json_encode(['success' => true, 'data' => $result]);
                break;
                
            case 'delete_product':
                $data = ['field_6750' => '1'];
                $result = makeBaserowRequest("/database/rows/table/" . PRODUCTS_TABLE_ID . "/" . $_POST['id'] . "/", 'PATCH', $data);
                echo json_encode(['success' => true, 'data' => $result]);
                break;
                
            default:
                throw new Exception('عملية غير صحيحة');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// API endpoints
if (isset($_GET['api'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    switch ($_GET['api']) {
        case 'categories':
            $data = getAllBaserowRecords(CATEGORIES_TABLE_ID, "filter__field_7005__not_equal=1");
            echo json_encode($data);
            break;
            
        case 'products':
            $categoryId = $_GET['category_id'] ?? '';
            if (empty($categoryId)) {
                echo json_encode(['error' => 'معرف القسم مطلوب']);
                exit;
            }
            $filters = "filter__field_6749__equal=" . urlencode($categoryId) . "&filter__field_6750__not_equal=1";
            $data = getAllBaserowRecords(PRODUCTS_TABLE_ID, $filters);
            echo json_encode($data);
            break;
            
        case 'test':
            $testData = makeBaserowRequest("/database/rows/table/" . CATEGORIES_TABLE_ID . "/?size=1");
            echo json_encode(['test' => $testData]);
            break;
            
        default:
            echo json_encode(['error' => 'API endpoint غير صحيح']);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المنتجات - ألفا الذهبية</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        :root {
            --gold: #977e2b; --gold-hover: #b89635; --gold-light: rgba(151, 126, 43, 0.1);
            --dark-gray: #2c2c2c; --medium-gray: #666; --light-gray: #f8f9fa;
            --white: #ffffff; --border-color: #e5e7eb; --success: #28a745; --error: #dc3545;
        }

        body { font-family: 'Cairo', sans-serif; font-size: 16px; direction: rtl; background: var(--light-gray); color: var(--dark-gray); margin: 0; padding: 0; }
        .container { padding: 24px; max-width: 1400px; margin: 0 auto; }
        .card { background: var(--white); border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border: 1px solid var(--border-color); margin-bottom: 24px; transition: all 0.3s ease; }
        .card:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,0.15); }
        .card-header { padding: 24px 28px; border-bottom: 1px solid var(--border-color); background: var(--light-gray); border-radius: 12px 12px 0 0; }
        .card-title { font-size: 22px; font-weight: 600; margin: 0; display: flex; align-items: center; gap: 10px; color: var(--dark-gray); }
        .btn { display: flex; align-items: center; gap: 8px; padding: 12px 24px; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; text-decoration: none; font-family: inherit; }
        .btn-primary { background: var(--gold); color: var(--white); }
        .btn-primary:hover { background: var(--gold-hover); transform: translateY(-1px); }
        .btn-secondary { background: var(--medium-gray); color: var(--white); }
        .btn-secondary:hover { background: #555; transform: translateY(-1px); }
        .btn-sm { padding: 10px 20px; font-size: 14px; }
        .form-group { margin-bottom: 24px; }
        .form-label { display: block; font-size: 16px; font-weight: 500; color: var(--dark-gray); margin-bottom: 8px; }
        .form-control { width: 100%; padding: 14px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 16px; transition: all 0.3s ease; font-family: inherit; box-sizing: border-box; }
        .form-control:focus { outline: none; border-color: var(--gold); box-shadow: 0 0 0 3px var(--gold-light); }
        .breadcrumb { display: flex; align-items: center; margin: 0; padding: 0; list-style: none; font-size: 14px; }
        .breadcrumb-link { color: var(--medium-gray); text-decoration: none; transition: color 0.2s ease; }
        .breadcrumb-link:hover { color: var(--gold); }
        .gallery-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px; margin-top: 20px; }
        .gallery-item { background: var(--white); border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border: 1px solid var(--border-color); overflow: hidden; transition: all 0.3s ease; cursor: pointer; }
        .gallery-item:hover { transform: translateY(-4px); box-shadow: 0 8px 25px rgba(0,0,0,0.15); }
        .gallery-item-image { width: 100%; height: 200px; object-fit: contain; background: var(--light-gray); border-bottom: 1px solid var(--border-color); }
        .gallery-item-content { padding: 20px; position: relative; }
        .gallery-item-title { font-size: 18px; font-weight: 600; color: var(--dark-gray); margin: 0 0 16px 0; text-align: center; }
        .gallery-item-actions { position: absolute; top: -50px; left: 16px; display: flex; gap: 8px; opacity: 0; transition: all 0.3s ease; }
        .gallery-item:hover .gallery-item-actions { opacity: 1; top: 16px; }
        .gallery-placeholder { width: 100%; height: 200px; display: flex; align-items: center; justify-content: center; background: var(--light-gray); border-bottom: 1px solid var(--border-color); }
        .gallery-placeholder i { font-size: 48px; color: var(--gold); }
        .image-preview { width: 120px; height: 120px; object-fit: contain; border: 1px solid var(--border-color); border-radius: 8px; }
        .spinner { width: 24px; height: 24px; border: 2px solid var(--border-color); border-top: 2px solid var(--gold); border-radius: 50%; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.4); }
        .modal.show { display: flex; align-items: center; justify-content: center; }
        .modal-dialog { background: var(--white); border-radius: 12px; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto; }
        .modal-header { padding: 24px 28px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; }
        .modal-title { font-size: 20px; font-weight: 600; margin: 0; }
        .btn-close { background: none; border: none; font-size: 24px; cursor: pointer; color: var(--medium-gray); }
        .modal-body { padding: 28px; }
        .modal-footer { padding: 24px 28px; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end; gap: 16px; }
        .toast-container { position: fixed; top: 20px; left: 20px; z-index: 1100; }
        .toast { background: var(--white); border: 1px solid var(--border-color); border-radius: 8px; padding: 16px 20px; margin-bottom: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); display: flex; align-items: center; gap: 10px; min-width: 350px; font-size: 16px; }
        .toast.success { border-color: var(--success); color: var(--success); }
        .toast.error { border-color: var(--error); color: var(--error); }
        .image-upload-area { border: 2px dashed var(--border-color); border-radius: 8px; padding: 30px; text-align: center; transition: all 0.3s ease; background: var(--light-gray); cursor: pointer; }
        .image-upload-area:hover { border-color: var(--gold); background: var(--gold-light); }
        .image-upload-text { font-size: 16px; margin-bottom: 8px; }
        .image-upload-hint { font-size: 14px; color: var(--medium-gray); }
        .btn.rounded-circle { width: 40px; height: 40px; padding: 0; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        .empty-state { text-align: center; padding: 60px 20px; }
        .empty-state i { font-size: 64px; color: var(--medium-gray); margin-bottom: 20px; }
        .empty-state h3 { font-size: 20px; color: var(--dark-gray); margin-bottom: 12px; }
        .empty-state p { font-size: 16px; color: var(--medium-gray); margin-bottom: 24px; }
        .d-none { display: none !important; }
        .d-flex { display: flex; }
        .align-items-center { align-items: center; }
        .justify-content-between { justify-content: space-between; }
        .text-center { text-align: center; }
        .text-muted { color: var(--medium-gray); }
        .me-1 { margin-right: 4px; }
        .me-2 { margin-right: 8px; }
        .ms-2 { margin-left: 8px; }
        .mt-2 { margin-top: 8px; }
        .mt-3 { margin-top: 16px; }
        .mb-0 { margin-bottom: 0; }
        .mb-2 { margin-bottom: 8px; }
        .mb-3 { margin-bottom: 16px; }
        .mx-2 { margin-left: 8px; margin-right: 8px; }

        @media (max-width: 768px) {
            .container { padding: 16px; }
            .card { border-radius: 0; margin-left: -16px; margin-right: -16px; }
            .gallery-grid { gap: 16px; }
            .card-title { font-size: 18px; }
            .btn { font-size: 14px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- عرض الأقسام -->
        <div id="categoriesView">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-2">
                                    <li class="breadcrumb-item">
                                        <a href="#" class="breadcrumb-link">
                                            <i class="fas fa-layer-group me-1"></i>الأقسام
                                        </a>
                                    </li>
                                </ol>
                            </nav>
                            <h1 class="card-title">إدارة الأقسام</h1>
                        </div>
                        <div>
                            <button id="addCategoryBtn" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>إضافة قسم جديد
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div id="categoriesContainer">
                <div id="categoriesGrid" class="gallery-grid"></div>
            </div>
        </div>

        <!-- عرض المنتجات -->
        <div id="productsView" class="d-none">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-2">
                                    <li class="breadcrumb-item">
                                        <a href="#" class="breadcrumb-link" onclick="showView('categories')">
                                            <i class="fas fa-layer-group me-1"></i>الأقسام
                                        </a>
                                    </li>
                                    <li class="breadcrumb-item">
                                        <i class="fas fa-chevron-left mx-2" style="color: var(--gold)"></i>
                                        <span id="currentCategoryBreadcrumb" style="color: var(--gold)"></span>
                                    </li>
                                </ol>
                            </nav>
                            <h1 id="categoryTitle" class="card-title"></h1>
                        </div>
                        <div>
                            <button id="addProductBtn" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>إضافة منتج جديد
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div id="productsContainer">
                <div id="productsGrid" class="gallery-grid"></div>
            </div>
        </div>
    </div>

    <!-- نوافذ الإضافة والتعديل -->
    <div class="modal" id="categoryModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="categoryModalTitle">قسم جديد</h5>
                    <button type="button" class="btn-close" onclick="closeModal('categoryModal')">&times;</button>
                </div>
                <form id="categoryForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" id="categoryId" name="id">
                        <input type="hidden" id="currentCategoryImage" name="current_image">
                        
                        <div class="form-group">
                            <label for="categoryName" class="form-label">اسم القسم</label>
                            <input type="text" class="form-control" id="categoryName" name="name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="categoryImage" class="form-label">صورة القسم</label>
                            <div class="image-upload-area" onclick="document.getElementById('categoryImage').click()">
                                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                <p class="image-upload-text text-muted mb-0">انقر هنا لاختيار صورة</p>
                                <small class="image-upload-hint">أو اسحب الصورة هنا (الحد الأقصى 5 ميجا بايت)</small>
                            </div>
                            <input type="file" class="form-control d-none" id="categoryImage" name="image" accept="image/*">
                            <div id="categoryImagePreview" class="mt-3 d-none text-center">
                                <img class="image-preview" alt="معاينة الصورة">
                                <div class="mt-2">
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="removeImagePreview('category')">
                                        <i class="fas fa-times me-1"></i>إزالة الصورة
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('categoryModal')">إلغاء</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="button-text">حفظ البيانات</span>
                            <span class="spinner d-none ms-2"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal" id="productModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="productModalTitle">منتج جديد</h5>
                    <button type="button" class="btn-close" onclick="closeModal('productModal')">&times;</button>
                </div>
                <form id="productForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" id="productId" name="id">
                        <input type="hidden" id="productCategoryId" name="category_id">
                        <input type="hidden" id="productCategoryName" name="category_name">
                        <input type="hidden" id="currentProductImage" name="current_image">
                        
                        <div class="form-group">
                            <label for="productName" class="form-label">اسم المنتج</label>
                            <input type="text" class="form-control" id="productName" name="name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="productImage" class="form-label">صورة المنتج</label>
                            <div class="image-upload-area" onclick="document.getElementById('productImage').click()">
                                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                <p class="image-upload-text text-muted mb-0">انقر هنا لاختيار صورة</p>
                                <small class="image-upload-hint">أو اسحب الصورة هنا (الحد الأقصى 5 ميجا بايت)</small>
                            </div>
                            <input type="file" class="form-control d-none" id="productImage" name="image" accept="image/*">
                            <div id="productImagePreview" class="mt-3 d-none text-center">
                                <img class="image-preview" alt="معاينة الصورة">
                                <div class="mt-2">
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="removeImagePreview('product')">
                                        <i class="fas fa-times me-1"></i>إزالة الصورة
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('productModal')">إلغاء</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="button-text">حفظ البيانات</span>
                            <span class="spinner d-none ms-2"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="toast-container"></div>

    <script>
        let currentCategoryId = null, currentCategoryName = null, currentView = 'categories';

        // دوال المساعدة
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

        function showLoading(button) {
            const text = button.querySelector('.button-text');
            const spinner = button.querySelector('.spinner');
            if (text) text.textContent = 'جاري الحفظ...';
            if (spinner) spinner.classList.remove('d-none');
            button.disabled = true;
        }

        function hideLoading(button) {
            const text = button.querySelector('.button-text');
            const spinner = button.querySelector('.spinner');
            if (text) text.textContent = 'حفظ البيانات';
            if (spinner) spinner.classList.add('d-none');
            button.disabled = false;
        }

        function showView(view) {
            const categoriesView = document.getElementById('categoriesView');
            const productsView = document.getElementById('productsView');
            
            if (view === 'categories') {
                categoriesView.classList.remove('d-none');
                productsView.classList.add('d-none');
                currentCategoryId = null;
                currentCategoryName = null;
                loadCategories();
            } else {
                categoriesView.classList.add('d-none');
                productsView.classList.remove('d-none');
            }
            currentView = view;
        }

        function openModal(modalId) {
            document.getElementById(modalId).classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
            document.body.style.overflow = 'auto';
        }

        function removeImagePreview(type) {
            const preview = document.getElementById(`${type}ImagePreview`);
            const input = document.getElementById(`${type}Image`);
            const uploadArea = input.previousElementSibling;
            
            preview.classList.add('d-none');
            input.value = '';
            uploadArea.innerHTML = `
                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                <p class="image-upload-text text-muted mb-0">انقر هنا لاختيار صورة</p>
                <small class="image-upload-hint">أو اسحب الصورة هنا (الحد الأقصى 5 ميجا بايت)</small>
            `;
        }

        // إعداد رفع الصور
        function setupImageUpload(inputId, previewId) {
            const input = document.getElementById(inputId);
            const preview = document.getElementById(previewId);
            const uploadArea = input.previousElementSibling;
            
            input.addEventListener('change', e => handleImageUpload(e.target.files[0], preview, uploadArea));
            
            uploadArea.addEventListener('dragover', e => { e.preventDefault(); uploadArea.style.borderColor = 'var(--gold)'; });
            uploadArea.addEventListener('dragleave', e => { e.preventDefault(); uploadArea.style.borderColor = 'var(--border-color)'; });
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

        // تحميل البيانات
        async function loadCategories() {
            try {
                const container = document.getElementById('categoriesGrid');
                container.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 40px;"><div class="spinner"></div><p class="mt-3 text-muted">جاري تحميل الأقسام...</p></div>';
                
                const response = await fetch('?api=categories');
                const data = await response.json();
                
                if (data.error) throw new Error(data.error);
                
                container.innerHTML = '';
                const results = data.results || [];
                
                if (results.length > 0) {
                    results.forEach(category => createCategoryCard(category));
                } else {
                    container.innerHTML = `
                        <div style="grid-column: 1/-1;">
                            <div class="card empty-state">
                                <i class="fas fa-folder-plus"></i>
                                <h3>لا توجد أقسام</h3>
                                <p>ابدأ بإنشاء أول قسم للمنتجات</p>
                                <button onclick="document.getElementById('addCategoryBtn').click()" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>إضافة أول قسم
                                </button>
                            </div>
                        </div>
                    `;
                }
            } catch (error) {
                showToast('فشل في تحميل الأقسام: ' + error.message, 'error');
            }
        }

        async function loadProducts(categoryId, categoryName) {
            try {
                const container = document.getElementById('productsGrid');
                container.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 40px;"><div class="spinner"></div><p class="mt-3 text-muted">جاري تحميل المنتجات...</p></div>';
                
                const response = await fetch(`?api=products&category_id=${encodeURIComponent(categoryId)}`);
                const data = await response.json();
                
                if (data.error) throw new Error(data.error);
                
                currentCategoryId = categoryId;
                currentCategoryName = categoryName;
                document.getElementById('categoryTitle').textContent = `منتجات ${categoryName}`;
                document.getElementById('currentCategoryBreadcrumb').textContent = categoryName;
                
                container.innerHTML = '';
                const results = data.results || [];
                
                if (results.length > 0) {
                    results.forEach(product => createProductCard(product));
                } else {
                    container.innerHTML = `
                        <div style="grid-column: 1/-1;">
                            <div class="card empty-state">
                                <i class="fas fa-cube"></i>
                                <h3>لا توجد منتجات</h3>
                                <p>ابدأ بإضافة أول منتج في قسم "${categoryName}"</p>
                                <button onclick="document.getElementById('addProductBtn').click()" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>إضافة أول منتج
                                </button>
                            </div>
                        </div>
                    `;
                }
            } catch (error) {
                showToast('فشل في تحميل المنتجات: ' + error.message, 'error');
            }
        }

        // إنشاء البطاقات
        function createCategoryCard(category) {
            const container = document.getElementById('categoriesGrid');
            const card = document.createElement('div');
            card.className = 'gallery-item';
            card.onclick = () => openCategory(category.id, category.field_7001, category.field_7003);
            
            card.innerHTML = `
                ${category.field_7002 ? 
                    `<img src="${category.field_7002}" alt="${category.field_7001}" class="gallery-item-image">` :
                    `<div class="gallery-placeholder"><i class="fas fa-folder"></i></div>`
                }
                <div class="gallery-item-content">
                    <h3 class="gallery-item-title">${category.field_7001}</h3>
                    <div class="gallery-item-actions">
                        <button onclick="event.stopPropagation(); editCategory(${category.id}, '${category.field_7001.replace(/'/g, "\\'")}', '${category.field_7002 || ''}')" class="btn btn-primary btn-sm rounded-circle">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="event.stopPropagation(); deleteCategory(${category.id})" class="btn btn-secondary btn-sm rounded-circle">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            container.appendChild(card);
        }

        function createProductCard(product) {
            const container = document.getElementById('productsGrid');
            const card = document.createElement('div');
            card.className = 'gallery-item';
            
            card.innerHTML = `
                ${product.field_6748 ? 
                    `<img src="${product.field_6748}" alt="${product.field_6747}" class="gallery-item-image">` :
                    `<div class="gallery-placeholder"><i class="fas fa-cube"></i></div>`
                }
                <div class="gallery-item-content">
                    <h3 class="gallery-item-title">${product.field_6747}</h3>
                    <div class="gallery-item-actions">
                        <button onclick="editProduct(${product.id}, '${product.field_6747.replace(/'/g, "\\'")}', '${product.field_6748 || ''}')" class="btn btn-primary btn-sm rounded-circle">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deleteProduct(${product.id})" class="btn btn-secondary btn-sm rounded-circle">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            container.appendChild(card);
        }

        // العمليات
        function openCategory(categoryId, categoryName, categoryUniqueId) {
            showView('products');
            loadProducts(categoryUniqueId || categoryId, categoryName);
        }

        function editCategory(id, name, image) {
            document.getElementById('categoryModalTitle').textContent = 'تعديل القسم';
            document.getElementById('categoryId').value = id;
            document.getElementById('categoryName').value = name;
            document.getElementById('currentCategoryImage').value = image;
            
            if (image) {
                const preview = document.getElementById('categoryImagePreview');
                preview.querySelector('img').src = image;
                preview.classList.remove('d-none');
            }
            openModal('categoryModal');
        }

        function deleteCategory(id) {
            if (confirm('هل أنت متأكد من حذف هذا القسم؟')) {
                const formData = new FormData();
                formData.append('action', 'delete_category');
                formData.append('id', id);
                
                fetch('', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('تم حذف القسم بنجاح');
                        loadCategories();
                    } else {
                        showToast(data.error || 'فشل في حذف القسم', 'error');
                    }
                });
            }
        }

        function editProduct(id, name, image) {
            document.getElementById('productModalTitle').textContent = 'تعديل المنتج';
            document.getElementById('productId').value = id;
            document.getElementById('productName').value = name;
            document.getElementById('currentProductImage').value = image;
            
            if (image) {
                const preview = document.getElementById('productImagePreview');
                preview.querySelector('img').src = image;
                preview.classList.remove('d-none');
            }
            openModal('productModal');
        }

        function deleteProduct(id) {
            if (confirm('هل أنت متأكد من حذف هذا المنتج؟')) {
                const formData = new FormData();
                formData.append('action', 'delete_product');
                formData.append('id', id);
                
                fetch('', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('تم حذف المنتج بنجاح');
                        loadProducts(currentCategoryId, currentCategoryName);
                    } else {
                        showToast(data.error || 'فشل في حذف المنتج', 'error');
                    }
                });
            }
        }

        // تهيئة النظام
        document.addEventListener('DOMContentLoaded', function() {
            setupImageUpload('categoryImage', 'categoryImagePreview');
            setupImageUpload('productImage', 'productImagePreview');

            // أزرار الإضافة
            document.getElementById('addCategoryBtn').addEventListener('click', () => {
                document.getElementById('categoryForm').reset();
                document.getElementById('categoryModalTitle').textContent = 'إضافة قسم جديد';
                document.getElementById('categoryId').value = '';
                document.getElementById('categoryImagePreview').classList.add('d-none');
                openModal('categoryModal');
            });

            document.getElementById('addProductBtn').addEventListener('click', () => {
                document.getElementById('productForm').reset();
                document.getElementById('productModalTitle').textContent = 'إضافة منتج جديد';
                document.getElementById('productId').value = '';
                document.getElementById('productCategoryId').value = currentCategoryId;
                document.getElementById('productCategoryName').value = currentCategoryName;
                document.getElementById('productImagePreview').classList.add('d-none');
                openModal('productModal');
            });

            // معالجة النماذج
            document.getElementById('categoryForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const submitButton = this.querySelector('button[type="submit"]');
                const action = document.getElementById('categoryId').value ? 'update_category' : 'add_category';
                
                showLoading(submitButton);
                const formData = new FormData(this);
                formData.append('action', action);
                
                fetch('', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    hideLoading(submitButton);
                    if (data.success) {
                        showToast(action === 'add_category' ? 'تم إضافة القسم بنجاح' : 'تم تحديث القسم بنجاح');
                        closeModal('categoryModal');
                        loadCategories();
                    } else {
                        showToast(data.error || 'فشل في حفظ القسم', 'error');
                    }
                })
                .catch(error => {
                    hideLoading(submitButton);
                    showToast('خطأ في الشبكة', 'error');
                });
            });

            document.getElementById('productForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const submitButton = this.querySelector('button[type="submit"]');
                const action = document.getElementById('productId').value ? 'update_product' : 'add_product';
                
                showLoading(submitButton);
                const formData = new FormData(this);
                formData.append('action', action);
                
                fetch('', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    hideLoading(submitButton);
                    if (data.success) {
                        showToast(action === 'add_product' ? 'تم إضافة المنتج بنجاح' : 'تم تحديث المنتج بنجاح');
                        closeModal('productModal');
                        loadProducts(currentCategoryId, currentCategoryName);
                    } else {
                        showToast(data.error || 'فشل في حفظ المنتج', 'error');
                    }
                })
                .catch(error => {
                    hideLoading(submitButton);
                    showToast('خطأ في الشبكة', 'error');
                });
            });

            // أحداث إضافية
            document.addEventListener('click', e => {
                if (e.target.classList.contains('modal')) {
                    document.querySelectorAll('.modal.show').forEach(modal => closeModal(modal.id));
                }
            });

            document.addEventListener('keydown', e => {
                if (e.key === 'Escape') {
                    document.querySelectorAll('.modal.show').forEach(modal => closeModal(modal.id));
                }
            });

            // تحميل البيانات الأولية
            loadCategories();
        });
    </script>
</body>
</html>