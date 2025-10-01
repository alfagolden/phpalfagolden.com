<?php
session_start();

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
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اختيار المشروع</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&family=Tajawal:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
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
    </style>
</head>
<body>
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
</body>
</html>