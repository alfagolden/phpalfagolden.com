<?php
session_start();

$baserow_url = 'https://base.alfagolden.com';
$api_token = 'h5qAt85gtiJDAzpH51WrXPywhmnhrPWy';

$customers_table_id = 702;
$quotes_table_id = 704;
$actions_table_id = 707;

$customer_fields = [
    'phone' => 6773,
    'name' => 6912,
    'gender' => 6913
];

$quote_fields = [
    'customer' => 6786,
    'created_by' => 6990  // حقل "بواسطة" لربط عرض السعر بالمستخدم الذي أنشأه
];

$action_fields = [
    'user' => 6928,
    'action' => 6929,
    'customers' => 6933,
    'quotes' => 6935
];

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header('Location: ../login.php');
    exit;
}

// فحص وجود quote_id في الرابط للتحقق من وضع التعديل
$quote_id = isset($_GET['quote_id']) ? (int)$_GET['quote_id'] : null;
$is_edit_mode = $quote_id !== null;
$existing_quote = null;
$existing_customer = null;

function makeBaserowRequest($url, $method = 'GET', $data = null) {
    global $api_token;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Token ' . $api_token,
        'Content-Type: application/json'
    ]);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    } elseif ($method === 'PATCH') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code >= 200 && $http_code < 300) {
        return json_decode($response, true);
    } else {
        error_log("Baserow API Error: " . $response);
        return ['error' => true, 'message' => 'خطأ في الاتصال بقاعدة البيانات', 'response' => $response];
    }
}

function getQuoteById($quote_id) {
    global $baserow_url, $quotes_table_id;
    
    $url = $baserow_url . '/api/database/rows/table/' . $quotes_table_id . '/' . $quote_id . '/';
    return makeBaserowRequest($url);
}

function getCustomerById($customer_id) {
    global $baserow_url, $customers_table_id;
    
    $url = $baserow_url . '/api/database/rows/table/' . $customers_table_id . '/' . $customer_id . '/';
    return makeBaserowRequest($url);
}

function logAction($action, $customer_id = null, $quote_id = null) {
    global $baserow_url, $actions_table_id, $action_fields, $user_id;
    
    $data = [
        'field_' . $action_fields['user'] => (int)$user_id,
        'field_' . $action_fields['action'] => $action
    ];
    
    if ($customer_id) {
        $data['field_' . $action_fields['customers']] = (int)$customer_id;
    }
    
    if ($quote_id) {
        $data['field_' . $action_fields['quotes']] = (int)$quote_id;
    }
    
    $url = $baserow_url . '/api/database/rows/table/' . $actions_table_id . '/';
    return makeBaserowRequest($url, 'POST', $data);
}

