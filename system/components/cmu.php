<?php
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
                
            case 'create_user':
                // Check for duplicate phone number
                $phone = $_POST['phone'];
                $users_response = makeApiRequest("rows/table/{$api_config['users_table_id']}/");
                $existing_users = $users_response['results'] ?? [];
                
                foreach ($existing_users as $user) {
                    if ($user[$fields['users']['phone']] === $phone) {
                        echo json_encode(['success' => false, 'message' => 'رقم الجوال موجود مسبقاً']);
                        exit;
                    }
                }
                
                $user_data = [
                    $fields['users']['name'] => $_POST['name'],
                    $fields['users']['phone'] => $phone,
                    $fields['users']['gender'] => $_POST['gender'],
                    $fields['users']['permissions'] => json_decode($_POST['permissions'] ?? '[]')
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
                
                $user_data = [
                    $fields['users']['name'] => $_POST['name'],
                    $fields['users']['phone'] => $phone,
                    $fields['users']['gender'] => $_POST['gender'],
                    $fields['users']['permissions'] => json_decode($_POST['permissions'] ?? '[]')
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        gold: '#977e2b',
                        'gold-hover': '#b89635',
                        'gold-light': 'rgba(151, 126, 43, 0.1)',
                        'dark-gray': '#2c2c2c',
                        'medium-gray': '#666',
                        'light-gray': '#f8f9fa'
                    },
                    fontFamily: {
                        'cairo': ['Cairo', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <style type="text/tailwindcss">
        body {
            font-family: 'Cairo', sans-serif;
        }
        
        .spinner {
            border: 2px solid #e5e7eb;
            border-top: 2px solid #977e2b;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .modal-enter {
            animation: modalSlideIn 0.2s ease-out;
        }
        
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: scale(0.95) translateY(-10px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }
        
        .user-card {
            border-top: 2px solid #977e2b;
        }
        
        .user-avatar {
            background: linear-gradient(135deg, #977e2b, #b89635);
        }
        
        .search-icon {
            left: 12px;
        }
        
        .permission-checkbox:hover {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body class="bg-light-gray text-dark-gray font-cairo text-sm font-normal leading-relaxed">
    <!-- Page Loader -->
    <div id="pageLoader" class="fixed inset-0 bg-white/90 flex items-center justify-center z-50">
        <div class="spinner"></div>
    </div>

    <div class="max-w-7xl mx-auto p-4 min-h-screen">
        <!-- Header -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6 border border-gray-200">
            <h1 class="text-lg font-semibold text-dark-gray flex items-center gap-2.5">
                <i class="fas fa-users-cog text-gold text-base"></i>
                إدارة المستخدمين
            </h1>
        </div>

        <!-- Messages -->
        <div id="messageContainer"></div>

        <!-- Toolbar -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6 border border-gray-200 flex justify-between items-center flex-wrap gap-4">
            <div class="relative flex-1 max-w-sm">
                <i class="fas fa-search absolute search-icon top-1/2 transform -translate-y-1/2 text-medium-gray text-sm"></i>
                <input 
                    type="text" 
                    id="searchInput" 
                    placeholder="البحث في المستخدمين..." 
                    class="w-full pr-10 pl-4 py-2.5 border border-gray-300 rounded-md text-sm transition-all duration-150 font-cairo font-normal bg-white focus:outline-none focus:border-gold focus:ring-3 focus:ring-gold-light"
                />
            </div>
            <button 
                class="bg-gold text-white px-4 py-2.5 rounded-md border-none font-medium text-sm cursor-pointer transition-all duration-300 font-cairo flex items-center gap-2 whitespace-nowrap hover:bg-gold-hover hover:-translate-y-0.5 hover:shadow-md"
                onclick="openAddUserModal()"
            >
                <i class="fas fa-user-plus"></i>
                إضافة مستخدم
            </button>
        </div>

        <!-- Loading -->
        <div id="loading" class="hidden text-center py-8 text-medium-gray">
            <div class="spinner mx-auto mb-4"></div>
            <p>جاري تحميل البيانات...</p>
        </div>

        <!-- Users Grid -->
        <div id="usersGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <!-- Users will be populated here -->
        </div>
    </div>

    <!-- Add/Edit User Modal -->
    <div id="userModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl p-8 w-full max-w-lg max-h-[90vh] overflow-y-auto shadow-2xl border border-gray-200 modal-enter">
            <div class="border-b border-gray-100 pb-4 mb-6">
                <h2 id="modalTitle" class="text-dark-gray text-base font-semibold flex items-center gap-2">
                    <i class="fas fa-user-plus text-gold text-sm"></i>
                    إضافة مستخدم جديد
                </h2>
            </div>
            
            <form id="userForm">
                <div class="mb-6">
                    <label for="userName" class="block mb-1.5 text-dark-gray font-medium text-xs">الاسم الكامل</label>
                    <input 
                        type="text" 
                        id="userName" 
                        placeholder="أدخل الاسم الكامل" 
                        required 
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-md text-sm transition-all duration-150 font-cairo font-normal bg-white focus:outline-none focus:border-gold focus:ring-3 focus:ring-gold-light"
                    />
                </div>
                
                <div class="mb-6">
                    <label for="userPhone" class="block mb-1.5 text-dark-gray font-medium text-xs">رقم الجوال</label>
                    <input 
                        type="tel" 
                        id="userPhone" 
                        placeholder="5xxxxxxxx" 
                        required 
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-md text-sm transition-all duration-150 font-cairo font-normal bg-white focus:outline-none focus:border-gold focus:ring-3 focus:ring-gold-light"
                    />
                </div>
                
                <div class="mb-6">
                    <label for="userGender" class="block mb-1.5 text-dark-gray font-medium text-xs">الجنس</label>
                    <select 
                        id="userGender" 
                        required 
                        class="w-full pl-8 pr-3 py-2.5 border border-gray-300 rounded-md text-sm transition-all duration-150 font-cairo font-normal bg-white focus:outline-none focus:border-gold focus:ring-3 focus:ring-gold-light appearance-none bg-[url('data:image/svg+xml,%3csvg xmlns=%27http://www.w3.org/2000/svg%27 fill=%27none%27 viewBox=%270 0 20 20%27%3e%3cpath stroke=%27%23666%27 stroke-linecap=%27round%27 stroke-linejoin=%27round%27 stroke-width=%271.5%27 d=%27M6 8l4 4 4-4%27/%3e%3c/svg%3e')] bg-[position:left_10px_center] bg-no-repeat bg-[length:14px_10px]"
                    >
                        <option value="">اختر الجنس</option>
                        <option value="ذكر">ذكر</option>
                        <option value="أنثى">أنثى</option>
                        <option value="مؤسسة">مؤسسة</option>
                    </select>
                </div>
                
                <div class="mb-6">
                    <label class="block mb-1.5 text-dark-gray font-medium text-xs">الصلاحيات</label>
                    <div id="permissionsContainer" class="border border-gray-300 rounded-md p-4 max-h-44 overflow-y-auto bg-white">
                        <!-- Permissions will be loaded here -->
                    </div>
                </div>
                
                <div class="flex gap-4 justify-end mt-8 pt-4 border-t border-gray-100">
                    <button 
                        type="button" 
                        class="bg-white text-medium-gray border border-gray-300 px-4 py-2 rounded-md font-medium cursor-pointer transition-all duration-150 font-cairo text-sm hover:bg-light-gray hover:border-medium-gray"
                        onclick="closeUserModal()"
                    >
                        إلغاء
                    </button>
                    <button 
                        type="submit" 
                        class="bg-gold text-white border-none px-4 py-2 rounded-md cursor-pointer text-sm font-medium transition-all duration-300 flex items-center justify-center font-cairo min-w-8 h-7 hover:bg-gold-hover hover:-translate-y-0.5 hover:shadow-sm"
                        id="saveUserBtn"
                    >
                        <i class="fas fa-save ml-1"></i>
                        حفظ
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Global variables
        let users = [];
        let permissions = [];
        let currentEditingUserId = null;

        // API Helper Functions
        async function makeAjaxRequest(action, data = {}) {
            const formData = new FormData();
            formData.append('action', action);
            
            for (const key in data) {
                formData.append(key, data[key]);
            }

            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                return result;
            } catch (error) {
                console.error('Request failed:', error);
                throw error;
            }
        }

        // Load users
        async function loadUsers() {
            try {
                showLoading(true);
                const response = await makeAjaxRequest('load_users');
                if (response.success) {
                    users = response.data || [];
                    renderUsers();
                    hideMessage();
                } else {
                    showMessage('فشل في تحميل المستخدمين.', 'error');
                }
            } catch (error) {
                console.error('Error loading users:', error);
                showMessage('فشل في تحميل المستخدمين. تأكد من اتصال الإنترنت.', 'error');
            } finally {
                showLoading(false);
            }
        }

        // Load permissions
        async function loadPermissions() {
            try {
                const response = await makeAjaxRequest('load_permissions');
                if (response.success) {
                    permissions = response.data || [];
                    renderPermissionsInModal();
                } else {
                    showMessage('فشل في تحميل الصلاحيات.', 'error');
                }
            } catch (error) {
                console.error('Error loading permissions:', error);
                showMessage('فشل في تحميل الصلاحيات.', 'error');
            }
        }

        // Extract permission IDs from API response
        function extractPermissionIds(permissionsData) {
            if (!permissionsData || !Array.isArray(permissionsData)) {
                return [];
            }
            
            return permissionsData.map(permission => {
                if (typeof permission === 'object' && permission.id) {
                    return permission.id;
                }
                return permission;
            }).filter(id => id != null);
        }

        // Get permission names by IDs
        function getPermissionNames(permissionIds) {
            if (!Array.isArray(permissionIds)) {
                return [];
            }
            
            return permissionIds
                .map(id => permissions.find(p => p.id === id))
                .filter(permission => permission)
                .map(permission => permission['<?php echo $fields['permissions']['name']; ?>']);
        }

        // Render users in grid
        function renderUsers(usersToRender = users) {
            const grid = document.getElementById('usersGrid');
            
            if (!usersToRender.length) {
                grid.innerHTML = `
                    <div class="col-span-full text-center py-12 text-medium-gray">
                        <i class="fas fa-users text-4xl text-gray-300 mb-3 block"></i>
                        <h3 class="text-base mb-1.5 text-dark-gray font-semibold">لا توجد مستخدمين</h3>
                        <p class="text-sm font-normal">ابدأ بإضافة أول مستخدم للنظام</p>
                    </div>
                `;
                return;
            }

            grid.innerHTML = usersToRender.map(user => {
                const userName = user['<?php echo $fields['users']['name']; ?>'] || 'بدون اسم';
                const userPhone = user['<?php echo $fields['users']['phone']; ?>'] || 'بدون رقم';
                const userGender = user['<?php echo $fields['users']['gender']; ?>'] || 'غير محدد';
                const userPermissionsData = user['<?php echo $fields['users']['permissions']; ?>'] || [];
                
                const permissionIds = extractPermissionIds(userPermissionsData);
                const permissionNames = getPermissionNames(permissionIds);
                
                const firstLetter = userName.charAt(0).toUpperCase();
                
                const genderIcon = userGender === 'ذكر' ? 'fas fa-male' : 
                                  userGender === 'أنثى' ? 'fas fa-female' : 
                                  userGender === 'مؤسسة' ? 'fas fa-building' : 'fas fa-user';
                
                let displayPhone = userPhone;
                if (userPhone.startsWith('+966')) {
                    displayPhone = '0' + userPhone.substring(4);
                }
                
                const permissionsHtml = permissionNames.length > 0 
                    ? permissionNames.map(name => `
                        <span class="inline-flex items-center gap-1 px-1.5 py-1 bg-blue-50 text-blue-700 rounded-md text-xs font-medium border border-blue-100 leading-tight">
                            <i class="fas fa-shield-alt text-xs"></i>
                            ${name}
                        </span>
                      `).join('')
                    : '<span class="text-medium-gray text-xs italic font-normal">لا توجد صلاحيات مُحددة</span>';

                return `
                    <div class="user-card bg-white rounded-xl p-6 shadow-sm border border-gray-200 transition-all duration-300 relative hover:shadow-md hover:-translate-y-0.5">
                        <div class="flex items-center mb-4 gap-3">
                            <div class="user-avatar w-10 h-10 rounded-md flex items-center justify-center text-white text-base font-semibold flex-shrink-0">
                                ${firstLetter}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-semibold text-dark-gray mb-0.5 overflow-hidden text-ellipsis whitespace-nowrap">
                                    ${userName}
                                </div>
                                <div class="text-xs text-medium-gray flex items-center gap-1.5 font-normal">
                                    <i class="fas fa-phone text-xs"></i>
                                    ${displayPhone}
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-gold-light text-gold rounded-full text-xs font-medium mb-2.5">
                                <i class="${genderIcon} text-xs"></i>
                                ${userGender}
                            </span>
                        </div>
                        
                        <div class="mb-4">
                            <div class="text-xs text-medium-gray mb-1.5 font-medium">الصلاحيات</div>
                            <div class="flex flex-wrap gap-1">
                                ${permissionsHtml}
                            </div>
                        </div>
                        
                        <div class="flex justify-center pt-2.5 border-t border-gray-100">
                            <button 
                                class="bg-blue-500 text-white border-none px-2.5 py-1.5 rounded-md cursor-pointer text-xs font-medium transition-all duration-300 flex items-center justify-center font-cairo min-w-8 h-7 hover:bg-blue-600 hover:-translate-y-0.5 hover:shadow-sm"
                                onclick="editUser(${user.id})" 
                                title="تعديل المستخدم"
                            >
                                <i class="fas fa-edit text-xs"></i>
                            </button>
                        </div>
                    </div>
                `;
            }).join('');
        }

        // Render permissions in modal
        function renderPermissionsInModal() {
            const container = document.getElementById('permissionsContainer');
            
            if (!permissions.length) {
                container.innerHTML = '<p class="text-medium-gray text-center py-4 text-xs">لا توجد صلاحيات متاحة</p>';
                return;
            }

            container.innerHTML = permissions.map(permission => `
                <div class="permission-checkbox flex items-center py-2 px-2 mb-1.5 rounded-md transition-all duration-150 cursor-pointer last:mb-0" onclick="togglePermission(this)">
                    <label class="flex-1 cursor-pointer m-0 text-sm font-normal">${permission['<?php echo $fields['permissions']['name']; ?>']}</label>
                    <input type="checkbox" name="permissions" value="${permission.id}" class="w-auto ml-2 mb-0" />
                </div>
            `).join('');
        }

        // Toggle permission checkbox
        function togglePermission(element) {
            const checkbox = element.querySelector('input[type="checkbox"]');
            checkbox.checked = !checkbox.checked;
        }

        // Open add user modal
        function openAddUserModal() {
            currentEditingUserId = null;
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-plus text-gold text-sm"></i> إضافة مستخدم جديد';
            document.getElementById('saveUserBtn').innerHTML = '<i class="fas fa-save ml-1"></i> حفظ';
            document.getElementById('userForm').reset();
            
            const checkboxes = document.querySelectorAll('input[name="permissions"]');
            checkboxes.forEach(cb => cb.checked = false);
            
            document.getElementById('userModal').classList.remove('hidden');
        }

        // Edit user
        function editUser(userId) {
            const user = users.find(u => u.id === userId);
            if (!user) return;

            currentEditingUserId = userId;
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-edit text-gold text-sm"></i> تعديل المستخدم';
            document.getElementById('saveUserBtn').innerHTML = '<i class="fas fa-save ml-1"></i> تحديث';

            document.getElementById('userName').value = user['<?php echo $fields['users']['name']; ?>'] || '';
            
            let phoneValue = user['<?php echo $fields['users']['phone']; ?>'] || '';
            if (phoneValue.startsWith('+966')) {
                phoneValue = phoneValue.substring(4);
            }
            document.getElementById('userPhone').value = phoneValue;
            
            document.getElementById('userGender').value = user['<?php echo $fields['users']['gender']; ?>'] || '';

            const userPermissionsData = user['<?php echo $fields['users']['permissions']; ?>'] || [];
            const permissionIds = extractPermissionIds(userPermissionsData);
            
            const checkboxes = document.querySelectorAll('input[name="permissions"]');
            checkboxes.forEach(cb => {
                cb.checked = permissionIds.includes(parseInt(cb.value));
            });

            document.getElementById('userModal').classList.remove('hidden');
        }

        // Close modal
        function closeUserModal() {
            document.getElementById('userModal').classList.add('hidden');
        }

        // Handle form submission
        document.getElementById('userForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            let phoneValue = document.getElementById('userPhone').value.trim();
            
            if (phoneValue.startsWith('0')) {
                phoneValue = phoneValue.substring(1);
            }
            if (!phoneValue.startsWith('+966')) {
                phoneValue = '+966' + phoneValue;
            }
            
            const selectedPermissions = Array.from(document.querySelectorAll('input[name="permissions"]:checked'))
                .map(cb => parseInt(cb.value));
            
            const formData = {
                name: document.getElementById('userName').value.trim(),
                phone: phoneValue,
                gender: document.getElementById('userGender').value,
                permissions: JSON.stringify(selectedPermissions)
            };

            if (!formData.name) {
                showMessage('يرجى إدخال الاسم', 'error');
                return;
            }
            if (!document.getElementById('userPhone').value.trim()) {
                showMessage('يرجى إدخال رقم الجوال', 'error');
                return;
            }
            if (!formData.gender) {
                showMessage('يرجى اختيار الجنس', 'error');
                return;
            }

            try {
                showLoading(true);
                
                let response;
                if (currentEditingUserId) {
                    formData.user_id = currentEditingUserId;
                    response = await makeAjaxRequest('update_user', formData);
                } else {
                    response = await makeAjaxRequest('create_user', formData);
                }
                
                if (response.success) {
                    showMessage(response.message, 'success');
                    closeUserModal();
                    await loadUsers();
                } else {
                    showMessage(response.message, 'error');
                }
            } catch (error) {
                showMessage('فشل في حفظ البيانات', 'error');
            } finally {
                showLoading(false);
            }
        });

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase().trim();
            
            if (!searchTerm) {
                renderUsers();
                return;
            }

            const filteredUsers = users.filter(user => {
                const name = (user['<?php echo $fields['users']['name']; ?>'] || '').toLowerCase();
                const phone = (user['<?php echo $fields['users']['phone']; ?>'] || '').toLowerCase();
                const gender = (user['<?php echo $fields['users']['gender']; ?>'] || '').toLowerCase();
                
                const userPermissionsData = user['<?php echo $fields['users']['permissions']; ?>'] || [];
                const permissionIds = extractPermissionIds(userPermissionsData);
                const permissionNames = getPermissionNames(permissionIds);
                const permissionsText = permissionNames.join(' ').toLowerCase();
                
                return name.includes(searchTerm) || 
                       phone.includes(searchTerm) || 
                       gender.includes(searchTerm) ||
                       permissionsText.includes(searchTerm);
            });

            renderUsers(filteredUsers);
        });

        // UI Helper Functions
        function showLoading(show) {
            document.getElementById('loading').classList.toggle('hidden', !show);
        }

        function showMessage(message, type) {
            const icon = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-triangle';
            const bgColor = type === 'success' ? 'bg-green-50' : 'bg-red-50';
            const textColor = type === 'success' ? 'text-green-700' : 'text-red-700';
            const borderColor = type === 'success' ? 'border-green-200' : 'border-red-200';
            
            const messageContainer = document.getElementById('messageContainer');
            messageContainer.innerHTML = `
                <div class="px-3.5 py-2.5 rounded-md mb-4 text-sm flex items-center gap-2 border font-normal ${bgColor} ${textColor} ${borderColor}">
                    <i class="${icon} text-sm"></i>
                    ${message}
                </div>
            `;
            setTimeout(() => hideMessage(), 5000);
        }

        function hideMessage() {
            document.getElementById('messageContainer').innerHTML = '';
        }

        // Close modal when clicking outside
        document.getElementById('userModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeUserModal();
            }
        });

        // Initialize app
        async function initApp() {
            try {
                await loadPermissions();
                await loadUsers();
            } catch (error) {
                console.error('Failed to initialize app:', error);
                showMessage('فشل في تحميل النظام. تأكد من الاتصال بالإنترنت.', 'error');
            }
        }

        // Hide page loader
        function hidePageLoader() {
            setTimeout(() => {
                document.getElementById('pageLoader').style.display = 'none';
            }, 500);
        }

        // Start the app
        document.addEventListener('DOMContentLoaded', function() {
            hidePageLoader();
            initApp();
        });
    </script>
</body>
</html>