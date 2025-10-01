<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// إعدادات Baserow
$token = 'ZC7JRm4cpaONJ8nHw6jN9lq4CBRpbq2Z';
$tableId = '716';
$baseUrl = 'https://base.alfagolden.com';

// مجلد رفع الصور
$uploadDir = __DIR__ . "/uploads/";
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

// دالة رفع الصورة
function uploadImage($file) {
    global $uploadDir;
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'خطأ في رفع الملف'];
    }
    
    $fileName = time() . '_' . basename($file['name']);
    $targetPath = $uploadDir . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $url = $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/uploads/' . $fileName;
        return ['success' => true, 'url' => $url];
    }
    
    return ['success' => false, 'message' => 'فشل في رفع الملف'];
}

// دالة API العامة
function apiCall($method, $endpoint, $data = null) {
    global $token, $baseUrl;
    
    $url = $baseUrl . '/api/database/rows/table/716/' . $endpoint . '?user_field_names=true';
    
    $headers = [
        'Authorization: Token ' . $token,
        'Content-Type: application/json'
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 30
    ]);
    
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        return json_decode($response, true);
    }
    
    return false;
}

// معالجة الطلبات
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
        case 'edit':
            // رفع الصور
            $imageUrls = [];
            
            if (isset($_FILES['images'])) {
                foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
                    if (!empty($tmpName)) {
                        $file = [
                            'name' => $_FILES['images']['name'][$key],
                            'tmp_name' => $tmpName,
                            'error' => $_FILES['images']['error'][$key]
                        ];
                        
                        $uploadResult = uploadImage($file);
                        if ($uploadResult['success']) {
                            $imageUrls[] = $uploadResult['url'];
                        }
                    }
                }
            }
            
            // إضافة الصور المرتبة من النافذة
            if (!empty($_POST['sorted_images'])) {
                $sortedUrls = json_decode($_POST['sorted_images'], true);
                if (is_array($sortedUrls)) {
                    $imageUrls = array_merge($sortedUrls, $imageUrls);
                }
            }
            
            $data = [
                'العنوان' => $_POST['title_ar'] ?? '',
                'العنوان-en' => $_POST['title_en'] ?? '',
                'نص' => $_POST['description_ar'] ?? '',
                'نص-en' => $_POST['description_en'] ?? '',
                'الحالة' => $_POST['status_ar'] ?? '',
                'صور' => implode(',', $imageUrls),
                'ترتيب' => $_POST['order'] ?? '1'
            ];
            
            if ($action === 'add') {
                $result = apiCall('POST', '', $data);
            } else {
                $id = $_POST['id'];
                $result = apiCall('PATCH', $id . '/', $data);
            }
            
            $response['success'] = $result !== false;
            $response['message'] = $response['success'] ? 'تم الحفظ بنجاح' : 'فشل في الحفظ';
            break;
            
        case 'reorder':
            $projectId = $_POST['project_id'];
            $newOrder = $_POST['new_order'];
            
            $data = ['ترتيب' => $newOrder];
            $result = apiCall('PATCH', $projectId . '/', $data);
            
            $response['success'] = $result !== false;
            $response['message'] = $response['success'] ? 'تم تحديث الترتيب' : 'فشل في تحديث الترتيب';
            break;
            
        case 'delete':
            $id = $_POST['id'];
            $result = apiCall('DELETE', $id . '/');
            $response['success'] = $result !== false;
            $response['message'] = $response['success'] ? 'تم الحذف بنجاح' : 'فشل في الحذف';
            break;
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// جلب البيانات
$projects = apiCall('GET', '') ?: ['results' => []];
$projects = $projects['results'] ?? [];

