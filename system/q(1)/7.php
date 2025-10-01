<?php
session_start();

/*========================================
  إعدادات الاتصال + تعريف الجداول
========================================*/
$baserow_url = 'https://base.alfagolden.com';
$api_token   = 'h5qAt85gtiJDAzpH51WrXPywhmnhrPWy';

$users_table_id     = 702; // اليوزرات
$quotes_table_id    = 704; // عروض الأسعار
$settings_table_id  = 705; // الإعدادات
$settings_record_id = 1;   // سجل الإعدادات الرئيسي

/*========================================
  تعريف الحقول (IDs) - حسب طلبك
========================================*/
/** جدول عروض الأسعار (704) **/
$quotes_fields = [
  'customer_link'        => 6786, // العميل (Link to users)
  'first_party'          => 7063, // الطرف الأول
  'iban'                 => 7066, // الآيبان
  'contract_opening'     => 7041, // جملة البداية للعقد

  // المواد المطلوبة فقط
  'article_1'            => 7036, // المادة 1
  'article_2'            => 7039, // المادة 2
  'article_3'            => 7043, // المادة 3
  'article_5'            => 7045, // المادة 5
  'article_8'            => 7048, // المادة 8
  'article_9'            => 7049, // المادة 9
  'article_10'           => 7050, // المادة 10
  
  // حقول للحصول على البراند ونوع الباب
  'brand'                => 6973, // البراند
  'door_type'            => 7070, // نوع الأبواب
];

/** جدول الإعدادات (705) **/
$settings_fields = [
  'first_party'          => 7062, // الطرف الاول
  'iban'                 => 7065, // الايبان
  'contract_opening'     => 7064, // جملة البداية للعقد

  // المواد
  'article_1'            => 7052,
  'article_2'            => 7053,
  'article_3'            => 7054,
  'article_5'            => 7056,
  'article_8'            => 7059,
  'article_9'            => 7060,
  'article_10'           => 7061,
];

/** جدول اليوزرات (702) **/
$users_fields = [
  'phone'                => 6773, // رقم الجوال
  'token'                => 6774,
  'otp'                  => 6775,
  'name'                 => 6912, // الاسم
  'gender'               => 6913, // الجنس
  'identity'             => 7028, // الهوية/السجل التجاري
  'address'              => 7031, // العنوان
];

/*========================================
  Session & Params
========================================*/
$user_id  = $_SESSION['user_id'] ?? null;
$quote_id = isset($_GET['quote_id']) ? (int)$_GET['quote_id'] : null;

if (!$user_id)  { header('Location: ../login.php'); exit; }
if (!$quote_id) { die('خطأ: معرف عرض السعر مفقود.'); }

/*========================================
  Helpers
========================================*/
function makeBaserowRequest($url, $method='GET', $data=null) {
  global $api_token;
  $ch = curl_init();
  curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTPHEADER => [
      'Authorization: Token '.$api_token,
      'Content-Type: application/json'
    ],
  ]);
  if ($method === 'POST') {
    curl_setopt($ch, CURLOPT_POST, true);
    if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
  } elseif ($method === 'PATCH') {
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
    if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
  }
  $response  = curl_exec($ch);
  $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $error     = curl_error($ch);
  curl_close($ch);
  if ($http_code >= 200 && $http_code < 300) {
    return json_decode($response, true);
  }
  return ['error'=>true,'message'=>"HTTP $http_code - $error",'response_body'=>$response];
}

/** تحميل صف عرض السعر (بدون user_field_names لتسهيل الوصول بـ field_ID) */
function getQuote($id){
  global $baserow_url, $quotes_table_id;
  return makeBaserowRequest("$baserow_url/api/database/rows/table/$quotes_table_id/$id/");
}

/** تحميل صف عميل */
function getUserRow($id){
  global $baserow_url, $users_table_id;
  return makeBaserowRequest("$baserow_url/api/database/rows/table/$users_table_id/$id/");
}

/** جلب سجل الإعدادات */
function getSettingsRow(){
  global $baserow_url, $settings_table_id, $settings_record_id;
  return makeBaserowRequest("$baserow_url/api/database/rows/table/$settings_table_id/$settings_record_id/");
}

// إضافة دالة لاستخراج البراند
function extractBrandValue($brand_field_data) {
    $valid_brands = ['ALFA PRO', 'ALFA ELITE'];
    $default_brand = 'ALFA PRO';
    
    if (empty($brand_field_data)) return $default_brand;
    
    $data_to_check = is_array($brand_field_data) ? $brand_field_data : [$brand_field_data];
    
    foreach ($data_to_check as $item) {
        if (is_array($item) && isset($item['value'])) {
            $value = $item['value'];
            if (is_array($value) && isset($value['value'])) {
                $brand = strtoupper(trim($value['value']));
                if (in_array($brand, $valid_brands)) {
                    return $brand;
                }
            } elseif (is_string($value)) {
                $brand = strtoupper(trim($value));
                if (in_array($brand, $valid_brands)) {
                    return $brand;
                }
            }
        }
    }
    
    return $default_brand;
}

