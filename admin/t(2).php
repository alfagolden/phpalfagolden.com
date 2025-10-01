<?php
// إعدادات قاعدة البيانات والAPI
$api_config = [
    'base_url' => 'https://app.nocodb.com/api/v2/tables',
    'token' => 'fwVaKHr6zbDns5iW9u8annJUf5LCBJXjqPfujIpV',
    'tables' => [
        'files' => 'mjzc3fv727to95i',
        'sections' => 'm1g39mqv5mtdwad', 
        'products' => 'm4twrspf9oj7rvi',
        'main' => 'ma95crsjyfik3ce'
    ]
];

// وظائف API
function api_request($table_id, $method = 'GET', $data = null, $record_id = null) {
    global $api_config;
    
    $url = $api_config['base_url'] . '/' . $table_id . '/records';
    if ($record_id) $url .= '/' . $record_id;
    
    $headers = [
        'xc-token: ' . $api_config['token'],
        'Content-Type: application/json'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    if ($data && in_array($method, ['POST', 'PATCH'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        $result = json_decode($response, true);
        return $method === 'GET' ? ($result['list'] ?? $result) : $result;
    }
    
    return false;
}

// معالجة الطلبات
if ($_POST) {
    $action = $_POST['action'] ?? '';
    $table = $_POST['table'] ?? '';
    $record_id = $_POST['record_id'] ?? null;
    
    if ($table && isset($api_config['tables'][$table])) {
        $table_id = $api_config['tables'][$table];
        
        switch ($action) {
            case 'create':
                unset($_POST['action'], $_POST['table'], $_POST['record_id']);
                $result = api_request($table_id, 'POST', [$_POST]);
                break;
                
            case 'update':
                unset($_POST['action'], $_POST['table']);
                $result = api_request($table_id, 'PATCH', [$_POST], $record_id);
                break;
                
            case 'delete':
                $delete_data = ['Id' => $record_id, 'محذوف' => 1];
                $result = api_request($table_id, 'PATCH', [$delete_data], $record_id);
                break;
                
            case 'reorder':
                $items = json_decode($_POST['items'], true);
                foreach ($items as $item) {
                    $update_data = ['Id' => $item['id'], 'ترتيب' => $item['order']];
                    api_request($table_id, 'PATCH', [$update_data], $item['id']);
                }
                break;
        }
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// جلب البيانات
$data = [];
foreach ($api_config['tables'] as $key => $table_id) {
    $data[$key] = api_request($table_id) ?: [];
}

// فلترة المحذوفات
foreach ($data as &$table_data) {
    $table_data = array_filter($table_data, function($item) {
        return !isset($item['محذوف']) || $item['محذوف'] != 1;
    });
}

$selected_section = $_GET['section'] ?? 'files';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المحتوى</title>
    
    <!-- Google Fonts - Cairo -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- SortableJS -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    
    <style>
        * { font-family: 'Cairo', sans-serif; }
        .img-thumb { width: 50px; height: 50px; object-fit: cover; border-radius: 8px; }
        .sortable-item { cursor: move; }
        .sortable-item:hover { background-color: #f8f9fa; }
        .form-floating { margin-bottom: 1rem; }
    </style>
</head>
<body class="bg-light">

<div class="container-fluid py-4">
    
    <!-- قائمة الأقسام -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body p-3">
                    <div class="btn-group w-100" role="group">
                        <a href="?section=files" class="btn <?= $selected_section == 'files' ? 'btn-primary' : 'btn-outline-primary' ?>">
                            <i class="fas fa-folder me-2"></i>الملفات
                        </a>
                        <a href="?section=sections" class="btn <?= $selected_section == 'sections' ? 'btn-primary' : 'btn-outline-primary' ?>">
                            <i class="fas fa-th-large me-2"></i>أقسام المنتجات
                        </a>
                        <a href="?section=products" class="btn <?= $selected_section == 'products' ? 'btn-primary' : 'btn-outline-primary' ?>">
                            <i class="fas fa-cube me-2"></i>المنتجات
                        </a>
                        <a href="?section=main" class="btn <?= $selected_section == 'main' ? 'btn-primary' : 'btn-outline-primary' ?>">
                            <i class="fas fa-home me-2"></i>الصفحة الرئيسية
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- قائمة العناصر -->
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <?php
                        $section_titles = [
                            'files' => 'الملفات',
                            'sections' => 'أقسام المنتجات', 
                            'products' => 'المنتجات',
                            'main' => 'الصفحة الرئيسية'
                        ];
                        echo $section_titles[$selected_section];
                        ?>
                    </h5>
                    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#itemModal" onclick="resetForm()">
                        <i class="fas fa-plus me-1"></i>إضافة جديد
                    </button>
                </div>
                
                <div class="card-body p-0">
                    <div id="sortable-list" class="list-group list-group-flush">
                        <?php 
                        $current_data = $data[$selected_section];
                        
                        // ترتيب البيانات
                        if ($selected_section == 'main' && !empty($current_data)) {
                            usort($current_data, function($a, $b) {
                                return ($a['ترتيب'] ?? 999) - ($b['ترتيب'] ?? 999);
                            });
                        }
                        
                        foreach ($current_data as $item): 
                            $id = $item['Id'] ?? $item['المعرف'] ?? '';
                            $name = '';
                            $image = '';
                            $subtitle = '';
                            
                            // تحديد البيانات حسب القسم
                            switch ($selected_section) {
                                case 'files':
                                    $name = $item['الملف'] ?? '';
                                    $subtitle = "معرف: " . ($item['المعرف'] ?? '');
                                    break;
                                case 'sections':
                                    $name = $item['الاسم'] ?? '';
                                    $image = $item['الصورة'] ?? '';
                                    $subtitle = "معرف القسم: " . ($item['معرف_القسم'] ?? '');
                                    break;
                                case 'products':
                                    $name = $item['الاسم'] ?? '';
                                    $image = $item['الصورة'] ?? '';
                                    $subtitle = "القسم: " . ($item['القسم'] ?? '');
                                    break;
                                case 'main':
                                    $name = $item['الاسم'] ?? '';
                                    $image = $item['الصورة'] ?? '';
                                    $subtitle = "الموقع: " . ($item['الموقع'] ?? '') . " | ترتيب: " . ($item['ترتيب'] ?? 'غير محدد');
                                    break;
                            }
                        ?>
                        
                        <div class="list-group-item sortable-item" data-id="<?= $id ?>">
                            <div class="d-flex align-items-center">
                                <!-- مؤشر الترتيب -->
                                <?php if ($selected_section == 'main'): ?>
                                <div class="me-3">
                                    <i class="fas fa-grip-vertical text-muted"></i>
                                </div>
                                <?php endif; ?>
                                
                                <!-- الصورة -->
                                <?php if ($image): ?>
                                <img src="<?= htmlspecialchars($image) ?>" class="img-thumb me-3" alt="صورة">
                                <?php else: ?>
                                <div class="bg-secondary rounded me-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                    <i class="fas fa-image text-white"></i>
                                </div>
                                <?php endif; ?>
                                
                                <!-- المحتوى -->
                                <div class="flex-grow-1">
                                    <h6 class="mb-1"><?= htmlspecialchars($name) ?></h6>
                                    <small class="text-muted"><?= htmlspecialchars($subtitle) ?></small>
                                </div>
                                
                                <!-- الأزرار -->
                                <div class="btn-group">
                                    <button class="btn btn-outline-primary btn-sm" onclick="editItem(<?= htmlspecialchars(json_encode($item)) ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-outline-danger btn-sm" onclick="deleteItem(<?= $id ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- نموذج التعديل -->
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h6 class="mb-0">نموذج التعديل</h6>
                </div>
                <div class="card-body">
                    <form method="post" id="itemForm">
                        <input type="hidden" name="action" id="formAction" value="create">
                        <input type="hidden" name="table" value="<?= $selected_section ?>">
                        <input type="hidden" name="record_id" id="recordId">
                        
                        <?php
                        // حقول النموذج حسب القسم
                        $form_fields = [
                            'files' => [
                                ['name' => 'الملف', 'type' => 'text', 'label' => 'اسم الملف', 'required' => true]
                            ],
                            'sections' => [
                                ['name' => 'الاسم', 'type' => 'text', 'label' => 'اسم القسم', 'required' => true],
                                ['name' => 'الصورة', 'type' => 'url', 'label' => 'رابط الصورة', 'required' => false],
                                ['name' => 'معرف_القسم', 'type' => 'number', 'label' => 'معرف القسم', 'required' => true],
                                ['name' => 'الرابط', 'type' => 'text', 'label' => 'الرابط', 'required' => false]
                            ],
                            'products' => [
                                ['name' => 'الاسم', 'type' => 'text', 'label' => 'اسم المنتج', 'required' => true],
                                ['name' => 'القسم', 'type' => 'text', 'label' => 'القسم', 'required' => true],
                                ['name' => 'الصورة', 'type' => 'url', 'label' => 'رابط الصورة', 'required' => false],
                                ['name' => 'معرف_القسم', 'type' => 'number', 'label' => 'معرف القسم', 'required' => true]
                            ],
                            'main' => [
                                ['name' => 'الاسم', 'type' => 'text', 'label' => 'الاسم', 'required' => true],
                                ['name' => 'الصورة', 'type' => 'url', 'label' => 'رابط الصورة', 'required' => false],
                                ['name' => 'الموقع', 'type' => 'select', 'label' => 'الموقع', 'required' => true, 'options' => ['سلايدر الهيدر', 'سلايدر العملاء', 'كتلوجات', 'الملفات']],
                                ['name' => 'الرابط', 'type' => 'url', 'label' => 'الرابط', 'required' => false],
                                ['name' => 'معرف الملف', 'type' => 'number', 'label' => 'معرف الملف', 'required' => false],
                                ['name' => 'ترتيب', 'type' => 'number', 'label' => 'الترتيب', 'required' => false],
                                ['name' => 'ترتيب فرعي', 'type' => 'number', 'label' => 'الترتيب الفرعي', 'required' => false],
                                ['name' => 'الاسم الفرعي', 'type' => 'text', 'label' => 'الاسم الفرعي', 'required' => false]
                            ]
                        ];
                        
                        foreach ($form_fields[$selected_section] as $field):
                        ?>
                        <div class="form-floating">
                            <?php if ($field['type'] == 'select'): ?>
                            <select class="form-select" name="<?= $field['name'] ?>" id="<?= $field['name'] ?>" <?= $field['required'] ? 'required' : '' ?>>
                                <option value="">اختر...</option>
                                <?php foreach ($field['options'] as $option): ?>
                                <option value="<?= $option ?>"><?= $option ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php else: ?>
                            <input type="<?= $field['type'] ?>" class="form-control" name="<?= $field['name'] ?>" id="<?= $field['name'] ?>" placeholder="<?= $field['label'] ?>" <?= $field['required'] ? 'required' : '' ?>>
                            <?php endif; ?>
                            <label for="<?= $field['name'] ?>"><?= $field['label'] ?></label>
                        </div>
                        <?php endforeach; ?>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-2"></i>حفظ
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="resetForm()">
                                <i class="fas fa-times me-2"></i>إلغاء
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// تطبيق الترتيب بالسحب
<?php if ($selected_section == 'main'): ?>
new Sortable(document.getElementById('sortable-list'), {
    animation: 150,
    ghostClass: 'bg-light',
    onEnd: function(evt) {
        const items = [];
        document.querySelectorAll('.sortable-item').forEach((item, index) => {
            items.push({
                id: item.dataset.id,
                order: index + 1
            });
        });
        
        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=reorder&table=<?= $selected_section ?>&items=${JSON.stringify(items)}`
        }).then(() => location.reload());
    }
});
<?php endif; ?>

// وظائف JavaScript
function resetForm() {
    document.getElementById('itemForm').reset();
    document.getElementById('formAction').value = 'create';
    document.getElementById('recordId').value = '';
}

function editItem(item) {
    document.getElementById('formAction').value = 'update';
    document.getElementById('recordId').value = item.Id || item.المعرف;
    
    // ملء الحقول
    for (const [key, value] of Object.entries(item)) {
        const field = document.getElementById(key);
        if (field) {
            field.value = value || '';
        }
    }
}

function deleteItem(id) {
    if (confirm('هل أنت متأكد من حذف هذا العنصر؟')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input name="action" value="delete">
            <input name="table" value="<?= $selected_section ?>">
            <input name="record_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

</body>
</html>