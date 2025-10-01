<?php
// --- 1. PHP Backend Logic (Handles AJAX requests from the page itself) ---

// Start session to get user_id
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle AJAX requests for getting and updating data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    // Use a placeholder for testing if session is not set
    // In a real environment, you would have a login system setting this.
    // For this example, let's assume a user_id is in the session.
    $user_id = $_SESSION['user_id'] ?? null; 

    if (!$user_id) {
        // If there is no user_id, return an error.
        // The user should be redirected to login.
        echo json_encode(['success' => false, 'message' => 'انتهت الجلسة، يرجى تسجيل الدخول مرة أخرى.']);
        exit;
    }
    
    $action = $_POST['action'] ?? '';
    
    // Action to get user data from the API
    if ($action === 'get_user_data') {
        $url = "https://base.alfagolden.com/api/database/rows/table/702/{$user_id}/";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Token h5qAt85gtiJDAzpH51WrXPywhmnhrPWy'
        ]);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200) {
             echo json_encode(['success' => false, 'message' => 'خطأ في جلب البيانات من المصدر.']);
             exit;
        }
        
        $data = json_decode($response, true);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'name' => $data['field_6912'] ?? '',
                'gender' => $data['field_6913'] ?? 'ذكر',
                'phone' => $data['field_6773'] ?? ''
            ]
        ]);
    }
    
    // Action to update user data via the API
    if ($action === 'update_user_data') {
        $name = trim($_POST['name'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        
        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'حقل الاسم لا يمكن أن يكون فارغاً.']);
            exit;
        }
        
        $url = "https://base.alfagolden.com/api/database/rows/table/702/{$user_id}/";
        
        $updateData = [
            'field_6912' => $name,
            'field_6913' => $gender
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Token h5qAt85gtiJDAzpH51WrXPywhmnhrPWy',
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($updateData));
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            echo json_encode(['success' => true, 'message' => 'تم تحديث البيانات بنجاح.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء تحديث البيانات.']);
        }
    }
    
    exit; // Stop execution after handling AJAX
}

