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

$quote_data = fetchQuoteData($quote_id, $baserow_url, $table_id, $token);
if (!$quote_data) die("لا يمكن الحصول على بيانات العرض. تأكد من صحة رقم العرض.");

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
            --border-color: #e5e7eb;
            --radius-small: 4mm; --radius-medium: 6mm; --radius-large: 8mm; --radius-xlarge: 12mm;
            --shadow-safe: 0 1mm 3mm rgba(151, 126, 43, 0.15);
            --shadow-safe-light: 0 0.5mm 2mm rgba(151, 126, 43, 0.1);
        }
        @page {
            size: A4; margin: 0; -webkit-print-color-adjust: exact; color-adjust: exact; print-color-adjust: exact;
        }
        * {
            box-sizing: border-box; -webkit-print-color-adjust: exact !important; color-adjust: exact !important; print-color-adjust: exact !important;
        }
body {
    font-family: arial;
    background: var(--white) !important;
    margin: 0;
    padding: 0;
    direction: rtl;
    font-size: 10pt;
    line-height: 1.4;
    color: var(--dark-gray);
}
        .page-container {
            width: 210mm; height: 297mm; position: relative; page-break-after: always;
            overflow: hidden; display: flex; flex-direction: column; background: var(--white);
        }
        .page-container:last-child { page-break-after: auto; }

        /* ===== أغلفة الصفحات (مشترك) ===== */
        .cover-page { position: relative; overflow: hidden; }
        .cover-top-section { flex: 1; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 25mm 20mm; background: var(--white); position: relative; z-index: 3; text-align: center; }
        .cover-bottom-section { flex: 1; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 25mm 20mm; position: relative; z-index: 2; text-align: center; background: var(--gold); color: var(--white); border-top-left-radius: var(--radius-xlarge); border-top-right-radius: var(--radius-xlarge); clip-path: inset(0 0 0 0 round var(--radius-xlarge) var(--radius-xlarge) 0 0); }
        .cover-bottom-section::before { content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background-image: url('https://alfagolden.com/q.jpg'); background-size: cover; background-position: center bottom; background-repeat: no-repeat; z-index: 0; }
        .cover-bottom-section::after { content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(to bottom, rgba(151, 126, 43, 0.9) 0%, rgba(151, 126, 43, 0.7) 50%, rgba(151, 126, 43, 0.4) 100%); z-index: 1; }
        .cover-bottom-section > * { position: relative; z-index: 2; }
        .cover-logo { width: 80mm; margin-bottom: 15mm; background: var(--white); padding: 8mm; border-radius: var(--radius-xlarge); box-shadow: var(--shadow-safe); }
        .cover-meta-info { position: absolute; top: 8mm; right: 8mm; font-size: 9pt; color: var(--medium-gray); text-align: right; background: rgba(255,255,255,0.95); padding: 4mm; border-radius: var(--radius-medium); box-shadow: var(--shadow-safe-light); z-index: 4; line-height: 1.5; }
        .cover-quote-title { margin-bottom: 12mm; }
        .cover-quote-title h1 { font-size: 22pt; font-weight: 700; color: var(--white); margin-bottom: 2mm; filter: drop-shadow(0 2mm 4mm rgba(0,0,0,0.3)); }
        .cover-quote-title h2 { font-size: 16pt; font-weight: 600; color: var(--white); filter: drop-shadow(0 2mm 4mm rgba(0,0,0,0.3)); }
        .cover-client-info { margin-bottom: 12mm; font-size: 14pt; font-weight: 600; color: var(--white); filter: drop-shadow(0 2mm 4mm rgba(0,0,0,0.3)); }
        .cover-client-info .title-parts { color: rgba(255,255,255,0.9); margin: 0 3mm; }
        .cover-client-info .client-name { font-weight: 700; }
        .cover-project-info { font-size: 10pt; color: rgba(255,255,255,0.9); line-height: 1.6; margin-bottom: 10mm; }
        .cover-specs { display: flex; justify-content: center; gap: 3mm; font-size: 8pt; color: rgba(255,255,255,0.9); background: rgba(0,0,0,0.1); padding: 3mm; border-radius: var(--radius-medium); flex-wrap: wrap; }
        
        /* ===== صفحات المحتوى ===== */
        .content-header { height: 15mm; width: 100%; background: linear-gradient(90deg, var(--gold) 0%, var(--gold-hover) 100%); display: flex; align-items: center; justify-content: space-between; padding: 0 8mm; position: relative; z-index: 10; border-bottom-left-radius: var(--radius-large); border-bottom-right-radius: var(--radius-large); }
        .header-info { display: flex; flex-direction: column; color: #fff; font-size: 9pt; font-weight: 600; }
        .header-logo { height: 11mm; width: auto; }
        .page-content { flex: 1; padding: 3mm 8mm 20mm 8mm; overflow: hidden; }
        .card { background: var(--white); border-radius: var(--radius-medium); box-shadow: var(--shadow-safe-light); border: 0.2mm solid var(--border-color); margin-bottom: 2.5mm; page-break-inside: avoid; overflow: hidden; }
        .card-header { padding: 2mm 3mm; border-bottom: 0.2mm solid var(--border-color); background: var(--light-gray); }
        .card-body { padding: 2.5mm; }
        .card-title { font-size: 10pt; font-weight: 600; margin: 0; display: flex; align-items: center; gap: 2mm; color: var(--dark-gray); }
        .section-icon { width: 4.5mm; height: 4.5mm; background: var(--gold); border-radius: var(--radius-small); display: inline-flex; align-items: center; justify-content: center; color: var(--white); font-size: 2.2mm; margin-left: 2mm; flex-shrink: 0; }
        .long-text { line-height: 1.7; text-align: justify; font-size: 7.5pt; word-wrap: break-word; }
        .modern-table { width: 100%; border-collapse: collapse; border-radius: var(--radius-small); overflow: hidden; border: 0.2mm solid var(--border-color); margin: 2mm 0; }
        .modern-table td, .modern-table th { padding: 2mm 2.5mm; text-align: right; border-bottom: 0.2mm solid var(--border-color); vertical-align: top; font-size: 7.5pt;}
        .modern-table th { font-weight: 600; font-size: 7pt; }
        .modern-table tbody tr:last-child td { border-bottom: none; }
        .table-label { background: var(--light-gray); font-weight: 600; width: 40%; color: var(--medium-gray); }

        /* ===== قسم السعر (التصميم الأصلي) ===== */
        .price-card { border: 0.5mm solid var(--gold); background: var(--white); border-radius: var(--radius-medium); }
        .price-row { display: flex; justify-content: space-between; align-items: center; padding: 2.5mm 0; font-size: 8pt; border-bottom: 0.2mm solid var(--border-color); }
        .price-row:last-child { border-bottom: none; margin-top: 2.5mm; padding-top: 3.5mm; border-top: 0.5mm solid var(--gold); font-weight: 700; font-size: 9pt; color: var(--gold); }
        .price-value { font-weight: 600; color: var(--gold); display: flex; align-items: center; justify-content: flex-end; gap: 1.5mm; direction: ltr; }
        .sar-icon { width: 3mm !important; height: 3mm !important; object-fit: contain; flex-shrink: 0; }

        /* ===== ترقيم الصفحات ===== */
        .page-number { position: absolute; bottom: 0mm; left: 50%; transform: translateX(-50%); width: 28mm; height: 7mm; background: var(--gold); color: var(--white); display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 9pt; border-top-left-radius: var(--radius-small); border-top-right-radius: var(--radius-small); z-index: 100; box-shadow: var(--shadow-safe-light); }
        .cover-page .page-number { background: var(--white); color: var(--gold); }
        
        /* ====== تعديلات الغلاف الأخير ====== */
        .final-cover-page .cover-top-section {
            padding-bottom: 30mm;
        }
        .final-cover-page .approval-btn-wrapper {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 9999;
            text-align: center;
        }
        .final-cover-page .approval-btn {
            display: inline-block;
            width: 70mm;
            padding: 4mm 6mm;
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-hover) 100%);
            color: var(--white) !important;
            text-decoration: none !important;
            border-radius: var(--radius-medium);
            font-size: 10pt;
            font-weight: 600;
            border: 1px solid rgba(151, 126, 43, 0.5);
            line-height: 1.3;
        }
        .final-cover-page .approval-btn i {
            margin-left: 2mm;
            vertical-align: middle;
        }
        .final-cover-page .final-cover-company { font-size: 14pt; font-weight: 700; margin-bottom: 6mm; color: var(--gold); }
        .final-cover-page .final-cover-department { font-size: 12pt; font-weight: 600; margin-bottom: 6mm; color: var(--dark-gray); }
        .final-cover-page .final-cover-signature { width: 45mm; }
        .final-cover-page .closing-sentence { font-size: 10pt; color: var(--gold); font-weight: 600; line-height: 1.6; margin-top: 8mm; }
        
        .final-cover-page .contact-info { display: grid; grid-template-columns: 1fr 1fr; gap: 8mm; margin-bottom: 6mm; text-align: right; width: 100%; }
        .final-cover-page .contact-section h3 { font-size: 11pt; margin-bottom: 4mm; color: var(--white); }
        .final-cover-page .contact-item { display: flex; align-items: center; gap: 2mm; margin-bottom: 2mm; font-size: 9pt; }
        .final-cover-page .contact-icon { width: 4mm; height: 4mm; background: rgba(255, 255, 255, 0.2); border-radius: var(--radius-small); display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: var(--white); }
        .final-cover-page .social-media { display: flex; justify-content: center; gap: 4mm; direction: ltr; }
        .final-cover-page .social-link { width: 9mm; height: 9mm; background: rgba(255, 255, 255, 0.2); border-radius: var(--radius-small); display: flex; align-items: center; justify-content: center; color: var(--white); text-decoration: none; }
    </style>
