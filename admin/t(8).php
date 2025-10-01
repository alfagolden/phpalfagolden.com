<?php
// إعدادات NocoDB
define('NOCODB_TOKEN', 'fwVaKHr6zbDns5iW9u8annJUf5LCBJXjqPfujIpV');
define('CATEGORIES_TABLE_URL', 'https://app.nocodb.com/api/v2/tables/m1g39mqv5mtdwad/records');
define('PRODUCTS_TABLE_URL', 'https://app.nocodb.com/api/v2/tables/m4twrspf9oj7rvi/records');
define('UPLOAD_DIR', 'uploads/');

// إنشاء مجلد الرفع إذا لم يكن موجوداً
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

// دالة لإرسال طلبات HTTP
function makeHttpRequest($url, $method = 'GET', $data = null) {
    $curl = curl_init();
    
    $headers = [
        'xc-token: ' . NOCODB_TOKEN,
        'Content-Type: application/json'
    ];
    
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10
    ]);
    
    if ($data && in_array($method, ['POST', 'PATCH'])) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);
    
    // تسجيل تفاصيل الطلب للتصحيح
    error_log("HTTP Request - URL: $url, Method: $method, HTTP Code: $httpCode");
    if ($error) {
        error_log("cURL Error: $error");
    }
    
    if ($response === false) {
        error_log("HTTP Request failed for URL: $url - Error: $error");
        return ['error' => 'فشل في الاتصال مع الخادم', 'details' => $error];
    }
    
    if ($httpCode !== 200) {
        error_log("HTTP Error Code: $httpCode for URL: $url");
        error_log("Response: $response");
        return ['error' => "خطأ في الخادم - كود: $httpCode", 'response' => $response];
    }
    
    $decoded = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON Decode Error: " . json_last_error_msg());
        return ['error' => 'خطأ في تحليل استجابة الخادم', 'raw_response' => $response];
    }
    
    return $decoded;
}

// دالة لجلب جميع السجلات مع pagination
function getAllRecords($baseUrl, $where = '') {
    $allRecords = [];
    $offset = 0;
    $limit = 100; // جلب 100 سجل في كل مرة
    
    do {
        $url = $baseUrl . "?limit=$limit&offset=$offset";
        
        // إضافة فلتر where إذا كان موجوداً
        if (!empty($where)) {
            $url .= "&where=" . urlencode($where);
        }
        
        error_log("Fetching records from: $url");
        $response = makeHttpRequest($url);
        
        if (isset($response['error'])) {
            return $response; // إرجاع الخطأ
        }
        
        if (!isset($response['list']) || !is_array($response['list'])) {
            break;
        }
        
        $allRecords = array_merge($allRecords, $response['list']);
        $offset += $limit;
        
        // التحقق من وجود صفحات إضافية
        $hasMore = isset($response['pageInfo']) && 
                   !$response['pageInfo']['isLastPage'] && 
                   count($response['list']) == $limit;
                   
    } while ($hasMore);
    
    return [
        'list' => $allRecords,
        'pageInfo' => [
            'totalRows' => count($allRecords)
        ]
    ];
}