// --- 2. HTML and Frontend Logic ---
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>البيانات الشخصية</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        /* --- CSS from Design Guide --- */
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
            --error: #dc3545;
        }

        body {
            font-family: 'Cairo', sans-serif;
            font-size: 14px;
            line-height: 1.5;
            direction: rtl;
            background: var(--light-gray);
            color: var(--dark-gray);
            margin: 0;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        /* --- Components from Design Guide --- */
        .card {
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border-color);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-color);
            background: var(--light-gray);
        }

        .card-body {
            padding: 24px;
        }

        .card-footer {
            padding: 16px 24px;
            border-top: 1px solid var(--border-color);
            background: var(--light-gray);
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        
        dd#viewPhone {
    direction: ltr;
    text-align: end;
}
        
        
        
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--gold);
            color: var(--white);
        }

        .btn-primary:hover {
            background: var(--gold-hover);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #6c757d;
            color: var(--white);
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn:disabled {
            background-color: #ccc;
            cursor: not-allowed;
            transform: none;
        }
        
        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: var(--medium-gray);
            margin-bottom: 6px;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Cairo', sans-serif;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--gold);
            box-shadow: 0 0 0 3px var(--gold-light);
        }

        .form-control:disabled {
            background-color: var(--light-gray);
            color: var(--medium-gray);
        }

        .modern-table {
            width: 100%;
            border-collapse: collapse;
        }

        /* Using a definition list for better semantics */
        .data-list {
            display: grid;
            grid-template-columns: 120px 1fr;
            gap: 16px;
        }

        .data-list dt {
            font-weight: 600;
            color: var(--medium-gray);
        }

        .data-list dd {
            margin: 0;
            font-weight: 500;
        }

        .message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .message.success {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .message.error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .spinner {
            width: 24px;
            height: 24px;
            border: 2px solid var(--border-color);
            border-top: 2px solid var(--gold);
            border-radius: 50%;
            animation: spin 1s linear infinite;
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
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        @media (max-width: 768px) {
            body {
                padding: 0;
            }
            .container {
                padding: 0;
            }
            .card {
                border-radius: 0;
                margin: 0;
                border-left: none;
                border-right: none;
            }
            .form-control {
                font-size: 16px; /* Prevent zoom on iOS */
            }
        }
    </style>
</head>
<body>

    <div class="page-loader" id="pageLoader">
        <div class="spinner"></div>
    </div>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">
                    <i class="fas fa-user-circle"></i>
                    <span>البيانات الشخصية</span>
                </h1>
            </div>
            <div class="card-body">
                <div id="messageContainer"></div>

                <!-- View Mode: Displays user data -->
                <div id="viewMode">
                    <dl class="data-list">
                        <dt>الاسم:</dt>
                        <dd id="viewName"></dd>
                        
                        <dt>الجنس:</dt>
                        <dd id="viewGender"></dd>

                        <dt>رقم الجوال:</dt>
                        <dd id="viewPhone"></dd>
                    </dl>
                </div>

                <!-- Edit Mode: Form for updating data (hidden by default) -->
                <div id="editMode" style="display:none;">
                    <form id="profileForm">
                        <div class="form-group">
                            <label for="formPhone" class="form-label">رقم الجوال</label>
                            <input type="text" id="formPhone" class="form-control" disabled>
                        </div>
                        <div class="form-group">
                            <label for="formName" class="form-label">الاسم</label>
                            <input type="text" id="formName" class="form-control" placeholder="أدخل اسمك الكامل">
                        </div>
                        <div class="form-group">
                            <label for="formGender" class="form-label">الجنس</label>
                            <select id="formGender" class="form-control">
                                <option value="ذكر">ذكر</option>
                                <option value="أنثى">أنثى</option>
                                <option value="مؤسسة">مؤسسة</option>
                            </select>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card-footer">
                <!-- Buttons will be dynamically shown/hidden here -->
                <div id="viewModeButtons">
                    <button id="editBtn" class="btn btn-primary">
                        <i class="fas fa-edit"></i>
                        <span>تعديل</span>
                    </button>
                </div>
                 <div id="editModeButtons" style="display:none;">
                    <button id="cancelBtn" class="btn btn-secondary">
                        <span>إلغاء</span>
                    </button>
                    <button id="saveBtn" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        <span>حفظ التغييرات</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Element References ---
    const pageLoader = document.getElementById('pageLoader');
    const messageContainer = document.getElementById('messageContainer');
    
    const viewMode = document.getElementById('viewMode');
    const editMode = document.getElementById('editMode');
    
    const viewModeButtons = document.getElementById('viewModeButtons');
    const editModeButtons = document.getElementById('editModeButtons');
    
    const editBtn = document.getElementById('editBtn');
    const saveBtn = document.getElementById('saveBtn');
    const cancelBtn = document.getElementById('cancelBtn');

    // View elements
    const viewName = document.getElementById('viewName');
    const viewGender = document.getElementById('viewGender');
    const viewPhone = document.getElementById('viewPhone');
    
    // Form elements
    const profileForm = document.getElementById('profileForm');
    const formName = document.getElementById('formName');
    const formGender = document.getElementById('formGender');
    const formPhone = document.getElementById('formPhone');

    let originalData = {};

    // --- Functions ---
    
    /**
     * Toggles between 'view' and 'edit' modes.
     * @param {string} mode - The mode to switch to ('view' or 'edit').
     */
    function toggleMode(mode) {
        clearMessages();
        if (mode === 'edit') {
            viewMode.style.display = 'none';
            viewModeButtons.style.display = 'none';
            editMode.style.display = 'block';
            editModeButtons.style.display = 'flex';
        } else { // 'view'
            editMode.style.display = 'none';
            editModeButtons.style.display = 'none';
            viewMode.style.display = 'block';
            viewModeButtons.style.display = 'block';
            
            // Reset form to original data on cancel
            formName.value = originalData.name || '';
            formGender.value = originalData.gender || 'ذكر';
        }
    }

    /**
     * Displays a message to the user.
     * @param {string} text - The message content.
     * @param {string} type - 'success' or 'error'.
     */
    function showMessage(text, type) {
        clearMessages();
        const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${type}`;
        messageDiv.innerHTML = `<i class="fas ${icon}"></i> <span>${text}</span>`;
        messageContainer.appendChild(messageDiv);
        
        // Auto-hide message after 5 seconds
        setTimeout(clearMessages, 5000);
    }
    
    function clearMessages() {
        messageContainer.innerHTML = '';
    }

    /**
     * Fetches user data and populates the page.
     */
    async function loadUserData() {
        pageLoader.style.display = 'flex';
        
        try {
            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append('action', 'get_user_data');

            const response = await fetch('', { // Post to the same page
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                originalData = result.data;
                // Populate view mode
                viewName.textContent = result.data.name || 'غير محدد';
                viewGender.textContent = result.data.gender || 'غير محدد';
                viewPhone.textContent = result.data.phone || 'غير محدد';
                
                // Populate edit mode form
                formName.value = result.data.name || '';
                formGender.value = result.data.gender || 'ذكر';
                formPhone.value = result.data.phone || 'لا يمكن تغييره';

            } else {
                showMessage(result.message || 'حدث خطأ غير متوقع.', 'error');
                // Hide buttons if data loading fails
                editBtn.style.display = 'none';
            }
        } catch (error) {
            console.error('Fetch Error:', error);
            showMessage('فشل الاتصال بالخادم. يرجى المحاولة مرة أخرى.', 'error');
        } finally {
            pageLoader.style.display = 'none';
        }
    }

    /**
     * Handles the save button click to update user data.
     */
    async function handleSave() {
        const name = formName.value.trim();
        if (!name) {
            showMessage('حقل الاسم مطلوب.', 'error');
            return;
        }

        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span class="spinner" style="width:16px; height:16px; border-width:2px; border-top-color: white;"></span> <span>جاري الحفظ...</span>';

        try {
            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append('action', 'update_user_data');
            formData.append('name', name);
            formData.append('gender', formGender.value);

            const response = await fetch('', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            
            if (result.success) {
                showMessage(result.message, 'success');
                
                // Update the view with new data
                originalData.name = name;
                originalData.gender = formGender.value;
                viewName.textContent = name;
                viewGender.textContent = formGender.value;
                
                setTimeout(() => toggleMode('view'), 1500); // Switch back to view after a delay
            } else {
                showMessage(result.message, 'error');
            }

        } catch (error) {
            showMessage('فشل الاتصال بالخادم. يرجى المحاولة مرة أخرى.', 'error');
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="fas fa-save"></i> <span>حفظ التغييرات</span>';
        }
    }

    // --- Event Listeners ---
    editBtn.addEventListener('click', () => toggleMode('edit'));
    cancelBtn.addEventListener('click', () => toggleMode('view'));
    saveBtn.addEventListener('click', handleSave);

    // Prevent form submission on enter key
    profileForm.addEventListener('submit', (e) => {
        e.preventDefault();
        handleSave();
    });

    // --- Initial Load ---
    loadUserData();
});
</script>

</body>
</html>