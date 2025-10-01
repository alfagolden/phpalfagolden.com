<?php
$baserow_url = "https://base.alfagolden.com";
$table_id = 704;
$token = "h5qAt85gtiJDAzpH51WrXPywhmnhrPWy";
$quote_id = isset($_GET['quote_id']) ? intval($_GET['quote_id']) : 1;

function fetchQuoteData($quote_id, $baserow_url, $table_id, $token) {
    $url = "{$baserow_url}/api/database/rows/table/{$table_id}/{$quote_id}/?user_field_names=true";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Token {$token}"]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ($httpCode == 200) ? json_decode($response, true) : null;
}

function extractValue($field, $default = '') {
    return (is_array($field) && !empty($field) && isset($field[0]['value']))
        ? (is_array($field[0]['value']) ? ($field[0]['value']['value'] ?? $default) : $field[0]['value'])
        : ($field ?: $default);
}

function formatGregorianDateToYMD($dateStr) {
    if (!$dateStr) return '';
    $date = new DateTime($dateStr);
    return $date->format('Y/m/d');
}

function formatNumberForDisplay($num) {
    return number_format($num, 0, '.', '');
}

// فحص متقدم لحالة PDF مع مراعاة عمر الملف
function checkAdvancedPdfStatus($quote_data) {
    $pdf_url = $quote_data['pdf'] ?? '';
    $pdf_time = $quote_data['pdftime'] ?? '';
    $last_modified = $quote_data['وقت اخر تعديل'] ?? '';
    
    // إذا لم يكن هناك رابط صالح
    if (empty($pdf_url) || $pdf_url === 'https://baserow.io' || !filter_var($pdf_url, FILTER_VALIDATE_URL)) {
        return [
            'status' => 'generate_needed',
            'reason' => 'no_valid_url',
            'message' => 'إنشاء ملف PDF',
            'auto_generate' => true
        ];
    }
    
    // إذا لم يكن هناك تاريخ إنشاء PDF أو تاريخ آخر تعديل
    if (empty($pdf_time) || empty($last_modified)) {
        return [
            'status' => 'generate_needed',
            'reason' => 'missing_timestamps',
            'message' => 'إنشاء ملف PDF',
            'auto_generate' => true,
            'clear_old_url' => true
        ];
    }
    
    try {
        // تحويل التواريخ لمقارنة
        $pdf_timestamp = new DateTime($pdf_time);
        $modified_timestamp = new DateTime($last_modified);
        
        // حساب الفرق بالثواني
        $diff_seconds = $modified_timestamp->getTimestamp() - $pdf_timestamp->getTimestamp();
        
        // إذا كان الفرق أكثر من 120 ثانية، الملف قديم
        if ($diff_seconds > 120) {
            return [
                'status' => 'generate_needed',
                'reason' => 'file_outdated',
                'message' => 'إنشاء ملف PDF محدث',
                'auto_generate' => true,
                'clear_old_url' => true,
                'age_diff' => $diff_seconds
            ];
        }
        
        // الملف حديث وصالح
        return [
            'status' => 'ready',
            'url' => $pdf_url,
            'message' => 'تحميل ملف PDF',
            'auto_generate' => false,
            'age_diff' => $diff_seconds
        ];
        
    } catch (Exception $e) {
        // خطأ في تحليل التاريخ
        return [
            'status' => 'generate_needed',
            'reason' => 'date_parse_error',
            'message' => 'إنشاء ملف PDF',
            'auto_generate' => true,
            'clear_old_url' => true,
            'error' => $e->getMessage()
        ];
    }
}

// دالة لحذف الرابط القديم
function clearOldPdfUrl($quote_id, $baserow_url, $table_id, $token) {
    $url = "{$baserow_url}/api/database/rows/table/{$table_id}/{$quote_id}/?user_field_names=true";
    
    $data = [
        'pdf' => '',
        'pdftime' => ''
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Token {$token}",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ($httpCode == 200);
}

// معالجة طلب مسح الرابط القديم عبر AJAX
if (isset($_POST['action']) && $_POST['action'] === 'clear_old_pdf') {
    header('Content-Type: application/json');
    
    try {
        $result = clearOldPdfUrl($quote_id, $baserow_url, $table_id, $token);
        
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'تم مسح الرابط القديم' : 'فشل في مسح الرابط القديم'
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'خطأ: ' . $e->getMessage()
        ]);
    }
    exit;
}

// معالجة طلب إنشاء PDF عبر AJAX (محدثة)
if (isset($_POST['action']) && $_POST['action'] === 'generate_pdf') {
    header('Content-Type: application/json');
    
    try {
        // مسح الرابط القديم أولاً إذا كان مطلوباً
        if (isset($_POST['clear_old']) && $_POST['clear_old'] === 'true') {
            clearOldPdfUrl($quote_id, $baserow_url, $table_id, $token);
            // انتظار قصير للتأكد من التحديث
            sleep(1);
        }
        
        // استدعاء الـ workflow
        $n8n_webhook_url = "https://n8n.alfagolden.com/webhook/cd5ffafa-b29f-414e-9b49-651b793b7586";
        $request_url = $n8n_webhook_url . "?quote_id=" . $quote_id;
        
        $ch = curl_init($request_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            // إعادة جلب البيانات المحدثة
            $updated_data = fetchQuoteData($quote_id, $baserow_url, $table_id, $token);
            $pdf_status = checkAdvancedPdfStatus($updated_data);
            
            echo json_encode([
                'success' => true,
                'status' => $pdf_status['status'],
                'url' => $pdf_status['url'] ?? '',
                'message' => $pdf_status['message'],
                'auto_generate' => $pdf_status['auto_generate'] ?? false
            ]);
        } else {
            throw new Exception("فشل في إنشاء الملف - رمز الخطأ: {$httpCode}");
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'حدث خطأ أثناء إنشاء الملف: ' . $e->getMessage()
        ]);
    }
    exit;
}

