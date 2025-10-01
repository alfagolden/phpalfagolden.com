<?php
// config.php - إعدادات قاعدة البيانات والـ API
$config = [
    'api_token' => 'fwVaKHr6zbDns5iW9u8annJUf5LCBJXjqPfujIpV',
    'api_base' => 'https://app.nocodb.com/api/v2/tables',
    'tables' => [
        'files' => 'mjzc3fv727to95i',
        'sections' => 'm1g39mqv5mtdwad', 
        'products' => 'm4twrspf9oj7rvi',
        'main' => 'ma95crsjyfik3ce'
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

// معالجة طلبات AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=UTF-8');
    
    try {
        switch ($_POST['action']) {
            case 'get_data':
                $tableId = $_POST['table_id'];
                $result = makeApiCall($tableId . '/records');
                
                if ($result && isset($result['list'])) {
                    echo json_encode(['success' => true, 'data' => $result['list']]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Failed to fetch data', 'result' => $result]);
                }
                break;
                
            case 'update_record':
                $tableId = $_POST['table_id'];
                $recordId = $_POST['record_id'];
                
                // تحديد الحقول حسب الجدول
                $data = ['Id' => intval($recordId)];
                
                if ($tableId === 'mjzc3fv727to95i') { // جدول الملفات
                    $data['الملف'] = $_POST['name'];
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
                
                // تحديد الحقول حسب الجدول
                if ($tableId === 'mjzc3fv727to95i') { // جدول الملفات
                    $data['الملف'] = $_POST['name'];
                } else {
                    $data['الاسم'] = $_POST['name'];
                }
                
                if (!empty($_POST['image'])) {
                    $data['الصورة'] = $_POST['image'];
                }
                
                // إضافة الحقول الخاصة حسب الجدول
                if (isset($_POST['section_id']) && !empty($_POST['section_id'])) {
                    $data['معرف_القسم'] = intval($_POST['section_id']);
                }
                if (isset($_POST['file_id']) && !empty($_POST['file_id'])) {
                    $data['معرف الملف'] = intval($_POST['file_id']);
                }
                if (isset($_POST['location']) && !empty($_POST['location'])) {
                    $data['الموقع'] = $_POST['location'];
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
                $records = $_POST['records'];
                $updates = [];
                
                foreach ($records as $index => $record) {
                    $updates[] = [
                        'Id' => intval($record['id']),
                        'ترتيب' => $index + 1
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
    <title>إدارة المحتوى</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- SortableJS -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    
    <!-- Axios -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    <style>
        :root {
            --primary-color: #977e2b;
            --primary-dark: #7a6423;
            --bg-light: #f8f9fa;
            --border-color: #dee2e6;
            --shadow-sm: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            --shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }

        * {
            font-family: 'Cairo', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            min-height: 100vh;
            box-shadow: var(--shadow);
        }

        .header {
            background: var(--primary-color);
            color: white;
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .breadcrumb {
            background: var(--bg-light);
            border-bottom: 1px solid var(--border-color);
            margin: 0;
            padding: 1rem 2rem;
        }

        .breadcrumb-item a {
            color: var(--primary-color);
            text-decoration: none;
        }

        .content-area {
            padding: 2rem;
        }

        .section-card {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            margin-bottom: 1rem;
            overflow: hidden;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .section-card:hover {
            box-shadow: var(--shadow);
            transform: translateY(-2px);
        }

        .section-header {
            background: var(--bg-light);
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .item-row {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            transition: background 0.2s ease;
        }

        .item-row:hover {
            background: var(--bg-light);
        }

        .item-row:last-child {
            border-bottom: none;
        }

        .item-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 6px;
            margin-left: 1rem;
            border: 1px solid var(--border-color);
        }

        .item-title {
            flex: 1;
            font-weight: 500;
        }

        .item-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-icon {
            width: 35px;
            height: 35px;
            border-radius: 6px;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-primary { background: var(--primary-color); color: white; }
        .btn-success { background: #198754; color: white; }
        .btn-danger { background: #dc3545; color: white; }

        .btn-icon:hover {
            transform: scale(1.1);
            opacity: 0.9;
        }

        .modal-content {
            border: none;
            border-radius: 12px;
            box-shadow: var(--shadow);
        }

        .modal-header {
            background: var(--primary-color);
            color: white;
            border-radius: 12px 12px 0 0;
        }

        .form-control, .form-select {
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-family: 'Cairo', sans-serif;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(151, 126, 43, 0.25);
        }

        .loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .sortable-ghost {
            opacity: 0.4;
        }

        .drag-handle {
            cursor: move;
            color: #999;
            margin-left: 0.5rem;
        }

        .count-badge {
            background: var(--primary-color);
            color: white;
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .debug-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 1rem;
            margin: 1rem 0;
            font-size: 0.85rem;
            font-family: monospace;
        }

        @media (max-width: 768px) {
            .content-area {
                padding: 1rem;
            }
            
            .item-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Loader -->
    <div class="loader" id="loader">
        <div class="spinner-border text-primary" role="status" style="color: var(--primary-color) !important;">
            <span class="visually-hidden">جاري التحميل...</span>
        </div>
    </div>

    <div class="main-container">
        <!-- Header -->
        <div class="header">
            <h4 class="mb-0">
                <i class="fas fa-cogs me-2"></i>
                إدارة المحتوى
            </h4>
        </div>

        <!-- Breadcrumb -->
        <nav class="breadcrumb">
            <ol class="breadcrumb mb-0" id="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="#" onclick="showMainSections()">
                        <i class="fas fa-home"></i> الرئيسية
                    </a>
                </li>
            </ol>
        </nav>

        <!-- Content Area -->
        <div class="content-area">
            <div id="main-sections">
                <!-- الأقسام الرئيسية -->
                <div class="row">
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="section-card" onclick="showLocationSections()">
                            <div class="section-header">
                                <div>
                                    <i class="fas fa-map-marker-alt me-2"></i>
                                    <strong>المواقع</strong>
                                </div>
                                <span class="count-badge" id="locations-count">0</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="section-card" onclick="showProductSections()">
                            <div class="section-header">
                                <div>
                                    <i class="fas fa-cube me-2"></i>
                                    <strong>أقسام المنتجات</strong>
                                </div>
                                <span class="count-badge" id="sections-count">0</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="section-card" onclick="showFiles()">
                            <div class="section-header">
                                <div>
                                    <i class="fas fa-folder me-2"></i>
                                    <strong>الملفات</strong>
                                </div>
                                <span class="count-badge" id="files-count">0</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Display Area -->
            <div id="content-display" style="display: none;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 id="section-title"></h5>
                    <button class="btn btn-success" onclick="showAddModal()">
                        <i class="fas fa-plus me-1"></i> إضافة جديد
                    </button>
                </div>
                
                <div id="items-container"></div>
            </div>

            <!-- Debug Info -->
            <div id="debug-info" class="debug-info" style="display: none;"></div>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div class="modal fade" id="itemModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">إضافة عنصر جديد</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="itemForm">
                        <input type="hidden" id="itemId">
                        <input type="hidden" id="currentTable">
                        <input type="hidden" id="currentParent">
                        
                        <div class="mb-3" id="sectionSelectContainer" style="display: none;">
                            <label class="form-label">القسم</label>
                            <select class="form-select" id="sectionSelect" required>
                                <option value="">اختر القسم</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">الاسم</label>
                            <input type="text" class="form-control" id="itemName" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">الصورة</label>
                            <input type="url" class="form-control" id="itemImage" placeholder="رابط الصورة">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" class="btn btn-primary" onclick="saveItem()">حفظ</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // المتغيرات العامة
        let currentContext = null;
        let allData = {};
        const tables = <?php echo json_encode($config['tables']); ?>;

        // إعداد Axios مع معالجة أفضل للأخطاء
        axios.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded';
        axios.interceptors.response.use(
            response => response,
            error => {
                console.error('Axios error:', error);
                debugLog('خطأ في الشبكة: ' + error.message);
                return Promise.reject(error);
            }
        );

        // وظيفة التشخيص
        function debugLog(message, data = null) {
            const debugDiv = document.getElementById('debug-info');
            const time = new Date().toLocaleTimeString('ar-SA');
            let logMessage = `[${time}] ${message}`;
            
            if (data) {
                logMessage += '\n' + JSON.stringify(data, null, 2);
            }
            
            debugDiv.innerHTML = logMessage + '\n\n' + debugDiv.innerHTML;
            debugDiv.style.display = 'block';
            
            console.log(message, data);
        }

        // تحميل البيانات
        async function loadData() {
            showLoader();
            debugLog('بدء تحميل البيانات...');
            
            try {
                allData = {}; // إعادة تعيين البيانات
                
                for (const [key, tableId] of Object.entries(tables)) {
                    debugLog(`تحميل جدول: ${key} (${tableId})`);
                    
                    const formData = new FormData();
                    formData.append('action', 'get_data');
                    formData.append('table_id', tableId);
                    
                    const response = await axios.post('', formData);
                    
                    if (response.data.success) {
                        allData[key] = response.data.data || [];
                        debugLog(`تم تحميل ${allData[key].length} عنصر من ${key}`);
                    } else {
                        debugLog(`فشل في تحميل ${key}:`, response.data);
                        allData[key] = [];
                    }
                }
                
                debugLog('تم تحميل جميع البيانات:', allData);
                updateCounts();
                
            } catch (error) {
                debugLog('خطأ في تحميل البيانات:', error);
                alert('فشل في تحميل البيانات. تحقق من الكونسول للتفاصيل.');
            } finally {
                hideLoader();
            }
        }

        // تحديث العدادات
        function updateCounts() {
            try {
                // عدد المواقع الفريدة (مع تجاهل القيم الفارغة)
                const locations = [...new Set(
                    allData.main?.filter(item => 
                        item.الموقع && 
                        item.الموقع.trim() && 
                        item.الموقع !== 'null' &&
                        !item.محذوف
                    ).map(item => item.الموقع)
                )] || [];
                
                debugLog(`المواقع الفريدة: ${locations.length}`, locations);
                document.getElementById('locations-count').textContent = locations.length;
                
                const sectionsCount = allData.sections?.filter(s => !s.محذوف).length || 0;
                const filesCount = allData.files?.filter(f => !f.محذوف).length || 0;
                
                document.getElementById('sections-count').textContent = sectionsCount;
                document.getElementById('files-count').textContent = filesCount;
                
                debugLog(`الإحصائيات - المواقع: ${locations.length}, الأقسام: ${sectionsCount}, الملفات: ${filesCount}`);
            } catch (error) {
                debugLog('خطأ في تحديث العدادات:', error);
            }
        }

        // عرض الأقسام الرئيسية
        function showMainSections() {
            document.getElementById('main-sections').style.display = 'block';
            document.getElementById('content-display').style.display = 'none';
            updateBreadcrumb([{text: 'الرئيسية', onclick: 'showMainSections()'}]);
            currentContext = null;
            debugLog('عرض الأقسام الرئيسية');
        }

        // عرض أقسام المواقع
        function showLocationSections() {
            try {
                const locations = [...new Set(
                    allData.main?.filter(item => 
                        item.الموقع && 
                        item.الموقع.trim() && 
                        item.الموقع !== 'null' &&
                        !item.محذوف
                    ).map(item => item.الموقع)
                )] || [];
                
                debugLog('عرض أقسام المواقع:', locations);
                
                document.getElementById('main-sections').style.display = 'none';
                document.getElementById('content-display').style.display = 'block';
                document.getElementById('section-title').innerHTML = '<i class="fas fa-map-marker-alt me-2"></i>المواقع';
                
                updateBreadcrumb([
                    {text: 'الرئيسية', onclick: 'showMainSections()'},
                    {text: 'المواقع', onclick: 'showLocationSections()'}
                ]);

                let html = '';
                if (locations.length === 0) {
                    html = '<div class="empty-state"><i class="fas fa-map-marker-alt"></i><p>لا توجد مواقع متاحة</p></div>';
                } else {
                    locations.forEach(location => {
                        const count = allData.main.filter(item => 
                            item.الموقع === location && !item.محذوف
                        ).length;
                        
                        html += `
                            <div class="section-card" onclick="showLocationItems('${location}')">
                                <div class="section-header">
                                    <div>
                                        <i class="fas fa-map-pin me-2"></i>
                                        <strong>${location}</strong>
                                    </div>
                                    <span class="count-badge">${count}</span>
                                </div>
                            </div>
                        `;
                    });
                }
                
                document.getElementById('items-container').innerHTML = html;
                currentContext = {type: 'locations'};
            } catch (error) {
                debugLog('خطأ في عرض أقسام المواقع:', error);
            }
        }

        // عرض أقسام المنتجات
        function showProductSections() {
            try {
                const sections = allData.sections?.filter(s => !s.محذوف) || [];
                debugLog('عرض أقسام المنتجات:', sections);
                
                document.getElementById('main-sections').style.display = 'none';
                document.getElementById('content-display').style.display = 'block';
                document.getElementById('section-title').innerHTML = '<i class="fas fa-cube me-2"></i>أقسام المنتجات';
                
                updateBreadcrumb([
                    {text: 'الرئيسية', onclick: 'showMainSections()'},
                    {text: 'أقسام المنتجات', onclick: 'showProductSections()'}
                ]);

                let html = '';
                if (sections.length === 0) {
                    html = '<div class="empty-state"><i class="fas fa-cube"></i><p>لا توجد أقسام متاحة</p></div>';
                } else {
                    sections.forEach(section => {
                        const count = allData.products?.filter(p => 
                            p.معرف_القسم === section.معرف_القسم && !p.محذوف
                        ).length || 0;
                        
                        html += createSectionCard(section, 'sections', count, `showSectionProducts(${section.معرف_القسم}, '${section.الاسم}')`);
                    });
                }
                
                document.getElementById('items-container').innerHTML = html;
                currentContext = {type: 'sections', table: 'sections'};
            } catch (error) {
                debugLog('خطأ في عرض أقسام المنتجات:', error);
            }
        }

        // عرض الملفات
        function showFiles() {
            try {
                const files = allData.files?.filter(f => !f.محذوف) || [];
                debugLog('عرض الملفات:', files);
                
                document.getElementById('main-sections').style.display = 'none';
                document.getElementById('content-display').style.display = 'block';
                document.getElementById('section-title').innerHTML = '<i class="fas fa-folder me-2"></i>الملفات';
                
                updateBreadcrumb([
                    {text: 'الرئيسية', onclick: 'showMainSections()'},
                    {text: 'الملفات', onclick: 'showFiles()'}
                ]);

                let html = '';
                if (files.length === 0) {
                    html = '<div class="empty-state"><i class="fas fa-folder"></i><p>لا توجد ملفات متاحة</p></div>';
                } else {
                    files.forEach(file => {
                        const count = allData.main?.filter(item => 
                            item['معرف الملف'] == file.المعرف && !item.محذوف
                        ).length || 0;
                        
                        html += createSectionCard(file, 'files', count, `showFileItems(${file.المعرف}, '${file.الملف}')`);
                    });
                }
                
                document.getElementById('items-container').innerHTML = html;
                currentContext = {type: 'files', table: 'files'};
            } catch (error) {
                debugLog('خطأ في عرض الملفات:', error);
            }
        }

        // إنشاء بطاقة قسم
        function createSectionCard(item, table, count, onclick) {
            const itemName = item.الاسم || item.الملف || 'بدون اسم';
            const hasImage = item.الصورة && item.الصورة.trim();
            
            return `
                <div class="section-card" onclick="${onclick}">
                    <div class="section-header">
                        <div class="d-flex align-items-center">
                            ${hasImage ? `<img src="${item.الصورة}" class="item-image me-2" alt="${itemName}" onerror="this.style.display='none'">` : ''}
                            <strong>${itemName}</strong>
                        </div>
                        <span class="count-badge">${count}</span>
                    </div>
                </div>
            `;
        }

        // عرض عناصر موقع معين
        function showLocationItems(location) {
            try {
                const items = allData.main?.filter(item => 
                    item.الموقع === location && !item.محذوف
                ) || [];
                
                debugLog(`عرض عناصر الموقع ${location}:`, items);
                
                document.getElementById('section-title').innerHTML = `<i class="fas fa-map-pin me-2"></i>${location}`;
                updateBreadcrumb([
                    {text: 'الرئيسية', onclick: 'showMainSections()'},
                    {text: 'المواقع', onclick: 'showLocationSections()'},
                    {text: location, onclick: `showLocationItems('${location}')`}
                ]);

                let html = '';
                if (items.length === 0) {
                    html = '<div class="empty-state"><i class="fas fa-inbox"></i><p>لا توجد عناصر في هذا الموقع</p></div>';
                } else {
                    items.forEach(item => {
                        html += createItemRow(item, 'main');
                    });
                }
                
                document.getElementById('items-container').innerHTML = html;
                currentContext = {type: 'location-items', table: 'main', parent: location};
                initSortable();
            } catch (error) {
                debugLog('خطأ في عرض عناصر الموقع:', error);
            }
        }

        // عرض منتجات قسم معين
        function showSectionProducts(sectionId, sectionName) {
            try {
                const products = allData.products?.filter(p => 
                    p.معرف_القسم == sectionId && !p.محذوف
                ) || [];
                
                debugLog(`عرض منتجات القسم ${sectionName}:`, products);
                
                document.getElementById('section-title').innerHTML = `<i class="fas fa-cube me-2"></i>منتجات ${sectionName}`;
                updateBreadcrumb([
                    {text: 'الرئيسية', onclick: 'showMainSections()'},
                    {text: 'أقسام المنتجات', onclick: 'showProductSections()'},
                    {text: sectionName, onclick: `showSectionProducts(${sectionId}, '${sectionName}')`}
                ]);

                let html = '';
                if (products.length === 0) {
                    html = '<div class="empty-state"><i class="fas fa-box"></i><p>لا توجد منتجات في هذا القسم</p></div>';
                } else {
                    products.forEach(product => {
                        html += createItemRow(product, 'products');
                    });
                }
                
                document.getElementById('items-container').innerHTML = html;
                currentContext = {type: 'section-products', table: 'products', parent: sectionId};
                initSortable();
            } catch (error) {
                debugLog('خطأ في عرض منتجات القسم:', error);
            }
        }

        // عرض محتويات ملف معين
        function showFileItems(fileId, fileName) {
            try {
                const items = allData.main?.filter(item => 
                    item['معرف الملف'] == fileId && !item.محذوف
                ) || [];
                
                debugLog(`عرض محتويات الملف ${fileName}:`, items);
                
                document.getElementById('section-title').innerHTML = `<i class="fas fa-folder me-2"></i>محتويات ${fileName}`;
                updateBreadcrumb([
                    {text: 'الرئيسية', onclick: 'showMainSections()'},
                    {text: 'الملفات', onclick: 'showFiles()'},
                    {text: fileName, onclick: `showFileItems(${fileId}, '${fileName}')`}
                ]);

                let html = '';
                if (items.length === 0) {
                    html = '<div class="empty-state"><i class="fas fa-file"></i><p>لا توجد عناصر في هذا الملف</p></div>';
                } else {
                    items.forEach(item => {
                        html += createItemRow(item, 'main');
                    });
                }
                
                document.getElementById('items-container').innerHTML = html;
                currentContext = {type: 'file-items', table: 'main', parent: fileId};
                initSortable();
            } catch (error) {
                debugLog('خطأ في عرض محتويات الملف:', error);
            }
        }

        // إنشاء صف عنصر
        function createItemRow(item, table) {
            const itemName = item.الاسم || item.الملف || 'بدون اسم';
            const hasImage = item.الصورة && item.الصورة.trim();
            const itemId = item.Id || item.المعرف || item.معرف_القسم;
            
            const imageHtml = hasImage ? 
                `<img src="${item.الصورة}" class="item-image" alt="${itemName}" onerror="this.style.display='none'">` : 
                '<div class="item-image bg-light d-flex align-items-center justify-content-center"><i class="fas fa-image text-muted"></i></div>';

            return `
                <div class="item-row" data-id="${itemId}">
                    <i class="fas fa-grip-vertical drag-handle"></i>
                    ${imageHtml}
                    <div class="item-title">${itemName}</div>
                    <div class="item-actions">
                        <button class="btn-icon btn-primary" onclick="editItem(${itemId}, '${table}')" title="تعديل">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-icon btn-danger" onclick="deleteItem(${itemId}, '${table}')" title="حذف">
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
        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'إضافة عنصر جديد';
            document.getElementById('itemForm').reset();
            document.getElementById('itemId').value = '';
            
            setupModalForContext();
            
            new bootstrap.Modal(document.getElementById('itemModal')).show();
        }

        // تعديل عنصر
        function editItem(id, table) {
            try {
                const item = findItemById(id, table);
                if (!item) {
                    debugLog(`لم يتم العثور على العنصر: ${id} في الجدول ${table}`);
                    return;
                }
                
                debugLog('تعديل العنصر:', item);
                
                document.getElementById('modalTitle').textContent = 'تعديل العنصر';
                document.getElementById('itemId').value = id;
                document.getElementById('currentTable').value = table;
                document.getElementById('itemName').value = item.الاسم || item.الملف || '';
                document.getElementById('itemImage').value = item.الصورة || '';
                
                setupModalForContext(false);
                
                new bootstrap.Modal(document.getElementById('itemModal')).show();
            } catch (error) {
                debugLog('خطأ في تعديل العنصر:', error);
            }
        }

        // حذف عنصر (حذف ناعم)
        async function deleteItem(id, table) {
            if (!confirm('هل تريد حذف هذا العنصر؟')) return;
            
            showLoader();
            debugLog(`محاولة حذف العنصر ${id} من الجدول ${table}`);
            
            try {
                const formData = new FormData();
                formData.append('action', 'soft_delete');
                formData.append('table_id', tables[table]);
                formData.append('record_id', id);
                
                const response = await axios.post('', formData);
                
                if (response.data.success) {
                    debugLog('تم حذف العنصر بنجاح');
                    await loadData();
                    refreshCurrentView();
                } else {
                    debugLog('فشل في حذف العنصر:', response.data);
                    alert('فشل في حذف العنصر');
                }
            } catch (error) {
                debugLog('خطأ في الحذف:', error);
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
            debugLog('محاولة حفظ العنصر...');
            
            try {
                const formData = new FormData();
                const isEdit = document.getElementById('itemId').value;
                
                formData.append('action', isEdit ? 'update_record' : 'add_record');
                formData.append('table_id', tables[currentContext.table]);
                formData.append('name', document.getElementById('itemName').value);
                formData.append('image', document.getElementById('itemImage').value);
                
                if (isEdit) {
                    formData.append('record_id', document.getElementById('itemId').value);
                    debugLog('تحديث العنصر:', {
                        id: document.getElementById('itemId').value,
                        name: document.getElementById('itemName').value
                    });
                } else {
                    // إضافة معلومات السياق للعناصر الجديدة
                    if (currentContext.type === 'location-items') {
                        formData.append('location', currentContext.parent);
                    } else if (currentContext.type === 'section-products') {
                        formData.append('section_id', currentContext.parent);
                    } else if (currentContext.type === 'file-items') {
                        formData.append('file_id', currentContext.parent);
                    }
                    
                    debugLog('إضافة عنصر جديد:', {
                        name: document.getElementById('itemName').value,
                        context: currentContext
                    });
                }
                
                const response = await axios.post('', formData);
                
                if (response.data.success) {
                    debugLog('تم حفظ العنصر بنجاح');
                    bootstrap.Modal.getInstance(document.getElementById('itemModal')).hide();
                    await loadData();
                    refreshCurrentView();
                } else {
                    debugLog('فشل في حفظ العنصر:', response.data);
                    alert('فشل في حفظ العنصر');
                }
            } catch (error) {
                debugLog('خطأ في الحفظ:', error);
                alert('حدث خطأ أثناء الحفظ');
            } finally {
                hideLoader();
            }
        }

        // إعداد modal حسب السياق
        function setupModalForContext(isAdd = true) {
            const sectionSelectContainer = document.getElementById('sectionSelectContainer');
            sectionSelectContainer.style.display = 'none';
            
            document.getElementById('currentTable').value = currentContext?.table || '';
            document.getElementById('currentParent').value = currentContext?.parent || '';
        }

        // البحث عن عنصر بالـ ID
        function findItemById(id, table) {
            try {
                return allData[table]?.find(item => 
                    (item.Id || item.المعرف || item.معرف_القسم) == id
                );
            } catch (error) {
                debugLog('خطأ في البحث عن العنصر:', error);
                return null;
            }
        }

        // تحديث العرض الحالي
        function refreshCurrentView() {
            if (!currentContext) {
                showMainSections();
                return;
            }
            
            try {
                switch (currentContext.type) {
                    case 'locations':
                        showLocationSections();
                        break;
                    case 'sections':
                        showProductSections();
                        break;
                    case 'files':
                        showFiles();
                        break;
                    case 'location-items':
                        showLocationItems(currentContext.parent);
                        break;
                    case 'section-products':
                        const section = findItemById(currentContext.parent, 'sections');
                        showSectionProducts(currentContext.parent, section?.الاسم || '');
                        break;
                    case 'file-items':
                        const file = findItemById(currentContext.parent, 'files');
                        showFileItems(currentContext.parent, file?.الملف || '');
                        break;
                }
            } catch (error) {
                debugLog('خطأ في تحديث العرض:', error);
            }
        }

        // تهيئة السحب والإفلات للترتيب
        function initSortable() {
            const container = document.getElementById('items-container');
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
            const items = document.querySelectorAll('#items-container .item-row');
            const records = Array.from(items).map(item => ({
                id: item.dataset.id
            }));
            
            showLoader();
            debugLog('تحديث الترتيب:', records);
            
            try {
                const formData = new FormData();
                formData.append('action', 'update_order');
                formData.append('table_id', tables[currentContext.table]);
                formData.append('records', JSON.stringify(records));
                
                const response = await axios.post('', formData);
                
                if (!response.data.success) {
                    debugLog('فشل في تحديث الترتيب');
                    alert('فشل في تحديث الترتيب');
                    refreshCurrentView();
                }
            } catch (error) {
                debugLog('خطأ في تحديث الترتيب:', error);
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
            debugLog('تهيئة الصفحة...');
            loadData();
        });

        // إخفاء معلومات التشخيص عند النقر
        document.getElementById('debug-info').addEventListener('click', function() {
            this.style.display = 'none';
        });
    </script>
</body>
</html>