// إضافة دالة لاستخراج نوع الباب
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

/** JSON options helpers (تحسين لدعم المعيارين) */
function processOptionsJSON($json_string) {
  if (empty($json_string)) return ['options'=>[], 'defaults'=>[]];
  
  $data = json_decode($json_string, true);
  if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
      return ['options'=>[], 'defaults'=>[]];
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

// دالة لاختيار القيمة الافتراضية بناءً على البراند ونوع الباب
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

/*========================================
  AJAX
========================================*/
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['ajax'])) {
  header('Content-Type: application/json; charset=utf-8');

  try {
    $action = $_POST['action'] ?? '';

    // جلب خيارات حقل من الإعدادات
    if ($action==='get_options') {
      $key = $_POST['key'] ?? '';
      $quote_brand = trim($_POST['quote_brand'] ?? 'ALFA PRO');
      $quote_door_type = trim($_POST['quote_door_type'] ?? 'أوتوماتيك');
      
      global $settings_fields;
      if (!$key || !isset($settings_fields[$key])) {
        echo json_encode(['success'=>false,'message'=>'مفتاح غير صالح']); exit;
      }
      
      $settings = getSettingsRow();
      if (isset($settings['error'])) {
        echo json_encode(['success'=>false,'message'=>'تعذر جلب الإعدادات']); exit;
      }
      
      $fid = $settings_fields[$key];
      $json = $settings['field_'.$fid] ?? '';
      
      error_log("جلب الخيارات - الحقل: $key");
      error_log("البراند: $quote_brand, نوع الباب: $quote_door_type");
      error_log("البيانات المسترجعة: " . $json);
      
      $parsed = processOptionsJSON($json);
      $default_value = selectDefaultValue($parsed['defaults'], $quote_brand, $quote_door_type) ?? ($parsed['options'][0] ?? null);
      
      error_log("القيمة الافتراضية المختارة: " . ($default_value ?? 'لا توجد'));
      
      echo json_encode([
          'success' => true,
          'options' => $parsed['options'],
          'defaults' => $parsed['defaults'],
          'current_default' => $default_value
      ]); 
      exit;
    }

    // تحديث خيارات حقل في الإعدادات
    if ($action==='update_options') {
      $key = $_POST['key'] ?? '';
      $options = json_decode($_POST['options'] ?? '[]', true);
      $defaults = json_decode($_POST['defaults'] ?? '{}', true);
      
      global $settings_fields, $baserow_url, $settings_table_id, $settings_record_id;
      if (!$key || !isset($settings_fields[$key]) || !is_array($options) || !is_array($defaults)) {
        echo json_encode(['success'=>false,'message'=>'بيانات غير صالحة']); exit;
      }
      
      $fid = $settings_fields[$key];
      $json_value = buildOptionsJSON($options, $defaults);
      
      // تسجيل البيانات للمراجعة
      error_log("تحديث الخيارات - الحقل: $key");
      error_log("البيانات المرسلة: " . json_encode(['options' => $options, 'defaults' => $defaults]));
      error_log("JSON المحفوظ: " . $json_value);
      
      $payload = ['field_'.$fid => $json_value];
      $resp = makeBaserowRequest("$baserow_url/api/database/rows/table/$settings_table_id/$settings_record_id/", 'PATCH', $payload);
      
      if (isset($resp['error'])) {
        error_log("خطأ في حفظ البيانات: " . $resp['message']);
        echo json_encode(['success'=>false,'message'=>'فشل حفظ الإعدادات']); exit;
      }
      
      error_log("تم حفظ البيانات بنجاح");
      echo json_encode(['success'=>true,'message'=>'تم تحديث الخيارات بنجاح']); exit;
    }

    // حفظ واحد لكل شيء
    if ($action==='save_all') {
      global $baserow_url, $quotes_table_id, $users_table_id, $quotes_fields, $users_fields;

      $qid = (int)($_POST['quote_id'] ?? 0);
      if (!$qid) { echo json_encode(['success'=>false,'message'=>'quote_id مفقود']); exit; }

      // 1) تحديث بيانات الطرف الثاني (العميل)
      $user_id_from_quote = (int)($_POST['customer_id'] ?? 0);
      if (!$user_id_from_quote) { echo json_encode(['success'=>false,'message'=>'معرف العميل مفقود']); exit; }

      $gender    = trim($_POST['gender'] ?? '');
      $identity  = trim($_POST['identity'] ?? '');
      $address   = trim($_POST['address'] ?? '');
      $cust_name = trim($_POST['cust_name'] ?? '');
      $cust_phone_raw = preg_replace('/[^0-9]/','', $_POST['cust_phone'] ?? '');
      if (substr($cust_phone_raw,0,1)==='0') { $cust_phone_raw = substr($cust_phone_raw,1); }

      if (!$gender || !$identity || !$address) {
        echo json_encode(['success'=>false,'message'=>'يرجى تعبئة (الجنس) و(الهوية/السجل التجاري) و(العنوان)']); exit;
      }

      $payload_user = [
        'field_'.$users_fields['gender']   => $gender,
        'field_'.$users_fields['identity'] => $identity,
        'field_'.$users_fields['address']  => $address,
      ];
      if ($cust_name !== '') {
        $payload_user['field_'.$users_fields['name']] = $cust_name;
      }
      if ($cust_phone_raw !== '') {
        if (!preg_match('/^5[0-9]{8}$/', $cust_phone_raw)) {
          echo json_encode(['success'=>false,'message'=>'رقم الجوال غير صحيح. يجب أن يبدأ بـ 5 ويتكون من 9 أرقام']); exit;
        }
        $payload_user['field_'.$users_fields['phone']] = '+966'.$cust_phone_raw;
      }

      $resp_user = makeBaserowRequest("$baserow_url/api/database/rows/table/$users_table_id/$user_id_from_quote/", 'PATCH', $payload_user);
      if (isset($resp_user['error'])) {
        echo json_encode(['success'=>false,'message'=>'فشل تحديث بيانات الطرف الثاني']); exit;
      }

      // 2) تحديث عرض السعر (الطرف الأول + الآيبان + جملة البداية + المواد)
      $payload_quote = [
        'field_'.$quotes_fields['first_party']      => trim($_POST['first_party'] ?? ''),
        'field_'.$quotes_fields['iban']             => trim($_POST['iban'] ?? ''),
        'field_'.$quotes_fields['contract_opening'] => trim($_POST['contract_opening'] ?? ''),

        'field_'.$quotes_fields['article_1']        => trim($_POST['article_1'] ?? ''),
        'field_'.$quotes_fields['article_2']        => trim($_POST['article_2'] ?? ''),
        'field_'.$quotes_fields['article_3']        => trim($_POST['article_3'] ?? ''),
        'field_'.$quotes_fields['article_5']        => trim($_POST['article_5'] ?? ''),
        'field_'.$quotes_fields['article_8']        => trim($_POST['article_8'] ?? ''),
        'field_'.$quotes_fields['article_9']        => trim($_POST['article_9'] ?? ''),
        'field_'.$quotes_fields['article_10']       => trim($_POST['article_10'] ?? ''),
      ];
      $resp_quote = makeBaserowRequest("$baserow_url/api/database/rows/table/$quotes_table_id/$qid/", 'PATCH', $payload_quote);
      if (isset($resp_quote['error'])) {
        echo json_encode(['success'=>false,'message'=>'فشل حفظ بنود العقد']); exit;
      }

      echo json_encode(['success'=>true,'message'=>'تم الحفظ بنجاح']); exit;
    }

    echo json_encode(['success'=>false,'message'=>'عملية غير معروفة.']); exit;

  } catch (Exception $e) {
    error_log('AJAX Error: '.$e->getMessage());
    echo json_encode(['success'=>false,'message'=>'خطأ غير متوقع.']); exit;
  }
}