// في وضع التعديل، استرجاع بيانات عرض السعر والعميل المرتبط به
if ($is_edit_mode) {
    $existing_quote = getQuoteById($quote_id);
    
    if (isset($existing_quote['error']) || !$existing_quote) {
        // عرض السعر غير موجود
        header('Location: 1.php');
        exit;
    }
    
    // استرجاع بيانات العميل المرتبط بعرض السعر
    $customer_link = $existing_quote['field_' . $quote_fields['customer']] ?? null;
    if ($customer_link && is_array($customer_link) && !empty($customer_link)) {
        $customer_id = $customer_link[0]['id']; // في حالة Link to table، يكون المرجع في مصفوفة
        $existing_customer = getCustomerById($customer_id);
        
        if (isset($existing_customer['error'])) {
            $existing_customer = null;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'search_customers':
                $url = $baserow_url . '/api/database/rows/table/' . $customers_table_id . '/?size=200';
                $response = makeBaserowRequest($url);
                
                if (isset($response['error'])) {
                    echo json_encode(['success' => false, 'message' => $response['message']]);
                    exit;
                }
                
                $customers = $response['results'] ?? [];
                $results = [];
                
                foreach ($customers as $customer) {
                    $phone = str_replace('+966', '', $customer['field_' . $customer_fields['phone']] ?? '');
                    $phone_display = $customer['field_' . $customer_fields['phone']] ?? '';
                    
                    // تنسيق رقم الجوال للعرض
                    if (substr($phone_display, 0, 4) === '+966') {
                        $display_formatted = '0' . substr($phone_display, 4);
                    } else {
                        $display_formatted = $phone_display;
                    }
                    
                    $results[] = [
                        'id' => $customer['id'],
                        'name' => $customer['field_' . $customer_fields['name']] ?? '',
                        'phone' => $phone,
                        'phone_display' => $display_formatted,
                        'gender' => $customer['field_' . $customer_fields['gender']] ?? ''
                    ];
                }
                
                echo json_encode(['success' => true, 'customers' => $results]);
                break;
                
            case 'add_customer':
                $name = trim($_POST['name'] ?? '');
                $phone = trim($_POST['phone'] ?? '');
                $gender = $_POST['gender'] ?? '';
                
                if (empty($name) || empty($phone) || empty($gender)) {
                    echo json_encode(['success' => false, 'message' => 'يرجى ملء جميع الحقول المطلوبة']);
                    exit;
                }
                
                $phone = preg_replace('/[^0-9]/', '', $phone);
                if (substr($phone, 0, 1) === '0') {
                    $phone = substr($phone, 1);
                }
                if (!preg_match('/^5[0-9]{8}$/', $phone)) {
                    echo json_encode(['success' => false, 'message' => 'رقم الجوال غير صحيح']);
                    exit;
                }
                $phone_formatted = '+966' . $phone;
                
                $data = [
                    'field_' . $customer_fields['name'] => $name,
                    'field_' . $customer_fields['phone'] => $phone_formatted,
                    'field_' . $customer_fields['gender'] => $gender,
                    'field_6777' => [3] // إضافة الصلاحية 3 تلقائياً عند إنشاء عميل جديد
                ];
                
                $url = $baserow_url . '/api/database/rows/table/' . $customers_table_id . '/';
                $response = makeBaserowRequest($url, 'POST', $data);
                
                if (isset($response['error'])) {
                    echo json_encode(['success' => false, 'message' => $response['message']]);
                    exit;
                }
                
                $action_text = "أضاف عميل جديد - الاسم: {$name}، رقم الجوال: {$phone_formatted}، الجنس: {$gender}";
                logAction($action_text, $response['id']);
                
                echo json_encode([
                    'success' => true, 
                    'customer' => [
                        'id' => $response['id'],
                        'name' => $name,
                        'phone' => $phone,
                        'phone_display' => $phone_formatted,
                        'gender' => $gender
                    ]
                ]);
                break;
                
            case 'update_customer':
                $customer_id = $_POST['customer_id'] ?? '';
                $name = trim($_POST['name'] ?? '');
                $phone = trim($_POST['phone'] ?? '');
                $gender = $_POST['gender'] ?? '';
                
                if (empty($customer_id)) {
                    echo json_encode(['success' => false, 'message' => 'معرف العميل مفقود']);
                    exit;
                }
                
                $data = [];
                $changes = [];
                
                if (!empty($name)) {
                    $data['field_' . $customer_fields['name']] = $name;
                    $changes[] = "الاسم: {$name}";
                }
                if (!empty($phone)) {
                    $phone_formatted = '+966' . $phone;
                    $data['field_' . $customer_fields['phone']] = $phone_formatted;
                    $changes[] = "رقم الجوال: {$phone_formatted}";
                }
                if (!empty($gender)) {
                    $data['field_' . $customer_fields['gender']] = $gender;
                    $changes[] = "الجنس: {$gender}";
                }
                
                if (empty($data)) {
                    echo json_encode(['success' => false, 'message' => 'لا توجد بيانات للتحديث']);
                    exit;
                }
                
                $url = $baserow_url . '/api/database/rows/table/' . $customers_table_id . '/' . $customer_id . '/';
                $response = makeBaserowRequest($url, 'PATCH', $data);
                
                if (isset($response['error'])) {
                    echo json_encode(['success' => false, 'message' => $response['message']]);
                    exit;
                }
                
                $action_text = "عدل بيانات عميل - " . implode('، ', $changes);
                logAction($action_text, $customer_id);
                
                echo json_encode(['success' => true]);
                break;
                
            case 'create_quote':
                $customer_id = $_POST['customer_id'] ?? '';
                
                if (empty($customer_id)) {
                    echo json_encode(['success' => false, 'message' => 'لم يتم اختيار عميل']);
                    exit;
                }
                
                $data = [
                    'field_' . $quote_fields['customer'] => (int)$customer_id,
                    'field_' . $quote_fields['created_by'] => (int)$user_id  // إضافة المستخدم الذي أنشأ عرض السعر
                ];
                
                $url = $baserow_url . '/api/database/rows/table/' . $quotes_table_id . '/';
                $response = makeBaserowRequest($url, 'POST', $data);
                
                if (isset($response['error'])) {
                    echo json_encode(['success' => false, 'message' => $response['message']]);
                    exit;
                }
                
                $action_text = "أنشأ عرض سعر جديد";
                logAction($action_text, $customer_id, $response['id']);
                
                echo json_encode([
                    'success' => true, 
                    'quote_id' => $response['id'],
                    'redirect' => '2.php?quote_id=' . $response['id']
                ]);
                break;
                
            case 'update_quote_customer':
                // تحديث عرض السعر الموجود بعميل جديد أو محدث
                $current_quote_id = $_POST['quote_id'] ?? '';
                $customer_id = $_POST['customer_id'] ?? '';
                
                if (empty($current_quote_id) || empty($customer_id)) {
                    echo json_encode(['success' => false, 'message' => 'معرف عرض السعر أو العميل مفقود']);
                    exit;
                }
                
                $data = [
                    'field_' . $quote_fields['customer'] => (int)$customer_id
                    // لا نحدث حقل "بواسطة" في وضع التعديل
                ];
                
                $url = $baserow_url . '/api/database/rows/table/' . $quotes_table_id . '/' . $current_quote_id . '/';
                $response = makeBaserowRequest($url, 'PATCH', $data);
                
                if (isset($response['error'])) {
                    echo json_encode(['success' => false, 'message' => $response['message']]);
                    exit;
                }
                
                $action_text = "حدث عرض السعر - تم تغيير العميل المرتبط";
                logAction($action_text, $customer_id, $current_quote_id);
                
                echo json_encode([
                    'success' => true,
                    'redirect' => '2.php?quote_id=' . $current_quote_id
                ]);
                break;
        }
    } catch (Exception $e) {
        error_log("PHP Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'حدث خطأ غير متوقع']);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit_mode ? 'تعديل عرض السعر' : 'اختيار العميل'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&family=Tajawal:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- نفس CSS الموجود سابقاً -->
    <style>
        :root {
            --gold: #977e2b;
            --gold-hover: #b89635;
            --gold-light: rgba(151, 126, 43, 0.1);
            --dark-gray: #2c2c2c;
            --medium-gray: #666;
            --light-gray: #f8f9fa;
            --white: #ffffff;
            --radius-sm: 6px;
            --radius-md: 12px;
            --radius-lg: 20px;
            --radius-xl: 25px;
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px rgba(0, 0, 0, 0.1);
            --transition-fast: all 0.15s ease;
            --transition-normal: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --space-sm: 10px;
            --space-md: 20px;
            --space-lg: 30px;
        }
        
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Cairo', 'Tajawal', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--light-gray);
            margin: 0;
            padding: 0;
            line-height: 1.5;
            font-size: 14px;
            color: var(--dark-gray);
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
        
        .spinner {
            width: 24px;
            height: 24px;
            border: 2px solid #e5e7eb;
            border-top: 2px solid var(--gold);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .container {
            max-width: 520px;
            margin: 0 auto;
            padding: var(--space-md);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .main-card {
            background: var(--white);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-md);
            width: 100%;
            max-width: 480px;
            border: 1px solid #e5e7eb;
        }
        
        @media (max-width: 768px) {
            body {
                background: var(--white);
            }
            
            .container {
                padding: 0;
                min-height: 100vh;
                align-items: stretch;
            }
            
            .main-card {
                border-radius: 0;
                box-shadow: none;
                border: none;
                min-height: 100vh;
                max-width: none;
                display: flex;
                flex-direction: column;
            }
            
            .card-header {
                padding: 20px 16px 16px;
                border-bottom: 1px solid #f3f4f6;
                background: var(--white);
            }
            
            .card-body {
                flex: 1;
                padding: 20px 16px;
                background: var(--white);
                display: flex;
                flex-direction: column;
            }
            
            .form-row {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .form-control, .phone-input {
                font-size: 16px;
                padding: 12px;
            }
            
            .form-group-with-edit {
                gap: 12px;
            }
            
            .edit-btn {
                width: 100%;
                height: 44px;
                justify-content: center;
                font-size: 14px;
            }
            
            .edit-btn i {
                margin-left: 6px;
            }
        }
        
        .card-header {
            padding: 24px 24px 16px;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark-gray);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .card-title i {
            color: var(--gold);
        }
        
        .card-body {
            padding: 24px;
        }
        
        .form-group {
            margin-bottom: var(--space-md);
            position: relative;
        }
        
        .form-group-with-edit {
            display: flex;
            align-items: flex-end;
            gap: 8px;
        }
        
        .form-input-container {
            flex: 1;
            position: relative;
        }
        
        .edit-btn {
            padding: 10px;
            background: var(--white);
            border: 1px solid #d1d5db;
            border-radius: var(--radius-sm);
            color: var(--medium-gray);
            cursor: pointer;
            transition: var(--transition-fast);
            min-width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .edit-btn:hover {
            color: var(--gold);
            border-color: var(--gold);
        }
        
        .edit-btn.hidden {
            display: none;
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
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: var(--radius-sm);
            font-size: 14px;
            transition: var(--transition-fast);
            background: var(--white);
            color: var(--dark-gray);
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--gold);
            box-shadow: 0 0 0 3px var(--gold-light);
        }
        
        .form-control:disabled {
            background: #f9fafb;
            color: var(--medium-gray);
        }
        
        .form-select {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23666' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: left 12px center;
            background-repeat: no-repeat;
            background-size: 16px 12px;
            padding-left: 40px;
        }
        
        .phone-wrapper {
            display: flex;
            border: 1px solid #d1d5db;
            border-radius: var(--radius-sm);
            overflow: hidden;
            transition: var(--transition-fast);
            direction: ltr;
        }
        
        .phone-wrapper:focus-within {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px var(--gold-light);
        }
        
        .country-code {
            background: #f9fafb;
            border-right: 1px solid #e5e7eb;
            padding: 10px 12px;
            font-size: 14px;
            color: var(--medium-gray);
            display: flex;
            align-items: center;
            min-width: fit-content;
            order: 1;
        }
        
        .phone-input {
            flex: 1;
            border: none;
            padding: 10px 12px;
            font-size: 14px;
            text-align: left;
            color: var(--dark-gray);
            order: 2;
        }
        
        .phone-input:focus {
            outline: none;
        }
        
        .phone-input:disabled {
            background: #f9fafb;
            color: var(--medium-gray);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        
        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: var(--white);
            border: 1px solid #e5e7eb;
            border-radius: var(--radius-sm);
            box-shadow: var(--shadow-lg);
            z-index: 100;
            max-height: 200px;
            overflow-y: auto;
            display: none;
        }
        
        .search-result-item {
            padding: 12px;
            cursor: pointer;
            border-bottom: 1px solid #f3f4f6;
            transition: var(--transition-fast);
        }
        
        .search-result-item:hover {
            background: var(--gold-light);
        }
        
        .search-result-item:last-child {
            border-bottom: none;
        }
        
        .result-name {
            font-weight: 500;
            color: var(--dark-gray);
            font-size: 14px;
        }
        
        .result-phone {
            font-size: 13px;
            color: var(--medium-gray);
            margin-top: 2px;
        }
        
        .result-gender {
            font-size: 12px;
            color: #9ca3af;
            margin-top: 2px;
            display: none;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 12px 24px;
            border: none;
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition-normal);
            text-decoration: none;
            width: 100%;
        }
        
        .btn-primary {
            background: var(--gold);
            color: var(--white);
        }
        
        .btn-primary:hover {
            background: var(--gold-hover);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .btn-primary:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
        }
        
        .btn.loading {
            pointer-events: none;
        }
        
        .btn .spinner {
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid var(--white);
        }
        
        .status-display {
            background: var(--gold-light);
            border: 1px solid rgba(151, 126, 43, 0.2);
            border-radius: var(--radius-sm);
            padding: 10px 12px;
            margin-bottom: 16px;
            font-size: 13px;
            color: var(--gold);
        }
        
        .status-display.hidden {
            display: none;
        }
        
        .message {
            padding: 12px;
            border-radius: var(--radius-sm);
            margin-bottom: 16px;
            font-size: 13px;
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
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        
        .hidden {
            display: none !important;
        }
        
        @media (max-width: 576px) {
            .form-control, .phone-input {
                font-size: 16px;
                padding: 12px;
            }
        }
        
        @media (max-height: 600px) {
            .container {
                align-items: flex-start;
                padding-top: var(--space-md);
            }
            
            .main-card {
                margin-bottom: var(--space-md);
            }
        }
    </style>
</head>
<body>
    <div id="pageLoader" class="page-loader">
        <div class="spinner"></div>
    </div>

    <div class="container">
        <div class="main-card">
            <div class="card-header">
                <h1 class="card-title">
                    <i class="fas fa-<?php echo $is_edit_mode ? 'edit' : 'users'; ?>"></i>
                    <?php echo $is_edit_mode ? 'تعديل عرض السعر' : 'اختيار العميل'; ?>
                </h1>
            </div>
            
            <div class="card-body">
                <div id="messageContainer"></div>
                <div id="customerStatus" class="status-display hidden"></div>
                
                <form id="customerForm">
                    <?php if ($is_edit_mode): ?>
                        <input type="hidden" id="editQuoteId" value="<?php echo $quote_id; ?>">
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">الاسم</label>
                            <div class="form-group-with-edit">
                                <div class="form-input-container">
                                    <input type="text" id="customerName" class="form-control" 
                                           value="<?php echo $existing_customer ? htmlspecialchars($existing_customer['field_' . $customer_fields['name']] ?? '') : ''; ?>"
                                           placeholder="اسم العميل أو رقم الجوال" autocomplete="off">
                                    <div id="nameSearchResults" class="search-results"></div>
                                </div>
                                <button type="button" id="nameEditBtn" class="edit-btn <?php echo $is_edit_mode && $existing_customer ? '' : 'hidden'; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">الجنس</label>
                            <div class="form-group-with-edit">
                                <div class="form-input-container">
                                    <select id="customerGender" class="form-control form-select">
                                        <option value="">اختر الجنس</option>
                                        <option value="ذكر" <?php echo ($existing_customer && ($existing_customer['field_' . $customer_fields['gender']] ?? '') === 'ذكر') ? 'selected' : ''; ?>>ذكر</option>
                                        <option value="أنثى" <?php echo ($existing_customer && ($existing_customer['field_' . $customer_fields['gender']] ?? '') === 'أنثى') ? 'selected' : ''; ?>>أنثى</option>
                                        <option value="مؤسسة" <?php echo ($existing_customer && ($existing_customer['field_' . $customer_fields['gender']] ?? '') === 'مؤسسة') ? 'selected' : ''; ?>>مؤسسة</option>
                                    </select>
                                </div>
                                <button type="button" id="genderEditBtn" class="edit-btn <?php echo $is_edit_mode && $existing_customer ? '' : 'hidden'; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">رقم الجوال</label>
                        <div class="form-group-with-edit">
                            <div class="form-input-container">
                                <div class="phone-wrapper">
                                    <?php
                                    $phone_value = '';
                                    if ($existing_customer) {
                                        $phone_display = $existing_customer['field_' . $customer_fields['phone']] ?? '';
                                        if (substr($phone_display, 0, 4) === '+966') {
                                            $phone_value = substr($phone_display, 4);
                                        }
                                    }
                                    ?>
                                    <input type="tel" id="customerPhone" class="phone-input" 
                                           value="<?php echo htmlspecialchars($phone_value); ?>"
                                           placeholder="5xxxxxxxx أو اسم العميل" maxlength="9" inputmode="numeric">
                                    <span class="country-code">+966</span>
                                </div>
                                <div id="phoneSearchResults" class="search-results"></div>
                            </div>
                            <button type="button" id="phoneEditBtn" class="edit-btn <?php echo $is_edit_mode && $existing_customer ? '' : 'hidden'; ?>">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" id="proceedBtn" class="btn btn-primary">
                        <span class="btn-text">
                            <?php echo $is_edit_mode ? 'حفظ التعديلات' : 'متابعة إلى الخطوة التالية'; ?>
                        </span>
                        <div class="spinner hidden"></div>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fuse.js@7.1.0/dist/fuse.min.js"></script>

    <script>
class CustomerManager {
    constructor() {
        this.allCustomers = [];
        this.selectedCustomer = null;
        this.isLoading = false;
        this.nameEditMode = false;
        this.phoneEditMode = false;
        this.genderEditMode = false;
        this.fuse = null;
        this.isEditMode = <?php echo $is_edit_mode ? 'true' : 'false'; ?>;
        this.quoteId = <?php echo $quote_id ?? 'null'; ?>;
        
        this.initializeElements();
        this.bindEvents();
        this.loadCustomers();
        
        // في وضع التعديل، تحديد العميل الموجود تلقائياً
        if (this.isEditMode) {
            this.initializeEditMode();
        }
        
        this.hidePageLoader();
    }
    
    initializeEditMode() {
        // في وضع التعديل، قم بتحديد العميل الموجود وتعطيل الحقول
        <?php if ($existing_customer): ?>
        this.selectedCustomer = {
            id: <?php echo $existing_customer['id']; ?>,
            name: '<?php echo htmlspecialchars($existing_customer['field_' . $customer_fields['name']] ?? ''); ?>',
            phone: '<?php echo htmlspecialchars(substr(($existing_customer['field_' . $customer_fields['phone']] ?? ''), 4)); ?>',
            phone_display: '<?php echo htmlspecialchars($existing_customer['field_' . $customer_fields['phone']] ?? ''); ?>',
            gender: '<?php echo htmlspecialchars($existing_customer['field_' . $customer_fields['gender']] ?? ''); ?>'
        };
        
        this.showStatus('وضع التعديل: ' + this.selectedCustomer.name);
        this.enableEditIcons();
        this.disableAllInputs();
        <?php endif; ?>
    }
    
    initializeElements() {
        this.customerName = document.getElementById('customerName');
        this.customerPhone = document.getElementById('customerPhone');
        this.customerGender = document.getElementById('customerGender');
        this.nameEditBtn = document.getElementById('nameEditBtn');
        this.phoneEditBtn = document.getElementById('phoneEditBtn');
        this.genderEditBtn = document.getElementById('genderEditBtn');
        this.nameSearchResults = document.getElementById('nameSearchResults');
        this.phoneSearchResults = document.getElementById('phoneSearchResults');
        this.proceedBtn = document.getElementById('proceedBtn');
        this.messageContainer = document.getElementById('messageContainer');
        this.customerStatus = document.getElementById('customerStatus');
        this.form = document.getElementById('customerForm');
        this.pageLoader = document.getElementById('pageLoader');
    }
    
    hidePageLoader() {
        setTimeout(() => {
            this.pageLoader.style.display = 'none';
        }, 800);
    }
    
    bindEvents() {
        // أحداث حقل الاسم
        this.customerName.addEventListener('input', () => this.handleNameSearch());
        this.customerName.addEventListener('focus', () => this.handleNameSearch());
        this.customerName.addEventListener('blur', (e) => {
            setTimeout(() => {
                if (!this.nameSearchResults.contains(document.activeElement)) {
                    this.hideNameResults();
                }
            }, 200);
        });
        
        // أحداث حقل رقم الجوال
        this.customerPhone.addEventListener('input', (e) => {
            this.handlePhoneInput(e);
            this.handlePhoneSearch();
        });
        this.customerPhone.addEventListener('focus', () => {
            this.handlePhoneSearch();
        });
        this.customerPhone.addEventListener('blur', (e) => {
            setTimeout(() => {
                if (!this.phoneSearchResults.contains(document.activeElement)) {
                    this.hidePhoneResults();
                }
            }, 200);
        });
        
        // أحداث أزرار التعديل
        this.nameEditBtn.addEventListener('click', () => {
            if (this.nameEditMode) {
                this.saveNameChanges();
            } else {
                this.enableNameEdit();
            }
        });
        this.phoneEditBtn.addEventListener('click', () => {
            if (this.phoneEditMode) {
                this.savePhoneChanges();
            } else {
                this.enablePhoneEdit();
            }
        });
        this.genderEditBtn.addEventListener('click', () => {
            if (this.genderEditMode) {
                this.saveGenderChanges();
            } else {
                this.enableGenderEdit();
            }
        });
        
        // حدث إرسال النموذج
        this.form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.proceedToNext();
        });
        
        // إخفاء النتائج عند النقر خارجها
        document.addEventListener('click', (e) => {
            if (!this.customerName.closest('.form-group').contains(e.target)) {
                this.hideNameResults();
            }
            if (!this.customerPhone.closest('.form-group').contains(e.target)) {
                this.hidePhoneResults();
            }
        });
    }
    
    async loadCustomers() {
        try {
            const response = await this.makeRequest('search_customers', {});
            
            if (response.success) {
                this.allCustomers = response.customers;
                this.initializeFuse();
            } else {
                this.showMessage('خطأ في تحميل العملاء: ' + response.message, 'error');
            }
        } catch (error) {
            this.showMessage('خطأ في تحميل العملاء', 'error');
        }
    }
    
    initializeFuse() {
        const options = {
            keys: [
                { name: 'name', weight: 0.7 },
                { name: 'phone', weight: 0.3 }
            ],
            threshold: 0.4,
            includeScore: true,
            minMatchCharLength: 2,
            ignoreLocation: true
        };
        
        this.fuse = new Fuse(this.allCustomers, options);
    }
    
    // دالة موحدة للبحث من حقل الاسم
    handleNameSearch() {
        if (this.nameEditMode) return;
        
        const searchTerm = this.customerName.value.trim();
        
        if (searchTerm.length < 2) {
            this.hideNameResults();
            if (!this.isEditMode) {
                this.clearCustomerSelection();
            }
            return;
        }
        
        // تحديد نوع البحث
        const isPhoneSearch = /^[0-9]+$/.test(searchTerm);
        
        if (isPhoneSearch) {
            this.searchCustomersFromName(searchTerm, 'phone');
        } else {
            this.searchCustomersFromName(searchTerm, 'name');
        }
    }
    
    // دالة موحدة للبحث من حقل رقم الجوال
    handlePhoneSearch() {
        if (this.phoneEditMode) return;
        
        const searchTerm = this.customerPhone.value.trim();
        
        if (searchTerm.length < 2) {
            this.hidePhoneResults();
            return;
        }
        
        // تحديد نوع البحث
        const isPhoneSearch = /^[0-9]+$/.test(searchTerm);
        
        if (isPhoneSearch) {
            this.searchCustomersFromPhone(searchTerm, 'phone');
        } else {
            this.searchCustomersFromPhone(searchTerm, 'name');
        }
    }
    
    // البحث من حقل الاسم
    searchCustomersFromName(searchTerm, searchType) {
        let results = [];
        
        if (searchType === 'phone') {
            // البحث برقم الجوال
            results = this.allCustomers.filter(customer => 
                customer.phone && customer.phone.includes(searchTerm)
            );
            
            // إذا كان الرقم مكتمل (9 أرقام) والبحث من حقل الاسم
            if (searchTerm.length === 9 && searchTerm.startsWith('5')) {
                const exactMatch = results.find(customer => customer.phone === searchTerm);
                if (exactMatch) {
                    this.selectExistingCustomer(exactMatch);
                    this.hideNameResults();
                    return;
                }
            }
        } else {
            // البحث بالاسم باستخدام Fuse.js
            if (this.fuse) {
                const fuseResults = this.fuse.search(searchTerm);
                results = fuseResults.map(result => result.item);
            }
        }
        
        if (results.length > 0) {
            this.showNameResults(results.slice(0, 5), searchType);
        } else {
            this.hideNameResults();
            if (!this.isEditMode) {
                this.showStatus('عميل جديد - أكمل البيانات للإضافة');
            }
        }
    }
    
    // البحث من حقل رقم الجوال
    searchCustomersFromPhone(searchTerm, searchType) {
        let results = [];
        
        if (searchType === 'phone') {
            // البحث برقم الجوال
            results = this.allCustomers.filter(customer => 
                customer.phone && customer.phone.includes(searchTerm)
            );
            
            // إذا كان الرقم مكتمل (9 أرقام)، تحديد تلقائي
            if (searchTerm.length === 9 && searchTerm.startsWith('5')) {
                const exactMatch = results.find(customer => customer.phone === searchTerm);
                if (exactMatch) {
                    this.selectExistingCustomer(exactMatch);
                    this.hidePhoneResults();
                    return;
                }
            }
        } else {
            // البحث بالاسم باستخدام Fuse.js
            if (this.fuse) {
                const fuseResults = this.fuse.search(searchTerm);
                results = fuseResults.map(result => result.item);
            }
        }
        
        if (results.length > 0) {
            this.showPhoneResults(results.slice(0, 5), searchType);
        } else {
            this.hidePhoneResults();
            if (searchType === 'phone' && searchTerm.length >= 5 && !this.isEditMode) {
                this.showStatus('رقم جوال جديد - أكمل البيانات للإضافة');
            }
        }
    }
    
    showNameResults(results, searchType) {
        const html = results.map(customer => {
            let displayPhone = customer.phone_display || '';
            if (displayPhone.startsWith('+966')) {
                displayPhone = '0' + displayPhone.substring(4);
            }
            
            // ترتيب العرض حسب نوع البحث
            if (searchType === 'phone') {
                return `
                    <div class="search-result-item" onclick="customerManager.selectCustomerFromResults(${customer.id})">
                        <div class="result-phone">${displayPhone || 'غير محدد'}</div>
                        <div class="result-name">${customer.name || 'غير محدد'}</div>
                    </div>
                `;
            } else {
                return `
                    <div class="search-result-item" onclick="customerManager.selectCustomerFromResults(${customer.id})">
                        <div class="result-name">${customer.name || 'غير محدد'}</div>
                        <div class="result-phone">${displayPhone || 'غير محدد'}</div>
                    </div>
                `;
            }
        }).join('');
        
        this.nameSearchResults.innerHTML = html;
        this.nameSearchResults.style.display = 'block';
    }
    
    showPhoneResults(results, searchType) {
        const html = results.map(customer => {
            let displayPhone = customer.phone_display || '';
            if (displayPhone.startsWith('+966')) {
                displayPhone = '0' + displayPhone.substring(4);
            }
            
            // ترتيب العرض حسب نوع البحث
            if (searchType === 'phone') {
                return `
                    <div class="search-result-item" onclick="customerManager.selectCustomerFromResults(${customer.id})">
                        <div class="result-phone">${displayPhone || 'غير محدد'}</div>
                        <div class="result-name">${customer.name || 'غير محدد'}</div>
                    </div>
                `;
            } else {
                return `
                    <div class="search-result-item" onclick="customerManager.selectCustomerFromResults(${customer.id})">
                        <div class="result-name">${customer.name || 'غير محدد'}</div>
                        <div class="result-phone">${displayPhone || 'غير محدد'}</div>
                    </div>
                `;
            }
        }).join('');
        
        this.phoneSearchResults.innerHTML = html;
        this.phoneSearchResults.style.display = 'block';
    }
    
    hideNameResults() {
        this.nameSearchResults.style.display = 'none';
    }
    
    hidePhoneResults() {
        this.phoneSearchResults.style.display = 'none';
    }
    
    selectCustomerFromResults(customerId) {
        const customer = this.allCustomers.find(c => c.id === customerId);
        if (customer) {
            this.selectExistingCustomer(customer);
            this.hideNameResults();
            this.hidePhoneResults();
        }
    }
    
    selectExistingCustomer(customer) {
        this.selectedCustomer = customer;
        this.customerName.value = customer.name || '';
        
        // تنسيق رقم الجوال للعرض في الحقل
        let phoneValue = customer.phone || '';
        if (phoneValue.length === 9 && phoneValue.startsWith('5')) {
            phoneValue = phoneValue;
        } else if (customer.phone_display && customer.phone_display.startsWith('+966')) {
            phoneValue = customer.phone_display.substring(4);
        }
        this.customerPhone.value = phoneValue;
        
        // حفظ الرقم المنسق مع العميل المختار
        this.selectedCustomer.displayPhone = phoneValue;
        
        this.customerGender.value = customer.gender || '';
        
        this.showStatus('تم العثور على العميل: ' + (customer.name || 'غير محدد'));
        this.enableEditIcons();
        this.disableAllInputs();
    }
    
    enableEditIcons() {
        this.nameEditBtn.classList.remove('hidden');
        this.phoneEditBtn.classList.remove('hidden');
        this.genderEditBtn.classList.remove('hidden');
        this.updateButtonIcon(this.nameEditBtn, false);
        this.updateButtonIcon(this.phoneEditBtn, false);
        this.updateButtonIcon(this.genderEditBtn, false);
    }
    
    disableAllInputs() {
        this.customerName.disabled = true;
        this.customerPhone.disabled = true;
        this.customerGender.disabled = true;
        
        document.querySelector('.phone-wrapper').classList.add('disabled');
        
        this.nameEditMode = false;
        this.phoneEditMode = false;
        this.genderEditMode = false;
    }
    
    updateButtonIcon(button, isEditMode) {
        const icon = button.querySelector('i');
        if (isEditMode) {
            icon.className = 'fas fa-check';
        } else {
            icon.className = 'fas fa-edit';
        }
    }
    
    enableNameEdit() {
        this.customerName.disabled = false;
        this.customerName.focus();
        this.nameEditMode = true;
        this.updateButtonIcon(this.nameEditBtn, true);
        this.showStatus('وضع تعديل الاسم - اضغط على علامة الصح للحفظ');
    }
    
    enablePhoneEdit() {
        this.customerPhone.disabled = false;
        document.querySelector('.phone-wrapper').classList.remove('disabled');
        this.phoneEditMode = true;
        this.updateButtonIcon(this.phoneEditBtn, true);
        this.showStatus('وضع تعديل رقم الجوال - اضغط على علامة الصح للحفظ');
        
        setTimeout(() => {
            this.customerPhone.focus();
            this.customerPhone.select();
        }, 100);
    }
    
    enableGenderEdit() {
        this.customerGender.disabled = false;
        this.customerGender.focus();
        this.genderEditMode = true;
        this.updateButtonIcon(this.genderEditBtn, true);
        this.showStatus('وضع تعديل الجنس - اضغط على علامة الصح للحفظ');
    }
    
    async saveNameChanges() {
        if (!this.nameEditMode || !this.selectedCustomer) return;
        
        const newName = this.customerName.value.trim();
        if (newName === this.selectedCustomer.name) {
            this.customerName.disabled = true;
            this.nameEditMode = false;
            this.updateButtonIcon(this.nameEditBtn, false);
            this.showStatus(this.isEditMode ? 'وضع التعديل: ' + newName : 'تم العثور على العميل: ' + newName);
            return;
        }
        
        if (newName) {
            try {
                const response = await this.makeRequest('update_customer', {
                    customer_id: this.selectedCustomer.id,
                    name: newName
                });
                
                if (response.success) {
                    this.selectedCustomer.name = newName;
                    const customerIndex = this.allCustomers.findIndex(c => c.id === this.selectedCustomer.id);
                    if (customerIndex !== -1) {
                        this.allCustomers[customerIndex].name = newName;
                    }
                    this.showMessage('تم تحديث اسم العميل بنجاح', 'success');
                    this.showStatus(this.isEditMode ? 'وضع التعديل: ' + newName : 'تم تحديث العميل: ' + newName);
                } else {
                    this.showMessage('خطأ في تحديث الاسم: ' + response.message, 'error');
                    this.customerName.value = this.selectedCustomer.name;
                }
            } catch (error) {
                this.showMessage('خطأ في تحديث الاسم', 'error');
                this.customerName.value = this.selectedCustomer.name;
            }
        }
        
        this.customerName.disabled = true;
        this.nameEditMode = false;
        this.updateButtonIcon(this.nameEditBtn, false);
    }
    
    async savePhoneChanges() {
        if (!this.phoneEditMode || !this.selectedCustomer) return;
        
        const newPhone = this.customerPhone.value.trim();
        const currentPhone = this.selectedCustomer.displayPhone || this.selectedCustomer.phone || '';
        
        if (newPhone === currentPhone) {
            this.customerPhone.disabled = true;
            document.querySelector('.phone-wrapper').classList.add('disabled');
            this.phoneEditMode = false;
            this.updateButtonIcon(this.phoneEditBtn, false);
            this.showStatus(this.isEditMode ? 'وضع التعديل: ' + this.selectedCustomer.name : 'تم العثور على العميل: ' + this.selectedCustomer.name);
            return;
        }
        
        if (newPhone && /^5[0-9]{8}$/.test(newPhone)) {
            // التحقق من عدم وجود رقم الجوال لدى عميل آخر
            const existingCustomer = this.allCustomers.find(customer => 
                customer.phone === newPhone && customer.id !== this.selectedCustomer.id
            );
            
            if (existingCustomer) {
                this.showMessage('رقم الجوال مسجل لعميل آخر: ' + existingCustomer.name, 'error');
                this.selectExistingCustomer(existingCustomer);
                return;
            }
            
            try {
                const response = await this.makeRequest('update_customer', {
                    customer_id: this.selectedCustomer.id,
                    phone: newPhone
                });
                
                if (response.success) {
                    this.selectedCustomer.phone = newPhone;
                    this.selectedCustomer.displayPhone = newPhone;
                    this.selectedCustomer.phone_display = '+966' + newPhone;
                    const customerIndex = this.allCustomers.findIndex(c => c.id === this.selectedCustomer.id);
                    if (customerIndex !== -1) {
                        this.allCustomers[customerIndex].phone = newPhone;
                        this.allCustomers[customerIndex].phone_display = '0' + newPhone;
                    }
                    this.showMessage('تم تحديث رقم الجوال بنجاح', 'success');
                    this.showStatus(this.isEditMode ? 'وضع التعديل: ' + this.selectedCustomer.name : 'تم تحديث العميل: ' + this.selectedCustomer.name);
                } else {
                    this.showMessage('خطأ في تحديث رقم الجوال: ' + response.message, 'error');
                    this.customerPhone.value = currentPhone;
                }
            } catch (error) {
                this.showMessage('خطأ في تحديث رقم الجوال', 'error');
                this.customerPhone.value = currentPhone;
            }
        } else {
            this.showMessage('رقم الجوال غير صحيح', 'error');
            this.customerPhone.value = currentPhone;
        }
        
        this.customerPhone.disabled = true;
        document.querySelector('.phone-wrapper').classList.add('disabled');
        this.phoneEditMode = false;
        this.updateButtonIcon(this.phoneEditBtn, false);
    }
    
    async saveGenderChanges() {
        if (!this.genderEditMode && !this.selectedCustomer) return;
        
        if (!this.customerGender.value) return;
        
        if (this.customerGender.value === this.selectedCustomer.gender) {
            if (this.genderEditMode) {
                this.customerGender.disabled = true;
                this.genderEditMode = false;
                this.updateButtonIcon(this.genderEditBtn, false);
                this.showStatus(this.isEditMode ? 'وضع التعديل: ' + this.selectedCustomer.name : 'تم العثور على العميل: ' + this.selectedCustomer.name);
            }
            return;
        }
        
        try {
            const response = await this.makeRequest('update_customer', {
                customer_id: this.selectedCustomer.id,
                gender: this.customerGender.value
            });
            
            if (response.success) {
                this.selectedCustomer.gender = this.customerGender.value;
                const customerIndex = this.allCustomers.findIndex(c => c.id === this.selectedCustomer.id);
                if (customerIndex !== -1) {
                    this.allCustomers[customerIndex].gender = this.customerGender.value;
                }
                this.showMessage('تم تحديث جنس العميل بنجاح', 'success');
                this.showStatus(this.isEditMode ? 'وضع التعديل: ' + this.selectedCustomer.name : 'تم تحديث العميل: ' + this.selectedCustomer.name);
            } else {
                this.showMessage('خطأ في تحديث الجنس: ' + response.message, 'error');
                this.customerGender.value = this.selectedCustomer.gender || '';
            }
        } catch (error) {
            this.showMessage('خطأ في تحديث الجنس', 'error');
            this.customerGender.value = this.selectedCustomer.gender || '';
        }
        
        if (this.genderEditMode) {
            this.customerGender.disabled = true;
            this.genderEditMode = false;
            this.updateButtonIcon(this.genderEditBtn, false);
        }
    }
    
    clearCustomerSelection() {
        if (this.isEditMode) return; // لا تمسح التحديد في وضع التعديل
        
        this.selectedCustomer = null;
        this.customerName.disabled = false;
        this.customerPhone.disabled = false;
        this.customerGender.disabled = false;
        
        document.querySelector('.phone-wrapper').classList.remove('disabled');
        
        this.nameEditMode = false;
        this.phoneEditMode = false;
        this.genderEditMode = false;
        this.nameEditBtn.classList.add('hidden');
        this.phoneEditBtn.classList.add('hidden');
        this.genderEditBtn.classList.add('hidden');
        this.hideStatus();
    }
    
    // دالة موحدة لمعالجة إدخال رقم الجوال
    handlePhoneInput(e) {
        const isNumericInput = /^[0-9]*$/.test(e.target.value);
        
        if (isNumericInput) {
            // إذا كان رقمياً، طبق قواعد تنسيق الأرقام
            this.formatPhoneInput(e);
        } else {
            // إذا كان نصاً، اتركه كما هو (للبحث بالاسم)
            // لا حاجة لتنسيق
        }
    }
    
    formatPhoneInput(e) {
        let value = e.target.value.replace(/[^0-9]/g, '');
        
        if (value.startsWith('0')) {
            value = value.substring(1);
        }
        
        if (value.length > 0 && value[0] !== '5') {
            this.showMessage('رقم الجوال يجب أن يبدأ بالرقم 5', 'error');
            value = '';
        }
        
        if (value.length > 9) {
            value = value.substring(0, 9);
        }
        
        e.target.value = value;
        this.clearMessage();
    }
    
    async proceedToNext() {
        const name = this.customerName.value.trim();
        const phone = this.customerPhone.value.trim();
        const gender = this.customerGender.value;
        
        if (!name || !phone || !gender) {
            this.showMessage('يرجى ملء جميع الحقول المطلوبة', 'error');
            return;
        }
        
        // التحقق من صحة رقم الجوال فقط إذا كان رقمياً
        if (/^[0-9]+$/.test(phone) && !/^5[0-9]{8}$/.test(phone)) {
            this.showMessage('رقم الجوال غير صحيح', 'error');
            return;
        }
        
        this.setLoading(true);
        
        try {
            let customerId;
            
            if (this.selectedCustomer) {
                customerId = this.selectedCustomer.id;
                
                if (this.selectedCustomer.gender !== gender) {
                    await this.saveGenderChanges();
                }
            } else {
                // في وضع التعديل لا يجب إضافة عميل جديد
                if (this.isEditMode) {
                    this.showMessage('يجب اختيار عميل موجود في وضع التعديل', 'error');
                    return;
                }
                
                const addResponse = await this.makeRequest('add_customer', {
                    name, phone, gender
                });
                
                if (!addResponse.success) {
                    this.showMessage('خطأ في إضافة العميل: ' + addResponse.message, 'error');
                    return;
                }
                
                customerId = addResponse.customer.id;
                this.showMessage('تم إضافة العميل بنجاح', 'success');
            }
            
            if (this.isEditMode) {
                // في وضع التعديل، حدث عرض السعر الموجود
                const updateResponse = await this.makeRequest('update_quote_customer', {
                    quote_id: this.quoteId,
                    customer_id: customerId
                });
                
                if (updateResponse.success) {
                    this.showMessage('تم حفظ التعديلات بنجاح', 'success');
                    setTimeout(() => {
                        window.location.href = updateResponse.redirect;
                    }, 1000);
                } else {
                    this.showMessage('خطأ في حفظ التعديلات: ' + updateResponse.message, 'error');
                }
            } else {
                // في وضع الإنشاء، أنشئ عرض سعر جديد
                const quoteResponse = await this.makeRequest('create_quote', {
                    customer_id: customerId
                });
                
                if (quoteResponse.success) {
                    this.showMessage('تم إنشاء عرض السعر بنجاح', 'success');
                    setTimeout(() => {
                        window.location.href = quoteResponse.redirect;
                    }, 1000);
                } else {
                    this.showMessage('خطأ في إنشاء عرض السعر: ' + quoteResponse.message, 'error');
                }
            }
            
        } catch (error) {
            this.showMessage('حدث خطأ غير متوقع', 'error');
        } finally {
            this.setLoading(false);
        }
    }
    
    async makeRequest(action, data) {
        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', action);
        
        Object.keys(data).forEach(key => {
            formData.append(key, data[key]);
        });
        
        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        
        return await response.json();
    }
    
    showMessage(message, type) {
        const icon = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
        this.messageContainer.innerHTML = `
            <div class="message ${type}">
                <i class="${icon}"></i>
                ${message}
            </div>
        `;
        setTimeout(() => this.clearMessage(), 5000);
    }
    
    clearMessage() {
        this.messageContainer.innerHTML = '';
    }
    
    showStatus(status) {
        this.customerStatus.textContent = status;
        this.customerStatus.classList.remove('hidden');
    }
    
    hideStatus() {
        this.customerStatus.classList.add('hidden');
    }
    
    setLoading(loading) {
        const btnText = this.proceedBtn.querySelector('.btn-text');
        const spinner = this.proceedBtn.querySelector('.spinner');
        
        if (loading) {
            this.proceedBtn.classList.add('loading');
            this.proceedBtn.disabled = true;
            btnText.style.display = 'none';
            spinner.classList.remove('hidden');
        } else {
            this.proceedBtn.classList.remove('loading');
            this.proceedBtn.disabled = false;
            btnText.style.display = 'inline';
            spinner.classList.add('hidden');
        }
    }
}

let customerManager;

document.addEventListener('DOMContentLoaded', () => {
    customerManager = new CustomerManager();
});
    </script>
</body>
</html>