// ترتيب المشاريع حسب حقل الترتيب
usort($projects, function($a, $b) {
    return intval($a['ترتيب'] ?? 999) <=> intval($b['ترتيب'] ?? 999);
});
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المشاريع - ألفا الذهبية</title>
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
            --danger: #dc3545;
            --warning: #ffc107;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Cairo', sans-serif; 
            background: var(--light-gray); 
            color: var(--dark-gray); 
            line-height: 1.6; 
        }
        
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            padding: 40px 20px; 
        }
        
        /* Header */
        .header { 
            background: var(--white); 
            padding: 30px; 
            border-radius: 16px; 
            margin-bottom: 30px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            border: 1px solid var(--border-color);
        }
        
        .header h1 {
            font-size: 32px;
            font-weight: 700;
            color: var(--gold);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .header p {
            color: var(--medium-gray);
            margin-bottom: 20px;
        }
        
        /* Buttons */
        .btn { 
            padding: 12px 24px; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer; 
            font-size: 14px; 
            font-weight: 600;
            transition: var(--transition);
            font-family: 'Cairo', sans-serif;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary { 
            background: var(--gold); 
            color: var(--white); 
        }
        
        .btn-primary:hover { 
            background: var(--gold-hover); 
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(151, 126, 43, 0.3);
        }
        
        .btn-success { background: var(--success); color: var(--white); }
        .btn-danger { background: var(--danger); color: var(--white); }
        .btn-warning { background: var(--warning); color: var(--dark-gray); }
        .btn-secondary { background: var(--medium-gray); color: var(--white); }
        
        .btn-sm { padding: 6px 12px; font-size: 12px; }
        .btn-xs { padding: 4px 8px; font-size: 11px; }
        
        /* Projects Grid */
        .projects-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); 
            gap: 30px; 
        }
        
        .project-card { 
            background: var(--white); 
            border-radius: 16px; 
            overflow: hidden; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.1); 
            position: relative;
            transition: var(--transition);
            border: 1px solid var(--border-color);
        }
        
        .project-card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 8px 30px rgba(0,0,0,0.15); 
        }
        
        .project-image { 
            width: 100%; 
            height: 250px; 
            object-fit: cover; 
        }
        
        .project-content { 
            padding: 25px; 
        }
        
        .project-title { 
            font-size: 20px; 
            font-weight: 700; 
            margin-bottom: 15px;
            color: var(--dark-gray);
        }
        
        .project-description { 
            color: var(--medium-gray); 
            margin-bottom: 15px;
            font-size: 15px;
        }
        
        .project-status { 
            background: var(--gold); 
            color: var(--white); 
            padding: 6px 15px; 
            border-radius: 20px; 
            font-size: 12px; 
            font-weight: 600;
            display: inline-block; 
        }
        
        .project-status.completed { 
            background: var(--success); 
        }
        
        /* Order Controls */
        .order-controls {
            position: absolute;
            top: 15px;
            left: 15px;
            display: flex;
            flex-direction: column;
            gap: 5px;
            z-index: 10;
        }
        
        .order-btn {
            width: 35px;
            height: 35px;
            border: none;
            border-radius: 50%;
            background: rgba(0,0,0,0.7);
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            transition: var(--transition);
            backdrop-filter: blur(5px);
        }
        
        .order-btn:hover {
            background: var(--gold);
            transform: scale(1.1);
        }
        
        .order-btn.up { border-bottom: 2px solid var(--success); }
        .order-btn.down { border-bottom: 2px solid var(--danger); }
        
        /* Project Actions */
        .project-actions { 
            position: absolute; 
            top: 15px; 
            right: 15px; 
            display: flex;
            gap: 8px;
            z-index: 10;
        }
        
        .action-btn {
            width: 35px;
            height: 35px;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            transition: var(--transition);
            backdrop-filter: blur(5px);
        }
        
        .edit-btn {
            background: rgba(0, 123, 255, 0.9);
            color: white;
        }
        
        .delete-btn {
            background: rgba(220, 53, 69, 0.9);
            color: white;
        }
        
        .action-btn:hover {
            transform: scale(1.1);
        }
        
        /* Order Number Badge */
        .order-number {
            position: absolute;
            bottom: 15px;
            left: 15px;
            background: var(--gold);
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            z-index: 10;
        }
        
        /* Modal */
        .modal { 
            display: none; 
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            background: rgba(0,0,0,0.5); 
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active { 
            display: flex; 
        }
        
        .modal-content { 
            background: var(--white); 
            padding: 0; 
            border-radius: 16px; 
            width: 90%; 
            max-width: 700px; 
            max-height: 90vh; 
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        .modal-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: 25px 30px;
            border-bottom: 1px solid var(--border-color);
            background: var(--light-gray);
        }
        
        .modal-title {
            font-size: 22px;
            font-weight: 700;
            color: var(--gold);
        }
        
        .close { 
            background: none; 
            border: none; 
            font-size: 28px; 
            cursor: pointer;
            color: var(--medium-gray);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }
        
        .close:hover {
            background: var(--danger);
            color: white;
        }
        
        .modal-body {
            padding: 30px;
        }
        
        /* Form Styles */
        .form-group { 
            margin-bottom: 20px; 
        }
        
        .form-label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: 600;
            color: var(--dark-gray);
        }
        
        .form-control { 
            width: 100%; 
            padding: 12px 16px; 
            border: 2px solid var(--border-color); 
            border-radius: 8px;
            font-family: 'Cairo', sans-serif;
            transition: var(--transition);
            background: var(--white);
        }
        
        .form-control:focus { 
            outline: none; 
            border-color: var(--gold);
            box-shadow: 0 0 0 3px var(--gold-light);
        }
        
        textarea.form-control { 
            min-height: 100px; 
            resize: vertical; 
        }
        
        /* Images Management */
        .images-section {
            border: 2px dashed var(--border-color);
            border-radius: 12px;
            padding: 20px;
            background: var(--light-gray);
        }
        
        .images-section h4 {
            margin-bottom: 15px;
            color: var(--gold);
            font-weight: 600;
        }
        
        .images-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
            max-height: 300px;
            overflow-y: auto;
            padding: 10px;
            background: var(--white);
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }
        
        .image-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: var(--white);
            border: 2px solid var(--border-color);
            border-radius: 10px;
            transition: var(--transition);
        }
        
        .image-item:hover {
            border-color: var(--gold);
            box-shadow: 0 2px 8px var(--gold-light);
        }
        
        .image-thumbnail {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid var(--border-color);
        }
        
        .image-info {
            flex: 1;
        }
        
        .image-name {
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--dark-gray);
        }
        
        .image-url {
            font-size: 12px;
            color: var(--medium-gray);
            word-break: break-all;
        }
        
        .image-controls {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .image-order-controls {
            display: flex;
            gap: 5px;
        }
        
        .image-order-btn {
            width: 30px;
            height: 30px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: white;
            transition: var(--transition);
        }
        
        .image-order-btn.up {
            background: var(--success);
        }
        
        .image-order-btn.down {
            background: var(--warning);
            color: var(--dark-gray);
        }
        
        .image-order-btn:hover {
            transform: scale(1.1);
        }
        
        .image-remove-btn {
            background: var(--danger);
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 11px;
            transition: var(--transition);
        }
        
        .image-remove-btn:hover {
            background: #c82333;
        }
        
        .image-order-number {
            background: var(--gold);
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
            min-width: 30px;
            text-align: center;
        }
        
        /* Utilities */
        .loading { 
            text-align: center; 
            padding: 60px 20px; 
            color: var(--medium-gray);
        }
        
        .spinner {
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 4px solid var(--border-color);
            border-top: 4px solid var(--gold);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .alert { 
            padding: 15px 20px; 
            margin: 15px 0; 
            border-radius: 8px;
            font-weight: 500;
        }
        
        .alert-success { 
            background: #d4edda; 
            color: #155724; 
            border: 1px solid #c3e6cb; 
        }
        
        .alert-error { 
            background: #f8d7da; 
            color: #721c24; 
            border: 1px solid #f5c6cb; 
        }
        
        .empty-state { 
            text-align: center; 
            padding: 60px 20px; 
            color: var(--medium-gray);
            background: var(--white);
            border-radius: 16px;
            border: 2px dashed var(--border-color);
        }
        
        .empty-state i {
            font-size: 48px;
            color: var(--gold);
            margin-bottom: 20px;
        }
        
        .hidden { display: none !important; }
        
        /* Responsive */
        @media (max-width: 768px) {
            .container { padding: 20px 15px; }
            .projects-grid { grid-template-columns: 1fr; gap: 20px; }
            .modal-content { width: 95%; margin: 10px; }
            .modal-body { padding: 20px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                <i class="fas fa-layer-group"></i>
                إدارة مشاريع ألفا الذهبية
            </h1>
            <p>إجمالي المشاريع: <strong><?= count($projects) ?></strong></p>
            <button class="btn btn-primary" onclick="openModal()">
                <i class="fas fa-plus"></i>
                إضافة مشروع جديد
            </button>
        </div>

        <div id="message"></div>

        <?php if (empty($projects)): ?>
            <div class="empty-state">
                <i class="fas fa-folder-plus"></i>
                <h3>لا توجد مشاريع</h3>
                <p>ابدأ بإضافة مشروع جديد</p>
            </div>
        <?php else: ?>
            <div class="projects-grid">
                <?php foreach ($projects as $index => $project): ?>
                    <?php 
                    $images = !empty($project['صور']) ? explode(',', $project['صور']) : [];
                    $firstImage = !empty($images) ? trim($images[0]) : '';
                    $order = $project['ترتيب'] ?? ($index + 1);
                    ?>
                    <div class="project-card" data-id="<?= $project['id'] ?>" data-order="<?= $order ?>">
                        <!-- Order Controls -->
                        <div class="order-controls">
                            <button class="order-btn up" onclick="reorderProject(<?= $project['id'] ?>, 'up')" title="تحريك لأعلى">
                                <i class="fas fa-chevron-up"></i>
                            </button>
                            <button class="order-btn down" onclick="reorderProject(<?= $project['id'] ?>, 'down')" title="تحريك لأسفل">
                                <i class="fas fa-chevron-down"></i>
                            </button>
                        </div>
                        
                        <!-- Project Actions -->
                        <div class="project-actions">
                            <button class="action-btn edit-btn" onclick="editProject(<?= htmlspecialchars(json_encode($project)) ?>)" title="تعديل">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="action-btn delete-btn" onclick="deleteProject(<?= $project['id'] ?>)" title="حذف">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        
                        <!-- Order Number -->
                        <div class="order-number"><?= $order ?></div>
                        
                        <?php if ($firstImage): ?>
                            <img src="<?= htmlspecialchars($firstImage) ?>" alt="<?= htmlspecialchars($project['العنوان']) ?>" class="project-image" onerror="this.style.display='none'">
                        <?php else: ?>
                            <div style="height: 250px; background: var(--light-gray); display: flex; align-items: center; justify-content: center; color: var(--medium-gray);">
                                <i class="fas fa-image" style="font-size: 48px;"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="project-content">
                            <h3 class="project-title"><?= htmlspecialchars($project['العنوان']) ?></h3>
                            <p class="project-description"><?= htmlspecialchars(substr($project['نص'], 0, 120)) ?>...</p>
                            <span class="project-status <?= $project['الحالة'] === 'مكتمل' ? 'completed' : '' ?>">
                                <?= htmlspecialchars($project['الحالة']) ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal -->
    <div id="projectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">إضافة مشروع</h3>
                <button class="close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="modal-body">
                <form id="projectForm" enctype="multipart/form-data">
                    <input type="hidden" id="projectId" name="id">
                    <input type="hidden" id="action" name="action" value="add">
                    <input type="hidden" id="sortedImages" name="sorted_images">
                    
                    <div class="form-group">
                        <label class="form-label">العنوان (عربي)</label>
                        <input type="text" class="form-control" id="titleAr" name="title_ar" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">العنوان (إنجليزي)</label>
                        <input type="text" class="form-control" id="titleEn" name="title_en" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">الوصف (عربي)</label>
                        <textarea class="form-control" id="descriptionAr" name="description_ar" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">الوصف (إنجليزي)</label>
                        <textarea class="form-control" id="descriptionEn" name="description_en" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">الحالة</label>
                        <select class="form-control" id="statusAr" name="status_ar" required>
                            <option value="">اختر الحالة</option>
                            <option value="قيد التنفيذ">قيد التنفيذ</option>
<option value="تم التنفيذ">تم التنفيذ</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">الترتيب</label>
                        <input type="number" class="form-control" id="order" name="order" min="1" value="1">
                    </div>
                    
                    <div class="form-group">
                        <div class="images-section">
                            <h4>
                                <i class="fas fa-images"></i>
                                إدارة الصور
                            </h4>
                            
                            <div style="margin-bottom: 15px;">
                                <label class="form-label">إضافة صور جديدة</label>
                                <input type="file" class="form-control" id="images" name="images[]" accept="image/*" multiple onchange="previewNewImages()">
                            </div>
                            
                            <div class="images-list" id="imagesList">
                                <!-- سيتم ملؤها بـ JavaScript -->
                            </div>
                        </div>
                    </div>
                    
                    <div style="text-align: center; margin-top: 30px;">
                        <button type="button" class="btn btn-secondary" onclick="closeModal()">
                            <i class="fas fa-times"></i>
                            إلغاء
                        </button>
                        <button type="submit" class="btn btn-primary" id="saveBtn">
                            <i class="fas fa-save"></i>
                            حفظ المشروع
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let currentImages = [];
        
        function showMessage(text, type = 'success') {
            const messageDiv = document.getElementById('message');
            messageDiv.innerHTML = `<div class="alert alert-${type}"><i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i> ${text}</div>`;
            setTimeout(() => messageDiv.innerHTML = '', 4000);
        }
        
        // ترتيب المشاريع
        async function reorderProject(projectId, direction) {
            const grid = document.querySelector('.projects-grid');
            const cards = Array.from(grid.querySelectorAll('.project-card')).sort((a, b) => {
                return parseInt(a.dataset.order) - parseInt(b.dataset.order);
            });
            
            const currentIndex = cards.findIndex(card => card.dataset.id == projectId);
            if (currentIndex === -1) return;
            
            let targetIndex;
            if (direction === 'up' && currentIndex > 0) {
                targetIndex = currentIndex - 1;
            } else if (direction === 'down' && currentIndex < cards.length - 1) {
                targetIndex = currentIndex + 1;
            } else {
                return; // لا يمكن التحرك
            }
            
            const currentCard = cards[currentIndex];
            const targetCard = cards[targetIndex];
            
            // تبديل أرقام الترتيب
            const currentOrder = parseInt(currentCard.dataset.order);
            const targetOrder = parseInt(targetCard.dataset.order);
            
            try {
                // إرسال طلبات تحديث الترتيب لكلا المشروعين
                const promises = [
                    updateProjectOrder(currentCard.dataset.id, targetOrder),
                    updateProjectOrder(targetCard.dataset.id, currentOrder)
                ];
                
                const results = await Promise.all(promises);
                
                if (results.every(result => result.success)) {
                    // تحديث البيانات في الواجهة
                    currentCard.dataset.order = targetOrder;
                    targetCard.dataset.order = currentOrder;
                    
                    // تحريك الكروت فعلياً في DOM
                    if (direction === 'up') {
                        grid.insertBefore(currentCard, targetCard);
                    } else {
                        grid.insertBefore(currentCard, targetCard.nextSibling);
                    }
                    
                    // تحديث أرقام الترتيب المعروضة
                    updateOrderNumbers();
                    
                    // إضافة تأثير بصري
                    currentCard.style.transform = 'scale(1.05)';
                    currentCard.style.boxShadow = '0 8px 30px rgba(151, 126, 43, 0.3)';
                    setTimeout(() => {
                        currentCard.style.transform = '';
                        currentCard.style.boxShadow = '';
                    }, 300);
                    
                    showMessage('تم تحديث الترتيب', 'success');
                } else {
                    showMessage('فشل في تحديث الترتيب', 'error');
                }
            } catch (error) {
                showMessage('حدث خطأ: ' + error.message, 'error');
            }
        }
        
        // دالة مساعدة لتحديث ترتيب مشروع واحد
        async function updateProjectOrder(projectId, newOrder) {
            const formData = new FormData();
            formData.append('action', 'reorder');
            formData.append('project_id', projectId);
            formData.append('new_order', newOrder);
            
            const response = await fetch('', {
                method: 'POST',
                body: formData
            });
            
            return await response.json();
        }
        
        function updateOrderNumbers() {
            const cards = Array.from(document.querySelectorAll('.project-card')).sort((a, b) => {
                return parseInt(a.dataset.order) - parseInt(b.dataset.order);
            });
            
            cards.forEach((card, index) => {
                const orderElement = card.querySelector('.order-number');
                if (orderElement) {
                    orderElement.textContent = index + 1;
                }
            });
        }
        
        // إدارة الصور في النافذة
        function updateImagesList() {
            const list = document.getElementById('imagesList');
            list.innerHTML = '';
            
            if (currentImages.length === 0) {
                list.innerHTML = '<p style="text-align: center; color: var(--medium-gray); padding: 20px;">لا توجد صور</p>';
                return;
            }
            
            currentImages.forEach((image, index) => {
                const imageItem = document.createElement('div');
                imageItem.className = 'image-item';
                imageItem.innerHTML = `
                    <div class="image-order-number">${index + 1}</div>
                    <img src="${image.url}" alt="صورة ${index + 1}" class="image-thumbnail">
                    <div class="image-info">
                        <div class="image-name">${image.name || `صورة ${index + 1}`}</div>
                        <div class="image-url">${image.isNew ? 'ملف جديد' : 'موجود'}</div>
                    </div>
                    <div class="image-controls">
                        <div class="image-order-controls">
                            <button type="button" class="image-order-btn up" onclick="moveImage(${index}, 'up')" title="تحريك لأعلى">
                                <i class="fas fa-chevron-up"></i>
                            </button>
                            <button type="button" class="image-order-btn down" onclick="moveImage(${index}, 'down')" title="تحريك لأسفل">
                                <i class="fas fa-chevron-down"></i>
                            </button>
                        </div>
                        <button type="button" class="image-remove-btn" onclick="removeImage(${index})">
                            <i class="fas fa-trash"></i> حذف
                        </button>
                    </div>
                `;
                list.appendChild(imageItem);
            });
            
            updateSortedImagesInput();
        }
        
        function moveImage(index, direction) {
            if (direction === 'up' && index > 0) {
                [currentImages[index], currentImages[index - 1]] = [currentImages[index - 1], currentImages[index]];
            } else if (direction === 'down' && index < currentImages.length - 1) {
                [currentImages[index], currentImages[index + 1]] = [currentImages[index + 1], currentImages[index]];
            }
            updateImagesList();
        }
        
        function removeImage(index) {
            currentImages.splice(index, 1);
            updateImagesList();
        }
        
        function updateSortedImagesInput() {
            const existingUrls = currentImages.filter(img => !img.isNew).map(img => img.url);
            document.getElementById('sortedImages').value = JSON.stringify(existingUrls);
        }
        
        function previewNewImages() {
            const files = document.getElementById('images').files;
            
            for (let file of files) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    currentImages.push({
                        url: e.target.result,
                        name: file.name,
                        file: file,
                        isNew: true
                    });
                    updateImagesList();
                };
                reader.readAsDataURL(file);
            }
        }
        
        function openModal(project = null) {
            document.getElementById('projectModal').classList.add('active');
            document.body.style.overflow = 'hidden';
            
            if (project) {
                document.getElementById('modalTitle').textContent = 'تعديل المشروع';
                document.getElementById('action').value = 'edit';
                document.getElementById('projectId').value = project.id;
                document.getElementById('titleAr').value = project['العنوان'] || '';
                document.getElementById('titleEn').value = project['العنوان-en'] || '';
                document.getElementById('descriptionAr').value = project['نص'] || '';
                document.getElementById('descriptionEn').value = project['نص-en'] || '';
                document.getElementById('statusAr').value = project['الحالة'] || '';
                document.getElementById('order').value = project['ترتيب'] || '1';
                
                // تحميل الصور الموجودة
                currentImages = [];
                if (project['صور']) {
                    const images = project['صور'].split(',');
                    images.forEach((url, index) => {
                        if (url.trim()) {
                            currentImages.push({
                                url: url.trim(),
                                name: `صورة ${index + 1}`,
                                isNew: false
                            });
                        }
                    });
                }
            } else {
                document.getElementById('modalTitle').textContent = 'إضافة مشروع';
                document.getElementById('action').value = 'add';
                document.getElementById('projectForm').reset();
                currentImages = [];
            }
            
            updateImagesList();
        }
        
        function closeModal() {
            document.getElementById('projectModal').classList.remove('active');
            document.body.style.overflow = '';
            document.getElementById('projectForm').reset();
            currentImages = [];
            updateImagesList();
        }
        
        function editProject(project) {
            openModal(project);
        }
        
        async function deleteProject(id) {
            if (!confirm('هل أنت متأكد من حذف هذا المشروع؟')) return;
            
            try {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);
                
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showMessage(result.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showMessage(result.message, 'error');
                }
            } catch (error) {
                showMessage('حدث خطأ: ' + error.message, 'error');
            }
        }
        
        document.getElementById('projectForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const saveBtn = document.getElementById('saveBtn');
            const originalText = saveBtn.innerHTML;
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري الحفظ...';
            
            try {
                const formData = new FormData(this);
                
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showMessage(result.message, 'success');
                    closeModal();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showMessage(result.message, 'error');
                }
            } catch (error) {
                showMessage('حدث خطأ: ' + error.message, 'error');
            } finally {
                saveBtn.disabled = false;
                saveBtn.innerHTML = originalText;
            }
        });
        
        // إغلاق النافذة عند النقر خارجها
        document.getElementById('projectModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>