/*========================================
  تحميل بيانات العرض + العميل المرتبط
========================================*/
$quote_row = getQuote($quote_id);
if (isset($quote_row['error'])) {
  die("<h1>خطأ في جلب بيانات العرض</h1><pre>".htmlspecialchars($quote_row['response_body'] ?? '')."</pre>");
}

$customer_link = $quote_row['field_'.$quotes_fields['customer_link']] ?? [];
$customer_id = 0;
if (is_array($customer_link) && !empty($customer_link)) {
  $first = $customer_link[0];
  if (is_array($first) && isset($first['id'])) {
    $customer_id = (int)$first['id'];
  } elseif (is_numeric($first)) {
    $customer_id = (int)$first;
  }
}
$customer_row = $customer_id ? getUserRow($customer_id) : null;

// استخراج البراند ونوع الباب
$quote_brand_data = $quote_row['field_'.$quotes_fields['brand']] ?? null;
$quote_brand = extractBrandValue($quote_brand_data);

$quote_door_type_data = $quote_row['field_'.$quotes_fields['door_type']] ?? null;
$quote_door_type = extractDoorTypeValue($quote_door_type_data);

$quote_ref_id = $quote_row['id'] ?? $quote_id;

// قيم حالية لعرضها
$current = [
  'first_party'      => $quote_row['field_'.$quotes_fields['first_party']]      ?? '',
  'iban'             => $quote_row['field_'.$quotes_fields['iban']]             ?? '',
  'contract_opening' => $quote_row['field_'.$quotes_fields['contract_opening']] ?? '',

  'article_1'  => $quote_row['field_'.$quotes_fields['article_1']]  ?? '',
  'article_2'  => $quote_row['field_'.$quotes_fields['article_2']]  ?? '',
  'article_3'  => $quote_row['field_'.$quotes_fields['article_3']]  ?? '',
  'article_5'  => $quote_row['field_'.$quotes_fields['article_5']]  ?? '',
  'article_8'  => $quote_row['field_'.$quotes_fields['article_8']]  ?? '',
  'article_9'  => $quote_row['field_'.$quotes_fields['article_9']]  ?? '',
  'article_10' => $quote_row['field_'.$quotes_fields['article_10']] ?? '',
];