// معالجة طلب فحص حالة PDF عبر AJAX (محدثة)
if (isset($_POST['action']) && $_POST['action'] === 'check_status') {
    header('Content-Type: application/json');
    
    $updated_data = fetchQuoteData($quote_id, $baserow_url, $table_id, $token);
    $pdf_status = checkAdvancedPdfStatus($updated_data);
    
    echo json_encode([
        'status' => $pdf_status['status'],
        'url' => $pdf_status['url'] ?? '',
        'message' => $pdf_status['message'],
        'auto_generate' => $pdf_status['auto_generate'] ?? false,
        'reason' => $pdf_status['reason'] ?? '',
        'age_diff' => $pdf_status['age_diff'] ?? null
    ]);
    exit;
}

$quote_data = fetchQuoteData($quote_id, $baserow_url, $table_id, $token);
if (!$quote_data) die("لا يمكن الحصول على بيانات العرض. تأكد من صحة رقم العرض.");

// فحص حالة PDF المتقدم
$pdf_status = checkAdvancedPdfStatus($quote_data);

$data = [
    'quote_number' => $quote_data['الرقم التست'] ?? '',
    'date' => $quote_data['تاريخ'] ?? '',
    'title_before' => extractValue($quote_data['قبل الاسم']),
    'client_name' => extractValue($quote_data['اسم العميل']),
    'title_after' => extractValue($quote_data['بعد الاسم']),
    'project' => extractValue($quote_data['المشروع']),
    'address' => extractValue($quote_data['العنوان']),
    'opening_text' => $quote_data['جملة البداية'] ?? '',
    'introductory_sentence' => $quote_data['الجملة التمهيدية'] ?? '',
    'elevators_count' => $quote_data['عدد المصاعد'] ?? '',
    'stops_count' => extractValue($quote_data['عدد الوقفات']),
    'capacity' => extractValue($quote_data['الحمولة']),
    'people_count' => extractValue($quote_data['عدد الاشخاص']),
    'entrance_count' => $quote_data['عدد جهات الدخول'] ?? '',
    'machine_position' => extractValue($quote_data['وضع الماكينة']),
    'machine_type' => $quote_data['نوع المكينة'] ?? '',
    'control_device' => $quote_data['جهاز تشغيل المصعد'] ?? '',
    'operation_method' => $quote_data['طريقة التشغيل'] ?? '',
    'stops_names' => $quote_data['مسميات الوقفات'] ?? '',
    'electrical_current' => $quote_data['التيار الكهربائي'] ?? '',
    'well_material' => extractValue($quote_data['البئر - مبني من']),
    'well_dimensions' => extractValue($quote_data['البئر - المقاس الداخلي']),
    'door_operation_method' => $quote_data['طريقة تشغيل الأبواب'] ?? '',
    'door_dimensions' => $quote_data['مقاسات الابواب'] ?? '',
    'internal_door' => $quote_data['الباب الداخلي'] ?? '',
    'elevator_rails' => $quote_data['سكك الصاعدة'] ?? '',
    'counterweight_rails' => $quote_data['سكك ثقل الموازنة'] ?? '',
    'traction_ropes' => $quote_data['حبال الجر'] ?? '',
    'flexible_cable' => $quote_data['الكابل المرن'] ?? '',
    'carrier_frame' => $quote_data['الاطار الحامل للصاعدة'] ?? '',
    'cabin_finishing' => $quote_data['الصاعدة - التشطيب'] ?? '',
    'cabin_dimensions' => extractValue($quote_data['الصاعدة - المقاسات الداخلية']),
    'ceiling' => $quote_data['السقف'] ?? '',
    'emergency_lighting' => $quote_data['اضاءة الطوارئ'] ?? '',
    'cabin_movement_device' => $quote_data['جهاز تحريك الصاعدة'] ?? '',
    'flooring' => $quote_data['الأرضية'] ?? '',
    'internal_cop' => $quote_data['لوحة الطلب الداخلية COP'] ?? '',
    'external_panel_finishing' => $quote_data['لوحة الطلب الخارجية - التشطيب'] ?? '',
    'external_main_stop' => $quote_data['لوحة الطلب الخارجية - الوقفة الرئيسية'] ?? '',
    'external_other_stops' => $quote_data['لوحة الطلب الخارجية - الوقفات الاخرى'] ?? '',
    'preparatory_work' => $quote_data['الأعمال التحضيرية'] ?? '',
    'warranty_maintenance' => $quote_data['الضمان والصيانة المجانية'] ?? '',
    'supply_installation' => $quote_data['التوريد والتركيب'] ?? '',
    'closing_sentence' => $quote_data['الجملة الختامية'] ?? '',
    'price_before_vat' => formatNumberForDisplay($quote_data['السعر قبل ضريبة القيمة المضافة (VAT)'] ?? 0),
    'vat_amount' => formatNumberForDisplay($quote_data['ضريبة القيمة المضافة (VAT) 15%'] ?? 0),
    'total_price_with_vat' => formatNumberForDisplay($quote_data['السعر شامل ضريبة القيمة المضافة (VAT) 15%'] ?? 0),
    'price_details' => $quote_data['تفاصيل السعر'] ?? '',
    'discount_amount' => formatNumberForDisplay($quote_data['مبلغ التخفيض'] ?? 0),
    'brand' => extractValue($quote_data['البراند']),
    'machine_brand' => extractValue($quote_data['الماكينة'])
];

