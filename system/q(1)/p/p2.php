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
    'title_before' => extractValue($quote_data['قبل الاسم']),
    'client_name' => extractValue($quote_data['اسم العميل']),
    'title_after' => extractValue($quote_data['بعد الاسم']),
    'opening_text' => $quote_data['جملة البداية'] ?? '',
    'introductory_sentence' => $quote_data['الجملة التمهيدية'] ?? '',
    'elevators_count' => $quote_data['عدد المصاعد'] ?? '',
    'stops_count' => extractValue($quote_data['عدد الوقفات']),
    'capacity' => extractValue($quote_data['الحمولة']),
    'people_count' => extractValue($quote_data['عدد الاشخاص']),
    'entrance_count' => $quote_data['عدد جهات الدخول'] ?? '',
    'machine_position' => extractValue($quote_data['وضع الماكينة']),
    'operation_method' => $quote_data['طريقة التشغيل'] ?? '',
    'stops_names' => $quote_data['مسميات الوقفات'] ?? '',
    'electrical_current' => $quote_data['التيار الكهربائي'] ?? '',
    'brand' => extractValue($quote_data['البراند']),
    'machine_brand' => extractValue($quote_data['الماكينة'])
];

$formattedDate = formatGregorianDate($data['date']);

$introductoryFontSize = 14;
if (!empty($data['introductory_sentence'])) {
    $textLength = mb_strlen($data['introductory_sentence'], 'UTF-8');
    if ($textLength > 180) {
        $introductoryFontSize = 13;
    } elseif ($textLength > 250) {
        $introductoryFontSize = 12;
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المواصفات العامة - عرض سعر رقم <?= htmlspecialchars($data['quote_number']) ?></title>
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

        .content-section {
            margin-bottom: 6mm;
        }

        .opening-text {
            font-size: 14px;
            line-height: 1.6;
            color: var(--text-dark);
            margin-bottom: 5mm;
            text-align: center;
            padding: 5mm;
            background: var(--light-gray);
            border-radius: 4px;
            border-right: 3mm solid var(--primary-gold);
        }

        .client-name-section {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 5mm;
            text-align: center;
            padding: 4mm;
            background: rgba(156, 125, 45, 0.1);
            border-radius: 4px;
        }

        .client-name-section .title-before {
            color: var(--primary-gold);
            margin-left: 3mm;
        }

        .client-name-section .client-name {
            color: var(--text-dark);
        }

        .introductory-text {
            font-size: <?= $introductoryFontSize ?>px;
            line-height: 1.5;
            color: var(--text-dark);
            text-align: center;
            background: rgba(156, 125, 45, 0.1);
            padding: 5mm;
            border-radius: 4px;
            margin: 5mm 0;
            font-weight: 500;
        }

        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--white);
            margin: 8mm 0 5mm;
            padding: 4mm 8mm;
            background: var(--primary-gold);
            border-radius: 4px;
            text-align: center;
        }

        .specs-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 3mm;
            margin: 6mm 0;
        }

        .spec-item {
            background: var(--white);
            border: 1px solid var(--border-light);
            border-radius: 4px;
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .spec-label {
            background: var(--light-gray);
            padding: 3mm;
            font-weight: 600;
            color: var(--text-dark);
            border-bottom: 1px solid var(--border-light);
            font-size: 11px;
            text-align: right;
            min-height: 25px;
            display: flex;
            align-items: center;
        }

        .spec-value {
            padding: 4mm 3mm;
            color: var(--text-dark);
            font-size: 12px;
            line-height: 1.3;
            min-height: 35px;
            display: flex;
            align-items: center;
            text-align: right;
            font-weight: 500;
        }

        .highlight-value {
            color: var(--primary-gold);
            font-weight: 700;
            font-size: 13px;
        }

        .specs-table {
            width: 100%;
            border-collapse: collapse;
            margin: 6mm 0;
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
            
            .specs-grid {
                grid-template-columns: 1fr;
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

    <?php if (!empty($data['opening_text'])): ?>
    <div class="content-section">
        <div class="opening-text">
            <?= nl2br(htmlspecialchars($data['opening_text'])) ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="content-section">
        <div class="client-name-section">
            <span class="title-before"><?= htmlspecialchars($data['title_before']) ?></span>
            <span class="client-name"><?= htmlspecialchars($data['client_name']) ?></span>
            <span class="title-before"><?= htmlspecialchars($data['title_after']) ?></span>
        </div>
    </div>

    <?php if (!empty($data['introductory_sentence'])): ?>
    <div class="content-section">
        <div class="introductory-text">
            <?= htmlspecialchars($data['introductory_sentence']) ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="section-title">المواصفات العامة</div>
    
    <div class="specs-grid">
        <div class="spec-item">
            <div class="spec-label">نوع المصعد</div>
            <div class="spec-value">MRL</div>
        </div>
        <div class="spec-item">
            <div class="spec-label">نوع المبنى</div>
            <div class="spec-value">مبنى سكني</div>
        </div>
        <div class="spec-item">
            <div class="spec-label">عدد المصاعد</div>
            <div class="spec-value highlight-value"><?= htmlspecialchars($data['elevators_count']) ?></div>
        </div>
        <div class="spec-item">
            <div class="spec-label">السرعة</div>
            <div class="spec-value">1.0 m/s</div>
        </div>
        <div class="spec-item">
            <div class="spec-label">وضع الماكينة</div>
            <div class="spec-value"><?= htmlspecialchars($data['machine_position']) ?></div>
        </div>
        <div class="spec-item">
            <div class="spec-label">عدد الوقفات</div>
            <div class="spec-value highlight-value"><?= htmlspecialchars($data['stops_count']) ?></div>
        </div>
        <div class="spec-item">
            <div class="spec-label">الحمولة</div>
            <div class="spec-value highlight-value"><?= htmlspecialchars($data['capacity']) ?> kg</div>
        </div>
        <div class="spec-item">
            <div class="spec-label">عدد الأشخاص</div>
            <div class="spec-value highlight-value"><?= htmlspecialchars($data['people_count']) ?></div>
        </div>
        <div class="spec-item">
            <div class="spec-label">عدد جهات الدخول</div>
            <div class="spec-value"><?= htmlspecialchars($data['entrance_count']) ?></div>
        </div>
        <?php if (!empty($data['machine_brand'])): ?>
        <div class="spec-item">
            <div class="spec-label">الماكينة</div>
            <div class="spec-value"><?= htmlspecialchars($data['machine_brand']) ?></div>
        </div>
        <?php endif; ?>
        <div class="spec-item">
            <div class="spec-label">البراند</div>
            <div class="spec-value"><?= htmlspecialchars($data['brand']) ?></div>
        </div>
    </div>

    <table class="specs-table">
        <tr>
            <td class="spec-label">طريقة التشغيل</td>
            <td class="spec-value"><?= htmlspecialchars($data['operation_method']) ?></td>
        </tr>
        <tr>
            <td class="spec-label">مسميات الوقفات</td>
            <td class="spec-value"><?= htmlspecialchars($data['stops_names']) ?></td>
        </tr>
        <tr>
            <td class="spec-label">التيار الكهربائي</td>
            <td class="spec-value"><?= htmlspecialchars($data['electrical_current']) ?></td>
        </tr>
    </table>

    <div class="page-number">2</div>
</div>

</body>
</html>