$customer = [
  'id'       => $customer_id,
  'name'     => $customer_row['field_'.$users_fields['name']]     ?? '',
  'phone'    => $customer_row['field_'.$users_fields['phone']]    ?? '',
  'gender'   => $customer_row['field_'.$users_fields['gender']]   ?? '',
  'identity' => $customer_row['field_'.$users_fields['identity']] ?? '',
  'address'  => $customer_row['field_'.$users_fields['address']]  ?? '',
];

// تنسيق رقم الجوال للعرض
$phone_display = $customer['phone'];
if (is_string($phone_display) && strpos($phone_display, '+966') === 0) {
  $phone_display = '0'.substr($phone_display, 4);
}

?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>بنود العقد - عرض #<?php echo htmlspecialchars($quote_ref_id); ?></title>

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

        .required::after {
            content: ' *';
            color: var(--error);
            font-weight: bold;
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

        .subtext {
            font-size: 12px;
            color: var(--medium-gray);
            margin-top: 4px;
        }
</style>
</head>
<body>
<div class="container">

  <!-- رأس -->
  <header class="page-header">
    <div class="d-flex justify-content-between align-items-center">
      <h1 class="page-title">
        <i class="fa-solid fa-file-contract text-gold"></i>
        بنود العقد لعرض السعر #<?php echo htmlspecialchars($quote_ref_id); ?>
      </h1>
      <div class="d-flex align-items-center flex-wrap">
        <strong class="me-2 d-none d-sm-inline">البراند:</strong>
        <span class="brand-badge"><?php echo htmlspecialchars($quote_brand); ?></span>
        <span class="door-type-badge"><?php echo htmlspecialchars($quote_door_type); ?></span>
      </div>
    </div>
  </header>

  <form id="contractForm">
    <input type="hidden" name="quote_id" value="<?php echo (int)$quote_ref_id; ?>">
    <input type="hidden" id="customer_id" name="customer_id" value="<?php echo (int)$customer['id']; ?>">

    <!-- 1) تحديث بيانات الطرف الثاني -->
    <div class="section-card">
      <div class="section-header">
        <i class="fa-solid fa-user fa-fw"></i>
        <h2 class="section-title">تحديث بيانات الطرف الثاني</h2>
      </div>
      <div class="section-body">
        <div id="userAlert" class="alert-container"></div>
        <div class="form-grid">
          <div class="form-group full-width">
            <label class="form-label">
              <i class="fa-solid fa-signature icon-gold fa-fw"></i>
              الاسم
            </label>
            <input type="text" id="cust_name" name="cust_name" class="form-control" value="<?php echo htmlspecialchars($customer['name']); ?>" placeholder="اكتب اسم العميل">
          </div>
          
          <div class="form-group full-width">
            <label class="form-label">
              <i class="fa-solid fa-phone icon-gold fa-fw"></i>
              رقم الجوال
            </label>
            <input type="tel" id="cust_phone" name="cust_phone" class="form-control" value="<?php echo htmlspecialchars($phone_display); ?>" placeholder="5xxxxxxxx أو 05xxxxxxxx" maxlength="10">
            <div class="subtext">يُحفظ بصيغة دولية (+966) تلقائياً.</div>
          </div>

          <div class="form-group">
            <label class="form-label required">
              <i class="fa-solid fa-venus-mars icon-gold fa-fw"></i>
              الجنس
            </label>
            <select id="gender" name="gender" class="form-control">
              <option value="">اختر الجنس</option>
              <option value="ذكر" <?php echo ($customer['gender']==='ذكر'?'selected':''); ?>>ذكر</option>
              <option value="أنثى" <?php echo ($customer['gender']==='أنثى'?'selected':''); ?>>أنثى</option>
              <option value="مؤسسة" <?php echo ($customer['gender']==='مؤسسة'?'selected':''); ?>>مؤسسة</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label required">
              <i class="fa-solid fa-id-card icon-gold fa-fw"></i>
              الهوية / السجل التجاري
            </label>
            <input type="text" id="identity" name="identity" class="form-control" value="<?php echo htmlspecialchars($customer['identity']); ?>" placeholder="رقم الهوية أو رقم السجل التجاري">
          </div>

          <div class="form-group full-width">
            <label class="form-label required">
              <i class="fa-solid fa-location-dot icon-gold fa-fw"></i>
              العنوان
            </label>
            <input type="text" id="address" name="address" class="form-control" value="<?php echo htmlspecialchars($customer['address']); ?>" placeholder="مثال: الرياض - حي ... - شارع ...">
          </div>
        </div>
      </div>
    </div>

    <!-- 2) الطرف الأول + الآيبان + جملة البداية للعقد -->
    <div class="section-card">
      <div class="section-header">
        <i class="fa-solid fa-file-signature fa-fw"></i>
        <h2 class="section-title">بيانات العقد</h2>
      </div>
      <div class="section-body">
        <div id="contractAlert" class="alert-container"></div>
        <div class="form-grid">
          <!-- الطرف الأول -->
          <div class="form-group full-width">
            <label class="form-label">
              <i class="fa-solid fa-building icon-gold fa-fw"></i>
              الطرف الأول
            </label>
            <div class="input-group">
              <button class="options-manage-btn" type="button" onclick="openOptions('first_party','إدارة خيارات الطرف الأول')">
                <i class="fa-solid fa-cog"></i>
              </button>
              <select id="first_party" name="first_party" class="form-control"></select>
            </div>
            <div class="subtext">يُحفظ في عرض السعر (7063) ويُجلب من الإعدادات (7062).</div>
          </div>

          <!-- الآيبان -->
          <div class="form-group full-width">
            <label class="form-label">
              <i class="fa-solid fa-university icon-gold fa-fw"></i>
              الآيبان
            </label>
            <div class="input-group">
              <button class="options-manage-btn" type="button" onclick="openOptions('iban','إدارة خيارات الآيبان')">
                <i class="fa-solid fa-cog"></i>
              </button>
              <select id="iban" name="iban" class="form-control"></select>
            </div>
            <div class="subtext">يُحفظ في عرض السعر (7066) ويُجلب من الإعدادات (7065).</div>
          </div>

          <!-- جملة بداية العقد -->
          <div class="form-group full-width">
            <label class="form-label">
              <i class="fa-solid fa-file-lines icon-gold fa-fw"></i>
              جملة البداية للعقد
            </label>
            <div class="input-group">
              <button class="options-manage-btn" type="button" onclick="openOptions('contract_opening','إدارة جُمل البداية')">
                <i class="fa-solid fa-cog"></i>
              </button>
              <select id="contract_opening" name="contract_opening" class="form-control"></select>
            </div>
            <div class="subtext">يُحفظ في عرض السعر (7041) ويُجلب من الإعدادات (7064).</div>
          </div>
        </div>
      </div>
    </div>

    <!-- 3) المواد المطلوبة فقط -->
    <div class="section-card">
      <div class="section-header">
        <i class="fa-solid fa-list-ol fa-fw"></i>
        <h2 class="section-title">المواد</h2>
      </div>
      <div class="section-body">
        <div id="articlesAlert" class="alert-container"></div>
        <div class="form-grid">
          <?php
            $articles = [
              'article_1'  => ['clipboard-list', 'المادة- 1'],
              'article_2'  => ['clipboard-list', 'المادة- 2'],
              'article_3'  => ['clipboard-list', 'المادة- 3'],
              'article_5'  => ['clipboard-list', 'المادة- 5'],
              'article_8'  => ['clipboard-list', 'المادة- 8'],
              'article_9'  => ['clipboard-list', 'المادة- 9'],
              'article_10' => ['clipboard-list', 'المادة- 10'],
            ];
            foreach ($articles as $key => [$icon, $label]){
              $saved_val = $current[$key] ?? '';
              echo '<div class="form-group full-width">';
              echo '  <label class="form-label">';
              echo '    <i class="fa-solid fa-'.$icon.' icon-gold fa-fw"></i>';
              echo '    '.$label;
              echo '  </label>';
              echo '  <div class="input-group">';
              echo '    <button class="options-manage-btn" type="button" onclick="openOptions(\''.$key.'\',\'إدارة '.$label.'\')">';
              echo '      <i class="fa-solid fa-cog"></i>';
              echo '    </button>';
              echo '    <select id="'.$key.'" name="'.$key.'" class="form-control" data-saved="'.htmlspecialchars($saved_val,ENT_QUOTES).'"></select>';
              echo '  </div>';
              echo '</div>';
            }
          ?>
        </div>
      </div>
    </div>

    <!-- زر حفظ واحد -->
    <div class="form-footer">
      <button id="saveBtn" class="btn btn-primary">
        <span class="btn-text">
          <i class="fa-solid fa-save"></i> حفظ ومتابعة
        </span>
        <span class="d-none spinner" id="saveSpinner">
          <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
        </span>
      </button>
    </div>
  </form>
