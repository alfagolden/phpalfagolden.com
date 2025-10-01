<?php
session_start();

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

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تفاصيل المصعد - عرض سعر رقم <?php echo htmlspecialchars($quote_ref_id); ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">

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
</head>
<body>
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
</body>
</html>