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

// وظائف API
function makeApiCall($endpoint, $method = 'GET', $data = null) {
    global $config;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $config['api_base'] . '/' . $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'xc-token: ' . $config['api_token'],
        'Content-Type: application/json'
    ]);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($method === 'PATCH') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        return json_decode($response, true);
    }
    
    return false;
}

// معالجة طلبات AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'get_data':
            $tableId = $_POST['table_id'];
            $result = makeApiCall($tableId . '/records');
            echo json_encode($result);
            break;
            
        case 'update_record':
            $tableId = $_POST['table_id'];
            $recordId = $_POST['record_id'];
            $data = [
                'Id' => $recordId,
                'الاسم' => $_POST['name'],
                'الصورة' => $_POST['image']
            ];
            $result = makeApiCall($tableId . '/records', 'PATCH', [$data]);
            echo json_encode(['success' => $result !== false]);
            break;
            
        case 'add_record':
            $tableId = $_POST['table_id'];
            $data = [
                'الاسم' => $_POST['name'],
                'الصورة' => $_POST['image']
            ];
            
            // إضافة الحقول الخاصة حسب الجدول
            if (isset($_POST['section_id'])) {
                $data['معرف_القسم'] = $_POST['section_id'];
            }
            if (isset($_POST['file_id'])) {
                $data['معرف الملف'] = $_POST['file_id'];
            }
            if (isset($_POST['location'])) {
                $data['الموقع'] = $_POST['location'];
            }
            
            $result = makeApiCall($tableId . '/records', 'POST', [$data]);
            echo json_encode(['success' => $result !== false]);
            break;
            
        case 'soft_delete':
            $tableId = $_POST['table_id'];
            $recordId = $_POST['record_id'];
            $data = [
                'Id' => $recordId,
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
                    'Id' => $record['id'],
                    'ترتيب' => $index + 1
                ];
            }
            
            $result = makeApiCall($tableId . '/records', 'PATCH', $updates);
            echo json_encode(['success' => $result !== false]);
            break;
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
        }

        .section-card:hover {
            box-shadow: var(--shadow);
            transform: translateY(-2px);
        }

        .section-header {
            background: var(--bg-light);
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            cursor: pointer;
            display: flex;
            justify-content: between;
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

        // تحميل البيانات
        async function loadData() {
            showLoader();
            try {
                for (const [key, tableId] of Object.entries(tables)) {
                    const response = await axios.post('', {
                        action: 'get_data',
                        table_id: tableId
                    });
                    allData[key] = response.data.list || [];
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
            // عدد المواقع الفريدة
            const locations = [...new Set(allData.main?.filter(item => item.الموقع).map(item => item.الموقع))];
            document.getElementById('locations-count').textContent = locations.length;
            
            document.getElementById('sections-count').textContent = allData.sections?.length || 0;
            document.getElementById('files-count').textContent = allData.files?.length || 0;
        }

        // عرض الأقسام الرئيسية
        function showMainSections() {
            document.getElementById('main-sections').style.display = 'block';
            document.getElementById('content-display').style.display = 'none';
            updateBreadcrumb([{text: 'الرئيسية', onclick: 'showMainSections()'}]);
            currentContext = null;
        }

        // عرض أقسام المواقع
        function showLocationSections() {
            const locations = [...new Set(allData.main?.filter(item => item.الموقع).map(item => item.الموقع))];
            
            document.getElementById('main-sections').style.display = 'none';
            document.getElementById('content-display').style.display = 'block';
            document.getElementById('section-title').innerHTML = '<i class="fas fa-map-marker-alt me-2"></i>المواقع';
            
            updateBreadcrumb([
                {text: 'الرئيسية', onclick: 'showMainSections()'},
                {text: 'المواقع', onclick: 'showLocationSections()'}
            ]);

            let html = '';
            locations.forEach(location => {
                const count = allData.main.filter(item => item.الموقع === location && !item.محذوف).length;
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
            
            document.getElementById('items-container').innerHTML = html;
            currentContext = {type: 'locations'};
        }

        // عرض أقسام المنتجات
        function showProductSections() {
            document.getElementById('main-sections').style.display = 'none';
            document.getElementById('content-display').style.display = 'block';
            document.getElementById('section-title').innerHTML = '<i class="fas fa-cube me-2"></i>أقسام المنتجات';
            
            updateBreadcrumb([
                {text: 'الرئيسية', onclick: 'showMainSections()'},
                {text: 'أقسام المنتجات', onclick: 'showProductSections()'}
            ]);

            let html = '';
            allData.sections?.forEach(section => {
                if (!section.محذوف) {
                    const count = allData.products?.filter(p => p.معرف_القسم === section.معرف_القسم && !p.محذوف).length || 0;
                    html += createItemRow(section, 'sections', count);
                }
            });
            
            document.getElementById('items-container').innerHTML = html;
            currentContext = {type: 'sections', table: 'sections'};
            initSortable();
        }

        // عرض الملفات
        function showFiles() {
            document.getElementById('main-sections').style.display = 'none';
            document.getElementById('content-display').style.display = 'block';
            document.getElementById('section-title').innerHTML = '<i class="fas fa-folder me-2"></i>الملفات';
            
            updateBreadcrumb([
                {text: 'الرئيسية', onclick: 'showMainSections()'},
                {text: 'الملفات', onclick: 'showFiles()'}
            ]);

            let html = '';
            allData.files?.forEach(file => {
                if (!file.محذوف) {
                    const count = allData.main?.filter(item => item['معرف الملف'] == file.المعرف && !item.محذوف).length || 0;
                    html += createItemRow(file, 'files', count);
                }
            });
            
            document.getElementById('items-container').innerHTML = html;
            currentContext = {type: 'files', table: 'files'};
            initSortable();
        }

        // عرض عناصر موقع معين
        function showLocationItems(location) {
            const items = allData.main?.filter(item => item.الموقع === location && !item.محذوف) || [];
            
            document.getElementById('section-title').innerHTML = `<i class="fas fa-map-pin me-2"></i>${location}`;
            updateBreadcrumb([
                {text: 'الرئيسية', onclick: 'showMainSections()'},
                {text: 'المواقع', onclick: 'showLocationSections()'},
                {text: location, onclick: `showLocationItems('${location}')`}
            ]);

            let html = '';
            items.forEach(item => {
                html += createItemRow(item, 'main');
            });
            
            document.getElementById('items-container').innerHTML = html;
            currentContext = {type: 'location-items', table: 'main', parent: location};
            initSortable();
        }

        // عرض منتجات قسم معين
        function showSectionProducts(sectionId, sectionName) {
            const products = allData.products?.filter(p => p.معرف_القسم == sectionId && !p.محذوف) || [];
            
            document.getElementById('section-title').innerHTML = `<i class="fas fa-cube me-2"></i>منتجات ${sectionName}`;
            updateBreadcrumb([
                {text: 'الرئيسية', onclick: 'showMainSections()'},
                {text: 'أقسام المنتجات', onclick: 'showProductSections()'},
                {text: sectionName, onclick: `showSectionProducts(${sectionId}, '${sectionName}')`}
            ]);

            let html = '';
            products.forEach(product => {
                html += createItemRow(product, 'products');
            });
            
            document.getElementById('items-container').innerHTML = html;
            currentContext = {type: 'section-products', table: 'products', parent: sectionId};
            initSortable();
        }

        // عرض محتويات ملف معين
        function showFileItems(fileId, fileName) {
            const items = allData.main?.filter(item => item['معرف الملف'] == fileId && !item.محذوف) || [];
            
            document.getElementById('section-title').innerHTML = `<i class="fas fa-folder me-2"></i>محتويات ${fileName}`;
            updateBreadcrumb([
                {text: 'الرئيسية', onclick: 'showMainSections()'},
                {text: 'الملفات', onclick: 'showFiles()'},
                {text: fileName, onclick: `showFileItems(${fileId}, '${fileName}')`}
            ]);

            let html = '';
            items.forEach(item => {
                html += createItemRow(item, 'main');
            });
            
            document.getElementById('items-container').innerHTML = html;
            currentContext = {type: 'file-items', table: 'main', parent: fileId};
            initSortable();
        }

        // إنشاء صف عنصر
        function createItemRow(item, table, count = null) {
            const hasImage = item.الصورة && item.الصورة.trim();
            const itemId = item.Id || item.المعرف || item.معرف_القسم;
            
            let actions = '';
            if (count !== null) {
                // عنصر قابل للنقر
                const clickAction = table === 'sections' ? 
                    `onclick="showSectionProducts(${item.معرف_القسم}, '${item.الاسم}')"`
                    : `onclick="showFileItems(${item.المعرف}, '${item.الملف}')"`;
                
                return `
                    <div class="section-card" ${clickAction}>
                        <div class="section-header">
                            <div>
                                ${hasImage ? `<img src="${item.الصورة}" class="item-image me-2" alt="${item.الاسم || item.الملف}">` : ''}
                                <strong>${item.الاسم || item.الملف}</strong>
                            </div>
                            <span class="count-badge">${count}</span>
                        </div>
                    </div>
                `;
            } else {
                // عنصر عادي مع أزرار
                actions = `
                    <button class="btn-icon btn-primary" onclick="editItem(${itemId}, '${table}')" title="تعديل">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-icon btn-danger" onclick="deleteItem(${itemId}, '${table}')" title="حذف">
                        <i class="fas fa-trash"></i>
                    </button>
                `;
            }

            return `
                <div class="item-row" data-id="${itemId}">
                    <i class="fas fa-grip-vertical drag-handle"></i>
                    ${hasImage ? `<img src="${item.الصورة}" class="item-image" alt="${item.الاسم}">` : 
                        '<div class="item-image bg-light d-flex align-items-center justify-content-center"><i class="fas fa-image text-muted"></i></div>'}
                    <div class="item-title">${item.الاسم}</div>
                    <div class="item-actions">
                        ${actions}
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
            
            // إعداد الخيارات حسب السياق
            setupModalForContext();
            
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
            document.getElementById('itemImage').value = item.الصورة || '';
            
            setupModalForContext(false);
            
            new bootstrap.Modal(document.getElementById('itemModal')).show();
        }

        // حذف عنصر (حذف ناعم)
        async function deleteItem(id, table) {
            if (!confirm('هل تريد حذف هذا العنصر؟')) return;
            
            showLoader();
            try {
                const response = await axios.post('', {
                    action: 'soft_delete',
                    table_id: tables[table],
                    record_id: id
                });
                
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
                
                formData.append('action', isEdit ? 'update_record' : 'add_record');
                formData.append('table_id', tables[currentContext.table]);
                formData.append('name', document.getElementById('itemName').value);
                formData.append('image', document.getElementById('itemImage').value);
                
                if (isEdit) {
                    formData.append('record_id', document.getElementById('itemId').value);
                } else {
                    // إضافة معلومات السياق للعناصر الجديدة
                    if (currentContext.type === 'location-items') {
                        formData.append('location', currentContext.parent);
                    } else if (currentContext.type === 'section-products') {
                        formData.append('section_id', currentContext.parent);
                    } else if (currentContext.type === 'file-items') {
                        formData.append('file_id', currentContext.parent);
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

        // إعداد modal حسب السياق
        function setupModalForContext(isAdd = true) {
            const sectionSelectContainer = document.getElementById('sectionSelectContainer');
            
            if (isAdd && (currentContext.type === 'sections' || currentContext.type === 'files')) {
                // عرض خيارات الأقسام للإضافة
                sectionSelectContainer.style.display = 'block';
                // ملء الخيارات...
            } else {
                sectionSelectContainer.style.display = 'none';
            }
            
            document.getElementById('currentTable').value = currentContext.table;
            document.getElementById('currentParent').value = currentContext.parent || '';
        }

        // البحث عن عنصر بالـ ID
        function findItemById(id, table) {
            return allData[table]?.find(item => 
                (item.Id || item.المعرف || item.معرف_القسم) == id
            );
        }

        // تحديث العرض الحالي
        function refreshCurrentView() {
            if (!currentContext) {
                showMainSections();
                return;
            }
            
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
            try {
                const response = await axios.post('', {
                    action: 'update_order',
                    table_id: tables[currentContext.table],
                    records: records
                });
                
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