// معالجة رفع الصور
function uploadImage($file) {
    try {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('نوع الملف غير مدعوم');
        }
        
        if ($file['size'] > 5 * 1024 * 1024) { // 5MB
            throw new Exception('حجم الملف كبير جداً');
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $filepath = UPLOAD_DIR . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            $script = dirname($_SERVER['SCRIPT_NAME']);
            return $protocol . '://' . $host . $script . '/' . $filepath;
        } else {
            throw new Exception('فشل في رفع الملف');
        }
    } catch (Exception $e) {
        error_log("Upload error: " . $e->getMessage());
        return false;
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
                    $imageUrl = uploadImage($_FILES['image']);
                    if (!$imageUrl) {
                        throw new Exception('فشل في رفع الصورة');
                    }
                }
                
                // إنشاء معرف فريد للقسم
                $categoryId = rand(1000, 9999);
                
                // البيانات الأساسية فقط - نحذف الحقول التي قد تسبب مشاكل
                $data = [[
                    'الاسم' => $_POST['name'],
                    'معرف_القسم' => $categoryId,
                    'الصورة' => $imageUrl
                ]];
                
                // سجل البيانات المرسلة للتصحيح
                error_log("Add Category Data: " . json_encode($data));
                
                $result = makeHttpRequest(CATEGORIES_TABLE_URL, 'POST', $data);
                
                // سجل النتيجة للتصحيح  
                error_log("Add Category Result: " . json_encode($result));
                
                // تحقق من وجود خطأ في النتيجة
                if (isset($result['error'])) {
                    throw new Exception('خطأ من NocoDB: ' . $result['error']);
                }
                
                // تحقق من نجاح العملية
                if (!isset($result[0]['Id']) && !isset($result['Id'])) {
                    error_log("Unexpected result structure: " . json_encode($result));
                    throw new Exception('لم يتم إنشاء القسم بشكل صحيح');
                }
                
                echo json_encode(['success' => true, 'data' => $result]);
                break;
                
            case 'update_category':
                $imageUrl = $_POST['current_image'] ?? '';
                if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                    $newImageUrl = uploadImage($_FILES['image']);
                    if ($newImageUrl) {
                        $imageUrl = $newImageUrl;
                    }
                }
                
                $data = [[
                    'Id' => $_POST['id'],
                    'الاسم' => $_POST['name'],
                    'الصورة' => $imageUrl
                ]];
                
                $result = makeHttpRequest(CATEGORIES_TABLE_URL, 'PATCH', $data);
                echo json_encode(['success' => true, 'data' => $result]);
                break;
                
            case 'delete_category':
                // حذف منطقي عبر تحديث حقل "حذف" إلى 1
                $data = [[
                    'Id' => $_POST['id'],
                    'حذف' => 1
                ]];
                
                $result = makeHttpRequest(CATEGORIES_TABLE_URL, 'PATCH', $data);
                echo json_encode(['success' => true, 'data' => $result]);
                break;
                
            case 'add_product':
                $imageUrl = '';
                if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                    $imageUrl = uploadImage($_FILES['image']);
                    if (!$imageUrl) {
                        throw new Exception('فشل في رفع الصورة');
                    }
                }
                
                // الحصول على اسم القسم من معرف القسم
                $categoryName = $_POST['category_name'] ?? 'قسم غير محدد';
                
                // البيانات الأساسية فقط
                $data = [[
                    'الاسم' => $_POST['name'],
                    'معرف_القسم' => $_POST['category_id'],
                    'الصورة' => $imageUrl,
                    'القسم' => $categoryName
                ]];
                
                // سجل البيانات المرسلة للتصحيح
                error_log("Add Product Data: " . json_encode($data));
                
                $result = makeHttpRequest(PRODUCTS_TABLE_URL, 'POST', $data);
                
                // سجل النتيجة للتصحيح  
                error_log("Add Product Result: " . json_encode($result));
                
                // تحقق من وجود خطأ في النتيجة
                if (isset($result['error'])) {
                    throw new Exception('خطأ من NocoDB: ' . $result['error']);
                }
                
                // تحقق من نجاح العملية
                if (!isset($result[0]['Id']) && !isset($result['Id'])) {
                    error_log("Unexpected result structure: " . json_encode($result));
                    throw new Exception('لم يتم إنشاء المنتج بشكل صحيح');
                }
                
                echo json_encode(['success' => true, 'data' => $result]);
                break;
                
            case 'update_product':
                $imageUrl = $_POST['current_image'] ?? '';
                if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                    $newImageUrl = uploadImage($_FILES['image']);
                    if ($newImageUrl) {
                        $imageUrl = $newImageUrl;
                    }
                }
                
                $data = [[
                    'Id' => $_POST['id'],
                    'الاسم' => $_POST['name'],
                    'الصورة' => $imageUrl
                ]];
                
                $result = makeHttpRequest(PRODUCTS_TABLE_URL, 'PATCH', $data);
                echo json_encode(['success' => true, 'data' => $result]);
                break;
                
            case 'delete_product':
                // حذف منطقي عبر تحديث حقل "حذف" إلى 1
                $data = [[
                    'Id' => $_POST['id'],
                    'حذف' => 1
                ]];
                
                $result = makeHttpRequest(PRODUCTS_TABLE_URL, 'PATCH', $data);
                echo json_encode(['success' => true, 'data' => $result]);
                break;
                
            default:
                throw new Exception('عملية غير صحيحة');
        }
    } catch (Exception $e) {
        error_log("Action error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// جلب البيانات
if (isset($_GET['api'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    try {
        switch ($_GET['api']) {
            case 'categories':
                // جلب جميع الأقسام - نعدل الفلتر ليشمل الحقول المفقودة أو null
                $where = '(حذف,neq,1)~or(حذف,null)';
                error_log("Fetching categories with filter: $where");
                $data = getAllRecords(CATEGORIES_TABLE_URL, $where);
                
                if (isset($data['error'])) {
                    error_log("API Error for categories: " . print_r($data, true));
                    echo json_encode(['error' => $data['error'], 'details' => $data]);
                    exit;
                }
                
                echo json_encode($data);
                break;
                
            case 'products':
                $categoryId = $_GET['category_id'] ?? '';
                if (empty($categoryId)) {
                    echo json_encode(['error' => 'معرف القسم مطلوب']);
                    exit;
                }
                
                // جلب جميع المنتجات غير المحذوفة للقسم المحدد - مع التعامل مع الحقول المفقودة
                $where = "(معرف_القسم,eq,$categoryId)~and((حذف,neq,1)~or(حذف,null))";
                error_log("Fetching products with filter: $where");
                $data = getAllRecords(PRODUCTS_TABLE_URL, $where);
                
                if (isset($data['error'])) {
                    error_log("API Error for products: " . print_r($data, true));
                    echo json_encode(['error' => $data['error'], 'details' => $data]);
                    exit;
                }
                
                echo json_encode($data);
                break;
                
            case 'test':
                $testData = makeHttpRequest(CATEGORIES_TABLE_URL . '?limit=1');
                echo json_encode(['test' => $testData]);
                break;
                
            case 'test_add':
                // اختبار إضافة قسم بسيط
                $testCategory = [[
                    'الاسم' => 'قسم تجريبي ' . date('H:i:s'),
                    'معرف_القسم' => rand(1000, 9999)
                ]];
                
                error_log("Test add data: " . json_encode($testCategory));
                $result = makeHttpRequest(CATEGORIES_TABLE_URL, 'POST', $testCategory);
                error_log("Test add result: " . json_encode($result));
                
                echo json_encode(['test_add' => $result]);
                break;
                
            default:
                echo json_encode(['error' => 'API endpoint غير صحيح']);
        }
    } catch (Exception $e) {
        error_log("API Exception: " . $e->getMessage());
        echo json_encode(['error' => 'خطأ في النظام: ' . $e->getMessage()]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام إدارة المحتوى - الطراز الذهبي</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --gold-primary: #D4AF37;
            --gold-light: #F7E98E;
            --gold-dark: #B8860B;
            --black-primary: #1a1a1a;
            --black-secondary: #2d2d2d;
            --gray-light: #f8f9fa;
            --gray-medium: #6c757d;
            --gray-dark: #343a40;
        }
        
        * { 
            font-family: 'Cairo', sans-serif; 
        }
        
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
        }
        
        .folder-card {
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            background: linear-gradient(145deg, #ffffff, #f8f9fa);
            border: 2px solid transparent;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border-radius: 16px;
            overflow: hidden;
            position: relative;
        }
        
        .folder-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, var(--gold-primary), var(--gold-light));
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: -1;
        }
        
        .folder-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 15px 35px rgba(212, 175, 55, 0.3);
            border-color: var(--gold-primary);
        }
        
        .folder-card:hover::before {
            opacity: 0.1;
        }
        
        .folder-icon {
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
            border-radius: 12px;
            position: relative;
            overflow: hidden;
        }
        
        .folder-icon::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.3), transparent);
            transform: rotate(45deg);
            transition: all 0.3s ease;
            opacity: 0;
        }
        
        .folder-card:hover .folder-icon::before {
            opacity: 1;
            animation: shine 0.6s ease-in-out;
        }
        
        @keyframes shine {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }
        
        .product-card {
            transition: all 0.3s ease;
            background: linear-gradient(145deg, #ffffff, #f8f9fa);
            border: 1px solid #e9ecef;
            border-radius: 12px;
            overflow: hidden;
        }
        
        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 25px rgba(212, 175, 55, 0.2);
            border-color: var(--gold-primary);
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(212, 175, 55, 0.2);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
            color: white;
            border: none;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-primary::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--gold-dark), #996515);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(212, 175, 55, 0.4);
        }
        
        .btn-primary:hover::before {
            width: 200%;
            height: 200%;
        }
        
        .header-gradient {
            background: linear-gradient(135deg, var(--black-primary), var(--black-secondary));
            border-bottom: 3px solid var(--gold-primary);
        }
        
        .action-btn {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            backdrop-filter: blur(10px);
        }
        
        .action-btn.edit {
            background: rgba(212, 175, 55, 0.9);
            color: white;
        }
        
        .action-btn.delete {
            background: rgba(220, 53, 69, 0.9);
            color: white;
        }
        
        .action-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }
        
        .notification {
            animation: slideInRight 0.3s ease;
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .empty-state {
            background: linear-gradient(135deg, #f8f9fa, #ffffff);
            border: 2px dashed #dee2e6;
            border-radius: 16px;
        }
        
        .breadcrumb-item {
            color: var(--gray-medium);
            transition: color 0.2s ease;
        }
        
        .breadcrumb-item:hover {
            color: var(--gold-primary);
        }
        
        .modal-overlay {
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
        }
        
        .input-field {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .input-field:focus {
            border-color: var(--gold-primary);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.1);
            outline: none;
        }
        
        .image-preview {
            border: 2px solid var(--gold-primary);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .loading-indicator {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- الهيدر -->
    <header class="header-gradient shadow-xl">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <div class="flex items-center">
                    <div class="folder-icon w-12 h-12 flex items-center justify-center text-white mr-4">
                        <i class="fas fa-crown text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-white">نظام إدارة المحتوى</h1>
                        <p class="text-yellow-200 text-sm">الطراز الذهبي المتميز</p>
                    </div>
                </div>
                <nav class="hidden md:flex items-center space-x-reverse space-x-6">
                    <button id="showCategoriesBtn" class="text-yellow-200 hover:text-white font-medium transition-colors duration-200">
                        <i class="fas fa-home mr-2"></i>الأقسام الرئيسية
                    </button>
                </nav>
            </div>
        </div>
    </header>

    <!-- المحتوى الرئيسي -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- شريط التنقل -->
        <div id="breadcrumb" class="mb-8 flex items-center text-sm">
            <button id="homeBreadcrumb" class="breadcrumb-item hover:text-yellow-600 transition-colors font-medium">
                <i class="fas fa-home mr-1"></i>الصفحة الرئيسية
            </button>
            <span id="currentCategory" class="mr-2"></span>
        </div>

        <!-- عرض الأقسام -->
        <div id="categoriesView" class="view-container">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="text-3xl font-bold text-gray-800 mb-2">مجموعات المنتجات</h2>
                    <p class="text-gray-600">إدارة وتنظيم الأقسام والمحتويات</p>
                </div>
                <button id="addCategoryBtn" class="btn-primary px-8 py-3 rounded-lg font-medium text-lg shadow-lg">
                    <i class="fas fa-plus mr-2"></i>إضافة قسم جديد
                </button>
            </div>
            <div id="categoriesGrid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-8"></div>
        </div>

        <!-- عرض المنتجات -->
        <div id="productsView" class="view-container hidden">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 id="categoryTitle" class="text-3xl font-bold text-gray-800 mb-2"></h2>
                    <p class="text-gray-600">محتويات هذا القسم</p>
                </div>
                <button id="addProductBtn" class="btn-primary px-8 py-3 rounded-lg font-medium text-lg shadow-lg">
                    <i class="fas fa-plus mr-2"></i>إضافة منتج جديد
                </button>
            </div>
            <div id="productsGrid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-8"></div>
        </div>
    </main>

    <!-- نافذة إضافة/تعديل القسم -->
    <div id="categoryModal" class="fixed inset-0 modal-overlay hidden z-50 flex items-center justify-center">
        <div class="glass-effect p-8 rounded-2xl w-full max-w-md mx-4 shadow-2xl">
            <h3 id="categoryModalTitle" class="text-2xl font-bold mb-6 text-gray-800">إضافة قسم جديد</h3>
            <form id="categoryForm" enctype="multipart/form-data">
                <input type="hidden" id="categoryId" name="id">
                <input type="hidden" id="currentCategoryImage" name="current_image">
                
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-3">اسم القسم</label>
                    <input type="text" id="categoryName" name="name" class="input-field w-full px-4 py-3 font-medium" placeholder="أدخل اسم القسم..." required>
                </div>
                
                <div class="mb-8">
                    <label class="block text-sm font-semibold text-gray-700 mb-3">صورة القسم</label>
                    <input type="file" id="categoryImage" name="image" accept="image/*" class="input-field w-full px-4 py-3">
                    <div id="categoryImagePreview" class="mt-4 hidden">
                        <img class="image-preview w-24 h-24 object-cover" alt="معاينة الصورة">
                    </div>
                </div>
                
                <div class="flex justify-end space-x-reverse space-x-4">
                    <button type="button" id="closeCategoryModal" class="px-6 py-3 text-gray-600 bg-gray-200 rounded-lg hover:bg-gray-300 transition-all duration-200 font-medium">إلغاء</button>
                    <button type="submit" class="btn-primary px-8 py-3 rounded-lg font-medium">
                        <span class="button-text">حفظ البيانات</span>
                        <span class="loading-indicator hidden"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- نافذة إضافة/تعديل المنتج -->
    <div id="productModal" class="fixed inset-0 modal-overlay hidden z-50 flex items-center justify-center">
        <div class="glass-effect p-8 rounded-2xl w-full max-w-md mx-4 shadow-2xl">
            <h3 id="productModalTitle" class="text-2xl font-bold mb-6 text-gray-800">إضافة منتج جديد</h3>
            <form id="productForm" enctype="multipart/form-data">
                <input type="hidden" id="productId" name="id">
                <input type="hidden" id="productCategoryId" name="category_id">
                <input type="hidden" id="productCategoryName" name="category_name">
                <input type="hidden" id="currentProductImage" name="current_image">
                
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-3">اسم المنتج</label>
                    <input type="text" id="productName" name="name" class="input-field w-full px-4 py-3 font-medium" placeholder="أدخل اسم المنتج..." required>
                </div>
                
                <div class="mb-8">
                    <label class="block text-sm font-semibold text-gray-700 mb-3">صورة المنتج</label>
                    <input type="file" id="productImage" name="image" accept="image/*" class="input-field w-full px-4 py-3">
                    <div id="productImagePreview" class="mt-4 hidden">
                        <img class="image-preview w-24 h-24 object-cover" alt="معاينة الصورة">
                    </div>
                </div>
                
                <div class="flex justify-end space-x-reverse space-x-4">
                    <button type="button" id="closeProductModal" class="px-6 py-3 text-gray-600 bg-gray-200 rounded-lg hover:bg-gray-300 transition-all duration-200 font-medium">إلغاء</button>
                    <button type="submit" class="btn-primary px-8 py-3 rounded-lg font-medium">
                        <span class="button-text">حفظ البيانات</span>
                        <span class="loading-indicator hidden"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // متغيرات عامة
        let currentCategoryId = null;
        let currentCategoryName = null;
        let currentView = 'categories';
        
        // عناصر DOM
        const categoriesView = document.getElementById('categoriesView');
        const productsView = document.getElementById('productsView');
        const categoriesGrid = document.getElementById('categoriesGrid');
        const productsGrid = document.getElementById('productsGrid');
        const categoryModal = document.getElementById('categoryModal');
        const productModal = document.getElementById('productModal');
        const breadcrumb = document.getElementById('breadcrumb');
        const currentCategory = document.getElementById('currentCategory');
        const categoryTitle = document.getElementById('categoryTitle');

        // اختبار الاتصال مع NocoDB
        async function testConnection() {
            try {
                console.log('اختبار الاتصال مع NocoDB...');
                const response = await fetch('?api=test');
                const data = await response.json();
                console.log('نتيجة اختبار الاتصال:', data);
                
                if (data.test && data.test.error) {
                    console.error('خطأ في الاتصال مع NocoDB:', data.test);
                    showError('خطأ في الاتصال مع قاعدة البيانات. تحقق من التوكن والروابط.');
                    return false;
                } else {
                    console.log('تم الاتصال مع NocoDB بنجاح');
                    return true;
                }
            } catch (error) {
                console.error('فشل اختبار الاتصال:', error);
                showError('فشل في اختبار الاتصال مع NocoDB');
                return false;
            }
        }

        // دوال المساعدة
        function showError(message) {
            console.error('خطأ:', message);
            
            const errorDiv = document.createElement('div');
            errorDiv.className = 'notification fixed top-4 right-4 bg-red-500 text-white px-6 py-4 rounded-lg shadow-2xl z-50 transform transition-all duration-300 max-w-sm';
            errorDiv.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-3 text-lg"></i>
                    <div class="flex-1">
                        <div class="font-medium">حدث خطأ</div>
                        <div class="text-sm opacity-90">${message}</div>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" class="mr-4 text-white hover:text-gray-200 text-lg">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            document.body.appendChild(errorDiv);
            
            setTimeout(() => {
                if (errorDiv.parentElement) {
                    errorDiv.remove();
                }
            }, 7000);
        }

        function showSuccess(message) {
            console.log('نجح:', message);
            
            const successDiv = document.createElement('div');
            successDiv.className = 'notification fixed top-4 right-4 bg-green-500 text-white px-6 py-4 rounded-lg shadow-2xl z-50 transform transition-all duration-300 max-w-sm';
            successDiv.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-3 text-lg"></i>
                    <div class="flex-1">
                        <div class="font-medium">تم بنجاح</div>
                        <div class="text-sm opacity-90">${message}</div>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" class="mr-4 text-white hover:text-gray-200 text-lg">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            document.body.appendChild(successDiv);
            
            setTimeout(() => {
                if (successDiv.parentElement) {
                    successDiv.remove();
                }
            }, 4000);
        }

        function showLoading(button) {
            const text = button.querySelector('.button-text');
            const loading = button.querySelector('.loading-indicator');
            if (text && loading) {
                text.classList.add('hidden');
                loading.classList.remove('hidden');
                button.disabled = true;
            }
        }

        function hideLoading(button) {
            const text = button.querySelector('.button-text');
            const loading = button.querySelector('.loading-indicator');
            if (text && loading) {
                text.classList.remove('hidden');
                loading.classList.add('hidden');
                button.disabled = false;
            }
        }

        function showView(view) {
            categoriesView.classList.toggle('hidden', view !== 'categories');
            productsView.classList.toggle('hidden', view !== 'products');
            currentView = view;
            
            if (view === 'categories') {
                currentCategory.innerHTML = '';
                currentCategoryId = null;
                currentCategoryName = null;
                loadCategories();
            }
        }

        // تحميل الأقسام
        async function loadCategories() {
            try {
                console.log('جاري تحميل الأقسام...');
                categoriesGrid.innerHTML = '<div class="col-span-full text-center py-8"><div class="loading-indicator mx-auto"></div><p class="mt-4 text-gray-600">جاري تحميل الأقسام...</p></div>';
                
                const response = await fetch('?api=categories');
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                console.log('استجابة الأقسام:', data);
                
                if (data.error) {
                    throw new Error(data.error);
                }
                
                categoriesGrid.innerHTML = '';
                
                if (data.list && Array.isArray(data.list) && data.list.length > 0) {
                    data.list.forEach(category => {
                        createCategoryCard(category);
                    });
                } else {
                    categoriesGrid.innerHTML = `
                        <div class="col-span-full">
                            <div class="empty-state text-center py-16 mx-auto max-w-md">
                                <i class="fas fa-folder-plus text-6xl text-gray-400 mb-4"></i>
                                <h3 class="text-xl font-bold text-gray-600 mb-2">لا توجد أقسام بعد</h3>
                                <p class="text-gray-500 mb-6">ابدأ بإنشاء أول قسم لتنظيم محتوياتك</p>
                                <button onclick="document.getElementById('addCategoryBtn').click()" class="btn-primary px-6 py-3 rounded-lg font-medium">
                                    <i class="fas fa-plus mr-2"></i>إضافة القسم الأول
                                </button>
                            </div>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('تفاصيل الخطأ:', error);
                showError('فشل في تحميل الأقسام: ' + error.message);
                categoriesGrid.innerHTML = `
                    <div class="col-span-full">
                        <div class="empty-state text-center py-16 mx-auto max-w-md border-red-200 bg-red-50">
                            <i class="fas fa-exclamation-triangle text-6xl text-red-400 mb-4"></i>
                            <h3 class="text-xl font-bold text-red-600 mb-2">حدث خطأ في تحميل البيانات</h3>
                            <p class="text-red-500 mb-6">${error.message}</p>
                            <button onclick="loadCategories()" class="bg-red-500 text-white px-6 py-3 rounded-lg hover:bg-red-600 font-medium">
                                <i class="fas fa-redo mr-2"></i>إعادة المحاولة
                            </button>
                        </div>
                    </div>
                `;
            }
        }

        // تحميل المنتجات
        async function loadProducts(categoryId, categoryName) {
            try {
                console.log('جاري تحميل المنتجات للقسم:', categoryId);
                productsGrid.innerHTML = '<div class="col-span-full text-center py-8"><div class="loading-indicator mx-auto"></div><p class="mt-4 text-gray-600">جاري تحميل المنتجات...</p></div>';
                
                const response = await fetch(`?api=products&category_id=${encodeURIComponent(categoryId)}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                console.log('استجابة المنتجات:', data);
                
                if (data.error) {
                    throw new Error(data.error);
                }
                
                currentCategoryId = categoryId;
                currentCategoryName = categoryName;
                categoryTitle.textContent = categoryName;
                currentCategory.innerHTML = `<i class="fas fa-chevron-left mx-2 text-yellow-500"></i><span class="text-yellow-600 font-medium">${categoryName}</span>`;
                
                productsGrid.innerHTML = '';
                
                if (data.list && Array.isArray(data.list) && data.list.length > 0) {
                    data.list.forEach(product => {
                        createProductCard(product);
                    });
                } else {
                    productsGrid.innerHTML = `
                        <div class="col-span-full">
                            <div class="empty-state text-center py-16 mx-auto max-w-md">
                                <i class="fas fa-cube text-6xl text-gray-400 mb-4"></i>
                                <h3 class="text-xl font-bold text-gray-600 mb-2">لا توجد منتجات في هذا القسم</h3>
                                <p class="text-gray-500 mb-6">ابدأ بإضافة أول منتج في هذا القسم</p>
                                <button onclick="document.getElementById('addProductBtn').click()" class="btn-primary px-6 py-3 rounded-lg font-medium">
                                    <i class="fas fa-plus mr-2"></i>إضافة أول منتج
                                </button>
                            </div>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('تفاصيل خطأ المنتجات:', error);
                showError('فشل في تحميل المنتجات: ' + error.message);
                productsGrid.innerHTML = `
                    <div class="col-span-full">
                        <div class="empty-state text-center py-16 mx-auto max-w-md border-red-200 bg-red-50">
                            <i class="fas fa-exclamation-triangle text-6xl text-red-400 mb-4"></i>
                            <h3 class="text-xl font-bold text-red-600 mb-2">حدث خطأ في تحميل المنتجات</h3>
                            <p class="text-red-500 mb-6">${error.message}</p>
                            <button onclick="loadProducts('${categoryId}', '${categoryName}')" class="bg-red-500 text-white px-6 py-3 rounded-lg hover:bg-red-600 font-medium">
                                <i class="fas fa-redo mr-2"></i>إعادة المحاولة
                            </button>
                        </div>
                    </div>
                `;
            }
        }

        // إنشاء بطاقة قسم
        function createCategoryCard(category) {
            const card = document.createElement('div');
            card.className = 'folder-card p-6 cursor-pointer';
            card.innerHTML = `
                <div class="relative group">
                    <div class="w-full h-32 folder-icon mb-4 flex items-center justify-center overflow-hidden">
                        ${category.الصورة ? 
                            `<img src="${category.الصورة}" alt="${category.الاسم}" class="w-full h-full object-cover">` :
                            `<i class="fas fa-folder text-white text-4xl"></i>`
                        }
                    </div>
                    <div class="absolute top-3 left-3 opacity-0 group-hover:opacity-100 transition-all duration-300">
                        <div class="flex space-x-2">
                            <button onclick="event.stopPropagation(); editCategory(${category.Id}, '${category.الاسم.replace(/'/g, "\\'")}', '${category.الصورة || ''}')" class="action-btn edit">
                                <i class="fas fa-edit text-xs"></i>
                            </button>
                            <button onclick="event.stopPropagation(); deleteCategory(${category.Id})" class="action-btn delete">
                                <i class="fas fa-trash text-xs"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <h3 class="text-center font-semibold text-gray-800 truncate text-lg">${category.الاسم}</h3>
                <p class="text-center text-sm text-gray-500 mt-1">انقر للاستعراض</p>
            `;
            
            card.addEventListener('click', () => {
                showView('products');
                loadProducts(category.معرف_القسم, category.الاسم);
            });
            
            categoriesGrid.appendChild(card);
        }

        // إنشاء بطاقة منتج
        function createProductCard(product) {
            const card = document.createElement('div');
            card.className = 'product-card p-5 bg-white group';
            card.innerHTML = `
                <div class="relative">
                    <div class="w-full h-32 bg-gray-100 rounded-lg mb-4 flex items-center justify-center overflow-hidden">
                        ${product.الصورة ? 
                            `<img src="${product.الصورة}" alt="${product.الاسم}" class="w-full h-full object-cover">` :
                            `<i class="fas fa-cube text-gray-400 text-3xl"></i>`
                        }
                    </div>
                    <div class="absolute top-3 left-3 opacity-0 group-hover:opacity-100 transition-all duration-300">
                        <div class="flex space-x-2">
                            <button onclick="editProduct(${product.Id}, '${product.الاسم.replace(/'/g, "\\'")}', '${product.الصورة || ''}')" class="action-btn edit">
                                <i class="fas fa-edit text-xs"></i>
                            </button>
                            <button onclick="deleteProduct(${product.Id})" class="action-btn delete">
                                <i class="fas fa-trash text-xs"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <h3 class="text-center font-semibold text-gray-800 truncate">${product.الاسم}</h3>
            `;
            
            productsGrid.appendChild(card);
        }

        // دوال إدارة الأقسام
        function editCategory(id, name, image) {
            document.getElementById('categoryModalTitle').textContent = 'تعديل القسم';
            document.getElementById('categoryId').value = id;
            document.getElementById('categoryName').value = name;
            document.getElementById('currentCategoryImage').value = image;
            
            if (image) {
                const preview = document.getElementById('categoryImagePreview');
                preview.querySelector('img').src = image;
                preview.classList.remove('hidden');
            }
            
            categoryModal.classList.remove('hidden');
        }

        function deleteCategory(id) {
            if (confirm('هل أنت متأكد من حذف هذا القسم؟\nسيتم إخفاء جميع المنتجات المرتبطة به أيضاً.')) {
                const formData = new FormData();
                formData.append('action', 'delete_category');
                formData.append('id', id);
                
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showSuccess('تم حذف القسم بنجاح');
                        loadCategories();
                    } else {
                        showError(data.error || 'فشل في حذف القسم');
                    }
                })
                .catch(error => showError('خطأ في الشبكة: ' + error.message));
            }
        }

        // دوال إدارة المنتجات
        function editProduct(id, name, image) {
            document.getElementById('productModalTitle').textContent = 'تعديل المنتج';
            document.getElementById('productId').value = id;
            document.getElementById('productName').value = name;
            document.getElementById('currentProductImage').value = image;
            
            if (image) {
                const preview = document.getElementById('productImagePreview');
                preview.querySelector('img').src = image;
                preview.classList.remove('hidden');
            }
            
            productModal.classList.remove('hidden');
        }

        function deleteProduct(id) {
            if (confirm('هل أنت متأكد من حذف هذا المنتج؟')) {
                const formData = new FormData();
                formData.append('action', 'delete_product');
                formData.append('id', id);
                
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showSuccess('تم حذف المنتج بنجاح');
                        loadProducts(currentCategoryId, currentCategoryName);
                    } else {
                        showError(data.error || 'فشل في حذف المنتج');
                    }
                })
                .catch(error => showError('خطأ في الشبكة: ' + error.message));
            }
        }

        // معالجات الأحداث
        document.getElementById('addCategoryBtn').addEventListener('click', () => {
            document.getElementById('categoryForm').reset();
            document.getElementById('categoryModalTitle').textContent = 'إضافة قسم جديد';
            document.getElementById('categoryId').value = '';
            document.getElementById('categoryImagePreview').classList.add('hidden');
            categoryModal.classList.remove('hidden');
        });

        document.getElementById('addProductBtn').addEventListener('click', () => {
            document.getElementById('productForm').reset();
            document.getElementById('productModalTitle').textContent = 'إضافة منتج جديد';
            document.getElementById('productId').value = '';
            document.getElementById('productCategoryId').value = currentCategoryId;
            document.getElementById('productCategoryName').value = currentCategoryName;
            document.getElementById('productImagePreview').classList.add('hidden');
            productModal.classList.remove('hidden');
        });

        document.getElementById('showCategoriesBtn').addEventListener('click', () => showView('categories'));
        document.getElementById('homeBreadcrumb').addEventListener('click', () => showView('categories'));

        document.getElementById('closeCategoryModal').addEventListener('click', () => {
            categoryModal.classList.add('hidden');
        });

        document.getElementById('closeProductModal').addEventListener('click', () => {
            productModal.classList.add('hidden');
        });

        // معاينة الصور
        document.getElementById('categoryImage').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('categoryImagePreview');
                    preview.querySelector('img').src = e.target.result;
                    preview.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            }
        });

        document.getElementById('productImage').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('productImagePreview');
                    preview.querySelector('img').src = e.target.result;
                    preview.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            }
        });

        // معالجة إرسال النماذج
        document.getElementById('categoryForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitButton = this.querySelector('button[type="submit"]');
            showLoading(submitButton);
            
            const formData = new FormData(this);
            const action = document.getElementById('categoryId').value ? 'update_category' : 'add_category';
            formData.append('action', action);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                hideLoading(submitButton);
                if (data.success) {
                    showSuccess(action === 'add_category' ? 'تم إضافة القسم بنجاح' : 'تم تحديث القسم بنجاح');
                    categoryModal.classList.add('hidden');
                    loadCategories();
                } else {
                    showError(data.error || 'فشل في حفظ القسم');
                }
            })
            .catch(error => {
                hideLoading(submitButton);
                showError('خطأ في الشبكة: ' + error.message);
            });
        });

        document.getElementById('productForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitButton = this.querySelector('button[type="submit"]');
            showLoading(submitButton);
            
            const formData = new FormData(this);
            const action = document.getElementById('productId').value ? 'update_product' : 'add_product';
            formData.append('action', action);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                hideLoading(submitButton);
                if (data.success) {
                    showSuccess(action === 'add_product' ? 'تم إضافة المنتج بنجاح' : 'تم تحديث المنتج بنجاح');
                    productModal.classList.add('hidden');
                    loadProducts(currentCategoryId, currentCategoryName);
                } else {
                    showError(data.error || 'فشل في حفظ المنتج');
                }
            })
            .catch(error => {
                hideLoading(submitButton);
                showError('خطأ في الشبكة: ' + error.message);
            });
        });

        // إغلاق النوافذ عند النقر خارجها
        categoryModal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
            }
        });

        productModal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
            }
        });

        // تحميل البيانات عند بداية التطبيق
        document.addEventListener('DOMContentLoaded', async function() {
            console.log('بدء تشغيل التطبيق...');
            
            const connectionOk = await testConnection();
            
            if (connectionOk) {
                loadCategories();
            } else {
                categoriesGrid.innerHTML = `
                    <div class="col-span-full">
                        <div class="empty-state text-center py-16 mx-auto max-w-md border-red-200 bg-red-50">
                            <i class="fas fa-exclamation-triangle text-6xl text-red-500 mb-4"></i>
                            <h3 class="text-xl font-bold text-red-700 mb-2">خطأ في الاتصال</h3>
                            <p class="text-red-600 mb-6">لا يمكن الاتصال مع قاعدة البيانات</p>
                            <button onclick="location.reload()" class="bg-red-500 text-white px-6 py-3 rounded-lg hover:bg-red-600 font-medium">
                                <i class="fas fa-redo mr-2"></i>إعادة المحاولة
                            </button>
                        </div>
                    </div>
                `;
            }
        });
    </script>
</body>
</html>