</div>

<!-- Modal إدارة الخيارات -->
<div class="modal fade" id="optionsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTitle"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="modalAlert"></div>
        <ul id="optionsList" class="list-group list-group-flush mb-3"></ul>
        <div class="border-top pt-3">
          <label class="form-label">إضافة خيار جديد</label>
          <textarea id="addTextarea" class="form-control mb-2" rows="3"></textarea>
          <button class="btn btn-sm btn-primary" onclick="addOption()">
            <i class="fa-solid fa-plus"></i> إضافة الخيار
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal تعيين الافتراضي -->
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
const quoteId = <?php echo (int)$quote_ref_id; ?>;
const customerId = <?php echo (int)$customer['id']; ?>;
const quoteBrand = '<?php echo $quote_brand; ?>';
const quoteDoorType = '<?php echo $quote_door_type; ?>';

// الحقول التي نحمّل خياراتها من جدول الإعدادات
const optionKeys = [
  'first_party','iban','contract_opening',
  'article_1','article_2','article_3','article_5','article_8','article_9','article_10'
];

// لتخزين الحالة الحالية لمودال الخيارات
let modalKey = null;
let modalOptions = [];
let modalDefaults = {};
let editingIndex = -1;
let selectedDefaultIndex = -1;
let optionsModal, brandModal;

