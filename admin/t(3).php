<?php
// إعدادات قاعدة البيانات والأمان
define('API_BASE', 'https://app.nocodb.com/api/v2/tables');
define('API_TOKEN', 'fwVaKHr6zbDns5iW9u8annJUf5LCBJXjqPfujIpV');

// معرفات الجداول
define('TABLE_FILES', 'mjzc3fv727to95i');
define('TABLE_SECTIONS', 'm1g39mqv5mtdwad');
define('TABLE_PRODUCTS', 'm4twrspf9oj7rvi');
define('TABLE_MAIN', 'ma95crsjyfik3ce');

class ContentManager {
    
    private function apiRequest($tableId, $method = 'GET', $data = null, $recordId = null) {
        $url = API_BASE . '/' . $tableId . '/records';
        if ($recordId) $url .= '/' . $recordId;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'xc-token: ' . API_TOKEN,
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
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    public function getTableData($tableId) {
        $result = $this->apiRequest($tableId);
        return $result['list'] ?? [];
    }
    
    public function updateRecord($tableId, $recordId, $data) {
        return $this->apiRequest($tableId, 'PATCH', [$data], $recordId);
    }
    
    public function createRecord($tableId, $data) {
        return $this->apiRequest($tableId, 'POST', [$data]);
    }
    
    public function deleteRecord($tableId, $recordId) {
        // الحذف المخفي - تعديل حقل "محذوف" إلى 1
        return $this->updateRecord($tableId, $recordId, ['محذوف' => 1]);
    }
    
    public function getSections() {
        $files = $this->getTableData(TABLE_FILES);
        $productSections = $this->getTableData(TABLE_SECTIONS);
        $mainData = $this->getTableData(TABLE_MAIN);
        
        $sections = [];
        
        // أقسام الملفات
        foreach ($files as $file) {
            $count = count(array_filter($mainData, function($item) use ($file) {
                return $item['معرف الملف'] == $file['المعرف'];
            }));
            
            $sections[] = [
                'id' => 'file_' . $file['المعرف'],
                'name' => $file['الملف'],
                'type' => 'file',
                'table_id' => TABLE_MAIN,
                'count' => $count,
                'filter' => ['معرف الملف' => $file['المعرف']]
            ];
        }
        
        // أقسام المنتجات
        foreach ($productSections as $section) {
            $products = $this->getTableData(TABLE_PRODUCTS);
            $count = count(array_filter($products, function($item) use ($section) {
                return $item['معرف_القسم'] == $section['معرف_القسم'];
            }));
            
            $sections[] = [
                'id' => 'product_' . $section['معرف_القسم'],
                'name' => $section['الاسم'],
                'type' => 'product',
                'table_id' => TABLE_PRODUCTS,
                'count' => $count,
                'filter' => ['معرف_القسم' => $section['معرف_القسم']]
            ];
        }
        
        // المواقع الثابتة
        $locations = [];
        foreach ($mainData as $item) {
            if (!empty($item['الموقع'])) {
                $locations[$item['الموقع']] = ($locations[$item['الموقع']] ?? 0) + 1;
            }
        }
        
        foreach ($locations as $location => $count) {
            $sections[] = [
                'id' => 'location_' . md5($location),
                'name' => $location,
                'type' => 'location',
                'table_id' => TABLE_MAIN,
                'count' => $count,
                'filter' => ['الموقع' => $location]
            ];
        }
        
        return $sections;
    }
    
    public function getSectionItems($sectionId) {
        list($type, $id) = explode('_', $sectionId, 2);
        
        switch ($type) {
            case 'file':
                $data = $this->getTableData(TABLE_MAIN);
                return array_filter($data, function($item) use ($id) {
                    return $item['معرف الملف'] == $id && ($item['محذوف'] ?? 0) != 1;
                });
                
            case 'product':
                $data = $this->getTableData(TABLE_PRODUCTS);
                return array_filter($data, function($item) use ($id) {
                    return $item['معرف_القسم'] == $id && ($item['محذوف'] ?? 0) != 1;
                });
                
            case 'location':
                $data = $this->getTableData(TABLE_MAIN);
                $sections = $this->getSections();
                $locationName = '';
                foreach ($sections as $section) {
                    if ($section['id'] == 'location_' . $id) {
                        $locationName = $section['name'];
                        break;
                    }
                }
                return array_filter($data, function($item) use ($locationName) {
                    return $item['الموقع'] == $locationName && ($item['محذوف'] ?? 0) != 1;
                });
        }
        
        return [];
    }
}

$manager = new ContentManager();

// معالجة الطلبات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    $response = ['success' => false];
    