</head>
<body>
    <!-- الغلاف الأول -->
    <div class="page-container cover-page">
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
                <span>عدد المصاعد: <?= htmlspecialchars($data['elevators_count']) ?></span>&nbsp;|&nbsp;
                <span>عدد الوقفات: <?= htmlspecialchars($data['stops_count']) ?></span>&nbsp;|&nbsp;
                <span>الحمولة: <?= htmlspecialchars($data['capacity']) ?> kg</span>
            </div>
        </div>
        <div class="page-number">1</div>
    </div>

    <!-- صفحة المحتوى 2 -->
    <div class="page-container">
        <div class="content-header">
            <div class="header-info"><span>رقم العرض: <?= htmlspecialchars($data['quote_number']) ?></span><span>التاريخ: <?= $formattedDate ?></span></div>
            <img src="https://alfagolden.com/llogo.png" alt="شعار ألفا الذهبية" class="header-logo" />
        </div>
        <div class="page-content">
            <?php if (!empty($data['opening_text'])): ?>
            <div class="card">
                <div class="card-body" style="text-align:center;">
                    <div style="font-weight: 600; font-size: 9pt; margin-bottom: 3mm; color: var(--gold); line-height: 1.7;"><?= nl2br(htmlspecialchars($data['opening_text'])) ?></div>
                    <div style="display: flex; justify-content: space-between; align-items: center; color: var(--gold); font-weight: 600; font-size: 9pt;">
                        <span style="color: var(--dark-gray);"><?= htmlspecialchars($data['title_before']) ?> /</span>
                        <span style="color: var(--gold); font-weight: 700;"><?= htmlspecialchars($data['client_name']) ?></span>
                        <span style="color: var(--dark-gray);"><?= htmlspecialchars($data['title_after']) ?></span>
                    </div>
                    <?php if (!empty($data['introductory_sentence'])): ?>
                    <div class="long-text" style="margin-top: 3mm;text-align:center;"><?= nl2br(htmlspecialchars($data['introductory_sentence'])) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="card section-card">
                <div class="card-header"><h2 class="card-title"><i class="fas fa-clipboard-list section-icon"></i>المواصفات العامة</h2></div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(45mm, 1fr)); gap: 2mm; margin-bottom: 2.5mm;">
                        <?php
                        $specs = [
                            ['label' => 'نوع المصعد', 'value' => 'MRL'], ['label' => 'نوع المبنى', 'value' => 'مبنى سكني'],
                            ['label' => 'عدد المصاعد', 'value' => $data['elevators_count']], ['label' => 'السرعة', 'value' => '1.0 m/s'],
                            ['label' => 'وضع الماكينة', 'value' => $data['machine_position']], ['label' => 'عدد الوقفات', 'value' => $data['stops_count']],
                            ['label' => 'الحمولة', 'value' => $data['capacity'] . ' kg'], ['label' => 'عدد الأشخاص', 'value' => $data['people_count']],
                            ['label' => 'عدد جهات الدخول', 'value' => $data['entrance_count']], ['label' => 'البراند', 'value' => $data['brand']]
                        ];
                        foreach ($specs as $spec): ?>
                        <div style="border: 0.2mm solid var(--border-color); border-radius: var(--radius-small); overflow: hidden; background: var(--white);"><div style="background: var(--light-gray); padding: 1.8mm 2.2mm; font-weight: 600; font-size: 7pt; text-align: center; border-bottom: 0.2mm solid var(--border-color); color: var(--medium-gray);"><?= $spec['label'] ?></div><div style="padding: 2.5mm; font-size: 8pt; min-height: 10mm; display: flex; align-items: center; justify-content: center; text-align: center; font-weight: 500;"><?= htmlspecialchars($spec['value']) ?></div></div>
                        <?php endforeach; ?>
                    </div>
                    <?= renderTable(['طريقة التشغيل' => $data['operation_method'], 'مسميات الوقفات' => $data['stops_names'], 'التيار الكهربائي' => $data['electrical_current']]) ?>
                </div>
            </div>
            <?php if (!empty($data['machine_type'])): echo renderSection('نوع الماكينة', 'cog', nl2br(htmlspecialchars($data['machine_type'])), true); endif; ?>
        </div>
        <div class="page-number">2</div>
    </div>

    <!-- صفحة 3 -->
    <div class="page-container">
        <div class="content-header"><div class="header-info"><span>رقم العرض: <?= htmlspecialchars($data['quote_number']) ?></span><span>التاريخ: <?= $formattedDate ?></span></div><img src="https://alfagolden.com/llogo.png" alt="شعار ألفا الذهبية" class="header-logo" /></div>
        <div class="page-content">
            <?php if (!empty($data['control_device'])): echo renderSection('جهاز تشغيل المصعد', 'sliders-h', nl2br(htmlspecialchars($data['control_device'])), true); endif; ?>
            <?= renderSection('البئر', 'circle', renderTable(['مبني من' => $data['well_material'], 'المقاس الداخلي' => $data['well_dimensions']])) ?>
            <?= renderSection('الأبواب الخارجية والداخلية', 'door-open', renderTable(['طريقة التشغيل' => $data['door_operation_method'], 'التشطيب' => $data['cabin_finishing'], 'مقاساتها' => $data['door_dimensions'], 'الباب الداخلي' => $data['internal_door']])) ?>
            <?= renderSection('الدلائل والتعليق', 'link', renderTable(['سكك الصاعدة' => $data['elevator_rails'], 'سكك ثقل الموازنة' => $data['counterweight_rails'], 'حبال الجر' => $data['traction_ropes'], 'الكابل المرن' => $data['flexible_cable'], 'الإطار الحامل للصاعدة' => $data['carrier_frame']])) ?>
        </div>
        <div class="page-number">3</div>
    </div>

    <!-- صفحة 4 -->
    <div class="page-container">
        <div class="content-header"><div class="header-info"><span>رقم العرض: <?= htmlspecialchars($data['quote_number']) ?></span><span>التاريخ: <?= $formattedDate ?></span></div><img src="https://alfagolden.com/llogo.png" alt="شعار ألفا الذهبية" class="header-logo" /></div>
        <div class="page-content">
            <?= renderSection('الصاعدة', 'home', renderTable(['التشطيب' => $data['cabin_finishing'], 'المقاسات الداخلية' => $data['cabin_dimensions'], 'السقف' => $data['ceiling'], 'إضاءة الطوارئ' => $data['emergency_lighting'], 'جهاز تحريك الصاعدة' => $data['cabin_movement_device'], 'الأرضية' => $data['flooring']])) ?>
            <?= renderSection('لوحات التحكم', 'tablet-alt', renderTable(['لوحة الطلب الداخلية COP' => $data['internal_cop'], 'التشطيب الخارجي' => $data['external_panel_finishing'], 'الوقفة الرئيسية' => $data['external_main_stop'], 'الوقفات الأخرى' => $data['external_other_stops']])) ?>
            <?= renderSection('أجهزة الطوارئ والأمان', 'shield-alt', renderTable($safety_devices)) ?>
        </div>
        <div class="page-number">4</div>
    </div>

    <!-- صفحة 5 -->
    <div class="page-container">
        <div class="content-header"><div class="header-info"><span>رقم العرض: <?= htmlspecialchars($data['quote_number']) ?></span><span>التاريخ: <?= $formattedDate ?></span></div><img src="https://alfagolden.com/llogo.png" alt="شعار ألفا الذهبية" class="header-logo" /></div>
        <div class="page-content">
            <?php if (!empty($data['preparatory_work'])): echo renderSection('الأعمال التحضيرية', 'hard-hat', nl2br(htmlspecialchars($data['preparatory_work'])), true); endif; ?>
            <?php if (!empty($additions)): ?>
            <div class="card section-card">
                <div class="card-header"><h2 class="card-title"><i class="fas fa-plus section-icon"></i>الإضافات</h2></div>
                <div class="card-body">
                    <table class="modern-table"><thead><tr><th>اسم الإضافة</th><th>السعر الوحدة</th><th>التأثير</th><th>طريقة الحساب</th><th>الإجمالي</th></tr></thead>
                        <tbody>
                            <?php foreach ($additions as $addition): ?>
                            <tr>
                                <td><?= htmlspecialchars($addition['name']) ?></td>
                                <td><span class="price-value"><img src="https://alfagolden.com/images/sar.png" alt="ر.س" class="sar-icon" /><?= htmlspecialchars($addition['price']) ?></span></td>
                                <td><?= htmlspecialchars($addition['effect']) ?></td>
                                <td><?= htmlspecialchars($addition['calculation']) ?></td>
                                <td><span class="price-value"><img src="https://alfagolden.com/images/sar.png" alt="ر.س" class="sar-icon" /><?= htmlspecialchars($addition['total']) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
            <div class="card price-card section-card">
                <div class="card-header"><h2 class="card-title"><i class="fas fa-dollar-sign section-icon"></i>السعر والدفعات</h2></div>
                <div class="card-body">
                    <?php if ((float)str_replace([' ', ','], '', $data['discount_amount']) > 0): ?>
                    <div class="price-row"><span>مبلغ الخصم:</span><span class="price-value"><img src="https://alfagolden.com/images/sar.png" alt="ر.س" class="sar-icon" />-<?= htmlspecialchars($data['discount_amount']) ?></span></div>
                    <?php endif; ?>
                    <div class="price-row"><span>السعر بدون ضريبة:</span><span class="price-value"><img src="https://alfagolden.com/images/sar.png" alt="ر.س" class="sar-icon" /><?= htmlspecialchars($data['price_before_vat']) ?></span></div>
                    <div class="price-row"><span>ضريبة القيمة المضافة (15%):</span><span class="price-value"><img src="https://alfagolden.com/images/sar.png" alt="ر.س" class="sar-icon" /><?= htmlspecialchars($data['vat_amount']) ?></span></div>
                    <div class="price-row"><span>السعر الإجمالي شامل الضريبة:</span><span class="price-value"><img src="https://alfagolden.com/images/sar.png" alt="ر.س" class="sar-icon" /><?= htmlspecialchars($data['total_price_with_vat']) ?></span></div>
                </div>
            </div>
            <?php if (!empty($data['supply_installation'])): echo renderSection('التوريد والتركيب', 'box', nl2br(htmlspecialchars($data['supply_installation'])), true); endif; ?>
            <?php if (!empty($data['warranty_maintenance'])): echo renderSection('الضمان والصيانة المجانية', 'tools', nl2br(htmlspecialchars($data['warranty_maintenance'])), true); endif; ?>
        </div>
        <div class="page-number">5</div>
    </div>

    <!-- الغلاف الأخير (بنية جديدة ومضمونة) -->
    <div class="page-container cover-page final-cover-page">
        <!-- الجزء العلوي (أبيض) -->
        <div class="cover-top-section">
            <img src="https://alfagolden.com/images/logo.png" alt="شعار شركة ألفا الذهبية" class="cover-logo">
            <div class="final-cover-company">شركة ألفا الذهبية للمصاعد</div>
            <div class="final-cover-department">إدارة العقــــود والمبيعـــات</div>
            <img src="https://alfagolden.com/sign.png" alt="توقيع الشركة" class="final-cover-signature">
            <?php if (!empty($data['closing_sentence'])): ?>
                <div class="closing-sentence">
                    <?= nl2br(htmlspecialchars($data['closing_sentence'])) ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- الجزء السفلي (صورة) -->
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
                    <div class="contact-item"><i class="fas fa-building contact-icon"></i> الرياض - القيروان - شارع الملك سلمان</div>
                </div>
            </div>
            <div class="social-media">
                <a href="https://x.com/alfagolden0" class="social-link" target="_blank"><i class="fab fa-twitter"></i></a>
                <a href="https://www.facebook.com/alfagolden2" class="social-link" target="_blank"><i class="fab fa-facebook-f"></i></a>
                <a href="https://www.instagram.com/alfagolden2/" class="social-link" target="_blank"><i class="fab fa-instagram"></i></a>
                <a href="https://www.youtube.com/@alfa.golden" class="social-link" target="_blank"><i class="fab fa-youtube"></i></a>
            </div>
        </div>

        <!-- طبقة زر الموافقة (فوق كل شيء) -->
        <div class="approval-btn-wrapper">
             <a href="https://alfagolden.com/system/q/approve.php?quote_id=<?= htmlspecialchars($quote_id) ?>" 
               class="approval-btn" target="_blank">
                <i class="fas fa-check-circle"></i>
                اضغط للموافقة على العرض
            </a>
        </div>

        <div class="page-number">6</div>
    </div>
</body>
</html>