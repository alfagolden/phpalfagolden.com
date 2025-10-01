<?php
// ملف موحّد تم توليده تلقائياً
session_start();
$step = isset($_GET['step']) ? strval($_GET['step']) : '1';
?>
<?php
if ($step === '1') {

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


<!DOCTYPE html>

}

if ($step === '2') {

$baserow_url = 'https://base.alfagolden.com';
$api_token = 'h5qAt85gtiJDAzpH51WrXPywhmnhrPWy';

$projects_table_id = 706;
$quotes_table_id = 704;
$actions_table_id = 707;
$settings_table_id = 705;
$settings_record_id = 1;

$project_fields = [
    'name' => 6916, 'city' => 6940, 'address' => 6921, 'type' => 6922,
    'location' => 6923, 'well_material' => 6941, 'well_size' => 6942
];

$quote_fields = ['project' => 6788, 'customer_name' => 6977];
$action_fields = ['user' => 6928, 'action' => 6929, 'projects' => 6938];
$settings_fields = ['cities' => 6943, 'project_types' => 6944, 'well_materials' => 6867];

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header('Location: ../login.php');
    exit;
}

$quote_id = $_GET['quote_id'] ?? null;
if (!$quote_id) {
    header('Location: 1.php');
    exit;
}

function makeBaserowRequest($url, $method = 'GET', $data = null) {
    global $api_token;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Token ' . $api_token, 'Content-Type: application/json']);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($method === 'PATCH') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code >= 200 && $http_code < 300) {
        return json_decode($response, true);
    } else {
        return ['error' => true, 'message' => 'خطأ في الاتصال بقاعدة البيانات'];
    }
}

function logAction($action, $project_id = null) {
    global $baserow_url, $actions_table_id, $action_fields, $user_id;
    $data = ['field_' . $action_fields['user'] => (int)$user_id, 'field_' . $action_fields['action'] => $action];
    if ($project_id) $data['field_' . $action_fields['projects']] = (int)$project_id;
    $url = $baserow_url . '/api/database/rows/table/' . $actions_table_id . '/';
    return makeBaserowRequest($url, 'POST', $data);
}

function getSettings() {
    global $baserow_url, $settings_table_id, $settings_record_id;
    $url = $baserow_url . '/api/database/rows/table/' . $settings_table_id . '/' . $settings_record_id . '/';
    return makeBaserowRequest($url);
}

function updateSettings($field_id, $value) {
    global $baserow_url, $settings_table_id, $settings_record_id;
    $data = ['field_' . $field_id => $value];
    $url = $baserow_url . '/api/database/rows/table/' . $settings_table_id . '/' . $settings_record_id . '/';
    return makeBaserowRequest($url, 'PATCH', $data);
}

function getCustomerName($quote_id) {
    global $baserow_url, $quotes_table_id, $quote_fields;
    $url = $baserow_url . '/api/database/rows/table/' . $quotes_table_id . '/' . $quote_id . '/';
    $response = makeBaserowRequest($url);
    
    if (!isset($response['error'])) {
        $customer_field = $response['field_' . $quote_fields['customer_name']] ?? null;
        
        // إذا كان lookup field، فإن القيمة تكون array
        if (is_array($customer_field) && !empty($customer_field)) {
            // أخذ أول عنصر من المصفوفة
            $first_customer = $customer_field[0];
            
            // إذا كان العنصر له خاصية 'value' أو 'name' أو شيء مماثل
            if (isset($first_customer['value'])) {
                return $first_customer['value'];
            } elseif (isset($first_customer['name'])) {
                return $first_customer['name'];
            } elseif (is_string($first_customer)) {
                return $first_customer;
            }
        } elseif (is_string($customer_field)) {
            return $customer_field;
        }
    }
    
    return '';
}

function processOptionsJson($json_string) {
    if (empty($json_string)) return ['options' => [], 'default' => null];
    $data = json_decode($json_string, true);
    if (json_last_error() !== JSON_ERROR_NONE) return ['options' => [], 'default' => null];
    return ['options' => $data['options'] ?? [], 'default' => $data['default'] ?? null];
}

function buildOptionsJson($options, $default = null) {
    return json_encode(['options' => array_values($options), 'default' => $default], JSON_UNESCAPED_UNICODE);
}

// جلب اسم العميل لاقتراح اسم المشروع
$customer_name = getCustomerName($quote_id);
$suggested_project_name = $customer_name ? "منزل " . $customer_name : "منزل";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'search_projects':
                $url = $baserow_url . '/api/database/rows/table/' . $projects_table_id . '/?size=200';
                $response = makeBaserowRequest($url);
                if (isset($response['error'])) {
                    echo json_encode(['success' => false, 'message' => $response['message']]);
                    exit;
                }
                $projects = $response['results'] ?? [];
                $results = [];
                foreach ($projects as $project) {
                    $results[] = [
                        'id' => $project['id'],
                        'name' => $project['field_' . $project_fields['name']] ?? '',
                        'city' => $project['field_' . $project_fields['city']] ?? '',
                        'address' => $project['field_' . $project_fields['address']] ?? '',
                        'type' => $project['field_' . $project_fields['type']] ?? '',
                        'location' => $project['field_' . $project_fields['location']] ?? '',
                        'well_material' => $project['field_' . $project_fields['well_material']] ?? '',
                        'well_size' => $project['field_' . $project_fields['well_size']] ?? ''
                    ];
                }
                echo json_encode(['success' => true, 'projects' => $results]);
                break;
                
            case 'get_options':
                $field_type = $_POST['field_type'] ?? '';
                $settings = getSettings();
                if (isset($settings['error'])) {
                    echo json_encode(['success' => false, 'message' => $settings['message']]);
                    exit;
                }
                $field_id = null;
                switch ($field_type) {
                    case 'cities': $field_id = $settings_fields['cities']; break;
                    case 'project_types': $field_id = $settings_fields['project_types']; break;
                    case 'well_materials': $field_id = $settings_fields['well_materials']; break;
                    default: echo json_encode(['success' => false, 'message' => 'نوع الحقل غير مدعوم']); exit;
                }
                $json_data = $settings['field_' . $field_id] ?? '';
                $options_data = processOptionsJson($json_data);
                echo json_encode(['success' => true, 'options' => $options_data['options'], 'default' => $options_data['default']]);
                break;
                
            case 'update_options':
                $field_type = $_POST['field_type'] ?? '';
                $options = json_decode($_POST['options'] ?? '[]', true);
                $default = $_POST['default'] ?? null;
                $field_id = null;
                switch ($field_type) {
                    case 'cities': $field_id = $settings_fields['cities']; break;
                    case 'project_types': $field_id = $settings_fields['project_types']; break;
                    case 'well_materials': $field_id = $settings_fields['well_materials']; break;
                    default: echo json_encode(['success' => false, 'message' => 'نوع الحقل غير مدعوم']); exit;
                }
                $json_value = buildOptionsJson($options, $default);
                $response = updateSettings($field_id, $json_value);
                if (isset($response['error'])) {
                    echo json_encode(['success' => false, 'message' => $response['message']]);
                    exit;
                }
                logAction("حدث خيارات " . $field_type);
                echo json_encode(['success' => true, 'message' => 'تم تحديث الخيارات بنجاح']);
                break;
                
            case 'add_project':
                $name = trim($_POST['name'] ?? '');
                $city = trim($_POST['city'] ?? '');
                $address = trim($_POST['address'] ?? '');
                $type = trim($_POST['type'] ?? '');
                $location = trim($_POST['location'] ?? '');
                $well_material = trim($_POST['well_material'] ?? '');
                $well_width = trim($_POST['well_width'] ?? '');
                $well_depth = trim($_POST['well_depth'] ?? '');
                
                if (empty($name) || empty($city) || empty($type) || empty($well_material) || empty($well_width) || empty($well_depth)) {
                    echo json_encode(['success' => false, 'message' => 'يرجى ملء جميع الحقول المطلوبة']);
                    exit;
                }
                
                // تنسيق المقاس بالشكل المطلوب
                $well_size = "العرض " . $well_width . "سم x العمق " . $well_depth . "سم";
                
                $data = [
                    'field_' . $project_fields['name'] => $name,
                    'field_' . $project_fields['city'] => $city,
                    'field_' . $project_fields['address'] => $address,
                    'field_' . $project_fields['type'] => $type,
                    'field_' . $project_fields['location'] => $location,
                    'field_' . $project_fields['well_material'] => $well_material,
                    'field_' . $project_fields['well_size'] => $well_size
                ];
                
                $url = $baserow_url . '/api/database/rows/table/' . $projects_table_id . '/';
                $response = makeBaserowRequest($url, 'POST', $data);
                
                if (isset($response['error'])) {
                    echo json_encode(['success' => false, 'message' => $response['message']]);
                    exit;
                }
                
                logAction("أضاف مشروع جديد - الاسم: {$name}، المدينة: {$city}، النوع: {$type}", $response['id']);
                
                echo json_encode([
                    'success' => true, 
                    'project' => [
                        'id' => $response['id'], 'name' => $name, 'city' => $city, 'address' => $address,
                        'type' => $type, 'location' => $location, 'well_material' => $well_material, 'well_size' => $well_size
                    ]
                ]);
                break;
                
            case 'select_project':
                $project_id = $_POST['project_id'] ?? '';
                $quote_id = $_POST['quote_id'] ?? '';
                
                if (empty($project_id) || empty($quote_id)) {
                    echo json_encode(['success' => false, 'message' => 'معرف المشروع أو عرض السعر مفقود']);
                    exit;
                }
                
                $data = ['field_' . $quote_fields['project'] => (int)$project_id];
                $url = $baserow_url . '/api/database/rows/table/' . $quotes_table_id . '/' . $quote_id . '/';
                $response = makeBaserowRequest($url, 'PATCH', $data);
                
                if (isset($response['error'])) {
                    echo json_encode(['success' => false, 'message' => $response['message']]);
                    exit;
                }
                
                logAction("ربط عرض السعر بمشروع", $project_id);
                echo json_encode(['success' => true, 'redirect' => '3.php?quote_id=' . $quote_id]);
                break;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'حدث خطأ غير متوقع']);
    }
    exit;
}


<!DOCTYPE html>

}

if ($step === '3') {

// صفحة 3.php - اختيار المصعد وحساب السعر
$baserow_url = 'https://base.alfagolden.com';
$api_token = 'h5qAt85gtiJDAzpH51WrXPywhmnhrPWy';

// معرفات الجداول
$elevators_table_id = 711;
$additions_table_id = 712;
$quotes_table_id = 704;
$actions_table_id = 707;

// حقول جدول عروض الأسعار
$quote_fields = [
    'elevator' => 6967,
    'elevators_additions' => 6971,
    'elevators_count' => 6794,
    'stops_count' => 6797,
    'total_price' => 6984,
    'price_details' => 6985,
    'discount_amount' => 6986,
    'increase_amount' => 7071  // حقل مبلغ الزيادة الجديد
];

$user_id = $_SESSION['user_id'] ?? null;
$quote_id = $_GET['quote_id'] ?? null;

if (!$user_id) { header('Location: ../login.php'); exit; }
if (!$quote_id) { header('Location: 1.php'); exit; }

function makeBaserowRequest($url, $method = 'GET', $data = null) {
    global $api_token;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Token ' . $api_token, 'Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // إضافة هذا السطر لتجنب مشاكل SSL
    
    if ($method === 'POST') { 
        curl_setopt($ch, CURLOPT_POST, true); 
        if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data)); 
    }
    elseif ($method === 'PATCH') { 
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH'); 
        if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data)); 
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    // إضافة معلومات debug في حالة الخطأ
    if (!empty($curl_error)) {
        error_log("CURL Error: " . $curl_error . " for URL: " . $url);
        return ['error' => true, 'message' => 'خطأ في الاتصال: ' . $curl_error];
    }
    
    if ($http_code >= 200 && $http_code < 300) {
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON Decode Error: " . json_last_error_msg() . " for response: " . substr($response, 0, 500));
            return ['error' => true, 'message' => 'خطأ في فك ترميز الاستجابة'];
        }
        return $decoded;
    }
    
    // تسجيل تفاصيل الخطأ
    error_log("HTTP Error $http_code for URL: $url, Response: " . substr($response, 0, 500));
    
    return ['error' => true, 'message' => 'خطأ في الاتصال - كود: ' . $http_code, 'details' => substr($response, 0, 200)];
}

