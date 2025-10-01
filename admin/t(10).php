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
                
                // البيانات الأساسية فقط - بدون معرف_القسم لأنه يتم توليده تلقائياً
                $data = [[
                    'الاسم' => $_POST['name'],
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
                
                // جلب جميع المنتجات غير المحذوفة للقسم المحدد
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
                // اختبار إضافة قسم بسيط - بدون معرف_القسم
                $testCategory = [[
                    'الاسم' => 'قسم تجريبي ' . date('H:i:s')
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
    <title>إدارة المنتجات</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'cairo': ['Cairo', 'sans-serif'],
                    },
                    colors: {
                        'gold': '#9a7e2e',
                        'gold-light': '#b8955a',
                        'gold-dark': '#7a6424',
                    }
                }
            }
        }
    </script>
    <style>
        * { font-family: 'Cairo', sans-serif; }
        
        .spinner {
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
        
        .slide-in {
            animation: slideIn 0.3s ease-out forwards;
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        .card-hover {
            transition: all 0.2s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body class="bg-gray-50 font-cairo">
    <!-- الهيدر -->
    <header class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <img src="https://alfagolden.com/iconalfa.png" alt="شعار" class="w-10 h-10 mr-3">
                    <div>
                        <h1 class="text-xl font-semibold text-gray-900">إدارة المنتجات</h1>
                    </div>
                </div>
                <nav class="hidden md:flex items-center">
                    <button id="showCategoriesBtn" class="text-gray-600 hover:text-gold font-medium transition-colors duration-200">
                        <i class="fas fa-home mr-2"></i>الأقسام الرئيسية
                    </button>
                </nav>
            </div>
        </div>
    </header>

    <!-- المحتوى الرئيسي -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- شريط التنقل -->
        <div id="breadcrumb" class="mb-6 flex items-center text-sm">
            <button id="homeBreadcrumb" class="text-gray-500 hover:text-gold transition-colors font-medium">
                <i class="fas fa-home mr-1"></i>الصفحة الرئيسية
            </button>
            <span id="currentCategory" class="mr-2"></span>
        </div>

        <!-- عرض الأقسام -->
        <div id="categoriesView" class="view-container">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="text-2xl font-semibold text-gray-900 mb-1">الأقسام</h2>
                    <p class="text-gray-600">إدارة وتنظيم أقسام المنتجات</p>
                </div>
                <button id="addCategoryBtn" class="bg-gold hover:bg-gold-dark text-white px-6 py-2.5 rounded-lg font-medium transition-colors duration-200">
                    <i class="fas fa-plus mr-2"></i>قسم جديد
                </button>
            </div>
            <div id="categoriesGrid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6"></div>
        </div>

        <!-- عرض المنتجات -->
        <div id="productsView" class="view-container hidden">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 id="categoryTitle" class="text-2xl font-semibold text-gray-900 mb-1"></h2>
                    <p class="text-gray-600">منتجات هذا القسم</p>
                </div>
                <button id="addProductBtn" class="bg-gold hover:bg-gold-dark text-white px-6 py-2.5 rounded-lg font-medium transition-colors duration-200">
                    <i class="fas fa-plus mr-2"></i>منتج جديد
                </button>
            </div>
            <div id="productsGrid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6"></div>
        </div>
    </main>

    <!-- نافذة إضافة/تعديل القسم -->
    <div id="categoryModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl w-full max-w-md shadow-xl">
            <div class="p-6">
                <h3 id="categoryModalTitle" class="text-xl font-semibold mb-6 text-gray-900">قسم جديد</h3>
                <form id="categoryForm" enctype="multipart/form-data">
                    <input type="hidden" id="categoryId" name="id">
                    <input type="hidden" id="currentCategoryImage" name="current_image">
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">اسم القسم</label>
                        <input type="text" id="categoryName" name="name" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gold focus:border-transparent" placeholder="أدخل اسم القسم..." required>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">صورة القسم</label>
                        <input type="file" id="categoryImage" name="image" accept="image/*" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gold focus:border-transparent">
                        <div id="categoryImagePreview" class="mt-3 hidden">
                            <img class="w-20 h-20 object-contain border border-gray-200 rounded-lg" alt="معاينة">
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-reverse space-x-3">
                        <button type="button" id="closeCategoryModal" class="px-6 py-2.5 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors duration-200">إلغاء</button>
                        <button type="submit" class="bg-gold hover:bg-gold-dark text-white px-6 py-2.5 rounded-lg transition-colors duration-200 flex items-center">
                            <span class="button-text">حفظ</span>
                            <span class="spinner hidden mr-2"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- نافذة إضافة/تعديل المنتج -->
    <div id="productModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl w-full max-w-md shadow-xl">
            <div class="p-6">
                <h3 id="productModalTitle" class="text-xl font-semibold mb-6 text-gray-900">منتج جديد</h3>
                <form id="productForm" enctype="multipart/form-data">
                    <input type="hidden" id="productId" name="id">
                    <input type="hidden" id="productCategoryId" name="category_id">
                    <input type="hidden" id="productCategoryName" name="category_name">
                    <input type="hidden" id="currentProductImage" name="current_image">
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">اسم المنتج</label>
                        <input type="text" id="productName" name="name" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gold focus:border-transparent" placeholder="أدخل اسم المنتج..." required>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">صورة المنتج</label>
                        <input type="file" id="productImage" name="image" accept="image/*" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gold focus:border-transparent">
                        <div id="productImagePreview" class="mt-3 hidden">
                            <img class="w-20 h-20 object-contain border border-gray-200 rounded-lg" alt="معاينة">
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-reverse space-x-3">
                        <button type="button" id="closeProductModal" class="px-6 py-2.5 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors duration-200">إلغاء</button>
                        <button type="submit" class="bg-gold hover:bg-gold-dark text-white px-6 py-2.5 rounded-lg transition-colors duration-200 flex items-center">
                            <span class="button-text">حفظ</span>
                            <span class="spinner hidden mr-2"></span>
                        </button>
                    </div>
                </form>
            </div>
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
                    showNotification('خطأ في الاتصال مع قاعدة البيانات', 'error');
                    return false;
                } else {
                    console.log('تم الاتصال مع NocoDB بنجاح');
                    return true;
                }
            } catch (error) {
                console.error('فشل اختبار الاتصال:', error);
                showNotification('فشل في اختبار الاتصال', 'error');
                return false;
            }
        }

        // دوال المساعدة
        function showNotification(message, type = 'success') {
            const bgColor = type === 'error' ? 'bg-red-500' : 'bg-green-500';
            const icon = type === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle';
            
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 ${bgColor} text-white px-6 py-4 rounded-lg shadow-lg z-50 slide-in max-w-sm`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas ${icon} mr-3"></i>
                    <span class="flex-1">${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()" class="mr-3 text-white hover:text-gray-200">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 5000);
        }

        function showLoading(button) {
            const text = button.querySelector('.button-text');
            const spinner = button.querySelector('.spinner');
            if (text && spinner) {
                text.textContent = 'جاري الحفظ...';
                spinner.classList.remove('hidden');
                button.disabled = true;
            }
        }

        function hideLoading(button) {
            const text = button.querySelector('.button-text');
            const spinner = button.querySelector('.spinner');
            if (text && spinner) {
                text.textContent = 'حفظ';
                spinner.classList.add('hidden');
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
                categoriesGrid.innerHTML = '<div class="col-span-full text-center py-12"><div class="spinner mx-auto"></div><p class="mt-4 text-gray-600">جاري التحميل...</p></div>';
                
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
                            <div class="text-center py-16 bg-white rounded-lg border-2 border-dashed border-gray-200">
                                <i class="fas fa-folder-plus text-4xl text-gray-400 mb-4"></i>
                                <h3 class="text-lg font-semibold text-gray-600 mb-2">لا توجد أقسام</h3>
                                <p class="text-gray-500 mb-6">ابدأ بإنشاء أول قسم</p>
                                <button onclick="document.getElementById('addCategoryBtn').click()" class="bg-gold hover:bg-gold-dark text-white px-6 py-2.5 rounded-lg">
                                    <i class="fas fa-plus mr-2"></i>إضافة قسم
                                </button>
                            </div>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('تفاصيل الخطأ:', error);
                showNotification('فشل في تحميل الأقسام: ' + error.message, 'error');
                categoriesGrid.innerHTML = `
                    <div class="col-span-full">
                        <div class="text-center py-16 bg-red-50 rounded-lg border border-red-200">
                            <i class="fas fa-exclamation-triangle text-4xl text-red-400 mb-4"></i>
                            <h3 class="text-lg font-semibold text-red-600 mb-2">حدث خطأ</h3>
                            <p class="text-red-500 mb-6">${error.message}</p>
                            <button onclick="loadCategories()" class="bg-red-500 text-white px-6 py-2.5 rounded-lg hover:bg-red-600">
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
                productsGrid.innerHTML = '<div class="col-span-full text-center py-12"><div class="spinner mx-auto"></div><p class="mt-4 text-gray-600">جاري التحميل...</p></div>';
                
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
                currentCategory.innerHTML = `<i class="fas fa-chevron-left mx-2 text-gold"></i><span class="text-gold font-medium">${categoryName}</span>`;
                
                productsGrid.innerHTML = '';
                
                if (data.list && Array.isArray(data.list) && data.list.length > 0) {
                    data.list.forEach(product => {
                        createProductCard(product);
                    });
                } else {
                    productsGrid.innerHTML = `
                        <div class="col-span-full">
                            <div class="text-center py-16 bg-white rounded-lg border-2 border-dashed border-gray-200">
                                <i class="fas fa-cube text-4xl text-gray-400 mb-4"></i>
                                <h3 class="text-lg font-semibold text-gray-600 mb-2">لا توجد منتجات</h3>
                                <p class="text-gray-500 mb-6">ابدأ بإضافة أول منتج</p>
                                <button onclick="document.getElementById('addProductBtn').click()" class="bg-gold hover:bg-gold-dark text-white px-6 py-2.5 rounded-lg">
                                    <i class="fas fa-plus mr-2"></i>إضافة منتج
                                </button>
                            </div>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('تفاصيل خطأ المنتجات:', error);
                showNotification('فشل في تحميل المنتجات: ' + error.message, 'error');
                productsGrid.innerHTML = `
                    <div class="col-span-full">
                        <div class="text-center py-16 bg-red-50 rounded-lg border border-red-200">
                            <i class="fas fa-exclamation-triangle text-4xl text-red-400 mb-4"></i>
                            <h3 class="text-lg font-semibold text-red-600 mb-2">حدث خطأ</h3>
                            <p class="text-red-500 mb-6">${error.message}</p>
                            <button onclick="loadProducts('${categoryId}', '${categoryName}')" class="bg-red-500 text-white px-6 py-2.5 rounded-lg hover:bg-red-600">
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
            card.className = 'bg-white rounded-xl p-4 shadow-sm hover:shadow-md transition-all duration-200 cursor-pointer card-hover border border-gray-100';
            card.innerHTML = `
                <div class="relative">
                    <div class="w-full h-32 bg-gray-50 rounded-lg mb-3 flex items-center justify-center overflow-hidden">
                        ${category.الصورة ? 
                            `<img src="${category.الصورة}" alt="${category.الاسم}" class="w-full h-full object-contain">` :
                            `<i class="fas fa-folder text-gray-400 text-3xl"></i>`
                        }
                    </div>
                    <div class="absolute top-2 left-2 flex space-x-reverse space-x-1">
                        <button onclick="event.stopPropagation(); editCategory(${category.Id}, '${category.الاسم.replace(/'/g, "\\'")}', '${category.الصورة || ''}')" class="w-7 h-7 bg-gold hover:bg-gold-dark text-white rounded-full flex items-center justify-center transition-colors">
                            <i class="fas fa-edit text-xs"></i>
                        </button>
                        <button onclick="event.stopPropagation(); deleteCategory(${category.Id})" class="w-7 h-7 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center transition-colors">
                            <i class="fas fa-trash text-xs"></i>
                        </button>
                    </div>
                </div>
                <h3 class="text-center font-medium text-gray-800 truncate">${category.الاسم}</h3>
            `;
            
            card.addEventListener('click', () => {
                showView('products');
                const categoryIdToUse = category.معرف_القسم || category.Id;
                loadProducts(categoryIdToUse, category.الاسم);
            });
            
            categoriesGrid.appendChild(card);
        }

        // إنشاء بطاقة منتج
        function createProductCard(product) {
            const card = document.createElement('div');
            card.className = 'bg-white rounded-xl p-4 shadow-sm hover:shadow-md transition-all duration-200 card-hover border border-gray-100';
            card.innerHTML = `
                <div class="relative">
                    <div class="w-full h-32 bg-gray-50 rounded-lg mb-3 flex items-center justify-center overflow-hidden">
                        ${product.الصورة ? 
                            `<img src="${product.الصورة}" alt="${product.الاسم}" class="w-full h-full object-contain">` :
                            `<i class="fas fa-cube text-gray-400 text-3xl"></i>`
                        }
                    </div>
                    <div class="absolute top-2 left-2 flex space-x-reverse space-x-1">
                        <button onclick="editProduct(${product.Id}, '${product.الاسم.replace(/'/g, "\\'")}', '${product.الصورة || ''}')" class="w-7 h-7 bg-gold hover:bg-gold-dark text-white rounded-full flex items-center justify-center transition-colors">
                            <i class="fas fa-edit text-xs"></i>
                        </button>
                        <button onclick="deleteProduct(${product.Id})" class="w-7 h-7 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center transition-colors">
                            <i class="fas fa-trash text-xs"></i>
                        </button>
                    </div>
                </div>
                <h3 class="text-center font-medium text-gray-800 truncate">${product.الاسم}</h3>
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
            if (confirm('هل أنت متأكد من حذف هذا القسم؟')) {
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
                        showNotification('تم حذف القسم بنجاح');
                        loadCategories();
                    } else {
                        showNotification(data.error || 'فشل في حذف القسم', 'error');
                    }
                })
                .catch(error => showNotification('خطأ في الشبكة: ' + error.message, 'error'));
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
                        showNotification('تم حذف المنتج بنجاح');
                        loadProducts(currentCategoryId, currentCategoryName);
                    } else {
                        showNotification(data.error || 'فشل في حذف المنتج', 'error');
                    }
                })
                .catch(error => showNotification('خطأ في الشبكة: ' + error.message, 'error'));
            }
        }

        // معالجات الأحداث
        document.getElementById('addCategoryBtn').addEventListener('click', () => {
            document.getElementById('categoryForm').reset();
            document.getElementById('categoryModalTitle').textContent = 'قسم جديد';
            document.getElementById('categoryId').value = '';
            document.getElementById('categoryImagePreview').classList.add('hidden');
            categoryModal.classList.remove('hidden');
        });

        document.getElementById('addProductBtn').addEventListener('click', () => {
            document.getElementById('productForm').reset();
            document.getElementById('productModalTitle').textContent = 'منتج جديد';
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
                    showNotification(action === 'add_category' ? 'تم إضافة القسم بنجاح' : 'تم تحديث القسم بنجاح');
                    categoryModal.classList.add('hidden');
                    loadCategories();
                } else {
                    showNotification(data.error || 'فشل في حفظ القسم', 'error');
                }
            })
            .catch(error => {
                hideLoading(submitButton);
                showNotification('خطأ في الشبكة: ' + error.message, 'error');
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
                    showNotification(action === 'add_product' ? 'تم إضافة المنتج بنجاح' : 'تم تحديث المنتج بنجاح');
                    productModal.classList.add('hidden');
                    loadProducts(currentCategoryId, currentCategoryName);
                } else {
                    showNotification(data.error || 'فشل في حفظ المنتج', 'error');
                }
            })
            .catch(error => {
                hideLoading(submitButton);
                showNotification('خطأ في الشبكة: ' + error.message, 'error');
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
                        <div class="text-center py-16 bg-red-50 rounded-lg border border-red-200">
                            <i class="fas fa-exclamation-triangle text-4xl text-red-500 mb-4"></i>
                            <h3 class="text-lg font-semibold text-red-700 mb-2">خطأ في الاتصال</h3>
                            <p class="text-red-600 mb-6">لا يمكن الاتصال مع قاعدة البيانات</p>
                            <button onclick="location.reload()" class="bg-red-500 text-white px-6 py-2.5 rounded-lg hover:bg-red-600">
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