// متغيرات للفلترة الجديدة
let selectedBrand = null, selectedDoorType = null;

document.addEventListener('DOMContentLoaded', () => {
  optionsModal = new bootstrap.Modal(document.getElementById('optionsModal'));
  brandModal = new bootstrap.Modal(document.getElementById('brandModal'));

  // تهيئة select2
  optionKeys.forEach(k => {
    const el = document.getElementById(k);
    if (el) $(el).select2({ 
      theme:'bootstrap-5', 
      placeholder:'اختر...', 
      language:{ noResults:()=> "لا توجد نتائج" }
    });
  });

  // تحميل الخيارات لكل الحقول + تعيين القيمة الحالية
  loadAllOptions();

  // حفظ واحد
  document.getElementById('contractForm').addEventListener('submit', e => e.preventDefault());
  document.getElementById('saveBtn').addEventListener('click', doSave);
});

async function loadAllOptions(){
  for (const key of optionKeys){
    await loadOne(key);
  }
  // تعبئة القيم الحالية للثلاثة الأساسية
  setSavedIfAny('first_party', <?php echo json_encode($current['first_party']); ?>);
  setSavedIfAny('iban', <?php echo json_encode($current['iban']); ?>);
  setSavedIfAny('contract_opening', <?php echo json_encode($current['contract_opening']); ?>);
}

function setSavedIfAny(key, saved){
  const $sel = $('#'+key);
  if (!saved) return;
  const exists = $sel.find('option').toArray().some(o => o.value===saved);
  if (exists) $sel.val(saved).trigger('change');
}

async function loadOne(key){
  try{
    console.log(`تحميل خيارات الحقل: ${key}`);
    console.log(`البراند: ${quoteBrand}, نوع الباب: ${quoteDoorType}`);
    
    const res = await api('get_options', { 
      key, 
      quote_brand: quoteBrand,
      quote_door_type: quoteDoorType
    });
    
    const $sel = $('#'+key);
    $sel.empty();
    
    if (res.success && Array.isArray(res.options) && res.options.length){
      res.options.forEach(o => $sel.append(new Option(o,o)));
      const saved = $('#'+key).data('saved') || $('#'+key).attr('data-saved'); // للمواد
      if (saved && res.options.includes(saved)) {
        $sel.val(saved);
      } else if (res.current_default && res.options.includes(res.current_default)) {
        $sel.val(res.current_default);
      }
    } else {
      $sel.append(new Option('لا توجد خيارات','',true,true)).prop('disabled',true);
    }
    $sel.trigger('change');
  }catch(e){
    console.error('loadOne', key, e);
  }
}