// معالجة طلبات AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    try {
        switch ($_POST['action']) {
            case 'load_elevators':
                $url = $baserow_url . '/api/database/rows/table/' . $elevators_table_id . '/?user_field_names=true&size=200';
                $response = makeBaserowRequest($url);
                
                if (isset($response['error'])) {
                    echo json_encode(['success' => false, 'message' => $response['message']]);
                    exit;
                }
                
                $elevators = [];
                foreach ($response['results'] as $elevator) {
                    $elevators[] = [
                        'id' => $elevator['id'],
                        'price' => floatval($elevator['السعر'] ?? 0),
                        'capacity' => $elevator['الحمولة']['value'] ?? null,
                        'stops' => intval($elevator['الوقفات'] ?? 0),
                        'door_type' => $elevator['نوع الأبواب']['value'] ?? null,
                        'brand' => $elevator['البراند']['value'] ?? null,
                        'gear' => $elevator['الجير']['value'] ?? null
                    ];
                }
                
                echo json_encode(['success' => true, 'elevators' => $elevators]);
                break;
                
            case 'load_additions':
                $url = $baserow_url . '/api/database/rows/table/' . $additions_table_id . '/?size=200';
                $response = makeBaserowRequest($url);
                
                if (isset($response['error'])) {
                    echo json_encode(['success' => false, 'message' => $response['message']]);
                    exit;
                }
                
                $additions = [];
                foreach ($response['results'] as $addition) {
                    $additions[] = [
                        'id' => $addition['id'],
                        'name' => $addition['field_6964'] ?? '',
                        'price' => floatval($addition['field_6969'] ?? 0),
                        'price_effect' => $addition['field_6970'] ?? 'مرة واحدة',
                        'door_type' => $addition['field_7069'] ?? ''  // إضافة نوع الأبواب للفلتر
                    ];
                }
                
                echo json_encode(['success' => true, 'additions' => $additions]);
                break;
                
            case 'select_elevator':
                $elevator_id = intval($_POST['elevator_id'] ?? 0);
                
                if (!$elevator_id) {
                    echo json_encode(['success' => false, 'message' => 'معرف المصعد مفقود']);
                    exit;
                }
                
                // حفظ المصعد المختار فقط - باقي العمليات ستتم لاحقاً
                $data = ['field_' . $quote_fields['elevator'] => [$elevator_id]];
                $url = $baserow_url . '/api/database/rows/table/' . $quotes_table_id . '/' . $quote_id . '/';
                $response = makeBaserowRequest($url, 'PATCH', $data);
                
                if (isset($response['error'])) {
                    echo json_encode(['success' => false, 'message' => 'خطأ في حفظ المصعد']);
                    exit;
                }
                
                echo json_encode(['success' => true, 'message' => 'تم اختيار المصعد بنجاح']);
                break;
                
            case 'calculate_final_price':
                // التحقق من البيانات الأساسية
                $elevators_count = max(1, intval($_POST['elevators_count'] ?? 1));
                $selected_additions = json_decode($_POST['selected_additions'] ?? '[]', true);
                $discount_amount = max(0, floatval($_POST['discount_amount'] ?? 0));
                $increase_amount = max(0, floatval($_POST['increase_amount'] ?? 0)); // إضافة مبلغ الزيادة
                
                if (!is_array($selected_additions)) {
                    $selected_additions = [];
                }
                
                // جلب بيانات العرض الحالي مع معالجة أفضل للأخطاء
                $quote_url = $baserow_url . '/api/database/rows/table/' . $quotes_table_id . '/' . $quote_id . '/';
                $quote_response = makeBaserowRequest($quote_url);
                
                if (isset($quote_response['error'])) {
                    echo json_encode(['success' => false, 'message' => 'خطأ في جلب بيانات العرض: ' . $quote_response['message']]);
                    exit;
                }
                
                // التأكد من وجود مصعد محدد
                $elevator_data = $quote_response['field_' . $quote_fields['elevator']] ?? [];
                if (empty($elevator_data)) {
                    echo json_encode(['success' => false, 'message' => 'يرجى اختيار المصعد أولاً']);
                    exit;
                }
                
                $elevator_id = $elevator_data[0]['id'] ?? null;
                if (!$elevator_id) {
                    echo json_encode(['success' => false, 'message' => 'معرف المصعد غير صالح']);
                    exit;
                }
                
                // جلب تفاصيل المصعد مع معالجة أفضل
                $elevator_url = $baserow_url . '/api/database/rows/table/' . $elevators_table_id . '/' . $elevator_id . '/?user_field_names=true';
                $elevator_response = makeBaserowRequest($elevator_url);
                
                if (isset($elevator_response['error'])) {
                    echo json_encode(['success' => false, 'message' => 'خطأ في جلب بيانات المصعد: ' . $elevator_response['message']]);
                    exit;
                }
                
                // التحقق من وجود البيانات المطلوبة
                if (!isset($elevator_response['السعر']) && !isset($elevator_response['الوقفات'])) {
                    echo json_encode(['success' => false, 'message' => 'بيانات المصعد غير مكتملة']);
                    exit;
                }
                
                $elevator_price = floatval($elevator_response['السعر'] ?? 0);
                $elevator_stops = max(1, intval($elevator_response['الوقفات'] ?? 1));
                
                if ($elevator_price <= 0) {
                    echo json_encode(['success' => false, 'message' => 'سعر المصعد غير صالح']);
                    exit;
                }
                
                // حساب السعر الأساسي
                $base_price = $elevator_price * $elevators_count;
                
                // حساب الإضافات
                $additions_total = 0;
                $additions_details = [];
                
                if (!empty($selected_additions)) {
                    foreach ($selected_additions as $addition_id) {
                        $addition_url = $baserow_url . '/api/database/rows/table/' . $additions_table_id . '/' . $addition_id . '/';
                        $addition_response = makeBaserowRequest($addition_url);
                        
                        if (isset($addition_response['error'])) continue;
                        
                        $addition_name = $addition_response['field_6964'] ?? 'إضافة غير محددة';
                        $addition_price = floatval($addition_response['field_6969'] ?? 0);
                        $price_effect = $addition_response['field_6970'] ?? 'مرة واحدة';
                        
                        if ($addition_price <= 0) continue;
                        
                        $addition_total = 0;
                        if ($price_effect === 'لكل دور') {
                            $addition_total = $addition_price * $elevator_stops * $elevators_count;
                        } else {
                            $addition_total = $addition_price;
                        }
                        
                        $additions_total += $addition_total;
                        $additions_details[] = [
                            'name' => $addition_name,
                            'price' => $addition_price,
                            'effect' => $price_effect,
                            'total' => $addition_total,
                            'calculation' => $price_effect === 'لكل دور' ? 
                                "$addition_price × $elevator_stops × $elevators_count" : 
                                "$addition_price (مرة واحدة)"
                        ];
                    }
                }
                
                // حساب المجموع قبل التخفيض والزيادة
                $subtotal = $base_price + $additions_total;
                
                // حساب الحد الأقصى للتخفيض (5% من المجموع + مبلغ الزيادة)
                $max_discount = ($subtotal * 0.05) + $increase_amount;
                if ($discount_amount > $max_discount) {
                    $discount_amount = $max_discount;
                }
                
                // المجموع النهائي مع التخفيض والزيادة
                $final_total = $subtotal - $discount_amount + $increase_amount;
                
                // إعداد تفاصيل السعر
                $price_details = [
                    'elevator_id' => $elevator_id,
                    'elevator_price' => $elevator_price,
                    'elevators_count' => $elevators_count,
                    'elevator_stops' => $elevator_stops,
                    'base_price' => $base_price,
                    'additions' => $additions_details,
                    'additions_total' => $additions_total,
                    'subtotal' => $subtotal,
                    'discount_amount' => $discount_amount,
                    'increase_amount' => $increase_amount, // إضافة مبلغ الزيادة
                    'max_discount_allowed' => $max_discount,
                    'final_total' => $final_total,
                    'calculated_at' => date('Y-m-d H:i:s')
                ];
                
                // حفظ البيانات النهائية مع التحقق
                $update_data = [
                    'field_' . $quote_fields['elevators_count'] => intval($elevators_count),
                    'field_' . $quote_fields['total_price'] => round($final_total, 2)
                ];
                
                // إضافة عدد الوقفات فقط إذا كان صالح
                if ($elevator_stops > 0) {
                    $update_data['field_' . $quote_fields['stops_count']] = intval($elevator_stops);
                }
                
                // إضافة التخفيض فقط إذا كان موجود
                if ($discount_amount > 0) {
                    $update_data['field_' . $quote_fields['discount_amount']] = round($discount_amount, 2);
                }
                
                // إضافة الزيادة فقط إذا كانت موجودة
                if ($increase_amount > 0) {
                    $update_data['field_' . $quote_fields['increase_amount']] = round($increase_amount, 2);
                }
                
                // إضافة تفاصيل السعر
                if (!empty($price_details)) {
                    $update_data['field_' . $quote_fields['price_details']] = json_encode($price_details, JSON_UNESCAPED_UNICODE);
                }
                
                // إضافة الإضافات فقط إذا كانت موجودة
                if (!empty($selected_additions)) {
                    $update_data['field_' . $quote_fields['elevators_additions']] = array_map('intval', $selected_additions);
                }
                
                // تسجيل البيانات التي سيتم إرسالها للمساعدة في التتبع
                error_log("Update data being sent: " . json_encode($update_data));
                
                $update_response = makeBaserowRequest($quote_url, 'PATCH', $update_data);
                
                if (isset($update_response['error'])) {
                    // محاولة حفظ البيانات الأساسية فقط في حالة الفشل
                    error_log("Main update failed, trying minimal data");
                    
                    $minimal_data = [
                        'field_' . $quote_fields['elevators_count'] => intval($elevators_count),
                        'field_' . $quote_fields['total_price'] => round($final_total, 2)
                    ];
                    
                    $retry_response = makeBaserowRequest($quote_url, 'PATCH', $minimal_data);
                    
                    if (isset($retry_response['error'])) {
                        error_log("Even minimal update failed: " . json_encode($retry_response));
                        echo json_encode([
                            'success' => false, 
                            'message' => 'خطأ في حفظ البيانات. يرجى المحاولة مرة أخرى أو التواصل مع الدعم الفني',
                            'details' => $update_response['message'] ?? 'غير محدد'
                        ]);
                        exit;
                    }
                    
                    // إذا نجح الحفظ الأساسي، حاول حفظ باقي البيانات تدريجياً
                    if ($discount_amount > 0) {
                        makeBaserowRequest($quote_url, 'PATCH', ['field_' . $quote_fields['discount_amount'] => round($discount_amount, 2)]);
                    }
                    
                    if ($increase_amount > 0) {
                        makeBaserowRequest($quote_url, 'PATCH', ['field_' . $quote_fields['increase_amount'] => round($increase_amount, 2)]);
                    }
                    
                    if (!empty($selected_additions)) {
                        makeBaserowRequest($quote_url, 'PATCH', ['field_' . $quote_fields['elevators_additions'] => array_map('intval', $selected_additions)]);
                    }
                    
                    if (!empty($price_details)) {
                        makeBaserowRequest($quote_url, 'PATCH', ['field_' . $quote_fields['price_details'] => json_encode($price_details, JSON_UNESCAPED_UNICODE)]);
                    }
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'تم حفظ جميع البيانات بنجاح',
                    'calculation' => $price_details,
                    'redirect' => '4.php?quote_id=' . $quote_id
                ]);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'عملية غير مدعومة']);
                break;
        }
    } catch (Exception $e) {
        error_log("AJAX Exception: " . $e->getMessage());
        error_log("AJAX Trace: " . $e->getTraceAsString());
        echo json_encode(['success' => false, 'message' => 'حدث خطأ غير متوقع: ' . $e->getMessage()]);
    }
    exit;
}

// جلب البيانات الحالية للعرض
$quote_url = $baserow_url . '/api/database/rows/table/' . $quotes_table_id . '/' . $quote_id . '/';
$quote_response = makeBaserowRequest($quote_url);
$current_quote = $quote_response && !isset($quote_response['error']) ? $quote_response : null;

$current_elevator_id = null;
$current_elevators_count = 1;
$current_discount = 0;
$current_increase = 0; // إضافة متغير للزيادة الحالية
$current_selected_additions = [];

if ($current_quote) {
    $elevator_data = $current_quote['field_' . $quote_fields['elevator']] ?? [];
    if (!empty($elevator_data) && is_array($elevator_data)) {
        $current_elevator_id = $elevator_data[0]['id'] ?? null;
    }
    
    $current_elevators_count = max(1, intval($current_quote['field_' . $quote_fields['elevators_count']] ?? 1));
    $current_discount = floatval($current_quote['field_' . $quote_fields['discount_amount']] ?? 0);
    $current_increase = floatval($current_quote['field_' . $quote_fields['increase_amount']] ?? 0); // جلب قيمة الزيادة
    
    $additions_data = $current_quote['field_' . $quote_fields['elevators_additions']] ?? [];
    if (is_array($additions_data)) {
        foreach ($additions_data as $addition) {
            if (is_array($addition) && isset($addition['id'])) {
                $current_selected_additions[] = $addition['id'];
            }
        }
    }
}


<!DOCTYPE html>

}

if ($step === '4') {

// ======== إعدادات الاتصال ========
$baserow_url = 'https://base.alfagolden.com';
$api_token = 'h5qAt85gtiJDAzpH51WrXPywhmnhrPWy';
$quotes_table_id = 704;
$settings_table_id = 705;
$settings_record_id = 1;

// ======== تعريف الحقول ========
$form_to_baserow_map = [
    'opening_sentence' => 'جملة البداية', 'introductory_sentence' => 'الجملة التمهيدية', 'closing_sentence' => 'الجملة الختامية',
    'elevator_control_system' => 'جهاز تشغيل المصعد', 'operation_method' => 'طريقة التشغيل', 'stops_naming' => 'مسميات الوقفات', 'electrical_current' => 'التيار الكهربائي',
    'machine_type' => 'نوع المكينة', 'rails_elevator' => 'سكك الصاعدة', 'rails_counterweight' => 'سكك ثقل الموازنة', 'traction_cables' => 'حبال الجر', 'flexible_cable' => 'الكابل المرن',
    'elevator_frame' => 'الاطار الحامل للصاعدة', 'car_finishing' => 'الصاعدة - التشطيب', 'car_internal_dimensions' => 'الصاعدة - المقاسات الداخلية', 'ceiling' => 'السقف',
    'emergency_lighting' => 'اضاءة الطوارئ', 'car_movement_device' => 'جهاز تحريك الصاعدة', 'flooring' => 'الأرضية',
    'door_operation_method' => 'طريقة تشغيل الأبواب', 'internal_door' => 'الباب الداخلي', 'door_dimensions' => 'مقاسات الابواب',
    'cop_panel' => 'لوحة الطلب الداخلية COP', 'lop_finishing' => 'لوحة الطلب الخارجية - التشطيب', 'lop_main_floor' => 'لوحة الطلب الخارجية - الوقفة الرئيسية', 'lop_other_floors' => 'لوحة الطلب الخارجية - الوقفات الاخرى',
    'safety_electrical_devices' => 'أجهزة الطوارئ والأمان - أجهزة الاتصال الكهربائية', 'emergency_devices' => 'أجهزة الطوارئ والأمان - أجهزة الطوارئ', 'lighting_devices' => 'أجهزة الطوارئ والأمان - أجهزة الانارة', 'safety_devices' => 'أجهزة الطوارئ والأمان - أجهزة الأمان',
    'light_curtain' => 'أجهزة الطوارئ والأمان - ستارة ضوئية', 'speed_governor' => 'أجهزة الطوارئ والأمان - جهاز منظم السرعة', 'shock_absorbers' => 'أجهزة الطوارئ والأمان - مخففات الصدمات', 'travel_end_device' => 'أجهزة الطوارئ والأمان - جهاز نهاية المشوار',
    'door_safety_cam' => 'أجهزة الطوارئ والأمان - كامة تأمين فتح الباب', 'car_guides' => 'أجهزة الطوارئ والأمان - مزايت الصاعدة', 'external_door_switch' => 'أجهزة الطوارئ والأمان - مفتاح الباب الخارجى', 'electrical_connections' => 'أجهزة الطوارئ والأمان - التوصيلات الكهربائية',
    'preparatory_works' => 'الأعمال التحضيرية', 'warranty_maintenance' => 'الضمان والصيانة المجانية', 'supply_installation' => 'التوريد والتركيب'
];

$settings_fields = [
    'opening_sentence' => 6851, 'introductory_sentence' => 6979, 'closing_sentence' => 6982, 'elevator_control_system' => 6863, 'operation_method' => 6864, 'stops_naming' => 6865, 'electrical_current' => 6866, 'machine_type' => 6862, 'rails_elevator' => 6873, 'rails_counterweight' => 6874, 'traction_cables' => 6875, 'flexible_cable' => 6876, 'elevator_frame' => 6877, 'car_finishing' => 6878, 'car_internal_dimensions' => 6879, 'ceiling' => 6880, 'emergency_lighting' => 6881, 'car_movement_device' => 6882, 'flooring' => 6883, 'door_operation_method' => 7009, 'internal_door' => 6872, 'door_dimensions' => 7010, 'cop_panel' => 6885, 'lop_finishing' => 6886, 'lop_main_floor' => 6887, 'lop_other_floors' => 6888, 'safety_electrical_devices' => 6889, 'emergency_devices' => 6890, 'lighting_devices' => 6891, 'safety_devices' => 6892, 'light_curtain' => 6893, 'speed_governor' => 6894, 'shock_absorbers' => 6895, 'travel_end_device' => 6896, 'door_safety_cam' => 6897, 'car_guides' => 6898, 'external_door_switch' => 6899, 'electrical_connections' => 6900, 'preparatory_works' => 6903, 'warranty_maintenance' => 6904, 'supply_installation' => 6975
];

$long_text_fields = ['introductory_sentence', 'closing_sentence', 'preparatory_works', 'warranty_maintenance', 'supply_installation', 'car_internal_dimensions', 'cop_panel', 'lop_other_floors', 'door_dimensions'];

$quote_fields_for_saving = [
    'opening_sentence' => 6791, 'introductory_sentence' => 6978, 'closing_sentence' => 6981, 'elevator_control_system' => 6803, 'operation_method' => 6804, 'stops_naming' => 6805, 'electrical_current' => 6806, 'machine_type' => 6802, 'rails_elevator' => 6813, 'rails_counterweight' => 6814, 'traction_cables' => 6815, 'flexible_cable' => 6816, 'elevator_frame' => 6817, 'car_finishing' => 6818, 'car_internal_dimensions' => 6819, 'ceiling' => 6820, 'emergency_lighting' => 6821, 'car_movement_device' => 6822, 'flooring' => 6823, 'door_operation_method' => 6998, 'internal_door' => 7000, 'door_dimensions' => 6999, 'cop_panel' => 6825, 'lop_finishing' => 6826, 'lop_main_floor' => 6827, 'lop_other_floors' => 6828, 'safety_electrical_devices' => 6829, 'emergency_devices' => 6830, 'lighting_devices' => 6831, 'safety_devices' => 6832, 'light_curtain' => 6833, 'speed_governor' => 6834, 'shock_absorbers' => 6835, 'travel_end_device' => 6836, 'door_safety_cam' => 6837, 'car_guides' => 6838, 'external_door_switch' => 6839, 'electrical_connections' => 6840, 'preparatory_works' => 6905, 'warranty_maintenance' => 6906, 'supply_installation' => 6974
];

$user_id = $_SESSION['user_id'] ?? null;
$quote_id = $_GET['quote_id'] ?? null;
if (!$user_id) { header('Location: ../login.php'); exit; }
if (!$quote_id) { die('خطأ: معرف عرض السعر مفقود.'); }

function makeBaserowRequest($url, $method = 'GET', $data = null) {
    global $api_token; $ch = curl_init();
    curl_setopt_array($ch, [CURLOPT_URL => $url, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30, CURLOPT_HTTPHEADER => ['Authorization: Token ' . $api_token, 'Content-Type: application/json']]);
    if ($method === 'POST') { curl_setopt($ch, CURLOPT_POST, true); if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data)); }
    elseif ($method === 'PATCH') { curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH'); if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data)); }
    $response = curl_exec($ch); $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE); $error = curl_error($ch);
    curl_close($ch);
    if ($http_code >= 200 && $http_code < 300) return json_decode($response, true) ?? ['error' => true, 'message' => 'فشل فك ترميز JSON'];
    return ['error' => true, 'message' => "خطأ اتصال (كود: $http_code) - $error", 'response_body' => $response];
}

function findBrandInValue($data_to_check) {
    $valid_brands = ['ALFA PRO', 'ALFA ELITE'];
    if (is_string($data_to_check)) { $upper_value = strtoupper(trim($data_to_check)); if (in_array($upper_value, $valid_brands)) return $upper_value; }
    elseif (is_array($data_to_check)) { foreach ($data_to_check as $value) { $found = findBrandInValue($value); if ($found) return $found; } }
    return null;
}

function extractBrandValue($brand_field_data) {
    $default_brand = 'ALFA PRO'; if (empty($brand_field_data)) return $default_brand;
    $data_to_check = is_array($brand_field_data) ? $brand_field_data : [$brand_field_data];
    foreach ($data_to_check as $item) { if (is_array($item) && isset($item['value'])) { $found_brand = findBrandInValue($item['value']); if ($found_brand) return $found_brand; } }
    return $default_brand;
}

// إضافة دالة جديدة لاستخراج نوع الباب
function extractDoorTypeValue($door_type_field_data) {
    $valid_door_types = ['أوتوماتيك', 'نصف أوتوماتيك'];
    $default_door_type = 'أوتوماتيك';
    
    if (empty($door_type_field_data)) return $default_door_type;
    
    $data_to_check = is_array($door_type_field_data) ? $door_type_field_data : [$door_type_field_data];
    
    foreach ($data_to_check as $item) {
        if (is_array($item) && isset($item['value'])) {
            $value = $item['value'];
            if (is_array($value) && isset($value['value'])) {
                $door_type = trim($value['value']);
                if (in_array($door_type, $valid_door_types)) {
                    return $door_type;
                }
            } elseif (is_string($value)) {
                $door_type = trim($value);
                if (in_array($door_type, $valid_door_types)) {
                    return $door_type;
                }
            }
        }
    }
    
    return $default_door_type;
}

