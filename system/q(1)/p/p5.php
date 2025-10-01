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

$quote_data = fetchQuoteData($quote_id, $baserow_url, $table_id, $token);

if (!$quote_data) {
    die("لا يمكن الحصول على بيانات العرض. تأكد من صحة رقم العرض.");
}

function extractValue($field, $default = '') {
    if (is_array($field) && !empty($field) && isset($field[0]['value'])) {
        return is_array($field[0]['value']) ? ($field[0]['value']['value'] ?? $default) : $field[0]['value'];
    }
    return $field ?: $default;
}

function formatGregorianDate($dateStr) {
    if (!$dateStr) return '';
    $date = new DateTime($dateStr);
    $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    return $date->format('j') . ' ' . $months[(int)$date->format('n') - 1] . ' ' . $date->format('Y');
}

$data = [
    'quote_number' => $quote_data['الرقم التست'] ?? '',
    'date' => $quote_data['تاريخ'] ?? '',
    'electrical_communication' => $quote_data['أجهزة الطوارئ والأمان - أجهزة الاتصال الكهربائية'] ?? '',
    'emergency_devices' => $quote_data['أجهزة الطوارئ والأمان - أجهزة الطوارئ'] ?? '',
    'lighting_devices' => $quote_data['أجهزة الطوارئ والأمان - أجهزة الانارة'] ?? '',
    'safety_devices' => $quote_data['أجهزة الطوارئ والأمان - أجهزة الأمان'] ?? '',
    'light_curtain' => $quote_data['أجهزة الطوارئ والأمان - ستارة ضوئية'] ?? '',
    'speed_regulator' => $quote_data['أجهزة الطوارئ والأمان - جهاز منظم السرعة'] ?? '',
    'shock_absorbers' => $quote_data['أجهزة الطوارئ والأمان - مخففات الصدمات'] ?? '',
    'limit_switch' => $quote_data['أجهزة الطوارئ والأمان - جهاز نهاية المشوار'] ?? '',
    'door_lock_cam' => $quote_data['أجهزة الطوارئ والأمان - كامة تأمين فتح الباب'] ?? '',
    'elevator_lubricators' => $quote_data['أجهزة الطوارئ والأمان - مزايت الصاعدة'] ?? '',
    'external_door_key' => $quote_data['أجهزة الطوارئ والأمان - مفتاح الباب الخارجى'] ?? '',
    'electrical_connections' => $quote_data['أجهزة الطوارئ والأمان - التوصيلات الكهربائية'] ?? '',
    'preparatory_work' => $quote_data['الأعمال التحضيرية'] ?? ''
];