$formattedDate = formatGregorianDateToYMD($data['date']);
$quote_type = (stripos($data['brand'], 'elite') !== false || stripos($data['brand'], 'إليت') !== false) ? 'ELITE' : 'PRO';

$additions = [];
if (!empty($data['price_details'])) {
    $price_details = json_decode($data['price_details'], true);
    if (json_last_error() === JSON_ERROR_NONE && isset($price_details['additions'])) {
        foreach ($price_details['additions'] as $idx => $addition) {
            $price_details['additions'][$idx]['price'] = formatNumberForDisplay($addition['price']);
            $price_details['additions'][$idx]['total'] = formatNumberForDisplay($addition['total']);
        }
        $additions = $price_details['additions'];
    }
}

function renderTable($items) {
    $html = '<table class="modern-table"><tbody>';
    foreach ($items as $label => $value) {
        if (!empty(trim(strval($value)))) {
            $html .= "<tr><td class=\"table-label\">".htmlspecialchars($label)."</td><td>".htmlspecialchars($value)."</td></tr>";
        }
    }
    $html .= '</tbody></table>';
    return $html;
}

function renderSection($title, $icon, $content, $isLongText = false) {
    $contentClass = $isLongText ? 'long-text' : '';
    return "
    <div class=\"card section-card\">
        <div class=\"card-header\">
            <h2 class=\"card-title\">
                <i class=\"fas fa-{$icon} section-icon\"></i>
                {$title}
            </h2>
        </div>
        <div class=\"card-body\">
            <div class=\"{$contentClass}\">{$content}</div>
        </div>
    </div>";
}