    switch ($action) {
        case 'get_sections':
            $response = ['success' => true, 'data' => $manager->getSections()];
            break;
            
        case 'get_items':
            $sectionId = $_POST['section_id'] ?? '';
            $items = $manager->getSectionItems($sectionId);
            $response = ['success' => true, 'data' => array_values($items)];
            break;
            
        case 'save_item':
            $tableId = $_POST['table_id'] ?? '';
            $recordId = $_POST['record_id'] ?? '';
            $name = $_POST['name'] ?? '';
            $image = $_POST['image'] ?? '';
            
            $data = ['الاسم' => $name];
            if (!empty($image)) $data['الصورة'] = $image;
            
            if ($recordId) {
                $result = $manager->updateRecord($tableId, $recordId, $data);
            } else {
                // إضافة جديدة - يحتاج معلومات إضافية حسب القسم
                $sectionId = $_POST['section_id'] ?? '';
                list($type, $id) = explode('_', $sectionId, 2);
                
                if ($type === 'file') {
                    $data['معرف الملف'] = $id;
                    $data['الموقع'] = 'الملفات';
                } elseif ($type === 'product') {
                    $data['معرف_القسم'] = $id;
                } elseif ($type === 'location') {
                    $sections = $manager->getSections();
                    foreach ($sections as $section) {
                        if ($section['id'] == $sectionId) {
                            $data['الموقع'] = $section['name'];
                            break;
                        }
                    }
                }
                
                $result = $manager->createRecord($tableId, $data);
            }
            
            $response = ['success' => true, 'data' => $result];
            break;
            
        case 'delete_item':
            $tableId = $_POST['table_id'] ?? '';
            $recordId = $_POST['record_id'] ?? '';
            $result = $manager->deleteRecord($tableId, $recordId);
            $response = ['success' => true, 'data' => $result];
            break;
    }
    
    echo json_encode($response);
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
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        * { font-family: 'Cairo', sans-serif; }
        
        :root {
            --primary: #977e2b;
            --dark: #212529;
            --light: #f8f9fa;
        }
        