$formattedDate = formatGregorianDate($data['date']);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>أجهزة الطوارئ والأمان - عرض سعر رقم <?= htmlspecialchars($data['quote_number']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gold: #9c7d2d;
            --secondary-gold: #c8a132;
            --light-gray: #f8f9fa;
            --dark-gold: #7a632a;
            --text-dark: #2c2c2c;
            --text-medium: #555;
            --text-light: #777;
            --white: #ffffff;
            --border-light: #e0e0e0;
            --shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Cairo', sans-serif;
            line-height: 1.5;
            color: var(--text-dark);
            background: var(--white);
            direction: rtl;
            font-size: 13px;
        }

        @media print {
            @page {
                size: A4;
                margin: 15mm;
            }
            
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }

        .page {
            width: 210mm;
            height: 297mm;
            margin: 0 auto;
            background: var(--white);
            position: relative;
            padding: 15mm;
            border: 1px solid var(--border-light);
            box-shadow: var(--shadow);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8mm;
            padding-bottom: 3mm;
            border-bottom: 2px solid var(--primary-gold);
        }

        .header-info {
            text-align: right;
            font-size: 12px;
            color: var(--text-medium);
            line-height: 1.4;
            font-weight: 500;
        }

        .logo {
            width: 120px;
            height: auto;
        }

        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--white);
            margin: 8mm 0 4mm;
            padding: 4mm 8mm;
            background: var(--primary-gold);
            border-radius: 4px;
            text-align: center;
        }

        .long-text {
            line-height: 1.6;
            text-align: justify;
            margin: 5mm 0;
            font-size: 13px;
            color: var(--text-dark);
            background: var(--light-gray);
            padding: 5mm;
            border-radius: 4px;
            border-right: 3mm solid var(--primary-gold);
        }

        .specs-table {
            width: 100%;
            border-collapse: collapse;
            margin: 5mm 0;
            background: var(--white);
            border-radius: 4px;
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .specs-table td {
            padding: 4mm;
            text-align: right;
            border-bottom: 1px solid var(--border-light);
            vertical-align: middle;
            font-size: 12px;
        }

        .specs-table .spec-label {
            background: var(--light-gray);
            font-weight: 600;
            color: var(--text-dark);
            width: 35%;
            border-right: 2px solid var(--primary-gold);
        }

        .specs-table .spec-value {
            background: var(--white);
            color: var(--text-dark);
            line-height: 1.4;
            font-weight: 500;
        }

        .specs-table tr:last-child td {
            border-bottom: none;
        }

        .page-number {
            position: absolute;
            bottom: 3mm;
            right: 3mm;
            font-size: 11px;
            color: var(--white);
            font-weight: 600;
            background: var(--primary-gold);
            padding: 3mm 6mm;
            border-radius: 15px;
            box-shadow: var(--shadow);
        }

        @media screen and (max-width: 768px) {
            .page {
                width: 100%;
                margin: 0;
                border: none;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>

<div class="page">
    <div class="header">
        <div class="header-info">
            <div><strong>شركة ألفا الذهبية للمصاعد</strong></div>
            <div>التاريخ: <?= $formattedDate ?></div>
            <div>رقم العرض: <?= htmlspecialchars($data['quote_number']) ?></div>
        </div>
        <img src="https://alfagolden.com/images/logo.png" alt="شعار شركة ألفا الذهبية" class="logo">
    </div>

    <div class="section-title">أجهزة الطوارئ والأمان</div>
    <table class="specs-table">
        <tr>
            <td class="spec-label">أجهزة الاتصال الكهربائية</td>
            <td class="spec-value"><?= htmlspecialchars($data['electrical_communication']) ?></td>
        </tr>
        <tr>
            <td class="spec-label">أجهزة الطوارئ</td>
            <td class="spec-value"><?= htmlspecialchars($data['emergency_devices']) ?></td>
        </tr>
        <tr>
            <td class="spec-label">أجهزة الإنارة</td>
            <td class="spec-value"><?= htmlspecialchars($data['lighting_devices']) ?></td>
        </tr>
        <tr>
            <td class="spec-label">أجهزة الأمان</td>
            <td class="spec-value"><?= htmlspecialchars($data['safety_devices']) ?></td>
        </tr>
        <tr>
            <td class="spec-label">ستارة ضوئية</td>
            <td class="spec-value"><?= htmlspecialchars($data['light_curtain']) ?></td>
        </tr>
        <tr>
            <td class="spec-label">جهاز منظم السرعة</td>
            <td class="spec-value"><?= htmlspecialchars($data['speed_regulator']) ?></td>
        </tr>
        <tr>
            <td class="spec-label">مخففات الصدمات</td>
            <td class="spec-value"><?= htmlspecialchars($data['shock_absorbers']) ?></td>
        </tr>
        <tr>
            <td class="spec-label">جهاز نهاية المشوار</td>
            <td class="spec-value"><?= htmlspecialchars($data['limit_switch']) ?></td>
        </tr>
        <tr>
            <td class="spec-label">كامة تأمين فتح الباب</td>
            <td class="spec-value"><?= htmlspecialchars($data['door_lock_cam']) ?></td>
        </tr>
        <tr>
            <td class="spec-label">مزايت الصاعدة</td>
            <td class="spec-value"><?= htmlspecialchars($data['elevator_lubricators']) ?></td>
        </tr>
        <tr>
            <td class="spec-label">مفتاح الباب الخارجي</td>
            <td class="spec-value"><?= htmlspecialchars($data['external_door_key']) ?></td>
        </tr>
        <tr>
            <td class="spec-label">التوصيلات الكهربائية</td>
            <td class="spec-value"><?= htmlspecialchars($data['electrical_connections']) ?></td>
        </tr>
    </table>

    <?php if (!empty($data['preparatory_work'])): ?>
    <div class="section-title">الأعمال التحضيرية</div>
    <div class="long-text">
        <?= nl2br(htmlspecialchars($data['preparatory_work'])) ?>
    </div>
    <?php endif; ?>

    <div class="page-number">5</div>
</div>

</body>
</html>