$safety_devices = [
    'أجهزة الاتصال الكهربائية' => $quote_data['أجهزة الطوارئ والأمان - أجهزة الاتصال الكهربائية'] ?? '',
    'أجهزة الطوارئ' => $quote_data['أجهزة الطوارئ والأمان - أجهزة الطوارئ'] ?? '',
    'أجهزة الإنارة' => $quote_data['أجهزة الطوارئ والأمان - أجهزة الانارة'] ?? '',
    'أجهزة الأمان' => $quote_data['أجهزة الطوارئ والأمان - أجهزة الأمان'] ?? '',
    'ستارة ضوئية' => $quote_data['أجهزة الطوارئ والأمان - ستارة ضوئية'] ?? '',
    'جهاز منظم السرعة' => $quote_data['أجهزة الطوارئ والأمان - جهاز منظم السرعة'] ?? '',
    'مخففات الصدمات' => $quote_data['أجهزة الطوارئ والأمان - مخففات الصدمات'] ?? '',
    'جهاز نهاية المشوار' => $quote_data['أجهزة الطوارئ والأمان - جهاز نهاية المشوار'] ?? '',
    'كامة تأمين فتح الباب' => $quote_data['أجهزة الطوارئ والأمان - كامة تأمين فتح الباب'] ?? '',
    'مزايت الصاعدة' => $quote_data['أجهزة الطوارئ والأمان - مزايت الصاعدة'] ?? '',
    'مفتاح الباب الخارجي' => $quote_data['أجهزة الطوارئ والأمان - مفتاح الباب الخارجى'] ?? '',
    'التوصيلات الكهربائية' => $quote_data['أجهزة الطوارئ والأمان - التوصيلات الكهربائية'] ?? ''
];
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عرض سعر رقم <?= htmlspecialchars($data['quote_number']) ?> - شركة ألفا الذهبية للمصاعد</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        :root {
            --gold: #977e2b; --gold-hover: #b89635; --gold-light: rgba(151, 126, 43, 0.1);
            --dark-gray: #2c2c2c; --medium-gray: #666; --light-gray: #f8f9fa; --white: #ffffff;
            --border-color: #e5e7eb; --shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            --radius-small: 6px; --radius-medium: 8px; --radius-large: 12px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Cairo', sans-serif; font-size: 14px; line-height: 1.5; direction: rtl;
            background: var(--light-gray); color: var(--dark-gray); padding-bottom: 40px;
            background-image: linear-gradient(rgba(255,255,255,0.95), rgba(255,255,255,0.95)), url('https://alfagolden.com/bk.jpg');
            background-size: cover; background-position: center bottom; background-attachment: fixed;
        }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .card {
            background: var(--white); border-radius: var(--radius-large); box-shadow: var(--shadow);
            border: 1px solid var(--border-color); margin-bottom: 20px; overflow: hidden;
        }
        .card-header { padding: 20px 24px; border-bottom: 1px solid var(--border-color); background: var(--light-gray); }
        .card-body { padding: 24px; }
        .card-title {
            font-size: 18px; font-weight: 600; margin: 0; display: flex; align-items: center;
            gap: 8px; color: var(--dark-gray);
        }
        .cover-card {
            background: linear-gradient(to bottom, var(--white) 50%, var(--gold) 50%);
            margin-bottom: 30px; min-height: 100vh; display: flex; flex-direction: column;
            padding: 0; position: relative; overflow: hidden;
        }
        .cover-card::before {
            content: ''; position: absolute; top: 50%; left: 0; right: 0; height: 20px;
            background: radial-gradient(ellipse at center, transparent 0%, transparent 30%, var(--gold) 100%);
            transform: translateY(-50%); z-index: 2;
        }
        .cover-top-section, .cover-bottom-section {
            flex: 1; display: flex; flex-direction: column; justify-content: center; align-items: center;
            padding: 60px 40px; position: relative; z-index: 3; text-align: center;
        }
        .cover-top-section { background: var(--white); }
        .cover-bottom-section {
            background: var(--gold); color: var(--white);
            background-image: linear-gradient(to bottom, rgba(151, 126, 43, 0.9) 0%, rgba(151, 126, 43, 0.7) 50%, rgba(151, 126, 43, 0.4) 100%), url('https://alfagolden.com/q.jpg');
            background-size: cover; background-position: center bottom; background-repeat: no-repeat;
        }
        .cover-logo {
            width: 300px; margin-bottom: 30px; background: var(--white); padding: 20px;
            border-radius: 15px; box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .cover-meta-info {
            position: absolute; top: 20px; right: 20px; font-size: 13px; color: var(--medium-gray);
            text-align: right; background: rgba(255,255,255,0.95); padding: 15px; border-radius: 8px;
            box-shadow: var(--shadow); z-index: 4;
        }
        .cover-quote-title { margin-bottom: 30px; }
        .cover-quote-title h1 {
            font-size: 42px; font-weight: 700; color: var(--white); margin-bottom: 10px;
            line-height: 1.2; text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        .cover-quote-title h2 {
            font-size: 32px; font-weight: 600; color: var(--white); line-height: 1.2;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        .cover-client-info {
            margin-bottom: 25px; font-size: 28px; font-weight: 600; line-height: 1.6;
            color: var(--white); text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        .cover-client-info .title-parts { color: rgba(255,255,255,0.9); margin: 0 10px; }
        .cover-client-info .client-name { color: var(--white); font-weight: 700; }
        .cover-project-info {
            font-size: 20px; color: rgba(255,255,255,0.9); line-height: 1.6; margin-bottom: 25px;
        }
        .cover-specs {
            display: flex; justify-content: center; gap: 15px; font-size: 16px;
            color: rgba(255,255,255,0.9); background: rgba(0,0,0,0.1); padding: 15px;
            border-radius: 8px; flex-wrap: wrap;
        }
        .opening-text {
            text-align: center; font-weight: 600; font-size: 16px; margin-bottom: 20px;
            color: var(--gold); line-height: 1.7;
        }
        .intro-text { line-height: 1.7; text-align: justify; margin-bottom: 20px; font-size: 14px; }
        .specs-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 15px; margin-bottom: 20px;
        }
        .spec-item {
            border: 1px solid var(--border-color); border-radius: var(--radius-medium); overflow: hidden;
            transition: transform 0.2s ease; background: var(--white);
        }
        .spec-label {
            background: var(--light-gray); padding: 12px 16px; font-weight: 600; font-size: 13px;
            text-align: center; border-bottom: 1px solid var(--border-color); color: var(--medium-gray);
        }
        .spec-value {
            padding: 16px; font-size: 14px; min-height: 55px; display: flex; align-items: center;
            justify-content: center; text-align: center; font-weight: 500;
        }
        .spec-highlight { color: var(--gold); font-weight: 600; }
        .modern-table {
            width: 100%; border-collapse: collapse; background: var(--white); border-radius: var(--radius-medium);
            overflow: hidden; border: 1px solid var(--border-color); margin: 15px 0;
        }
        .modern-table th {
            background: var(--light-gray); padding: 12px 16px; text-align: right; font-weight: 600;
            font-size: 12px; border-bottom: 1px solid var(--border-color);
        }
        .modern-table td {
            padding: 12px 16px; text-align: right; border-bottom: 1px solid var(--border-color);
            font-size: 13px; vertical-align: top;
        }
        .modern-table tbody tr:last-child td { border-bottom: none; }
        .table-label { background: var(--light-gray); font-weight: 600; width: 40%; color: var(--medium-gray); }
        .long-text { line-height: 1.7; text-align: justify; font-size: 14px; word-wrap: break-word; }
        .section-icon {
            width: 24px; height: 24px; background: var(--gold); border-radius: var(--radius-small); display: inline-flex;
            align-items: center; justify-content: center; color: var(--white); font-size: 12px;
            margin-left: 8px; flex-shrink: 0;
        }
        .price-card { border: 2px solid var(--gold); }
        .price-row {
            display: flex; justify-content: space-between; align-items: center; padding: 15px 0;
            font-size: 14px; border-bottom: 1px solid var(--border-color);
        }
        .price-row:last-child {
            border-bottom: none; margin-top: 15px; padding-top: 20px; border-top: 2px solid var(--gold);
            font-weight: 700; font-size: 16px; color: var(--gold);
        }
        .price-value {
            font-weight: 600; color: var(--gold); display: flex; align-items: center;
            justify-content: flex-end; gap: 6px; direction: ltr;
        }
        .sar-icon { width: 16px; height: 16px; fill: currentColor; flex-shrink: 0; }
        .approval-btn {
            display: block; width: 100%; max-width: 280px; margin: 25px auto; padding: 12px 24px;
            background: var(--gold); color: var(--white); text-decoration: none; border-radius: var(--radius-medium);
            font-size: 14px; font-weight: 600; text-align: center;
            box-shadow: 0 4px 15px rgba(151, 126, 43, 0.3); transition: all 0.3s ease;
            border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .approval-btn:hover {
            background: var(--gold-hover); transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(151, 126, 43, 0.4);
        }
        .final-cover-company { font-size: 28px; font-weight: 700; margin-bottom: 15px; color: var(--gold); }
        .final-cover-department { font-size: 24px; font-weight: 600; margin-bottom: 25px; color: var(--dark-gray); }
        .final-cover-signature { width: 150px; margin: 20px auto; }
        .contact-info {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px; margin-bottom: 30px;
        }
        .contact-section h3 { font-size: 18px; margin-bottom: 15px; color: var(--white); }
        .contact-item {
            display: flex; align-items: center; gap: 10px; margin-bottom: 10px; font-size: 14px;
        }
        .contact-icon {
            width: 20px; height: 20px; background: rgba(255, 255, 255, 0.2); border-radius: 4px;
            display: flex; align-items: center; justify-content: center; color: var(--white); flex-shrink: 0;
        }
        .social-media { display: flex; justify-content: flex-start; gap: 20px; margin-top: 30px; direction: ltr; }
        .social-link {
            width: 45px; height: 45px; background: rgba(255, 255, 255, 0.2); border-radius: var(--radius-medium);
            display: flex; align-items: center; justify-content: center; color: var(--white);
            text-decoration: none; transition: all 0.3s ease;
        }
        .social-link:hover { background: var(--white); color: var(--gold); transform: translateY(-2px); }

        /* أزرار الحفظ والمشاركة */
        .action-section {
            text-align: center; padding: 40px 20px; 
        }
        .action-buttons {
            display: flex; gap: 20px; justify-content: center; align-items: center; flex-wrap: wrap;
        }
        .action-btn {
            background: var(--gold); border: none; color: var(--white); padding: 15px 30px;
            border-radius: var(--radius-medium); display: flex; align-items: center; gap: 10px; font-size: 16px;
            font-weight: 600; cursor: pointer; transition: all 0.3s ease;
            text-decoration: none; min-width: 160px; justify-content: center;
            box-shadow: 0 4px 15px rgba(151, 126, 43, 0.3);
        }
        .action-btn:hover {
            background: var(--gold-hover); transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(151, 126, 43, 0.4);
        }
        .action-btn.secondary {
            background: var(--medium-gray); color: var(--white);
        }
        .action-btn.secondary:hover {
            background: var(--dark-gray);
        }
        
        /* حالات زر الحفظ */
        .action-btn.loading { 
            background: var(--medium-gray); cursor: not-allowed; 
        }
        .action-btn.loading i {
            animation: spin 1s linear infinite;
        }
        

        
        @keyframes spin { 
            0% { transform: rotate(0deg); } 
            100% { transform: rotate(360deg); } 
        }

        @media (max-width: 768px) {
            body { font-size: 14px; }
            .container { padding: 16px; }
            .cover-card { padding: 40px 20px; }
            .cover-logo { width: 250px; }
            .cover-quote-title h1 { font-size: 32px; }
            .cover-quote-title h2 { font-size: 24px; }
            .cover-meta-info { position: static; margin-bottom: 20px; }
            .cover-specs { flex-direction: column; gap: 8px; text-align: center; }
            .specs-grid { grid-template-columns: 1fr; }
            .contact-info { grid-template-columns: 1fr; }
            .social-media { justify-content: center; }
            .action-buttons { flex-direction: column; gap: 15px; }
            .action-btn { min-width: 200px; padding: 12px 25px; font-size: 15px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- محتوى العرض -->
        <div class="card cover-card">
            <div class="cover-top-section">
                <div class="cover-meta-info">
                    <div><strong>رقم العرض:</strong> <?= htmlspecialchars($data['quote_number']) ?></div>
                    <div><strong>التاريخ:</strong> <?= $formattedDate ?></div>
                </div>
                <img src="https://alfagolden.com/images/logo.png" alt="شعار شركة ألفا الذهبية" class="cover-logo">
            </div>
            <div class="cover-bottom-section">
                <div class="cover-quote-title">
                    <h1>عرض سعر</h1>
                    <h2>ALFA <?= strtoupper($quote_type) ?></h2>
                </div>
                <div class="cover-client-info">
                    <span class="title-parts"><?= htmlspecialchars($data['title_before']) ?></span>
                    <span class="client-name"><?= htmlspecialchars($data['client_name']) ?></span>
                    <span class="title-parts"><?= htmlspecialchars($data['title_after']) ?></span>
                </div>
                <div class="cover-project-info">
                    <div><strong>المشروع:</strong> <?= htmlspecialchars($data['project']) ?></div>
                    <div><strong>العنوان:</strong> <?= htmlspecialchars($data['address']) ?></div>
                </div>
                <div class="cover-specs">
                    <span>عدد المصاعد: <?= htmlspecialchars($data['elevators_count']) ?></span>
                    <span>|</span>
                    <span>عدد الوقفات: <?= htmlspecialchars($data['stops_count']) ?></span>
                    <span>|</span>
                    <span>الحمولة: <?= htmlspecialchars($data['capacity']) ?> kg</span>
                </div>
            </div>
        </div>

        <?php if (!empty($data['opening_text'])): ?>
        <div class="card">
            <div class="card-body">
                <div class="opening-text"><?= nl2br(htmlspecialchars($data['opening_text'])) ?></div>
                <div class="opening-text" style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="color: var(--dark-gray);"><?= htmlspecialchars($data['title_before']) ?> /</span>
                    <span style="color: var(--gold); font-weight: 700;"><?= htmlspecialchars($data['client_name']) ?></span>
                    <span style="color: var(--dark-gray);"><?= htmlspecialchars($data['title_after']) ?></span>
                </div>
                <?php if (!empty($data['introductory_sentence'])): ?>
                <div class="intro-text"><?= nl2br(htmlspecialchars($data['introductory_sentence'])) ?></div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header"><h2 class="card-title"><i class="fas fa-clipboard-list section-icon"></i>المواصفات العامة</h2></div>
            <div class="card-body">
                <div class="specs-grid">
                    <?php
                    $specs = [
                        ['label' => 'نوع المصعد', 'value' => 'MRL'],
                        ['label' => 'نوع المبنى', 'value' => 'مبنى سكني'],
                        ['label' => 'عدد المصاعد', 'value' => $data['elevators_count'], 'highlight' => true],
                        ['label' => 'السرعة', 'value' => '1.0 m/s'],
                        ['label' => 'وضع الماكينة', 'value' => $data['machine_position']],
                        ['label' => 'عدد الوقفات', 'value' => $data['stops_count'], 'highlight' => true],
                        ['label' => 'الحمولة', 'value' => $data['capacity'] . ' kg', 'highlight' => true],
                        ['label' => 'عدد الأشخاص', 'value' => $data['people_count'], 'highlight' => true],
                        ['label' => 'عدد جهات الدخول', 'value' => $data['entrance_count']],
                        ['label' => 'البراند', 'value' => $data['brand']]
                    ];
                    foreach ($specs as $spec): ?>
                    <div class="spec-item">
                        <div class="spec-label"><?= $spec['label'] ?></div>
                        <div class="spec-value <?= isset($spec['highlight']) ? 'spec-highlight' : '' ?>"><?= htmlspecialchars($spec['value']) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?= renderTable(['طريقة التشغيل' => $data['operation_method'], 'مسميات الوقفات' => $data['stops_names'], 'التيار الكهربائي' => $data['electrical_current']]) ?>
            </div>
        </div>

        <?php if (!empty($data['machine_type'])) echo renderSection('نوع الماكينة', 'cog', nl2br(htmlspecialchars($data['machine_type'])), true); ?>
        <?php if (!empty($data['control_device'])) echo renderSection('جهاز تشغيل المصعد', 'sliders-h', nl2br(htmlspecialchars($data['control_device'])), true); ?>
        <?= renderSection('البئر', 'circle', renderTable(['مبني من' => $data['well_material'], 'المقاس الداخلي' => $data['well_dimensions']])) ?>
        <?= renderSection('الأبواب الخارجية والداخلية', 'door-open', renderTable(['طريقة التشغيل' => $data['door_operation_method'], 'التشطيب' => $data['cabin_finishing'], 'مقاساتها' => $data['door_dimensions'], 'الباب الداخلي' => $data['internal_door']])) ?>
        <?= renderSection('الدلائل والتعليق', 'link', renderTable(['سكك الصاعدة' => $data['elevator_rails'], 'سكك ثقل الموازنة' => $data['counterweight_rails'], 'حبال الجر' => $data['traction_ropes'], 'الكابل المرن' => $data['flexible_cable'], 'الإطار الحامل للصاعدة' => $data['carrier_frame']])) ?>
        <?= renderSection('الصاعدة', 'home', renderTable(['التشطيب' => $data['cabin_finishing'], 'المقاسات الداخلية' => $data['cabin_dimensions'], 'السقف' => $data['ceiling'], 'إضاءة الطوارئ' => $data['emergency_lighting'], 'جهاز تحريك الصاعدة' => $data['cabin_movement_device'], 'الأرضية' => $data['flooring']])) ?>
        <?= renderSection('لوحات التحكم', 'tablet-alt', renderTable(['لوحة الطلب الداخلية COP' => $data['internal_cop'], 'التشطيب الخارجي' => $data['external_panel_finishing'], 'الوقفة الرئيسية' => $data['external_main_stop'], 'الوقفات الأخرى' => $data['external_other_stops']])) ?>
        <?= renderSection('أجهزة الطوارئ والأمان', 'shield-alt', renderTable($safety_devices)) ?>

        <?php if (!empty($additions)): ?>
        <div class="card">
            <div class="card-header"><h2 class="card-title"><i class="fas fa-plus section-icon"></i>الإضافات</h2></div>
            <div class="card-body">
                <table class="modern-table">
                    <thead><tr><th>اسم الإضافة</th><th>السعر الوحدة</th><th>التأثير</th><th>طريقة الحساب</th><th>الإجمالي</th></tr></thead>
                    <tbody>
                        <?php foreach ($additions as $addition): ?>
                        <tr>
                            <td><?= htmlspecialchars($addition['name']) ?></td>
                            <td><span class="price-value"><img src="https://alfagolden.com/images/sar.png" alt="ر.س" class="sar-icon" /><?= htmlspecialchars($addition['price']) ?></span></td>
                            <td><?= htmlspecialchars($addition['effect']) ?></td>
                            <td><?= htmlspecialchars($addition['calculation']) ?></td>
                            <td><span class="price-value"><img src="https://alfagolden.com/images/sar.svg" alt="ر.س" class="sar-icon"><?= htmlspecialchars($addition['total']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($data['preparatory_work'])) echo renderSection('الأعمال التحضيرية', 'hard-hat', nl2br(htmlspecialchars($data['preparatory_work'])), true); ?>
        <?php if (!empty($data['supply_installation'])) echo renderSection('التوريد والتركيب', 'box', nl2br(htmlspecialchars($data['supply_installation'])), true); ?>

        <div class="card price-card">
            <div class="card-header"><h2 class="card-title"><i class="fas fa-dollar-sign section-icon"></i>السعر والدفعات</h2></div>
            <div class="card-body">
                <div class="price-breakdown">
                    <?php if ((float)$data['discount_amount'] > 0): ?>
                    <div class="price-row">
                        <span>مبلغ الخصم:</span>
                        <span class="price-value"><img src="https://alfagolden.com/images/sar.png" alt="ر.س" class="sar-icon" />-<?= htmlspecialchars($data['discount_amount']) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="price-row">
                        <span>السعر بدون ضريبة:</span>
                        <span class="price-value"><img src="https://alfagolden.com/images/sar.png" alt="ر.س" class="sar-icon" /><?= htmlspecialchars($data['price_before_vat']) ?></span>
                    </div>
                    <div class="price-row">
                        <span>ضريبة القيمة المضافة (15%):</span>
                        <span class="price-value"><img src="https://alfagolden.com/images/sar.png" alt="ر.س" class="sar-icon" /><?= htmlspecialchars($data['vat_amount']) ?></span>
                    </div>
                    <div class="price-row">
                        <span>السعر الإجمالي شامل الضريبة:</span>
                        <span class="price-value"><img src="https://alfagolden.com/images/sar.png" alt="ر.س" class="sar-icon" /><?= htmlspecialchars($data['total_price_with_vat']) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($data['warranty_maintenance'])) echo renderSection('الضمان والصيانة المجانية', 'tools', nl2br(htmlspecialchars($data['warranty_maintenance'])), true); ?>

        <div class="card cover-card">
            <div class="cover-top-section">
                <img src="https://alfagolden.com/images/logo.png" alt="شعار شركة ألفا الذهبية" class="cover-logo">
                <div class="final-cover-company">شركة ألفا الذهبية للمصاعد</div>
                <div class="final-cover-department">إدارة العقــــود والمبيعـــات</div>
                <img src="https://alfagolden.com/sign.png" alt="توقيع الشركة" class="final-cover-signature">
                <?php if (!empty($data['closing_sentence'])): ?>
                <div class="long-text" style="font-size: 16px; color: var(--gold); font-weight: 600; margin-top: 20px; text-align: center;"><?= nl2br(htmlspecialchars($data['closing_sentence'])) ?></div>
                <?php endif; ?>
                <a href="https://alfagolden.com/system/q/approve.php?quote_id=<?= htmlspecialchars($quote_id) ?>" class="approval-btn" target="_blank"><i class="fas fa-check-circle"></i>اضغط للموافقة على العرض</a>
            </div>
            <div class="cover-bottom-section">
                <div class="contact-info">
                    <div class="contact-section">
                        <h3>أرقام التواصل</h3>
                        <div class="contact-item"><i class="fas fa-phone contact-icon"></i> 0148400009</div>
                        <div class="contact-item"><i class="fas fa-mobile-alt contact-icon"></i> 0506086333</div>
                        <div class="contact-item"><i class="fas fa-phone contact-icon"></i> 0112522227</div>
                        <div class="contact-item"><i class="fas fa-mobile-alt contact-icon"></i> 0506023111</div>
                    </div>
                    <div class="contact-section">
                        <h3>عناوين الفروع</h3>
                        <div class="contact-item"><i class="fas fa-building contact-icon"></i> المدينة المنورة - القصواء - شارع الأمير سلطان</div>
<div class="contact-item"><i class="fas fa-building contact-icon"></i>الرياض - القيروان - شارع الملك سلمان</div>                    </div>
                </div>
                <div class="social-media">
                    <a href="https://x.com/alfagolden0" class="social-link" target="_blank"><i class="fab fa-twitter"></i></a>
                    <a href="https://www.facebook.com/alfagolden2" class="social-link" target="_blank"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://www.instagram.com/alfagolden2/" class="social-link" target="_blank"><i class="fab fa-instagram"></i></a>
                    <a href="https://www.youtube.com/@alfa.golden" class="social-link" target="_blank"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
        </div>

        <!-- أزرار الحفظ والمشاركة -->
        <div class="action-section">
            <div class="action-buttons">
                <button class="action-btn" id="saveBtn">
                    <i class="fas fa-file-pdf"></i>
                    <span id="saveBtnText">جارٍ التحضير...</span>
                </button>
                <button class="action-btn secondary" id="shareBtn">
                    <i class="fas fa-spinner"></i>
                    <span id="shareBtnText">جارٍ التحضير...</span>
                </button>
            </div>
        </div>
    </div>



    <script>
        // بيانات العرض مع الحالة المتقدمة
        const quoteData = {
            quote_number: '<?= htmlspecialchars($data['quote_number']) ?>',
            client_name: '<?= htmlspecialchars($data['client_name']) ?>',
            title_before: '<?= htmlspecialchars($data['title_before']) ?>',
            quote_id: '<?= $quote_id ?>',
            pdf_status: '<?= $pdf_status['status'] ?>',
            pdf_url: '<?= $pdf_status['url'] ?? '' ?>',
            auto_generate: <?= $pdf_status['auto_generate'] ? 'true' : 'false' ?>,
            clear_old_url: <?= isset($pdf_status['clear_old_url']) && $pdf_status['clear_old_url'] ? 'true' : 'false' ?>,
            reason: '<?= $pdf_status['reason'] ?? '' ?>'
        };

        const saveBtn = document.getElementById('saveBtn');
        const saveBtnText = document.getElementById('saveBtnText');
        const shareBtn = document.getElementById('shareBtn');
        const shareBtnText = document.getElementById('shareBtnText');

        // تحديث حالة الأزرار
        function updateButtonState(btn, textEl, status, text, icon = 'fa-download') {
            const btnIcon = btn.querySelector('i');
            btnIcon.className = `fas ${icon}`;
            textEl.textContent = text;
            
            btn.className = 'action-btn';
            if (status === 'loading') {
                btn.classList.add('loading');
            } else if (btn === shareBtn) {
                btn.classList.add('secondary');
            }
        }

        // تحديث كلا الزرين معاً
        function updateBothButtons(status, saveText, shareText, saveIcon = 'fa-download', shareIcon = 'fa-share-alt') {
            updateButtonState(saveBtn, saveBtnText, status, saveText, saveIcon);
            updateButtonState(shareBtn, shareBtnText, status, shareText, shareIcon);
        }

        // بدء العملية التلقائية عند تحميل الصفحة
        window.addEventListener('load', async function() {
            if (quoteData.auto_generate) {
                await generatePdf(true); // auto = true
            } else {
                // الملف جاهز
                updateBothButtons('ready', 'تحميل ملف PDF', 'مشاركة الملف');
            }
        });

        // زر الحفظ
        saveBtn.addEventListener('click', async () => {
            if (quoteData.pdf_status === 'ready' && quoteData.pdf_url) {
                downloadFile(quoteData.pdf_url);
            } else {
                await generatePdf(false);
            }
        });

        // زر المشاركة
        shareBtn.addEventListener('click', async () => {
            if (quoteData.pdf_status === 'ready' && quoteData.pdf_url) {
                await sharePdfFile();
            } else {
                await generatePdf(false);
            }
        });

        // إنشاء ملف PDF
        async function generatePdf(isAuto = false) {
            updateBothButtons('loading', 'جارٍ الإنشاء...', 'جارٍ الإنشاء...', 'fa-spinner', 'fa-spinner');

            try {
                const formData = new FormData();
                formData.append('action', 'generate_pdf');
                if (quoteData.clear_old_url) {
                    formData.append('clear_old', 'true');
                }

                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    quoteData.pdf_status = result.status;
                    quoteData.pdf_url = result.url;
                    
                    if (result.status === 'ready' && result.url) {
                        updateBothButtons('ready', 'تحميل ملف PDF', 'مشاركة الملف');
                    } else {
                        updateBothButtons('loading', 'جارٍ المعالجة...', 'جارٍ المعالجة...', 'fa-spinner', 'fa-spinner');
                        checkStatus();
                    }
                } else {
                    throw new Error(result.message);
                }

            } catch (error) {
                console.error('حدث خطأ:', error);
                updateBothButtons('ready', 'إعادة المحاولة', 'إعادة المحاولة', 'fa-redo', 'fa-redo');
            }
        }

        // فحص الحالة دورياً
        async function checkStatus() {
            try {
                const formData = new FormData();
                formData.append('action', 'check_status');

                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.status === 'ready' && result.url) {
                    quoteData.pdf_status = result.status;
                    quoteData.pdf_url = result.url;
                    updateBothButtons('ready', 'تحميل ملف PDF', 'مشاركة الملف');
                } else {
                    setTimeout(checkStatus, 3000);
                }

            } catch (error) {
                console.error('خطأ في فحص الحالة:', error);
                updateBothButtons('ready', 'إعادة المحاولة', 'إعادة المحاولة', 'fa-redo', 'fa-redo');
            }
        }

        // تحميل الملف
        function downloadFile(url) {
            try {
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = url;
                a.download = getFileName();
                
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            } catch (error) {
                console.error('خطأ في التحميل:', error);
            }
        }

        // مشاركة الملف
        async function sharePdfFile() {
            if (quoteData.pdf_status !== 'ready' || !quoteData.pdf_url) {
                return;
            }

            try {
                if (navigator.share && navigator.canShare) {
                    const response = await fetch(quoteData.pdf_url);
                    const blob = await response.blob();
                    const file = new File([blob], getFileName(), { type: 'application/pdf' });

                    if (navigator.canShare({ files: [file] })) {
                        await navigator.share({
                            title: `عرض سعر #${quoteData.quote_number} - ${quoteData.client_name}`,
                            text: 'عرض سعر من شركة ألفا الذهبية للمصاعد',
                            files: [file]
                        });
                        return;
                    }
                }

                await navigator.clipboard.writeText(quoteData.pdf_url);
                updateButtonState(shareBtn, shareBtnText, 'ready', 'تم نسخ الرابط', 'fa-check');
                
                setTimeout(() => {
                    updateButtonState(shareBtn, shareBtnText, 'ready', 'مشاركة الملف', 'fa-share-alt');
                }, 2000);

            } catch (error) {
                console.error('خطأ في المشاركة:', error);
                downloadFile(quoteData.pdf_url);
            }
        }

        // إنشاء اسم الملف
        function getFileName() {
            const beforeName = quoteData.title_before.replace(/[/\\:*?"<>|]/g, '');
            const clientName = quoteData.client_name.replace(/[/\\:*?"<>|]/g, '');
            return `عرض سعر (${beforeName} ${clientName}) #${quoteData.quote_number}.pdf`;
        }
    </script>
</body>
</html>