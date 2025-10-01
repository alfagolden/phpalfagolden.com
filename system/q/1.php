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
    'gender' => 6913,
    'prefix' => 7145,  // قبل الاسم
    'suffix' => 7146,  // بعد الاسم
    'care_name' => 7142,    // ع-الاسم
    'care_prefix' => 7143,  // ع-قبل الاسم
    'care_suffix' => 7144   // ع-بعد الاسم
];

$quote_fields = [
    'customer' => 6786,
    'created_by' => 6990
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

if ($is_edit_mode) {
    $existing_quote = getQuoteById($quote_id);
    
    if (isset($existing_quote['error']) || !$existing_quote) {
        header('Location: 1.php');
        exit;
    }
    
    $customer_link = $existing_quote['field_' . $quote_fields['customer']] ?? null;
    if ($customer_link && is_array($customer_link) && !empty($customer_link)) {
        $customer_id = $customer_link[0]['id'];
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
                        'gender' => $customer['field_' . $customer_fields['gender']] ?? '',
                        'prefix' => $customer['field_' . $customer_fields['prefix']] ?? '',
                        'suffix' => $customer['field_' . $customer_fields['suffix']] ?? '',
                        'care_name' => $customer['field_' . $customer_fields['care_name']] ?? '',
                        'care_prefix' => $customer['field_' . $customer_fields['care_prefix']] ?? '',
                        'care_suffix' => $customer['field_' . $customer_fields['care_suffix']] ?? ''
                    ];
                }
                
                echo json_encode(['success' => true, 'customers' => $results]);
                break;
                
            case 'add_customer':
                $name = trim($_POST['name'] ?? '');
                $phone = trim($_POST['phone'] ?? '');
                $gender = $_POST['gender'] ?? '';
                $prefix = trim($_POST['prefix'] ?? '');
                $suffix = trim($_POST['suffix'] ?? '');
                
                $care_name = trim($_POST['care_name'] ?? '');
                $care_prefix = trim($_POST['care_prefix'] ?? '');
                $care_suffix = trim($_POST['care_suffix'] ?? '');
                
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
                    'field_' . $customer_fields['prefix'] => $prefix,
                    'field_' . $customer_fields['suffix'] => $suffix,
                    'field_6777' => [3]
                ];
                
                // إضافة بيانات العناية إذا كانت موجودة
                if ($gender === 'شركة') {
                    if (!empty($care_name)) $data['field_' . $customer_fields['care_name']] = $care_name;
                    if (!empty($care_prefix)) $data['field_' . $customer_fields['care_prefix']] = $care_prefix;
                    if (!empty($care_suffix)) $data['field_' . $customer_fields['care_suffix']] = $care_suffix;
                }
                
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
                $prefix = trim($_POST['prefix'] ?? '');
                $suffix = trim($_POST['suffix'] ?? '');
                
                $care_name = trim($_POST['care_name'] ?? '');
                $care_prefix = trim($_POST['care_prefix'] ?? '');
                $care_suffix = trim($_POST['care_suffix'] ?? '');
                
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
                if (!empty($prefix)) {
                    $data['field_' . $customer_fields['prefix']] = $prefix;
                    $changes[] = "قبل الاسم: {$prefix}";
                }
                if (!empty($suffix)) {
                    $data['field_' . $customer_fields['suffix']] = $suffix;
                    $changes[] = "بعد الاسم: {$suffix}";
                }
                
                // تحديث بيانات العناية
                if (!empty($care_name)) {
                    $data['field_' . $customer_fields['care_name']] = $care_name;
                    $changes[] = "ع-الاسم: {$care_name}";
                }
                if (!empty($care_prefix)) {
                    $data['field_' . $customer_fields['care_prefix']] = $care_prefix;
                    $changes[] = "ع-قبل الاسم: {$care_prefix}";
                }
                if (!empty($care_suffix)) {
                    $data['field_' . $customer_fields['care_suffix']] = $care_suffix;
                    $changes[] = "ع-بعد الاسم: {$care_suffix}";
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
                    'field_' . $quote_fields['created_by'] => (int)$user_id
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
                $current_quote_id = $_POST['quote_id'] ?? '';
                $customer_id = $_POST['customer_id'] ?? '';
                
                if (empty($current_quote_id) || empty($customer_id)) {
                    echo json_encode(['success' => false, 'message' => 'معرف عرض السعر أو العميل مفقود']);
                    exit;
                }
                
                $data = [
                    'field_' . $quote_fields['customer'] => (int)$customer_id
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
            --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
            --transition-fast: all 0.15s ease;
            --transition-normal: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --space-md: 20px;
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
        
        /* حقول قبل وبعد الاسم */
        .name-fields {
            display: grid;
            grid-template-columns: 1fr 2fr 1fr;
            gap: 12px;
            margin-bottom: var(--space-md);
        }
        
        .name-fields .form-group {
            margin-bottom: 0;
        }
        
        /* قسم العناية */
        .care-section {
            border: 1px solid #e5e7eb;
            border-radius: var(--radius-sm);
            padding: 16px;
            margin-bottom: var(--space-md);
            background: #fafbfc;
        }
        
        .care-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--dark-gray);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .care-title i {
            color: var(--gold);
        }
        
        .care-fields {
            display: grid;
            grid-template-columns: 1fr 2fr 1fr;
            gap: 12px;
        }
        
        .care-fields .form-group {
            margin-bottom: 0;
        }
        
        @media (max-width: 768px) {
            .name-fields,
            .care-fields {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            
            .container {
                padding: 16px;
            }
            
            .main-card {
                border-radius: 0;
                box-shadow: none;
                border: none;
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
                    
                    <!-- مرحلة 1: رقم الجوال -->
                    <div id="phoneStage">
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
                                               placeholder="5xxxxxxxx" maxlength="9" inputmode="numeric">
                                        <span class="country-code">+966</span>
                                    </div>
                                    <div id="phoneSearchResults" class="search-results"></div>
                                </div>
                                <button type="button" id="phoneEditBtn" class="edit-btn <?php echo $is_edit_mode && $existing_customer ? '' : 'hidden'; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- مرحلة 2: اختيار الجنس للعميل الجديد -->
                    <div id="genderStage" class="hidden">
                        <div class="form-group">
                            <label class="form-label">الجنس</label>
                            <div class="form-group-with-edit">
                                <div class="form-input-container">
                                    <select id="customerGender" class="form-control form-select">
                                        <option value="">اختر الجنس</option>
                                        <option value="ذكر" <?php echo ($existing_customer && ($existing_customer['field_' . $customer_fields['gender']] ?? '') === 'ذكر') ? 'selected' : ''; ?>>ذكر</option>
                                        <option value="أنثى" <?php echo ($existing_customer && ($existing_customer['field_' . $customer_fields['gender']] ?? '') === 'أنثى') ? 'selected' : ''; ?>>أنثى</option>
                                        <option value="شركة" <?php echo ($existing_customer && ($existing_customer['field_' . $customer_fields['gender']] ?? '') === 'شركة') ? 'selected' : ''; ?>>شركة</option>
                                    </select>
                                </div>
                                <button type="button" id="genderEditBtn" class="edit-btn <?php echo $is_edit_mode && $existing_customer ? '' : 'hidden'; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- مرحلة 3: بيانات الاسم -->
                    <div id="nameStage" class="hidden">
                        <div class="name-fields">
                            <div class="form-group">
                                <label class="form-label">قبل الاسم</label>
                                <input type="text" id="customerPrefix" class="form-control" 
                                       value="<?php echo $existing_customer ? htmlspecialchars($existing_customer['field_' . $customer_fields['prefix']] ?? '') : ''; ?>"
                                       placeholder="السيد/السيدة">
                            </div>
                            <div class="form-group">
                                <label class="form-label">الاسم الكامل</label>
                                <input type="text" id="customerName" class="form-control" 
                                       value="<?php echo $existing_customer ? htmlspecialchars($existing_customer['field_' . $customer_fields['name']] ?? '') : ''; ?>"
                                       placeholder="الاسم الكامل" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">بعد الاسم</label>
                                <input type="text" id="customerSuffix" class="form-control" 
                                       value="<?php echo $existing_customer ? htmlspecialchars($existing_customer['field_' . $customer_fields['suffix']] ?? '') : ''; ?>"
                                       placeholder="المحترم/المحترمة">
                            </div>
                        </div>
                    </div>
                    
                    <!-- مرحلة 4: عناية (للشركات فقط) -->
                    <div id="careStage" class="hidden">
                        <div class="care-section">
                            <div class="care-title">
                                <i class="fas fa-building-user"></i>
                                عناية (اختياري)
                            </div>
                            <div class="care-fields">
                                <div class="form-group">
                                    <label class="form-label">قبل</label>
                                    <input type="text" id="carePrefix" class="form-control" 
                                           value="<?php echo $existing_customer ? htmlspecialchars($existing_customer['field_' . $customer_fields['care_prefix']] ?? '') : ''; ?>"
                                           placeholder="السادة/">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">الاسم</label>
                                    <input type="text" id="careName" class="form-control" 
                                           value="<?php echo $existing_customer ? htmlspecialchars($existing_customer['field_' . $customer_fields['care_name']] ?? '') : ''; ?>"
                                           placeholder="اسم المسؤول">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">بعد</label>
                                    <input type="text" id="careSuffix" class="form-control" 
                                           value="<?php echo $existing_customer ? htmlspecialchars($existing_customer['field_' . $customer_fields['care_suffix']] ?? '') : ''; ?>"
                                           placeholder="المحترمين">
                                </div>
                            </div>
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
        this.phoneEditMode = false;
        this.genderEditMode = false;
        this.fuse = null;
        this.isEditMode = <?php echo $is_edit_mode ? 'true' : 'false'; ?>;
        this.quoteId = <?php echo $quote_id ?? 'null'; ?>;
        this.currentStage = 'phone'; // phone, gender, name, care
        
        this.initializeElements();
        this.bindEvents();
        this.loadCustomers();
        
        if (this.isEditMode) {
            this.initializeEditMode();
        }
        
        this.hidePageLoader();
    }
    
    initializeEditMode() {
        <?php if ($existing_customer): ?>
        this.selectedCustomer = {
            id: <?php echo $existing_customer['id']; ?>,
            name: '<?php echo htmlspecialchars($existing_customer['field_' . $customer_fields['name']] ?? ''); ?>',
            phone: '<?php echo htmlspecialchars(substr(($existing_customer['field_' . $customer_fields['phone']] ?? ''), 4)); ?>',
            phone_display: '<?php echo htmlspecialchars($existing_customer['field_' . $customer_fields['phone']] ?? ''); ?>',
            gender: '<?php echo htmlspecialchars($existing_customer['field_' . $customer_fields['gender']] ?? ''); ?>',
            prefix: '<?php echo htmlspecialchars($existing_customer['field_' . $customer_fields['prefix']] ?? ''); ?>',
            suffix: '<?php echo htmlspecialchars($existing_customer['field_' . $customer_fields['suffix']] ?? ''); ?>',
            care_name: '<?php echo htmlspecialchars($existing_customer['field_' . $customer_fields['care_name']] ?? ''); ?>',
            care_prefix: '<?php echo htmlspecialchars($existing_customer['field_' . $customer_fields['care_prefix']] ?? ''); ?>',
            care_suffix: '<?php echo htmlspecialchars($existing_customer['field_' . $customer_fields['care_suffix']] ?? ''); ?>'
        };
        
        this.showAllStages();
        this.showStatus('وضع التعديل: ' + this.selectedCustomer.name);
        this.enableEditIcons();
        this.disableAllInputs();
        <?php endif; ?>
    }
    
    initializeElements() {
        this.customerPhone = document.getElementById('customerPhone');
        this.customerGender = document.getElementById('customerGender');
        this.customerName = document.getElementById('customerName');
        this.customerPrefix = document.getElementById('customerPrefix');
        this.customerSuffix = document.getElementById('customerSuffix');
        this.careName = document.getElementById('careName');
        this.carePrefix = document.getElementById('carePrefix');
        this.careSuffix = document.getElementById('careSuffix');
        
        this.phoneEditBtn = document.getElementById('phoneEditBtn');
        this.genderEditBtn = document.getElementById('genderEditBtn');
        this.phoneSearchResults = document.getElementById('phoneSearchResults');
        this.proceedBtn = document.getElementById('proceedBtn');
        this.messageContainer = document.getElementById('messageContainer');
        this.customerStatus = document.getElementById('customerStatus');
        this.form = document.getElementById('customerForm');
        this.pageLoader = document.getElementById('pageLoader');
        
        // المراحل
        this.phoneStage = document.getElementById('phoneStage');
        this.genderStage = document.getElementById('genderStage');
        this.nameStage = document.getElementById('nameStage');
        this.careStage = document.getElementById('careStage');
    }
    
    hidePageLoader() {
        setTimeout(() => {
            this.pageLoader.style.display = 'none';
        }, 800);
    }
    
    bindEvents() {
        // أحداث رقم الجوال
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
        
        // أحداث الجنس
        this.customerGender.addEventListener('change', () => {
            this.handleGenderChange();
        });
        
        // أحداث أزرار التعديل
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
    
    handlePhoneSearch() {
        if (this.phoneEditMode) return;
        
        const searchTerm = this.customerPhone.value.trim();
        
        if (searchTerm.length < 2) {
            this.hidePhoneResults();
            return;
        }
        
        const results = this.allCustomers.filter(customer => 
            customer.phone && customer.phone.includes(searchTerm)
        );
        
        // إذا كان الرقم مكتمل (9 أرقام)، تحديد تلقائي
        if (searchTerm.length === 9 && searchTerm.startsWith('5')) {
            const exactMatch = results.find(customer => customer.phone === searchTerm);
            if (exactMatch) {
                this.selectExistingCustomer(exactMatch);
                this.hidePhoneResults();
                return;
            } else {
                // رقم جديد، إظهار اختيار الجنس
                this.showGenderStage();
                this.hidePhoneResults();
                return;
            }
        }
        
        if (results.length > 0) {
            this.showPhoneResults(results.slice(0, 5));
        } else {
            this.hidePhoneResults();
        }
    }
    
    showPhoneResults(results) {
        const html = results.map(customer => {
            let displayPhone = customer.phone_display || '';
            if (displayPhone.startsWith('+966')) {
                displayPhone = '0' + displayPhone.substring(4);
            }
            
            return `
                <div class="search-result-item" onclick="customerManager.selectCustomerFromResults(${customer.id})">
                    <div class="result-phone">${displayPhone || 'غير محدد'}</div>
                    <div class="result-name">${customer.name || 'غير محدد'}</div>
                </div>
            `;
        }).join('');
        
        this.phoneSearchResults.innerHTML = html;
        this.phoneSearchResults.style.display = 'block';
    }
    
    hidePhoneResults() {
        this.phoneSearchResults.style.display = 'none';
    }
    
    selectCustomerFromResults(customerId) {
        const customer = this.allCustomers.find(c => c.id === customerId);
        if (customer) {
            this.selectExistingCustomer(customer);
            this.hidePhoneResults();
        }
    }
    
    selectExistingCustomer(customer) {
        this.selectedCustomer = customer;
        
        // تعبئة البيانات
        let phoneValue = customer.phone || '';
        if (phoneValue.length === 9 && phoneValue.startsWith('5')) {
            phoneValue = phoneValue;
        } else if (customer.phone_display && customer.phone_display.startsWith('+966')) {
            phoneValue = customer.phone_display.substring(4);
        }
        this.customerPhone.value = phoneValue;
        this.selectedCustomer.displayPhone = phoneValue;
        
        this.customerGender.value = customer.gender || '';
        this.customerName.value = customer.name || '';
        this.customerPrefix.value = customer.prefix || this.getDefaultPrefix(customer.gender);
        this.customerSuffix.value = customer.suffix || this.getDefaultSuffix(customer.gender);
        
        // بيانات العناية
        this.careName.value = customer.care_name || '';
        this.carePrefix.value = customer.care_prefix || '';
        this.careSuffix.value = customer.care_suffix || '';
        
        this.showAllStages();
        this.showStatus('تم العثور على العميل: ' + (customer.name || 'غير محدد'));
        this.enableEditIcons();
        this.disableAllInputs();
    }
    
    showGenderStage() {
        this.genderStage.classList.remove('hidden');
        this.currentStage = 'gender';
        this.showStatus('رقم جوال جديد - اختر الجنس');
    }
    
    handleGenderChange() {
        if (!this.customerGender.value) return;
        
        this.showNameStage();
        this.applyDefaultNames();
        
        if (this.customerGender.value === 'شركة') {
            this.showCareStage();
        } else {
            this.hideCareStage();
        }
    }
    
    showNameStage() {
        this.nameStage.classList.remove('hidden');
        this.currentStage = 'name';
    }
    
    showCareStage() {
        this.careStage.classList.remove('hidden');
        this.currentStage = 'care';
    }
    
    hideCareStage() {
        this.careStage.classList.add('hidden');
    }
    
    showAllStages() {
        this.genderStage.classList.remove('hidden');
        this.nameStage.classList.remove('hidden');
        if (this.customerGender.value === 'شركة') {
            this.careStage.classList.remove('hidden');
        }
    }
    
    applyDefaultNames() {
        const gender = this.customerGender.value;
        if (!this.customerPrefix.value) {
            this.customerPrefix.value = this.getDefaultPrefix(gender);
        }
        if (!this.customerSuffix.value) {
            this.customerSuffix.value = this.getDefaultSuffix(gender);
        }
    }
    
    getDefaultPrefix(gender) {
        switch(gender) {
            case 'ذكر': return 'السيد';
            case 'أنثى': return 'السيدة';
            case 'شركة': return 'السادة';
            default: return '';
        }
    }
    
    getDefaultSuffix(gender) {
        switch(gender) {
            case 'ذكر': return 'المحترم';
            case 'أنثى': return 'المحترمة';
            case 'شركة': return 'المحترمين';
            default: return '';
        }
    }
    
    enableEditIcons() {
        this.phoneEditBtn.classList.remove('hidden');
        this.genderEditBtn.classList.remove('hidden');
        this.updateButtonIcon(this.phoneEditBtn, false);
        this.updateButtonIcon(this.genderEditBtn, false);
    }
    
    disableAllInputs() {
        this.customerPhone.disabled = true;
        this.customerGender.disabled = true;
        
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
    
    enablePhoneEdit() {
        this.customerPhone.disabled = false;
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
        this.genderEditMode = true;
        this.updateButtonIcon(this.genderEditBtn, true);
        this.showStatus('وضع تعديل الجنس - اضغط على علامة الصح للحفظ');
        this.customerGender.focus();
    }
    
    async savePhoneChanges() {
        if (!this.phoneEditMode || !this.selectedCustomer) return;
        
        const newPhone = this.customerPhone.value.trim();
        const currentPhone = this.selectedCustomer.displayPhone || this.selectedCustomer.phone || '';
        
        if (newPhone === currentPhone) {
            this.customerPhone.disabled = true;
            this.phoneEditMode = false;
            this.updateButtonIcon(this.phoneEditBtn, false);
            this.showStatus(this.isEditMode ? 'وضع التعديل: ' + this.selectedCustomer.name : 'تم العثور على العميل: ' + this.selectedCustomer.name);
            return;
        }
        
        if (newPhone && /^5[0-9]{8}$/.test(newPhone)) {
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
        this.phoneEditMode = false;
        this.updateButtonIcon(this.phoneEditBtn, false);
    }
    
    async saveGenderChanges() {
        if (!this.genderEditMode || !this.selectedCustomer) return;
        
        if (!this.customerGender.value) return;
        
        if (this.customerGender.value === this.selectedCustomer.gender) {
            this.customerGender.disabled = true;
            this.genderEditMode = false;
            this.updateButtonIcon(this.genderEditBtn, false);
            this.showStatus(this.isEditMode ? 'وضع التعديل: ' + this.selectedCustomer.name : 'تم العثور على العميل: ' + this.selectedCustomer.name);
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
                
                // تحديث الافتراضيات
                this.customerPrefix.value = this.getDefaultPrefix(this.customerGender.value);
                this.customerSuffix.value = this.getDefaultSuffix(this.customerGender.value);
                
                // إظهار/إخفاء العناية
                if (this.customerGender.value === 'شركة') {
                    this.showCareStage();
                } else {
                    this.hideCareStage();
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
        
        this.customerGender.disabled = true;
        this.genderEditMode = false;
        this.updateButtonIcon(this.genderEditBtn, false);
    }
    
    handlePhoneInput(e) {
        const isNumericInput = /^[0-9]*$/.test(e.target.value);
        
        if (isNumericInput) {
            this.formatPhoneInput(e);
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
        const phone = this.customerPhone.value.trim();
        const gender = this.customerGender.value;
        const name = this.customerName.value.trim();
        const prefix = this.customerPrefix.value.trim();
        const suffix = this.customerSuffix.value.trim();
        
        const careName = this.careName.value.trim();
        const carePrefix = this.carePrefix.value.trim();
        const careSuffix = this.careSuffix.value.trim();
        
        if (!phone || !gender || !name) {
            this.showMessage('يرجى ملء جميع الحقول المطلوبة', 'error');
            return;
        }
        
        if (!/^5[0-9]{8}$/.test(phone)) {
            this.showMessage('رقم الجوال غير صحيح', 'error');
            return;
        }
        
        this.setLoading(true);
        
        try {
            let customerId;
            
            if (this.selectedCustomer) {
                customerId = this.selectedCustomer.id;
                
                // تحديث البيانات إذا تغيرت
                const updateData = {
                    customer_id: customerId,
                    name: name,
                    prefix: prefix,
                    suffix: suffix
                };
                
                if (gender === 'شركة') {
                    updateData.care_name = careName;
                    updateData.care_prefix = carePrefix;
                    updateData.care_suffix = careSuffix;
                }
                
                await this.makeRequest('update_customer', updateData);
                
                if (this.selectedCustomer.gender !== gender) {
                    await this.saveGenderChanges();
                }
            } else {
                if (this.isEditMode) {
                    this.showMessage('يجب اختيار عميل موجود في وضع التعديل', 'error');
                    this.setLoading(false);
                    return;
                }
                
                const addData = {
                    name: name,
                    phone: phone,
                    gender: gender,
                    prefix: prefix,
                    suffix: suffix
                };
                
                if (gender === 'شركة') {
                    addData.care_name = careName;
                    addData.care_prefix = carePrefix;
                    addData.care_suffix = careSuffix;
                }
                
                const addResponse = await this.makeRequest('add_customer', addData);
                
                if (!addResponse.success) {
                    this.showMessage('خطأ في إضافة العميل: ' + addResponse.message, 'error');
                    this.setLoading(false);
                    return;
                }
                
                customerId = addResponse.customer.id;
                this.showMessage('تم إضافة العميل بنجاح', 'success');
            }
            
            if (this.isEditMode) {
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