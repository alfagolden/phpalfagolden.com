<?php
// إعدادات قاعدة البيانات والـ API
$config = [
    'api_token' => 'fwVaKHr6zbDns5iW9u8annJUf5LCBJXjqPfujIpV',
    'api_base' => 'https://app.nocodb.com/api/v2/tables',
    'tables' => [
        'files' => 'mjzc3fv727to95i',
        'sections' => 'm1g39mqv5mtdwad', 
        'products' => 'm4twrspf9oj7rvi',
        'main' => 'ma95crsjyfik3ce'
    ],
    'views' => [
        'slider_header' => 'vwbkey10hmb8eo3i',
        'slider_clients' => 'vwgb3ystgr089gb9',
        'catalogs' => 'vw4dpso62vulugx5',
        'files_view' => 'vwm7ve6soxdrrbea'
    ]
];

// إعدادات الترميز
header('Content-Type: text/html; charset=UTF-8');

// وظائف API
function makeApiCall($endpoint, $method = 'GET', $data = null) {
    global $config;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $config['api_base'] . '/' . $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'xc-token: ' . $config['api_token'],
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($method === 'PATCH') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log("CURL Error: " . $error);
        return false;
    }
    
    if ($httpCode === 200) {
        $decoded = json_decode($response, true);
        return $decoded;
    } else {
        error_log("HTTP Error: " . $httpCode . " Response: " . $response);
        return false;
    }
}

// رفع الصور
function uploadImage($file) {
    $uploadDir = 'uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'error' => 'نوع الملف غير مدعوم'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'حجم الملف كبير جداً'];
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        $baseUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
        return ['success' => true, 'url' => $baseUrl . '/' . $filepath];
    }
    
    return ['success' => false, 'error' => 'فشل في رفع الملف'];
}