        body { background: var(--light); }
        .btn-primary { background: var(--primary); border-color: var(--primary); }
        .btn-primary:hover { background: #7a6522; border-color: #7a6522; }
        .text-primary { color: var(--primary) !important; }
        .border-primary { border-color: var(--primary) !important; }
        
        .card { border: none; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .section-item { cursor: pointer; transition: all 0.2s; }
        .section-item:hover { background: var(--primary); color: white; }
        
        .loader { 
            display: none; 
            position: fixed; 
            top: 0; left: 0; right: 0; bottom: 0; 
            background: rgba(255,255,255,0.9); 
            z-index: 9999; 
        }
        
        .item-image { width: 50px; height: 50px; object-fit: cover; border-radius: 5px; }
        .breadcrumb { background: none; padding: 0; }
        .breadcrumb-item + .breadcrumb-item::before { content: "←"; }
    </style>
</head>
<body>
    <!-- Loader -->
    <div class="loader d-flex justify-content-center align-items-center">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;"></div>
    </div>

    <div class="container-fluid py-4">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb" id="breadcrumb">
                <li class="breadcrumb-item"><a href="#" onclick="showSections()">الأقسام</a></li>
            </ol>
        </nav>

        <!-- Sections View -->
        <div id="sectionsView">
            <div class="row g-3" id="sectionsList"></div>
        </div>

        <!-- Items View -->
        <div id="itemsView" style="display: none;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 id="sectionTitle"></h4>
                <button class="btn btn-primary" onclick="showAddForm()">
                    <i class="fas fa-plus"></i> إضافة جديد
                </button>
            </div>
            
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="60">الصورة</th>
                                    <th>الاسم</th>
                                    <th width="100">الترتيب</th>
                                    <th width="150">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody id="itemsList"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div class="modal fade" id="itemModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">إضافة عنصر</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="itemForm">
                        <input type="hidden" id="recordId">
                        <input type="hidden" id="tableId">
                        <input type="hidden" id="sectionId">
                        
                        <div class="mb-3">
                            <label class="form-label">الاسم</label>
                            <input type="text" class="form-control" id="itemName" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">الصورة (رابط)</label>
                            <input type="url" class="form-control" id="itemImage" placeholder="https://...">
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
        let currentSection = null;
        let currentItems = [];
        
        // تحميل الأقسام
        async function loadSections() {
            showLoader();
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=get_sections'
                });
                const data = await response.json();
                
                if (data.success) {
                    displaySections(data.data);
                }
            } catch (error) {
                console.error('خطأ في تحميل الأقسام:', error);
            }
            hideLoader();
        }
        
        // عرض الأقسام
        function displaySections(sections) {
            const container = document.getElementById('sectionsList');
            container.innerHTML = '';
            
            sections.forEach(section => {
                const col = document.createElement('div');
                col.className = 'col-md-4 col-lg-3';
                col.innerHTML = `
                    <div class="card section-item h-100" onclick="showSection('${section.id}', '${section.name}', '${section.table_id}')">
                        <div class="card-body text-center">
                            <i class="fas fa-${getIconForType(section.type)} fa-2x text-primary mb-3"></i>
                            <h6 class="card-title">${section.name}</h6>
                            <small class="text-muted">${section.count} عنصر</small>
                        </div>
                    </div>
                `;
                container.appendChild(col);
            });
        }
        
        function getIconForType(type) {
            switch(type) {
                case 'file': return 'file-alt';
                case 'product': return 'cube';
                case 'location': return 'map-marker-alt';
                default: return 'folder';
            }
        }
        
        // عرض قسم معين
        async function showSection(sectionId, sectionName, tableId) {
            currentSection = {id: sectionId, name: sectionName, tableId: tableId};
            
            showLoader();
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=get_items&section_id=${sectionId}`
                });
                const data = await response.json();
                
                if (data.success) {
                    currentItems = data.data;
                    displayItems();
                    
                    // تحديث العرض
                    document.getElementById('sectionsView').style.display = 'none';
                    document.getElementById('itemsView').style.display = 'block';
                    document.getElementById('sectionTitle').textContent = sectionName;
                    
                    // تحديث breadcrumb
                    document.getElementById('breadcrumb').innerHTML = `
                        <li class="breadcrumb-item"><a href="#" onclick="showSections()">الأقسام</a></li>
                        <li class="breadcrumb-item active">${sectionName}</li>
                    `;
                }
            } catch (error) {
                console.error('خطأ في تحميل العناصر:', error);
            }
            hideLoader();
        }
        
        // عرض العناصر
        function displayItems() {
            const tbody = document.getElementById('itemsList');
            tbody.innerHTML = '';
            
            currentItems.forEach((item, index) => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>
                        ${item.الصورة ? `<img src="${item.الصورة}" class="item-image" alt="">` : '<i class="fas fa-image text-muted fa-2x"></i>'}
                    </td>
                    <td>${item.الاسم || item.اسم || 'بدون اسم'}</td>
                    <td>
                        <div class="btn-group-vertical btn-group-sm">
                            <button class="btn btn-outline-secondary btn-sm" onclick="moveItem(${index}, 'up')" ${index === 0 ? 'disabled' : ''}>
                                <i class="fas fa-arrow-up"></i>
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" onclick="moveItem(${index}, 'down')" ${index === currentItems.length - 1 ? 'disabled' : ''}>
                                <i class="fas fa-arrow-down"></i>
                            </button>
                        </div>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" onclick="editItem(${index})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-outline-danger" onclick="deleteItem(${index})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }
        
        // عرض الأقسام
        function showSections() {
            document.getElementById('sectionsView').style.display = 'block';
            document.getElementById('itemsView').style.display = 'none';
            document.getElementById('breadcrumb').innerHTML = '<li class="breadcrumb-item active">الأقسام</li>';
            currentSection = null;
        }
        
        // إضافة عنصر جديد
        function showAddForm() {
            document.getElementById('modalTitle').textContent = 'إضافة جديد';
            document.getElementById('recordId').value = '';
            document.getElementById('tableId').value = currentSection.tableId;
            document.getElementById('sectionId').value = currentSection.id;
            document.getElementById('itemName').value = '';
            document.getElementById('itemImage').value = '';
            
            new bootstrap.Modal(document.getElementById('itemModal')).show();
        }
        
        // تحرير عنصر
        function editItem(index) {
            const item = currentItems[index];
            document.getElementById('modalTitle').textContent = 'تحرير العنصر';
            document.getElementById('recordId').value = item.Id || item.id;
            document.getElementById('tableId').value = currentSection.tableId;
            document.getElementById('sectionId').value = currentSection.id;
            document.getElementById('itemName').value = item.الاسم || item.اسم || '';
            document.getElementById('itemImage').value = item.الصورة || '';
            
            new bootstrap.Modal(document.getElementById('itemModal')).show();
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
                formData.append('action', 'save_item');
                formData.append('record_id', document.getElementById('recordId').value);
                formData.append('table_id', document.getElementById('tableId').value);
                formData.append('section_id', document.getElementById('sectionId').value);
                formData.append('name', document.getElementById('itemName').value);
                formData.append('image', document.getElementById('itemImage').value);
                
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('itemModal')).hide();
                    showSection(currentSection.id, currentSection.name, currentSection.tableId);
                }
            } catch (error) {
                console.error('خطأ في حفظ العنصر:', error);
            }
            hideLoader();
        }
        
        // حذف عنصر
        async function deleteItem(index) {
            if (!confirm('هل أنت متأكد من الحذف؟')) return;
            
            const item = currentItems[index];
            showLoader();
            
            try {
                const formData = new FormData();
                formData.append('action', 'delete_item');
                formData.append('record_id', item.Id || item.id);
                formData.append('table_id', currentSection.tableId);
                
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                
                if (data.success) {
                    showSection(currentSection.id, currentSection.name, currentSection.tableId);
                }
            } catch (error) {
                console.error('خطأ في الحذف:', error);
            }
            hideLoader();
        }
        
        // تحريك العناصر (للترتيب)
        function moveItem(index, direction) {
            // سيتم تنفيذ هذا لاحقاً مع تحديث ترتيب قاعدة البيانات
            console.log(`Move item ${index} ${direction}`);
        }
        
        function showLoader() {
            document.querySelector('.loader').style.display = 'flex';
        }
        
        function hideLoader() {
            document.querySelector('.loader').style.display = 'none';
        }
        
        // بدء التطبيق
        document.addEventListener('DOMContentLoaded', function() {
            loadSections();
        });
    </script>
</body>
</html>