function processOptionsJSON($json_string) {
    if (empty($json_string)) return ['options' => [], 'defaults' => []];
    
    $data = json_decode($json_string, true); 
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
        return ['options' => [], 'defaults' => []];
    }
    
    // استخراج الخيارات
    $options = $data['options'] ?? []; 
    if (!is_array($options)) {
        $options = is_array($data) ? array_filter($data, 'is_string') : [];
    }
    
    // استخراج الافتراضيات
    $defaults = [];
    
    // الطريقة الجديدة: brand_defaults
    if (isset($data['brand_defaults']) && is_array($data['brand_defaults'])) {
        $defaults = $data['brand_defaults'];
    }
    // الطريقة القديمة: default
    elseif (isset($data['default'])) {
        $defaults['general'] = $data['default'];
    }
    
    return ['options' => array_values($options), 'defaults' => $defaults];
}

function buildOptionsJSON($options, $defaults) {
    $cleanOptions = array_values(array_filter($options, fn($o) => !empty(trim($o)))); 
    if (empty($cleanOptions)) return json_encode(['options' => []], JSON_UNESCAPED_UNICODE);
    
    $data = ['options' => $cleanOptions];
    
    // إذا كان هناك أي إعدادات افتراضية، احفظها في brand_defaults
    if (!empty($defaults)) {
        // تنظيف الافتراضيات وإزالة القيم الفارغة
        $cleanDefaults = array_filter($defaults, fn($v) => !empty(trim($v)));
        if (!empty($cleanDefaults)) {
            $data['brand_defaults'] = $cleanDefaults;
        }
    }
    
    // إذا لم تكن هناك افتراضيات، اجعل أول خيار هو الافتراضي العام
    if (empty($data['brand_defaults']) && !empty($cleanOptions)) {
        $data['default'] = $cleanOptions[0];
    }
    
    return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

// دالة جديدة لاختيار القيمة الافتراضية بناءً على البراند ونوع الباب
function selectDefaultValue($defaults, $quote_brand, $quote_door_type) {
    // التحقق من وجود افتراضيات
    if (empty($defaults) || !is_array($defaults)) {
        return null;
    }
    
    // البحث عن التركيبة المحددة (Brand + Door Type)
    $combined_key = $quote_brand . '_' . $quote_door_type;
    if (isset($defaults[$combined_key]) && !empty($defaults[$combined_key])) {
        return $defaults[$combined_key];
    }
    
    // البحث عن البراند فقط
    if (isset($defaults[$quote_brand]) && !empty($defaults[$quote_brand])) {
        return $defaults[$quote_brand];
    }
    
    // البحث عن نوع الباب فقط
    if (isset($defaults[$quote_door_type]) && !empty($defaults[$quote_door_type])) {
        return $defaults[$quote_door_type];
    }
    
    // البحث عن "الكل"
    if (isset($defaults['الكل']) && !empty($defaults['الكل'])) {
        return $defaults['الكل'];
    }
    
    // القيمة الافتراضية العامة (التوافق مع النظام القديم)
    if (isset($defaults['general']) && !empty($defaults['general'])) {
        return $defaults['general'];
    }
    
    // إذا لم نجد شيء، أرجع أول قيمة موجودة
    foreach ($defaults as $key => $value) {
        if (!empty($value)) {
            return $value;
        }
    }
    
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    try {
        switch ($_POST['action']) {
            case 'get_options':
                $field_type = trim($_POST['field_type'] ?? ''); 
                $quote_brand = trim($_POST['quote_brand'] ?? 'ALFA PRO');
                $quote_door_type = trim($_POST['quote_door_type'] ?? 'أوتوماتيك');
                
                if (empty($field_type) || !isset($GLOBALS['settings_fields'][$field_type])) {
                    exit(json_encode(['success' => false, 'message' => 'نوع الحقل غير صالح']));
                }
                
                $settings = makeBaserowRequest($GLOBALS['baserow_url'] . '/api/database/rows/table/' . $GLOBALS['settings_table_id'] . '/' . $GLOBALS['settings_record_id'] . '/');
                if (isset($settings['error'])) {
                    exit(json_encode(['success' => false, 'message' => 'خطأ في جلب الإعدادات: ' . $settings['message']]));
                }
                
                $field_id = $GLOBALS['settings_fields'][$field_type]; 
                $json_data = $settings['field_' . $field_id] ?? '';
                
                error_log("جلب الخيارات - الحقل: $field_type");
                error_log("البراند: $quote_brand, نوع الباب: $quote_door_type");
                error_log("البيانات المسترجعة: " . $json_data);
                
                $processed = processOptionsJSON($json_data);
                $default_value = selectDefaultValue($processed['defaults'], $quote_brand, $quote_door_type) ?? ($processed['options'][0] ?? null);
                
                error_log("القيمة الافتراضية المختارة: " . ($default_value ?? 'لا توجد'));
                
                echo json_encode([
                    'success' => true, 
                    'options' => $processed['options'], 
                    'defaults' => $processed['defaults'], 
                    'current_default' => $default_value, 
                    'is_long_text' => in_array($field_type, $GLOBALS['long_text_fields'])
                ]);
                break;
                
            case 'update_options':
                $field_type = trim($_POST['field_type'] ?? ''); $options = json_decode($_POST['options'] ?? '[]', true); $defaults = json_decode($_POST['defaults'] ?? '{}', true);
                if (empty($field_type) || !isset($GLOBALS['settings_fields'][$field_type])) exit(json_encode(['success' => false, 'message' => 'نوع الحقل غير صالح']));
                if (!is_array($options) || !is_array($defaults)) exit(json_encode(['success' => false, 'message' => 'بيانات غير صالحة']));
                $field_id = $GLOBALS['settings_fields'][$field_type]; $json_value = buildOptionsJSON($options, $defaults);
                $response = makeBaserowRequest($GLOBALS['baserow_url'] . '/api/database/rows/table/' . $GLOBALS['settings_table_id'] . '/' . $GLOBALS['settings_record_id'] . '/', 'PATCH', ['field_' . $field_id => $json_value]);
                echo isset($response['error']) ? json_encode(['success' => false, 'message' => 'فشل الحفظ: ' . $response['message']]) : json_encode(['success' => true, 'message' => 'تم تحديث الخيارات بنجاح']);
                break;
                
            case 'update_quote':
                $quote_id_post = $_POST['quote_id'] ?? ''; if (empty($quote_id_post)) exit(json_encode(['success' => false, 'message' => 'معرف عرض السعر مفقود']));
                $data = []; foreach ($GLOBALS['quote_fields_for_saving'] as $name => $id) { if (isset($_POST[$name])) $data['field_' . $id] = trim($_POST[$name]); }
                if (empty($data)) exit(json_encode(['success' => false, 'message' => 'لا توجد بيانات للتحديث']));
                $response = makeBaserowRequest($GLOBALS['baserow_url'] . '/api/database/rows/table/' . $GLOBALS['quotes_table_id'] . '/' . $quote_id_post . '/', 'PATCH', $data);
                echo isset($response['error']) ? json_encode(['success' => false, 'message' => 'فشل تحديث عرض السعر: ' . $response['message']]) : json_encode(['success' => true, 'message' => 'تم تحديث تفاصيل المصعد بنجاح.', 'redirect' => "5.php?quote_id=$quote_id_post"]);
                break;
                
            default: echo json_encode(['success' => false, 'message' => 'عملية غير معروفة.']); break;
        }
    } catch (Exception $e) { error_log("AJAX Error: " . $e->getMessage()); echo json_encode(['success' => false, 'message' => 'حدث خطأ غير متوقع في الخادم.']); }
    exit;
}

$quote_url = $baserow_url . '/api/database/rows/table/' . $quotes_table_id . '/' . $quote_id . '/?user_field_names=true';
$current_quote = makeBaserowRequest($quote_url);
if (isset($current_quote['error'])) die("<h1>خطأ في جلب بيانات عرض السعر</h1><p>{$current_quote['message']}</p><pre>" . htmlspecialchars($current_quote['response_body'] ?? '') . "</pre>");

$quote_brand_data = $current_quote['البراند'] ?? null; 
$quote_brand = extractBrandValue($quote_brand_data); 
$quote_ref_id = $current_quote['id'] ?? $quote_id;

// استخراج نوع الباب
$quote_door_type_data = $current_quote['نوع الأبواب'] ?? null;
$quote_door_type = extractDoorTypeValue($quote_door_type_data);


<!DOCTYPE html>

}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>واجهة موحدة</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&family=Tajawal:wght@400;500;600;700&display=swap" rel="stylesheet"><link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet"><link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"><link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css" rel="stylesheet" /><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

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
    </style><style>
        :root {
            --gold: #977e2b; --gold-hover: #b89635; --gold-light: rgba(151, 126, 43, 0.1);
            --dark-gray: #2c2c2c; --medium-gray: #666; --light-gray: #f8f9fa; --white: #ffffff;
            --radius-sm: 6px; --radius-md: 12px; --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1); --transition-fast: all 0.15s ease;
            --transition-normal: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); --space-md: 20px; --space-lg: 30px;
        }
        
        * { box-sizing: border-box; }
        
        body {
            font-family: 'Cairo', 'Tajawal', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--light-gray); margin: 0; padding: 0; line-height: 1.5;
            font-size: 14px; color: var(--dark-gray);
        }
        
        .page-loader {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(255, 255, 255, 0.9); display: flex; align-items: center;
            justify-content: center; z-index: 9999;
        }
        
        .spinner {
            width: 24px; height: 24px; border: 2px solid #e5e7eb;
            border-top: 2px solid var(--gold); border-radius: 50%; animation: spin 1s linear infinite;
        }
        
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        
        .container {
            max-width: 640px; margin: 0 auto; padding: var(--space-md); min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
        }
        
        .main-card {
            background: var(--white); border-radius: var(--radius-md); box-shadow: var(--shadow-md);
            width: 100%; max-width: 600px; border: 1px solid #e5e7eb;
        }
        
        @media (max-width: 768px) {
            body { background: var(--white); }
            .container { padding: 0; min-height: 100vh; align-items: stretch; }
            .main-card {
                border-radius: 0; box-shadow: none; border: none; min-height: 100vh;
                max-width: none; display: flex; flex-direction: column;
            }
            .card-header { padding: 20px 16px 16px; border-bottom: 1px solid #f3f4f6; background: var(--white); }
            .card-body {
                flex: 1; padding: 20px 16px; background: var(--white);
                display: flex; flex-direction: column;
            }
            .form-grid { grid-template-columns: 1fr; gap: 20px; }
            .form-control, .form-input, .form-select { font-size: 16px; padding: 12px; }
        }
        
        .card-header { padding: 24px 24px 16px; border-bottom: 1px solid #f3f4f6; }
        
        .card-title {
            font-size: 18px; font-weight: 600; color: var(--dark-gray); margin: 0;
            display: flex; align-items: center; gap: 8px;
        }
        
        .card-title i { color: var(--gold); }
        .card-body { padding: 24px; }
        .form-grid { display: grid; gap: var(--space-md); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-row.single { grid-template-columns: 1fr; }
        .form-group { margin-bottom: 0; position: relative; }
        .form-group.full-width { grid-column: 1 / -1; }
        
        .form-label {
            display: block; font-size: 13px; font-weight: 500;
            color: var(--medium-gray); margin-bottom: 6px;
        }
        
        .required { color: #dc2626; }
        
        .form-control, .form-input, .form-select {
            width: 100%; padding: 10px 12px; border: 1px solid #d1d5db;
            border-radius: var(--radius-sm); font-size: 14px; transition: var(--transition-fast);
            background: var(--white); color: var(--dark-gray);
        }
        
        .form-control:focus, .form-input:focus, .form-select:focus {
            outline: none; border-color: var(--gold); box-shadow: 0 0 0 3px var(--gold-light);
        }
        
        .form-control:disabled, .form-input:disabled, .form-select:disabled {
            background: #f9fafb; color: var(--medium-gray);
        }
        
        .form-select {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23666' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: left 12px center; background-repeat: no-repeat;
            background-size: 16px 12px; padding-left: 40px;
        }
        
        .options-container { display: flex; align-items: center; gap: 8px; }
        .options-select { flex: 1; }
        
        .options-manage-btn {
            background: var(--gold-light); border: 1px solid var(--gold); color: var(--gold);
            padding: 10px 12px; border-radius: var(--radius-sm); cursor: pointer;
            transition: var(--transition-fast); font-size: 14px; display: flex;
            align-items: center; gap: 4px;
        }
        
        .options-manage-btn:hover { background: var(--gold); color: var(--white); }
        
        .search-results {
            position: absolute; top: 100%; left: 0; right: 0; background: var(--white);
            border: 1px solid #e5e7eb; border-radius: var(--radius-sm); box-shadow: var(--shadow-lg);
            z-index: 100; max-height: 250px; overflow-y: auto; display: none;
        }
        
        .search-result-item {
            padding: 12px; cursor: pointer; border-bottom: 1px solid #f3f4f6;
            transition: var(--transition-fast);
        }
        
        .search-result-item:hover { background: var(--gold-light); }
        .search-result-item:last-child { border-bottom: none; }
        
        .result-name { font-weight: 500; color: var(--dark-gray); font-size: 14px; }
        .result-details { font-size: 13px; color: var(--medium-gray); margin-top: 2px; }
        .result-city { font-weight: 500; color: var(--gold); }
        
        .section-title {
            font-size: 16px; font-weight: 600; color: var(--dark-gray);
            margin: var(--space-lg) 0 var(--space-md) 0; display: flex; align-items: center;
            gap: 8px; padding-bottom: 8px; border-bottom: 2px solid var(--gold-light);
        }
        
        .section-title:first-child { margin-top: 0; }
        .section-title i { color: var(--gold); }
        
        .dimensions-row { 
            display: flex; 
            align-items: center; 
            gap: 16px; 
            justify-content: center;
        }
        
        .dimensions-input { 
            flex: 1;
            text-align: center;
            position: relative;
            max-width: 180px;
        }
        
        .dimensions-input .form-input { 
            padding: 12px; 
            text-align: center; 
            width: 100%;
        }
        
        .dimensions-unit {
            margin-top: 8px;
            font-size: 12px; 
            color: var(--medium-gray); 
            text-align: center;
            font-weight: 500;
            background: var(--gold-light);
            padding: 4px 8px;
            border-radius: 4px;
            display: inline-block;
        }
        
        .dimensions-separator { 
            font-size: 20px; 
            font-weight: bold; 
            color: var(--gold);
            margin: 0 8px;
            align-self: flex-start;
            margin-top: 10px;
        }
        
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 6px;
            padding: 12px 24px; border: none; border-radius: var(--radius-sm);
            font-size: 14px; font-weight: 600; cursor: pointer; transition: var(--transition-normal);
            text-decoration: none; width: 100%; background: var(--gold); color: var(--white);
        }
        
        .btn:hover { background: var(--gold-hover); transform: translateY(-2px); box-shadow: var(--shadow-lg); }
        .btn:disabled { background: #9ca3af; cursor: not-allowed; transform: none; }
        .btn.loading { pointer-events: none; }
        .btn .spinner { width: 16px; height: 16px; border: 2px solid rgba(255, 255, 255, 0.3); border-top: 2px solid var(--white); }
        
        .status-display {
            background: var(--gold-light); border: 1px solid rgba(151, 126, 43, 0.2);
            border-radius: var(--radius-sm); padding: 10px 12px; margin-bottom: 16px;
            font-size: 13px; color: var(--gold);
        }
        
        .status-display.hidden { display: none; }
        
        .message {
            padding: 12px; border-radius: var(--radius-sm); margin-bottom: 16px;
            font-size: 13px; display: flex; align-items: center; gap: 8px;
        }
        
        .message.success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .message.error { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .hidden { display: none !important; }
        
        .modal-backdrop {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
            background: rgba(0, 0, 0, 0.6); z-index: 9999; 
            display: none; justify-content: center; align-items: center;
        }
        
        .modal-container {
            background: white; border-radius: 12px; width: 90%; max-width: 500px; 
            max-height: 90vh; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            animation: modalFadeIn 0.3s ease;
        }
        
        @keyframes modalFadeIn {
            from { opacity: 0; transform: scale(0.8); }
            to { opacity: 1; transform: scale(1); }
        }
        
        .modal-header {
            padding: 20px 24px; border-bottom: 1px solid #eee; 
            display: flex; justify-content: space-between; align-items: center;
        }
        
        .modal-title { font-size: 18px; font-weight: 600; color: #333; }
        .modal-close { background: none; border: none; font-size: 24px; color: #999; cursor: pointer; }
        .modal-close:hover { color: #333; }
        
        .modal-body { padding: 20px 24px; max-height: 70vh; overflow-y: auto; }
        
        .options-list { list-style: none; margin: 0; padding: 0; }
        .option-item {
            display: flex; justify-content: space-between; align-items: center; 
            padding: 12px; border-bottom: 1px solid #eee;
        }
        .option-item:last-child { border-bottom: none; }
        .option-item.default { background: var(--gold-light); }
        
        .option-text { flex: 1; }
        .option-controls { display: flex; gap: 8px; }
        
        .option-btn {
            background: none; border: none; padding: 6px 8px; 
            border-radius: 4px; cursor: pointer; font-size: 14px;
        }
        .option-btn:hover { background: #f0f0f0; }
        .option-btn.edit { color: #007bff; }
        .option-btn.default { color: var(--gold); }
        .option-btn.delete { color: #dc3545; }
        
        .add-section { margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; }
        .add-form { display: flex; gap: 10px; }
        .add-input { flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 6px; }
        .add-btn { 
            background: var(--gold); color: white; border: none; 
            padding: 10px 15px; border-radius: 6px; cursor: pointer; 
        }
        .add-btn:hover { background: var(--gold-hover); }
        
        .edit-form { display: flex; gap: 8px; width: 100%; }
        .edit-input { flex: 1; padding: 6px; border: 1px solid #ddd; border-radius: 4px; }
        .edit-save { background: #28a745; color: white; border: none; padding: 6px 10px; border-radius: 4px; }
        .edit-cancel { background: #6c757d; color: white; border: none; padding: 6px 10px; border-radius: 4px; }
        
        @media (max-width: 576px) {
            .form-control, .form-input, .form-select { font-size: 16px; padding: 12px; }
            .modal-container { width: 95%; }
            .dimensions-row { 
                gap: 12px;
            }
            .dimensions-input { 
                max-width: 140px;
            }
            .dimensions-separator { 
                font-size: 18px;
                margin: 0 4px;
            }
        }
        
        @media (max-height: 600px) {
            .container { align-items: flex-start; padding-top: var(--space-md); }
            .main-card { margin-bottom: var(--space-md); }
        }
    </style><style>
        :root {
            --gold: #977e2b;
            --gold-hover: #b89635;
            --gold-light: rgba(151, 126, 43, 0.08);
            --dark-gray: #2c2c2c;
            --medium-gray: #5a5a5a;
            --light-gray: #fafafa;
            --white: #ffffff;
            --border-color: #e0e0e0;
            --radius-sm: 6px;
            --radius-md: 12px;
            --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition-fast: all 0.2s ease-in-out;
            --success-color: #28a745;
            --success-light: #f0fdf4;
            --error-color: #dc3545;
            --error-light: #fef2f2;
            --increase-color: #28a745;
            --increase-light: #f0f9f0;
            --discount-color: #dc3545;
            --discount-light: #fdf2f2;
        }

        body { font-family: 'Cairo', sans-serif; background: var(--light-gray); color: var(--dark-gray); font-size: 14px; }
        .page-loader { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.9); display: flex; align-items: center; justify-content: center; z-index: 9999; }
        .spinner { width: 24px; height: 24px; border: 2px solid var(--border-color); border-top-color: var(--gold); border-radius: 50%; animation: spin 1s linear infinite; }
        .btn-spinner { width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.5); border-top-color: var(--white); }
        @keyframes spin { 100% { transform: rotate(360deg); } }

        .container { max-width: 1200px; padding: 20px; }
        .main-card { background: var(--white); border-radius: var(--radius-md); box-shadow: var(--shadow-md); border: 1px solid var(--border-color); overflow: hidden; margin-bottom: 20px; }
        .card-header { padding: 20px 24px; border-bottom: 1px solid var(--border-color); }
        .card-title { font-size: 18px; font-weight: 600; margin: 0; display: flex; align-items: center; gap: 8px; }
        .card-title i { color: var(--gold); }
        .card-body { padding: 24px; }

        .section { margin-bottom: 32px; }
        .section-title { font-size: 16px; font-weight: 600; color: var(--dark-gray); margin-bottom: 16px; display: flex; align-items: center; gap: 8px; padding-bottom: 8px; border-bottom: 2px solid var(--gold-light); }
        .section-title i { color: var(--gold); }

        .filters-container { display: flex; flex-wrap: wrap; align-items: center; gap: 16px; margin-bottom: 24px; padding: 16px; background: #f6f6f6; border-radius: var(--radius-sm); border: 1px solid var(--border-color); }
        .filter-group { display: flex; align-items: center; gap: 8px; }
        .filter-label { font-size: 13px; font-weight: 500; color: var(--medium-gray); white-space: nowrap; }
        .filter-options { display: flex; gap: 6px; }
        .filter-option { padding: 6px 12px; background: var(--white); border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 13px; cursor: pointer; transition: var(--transition-fast); white-space: nowrap; }
        .filter-option:hover { border-color: var(--gold); background: var(--gold-light); }
        .filter-option.active { background: var(--gold); color: var(--white); border-color: var(--gold); }
        
        .table-responsive { overflow-x: auto; }
        .modern-table { width: 100%; border-collapse: collapse; }
        .modern-table th, .modern-table td { padding: 12px 15px; text-align: right; border-bottom: 1px solid var(--border-color); white-space: nowrap; vertical-align: middle; }
        .modern-table thead th { background-color: #f6f6f6; font-size: 12px; font-weight: 600; color: var(--medium-gray); text-transform: uppercase; }
        .modern-table tbody tr:nth-child(2n) { background-color: var(--light-gray); }
        .modern-table tbody tr:hover { background-color: var(--gold-light); }
        .modern-table .brand-tag { background-color: var(--gold-light); color: var(--gold); padding: 4px 8px; border-radius: 4px; font-weight: 500; }
        .modern-table .not-specified { color: #9ca3af; }
        .price-wrapper { display: flex; align-items: center; gap: 6px; font-weight: 600; color: var(--dark-gray); }
        .currency-icon { width: 14px; height: 14px; vertical-align: middle; }

        .btn-select { background: var(--gold); color: var(--white); border: none; padding: 8px 16px; border-radius: var(--radius-sm); font-size: 13px; font-weight: 500; cursor: pointer; transition: var(--transition-fast); display: flex; align-items: center; justify-content: center; gap: 6px; min-width: 80px; }
        .btn-select:hover { background: var(--gold-hover); }
        .btn-select:disabled { background: var(--medium-gray); cursor: not-allowed; }
        .btn-select.selected { background: var(--success-color); }
        
        .no-results-row td { text-align: center !important; padding: 40px; color: var(--medium-gray); }
        .no-results-row i { font-size: 32px; color: #d1d5db; margin-bottom: 8px; display: block; }

        .form-group { margin-bottom: 20px; }
        .form-label { display: block; font-size: 14px; font-weight: 500; color: var(--medium-gray); margin-bottom: 8px; }
        .form-control { width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 14px; transition: var(--transition-fast); }
        .form-control:focus { outline: none; border-color: var(--gold); box-shadow: 0 0 0 3px var(--gold-light); }

        .additions-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .addition-card { background: var(--white); border: 2px solid var(--border-color); border-radius: var(--radius-sm); padding: 16px; cursor: pointer; transition: var(--transition-fast); position: relative; }
        .addition-card:hover { border-color: var(--gold); box-shadow: 0 2px 8px rgba(151, 126, 43, 0.1); }
        .addition-card.selected { border-color: var(--gold); background: var(--gold-light); }
        .addition-card.selected::before { content: ''; position: absolute; top: 8px; left: 8px; width: 20px; height: 20px; background: var(--gold); border-radius: 50%; }
        .addition-card.selected::after { content: '✓'; position: absolute; top: 8px; left: 8px; width: 20px; height: 20px; color: var(--white); font-size: 12px; font-weight: bold; display: flex; align-items: center; justify-content: center; }
        .addition-name { font-size: 16px; font-weight: 600; color: var(--dark-gray); margin-bottom: 8px; }
        .addition-price { display: flex; align-items: center; gap: 6px; margin-bottom: 8px; }
        .price-value { font-size: 18px; font-weight: 600; color: var(--gold); }
        .addition-effect { font-size: 13px; color: var(--medium-gray); background: #f3f4f6; padding: 4px 8px; border-radius: 4px; display: inline-block; }

        .calculation-card { background: linear-gradient(135deg, #f8fafc, #e2e8f0); border: 1px solid #cbd5e1; border-radius: var(--radius-sm); padding: 20px; margin: 20px 0; }
        .calc-title { font-size: 16px; font-weight: 600; color: var(--dark-gray); margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
        .calc-row { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #e2e8f0; }
        .calc-row:last-child { border-bottom: 2px solid var(--gold); font-weight: 600; color: var(--gold); font-size: 16px; }
        .calc-label { flex: 1; }
        .calc-value { font-weight: 500; display: flex; align-items: center; gap: 4px; }

        .discount-toggle { background: none; border: 1px dashed var(--discount-color); color: var(--discount-color); padding: 6px 12px; border-radius: var(--radius-sm); font-size: 12px; cursor: pointer; transition: var(--transition-fast); margin: 12px 0; }
        .discount-toggle:hover { background: var(--discount-light); }
        .discount-section { display: none; background: var(--discount-light); border: 1px solid #f5b5b5; border-radius: var(--radius-sm); padding: 12px; margin-top: 8px; }
        .discount-section.show { display: block; }
        .discount-info { font-size: 12px; color: var(--discount-color); margin-top: 4px; }

        .increase-toggle { background: none; border: 1px dashed var(--increase-color); color: var(--increase-color); padding: 6px 12px; border-radius: var(--radius-sm); font-size: 12px; cursor: pointer; transition: var(--transition-fast); margin: 12px 0; }
        .increase-toggle:hover { background: var(--increase-light); }
        .increase-section { display: none; background: var(--increase-light); border: 1px solid #b5e5b5; border-radius: var(--radius-sm); padding: 12px; margin-top: 8px; }
        .increase-section.show { display: block; }
        .increase-info { font-size: 12px; color: var(--increase-color); margin-top: 4px; }

        .message { padding: 12px; border-radius: var(--radius-sm); margin-bottom: 16px; font-size: 13px; display: none; align-items: center; gap: 8px; }
        .message.show { display: flex; }
        .message.success { background: var(--success-light); color: #166534; border: 1px solid #bbf7d0; }
        .message.error { background: var(--error-light); color: #991b1b; border: 1px solid #fecaca; }
        .message.info { background: #dbeafe; color: #1e40af; border: 1px solid #93c5fd; }

        .action-buttons { display: flex; gap: 16px; justify-content: flex-end; margin-top: 32px; }
        .btn { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 24px; border: none; border-radius: var(--radius-sm); font-size: 14px; font-weight: 600; cursor: pointer; transition: var(--transition-fast); }
        .btn-primary { background: var(--gold); color: var(--white); }
        .btn-primary:hover { background: var(--gold-hover); }
        .btn-success { background: var(--success-color); color: var(--white); }
        .btn-success:hover { background: #218838; }
        .btn:disabled { background: var(--medium-gray); cursor: not-allowed; }

        .hidden { display: none !important; }

        @media (max-width: 768px) {
            .additions-grid { grid-template-columns: 1fr; }
            .filters-container { flex-direction: column; align-items: stretch; }
            .filter-group { flex-direction: column; align-items: flex-start; gap: 8px; }
            .filter-options { flex-wrap: wrap; }
            .action-buttons { flex-direction: column; }
        }
    </style><style>
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
        }

        .container {
            max-width: 1200px;
            padding: 16px;
        }

        /* Page Header */
        .page-header {
            background: var(--white);
            border-bottom: 1px solid var(--border-color);
            padding: 20px 0;
            margin-bottom: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .page-title {
            font-size: 18px;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .brand-badge {
            background-color: var(--gold);
            color: var(--white);
            font-size: 14px;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 8px;
        }

        .door-type-badge {
            background-color: var(--medium-gray);
            color: var(--white);
            font-size: 12px;
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 6px;
            margin-right: 8px;
        }

        /* Section Cards */
        .section-card {
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border-color);
            margin-bottom: 24px;
            overflow: hidden;
        }

        .section-header {
            background: linear-gradient(135deg, var(--gold), var(--gold-hover));
            color: var(--white);
            padding: 20px 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin: 0;
        }

        .section-body {
            padding: 32px 24px;
        }

        /* Form Grid */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
        }

        @media (min-width: 992px) {
            .form-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .form-grid .full-width {
                grid-column: 1 / -1;
            }
        }

        /* Forms */
        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-label {
            font-size: 14px;
            font-weight: 600;
            color: var(--dark-gray);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: 'Cairo', sans-serif;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--gold);
            box-shadow: 0 0 0 3px var(--gold-light);
        }

        .input-group {
            display: flex;
        }

        .input-group .form-control {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
            flex: 1;
        }

        .options-manage-btn {
            background: var(--white);
            border: 1px solid var(--border-color);
            color: var(--medium-gray);
            padding: 12px 16px;
            border-top-right-radius: 8px;
            border-bottom-right-radius: 8px;
            border-right: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .options-manage-btn:hover {
            background-color: var(--gold-light);
            border-color: var(--gold);
            color: var(--gold);
        }

        /* Buttons */
        .btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Cairo', sans-serif;
        }

        .btn-primary {
            background: var(--gold);
            color: var(--white);
        }

        .btn-primary:hover {
            background: var(--gold-hover);
            color: var(--white);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .btn-primary.loading, 
        .btn-primary:disabled {
            background: var(--medium-gray);
            color: var(--white);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .btn-secondary {
            background: var(--medium-gray);
            color: var(--white);
        }

        .btn-success {
            background: var(--success);
            color: var(--white);
        }

        /* Messages */
        .message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .message.alert-success {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .message.alert-danger {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .message.alert-warning {
            background: #fffbeb;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        /* Loading Spinner */
        .spinner {
            width: 24px;
            height: 24px;
            border: 2px solid var(--border-color);
            border-top: 2px solid var(--gold);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        .spinner-border-sm {
            width: 16px;
            height: 16px;
            border-width: 2px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Select2 Customization */
        .select2-container { 
            width: 100% !important; 
        }
        
        .input-group .select2-container { 
            flex: 1 1 auto; 
            width: 1% !important; 
        }
        
        .select2-container--bootstrap-5 .select2-selection { 
            height: auto !important; 
            min-height: calc(1.5em + 24px);
            border-color: var(--border-color);
            border-radius: 8px;
            font-family: 'Cairo', sans-serif;
            border-top-left-radius: 0 !important;
            border-bottom-left-radius: 0 !important;
        }
        
        .select2-container--bootstrap-5 .select2-selection__rendered { 
            white-space: normal !important; 
            line-height: 1.5 !important;
            padding: 12px;
        }
        
        .select2-results__option { 
            white-space: normal !important; 
            word-wrap: break-word;
            font-family: 'Cairo', sans-serif;
        }

        .select2-container--bootstrap-5.select2-container--focus .select2-selection {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px var(--gold-light);
        }

        /* Modal */
        .modal-header {
            background-color: var(--gold);
            color: var(--white);
            border-bottom: none;
            padding: 20px 24px;
        }

        .modal-title {
            font-weight: 600;
            font-size: 18px;
        }

        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }

        .modal-body {
            padding: 24px;
        }

        .option-item-text {
            white-space: pre-wrap;
            word-break: break-word;
            line-height: 1.6;
        }

        .list-group-item {
            border: 1px solid var(--border-color);
            padding: 16px;
            margin-bottom: 8px;
            border-radius: 8px;
        }

        .form-footer {
            background: var(--white);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        /* Badge Styles */
        .badge {
            font-size: 12px;
            padding: 4px 8px;
            border-radius: 6px;
            font-weight: 500;
        }

        .bg-primary {
            background-color: var(--gold) !important;
        }

        /* Alert Container */
        .alert-container {
            margin-bottom: 20px;
        }

        /* Filter Selection Styles */
        .filter-selection {
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px;
            margin-bottom: 20px;
        }

        @media (min-width: 768px) {
            .filter-selection {
                grid-template-columns: 1fr 1fr;
            }
        }

        .filter-group {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 16px;
            background: var(--light-gray);
        }

        .filter-label {
            font-weight: 600;
            margin-bottom: 12px;
            color: var(--dark-gray);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 12px;
            }
            
            .section-card {
                border-radius: 8px;
                margin-left: -12px;
                margin-right: -12px;
                margin-bottom: 16px;
            }
            
            .section-body {
                padding: 20px 16px;
            }
            
            .section-header {
                padding: 16px;
            }
            
            .form-control {
                font-size: 16px; /* منع التكبير على iOS */
            }

            .page-title {
                font-size: 16px;
            }

            .btn {
                padding: 10px 16px;
                font-size: 13px;
            }

            .brand-badge {
                font-size: 12px;
                padding: 6px 12px;
            }

            .form-grid {
                gap: 16px;
            }
        }

        .text-gold {
            color: var(--gold) !important;
        }

        .icon-gold {
            color: var(--gold);
        }
    </style>

<style>

/* === تصميم موحد خفيف === */
:root{
  --bg:#0b0f19;
  --card:#151b2b;
  --muted:#8ba3c7;
  --fg:#e6edf3;
  --accent:#4cc3ff;
  --accent-2:#a5d6ff;
  --danger:#ff6b6b;
  --radius:14px;
  --gap:18px;
  --font: system-ui, -apple-system, Segoe UI, Roboto, 'Noto Kufi Arabic', 'Cairo', 'Tahoma', sans-serif;
}
*{box-sizing:border-box}
html,body{height:100%}
body.unified{background:var(--bg);color:var(--fg);margin:0;font-family:var(--font);direction:rtl}
.container{max-width:1100px;margin:0 auto;padding:24px}
.header{
  position:sticky;top:0;background:rgba(11,15,25,.7);backdrop-filter: blur(10px);
  border-bottom:1px solid rgba(255,255,255,.07);z-index:999
}
.header-inner{display:flex;align-items:center;gap:14px;padding:14px 24px}
.brand{font-weight:800;letter-spacing:.5px}
.steps{display:flex;gap:8px;margin-inline-start:auto}
.step{padding:8px 14px;border:1px solid rgba(255,255,255,.1);border-radius:999px;font-size:13px;color:var(--muted)}
.step.active{border-color:var(--accent);color:var(--fg);}
.card{background:var(--card);border:1px solid rgba(255,255,255,.08);border-radius:var(--radius);padding:24px; box-shadow: 0 10px 30px rgba(0,0,0,.25)}
.card + .card{margin-top:var(--gap)}
button, .btn{background:linear-gradient(180deg, var(--accent), var(--accent-2));border:none;border-radius:12px;color:#00182a;font-weight:700;padding:10px 16px;cursor:pointer}
input, select, textarea{background:#0f1422;border:1px solid rgba(255,255,255,.12);border-radius:10px;padding:10px;color:var(--fg);width:100%}
table{width:100%;border-collapse:collapse;background:#0f1422;border-radius:12px;overflow:hidden}
th,td{padding:10px;border-bottom:1px solid rgba(255,255,255,.06);text-align:right}
th{background:#0c1220;color:#bcd2f2}
.notice{padding:10px 14px;border:1px dashed rgba(255,255,255,.2);border-radius:10px;color:#bcd2f2;background:#0f1629}
footer{opacity:.6;padding:24px;text-align:center}
/* حاوية تلتف أي محتوى قديم داخل الكارد */
.unified-slot .card{background:transparent;border:none;box-shadow:none;padding:0}

</style></head>
<body class="unified">
<div class="header">
  <div class="header-inner">
    <div class="brand">واجهة موحدة</div>
    <nav class="steps">
      <a class="step {step=='1'?'active':''}">العميل</a>
      <a class="step {step=='2'?'active':''}">المشروع</a>
      <a class="step {step=='3'?'active':''}">المصعد</a>
      <a class="step {step=='4'?'active':''}">التفاصيل</a>
    </nav>
  </div>
</div>
<div class="container">
<?php if ($step === '1'): ?>
<div class='unified-slot'>
<div class='card'>

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

</div>
</div>
<?php endif; ?>

<?php if ($step === '2'): ?>
<div class='unified-slot'>
<div class='card'>

    <div id="pageLoader" class="page-loader">
        <div class="spinner"></div>
    </div>

    <div id="optionsModal" class="modal-backdrop">
        <div class="modal-container">
            <div class="modal-header">
                <h3 id="modalTitle" class="modal-title">إدارة الخيارات</h3>
                <button class="modal-close" onclick="closeOptionsModal()">&times;</button>
            </div>
            <div class="modal-body">
                <ul id="optionsList" class="options-list"></ul>
                <div class="add-section">
                    <div class="add-form">
                        <input type="text" id="newOptionInput" class="add-input" placeholder="إضافة خيار جديد...">
                        <button class="add-btn" onclick="addNewOption()">إضافة</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="main-card">
            <div class="card-header">
                <h1 class="card-title">
                    <i class="fas fa-building"></i>
                    اختيار المشروع
                </h1>
            </div>
            
            <div class="card-body">
                <div id="messageContainer"></div>
                <div id="projectStatus" class="status-display hidden"></div>
                
                <form id="projectForm" class="form-grid">
                    <div class="form-row single">
                        <div class="form-group full-width">
                            <label class="form-label">
                                <i class="fas fa-project-diagram me-2"></i>
                                اسم المشروع <span class="required">*</span>
                            </label>
                            <div class="position-relative">
                                <input type="text" id="projectName" class="form-input" value="<?php echo htmlspecialchars($suggested_project_name); ?>" placeholder="ابحث عن مشروع أو أدخل اسم مشروع جديد" autocomplete="off">
                                <div id="nameSearchResults" class="search-results"></div>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-map-marker-alt me-2"></i>
                                المدينة <span class="required">*</span>
                            </label>
                            <div class="options-container">
                                <select id="projectCity" class="form-select options-select">
                                    <option value="">اختر المدينة</option>
                                </select>
                                <button type="button" class="options-manage-btn" onclick="openOptionsModal('cities', 'إدارة المدن')">
                                    <i class="fas fa-cog"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-tag me-2"></i>
                                النوع <span class="required">*</span>
                            </label>
                            <div class="options-container">
                                <select id="projectType" class="form-select options-select">
                                    <option value="">اختر النوع</option>
                                </select>
                                <button type="button" class="options-manage-btn" onclick="openOptionsModal('project_types', 'إدارة أنواع المشاريع')">
                                    <i class="fas fa-cog"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row single">
                        <div class="form-group full-width">
                            <label class="form-label">
                                <i class="fas fa-home me-2"></i>
                                العنوان
                            </label>
                            <input type="text" id="projectAddress" class="form-input" placeholder="الحي - الشارع">
                        </div>
                    </div>
                    
                    <div class="form-row single">
                        <div class="form-group full-width">
                            <label class="form-label">
                                <i class="fas fa-map me-2"></i>
                                الموقع
                            </label>
                            <input type="url" id="projectLocation" class="form-input" placeholder="رابط قوقل ماب">
                        </div>
                    </div>

                    <h2 class="section-title">
                        <i class="fas fa-grip-hole"></i>
                        البئر
                    </h2>
                    
                    <div class="form-row single">
                        <div class="form-group full-width">
                            <label class="form-label">
                                <i class="fas fa-tools me-2"></i>
                                بئر مبني من <span class="required">*</span>
                            </label>
                            <div class="options-container">
                                <select id="wellMaterial" class="form-select options-select">
                                    <option value="">اختر المادة</option>
                                </select>
                                <button type="button" class="options-manage-btn" onclick="openOptionsModal('well_materials', 'إدارة مواد البئر')">
                                    <i class="fas fa-cog"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row single">
                        <div class="form-group full-width">
                            <label class="form-label">
                                <i class="fas fa-ruler me-2"></i>
                                المقاس الداخلي للبئر <span class="required">*</span>
                            </label>
                            <div class="dimensions-row">
                                <div class="dimensions-input">
                                    <input type="number" id="wellWidth" class="form-input" placeholder="العرض" min="0">
                                    <div class="dimensions-unit">سم (عرضاً)</div>
                                </div>
                                <div class="dimensions-separator">×</div>
                                <div class="dimensions-input">
                                    <input type="number" id="wellDepth" class="form-input" placeholder="العمق" min="0">
                                    <div class="dimensions-unit">سم (عمقاً)</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" id="proceedBtn" class="btn">
                        <i class="fas fa-arrow-left me-2"></i>
                        <span class="btn-text">متابعة إلى الخطوة التالية</span>
                        <div class="spinner hidden"></div>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fuse.js@7.1.0/dist/fuse.min.js"></script>

    <script>
        let projects = [];
        let selectedProject = null;
        let fuse = null;
        let currentFieldType = null;
        let currentOptions = [];
        let currentDefault = null;
        let editingIndex = -1;

        document.addEventListener('DOMContentLoaded', function() {
            initializeApp();
        });

        function initializeApp() {
            setTimeout(() => {
                document.getElementById('pageLoader').style.display = 'none';
            }, 800);

            bindEvents();
            loadAllData();
        }

        function bindEvents() {
            const projectName = document.getElementById('projectName');
            const projectForm = document.getElementById('projectForm');
            const nameResults = document.getElementById('nameSearchResults');
            const newOptionInput = document.getElementById('newOptionInput');

            projectName.addEventListener('input', searchProjects);
            projectName.addEventListener('focus', searchProjects);
            projectName.addEventListener('blur', function(e) {
                setTimeout(() => {
                    if (!nameResults.contains(document.activeElement)) {
                        hideSearchResults();
                    }
                }, 200);
            });

            projectForm.addEventListener('submit', function(e) {
                e.preventDefault();
                proceedToNext();
            });

            newOptionInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    addNewOption();
                }
            });

            document.addEventListener('click', function(e) {
                const nameGroup = projectName.closest('.form-group');
                if (!nameGroup.contains(e.target)) {
                    hideSearchResults();
                }
            });
        }

        async function loadAllData() {
            try {
                await loadProjects();
                await loadFieldOptions('cities');
                await loadFieldOptions('project_types');
                await loadFieldOptions('well_materials');
            } catch (error) {
                showMessage('خطأ في تحميل البيانات', 'error');
            }
        }

        async function loadProjects() {
            const response = await makeRequest('search_projects', {});
            if (response.success) {
                projects = response.projects;
                initializeFuse();
            } else {
                showMessage('خطأ في تحميل المشاريع: ' + response.message, 'error');
            }
        }

        async function loadFieldOptions(fieldType) {
            const response = await makeRequest('get_options', { field_type: fieldType });
            if (response.success) {
                let selectElement;
                switch (fieldType) {
                    case 'cities': selectElement = document.getElementById('projectCity'); break;
                    case 'project_types': selectElement = document.getElementById('projectType'); break;
                    case 'well_materials': selectElement = document.getElementById('wellMaterial'); break;
                }
                if (selectElement) {
                    populateSelectOptions(selectElement, response.options, response.default);
                }
            }
        }

        function populateSelectOptions(selectElement, options, defaultValue) {
            const currentValue = selectElement.value;
            const placeholder = selectElement.querySelector('option[value=""]');
            selectElement.innerHTML = '';
            if (placeholder) selectElement.appendChild(placeholder);

            options.forEach(option => {
                const optionElement = document.createElement('option');
                optionElement.value = option;
                optionElement.textContent = option;
                selectElement.appendChild(optionElement);
            });

            if (defaultValue && options.includes(defaultValue)) {
                selectElement.value = defaultValue;
            } else if (currentValue && options.includes(currentValue)) {
                selectElement.value = currentValue;
            }
        }

        function initializeFuse() {
            fuse = new Fuse(projects, {
                keys: [{ name: 'name', weight: 1.0 }],
                threshold: 0.4,
                includeScore: true,
                minMatchCharLength: 2,
                ignoreLocation: true
            });
        }

        function searchProjects() {
            const searchTerm = document.getElementById('projectName').value.trim();
            
            if (searchTerm.length < 2) {
                hideSearchResults();
                clearProjectSelection();
                return;
            }

            if (!fuse) return;

            const results = fuse.search(searchTerm);
            if (results.length > 0) {
                showSearchResults(results.map(r => r.item).slice(0, 5));
            } else {
                hideSearchResults();
                showProjectStatus('مشروع جديد - أكمل البيانات للإضافة');
            }
        }

        function showSearchResults(results) {
            const resultsHtml = results.map(project => `
                <div class="search-result-item" onclick="selectProjectFromSearch(${project.id})">
                    <div class="result-name">${project.name || 'غير محدد'}</div>
                    <div class="result-details">
                        <span class="result-city">${project.city || 'غير محدد'}</span>
                        ${project.type ? ' - ' + project.type : ''}
                    </div>
                    ${project.address ? `<div class="result-details">${project.address}</div>` : ''}
                    ${project.well_material ? `<div class="result-details">بئر: ${project.well_material}</div>` : ''}
                </div>
            `).join('');

            document.getElementById('nameSearchResults').innerHTML = resultsHtml;
            document.getElementById('nameSearchResults').style.display = 'block';
        }

        function hideSearchResults() {
            document.getElementById('nameSearchResults').style.display = 'none';
        }

        function selectProjectFromSearch(projectId) {
            const project = projects.find(p => p.id === projectId);
            if (project) {
                selectedProject = project;
                fillProjectForm(project);
                showProjectStatus('تم العثور على المشروع: ' + (project.name || 'غير محدد'));
                hideSearchResults();
            }
        }

        function fillProjectForm(project) {
            document.getElementById('projectName').value = project.name || '';
            document.getElementById('projectCity').value = project.city || '';
            document.getElementById('projectAddress').value = project.address || '';
            document.getElementById('projectLocation').value = project.location || '';
            document.getElementById('projectType').value = project.type || '';
            document.getElementById('wellMaterial').value = project.well_material || '';

            // استخراج أبعاد البئر من النص المحفوظ
            if (project.well_size) {
                const sizeMatch = project.well_size.match(/العرض\s*(\d+)\s*سم\s*x\s*العمق\s*(\d+)\s*سم/);
                if (sizeMatch) {
                    document.getElementById('wellWidth').value = sizeMatch[1];
                    document.getElementById('wellDepth').value = sizeMatch[2];
                }
            }
        }

        function clearProjectSelection() {
            selectedProject = null;
            hideProjectStatus();
        }

        function openOptionsModal(fieldType, title) {
            currentFieldType = fieldType;
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('optionsList').innerHTML = '<li class="option-item"><div class="option-text">جاري التحميل...</div></li>';
            document.getElementById('newOptionInput').value = '';
            
            document.getElementById('optionsModal').style.display = 'flex';
            
            loadOptionsForModal(fieldType);
        }

        async function loadOptionsForModal(fieldType) {
            try {
                const response = await makeRequest('get_options', { field_type: fieldType });
                if (response.success) {
                    currentOptions = response.options || [];
                    currentDefault = response.default || null;
                    renderOptionsList();
                    setTimeout(() => {
                        document.getElementById('newOptionInput').focus();
                    }, 100);
                } else {
                    showMessage('خطأ في تحميل الخيارات: ' + response.message, 'error');
                    closeOptionsModal();
                }
            } catch (error) {
                showMessage('خطأ في الاتصال بالخادم', 'error');
                closeOptionsModal();
            }
        }

        function closeOptionsModal() {
            document.getElementById('optionsModal').style.display = 'none';
            currentFieldType = null;
            editingIndex = -1;
            currentOptions = [];
            currentDefault = null;
        }

        function renderOptionsList() {
            const optionsList = document.getElementById('optionsList');
            
            if (!currentOptions || currentOptions.length === 0) {
                optionsList.innerHTML = `
                    <li class="option-item">
                        <div class="option-text" style="text-align: center; color: #999; padding: 20px;">
                            لا توجد خيارات محفوظة<br>
                            <small>يمكنك إضافة خيارات جديدة أدناه</small>
                        </div>
                    </li>
                `;
                return;
            }

            const optionsHtml = currentOptions.map((option, index) => {
                const isDefault = option === currentDefault;
                const isEditing = editingIndex === index;

                if (isEditing) {
                    return `
                        <li class="option-item ${isDefault ? 'default' : ''}">
                            <div class="edit-form">
                                <input type="text" class="edit-input" value="${option}" id="editInput${index}">
                                <button class="edit-save" onclick="saveEditOption(${index})">حفظ</button>
                                <button class="edit-cancel" onclick="cancelEditOption()">إلغاء</button>
                            </div>
                        </li>
                    `;
                }

                return `
                    <li class="option-item ${isDefault ? 'default' : ''}">
                        <div class="option-text">${option} ${isDefault ? '<span style="color: var(--gold); font-size: 12px;">(افتراضي)</span>' : ''}</div>
                        <div class="option-controls">
                            <button class="option-btn edit" onclick="editOption(${index})" title="تعديل">
                                <i class="fas fa-edit"></i>
                            </button>
                            ${!isDefault ? `
                                <button class="option-btn default" onclick="setDefaultOption(${index})" title="تعيين كافتراضي">
                                    <i class="fas fa-star"></i>
                                </button>
                            ` : `
                                <button class="option-btn default" onclick="removeDefaultOption()" title="إلغاء الافتراضي">
                                    <i class="fas fa-star-half-alt"></i>
                                </button>
                            `}
                            <button class="option-btn delete" onclick="deleteOption(${index})" title="حذف">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </li>
                `;
            }).join('');

            optionsList.innerHTML = optionsHtml;
        }

        function addNewOption() {
            const newOption = document.getElementById('newOptionInput').value.trim();
            
            if (!newOption) {
                document.getElementById('newOptionInput').focus();
                return;
            }

            if (currentOptions.includes(newOption)) {
                showMessage('هذا الخيار موجود بالفعل', 'error');
                document.getElementById('newOptionInput').select();
                return;
            }

            currentOptions.push(newOption);
            saveCurrentOptions();
            document.getElementById('newOptionInput').value = '';
            document.getElementById('newOptionInput').focus();
        }

        function editOption(index) {
            editingIndex = index;
            renderOptionsList();
            setTimeout(() => {
                const editInput = document.getElementById(`editInput${index}`);
                if (editInput) {
                    editInput.focus();
                    editInput.select();
                }
            }, 10);
        }

        function saveEditOption(index) {
            const editInput = document.getElementById(`editInput${index}`);
            const newValue = editInput.value.trim();

            if (!newValue) {
                showMessage('يرجى إدخال قيمة صحيحة', 'error');
                editInput.focus();
                return;
            }

            if (newValue !== currentOptions[index] && currentOptions.includes(newValue)) {
                showMessage('هذا الخيار موجود بالفعل', 'error');
                editInput.select();
                return;
            }

            if (currentDefault === currentOptions[index]) {
                currentDefault = newValue;
            }

            currentOptions[index] = newValue;
            editingIndex = -1;
            saveCurrentOptions();
        }

        function cancelEditOption() {
            editingIndex = -1;
            renderOptionsList();
        }

        function setDefaultOption(index) {
            currentDefault = currentOptions[index];
            saveCurrentOptions();
        }

        function removeDefaultOption() {
            currentDefault = null;
            saveCurrentOptions();
        }

        function deleteOption(index) {
            if (!confirm('هل أنت متأكد من حذف هذا الخيار؟')) return;

            const deletedOption = currentOptions[index];
            if (currentDefault === deletedOption) {
                currentDefault = null;
            }

            currentOptions.splice(index, 1);
            saveCurrentOptions();
        }

        async function saveCurrentOptions() {
            try {
                const response = await makeRequest('update_options', {
                    field_type: currentFieldType,
                    options: JSON.stringify(currentOptions),
                    default: currentDefault
                });

                if (response.success) {
                    renderOptionsList();
                    await loadFieldOptions(currentFieldType);
                    showModalMessage('تم الحفظ بنجاح', 'success');
                } else {
                    showMessage('خطأ في حفظ الخيارات: ' + response.message, 'error');
                }
            } catch (error) {
                showMessage('خطأ في الاتصال بالخادم', 'error');
            }
        }

        function showModalMessage(message, type) {
            const modalBody = document.querySelector('.modal-body');
            const tempMessage = document.createElement('div');
            tempMessage.className = `message ${type}`;
            tempMessage.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
            tempMessage.style.margin = '10px 0';

            modalBody.insertBefore(tempMessage, modalBody.firstChild);

            setTimeout(() => {
                if (tempMessage.parentNode) {
                    tempMessage.parentNode.removeChild(tempMessage);
                }
            }, 2000);
        }

        async function proceedToNext() {
            const name = document.getElementById('projectName').value.trim();
            const city = document.getElementById('projectCity').value;
            const address = document.getElementById('projectAddress').value.trim();
            const type = document.getElementById('projectType').value;
            const location = document.getElementById('projectLocation').value.trim();
            const material = document.getElementById('wellMaterial').value;
            const width = document.getElementById('wellWidth').value.trim();
            const depth = document.getElementById('wellDepth').value.trim();

            if (!name || !city || !type || !material || !width || !depth) {
                showMessage('يرجى ملء جميع الحقول المطلوبة', 'error');
                return;
            }

            setLoading(true);

            try {
                let projectId;

                if (selectedProject) {
                    projectId = selectedProject.id;
                } else {
                    const addResponse = await makeRequest('add_project', {
                        name, city, address, type, location, 
                        well_material: material,
                        well_width: width,
                        well_depth: depth
                    });

                    if (!addResponse.success) {
                        showMessage('خطأ في إضافة المشروع: ' + addResponse.message, 'error');
                        return;
                    }

                    projectId = addResponse.project.id;
                    showMessage('تم إضافة المشروع بنجاح', 'success');
                }

                const selectResponse = await makeRequest('select_project', {
                    project_id: projectId,
                    quote_id: <?php echo json_encode($quote_id); ?>
                });

                if (selectResponse.success) {
                    showMessage('تم ربط المشروع بعرض السعر بنجاح', 'success');
                    setTimeout(() => {
                        window.location.href = selectResponse.redirect;
                    }, 1000);
                } else {
                    showMessage('خطأ في ربط المشروع: ' + selectResponse.message, 'error');
                }
            } catch (error) {
                showMessage('حدث خطأ غير متوقع', 'error');
            } finally {
                setLoading(false);
            }
        }

        async function makeRequest(action, data) {
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

            if (!response.ok) throw new Error('Network error');
            return await response.json();
        }

        function showMessage(message, type) {
            const icon = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
            document.getElementById('messageContainer').innerHTML = `
                <div class="message ${type}">
                    <i class="${icon}"></i>
                    ${message}
                </div>
            `;
            setTimeout(() => {
                document.getElementById('messageContainer').innerHTML = '';
            }, 5000);
        }

        function showProjectStatus(status) {
            const statusEl = document.getElementById('projectStatus');
            statusEl.textContent = status;
            statusEl.classList.remove('hidden');
        }

        function hideProjectStatus() {
            document.getElementById('projectStatus').classList.add('hidden');
        }

        function setLoading(loading) {
            const btn = document.getElementById('proceedBtn');
            const text = btn.querySelector('.btn-text');
            const spinner = btn.querySelector('.spinner');

            if (loading) {
                btn.classList.add('loading');
                btn.disabled = true;
                text.style.display = 'none';
                spinner.classList.remove('hidden');
            } else {
                btn.classList.remove('loading');
                btn.disabled = false;
                text.style.display = 'inline';
                spinner.classList.add('hidden');
            }
        }
    </script>

</div>
</div>
<?php endif; ?>

<?php if ($step === '3'): ?>
<div class='unified-slot'>
<div class='card'>

    <div id="pageLoader" class="page-loader"><div class="spinner"></div></div>

    <div class="container">
        <!-- قسم اختيار المصعد -->
        <div class="main-card" id="elevatorSection">
            <div class="card-header">
                <h1 class="card-title"><i class="fas fa-elevator"></i> اختيار المصعد</h1>
            </div>
            
            <div class="card-body">
                <div id="messageContainer" class="message"></div>
                
                <div class="filters-container">
                    <div class="filter-group">
                        <div class="filter-label">البراند:</div>
                        <div class="filter-options">
                            <div class="filter-option active" data-filter="brand" data-value="">الكل</div>
                            <div class="filter-option" data-filter="brand" data-value="ALFA PRO">ALFA PRO</div>
                            <div class="filter-option" data-filter="brand" data-value="ALFA ELITE">ALFA ELITE</div>
                        </div>
                    </div>
                    <div class="filter-group">
                        <div class="filter-label">الوقفات:</div>
                        <div class="filter-options">
                            <div class="filter-option active" data-filter="stops" data-value="">الكل</div>
                            <div class="filter-option" data-filter="stops" data-value="3">3</div>
                            <div class="filter-option" data-filter="stops" data-value="4">4</div>
                            <div class="filter-option" data-filter="stops" data-value="5">5</div>
                            <div class="filter-option" data-filter="stops" data-value="6">6</div>
                            <div class="filter-option" data-filter="stops" data-value="7">7</div>
                            <div class="filter-option" data-filter="stops" data-value="8">8</div>
                        </div>
                    </div>
                    <div class="filter-group">
                        <div class="filter-label">الأبواب:</div>
                        <div class="filter-options">
                            <div class="filter-option active" data-filter="door_type" data-value="">الكل</div>
                            <div class="filter-option" data-filter="door_type" data-value="أوتوماتيك">أوتوماتيك</div>
                            <div class="filter-option" data-filter="door_type" data-value="نصف أوتوماتيك">نصف أوتوماتيك</div>
                        </div>
                    </div>
                    <div class="filter-group">
                        <div class="filter-label">الجير:</div>
                        <div class="filter-options">
                            <div class="filter-option active" data-filter="gear" data-value="">الكل</div>
                            <div class="filter-option" data-filter="gear" data-value="MR">MR</div>
                            <div class="filter-option" data-filter="gear" data-value="MRL">MRL</div>
                        </div>
                    </div>
                    <div class="filter-group">
                        <div class="filter-label">الحمولة:</div>
                        <div class="filter-options">
                            <div class="filter-option active" data-filter="capacity" data-value="">الكل</div>
                            <div class="filter-option" data-filter="capacity" data-value="630">630</div>
                            <div class="filter-option" data-filter="capacity" data-value="450">450</div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>البراند</th>
                                <th>الوقفات</th>
                                <th>نوع الأبواب</th>
                                <th>الجير</th>
                                <th>الحمولة (كجم)</th>
                                <th>السعر</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="elevatorsTableBody">
                            <!-- سيتم تحميل المصاعد هنا -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- قسم التفاصيل والحساب -->
        <div class="main-card" id="detailsSection" style="display: none;">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-calculator"></i> تفاصيل الطلب وحساب السعر</h2>
            </div>
            
            <div class="card-body">
                <div id="detailsMessage" class="message"></div>

                <!-- عدد المصاعد -->
                <div class="section">
                    <div class="section-title">
                        <i class="fas fa-building"></i>
                        عدد المصاعد
                    </div>
                    <div class="form-group">
                        <label class="form-label">عدد المصاعد المطلوبة</label>
                        <input type="number" id="elevatorsCount" class="form-control" min="1" max="10" 
                               value="<?php echo $current_elevators_count; ?>" style="max-width: 200px;">
                    </div>
                </div>

                <!-- الإضافات -->
                <div class="section">
                    <div class="section-title">
                        <i class="fas fa-plus-circle"></i>
                        إضافات المصعد (اختيارية)
                    </div>
                    <div id="additionsGrid" class="additions-grid">
                        <!-- سيتم تحميل الإضافات هنا -->
                    </div>
                </div>

                <!-- حساب السعر -->
                <div class="calculation-card" id="calculationCard">
                    <div class="calc-title">
                        <i class="fas fa-receipt"></i>
                        تفاصيل السعر
                    </div>
                    <div id="calculationContent">
                        <!-- سيتم عرض الحساب هنا -->
                    </div>
                    
                    <!-- التخفيض -->
                    <button type="button" class="discount-toggle" onclick="toggleDiscount()">
                        <i class="fas fa-tag"></i> إضافة تخفيض
                    </button>
                    <div class="discount-section" id="discountSection">
                        <div class="form-group" style="margin: 0;">
                            <label class="form-label">مبلغ التخفيض</label>
                            <input type="number" id="discountAmount" class="form-control" min="0" step="1" 
                                   value="<?php echo $current_discount; ?>" placeholder="0" style="max-width: 200px;">
                            <div class="discount-info" id="discountInfo">الحد الأقصى: 0 ريال</div>
                        </div>
                    </div>

                    <!-- الزيادة -->
                    <button type="button" class="increase-toggle" onclick="toggleIncrease()">
                        <i class="fas fa-plus-square"></i> إضافة زيادة
                    </button>
                    <div class="increase-section" id="increaseSection">
                        <div class="form-group" style="margin: 0;">
                            <label class="form-label">مبلغ الزيادة</label>
                            <input type="number" id="increaseAmount" class="form-control" min="0" step="1" 
                                   value="<?php echo $current_increase; ?>" placeholder="0" style="max-width: 200px;">
                            <div class="increase-info">يمكن إضافة أي مبلغ إضافي للسعر</div>
                        </div>
                    </div>
                </div>

                <div class="action-buttons">
                    <button type="button" id="calculateBtn" class="btn btn-primary" onclick="calculateFinalPrice()">
                        <i class="fas fa-calculator"></i>
                        <span class="btn-text">حساب وحفظ السعر النهائي</span>
                        <div class="spinner btn-spinner hidden"></div>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        class ElevatorPricingManager {
            constructor() {
                this.allElevators = [];
                this.allAdditions = [];
                this.selectedAdditions = new Set(<?php echo json_encode($current_selected_additions); ?>);
                this.selectedElevator = null;
                this.filters = { brand: '', stops: '', door_type: '', gear: '', capacity: '' };
                this.isLoading = false;
                this.init();
            }
            
            init() {
                this.bindEvents();
                this.loadAllData();
                setTimeout(() => { document.getElementById('pageLoader').style.display = 'none'; }, 800);
            }
            
            bindEvents() {
                // Filter events
                document.querySelectorAll('.filter-option').forEach(option => {
                    option.addEventListener('click', (e) => {
                        if (this.isLoading) return;
                        const target = e.currentTarget;
                        target.parentElement.querySelectorAll('.filter-option').forEach(opt => opt.classList.remove('active'));
                        target.classList.add('active');
                        this.filters[target.dataset.filter] = target.dataset.value;
                        this.applyFilters();
                    });
                });
                
                // Count change
                document.getElementById('elevatorsCount').addEventListener('input', () => {
                    this.updateCalculation();
                });
                
                // Discount change
                document.getElementById('discountAmount').addEventListener('input', () => {
                    this.validateDiscount();
                    this.updateCalculation();
                });

                // Increase change
                document.getElementById('increaseAmount').addEventListener('input', () => {
                    this.updateCalculation();
                });
            }
            
            async loadAllData() {
                this.isLoading = true;
                this.renderTableSpinner();
                
                try {
                    await this.loadElevators();
                    await this.loadAdditions();
                    this.applyFilters();
                    this.renderAdditions();
                    
                    // إذا كان هناك مصعد محدد مسبقاً، عرض قسم التفاصيل
                    <?php if ($current_elevator_id): ?>
                        this.selectedElevator = this.allElevators.find(e => e.id === <?php echo $current_elevator_id; ?>);
                        if (this.selectedElevator) {
                            this.showDetailsSection();
                        }
                    <?php endif; ?>
                } catch (error) {
                    this.showMessage('خطأ في تحميل البيانات', 'error');
                } finally {
                    this.isLoading = false;
                }
            }
            
            async loadElevators() {
                const response = await this.makeRequest('load_elevators', {});
                if (response.success) {
                    this.allElevators = response.elevators;
                } else {
                    throw new Error(response.message || 'خطأ في تحميل المصاعد');
                }
            }
            
            async loadAdditions() {
                const response = await this.makeRequest('load_additions', {});
                if (response.success) {
                    this.allAdditions = response.additions;
                } else {
                    throw new Error(response.message || 'خطأ في تحميل الإضافات');
                }
            }
            
            // فلتر الإضافات بناءً على نوع الأبواب للمصعد المختار
            filterAdditionsByDoorType() {
                if (!this.selectedElevator) {
                    return this.allAdditions;
                }
                
                const elevatorDoorType = this.selectedElevator.door_type;
                
                return this.allAdditions.filter(addition => {
                    // إظهار الإضافات بدون نوع أبواب محدد (قيمة فارغة)
                    if (!addition.door_type || addition.door_type.trim() === '') {
                        return true;
                    }
                    
                    // إظهار الإضافات التي تطابق نوع أبواب المصعد المختار
                    return addition.door_type === elevatorDoorType;
                });
            }
            
            applyFilters() {
                const filteredElevators = this.allElevators.filter(elevator => 
                    Object.keys(this.filters).every(key => 
                        !this.filters[key] || String(elevator[key]) === this.filters[key]
                    )
                );
                this.renderTable(filteredElevators);
            }

            renderTableSpinner() {
                const tableBody = document.getElementById('elevatorsTableBody');
                tableBody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 40px;"><div class="spinner"></div></td></tr>`;
            }
            
            renderTable(elevators) {
                const tableBody = document.getElementById('elevatorsTableBody');
                tableBody.innerHTML = '';

                if (elevators.length === 0) {
                    tableBody.innerHTML = `<tr class="no-results-row"><td colspan="7"><i class="fas fa-search"></i><span>لا توجد مصاعد تطابق بحثك.</span></td></tr>`;
                    return;
                }

                tableBody.innerHTML = elevators.map(e => this.createRowHtml(e)).join('');
                
                tableBody.querySelectorAll('.btn-select').forEach(button => {
                    button.addEventListener('click', (e) => this.handleElevatorSelection(e));
                });
            }

            createRowHtml(elevator) {
                const notSpecified = '<span class="not-specified">غير محدد</span>';
                const isSelected = this.selectedElevator && this.selectedElevator.id === elevator.id;
                
                return `
                    <tr ${isSelected ? 'style="background-color: var(--gold-light);"' : ''}>
                        <td>${elevator.brand ? `<span class="brand-tag">${elevator.brand}</span>` : notSpecified}</td>
                        <td>${elevator.stops || notSpecified}</td>
                        <td>${elevator.door_type || notSpecified}</td>
                        <td>${elevator.gear || notSpecified}</td>
                        <td>${elevator.capacity || notSpecified}</td>
                        <td>
                            <div class="price-wrapper">
                                <span>${this.formatPrice(elevator.price)}</span>
                                <img src="https://alfagolden.com/images/sar.svg" class="currency-icon" alt="عملة ريال سعودي">
                            </div>
                        </td>
                        <td>
                            <button class="btn-select ${isSelected ? 'selected' : ''}" data-elevator-id="${elevator.id}">
                                <span class="btn-text">${isSelected ? 'محدد' : 'اختيار'}</span>
                                <div class="spinner btn-spinner hidden"></div>
                            </button>
                        </td>
                    </tr>
                `;
            }
            
            async handleElevatorSelection(event) {
                if (this.isLoading) return;

                const button = event.currentTarget;
                const elevatorId = parseInt(button.dataset.elevatorId);
                
                this.setLoading(button, true);

                try {
                    const response = await this.makeRequest('select_elevator', {
                        elevator_id: elevatorId
                    });
                    
                    if (response.success) {
                        this.selectedElevator = this.allElevators.find(e => e.id === elevatorId);
                        this.showMessage('تم اختيار المصعد بنجاح', 'success');
                        this.showDetailsSection();
                        this.renderTable(this.allElevators.filter(elevator => 
                            Object.keys(this.filters).every(key => 
                                !this.filters[key] || String(elevator[key]) === this.filters[key]
                            )
                        ));
                    } else {
                        this.showMessage('خطأ: ' + response.message, 'error');
                    }
                } catch (error) {
                    this.showMessage('حدث خطأ فني غير متوقع', 'error');
                } finally {
                    this.setLoading(button, false);
                }
            }
            
            showDetailsSection() {
                document.getElementById('detailsSection').style.display = 'block';
                document.getElementById('detailsSection').scrollIntoView({ behavior: 'smooth' });
                this.renderAdditions(); // إعادة رسم الإضافات بالفلتر الجديد
                this.updateCalculation();
            }
            
            renderAdditions() {
                const grid = document.getElementById('additionsGrid');
                const filteredAdditions = this.filterAdditionsByDoorType();
                
                if (filteredAdditions.length === 0) {
                    grid.innerHTML = '<p style="text-align: center; color: var(--medium-gray); grid-column: 1 / -1;">لا توجد إضافات متاحة لهذا النوع من المصاعد</p>';
                    return;
                }
                
                grid.innerHTML = filteredAdditions.map(addition => `
                    <div class="addition-card ${this.selectedAdditions.has(addition.id) ? 'selected' : ''}" 
                         data-addition-id="${addition.id}" onclick="pricingManager.toggleAddition(${addition.id})">
                        <div class="addition-name">${addition.name}</div>
                        <div class="addition-price">
                            <span class="price-value">${this.formatPrice(addition.price)}</span>
                            <img src="https://alfagolden.com/images/sar.svg" class="currency-icon" alt="ريال سعودي">
                        </div>
                        <div class="addition-effect">${addition.price_effect}</div>
                    </div>
                `).join('');
            }
            
            toggleAddition(additionId) {
                if (this.selectedAdditions.has(additionId)) {
                    this.selectedAdditions.delete(additionId);
                } else {
                    this.selectedAdditions.add(additionId);
                }
                
                const element = document.querySelector(`[data-addition-id="${additionId}"]`);
                if (element) {
                    element.classList.toggle('selected', this.selectedAdditions.has(additionId));
                }
                
                this.updateCalculation();
            }
            
            updateCalculation() {
                if (!this.selectedElevator) return;
                
                const elevatorsCount = Math.max(1, parseInt(document.getElementById('elevatorsCount').value) || 1);
                const discountAmount = parseFloat(document.getElementById('discountAmount').value) || 0;
                const increaseAmount = parseFloat(document.getElementById('increaseAmount').value) || 0;
                
                // حساب السعر الأساسي
                const basePrice = this.selectedElevator.price * elevatorsCount;
                
                // حساب الإضافات
                let additionsTotal = 0;
                let additionsHtml = '';
                
                this.selectedAdditions.forEach(additionId => {
                    const addition = this.allAdditions.find(a => a.id === additionId);
                    if (!addition) return;
                    
                    let additionTotal = 0;
                    let calculationText = '';
                    
                    if (addition.price_effect === 'لكل دور') {
                        additionTotal = addition.price * this.selectedElevator.stops * elevatorsCount;
                        calculationText = `${this.formatPrice(addition.price)} × ${this.selectedElevator.stops} × ${elevatorsCount}`;
                    } else {
                        additionTotal = addition.price;
                        calculationText = `${this.formatPrice(addition.price)} (مرة واحدة)`;
                    }
                    
                    additionsTotal += additionTotal;
                    additionsHtml += `
                        <div class="calc-row">
                            <div class="calc-label">${addition.name} (${calculationText})</div>
                            <div class="calc-value">
                                ${this.formatPrice(additionTotal)}
                                <img src="https://alfagolden.com/images/sar.svg" class="currency-icon" alt="ريال">
                            </div>
                        </div>
                    `;
                });
                
                const subtotal = basePrice + additionsTotal;
                // الحد الأقصى للتخفيض = 5% من المجموع + مبلغ الزيادة
                const maxDiscount = (subtotal * 0.05) + increaseAmount;
                const finalTotal = subtotal - discountAmount + increaseAmount;
                
                // تحديث معلومات التخفيض
                document.getElementById('discountInfo').textContent = `الحد الأقصى: ${this.formatPrice(maxDiscount)} ريال`;
                
                // عرض الحساب
                let calculationHtml = `
                    <div class="calc-row">
                        <div class="calc-label">السعر الأساسي (${this.formatPrice(this.selectedElevator.price)} × ${elevatorsCount})</div>
                        <div class="calc-value">
                            ${this.formatPrice(basePrice)}
                            <img src="https://alfagolden.com/images/sar.svg" class="currency-icon" alt="ريال">
                        </div>
                    </div>
                `;
                
                if (additionsHtml) {
                    calculationHtml += additionsHtml;
                    calculationHtml += `
                        <div class="calc-row">
                            <div class="calc-label">مجموع الإضافات</div>
                            <div class="calc-value">
                                ${this.formatPrice(additionsTotal)}
                                <img src="https://alfagolden.com/images/sar.svg" class="currency-icon" alt="ريال">
                            </div>
                        </div>
                    `;
                }
                
                if (discountAmount > 0) {
                    calculationHtml += `
                        <div class="calc-row">
                            <div class="calc-label">التخفيض</div>
                            <div class="calc-value" style="color: var(--discount-color);">
                                -${this.formatPrice(discountAmount)}
                                <img src="https://alfagolden.com/images/sar.svg" class="currency-icon" alt="ريال">
                            </div>
                        </div>
                    `;
                }

                if (increaseAmount > 0) {
                    calculationHtml += `
                        <div class="calc-row">
                            <div class="calc-label">الزيادة</div>
                            <div class="calc-value" style="color: var(--increase-color);">
                                +${this.formatPrice(increaseAmount)}
                                <img src="https://alfagolden.com/images/sar.svg" class="currency-icon" alt="ريال">
                            </div>
                        </div>
                    `;
                }
                
                calculationHtml += `
                    <div class="calc-row">
                        <div class="calc-label">المجموع النهائي</div>
                        <div class="calc-value">
                            ${this.formatPrice(finalTotal)}
                            <img src="https://alfagolden.com/images/sar.svg" class="currency-icon" alt="ريال">
                        </div>
                    </div>
                `;
                
                document.getElementById('calculationContent').innerHTML = calculationHtml;
            }
            
            validateDiscount() {
                if (!this.selectedElevator) return;
                
                const elevatorsCount = Math.max(1, parseInt(document.getElementById('elevatorsCount').value) || 1);
                const increaseAmount = parseFloat(document.getElementById('increaseAmount').value) || 0;
                const basePrice = this.selectedElevator.price * elevatorsCount;
                
                let additionsTotal = 0;
                this.selectedAdditions.forEach(additionId => {
                    const addition = this.allAdditions.find(a => a.id === additionId);
                    if (!addition) return;
                    
                    if (addition.price_effect === 'لكل دور') {
                        additionsTotal += addition.price * this.selectedElevator.stops * elevatorsCount;
                    } else {
                        additionsTotal += addition.price;
                    }
                });
                
                const subtotal = basePrice + additionsTotal;
                // الحد الأقصى للتخفيض = 5% من المجموع + مبلغ الزيادة
                const maxDiscount = (subtotal * 0.05) + increaseAmount;
                const discountInput = document.getElementById('discountAmount');
                
                if (parseFloat(discountInput.value) > maxDiscount) {
                    discountInput.value = Math.floor(maxDiscount);
                }
            }
            
            async calculateFinalPrice() {
                if (!this.selectedElevator) {
                    this.showDetailsMessage('يرجى اختيار المصعد أولاً', 'error');
                    return;
                }
                
                const button = document.getElementById('calculateBtn');
                this.setLoading(button, true);
                
                try {
                    const elevatorsCount = parseInt(document.getElementById('elevatorsCount').value) || 1;
                    const discountAmount = parseFloat(document.getElementById('discountAmount').value) || 0;
                    const increaseAmount = parseFloat(document.getElementById('increaseAmount').value) || 0;
                    
                    const data = {
                        elevators_count: elevatorsCount,
                        selected_additions: JSON.stringify(Array.from(this.selectedAdditions)),
                        discount_amount: discountAmount,
                        increase_amount: increaseAmount
                    };
                    
                    console.log('Sending data:', data);
                    
                    const response = await this.makeRequest('calculate_final_price', data);
                    
                    console.log('Response:', response);
                    
                    if (response.success) {
                        this.showDetailsMessage('تم حفظ جميع البيانات بنجاح', 'success');
                        setTimeout(() => {
                            if (response.redirect) {
                                window.location.href = response.redirect;
                            }
                        }, 1500);
                    } else {
                        const errorMsg = response.message || 'حدث خطأ غير محدد';
                        this.showDetailsMessage('خطأ: ' + errorMsg, 'error');
                        
                        if (response.details) {
                            console.error('Error details:', response.details);
                        }
                    }
                } catch (error) {
                    console.error('Calculation error:', error);
                    this.showDetailsMessage('حدث خطأ في الاتصال بالخادم', 'error');
                } finally {
                    this.setLoading(button, false);
                }
            }
            
            formatPrice(price) {
                if (price === undefined || price === null) return '0';
                return new Intl.NumberFormat('en-US', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0
                }).format(price);
            }
            
            async makeRequest(action, data) {
                console.log(`Making request: ${action}`, data);
                
                const formData = new FormData();
                formData.append('ajax', '1');
                formData.append('action', action);
                Object.keys(data).forEach(key => formData.append(key, data[key]));
                
                try {
                    const response = await fetch(window.location.href, { method: 'POST', body: formData });
                    const result = await response.json();
                    
                    console.log(`Response for ${action}:`, result);
                    return result;
                } catch (error) {
                    console.error(`Error in ${action}:`, error);
                    throw error;
                }
            }
            
            showMessage(message, type) {
                const container = document.getElementById('messageContainer');
                const iconClass = type === 'success' ? 'fa-check-circle' : 'fa-times-circle';
                container.className = `message ${type} show`;
                container.innerHTML = `<i class="fas ${iconClass}"></i> ${message}`;
                setTimeout(() => container.classList.remove('show'), 4000);
            }
            
            showDetailsMessage(message, type) {
                const container = document.getElementById('detailsMessage');
                const iconClass = type === 'success' ? 'fa-check-circle' : 'fa-times-circle';
                container.className = `message ${type} show`;
                container.innerHTML = `<i class="fas ${iconClass}"></i> ${message}`;
                setTimeout(() => container.classList.remove('show'), 4000);
            }
            
            setLoading(button, isLoading) {
                this.isLoading = isLoading;
                const text = button.querySelector('.btn-text');
                const spinner = button.querySelector('.spinner');

                button.disabled = isLoading;
                text.classList.toggle('hidden', isLoading);
                spinner.classList.toggle('hidden', !isLoading);

                document.querySelectorAll('.btn-select').forEach(b => {
                    if (b !== button) b.disabled = isLoading;
                });
            }
        }
        
        // Global functions
        let pricingManager;
        
        function toggleDiscount() {
            const section = document.getElementById('discountSection');
            section.classList.toggle('show');
            if (section.classList.contains('show')) {
                document.getElementById('discountAmount').focus();
            }
        }

        function toggleIncrease() {
            const section = document.getElementById('increaseSection');
            section.classList.toggle('show');
            if (section.classList.contains('show')) {
                document.getElementById('increaseAmount').focus();
            }
        }
        
        function calculateFinalPrice() {
            pricingManager.calculateFinalPrice();
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            pricingManager = new ElevatorPricingManager();
        });
    </script>

</div>
</div>
<?php endif; ?>

<?php if ($step === '4'): ?>
<div class='unified-slot'>
<div class='card'>

    <div class="container">
        <header class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="page-title">
                    <i class="fa-solid fa-file-invoice text-gold"></i>
                    تفاصيل المصعد لعرض السعر #<?php echo htmlspecialchars($quote_ref_id); ?>
                </h1>
                <div class="d-flex align-items-center flex-wrap">
                    <strong class="me-2 d-none d-sm-inline">البراند:</strong>
                    <span class="brand-badge"><?php echo htmlspecialchars($quote_brand); ?></span>
                    <span class="door-type-badge"><?php echo htmlspecialchars($quote_door_type); ?></span>
                </div>
            </div>
        </header>
        
        <main>
            <form id="elevatorForm">
                <?php
                $sections = [
                    'texts' => [
                        'title' => 'النصوص والجمل',
                        'icon' => 'fa-file-lines',
                        'fields' => [
                            'opening_sentence' => ['play', 'جملة البداية'],
                            'introductory_sentence' => ['align-left', 'الجملة التمهيدية'],
                            'closing_sentence' => ['stop', 'الجملة الختامية']
                        ]
                    ],
                    'operation' => [
                        'title' => 'التشغيل والتحكم',
                        'icon' => 'fa-gears',
                        'fields' => [
                            'elevator_control_system' => ['microchip', 'جهاز تشغيل المصعد'],
                            'operation_method' => ['sliders', 'طريقة التشغيل'],
                            'stops_naming' => ['tag', 'مسميات الوقفات'],
                            'electrical_current' => ['bolt', 'التيار الكهربائي']
                        ]
                    ],
                    'components' => [
                        'title' => 'مكونات الصاعدة والمكينة',
                        'icon' => 'fa-cogs',
                        'fields' => [
                            'machine_type' => ['cogs', 'نوع المكينة'],
                            'rails_elevator' => ['grip-lines', 'سكك الصاعدة'],
                            'rails_counterweight' => ['grip-lines-vertical', 'سكك ثقل الموازنة'],
                            'traction_cables' => ['link', 'حبال الجر'],
                            'flexible_cable' => ['plug', 'الكابل المرن']
                        ]
                    ],
                    'car' => [
                        'title' => 'الصاعدة',
                        'icon' => 'fa-elevator',
                        'fields' => [
                            'elevator_frame' => ['border-all', 'الإطار الحامل للصاعدة'],
                            'car_finishing' => ['palette', 'التشطيب'],
                            'car_internal_dimensions' => ['arrows-alt-h', 'المقاسات الداخلية'],
                            'ceiling' => ['arrow-up-from-bracket', 'السقف'],
                            'emergency_lighting' => ['lightbulb', 'إضاءة الطوارئ'],
                            'car_movement_device' => ['hand-pointer', 'جهاز تحريك الصاعدة'],
                            'flooring' => ['border-bottom', 'الأرضية']
                        ]
                    ],
                    'doors' => [
                        'title' => 'الأبواب',
                        'icon' => 'fa-door-open',
                        'fields' => [
                            'door_operation_method' => ['arrows-h', 'طريقة تشغيل الأبواب'],
                            'internal_door' => ['door-closed', 'الباب الداخلي'],
                            'door_dimensions' => ['ruler-combined', 'مقاسات الأبواب']
                        ]
                    ],
                    'panels' => [
                        'title' => 'لوحات التحكم',
                        'icon' => 'fa-tablet-screen-button',
                        'fields' => [
                            'cop_panel' => ['tablet-alt', 'لوحة الطلب الداخلية COP'],
                            'lop_finishing' => ['ruler-combined', 'لوحة الطلب الخارجية - التشطيب'],
                            'lop_main_floor' => ['door-open', 'لوحة الطلب الخارجية - الوقفة الرئيسية'],
                            'lop_other_floors' => ['building', 'لوحة الطلب الخارجية - الوقفات الأخرى']
                        ]
                    ],
                    'safety' => [
                        'title' => 'أجهزة الأمان والطوارئ',
                        'icon' => 'fa-shield-halved',
                        'fields' => [
                            'safety_electrical_devices' => ['phone', 'أجهزة الاتصال'],
                            'emergency_devices' => ['triangle-exclamation', 'أجهزة الطوارئ'],
                            'lighting_devices' => ['sun', 'أجهزة الإنارة'],
                            'safety_devices' => ['lock', 'أجهزة الأمان'],
                            'light_curtain' => ['vector-square', 'ستارة ضوئية'],
                            'speed_governor' => ['gauge-high', 'جهاز منظم السرعة'],
                            'shock_absorbers' => ['arrows-down-to-line', 'مخففات الصدمات'],
                            'travel_end_device' => ['stop-circle', 'جهاز نهاية المشوار'],
                            'door_safety_cam' => ['key', 'كامة تأمين الباب'],
                            'car_guides' => ['oil-can', 'مزايت الصاعدة'],
                            'external_door_switch' => ['toggle-off', 'مفتاح الباب الخارجي'],
                            'electrical_connections' => ['plug-circle-bolt', 'التوصيلات الكهربائية']
                        ]
                    ],
                    'services' => [
                        'title' => 'الخدمات والأعمال',
                        'icon' => 'fa-briefcase',
                        'fields' => [
                            'preparatory_works' => ['clipboard-list', 'الأعمال التحضيرية'],
                            'warranty_maintenance' => ['user-shield', 'الضمان والصيانة'],
                            'supply_installation' => ['truck-fast', 'التوريد والتركيب']
                        ]
                    ]
                ];
                
                foreach ($sections as $key => $section) {
                    echo "<div class='section-card'>";
                    echo "<div class='section-header'>";
                    echo "<i class='fa-solid {$section['icon']} fa-fw'></i>";
                    echo "<h2 class='section-title'>{$section['title']}</h2>";
                    echo "</div>";
                    echo "<div class='section-body'>";
                    echo "<div id='alert-container-{$key}' class='alert-container'></div>";
                    echo "<div class='form-grid'>";
                    
                    foreach ($section['fields'] as $name => [$field_icon, $label]) {
                        $is_long_text = in_array($name, $long_text_fields);
                        $class = $is_long_text ? 'form-group full-width' : 'form-group';
                        
                        $baserow_name = $form_to_baserow_map[$name] ?? $name; 
                        $current_val_raw = $current_quote[$baserow_name] ?? null; 
                        $current_val_display = '';
                        
                        if (is_array($current_val_raw) && !empty($current_val_raw)) { 
                            $first_item = $current_val_raw[0]; 
                            if (isset($first_item['value'])) 
                                $current_val_display = is_array($first_item['value']) ? ($first_item['value']['value'] ?? '') : $first_item['value']; 
                        } 
                        elseif (is_string($current_val_raw)) 
                            $current_val_display = $current_val_raw;
                        
                        echo "<div class='{$class}'>";
                        echo "<label for='{$name}' class='form-label'>";
                        echo "<i class='fa-solid fa-{$field_icon} icon-gold fa-fw'></i>";
                        echo "{$label}";
                        echo "</label>";
                        echo "<div class='input-group'>";
                        echo "<button class='btn options-manage-btn' type='button' onclick=\"openModal('{$name}', 'إدارة {$label}')\">";
                        echo "<i class='fa-solid fa-cog'></i>";
                        echo "</button>";
                        echo "<select id='{$name}' name='{$name}' class='form-select' data-saved-value='".htmlspecialchars($current_val_display, ENT_QUOTES)."'></select>";
                        echo "</div>";
                        echo "</div>";
                    }
                    
                    echo "</div>"; // form-grid
                    echo "</div>"; // section-body
                    echo "</div>"; // section-card
                }
                ?>
                
                <div class="form-footer">
                    <button type="submit" id="submitBtn" class="btn btn-primary">
                        <span class="btn-text">
                            <i class="fa-solid fa-save"></i>حفظ ومتابعة
                        </span>
                        <span class="spinner d-none">
                            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        </span>
                    </button>
                </div>
            </form>
        </main>
    </div>

    <div class="modal fade" id="optionsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="modal-alert-container"></div>
                    <ul id="optionsList" class="list-group list-group-flush mb-3"></ul>
                    <div class="add-form border-top pt-3">
                        <label class="form-label">إضافة خيار جديد</label>
                        <textarea id="addTextarea" class="form-control mb-2" rows="3"></textarea>
                        <button class="btn btn-sm btn-primary" onclick="addOption()">
                            <i class="fa-solid fa-plus"></i>إضافة الخيار
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="brandModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="brandModalLabel">تعيين كافتراضي</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="filter-selection">
                        <div class="filter-group">
                            <div class="filter-label">
                                <i class="fa-solid fa-tag"></i>
                                اختر البراند
                            </div>
                            <div class="d-grid gap-2">
                                <button class="btn btn-primary btn-sm" onclick="setBrandFilter('ALFA PRO')">ALFA PRO</button>
                                <button class="btn btn-secondary btn-sm" onclick="setBrandFilter('ALFA ELITE')">ALFA ELITE</button>
                                <button class="btn btn-success btn-sm" onclick="setBrandFilter('الكل')">الكل (جميع البراندات)</button>
                            </div>
                        </div>
                        
                        <div class="filter-group">
                            <div class="filter-label">
                                <i class="fa-solid fa-door-open"></i>
                                اختر نوع الباب
                            </div>
                            <div class="d-grid gap-2">
                                <button class="btn btn-primary btn-sm" onclick="setDoorTypeFilter('أوتوماتيك')">أوتوماتيك</button>
                                <button class="btn btn-secondary btn-sm" onclick="setDoorTypeFilter('نصف أوتوماتيك')">نصف أوتوماتيك</button>
                                <button class="btn btn-success btn-sm" onclick="setDoorTypeFilter('الكل')">الكل (جميع الأنواع)</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mt-3">
                        <div class="mb-2">
                            <strong>الاختيار الحالي:</strong>
                        </div>
                        <div>
                            <span class="badge bg-primary me-2" id="selectedBrand">لم يتم الاختيار</span>
                            <span class="badge bg-secondary" id="selectedDoorType">لم يتم الاختيار</span>
                        </div>
                        <button class="btn btn-success mt-3" onclick="confirmSelection()" id="confirmBtn" disabled>
                            <i class="fa-solid fa-check"></i>تأكيد الاختيار
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.full.min.js"></script>
    
    <script>
    const quoteBrand = '<?php echo $quote_brand; ?>';
    const quoteDoorType = '<?php echo $quote_door_type; ?>';
    const quoteId = '<?php echo $quote_id; ?>';
    const allFormFields = <?php echo json_encode(array_keys($form_to_baserow_map)); ?>;
    const longFields = <?php echo json_encode($long_text_fields); ?>;
    
    let currentModalField = null, currentOptions = [], currentDefaults = {}, editingIndex = -1, selectedDefaultIndex = -1;
    let optionsModal, brandModal;
    
    // متغيرات للفلترة الجديدة
    let selectedBrand = null, selectedDoorType = null;

    // تنسيق الأرقام بدون فواصل وإنجليزية
    function formatNumber(num) {
        return num.toString();
    }

    // تنسيق التواريخ YYYY/MM/DD
    function formatDate(date) {
        const d = new Date(date);
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${year}/${month}/${day}`;
    }

    document.addEventListener('DOMContentLoaded', function() {
        optionsModal = new bootstrap.Modal(document.getElementById('optionsModal'));
        brandModal = new bootstrap.Modal(document.getElementById('brandModal'));
        initializeAllFields();
        document.getElementById('elevatorForm').addEventListener('submit', saveData);
    });

    async function initializeAllFields() {
        allFormFields.forEach(field => {
            const selectElement = document.getElementById(field);
            if (selectElement) {
                $(selectElement).select2({ 
                    theme: 'bootstrap-5', 
                    placeholder: 'جاري التحميل...', 
                    language: { noResults: () => "لا توجد نتائج" }
                });
            }
        });
        await Promise.all(allFormFields.map(field => loadOptions(field)));
    }

    async function loadOptions(field) {
        try {
            console.log(`تحميل خيارات الحقل: ${field}`);
            console.log(`البراند: ${quoteBrand}, نوع الباب: ${quoteDoorType}`);
            
            const response = await makeRequest('get_options', { 
                field_type: field, 
                quote_brand: quoteBrand,
                quote_door_type: quoteDoorType
            });
            
            if (response.success) {
                console.log(`تم تحميل الخيارات للحقل ${field}:`, {
                    options: response.options,
                    defaults: response.defaults,
                    current_default: response.current_default
                });
                
                populateSelect(field, response.options, response.current_default);
            } else {
                console.error(`خطأ تحميل خيارات ${field}:`, response.message);
                showGlobalAlert(`خطأ تحميل خيارات ${field}: ${response.message}`, 'danger', field);
            }
        } catch (error) { 
            console.error(`Network error loading options for ${field}:`, error); 
            showGlobalAlert(`خطأ شبكة في تحميل خيارات ${field}`, 'danger', field);
        }
    }

    function populateSelect(field, options, defaultValue) {
        const select = $(`#${field}`); select.empty();
        if (options.length === 0) {
            select.append(new Option('لا توجد خيارات', '', true, true)).prop('disabled', true);
        } else {
            select.prop('disabled', false);
            options.forEach(option => select.append(new Option(option, option)));
            const savedValue = select.data('saved-value');
            if (savedValue && options.includes(savedValue)) select.val(savedValue);
            else if (defaultValue && options.includes(defaultValue)) select.val(defaultValue);
        }
        select.trigger('change');
    }

    async function openModal(field, title) {
        currentModalField = field; editingIndex = -1;
        document.getElementById('modalTitle').textContent = title;
        document.getElementById('optionsList').innerHTML = '<li class="list-group-item text-center"><span class="spinner"></span></li>';
        document.getElementById('addTextarea').rows = longFields.includes(field) ? 4 : 2;
        optionsModal.show();
        try {
            const response = await makeRequest('get_options', { 
                field_type: field, 
                quote_brand: quoteBrand,
                quote_door_type: quoteDoorType
            });
            if (response.success) { 
                currentOptions = response.options || []; 
                currentDefaults = response.defaults || {}; 
                renderOptionsList(); 
            }
            else showModalAlert('خطأ تحميل الخيارات: ' + response.message, 'danger');
        } catch (error) { showModalAlert('خطأ في الاتصال.', 'danger'); }
    }

    function renderOptionsList() {
        const list = document.getElementById('optionsList'); list.innerHTML = '';
        if (currentOptions.length === 0) { 
            list.innerHTML = '<li class="list-group-item text-center text-muted">لا توجد خيارات محفوظة.</li>'; 
            return; 
        }
        currentOptions.forEach((option, index) => {
            const li = document.createElement('li');
            li.className = 'list-group-item d-flex justify-content-between align-items-center flex-wrap gap-2';
            if (editingIndex === index) {
                li.innerHTML = `<div class="w-100"><textarea class="form-control mb-2" id="editInput${index}" rows="3">${option}</textarea></div><div class="ms-auto"><button class="btn btn-sm btn-success" onclick="saveEdit(${index})"><i class="fa-solid fa-check"></i> حفظ</button><button class="btn btn-sm btn-secondary ms-1" onclick="cancelEdit()"><i class="fa-solid fa-times"></i> إلغاء</button></div>`;
            } else {
                let badges = '';
                
                // عرض شارات للتركيبات المختلفة
                Object.keys(currentDefaults).forEach(key => {
                    if (currentDefaults[key] === option) {
                        if (key.includes('_')) {
                            // تركيبة من براند ونوع باب
                            const [brand, doorType] = key.split('_');
                            badges += `<span class="badge bg-info me-1">${brand} + ${doorType}</span>`;
                        } else if (key === 'ALFA PRO') {
                            badges += '<span class="badge bg-primary me-1">PRO</span>';
                        } else if (key === 'ALFA ELITE') {
                            badges += '<span class="badge bg-secondary me-1">ELITE</span>';
                        } else if (key === 'أوتوماتيك') {
                            badges += '<span class="badge bg-info me-1">أوتوماتيك</span>';
                        } else if (key === 'نصف أوتوماتيك') {
                            badges += '<span class="badge bg-warning text-dark me-1">نصف أوتوماتيك</span>';
                        } else if (key === 'الكل') {
                            badges += '<span class="badge bg-success me-1">الكل</span>';
                        } else if (key === 'general') {
                            badges += '<span class="badge bg-warning text-dark me-1">افتراضي عام</span>';
                        }
                    }
                });
                
                let escapedOption = option.replace(/</g, "&lt;").replace(/>/g, "&gt;");
                li.innerHTML = `<div class="option-item-text me-3 flex-grow-1">${escapedOption}</div><div class="ms-auto flex-shrink-0"><div class="btn-group">${badges}<button class="btn btn-sm btn-outline-secondary" onclick="editOption(${index})" title="تعديل"><i class="fa-solid fa-edit"></i></button><button class="btn btn-sm btn-outline-secondary" onclick="setDefault(${index})" title="تعيين افتراضي"><i class="fa-solid fa-star"></i></button><button class="btn btn-sm btn-outline-danger" onclick="deleteOption(${index})" title="حذف"><i class="fa-solid fa-trash"></i></button></div></div>`;
            }
            list.appendChild(li);
        });
    }

    async function addOption() {
        const textarea = document.getElementById('addTextarea'); 
        const newOption = textarea.value.trim();
        if (!newOption) return; 
        if (currentOptions.includes(newOption)) { 
            showModalAlert('هذا الخيار موجود بالفعل.', 'warning'); 
            return; 
        }
        currentOptions.push(newOption); 
        if (currentOptions.length === 1) currentDefaults['general'] = newOption;
        await saveOptions(); 
        textarea.value = '';
    }

    function editOption(index) {
        editingIndex = index; 
        renderOptionsList();
        setTimeout(() => {
            const editInput = document.getElementById(`editInput${index}`);
            if (editInput) { editInput.focus(); editInput.select(); }
        }, 100);
    }
    
    async function saveEdit(index) {
        const newValue = document.getElementById(`editInput${index}`).value.trim(); 
        if (!newValue) return;
        if (newValue !== currentOptions[index] && currentOptions.includes(newValue)) { 
            showModalAlert('هذا الخيار موجود بالفعل.', 'warning'); 
            return; 
        }
        const oldValue = currentOptions[index]; 
        currentOptions[index] = newValue;
        Object.keys(currentDefaults).forEach(key => { 
            if (currentDefaults[key] === oldValue) currentDefaults[key] = newValue; 
        });
        editingIndex = -1; 
        await saveOptions();
    }

    function cancelEdit() { 
        editingIndex = -1; 
        renderOptionsList(); 
    }
    
    function setDefault(index) { 
        selectedDefaultIndex = index; 
        selectedBrand = null;
        selectedDoorType = null;
        updateSelectionDisplay();
        brandModal.show(); 
    }

    function setBrandFilter(brand) {
        selectedBrand = brand;
        updateSelectionDisplay();
    }

    function setDoorTypeFilter(doorType) {
        selectedDoorType = doorType;
        updateSelectionDisplay();
    }

    function updateSelectionDisplay() {
        document.getElementById('selectedBrand').textContent = selectedBrand || 'لم يتم الاختيار';
        document.getElementById('selectedDoorType').textContent = selectedDoorType || 'لم يتم الاختيار';
        
        const confirmBtn = document.getElementById('confirmBtn');
        confirmBtn.disabled = !selectedBrand || !selectedDoorType;
    }

    async function confirmSelection() {
        if (selectedDefaultIndex !== -1 && selectedBrand && selectedDoorType) {
            let key;
            
            if (selectedBrand === 'الكل' && selectedDoorType === 'الكل') {
                key = 'الكل';
            } else if (selectedBrand === 'الكل') {
                key = selectedDoorType;
            } else if (selectedDoorType === 'الكل') {
                key = selectedBrand;
            } else {
                key = selectedBrand + '_' + selectedDoorType;
            }
            
            currentDefaults[key] = currentOptions[selectedDefaultIndex]; 
            brandModal.hide(); 
            await saveOptions();
        }
    }

    async function deleteOption(index) {
        if (!confirm('هل أنت متأكد من حذف هذا الخيار؟')) return;
        const deletedOption = currentOptions.splice(index, 1)[0];
        Object.keys(currentDefaults).forEach(key => { 
            if (currentDefaults[key] === deletedOption) delete currentDefaults[key]; 
        });
        await saveOptions();
    }

    async function saveOptions() {
        try {
            console.log('حفظ الخيارات:', {
                field: currentModalField,
                options: currentOptions,
                defaults: currentDefaults
            });
            
            const response = await makeRequest('update_options', { 
                field_type: currentModalField, 
                options: JSON.stringify(currentOptions), 
                defaults: JSON.stringify(currentDefaults) 
            });
            
            if (response.success) { 
                showModalAlert(response.message, 'success'); 
                renderOptionsList(); 
                
                // إعادة تحميل الخيارات للحقل الحالي
                await loadOptions(currentModalField); 
                
                console.log('تم حفظ الخيارات بنجاح');
            }
            else {
                console.error('خطأ في حفظ الخيارات:', response.message);
                showModalAlert('فشل الحفظ: ' + response.message, 'danger');
            }
        } catch (error) { 
            console.error('خطأ في الاتصال:', error);
            showModalAlert('خطأ في الاتصال بالخادم.', 'danger'); 
        }
    }
    
    function setLoading(isLoading) {
        const btn = document.getElementById('submitBtn');
        const text = btn.querySelector('.btn-text');
        const spinner = btn.querySelector('.spinner');
        if (isLoading) {
            btn.classList.add('loading'); btn.disabled = true;
            text.classList.add('d-none'); spinner.classList.remove('d-none');
        } else {
            btn.classList.remove('loading'); btn.disabled = false;
            text.classList.remove('d-none'); spinner.classList.add('d-none');
        }
    }

    async function saveData(event) {
        event.preventDefault(); 
        setLoading(true);
        const formData = new FormData(event.target); 
        const data = Object.fromEntries(formData.entries()); 
        data.quote_id = quoteId;
        try {
            const response = await makeRequest('update_quote', data);
            if (response.success) { 
                showGlobalAlert(response.message, 'success'); 
                setTimeout(() => window.location.href = response.redirect, 1500); 
            }
            else showGlobalAlert(response.message || 'فشل تحديث البيانات.', 'danger');
        } catch(error) { 
            showGlobalAlert('حدث خطأ في الشبكة.', 'danger'); 
        } finally { 
            setLoading(false); 
        }
    }

    async function makeRequest(action, data) {
        const formData = new FormData(); 
        formData.append('ajax', '1'); 
        formData.append('action', action);
        for (const key in data) formData.append(key, data[key]);
        const response = await fetch(window.location.href, { method: 'POST', body: formData });
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        return await response.json();
    }
    
    function showGlobalAlert(message, type = 'info', section = null) {
        let container;
        if (section) {
            // العثور على القسم المحدد
            const sectionKeys = ['texts', 'operation', 'components', 'car', 'doors', 'panels', 'safety', 'services'];
            const fieldSections = {
                'opening_sentence': 'texts', 'introductory_sentence': 'texts', 'closing_sentence': 'texts',
                'elevator_control_system': 'operation', 'operation_method': 'operation', 'stops_naming': 'operation', 'electrical_current': 'operation',
                'machine_type': 'components', 'rails_elevator': 'components', 'rails_counterweight': 'components', 'traction_cables': 'components', 'flexible_cable': 'components',
                'elevator_frame': 'car', 'car_finishing': 'car', 'car_internal_dimensions': 'car', 'ceiling': 'car', 'emergency_lighting': 'car', 'car_movement_device': 'car', 'flooring': 'car',
                'door_operation_method': 'doors', 'internal_door': 'doors', 'door_dimensions': 'doors',
                'cop_panel': 'panels', 'lop_finishing': 'panels', 'lop_main_floor': 'panels', 'lop_other_floors': 'panels'
            };
            const sectionKey = fieldSections[section] || 'texts';
            container = document.getElementById(`alert-container-${sectionKey}`);
        } else {
            container = document.querySelector('.alert-container');
        }
        
        if (!container) return;
        
        container.innerHTML = `<div class="message alert alert-${type} alert-dismissible fade show" role="alert">${message}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>`;
        container.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function showModalAlert(message, type = 'info') {
        const container = document.getElementById('modal-alert-container');
        container.innerHTML = `<div class="message alert alert-${type} alert-dismissible fade show" role="alert">${message}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>`;
    }
    </script>

</div>
</div>
<?php endif; ?>

</div>
<footer>© الواجهة الموحدة — تم تجميع الصفحات الأربع في ملف واحد</footer>
</body>
</html>