// معالجة طلبات AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=UTF-8');
    
    try {
        switch ($_POST['action']) {
            case 'upload_image':
                if (isset($_FILES['image'])) {
                    $result = uploadImage($_FILES['image']);
                    echo json_encode($result);
                } else {
                    echo json_encode(['success' => false, 'error' => 'لم يتم اختيار ملف']);
                }
                break;
                
            case 'get_data':
                $tableId = $_POST['table_id'];
                $viewId = isset($_POST['view_id']) ? $_POST['view_id'] : null;
                
                $endpoint = $tableId . '/records';
                if ($viewId) {
                    $endpoint = $tableId . '/views/' . $viewId . '/records';
                }
                
                $result = makeApiCall($endpoint);
                
                if ($result && isset($result['list'])) {
                    echo json_encode(['success' => true, 'data' => $result['list']]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Failed to fetch data']);
                }
                break;
                
            case 'update_record':
                $tableId = $_POST['table_id'];
                $recordId = $_POST['record_id'];
                
                $data = ['Id' => intval($recordId)];
                
                if ($tableId === 'mjzc3fv727to95i') { // جدول الملفات
                    $data['الملف'] = $_POST['name'];
                } elseif ($tableId === 'ma95crsjyfik3ce') { // الجدول الرئيسي
                    if (isset($_POST['sub_name']) && !empty($_POST['sub_name'])) {
                        $data['الاسم الفرعي'] = $_POST['sub_name'];
                    }
                    if (isset($_POST['name']) && !empty($_POST['name'])) {
                        $data['الاسم'] = $_POST['name'];
                    }
                } else {
                    $data['الاسم'] = $_POST['name'];
                }
                
                if (!empty($_POST['image'])) {
                    $data['الصورة'] = $_POST['image'];
                }
                
                $result = makeApiCall($tableId . '/records', 'PATCH', [$data]);
                echo json_encode(['success' => $result !== false]);
                break;
                
            case 'add_record':
                $tableId = $_POST['table_id'];
                $data = [];
                
                if ($tableId === 'mjzc3fv727to95i') { // جدول الملفات
                    $data['الملف'] = $_POST['name'];
                } elseif ($tableId === 'ma95crsjyfik3ce') { // الجدول الرئيسي
                    $data['الاسم'] = $_POST['name'];
                    $data['الاسم الفرعي'] = $_POST['sub_name'] ?? $_POST['name'];
                    $data['الموقع'] = $_POST['location'] ?? 'الملفات';
                    
                    if (isset($_POST['file_id']) && !empty($_POST['file_id'])) {
                        $data['معرف الملف'] = floatval($_POST['file_id']);
                    }
                    
                    // تحديد الترتيب التلقائي
                    $existingRecords = makeApiCall($tableId . '/records');
                    if ($existingRecords && isset($existingRecords['list'])) {
                        $maxOrder = 0;
                        foreach ($existingRecords['list'] as $record) {
                            if (isset($record['ترتيب']) && is_numeric($record['ترتيب'])) {
                                $maxOrder = max($maxOrder, intval($record['ترتيب']));
                            }
                        }
                        $data['ترتيب'] = strval($maxOrder + 1);
                        $data['ترتيب فرعي'] = '1';
                    }
                } else {
                    $data['الاسم'] = $_POST['name'];
                    if (isset($_POST['section_id']) && !empty($_POST['section_id'])) {
                        $data['معرف_القسم'] = intval($_POST['section_id']);
                    }
                }
                
                if (!empty($_POST['image'])) {
                    $data['الصورة'] = $_POST['image'];
                }
                
                $result = makeApiCall($tableId . '/records', 'POST', [$data]);
                echo json_encode(['success' => $result !== false]);
                break;
                
            case 'soft_delete':
                $tableId = $_POST['table_id'];
                $recordId = $_POST['record_id'];
                $data = [
                    'Id' => intval($recordId),
                    'محذوف' => 1
                ];
                $result = makeApiCall($tableId . '/records', 'PATCH', [$data]);
                echo json_encode(['success' => $result !== false]);
                break;
                
            case 'update_order':
                $tableId = $_POST['table_id'];
                $records = json_decode($_POST['records'], true);
                $updates = [];
                
                foreach ($records as $index => $record) {
                    $updates[] = [
                        'Id' => intval($record['id']),
                        'ترتيب' => strval($index + 1)
                    ];
                }
                
                $result = makeApiCall($tableId . '/records', 'PATCH', $updates);
                echo json_encode(['success' => $result !== false]);
                break;
                
            default:
                echo json_encode(['success' => false, 'error' => 'Invalid action']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام إدارة المحتوى المتطور</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    <style>
        :root {
            --primary-color: #2c3e50;
            --primary-dark: #1a252f;
            --secondary-color: #3498db;
            --success-color: #27ae60;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --bg-light: #f8f9fa;
            --bg-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --border-color: #dee2e6;
            --shadow-sm: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            --shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            --shadow-lg: 0 1rem 2rem rgba(0, 0, 0, 0.175);
        }

        * {
            font-family: 'Cairo', sans-serif;
        }

        body {
            background: var(--bg-gradient);
            min-height: 100vh;
        }

        .main-container {
            max-width: 1400px;
            margin: 2rem auto;
            background: white;
            min-height: calc(100vh - 4rem);
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }

        .header {
            background: var(--primary-color);
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 2rem;
            text-align: center;
            position: relative;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.1;
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0;
            position: relative;
            z-index: 1;
        }

        .breadcrumb-container {
            background: var(--bg-light);
            border-bottom: 2px solid var(--border-color);
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .breadcrumb {
            background: transparent;
            margin: 0;
            padding: 0;
        }

        .breadcrumb-item a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .breadcrumb-item a:hover {
            color: var(--secondary-color);
            transform: translateX(-3px);
        }

        .content-area {
            padding: 2rem;
            min-height: 500px;
        }

        .main-folders {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .folder-card {
            background: white;
            border: none;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }

        .folder-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.5s;
        }

        .folder-card:hover::before {
            left: 100%;
        }

        .folder-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: var(--shadow-lg);
        }

        .folder-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
            transition: all 0.3s ease;
        }

        .folder-card:hover .folder-icon {
            color: var(--secondary-color);
            transform: scale(1.1);
        }

        .folder-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .folder-count {
            background: var(--secondary-color);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            display: inline-block;
            margin-top: 1rem;
        }

        .section-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .section-card {
            background: white;
            border: none;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            cursor: pointer;
            box-shadow: var(--shadow-sm);
            position: relative;
        }

        .section-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow);
        }

        .section-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: var(--bg-light);
        }

        .section-body {
            padding: 1.5rem;
        }

        .section-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .section-count {
            background: var(--success-color);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .items-list {
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }

        .items-header {
            background: var(--primary-color);
            color: white;
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: between;
            align-items: center;
        }

        .items-header h4 {
            margin: 0;
            flex: 1;
        }

        .add-btn {
            background: var(--success-color);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .add-btn:hover {
            background: #219a52;
            transform: translateY(-2px);
            color: white;
        }

        .item-row {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            position: relative;
        }

        .item-row:hover {
            background: var(--bg-light);
        }

        .item-row:last-child {
            border-bottom: none;
        }

        .drag-handle {
            cursor: move;
            color: #999;
            margin-left: 1rem;
            font-size: 1.2rem;
            transition: color 0.3s ease;
        }

        .drag-handle:hover {
            color: var(--primary-color);
        }

        .item-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            margin-left: 1rem;
            border: 2px solid var(--border-color);
        }

        .item-content {
            flex: 1;
            margin-left: 1rem;
        }

        .item-title {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.25rem;
        }

        .item-subtitle {
            font-size: 0.9rem;
            color: #6c757d;
        }

        .item-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .btn-icon:hover {
            transform: scale(1.1);
        }

        .btn-edit {
            background: var(--secondary-color);
            color: white;
        }

        .btn-edit:hover {
            background: #2980b9;
        }

        .btn-delete {
            background: var(--danger-color);
            color: white;
        }

        .btn-delete:hover {
            background: #c0392b;
        }

        .modal-content {
            border: none;
            border-radius: 15px;
            box-shadow: var(--shadow-lg);
        }

        .modal-header {
            background: var(--primary-color);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 1.5rem 2rem;
        }

        .modal-title {
            font-weight: 600;
        }

        .modal-body {
            padding: 2rem;
        }

        .form-label {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select {
            border: 2px solid var(--border-color);
            border-radius: 8px;
            padding: 0.75rem;
            font-family: 'Cairo', sans-serif;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }

        .image-upload-area {
            border: 2px dashed var(--border-color);
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .image-upload-area:hover {
            border-color: var(--secondary-color);
            background: var(--bg-light);
        }

        .image-preview {
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
            margin: 1rem auto;
            display: block;
        }

        .loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.95);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .loader-content {
            text-align: center;
            color: var(--primary-color);
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid var(--border-color);
            border-top-color: var(--secondary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .sortable-ghost {
            opacity: 0.4;
        }

        .btn-primary {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
        }

        @media (max-width: 768px) {
            .main-container {
                margin: 1rem;
                border-radius: 15px;
                min-height: calc(100vh - 2rem);
            }
            
            .header {
                padding: 1.5rem;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .content-area {
                padding: 1rem;
            }
            
            .main-folders {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .item-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
                padding: 1rem;
            }
            
            .item-actions {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Loader -->
    <div class="loader" id="loader">
        <div class="loader-content">
            <div class="spinner"></div>
            <h5>جاري التحميل...</h5>
        </div>
    </div>

    <div class="main-container">
        <!-- Header -->
        <div class="header">
            <h1>
                <i class="fas fa-cogs me-3"></i>
                نظام إدارة المحتوى
            </h1>
        </div>

        <!-- Breadcrumb -->
        <div class="breadcrumb-container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb" id="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="#" onclick="showMainFolders()">
                            <i class="fas fa-home me-1"></i> الرئيسية
                        </a>
                    </li>
                </ol>
            </nav>
        </div>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Main Folders -->
            <div id="main-folders" class="main-folders">
                <div class="folder-card" onclick="showProductsSection()">
                    <i class="fas fa-cube folder-icon"></i>
                    <div class="folder-title">المنتجات</div>
                    <div class="folder-count" id="products-count">0</div>
                </div>
                
                <div class="folder-card" onclick="showSiteElementsSection()">
                    <i class="fas fa-globe folder-icon"></i>
                    <div class="folder-title">عناصر الموقع</div>
                    <div class="folder-count" id="site-elements-count">0</div>
                </div>
            </div>

            <!-- Dynamic Content -->
            <div id="dynamic-content" style="display: none;"></div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="itemModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">إضافة عنصر جديد</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="itemForm">
                        <input type="hidden" id="itemId">
                        <input type="hidden" id="currentTable">
                        <input type="hidden" id="currentContext">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">الاسم الرئيسي</label>
                                    <input type="text" class="form-control" id="itemName" required>
                                </div>
                            </div>
                            <div class="col-md-6" id="subNameContainer" style="display: none;">
                                <div class="mb-3">
                                    <label class="form-label">الاسم الفرعي</label>
                                    <input type="text" class="form-control" id="itemSubName">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">الصورة</label>
                            <div class="image-upload-area" onclick="document.getElementById('imageInput').click()">
                                <i class="fas fa-cloud-upload-alt fa-2x mb-2"></i>
                                <p>اضغط لرفع صورة أو اسحب الملف هنا</p>
                                <input type="file" id="imageInput" accept="image/*" style="display: none;">
                            </div>
                            <img id="imagePreview" class="image-preview" style="display: none;">
                            <input type="hidden" id="imageUrl">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" class="btn btn-primary" onclick="saveItem()">
                        <i class="fas fa-save me-1"></i> حفظ
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // المتغيرات العامة
        let currentContext = null;
        let allData = {};
        const tables = <?php echo json_encode($config['tables']); ?>;
        const views = <?php echo json_encode($config['views']); ?>;

        // إعداد Axios
        axios.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded';

        // تحميل البيانات
        async function loadData() {
            showLoader();
            
            try {
                allData = {};
                
                for (const [key, tableId] of Object.entries(tables)) {
                    const formData = new FormData();
                    formData.append('action', 'get_data');
                    formData.append('table_id', tableId);
                    
                    const response = await axios.post('', formData);
                    
                    if (response.data.success) {
                        allData[key] = response.data.data.filter(item => !item.محذوف) || [];
                    } else {
                        allData[key] = [];
                    }
                }
                
                updateCounts();
                
            } catch (error) {
                console.error('خطأ في تحميل البيانات:', error);
                alert('فشل في تحميل البيانات');
            } finally {
                hideLoader();
            }
        }

        // تحديث العدادات
        function updateCounts() {
            const sectionsCount = allData.sections?.length || 0;
            const productsCount = allData.products?.length || 0;
            const siteElementsCount = allData.main?.length || 0;
            
            document.getElementById('products-count').textContent = sectionsCount + productsCount;
            document.getElementById('site-elements-count').textContent = siteElementsCount;
        }

        // عرض الصفحة الرئيسية
        function showMainFolders() {
            document.getElementById('main-folders').style.display = 'grid';
            document.getElementById('dynamic-content').style.display = 'none';
            updateBreadcrumb([{text: 'الرئيسية', onclick: 'showMainFolders()'}]);
            currentContext = null;
        }

        // عرض قسم المنتجات
        function showProductsSection() {
            const sections = allData.sections || [];
            
            document.getElementById('main-folders').style.display = 'none';
            document.getElementById('dynamic-content').style.display = 'block';
            
            updateBreadcrumb([
                {text: 'الرئيسية', onclick: 'showMainFolders()'},
                {text: 'المنتجات', onclick: 'showProductsSection()'}
            ]);

            let html = `
                <div class="items-list">
                    <div class="items-header">
                        <h4><i class="fas fa-cube me-2"></i>أقسام المنتجات</h4>
                        <button class="add-btn" onclick="showAddModal('sections')">
                            <i class="fas fa-plus"></i> إضافة قسم جديد
                        </button>
                    </div>
            `;

            if (sections.length === 0) {
                html += '<div class="empty-state"><i class="fas fa-cube"></i><p>لا توجد أقسام متاحة</p></div>';
            } else {
                html += '<div class="section-grid" style="padding: 2rem;">';
                sections.forEach(section => {
                    const count = allData.products?.filter(p => p.معرف_القسم === section.معرف_القسم).length || 0;
                    const hasImage = section.الصورة && section.الصورة.trim();
                    
                    html += `
                        <div class="section-card" onclick="showSectionProducts(${section.معرف_القسم}, '${section.الاسم}')">
                            ${hasImage ? 
                                `<img src="${section.الصورة}" class="section-image" alt="${section.الاسم}" onerror="this.style.display='none'">` : 
                                `<div class="section-image d-flex align-items-center justify-content-center bg-light">
                                    <i class="fas fa-cube fa-3x text-muted"></i>
                                </div>`
                            }
                            <div class="section-body">
                                <div class="section-title">${section.الاسم}</div>
                                <span class="section-count">${count} منتج</span>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
            }
            
            html += '</div>';
            document.getElementById('dynamic-content').innerHTML = html;
            currentContext = {type: 'sections', table: 'sections'};
        }

        // عرض منتجات قسم معين
        function showSectionProducts(sectionId, sectionName) {
            const products = allData.products?.filter(p => p.معرف_القسم === sectionId) || [];
            
            updateBreadcrumb([
                {text: 'الرئيسية', onclick: 'showMainFolders()'},
                {text: 'المنتجات', onclick: 'showProductsSection()'},
                {text: sectionName, onclick: `showSectionProducts(${sectionId}, '${sectionName}')`}
            ]);

            let html = `
                <div class="items-list">
                    <div class="items-header">
                        <h4><i class="fas fa-cube me-2"></i>منتجات ${sectionName}</h4>
                        <button class="add-btn" onclick="showAddModal('products', ${sectionId})">
                            <i class="fas fa-plus"></i> إضافة منتج جديد
                        </button>
                    </div>
            `;

            if (products.length === 0) {
                html += '<div class="empty-state"><i class="fas fa-box"></i><p>لا توجد منتجات في هذا القسم</p></div>';
            } else {
                products.forEach(product => {
                    html += createItemRow(product, 'products');
                });
            }
            
            html += '</div>';
            document.getElementById('dynamic-content').innerHTML = html;
            currentContext = {type: 'products', table: 'products', parent: sectionId};
            initSortable();
        }

        // عرض قسم عناصر الموقع
        function showSiteElementsSection() {
            document.getElementById('main-folders').style.display = 'none';
            document.getElementById('dynamic-content').style.display = 'block';
            
            updateBreadcrumb([
                {text: 'الرئيسية', onclick: 'showMainFolders()'},
                {text: 'عناصر الموقع', onclick: 'showSiteElementsSection()'}
            ]);

            // الحصول على المجلدات من الملفات
            const files = allData.files || [];
            
            // المواقع الخاصة
            const specialLocations = [
                {name: 'سلايدر الهيدر', view: 'slider_header', icon: 'fas fa-sliders-h'},
                {name: 'سلايدر العملاء', view: 'slider_clients', icon: 'fas fa-users'},
                {name: 'الكتلوجات', view: 'catalogs', icon: 'fas fa-book'},
                {name: 'الملفات', view: 'files_view', icon: 'fas fa-folder'}
            ];

            let html = `
                <div class="items-list">
                    <div class="items-header">
                        <h4><i class="fas fa-globe me-2"></i>عناصر الموقع</h4>
                    </div>
                    <div class="section-grid" style="padding: 2rem;">
            `;

            // إضافة المجلدات من الملفات
            files.forEach(file => {
                const count = allData.main?.filter(item => item['معرف الملف'] == file.المعرف).length || 0;
                
                html += `
                    <div class="section-card" onclick="showFileItems(${file.المعرف}, '${file.الملف}')">
                        <div class="section-image d-flex align-items-center justify-content-center bg-light">
                            <i class="fas fa-folder fa-3x text-primary"></i>
                        </div>
                        <div class="section-body">
                            <div class="section-title">${file.الملف}</div>
                            <span class="section-count">${count} عنصر</span>
                        </div>
                    </div>
                `;
            });

            // إضافة المواقع الخاصة
            specialLocations.forEach(location => {
                html += `
                    <div class="section-card" onclick="showLocationItems('${location.name}', '${location.view}')">
                        <div class="section-image d-flex align-items-center justify-content-center bg-light">
                            <i class="${location.icon} fa-3x text-success"></i>
                        </div>
                        <div class="section-body">
                            <div class="section-title">${location.name}</div>
                            <span class="section-count">0 عنصر</span>
                        </div>
                    </div>
                `;
            });

            html += `
                    </div>
                </div>
            `;
            
            document.getElementById('dynamic-content').innerHTML = html;
            currentContext = {type: 'site-elements'};
        }

        // عرض عناصر ملف معين
        function showFileItems(fileId, fileName) {
            const items = allData.main?.filter(item => item['معرف الملف'] == fileId) || [];
            
            updateBreadcrumb([
                {text: 'الرئيسية', onclick: 'showMainFolders()'},
                {text: 'عناصر الموقع', onclick: 'showSiteElementsSection()'},
                {text: fileName, onclick: `showFileItems(${fileId}, '${fileName}')`}
            ]);

            let html = `
                <div class="items-list">
                    <div class="items-header">
                        <h4><i class="fas fa-folder me-2"></i>${fileName}</h4>
                        <button class="add-btn" onclick="showAddModal('main', null, ${fileId})">
                            <i class="fas fa-plus"></i> إضافة عنصر جديد
                        </button>
                    </div>
            `;

            if (items.length === 0) {
                html += '<div class="empty-state"><i class="fas fa-file"></i><p>لا توجد عناصر في هذا الملف</p></div>';
            } else {
                // ترتيب العناصر حسب الترتيب والترتيب الفرعي
                items.sort((a, b) => {
                    const orderA = parseInt(a.ترتيب) || 0;
                    const orderB = parseInt(b.ترتيب) || 0;
                    if (orderA !== orderB) return orderA - orderB;
                    
                    const subOrderA = parseInt(a['ترتيب فرعي']) || 0;
                    const subOrderB = parseInt(b['ترتيب فرعي']) || 0;
                    return subOrderA - subOrderB;
                });
                
                items.forEach(item => {
                    html += createItemRow(item, 'main', true);
                });
            }
            
            html += '</div>';
            document.getElementById('dynamic-content').innerHTML = html;
            currentContext = {type: 'file-items', table: 'main', parent: fileId};
            initSortable();
        }

        // عرض عناصر موقع خاص
        async function showLocationItems(locationName, viewId) {
            showLoader();
            
            try {
                const formData = new FormData();
                formData.append('action', 'get_data');
                formData.append('table_id', tables.main);
                formData.append('view_id', views[viewId]);
                
                const response = await axios.post('', formData);
                const items = response.data.success ? (response.data.data || []) : [];
                
                updateBreadcrumb([
                    {text: 'الرئيسية', onclick: 'showMainFolders()'},
                    {text: 'عناصر الموقع', onclick: 'showSiteElementsSection()'},
                    {text: locationName, onclick: `showLocationItems('${locationName}', '${viewId}')`}
                ]);

                let html = `
                    <div class="items-list">
                        <div class="items-header">
                            <h4><i class="fas fa-map-pin me-2"></i>${locationName}</h4>
                            <button class="add-btn" onclick="showAddModal('main', null, null, '${locationName}')">
                                <i class="fas fa-plus"></i> إضافة عنصر جديد
                            </button>
                        </div>
                `;

                if (items.length === 0) {
                    html += '<div class="empty-state"><i class="fas fa-inbox"></i><p>لا توجد عناصر في هذا الموقع</p></div>';
                } else {
                    items.forEach(item => {
                        html += createItemRow(item, 'main', true);
                    });
                }
                
                html += '</div>';
                document.getElementById('dynamic-content').innerHTML = html;
                currentContext = {type: 'location-items', table: 'main', location: locationName};
                initSortable();
                
            } catch (error) {
                console.error('خطأ في تحميل عناصر الموقع:', error);
                alert('فشل في تحميل العناصر');
            } finally {
                hideLoader();
            }
        }

        // إنشاء صف عنصر
        function createItemRow(item, table, showSubName = false) {
            const itemName = item.الاسم || item.الملف || 'بدون اسم';
            const itemSubName = item['الاسم الفرعي'] || '';
            const hasImage = item.الصورة && item.الصورة.trim();
            const itemId = item.Id || item.المعرف || item.معرف_القسم;
            
            const imageHtml = hasImage ? 
                `<img src="${item.الصورة}" class="item-image" alt="${itemName}" onerror="this.style.display='none'">` : 
                '<div class="item-image bg-light d-flex align-items-center justify-content-center"><i class="fas fa-image text-muted"></i></div>';

            const orderInfo = table === 'main' && item.ترتيب ? 
                `<small class="text-muted">ترتيب: ${item.ترتيب}${item['ترتيب فرعي'] ? '.' + item['ترتيب فرعي'] : ''}</small>` : '';

            return `
                <div class="item-row" data-id="${itemId}">
                    <i class="fas fa-grip-vertical drag-handle"></i>
                    ${imageHtml}
                    <div class="item-content">
                        <div class="item-title">${showSubName && itemSubName ? itemSubName : itemName}</div>
                        ${showSubName && itemSubName && itemSubName !== itemName ? `<div class="item-subtitle">${itemName}</div>` : ''}
                        ${orderInfo}
                    </div>
                    <div class="item-actions">
                        <button class="btn-icon btn-edit" onclick="editItem(${itemId}, '${table}')" title="تعديل">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-icon btn-delete" onclick="deleteItem(${itemId}, '${table}')" title="حذف">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
        }

        // تحديث مسار التنقل
        function updateBreadcrumb(items) {
            const breadcrumb = document.getElementById('breadcrumb');
            breadcrumb.innerHTML = '';
            
            items.forEach((item, index) => {
                const li = document.createElement('li');
                li.className = 'breadcrumb-item';
                
                if (index === items.length - 1) {
                    li.className += ' active';
                    li.textContent = item.text;
                } else {
                    li.innerHTML = `<a href="#" onclick="${item.onclick}">${item.text}</a>`;
                }
                
                breadcrumb.appendChild(li);
            });
        }

        // إظهار modal للإضافة
        function showAddModal(table, sectionId = null, fileId = null, location = null) {
            document.getElementById('modalTitle').textContent = 'إضافة عنصر جديد';
            document.getElementById('itemForm').reset();
            document.getElementById('itemId').value = '';
            document.getElementById('currentTable').value = table;
            document.getElementById('imagePreview').style.display = 'none';
            document.getElementById('imageUrl').value = '';
            
            // إعداد الحقول حسب نوع الجدول
            const subNameContainer = document.getElementById('subNameContainer');
            if (table === 'main') {
                subNameContainer.style.display = 'block';
                document.getElementById('currentContext').value = JSON.stringify({
                    sectionId, fileId, location
                });
            } else {
                subNameContainer.style.display = 'none';
            }
            
            new bootstrap.Modal(document.getElementById('itemModal')).show();
        }

        // تعديل عنصر
        function editItem(id, table) {
            const item = findItemById(id, table);
            if (!item) return;
            
            document.getElementById('modalTitle').textContent = 'تعديل العنصر';
            document.getElementById('itemId').value = id;
            document.getElementById('currentTable').value = table;
            document.getElementById('itemName').value = item.الاسم || item.الملف || '';
            
            // إعداد الحقول حسب نوع الجدول
            const subNameContainer = document.getElementById('subNameContainer');
            if (table === 'main') {
                subNameContainer.style.display = 'block';
                document.getElementById('itemSubName').value = item['الاسم الفرعي'] || '';
            } else {
                subNameContainer.style.display = 'none';
            }
            
            if (item.الصورة) {
                document.getElementById('imageUrl').value = item.الصورة;
                document.getElementById('imagePreview').src = item.الصورة;
                document.getElementById('imagePreview').style.display = 'block';
            } else {
                document.getElementById('imagePreview').style.display = 'none';
                document.getElementById('imageUrl').value = '';
            }
            
            new bootstrap.Modal(document.getElementById('itemModal')).show();
        }

        // حذف عنصر
        async function deleteItem(id, table) {
            if (!confirm('هل تريد حذف هذا العنصر؟')) return;
            
            showLoader();
            
            try {
                const formData = new FormData();
                formData.append('action', 'soft_delete');
                formData.append('table_id', tables[table]);
                formData.append('record_id', id);
                
                const response = await axios.post('', formData);
                
                if (response.data.success) {
                    await loadData();
                    refreshCurrentView();
                } else {
                    alert('فشل في حذف العنصر');
                }
            } catch (error) {
                console.error('خطأ في الحذف:', error);
                alert('حدث خطأ أثناء الحذف');
            } finally {
                hideLoader();
            }
        }

        // حفظ العنصر
        async function saveItem() {
            const form = document.getElementById('itemForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            showLoader();
            
            try {
                const formData = new FormData();
                const isEdit = document.getElementById('itemId').value;
                const table = document.getElementById('currentTable').value;
                
                formData.append('action', isEdit ? 'update_record' : 'add_record');
                formData.append('table_id', tables[table]);
                formData.append('name', document.getElementById('itemName').value);
                formData.append('image', document.getElementById('imageUrl').value);
                
                if (table === 'main') {
                    formData.append('sub_name', document.getElementById('itemSubName').value);
                    
                    if (!isEdit) {
                        const context = JSON.parse(document.getElementById('currentContext').value || '{}');
                        if (context.fileId) {
                            formData.append('file_id', context.fileId);
                        }
                        if (context.location) {
                            formData.append('location', context.location);
                        }
                    }
                }
                
                if (isEdit) {
                    formData.append('record_id', document.getElementById('itemId').value);
                } else if (currentContext?.parent) {
                    if (table === 'products') {
                        formData.append('section_id', currentContext.parent);
                    }
                }
                
                const response = await axios.post('', formData);
                
                if (response.data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('itemModal')).hide();
                    await loadData();
                    refreshCurrentView();
                } else {
                    alert('فشل في حفظ العنصر');
                }
            } catch (error) {
                console.error('خطأ في الحفظ:', error);
                alert('حدث خطأ أثناء الحفظ');
            } finally {
                hideLoader();
            }
        }

        // رفع الصورة
        async function uploadImage(file) {
            const formData = new FormData();
            formData.append('action', 'upload_image');
            formData.append('image', file);
            
            try {
                const response = await axios.post('', formData);
                return response.data;
            } catch (error) {
                console.error('خطأ في رفع الصورة:', error);
                return {success: false, error: 'فشل في رفع الصورة'};
            }
        }

        // تهيئة رفع الصور
        document.getElementById('imageInput').addEventListener('change', async function(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            showLoader();
            
            const result = await uploadImage(file);
            
            if (result.success) {
                document.getElementById('imageUrl').value = result.url;
                document.getElementById('imagePreview').src = result.url;
                document.getElementById('imagePreview').style.display = 'block';
            } else {
                alert(result.error || 'فشل في رفع الصورة');
            }
            
            hideLoader();
        });

        // البحث عن عنصر بالـ ID
        function findItemById(id, table) {
            return allData[table]?.find(item => 
                (item.Id || item.المعرف || item.معرف_القسم) == id
            );
        }

        // تحديث العرض الحالي
        function refreshCurrentView() {
            if (!currentContext) {
                showMainFolders();
                return;
            }
            
            switch (currentContext.type) {
                case 'sections':
                    showProductsSection();
                    break;
                case 'products':
                    const section = findItemById(currentContext.parent, 'sections');
                    showSectionProducts(currentContext.parent, section?.الاسم || '');
                    break;
                case 'site-elements':
                    showSiteElementsSection();
                    break;
                case 'file-items':
                    const file = findItemById(currentContext.parent, 'files');
                    showFileItems(currentContext.parent, file?.الملف || '');
                    break;
                case 'location-items':
                    showLocationItems(currentContext.location, currentContext.view);
                    break;
            }
        }

        // تهيئة السحب والإفلات
        function initSortable() {
            const container = document.querySelector('#dynamic-content .items-list');
            if (!container) return;
            
            const items = container.querySelectorAll('.item-row');
            
            if (items.length > 0) {
                new Sortable(container, {
                    handle: '.drag-handle',
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    onEnd: async function(evt) {
                        await updateOrder();
                    }
                });
            }
        }

        // تحديث ترتيب العناصر
        async function updateOrder() {
            const items = document.querySelectorAll('#dynamic-content .item-row');
            const records = Array.from(items).map(item => ({
                id: item.dataset.id
            }));
            
            showLoader();
            
            try {
                const formData = new FormData();
                formData.append('action', 'update_order');
                formData.append('table_id', tables[currentContext.table]);
                formData.append('records', JSON.stringify(records));
                
                const response = await axios.post('', formData);
                
                if (!response.data.success) {
                    alert('فشل في تحديث الترتيب');
                    refreshCurrentView();
                }
            } catch (error) {
                console.error('خطأ في تحديث الترتيب:', error);
                refreshCurrentView();
            } finally {
                hideLoader();
            }
        }

        // إظهار/إخفاء اللودر
        function showLoader() {
            document.getElementById('loader').style.display = 'flex';
        }

        function hideLoader() {
            document.getElementById('loader').style.display = 'none';
        }

        // تهيئة الصفحة
        document.addEventListener('DOMContentLoaded', function() {
            loadData();
        });
    </script>
</body>
</html>