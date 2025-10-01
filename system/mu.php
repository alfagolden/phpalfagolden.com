<?php
// ##################################################################
// ########### PHP BACKEND - UPDATED FOR SINGLE PERMISSION ###########
// ##################################################################

// Configuration
$api_config = [
    'base_url' => 'https://base.alfagolden.com',
    'token' => 'h5qAt85gtiJDAzpH51WrXPywhmnhrPWy',
    'users_table_id' => 702,
    'permissions_table_id' => 699
];

// Field mappings
$fields = [
    'users' => [
        'phone' => 'field_6773',
        'name' => 'field_6912',
        'gender' => 'field_6913',
        'permissions' => 'field_6777'
    ],
    'permissions' => [
        'name' => 'field_6763'
    ]
];

// Helper function to make API requests
function makeApiRequest($endpoint, $method = 'GET', $data = null) {
    global $api_config;
    
    $url = $api_config['base_url'] . '/api/database/' . $endpoint;
    
    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Token ' . $api_config['token'],
            'Content-Type: application/json'
        ],
        CURLOPT_CUSTOMREQUEST => $method
    ];
    
    if ($data) {
        $options[CURLOPT_POSTFIELDS] = json_encode($data);
    }
    
    $curl = curl_init();
    curl_setopt_array($curl, $options);
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    if ($httpCode >= 400) {
        throw new Exception("API Error: HTTP $httpCode");
    }
    
    return json_decode($response, true);
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'load_users':
                $response = makeApiRequest("rows/table/{$api_config['users_table_id']}/");
                echo json_encode(['success' => true, 'data' => $response['results'] ?? []]);
                break;
                
            case 'load_permissions':
                $response = makeApiRequest("rows/table/{$api_config['permissions_table_id']}/");
                echo json_encode(['success' => true, 'data' => $response['results'] ?? []]);
                break;
                
            case 'check_phone':
                $phone = $_POST['phone'];
                $users_response = makeApiRequest("rows/table/{$api_config['users_table_id']}/");
                $existing_users = $users_response['results'] ?? [];
                
                foreach ($existing_users as $user) {
                    if ($user[$fields['users']['phone']] === $phone) {
                        echo json_encode(['success' => true, 'exists' => true, 'user' => $user]);
                        exit;
                    }
                }
                echo json_encode(['success' => true, 'exists' => false]);
                break;
                
            case 'create_user':
                $phone = $_POST['phone'];
                $users_response = makeApiRequest("rows/table/{$api_config['users_table_id']}/");
                $existing_users = $users_response['results'] ?? [];
                
                foreach ($existing_users as $user) {
                    if ($user[$fields['users']['phone']] === $phone) {
                        echo json_encode(['success' => false, 'message' => 'رقم الجوال موجود مسبقاً']);
                        exit;
                    }
                }
                
                $permission_id = !empty($_POST['permission']) ? intval($_POST['permission']) : null;
                $permissions_array = $permission_id ? [$permission_id] : [];
                
                $user_data = [
                    $fields['users']['phone'] => $phone,
                    $fields['users']['name'] => $_POST['name'],
                    $fields['users']['gender'] => $_POST['gender'],
                    $fields['users']['permissions'] => $permissions_array
                ];
                
                $response = makeApiRequest("rows/table/{$api_config['users_table_id']}/", 'POST', $user_data);
                echo json_encode(['success' => true, 'message' => 'تم إضافة المستخدم بنجاح']);
                break;
                
            case 'update_user':
                $user_id = $_POST['user_id'];
                $phone = $_POST['phone'];
                
                // Check for duplicate phone number (excluding current user)
                $users_response = makeApiRequest("rows/table/{$api_config['users_table_id']}/");
                $existing_users = $users_response['results'] ?? [];
                
                foreach ($existing_users as $user) {
                    if ($user['id'] != $user_id && $user[$fields['users']['phone']] === $phone) {
                        echo json_encode(['success' => false, 'message' => 'رقم الجوال موجود مسبقاً']);
                        exit;
                    }
                }
                
                $permission_id = !empty($_POST['permission']) ? intval($_POST['permission']) : null;
                $permissions_array = $permission_id ? [$permission_id] : [];
                
                $user_data = [
                    $fields['users']['phone'] => $phone,
                    $fields['users']['name'] => $_POST['name'],
                    $fields['users']['gender'] => $_POST['gender'],
                    $fields['users']['permissions'] => $permissions_array
                ];
                
                $response = makeApiRequest("rows/table/{$api_config['users_table_id']}/{$user_id}/", 'PATCH', $user_data);
                echo json_encode(['success' => true, 'message' => 'تم تحديث المستخدم بنجاح']);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'إجراء غير صحيح']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'خطأ: ' . $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المستخدمين - ألفا الذهبية</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.7.2/css/all.min.css" rel="stylesheet" crossorigin="anonymous">
    <link href="https://unpkg.com/@fortawesome/fontawesome-free@6.7.2/css/all.min.css" rel="stylesheet" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ########## IMPROVED DESIGN CSS ########## */
        
        :root {
            --gold: #977e2b;
            --gold-hover: #b89635;
            --gold-light: rgba(151, 126, 43, 0.1);
            --dark-gray: #2c2c2c;
            --medium-gray: #666;
            --light-gray: #f8f9fa;
            --white: #ffffff;
            --border-color: #e5e7eb;
            --success-bg: #f0fdf4;
            --success-text: #166534;
            --success-border: #bbf7d0;
            --error-bg: #fef2f2;
            --error-text: #991b1b;
            --error-border: #fecaca;
        }

        * {
            font-family: 'Cairo', sans-serif !important;
        }

        body {
            font-family: 'Cairo', sans-serif !important;
            font-size: 14px;
            line-height: 1.5;
            direction: rtl;
            background: var(--light-gray);
            color: var(--dark-gray);
            margin: 0;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 24px;
        }

        /* Cards */
        .card {
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-color);
            margin-bottom: 20px;
        }

        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-color);
        }

        .card-body {
            padding: 24px;
        }
        
        .card-body > *:last-child {
            margin-bottom: 0;
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--gold);
            font-family: 'Cairo', sans-serif !important;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            font-family: 'Cairo', sans-serif !important;
        }

        .btn-primary {
            background: var(--gold);
            color: var(--white);
        }

        .btn-primary:hover {
            background: var(--gold-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(151, 126, 43, 0.2);
        }
        
        .btn-secondary {
            background-color: var(--light-gray);
            color: var(--dark-gray);
            border: 1px solid var(--border-color);
        }
        
        .btn-secondary:hover {
            background-color: #e9ecef;
            border-color: #dee2e6;
        }

        /* Forms */
        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--dark-gray);
            margin-bottom: 8px;
            font-family: 'Cairo', sans-serif !important;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Cairo', sans-serif !important;
            transition: all 0.2s ease;
            box-sizing: border-box;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--gold);
            box-shadow: 0 0 0 3px var(--gold-light);
        }

        /* Tables */
        .table-wrapper {
            overflow-x: auto;
        }

        .modern-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--white);
        }

        .modern-table th {
            background: var(--light-gray);
            padding: 12px 16px;
            text-align: right;
            font-weight: 600;
            font-size: 12px;
            color: var(--medium-gray);
            text-transform: uppercase;
            border-bottom: 1px solid var(--border-color);
            font-family: 'Cairo', sans-serif !important;
        }

        .modern-table td {
            padding: 12px 16px;
            text-align: right;
            border-bottom: 1px solid var(--border-color);
            font-size: 14px;
            color: var(--dark-gray);
            font-family: 'Cairo', sans-serif !important;
        }

        .modern-table tbody tr:hover {
            background: var(--gold-light);
        }
        
        .modern-table tbody tr:last-child td {
            border-bottom: none;
        }

        .permission-tag {
            display: inline-block;
            padding: 4px 8px;
            background-color: #eef2ff;
            color: #4338ca;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            margin: 2px;
            font-family: 'Cairo', sans-serif !important;
        }
        
        /* Messages */
        .message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid transparent;
            font-family: 'Cairo', sans-serif !important;
        }

        .message.success {
            background: var(--success-bg);
            color: var(--success-text);
            border-color: var(--success-border);
        }

        .message.error {
            background: var(--error-bg);
            color: var(--error-text);
            border-color: var(--error-border);
        }
        
        /* Loading */
        .spinner-container {
            text-align: center;
            padding: 40px;
            display: none;
        }
        
        .spinner {
            width: 32px;
            height: 32px;
            border: 3px solid var(--border-color);
            border-top: 3px solid var(--gold);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .page-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            transition: opacity 0.3s ease;
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 16px;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .modal-content {
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 550px;
            max-height: 90vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transform: scale(0.95);
            transition: transform 0.3s ease;
        }
        
        .modal-overlay.active .modal-content {
            transform: scale(1);
        }

        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-color);
            flex-shrink: 0;
        }
        
        .modal-body {
            padding: 24px;
            overflow-y: auto;
        }
        
        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid var(--border-color);
            background-color: var(--light-gray);
            text-align: left;
            flex-shrink: 0;
        }
        
        .modal-footer .btn {
            margin-right: 10px;
        }
        
        /* Fallback for icons if FontAwesome fails to load */
        i[class*="fa-"]:before {
            font-family: "Font Awesome 6 Free", "Font Awesome 6 Pro", "Font Awesome 5 Free", "Font Awesome 5 Pro", "FontAwesome", sans-serif !important;
            font-weight: 900 !important;
            font-style: normal !important;
            display: inline-block !important;
        }
        
        /* Ensure specific icons work - try multiple font declarations */
        .fa-pen:before, .fa-user-pen:before {
            font-family: "Font Awesome 6 Free", "FontAwesome" !important;
            font-weight: 900 !important;
        }
        
        /* Force specific icon styles and provide alternatives */
        .edit-icon:before {
            content: "\f304" !important; /* fa-pen unicode */
            font-family: "Font Awesome 6 Free" !important;
            font-weight: 900 !important;
        }
        
        /* Fallback to fa-edit if fa-pen doesn't work */
        .edit-icon.fa-pen:before {
            content: "\f044" !important; /* fa-edit unicode as fallback */
        }
        
        /* Force icon styles */
        .fas, .far, .fab {
            font-family: "Font Awesome 6 Free" !important;
            font-weight: 900 !important;
        }
        
        /* Text fallbacks when Font Awesome fails */
        .fa-fallback .fa-users-gear:before { content: "👥" !important; font-family: "Cairo", Arial, sans-serif !important; }
        .fa-fallback .fa-plus:before { content: "+" !important; font-family: "Cairo", Arial, sans-serif !important; font-weight: bold !important; background: #977e2b; color: white; border-radius: 3px; padding: 2px 4px; }
        .fa-fallback .fa-pen:before, .fa-fallback .edit-icon:before { content: "✏" !important; font-family: "Cairo", Arial, sans-serif !important; background: #f8f9fa; color: #666; border-radius: 3px; padding: 2px; }
        .fa-fallback .fa-user-plus:before { content: "+👤" !important; font-family: "Cairo", Arial, sans-serif !important; }
        .fa-fallback .fa-user-pen:before { content: "✏👤" !important; font-family: "Cairo", Arial, sans-serif !important; }
        .fa-fallback .fa-circle-check:before { content: "✓" !important; font-family: "Cairo", Arial, sans-serif !important; color: #166534 !important; font-weight: bold !important; background: #f0fdf4; border-radius: 50%; padding: 2px 4px; }
        .fa-fallback .fa-triangle-exclamation:before { content: "!" !important; font-family: "Cairo", Arial, sans-serif !important; color: #991b1b !important; font-weight: bold !important; background: #fef2f2; border-radius: 3px; padding: 2px 4px; }
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        /* Modal Message Container */
        .modal-message-container {
            margin-bottom: 16px;
        }

        .modal-message-container .message {
            margin-bottom: 0;
        }
    </style>
</head>
<body>

    <div id="pageLoader" class="page-loader">
        <div class="spinner"></div>
    </div>

    <div class="container">
        <!-- Header Card -->
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">
                    <i class="fas fa-users-gear"></i>
                    إدارة المستخدمين
                </h1>
            </div>
            <div class="card-body">
                <div id="messageContainer"></div>
                <div class="toolbar">
                    <input 
                        type="text" 
                        id="searchInput" 
                        placeholder="ابحث بالاسم، الرقم، أو الصلاحية..." 
                        class="form-control"
                        style="max-width: 400px;"
                    />
                    <button class="btn btn-primary" onclick="openAddUserModal()">
                        <i class="fas fa-plus"></i>
                        إضافة مستخدم
                    </button>
                </div>
            </div>
        </div>

        <!-- Users Table Card -->
        <div class="card">
            <div class="card-body" style="padding: 0;">
                <div id="loading" class="spinner-container">
                    <div class="spinner"></div>
                    <p>جاري تحميل البيانات...</p>
                </div>
                <div class="table-wrapper">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>رقم الجوال</th>
                                <th>الاسم</th>
                                <th>الجنس</th>
                                <th>الصلاحية</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody">
                            <!-- User rows will be populated here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit User Modal -->
    <div id="userModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle" class="card-title" style="font-size: 16px;"></h2>
            </div>
            <form id="userForm" onsubmit="handleFormSubmit(event)">
                <div class="modal-body">
                    <div id="modalMessageContainer" class="modal-message-container"></div>
                    
                    <div class="form-group">
                        <label for="userPhone" class="form-label">رقم الجوال (صيغة 05XXXXXXXX)</label>
                        <input type="tel" id="userPhone" class="form-control" required pattern="^05[0-9]{8}$" title="يجب أن يبدأ الرقم بـ 05 ويتكون من 10 أرقام." onblur="checkPhoneExists()" />
                    </div>
                    
                    <div class="form-group">
                        <label for="userName" class="form-label">الاسم الكامل</label>
                        <input type="text" id="userName" class="form-control" required />
                    </div>
                    
                    <div class="form-group">
                        <label for="userGender" class="form-label">الجنس</label>
                        <select id="userGender" class="form-control" required>
                            <option value="" disabled selected>اختر الجنس</option>
                            <option value="ذكر">ذكر</option>
                            <option value="أنثى">أنثى</option>
                            <option value="مؤسسة">مؤسسة</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="userPermission" class="form-label">الصلاحية</label>
                        <select id="userPermission" class="form-control">
                            <option value="">اختر الصلاحية</option>
                            <!-- Permissions options will be loaded here -->
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                     <button type="submit" id="saveUserBtn" class="btn btn-primary">حفظ</button>
                     <button type="button" class="btn btn-secondary" onclick="closeUserModal()">إلغاء</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // ##################################################################
        // ############ JAVASCRIPT LOGIC - IMPROVED VERSION ############
        // ##################################################################

        // Global variables
        let users = [];
        let permissions = [];
        let currentEditingUserId = null;

        // API Helper
        async function makeAjaxRequest(action, data = {}) {
            const formData = new FormData();
            formData.append('action', action);
            for (const key in data) {
                formData.append(key, data[key]);
            }
            const response = await fetch('', { method: 'POST', body: formData });
            return response.json();
        }

        // Data Loading
        async function loadUsers() {
            try {
                showLoading(true);
                const response = await makeAjaxRequest('load_users');
                showLoading(false);
                if (response.success) {
                    users = response.data || [];
                    renderUsers();
                } else {
                    showMessage('فشل في تحميل المستخدمين.', 'error');
                }
            } catch (error) {
                showLoading(false);
                showMessage('خطأ في الاتصال بالخادم.', 'error');
            }
        }

        async function loadPermissions() {
            const response = await makeAjaxRequest('load_permissions');
            if (response.success) {
                permissions = response.data || [];
                renderPermissionsInSelect();
            } else {
                showMessage('فشل في تحميل الصلاحيات.', 'error');
            }
        }

        // Phone check function
        async function checkPhoneExists() {
            const phoneInput = document.getElementById('userPhone');
            const phone = phoneInput.value.trim();
            
            if (!phone || phone.length !== 10 || !phone.startsWith('05')) {
                return;
            }

            // Skip check if we're editing and the phone belongs to current user
            if (currentEditingUserId) {
                const currentUser = users.find(u => u.id === currentEditingUserId);
                if (currentUser) {
                    let currentPhone = currentUser['<?php echo $fields['users']['phone']; ?>'] || '';
                    if (currentPhone.startsWith('+966')) {
                        currentPhone = '0' + currentPhone.substring(4);
                    }
                    if (currentPhone === phone) {
                        return;
                    }
                }
            }

            const phoneValue = '+966' + phone.substring(1);
            
            try {
                const response = await makeAjaxRequest('check_phone', { phone: phoneValue });
                if (response.success && response.exists) {
                    const existingUser = response.user;
                    
                    // Show message that user exists
                    showModalMessage('هذا المستخدم موجود مسبقاً. تم تحويل النموذج إلى وضع التعديل.', 'success');
                    
                    // Switch to edit mode
                    currentEditingUserId = existingUser.id;
                    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-pen"></i> تعديل بيانات المستخدم';
                    
                    // Fill form with existing data
                    document.getElementById('userName').value = existingUser['<?php echo $fields['users']['name']; ?>'] || '';
                    document.getElementById('userGender').value = existingUser['<?php echo $fields['users']['gender']; ?>'] || '';
                    
                    // Set permission (single value)
                    const userPermissions = existingUser['<?php echo $fields['users']['permissions']; ?>'];
                    let selectedPermissionId = '';
                    if (Array.isArray(userPermissions) && userPermissions.length > 0) {
                        selectedPermissionId = userPermissions[0].id || userPermissions[0];
                    }
                    document.getElementById('userPermission').value = selectedPermissionId;
                }
            } catch (error) {
                console.error('Error checking phone:', error);
            }
        }

        // Data Helpers
        function extractPermissionId(permissionsData) {
            if (!Array.isArray(permissionsData) || permissionsData.length === 0) return null;
            const firstPerm = permissionsData[0];
            return (typeof firstPerm === 'object' && firstPerm.id) ? firstPerm.id : firstPerm;
        }

        function getPermissionName(permissionId) {
            if (!permissionId) return 'لا يوجد';
            const permission = permissions.find(p => p.id === permissionId);
            return permission ? permission['<?php echo $fields['permissions']['name']; ?>'] : 'غير محدد';
        }

        // Rendering Functions
        function renderUsers(usersToRender = users) {
            const tableBody = document.getElementById('usersTableBody');
            if (!usersToRender.length) {
                tableBody.innerHTML = `<tr><td colspan="5" style="text-align: center; padding: 30px; color: var(--medium-gray);">لا يوجد مستخدمون لعرضهم.</td></tr>`;
                return;
            }

            tableBody.innerHTML = usersToRender.map(user => {
                const userPhone = user['<?php echo $fields['users']['phone']; ?>'] || '';
                const userName = user['<?php echo $fields['users']['name']; ?>'] || 'N/A';
                const userGender = user['<?php echo $fields['users']['gender']; ?>'] || 'N/A';
                
                let displayPhone = userPhone;
                if (userPhone.startsWith('+966')) {
                    displayPhone = '0' + userPhone.substring(4);
                }

                const permissionId = extractPermissionId(user['<?php echo $fields['users']['permissions']; ?>']);
                const permissionName = getPermissionName(permissionId);
                const permissionHtml = permissionId ? 
                    `<span class="permission-tag">${permissionName}</span>` : 
                    `<span style="color: var(--medium-gray);">لا يوجد</span>`;

                return `
                    <tr>
                        <td>${displayPhone}</td>
                        <td>${userName}</td>
                        <td>${userGender}</td>
                        <td>${permissionHtml}</td>
                        <td>
                            <button class="btn btn-secondary" style="padding: 5px 10px;" onclick="editUser(${user.id})">
                                <i class="fas fa-pen edit-icon"></i> تعديل
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function renderPermissionsInSelect() {
            const select = document.getElementById('userPermission');
            if (!permissions.length) {
                select.innerHTML = '<option value="">لا توجد صلاحيات متاحة</option>';
                return;
            }
            
            select.innerHTML = '<option value="">اختر الصلاحية</option>' + 
                permissions.map(p => `<option value="${p.id}">${p['<?php echo $fields['permissions']['name']; ?>']}</option>`).join('');
        }

        // Modal Handling
        const userModal = document.getElementById('userModal');
        function openModal() { userModal.classList.add('active'); }
        function closeModal() { userModal.classList.remove('active'); }

        function openAddUserModal() {
            currentEditingUserId = null;
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-plus"></i> إضافة مستخدم جديد';
            document.getElementById('userForm').reset();
            clearModalMessage();
            openModal();
        }
        
        function closeUserModal() {
            closeModal();
            clearModalMessage();
        }
        
        function editUser(userId) {
            const user = users.find(u => u.id === userId);
            if (!user) return;

            currentEditingUserId = userId;
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-edit"></i> تعديل بيانات المستخدم';
            clearModalMessage();
            
            let phoneValue = user['<?php echo $fields['users']['phone']; ?>'] || '';
            if (phoneValue.startsWith('+966')) {
                phoneValue = '0' + phoneValue.substring(4);
            }
            document.getElementById('userPhone').value = phoneValue;
            document.getElementById('userName').value = user['<?php echo $fields['users']['name']; ?>'] || '';
            document.getElementById('userGender').value = user['<?php echo $fields['users']['gender']; ?>'] || '';

            const permissionId = extractPermissionId(user['<?php echo $fields['users']['permissions']; ?>']);
            document.getElementById('userPermission').value = permissionId || '';

            openModal();
        }

        // Form & Search Handling
        async function handleFormSubmit(e) {
            e.preventDefault();
            const saveBtn = document.getElementById('saveUserBtn');
            saveBtn.disabled = true;
            saveBtn.innerHTML = 'جاري الحفظ...';

            let phoneInput = document.getElementById('userPhone').value.trim();
            let phoneValue = '+966' + phoneInput.substring(1);
            
            const formData = {
                phone: phoneValue,
                name: document.getElementById('userName').value.trim(),
                gender: document.getElementById('userGender').value,
                permission: document.getElementById('userPermission').value
            };

            try {
                const action = currentEditingUserId ? 'update_user' : 'create_user';
                if (currentEditingUserId) {
                    formData.user_id = currentEditingUserId;
                }
                const response = await makeAjaxRequest(action, formData);

                if (response.success) {
                    showMessage(response.message, 'success');
                    closeUserModal();
                    await loadUsers();
                } else {
                    showModalMessage(response.message || 'حدث خطأ غير متوقع', 'error');
                }
            } catch (error) {
                showModalMessage('فشل في حفظ البيانات. تحقق من الاتصال.', 'error');
            } finally {
                saveBtn.disabled = false;
                saveBtn.innerHTML = 'حفظ';
            }
        }

        document.getElementById('searchInput').addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase().trim();
            if (!searchTerm) {
                renderUsers();
                return;
            }
            const filteredUsers = users.filter(user => {
                const permissionId = extractPermissionId(user['<?php echo $fields['users']['permissions']; ?>']);
                const permissionName = getPermissionName(permissionId).toLowerCase();
                return (user['<?php echo $fields['users']['name']; ?>'] || '').toLowerCase().includes(searchTerm) ||
                       (user['<?php echo $fields['users']['phone']; ?>'] || '').includes(searchTerm) ||
                       permissionName.includes(searchTerm);
            });
            renderUsers(filteredUsers);
        });

        // UI Helpers
        function showLoading(show) {
            document.getElementById('loading').style.display = show ? 'block' : 'none';
            document.querySelector('.table-wrapper').style.display = show ? 'none' : 'block';
        }

        function showMessage(message, type) {
            const icon = type === 'success' ? 'fas fa-circle-check' : 'fas fa-triangle-exclamation';
            const messageContainer = document.getElementById('messageContainer');
            messageContainer.innerHTML = `
                <div class="message ${type}">
                    <i class="${icon}"></i>
                    <span>${message}</span>
                </div>`;
            setTimeout(() => messageContainer.innerHTML = '', 5000);
        }

        function showModalMessage(message, type) {
            const icon = type === 'success' ? 'fas fa-circle-check' : 'fas fa-triangle-exclamation';
            const messageContainer = document.getElementById('modalMessageContainer');
            messageContainer.innerHTML = `
                <div class="message ${type}">
                    <i class="${icon}"></i>
                    <span>${message}</span>
                </div>`;
        }

        function clearModalMessage() {
            document.getElementById('modalMessageContainer').innerHTML = '';
        }

        // Initialization
        document.addEventListener('DOMContentLoaded', async () => {
            // Check if Font Awesome loaded properly with delay
            setTimeout(() => {
                checkFontAwesome();
            }, 500);
            
            await loadPermissions();
            await loadUsers();
            
            // Hide page loader
            document.getElementById('pageLoader').style.opacity = '0';
            setTimeout(() => {
                document.getElementById('pageLoader').style.display = 'none';
            }, 300);

            // Close modal on outside click
            userModal.addEventListener('click', (e) => {
                if (e.target === userModal) {
                    closeUserModal();
                }
            });
        });

        // Check if Font Awesome loaded and add fallback if needed
        function checkFontAwesome() {
            try {
                // Test multiple icons to be sure
                const icons = ['fas fa-pen', 'fas fa-users-gear', 'fas fa-plus'];
                let faWorking = false;
                
                for (let iconClass of icons) {
                    const testElement = document.createElement('i');
                    testElement.className = iconClass;
                    testElement.style.position = 'absolute';
                    testElement.style.left = '-9999px';
                    testElement.style.fontSize = '16px';
                    document.body.appendChild(testElement);
                    
                    const computedStyle = window.getComputedStyle(testElement, ':before');
                    const content = computedStyle.getPropertyValue('content');
                    const fontFamily = computedStyle.getPropertyValue('font-family');
                    
                    // Check if Font Awesome is working
                    if (content && content !== 'none' && content !== '""' && 
                        fontFamily && fontFamily.toLowerCase().includes('awesome')) {
                        faWorking = true;
                    }
                    
                    document.body.removeChild(testElement);
                    
                    if (faWorking) break;
                }
                
                if (!faWorking) {
                    console.warn('Font Awesome did not load properly. Using fallback icons.');
                    document.body.classList.add('fa-fallback');
                } else {
                    console.log('Font Awesome loaded successfully.');
                }
            } catch (error) {
                console.error('Error checking Font Awesome:', error);
                document.body.classList.add('fa-fallback');
            }
        }
    </script>
</body>
</html>