function openOptions(key, title){
  modalKey = key;
  editingIndex = -1;
  document.getElementById('modalTitle').textContent = title;
  document.getElementById('optionsList').innerHTML = '<li class="list-group-item text-center"><span class="spinner"></span></li>';
  document.getElementById('addTextarea').value = '';
  loadModalData().then(()=> optionsModal.show());
}

async function loadModalData(){
  const res = await api('get_options', { 
    key: modalKey,
    quote_brand: quoteBrand,
    quote_door_type: quoteDoorType
  });
  
  if (!res.success){ 
    return showModalAlert('تعذر تحميل الخيارات','error'); 
  }
  
  modalOptions = res.options || [];
  modalDefaults = res.defaults || {};
  renderOptionsList();
}

function renderOptionsList(){
  const list = document.getElementById('optionsList');
  list.innerHTML = '';
  
  if (!modalOptions.length){
    list.innerHTML = '<li class="list-group-item text-center text-muted">لا توجد خيارات محفوظة.</li>';
    return;
  }
  
  modalOptions.forEach((opt,idx)=>{
    const li = document.createElement('li');
    li.className='list-group-item d-flex justify-content-between align-items-center flex-wrap gap-2';
    
    if (editingIndex === idx) {
      li.innerHTML = `
        <div class="w-100">
          <textarea class="form-control mb-2" id="editInput${idx}" rows="3">${opt}</textarea>
        </div>
        <div class="ms-auto">
          <button class="btn btn-sm btn-success" onclick="saveEdit(${idx})">
            <i class="fa-solid fa-check"></i> حفظ
          </button>
          <button class="btn btn-sm btn-secondary ms-1" onclick="cancelEdit()">
            <i class="fa-solid fa-times"></i> إلغاء
          </button>
        </div>`;
    } else {
      let badges = '';
      
      // عرض شارات للتركيبات المختلفة
      Object.keys(modalDefaults).forEach(key => {
        if (modalDefaults[key] === opt) {
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
      
      let escapedOption = opt.replace(/</g, "&lt;").replace(/>/g, "&gt;");
      li.innerHTML = `
        <div class="option-item-text me-3 flex-grow-1">${escapedOption}</div>
        <div class="ms-auto flex-shrink-0">
          <div class="btn-group">
            ${badges}
            <button class="btn btn-sm btn-outline-secondary" onclick="editOption(${idx})" title="تعديل">
              <i class="fa-solid fa-edit"></i>
            </button>
            <button class="btn btn-sm btn-outline-secondary" onclick="setDefault(${idx})" title="تعيين افتراضي">
              <i class="fa-solid fa-star"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger" onclick="deleteOption(${idx})" title="حذف">
              <i class="fa-solid fa-trash"></i>
            </button>
          </div>
        </div>`;
    }
    
    list.appendChild(li);
  });
}

function editOption(idx){
  editingIndex = idx;
  renderOptionsList();
  setTimeout(() => {
    const editInput = document.getElementById(`editInput${idx}`);
    if (editInput) { editInput.focus(); editInput.select(); }
  }, 100);
}

function saveEdit(idx){
  const newValue = document.getElementById(`editInput${idx}`).value.trim();
  if (!newValue) return;
  
  if (newValue !== modalOptions[idx] && modalOptions.includes(newValue)) {
    showModalAlert('هذا الخيار موجود بالفعل.', 'error');
    return;
  }
  
  const oldValue = modalOptions[idx];
  modalOptions[idx] = newValue;
  
  Object.keys(modalDefaults).forEach(key => {
    if (modalDefaults[key] === oldValue) modalDefaults[key] = newValue;
  });
  
  editingIndex = -1;
  renderOptionsList();
}

function cancelEdit(){
  editingIndex = -1;
  renderOptionsList();
}

function setDefault(idx){
  selectedDefaultIndex = idx;
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
    
    modalDefaults[key] = modalOptions[selectedDefaultIndex];
    brandModal.hide();
    await saveOptions();
  }
}

function deleteOption(idx){
  if (!confirm('هل أنت متأكد من حذف هذا الخيار؟')) return;
  
  const deletedOption = modalOptions.splice(idx, 1)[0];
  Object.keys(modalDefaults).forEach(key => {
    if (modalDefaults[key] === deletedOption) delete modalDefaults[key];
  });
  
  renderOptionsList();
}

function addOption(){
  const t = document.getElementById('addTextarea');
  const v = (t.value || '').trim();
  if (!v) return;
  if (modalOptions.includes(v)) { 
    showModalAlert('الخيار موجود مسبقاً','error'); 
    return; 
  }
  modalOptions.push(v);
  if (Object.keys(modalDefaults).length === 0) modalDefaults['general'] = v;
  t.value='';
  renderOptionsList();
}

async function saveOptions(){
  if (!modalKey) return;
  
  console.log('حفظ الخيارات:', {
    field: modalKey,
    options: modalOptions,
    defaults: modalDefaults
  });
  
  const res = await api('update_options', { 
    key: modalKey, 
    options: JSON.stringify(modalOptions), 
    defaults: JSON.stringify(modalDefaults)
  });
  
  if (res.success){
    showModalAlert('تم حفظ الخيارات بنجاح','success');
    renderOptionsList();
    // أعِد تحميل القائمة الرئيسية للحقل الحالي
    await loadOne(modalKey);
    console.log('تم حفظ الخيارات بنجاح');
  }else{
    console.error('خطأ في حفظ الخيارات:', res.message);
    showModalAlert(res.message || 'فشل الحفظ','error');
  }
}

function showModalAlert(msg,type='info'){
  const c = document.getElementById('modalAlert');
  const cls = type==='success'?'alert-success':(type==='error'?'alert-danger':'alert-info');
  c.innerHTML = `<div class="message ${cls}">${msg}</div>`;
  setTimeout(()=> c.innerHTML='', 2500);
}

function setSaving(s){
  const btn = document.getElementById('saveBtn');
  const spinner = document.getElementById('saveSpinner');
  if (s){ 
    btn.disabled=true; 
    spinner.classList.remove('d-none'); 
    btn.querySelector('.btn-text').style.opacity='0.6'; 
  } else { 
    btn.disabled=false; 
    spinner.classList.add('d-none'); 
    btn.querySelector('.btn-text').style.opacity='1'; 
  }
}

async function doSave(){
  const gender   = (document.getElementById('gender').value || '').trim();
  const identity = (document.getElementById('identity').value || '').trim();
  const address  = (document.getElementById('address').value || '').trim();

  // تطبيع رقم الجوال
  let cust_phone = (document.getElementById('cust_phone').value || '').replace(/[^0-9]/g,'');
  if (cust_phone.startsWith('0')) cust_phone = cust_phone.slice(1);
  if (cust_phone && !/^5\d{8}$/.test(cust_phone)){
    showAlert('userAlert','رقم الجوال غير صحيح. يجب أن يبدأ بـ 5 ويتكون من 9 أرقام','error');
    return;
  }

  if (!gender || !identity || !address){
    showAlert('userAlert','يرجى تعبئة (الجنس) و(الهوية/السجل التجاري) و(العنوان)','error');
    return;
  }

  setSaving(true);
  const data = {
    quote_id: quoteId,
    customer_id: customerId,
    gender, identity, address,

    cust_name: (document.getElementById('cust_name').value || '').trim(),
    cust_phone: cust_phone,

    first_party: $('#first_party').val() || '',
    iban: $('#iban').val() || '',
    contract_opening: $('#contract_opening').val() || '',

    article_1:  $('#article_1').val()  || '',
    article_2:  $('#article_2').val()  || '',
    article_3:  $('#article_3').val()  || '',
    article_5:  $('#article_5').val()  || '',
    article_8:  $('#article_8').val()  || '',
    article_9:  $('#article_9').val()  || '',
    article_10: $('#article_10').val() || '',
  };

  try{
    const res = await api('save_all', data);
    if (res.success){
      showAlert('contractAlert', res.message, 'success');
      showAlert('articlesAlert', res.message, 'success');
      showAlert('userAlert', res.message, 'success');

      // تحويل إلى الصفحة الجديدة بعد 2 ثانية
      setTimeout(() => {
        window.location.href = `https://alfagolden.com/system/q/9.php?quote_id=${quoteId}`;
      }, 2000);
    }else{
      showAlert('contractAlert', res.message || 'فشل الحفظ', 'error');
    }
  }catch(e){
    showAlert('contractAlert','حدث خطأ في الاتصال','error');
  }finally{
    setSaving(false);
  }
}

function showAlert(where, msg, type='info'){
  const c = document.getElementById(where);
  if(!c) return;
  const cls = type==='success'?'alert-success':(type==='error'?'alert-danger':'alert-info');
  c.innerHTML = `<div class="message ${cls}">${msg}</div>`;
  c.scrollIntoView({behavior:'smooth', block:'center'});
  setTimeout(()=> c.innerHTML='', 4000);
}

async function api(action, data){
  const fd = new FormData();
  fd.append('ajax','1'); 
  fd.append('action',action);
  Object.keys(data||{}).forEach(k => fd.append(k, data[k]));
  const res = await fetch(window.location.href, { method:'POST', body:fd });
  if (!res.ok) throw new Error('HTTP '+res.status);
  return await res.json();
}
</script>
</body>
</html>