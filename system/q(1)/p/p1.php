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
    'project' => extractValue($quote_data['المشروع']),
    'address' => extractValue($quote_data['العنوان']),
    'elevators_count' => $quote_data['عدد المصاعد'] ?? '',
    'stops_count' => extractValue($quote_data['عدد الوقفات']),
    'capacity' => extractValue($quote_data['الحمولة']),
    'brand' => extractValue($quote_data['البراند'])
];

$formattedDate = formatGregorianDate($data['date']);
$quote_type = (stripos($data['brand'], 'elite') !== false || stripos($data['brand'], 'إليت') !== false) ? 'ELITE' : 'PRO';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عرض سعر رقم <?= htmlspecialchars($data['quote_number']) ?> - شركة ألفا الذهبية للمصاعد</title>
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
            line-height: 1.6;
            color: var(--text-dark);
            background: var(--white);
            direction: rtl;
            font-size: 14px;
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
            padding: 0;
            border: 1px solid var(--border-light);
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            text-align: center;
            background: linear-gradient(to bottom, var(--white) 50%, var(--primary-gold) 50%);
        }

        .cover-top-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 30mm 20mm;
            background: var(--white);
            width: 100%;
            position: relative;
        }

        .cover-bottom-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 30mm 20mm;
            background: var(--primary-gold);
            width: 100%;
            color: var(--white);
            position: relative;
        }

        .cover-logo {
            width: 280px;
            height: auto;
            margin-bottom: 25mm;
        }

        .cover-quote-title {
            margin-bottom: 15mm;
        }

        .cover-quote-title h1 {
            font-size: 32px;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 8mm;
            line-height: 1.2;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .cover-quote-title h2 {
            font-size: 24px;
            font-weight: 600;
            color: var(--white);
            line-height: 1.2;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .quote-meta {
            font-size: 16px;
            color: rgba(255,255,255,0.9);
            margin-bottom: 15mm;
            display: flex;
            gap: 15mm;
            justify-content: center;
        }

        .cover-client-info {
            margin-bottom: 20mm;
            font-size: 20px;
            font-weight: 600;
            line-height: 1.6;
            color: var(--white);
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .cover-client-info .title-parts {
            color: var(--light-gray);
            margin: 0 5mm;
        }

        .cover-client-info .client-name {
            color: var(--white);
            font-weight: 700;
        }

        .cover-project-info {
            font-size: 16px;
            color: rgba(255,255,255,0.9);
            line-height: 1.6;
            text-align: center;
            margin-bottom: 15mm;
        }

        .cover-project-info div {
            margin-bottom: 2mm;
        }

        .sidebar-specs {
            display: flex;
            justify-content: center;
            gap: 5mm;
            font-size: 13px;
            color: rgba(255,255,255,0.9);
            background: rgba(0,0,0,0.1);
            padding: 4mm;
            border-radius: 4px;
        }

        .page-number {
            position: absolute;
            bottom: 3mm;
            right: 3mm;
            font-size: 11px;
            color: var(--white);
            font-weight: 600;
            background: var(--dark-gold);
            padding: 3mm 6mm;
            border-radius: 15px;
            box-shadow: var(--shadow);
            z-index: 10;
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
    <div class="cover-top-section">
        <img src="https://alfagolden.com/images/logo.png" alt="شعار شركة ألفا الذهبية" class="cover-logo">
    </div>

    <div class="cover-bottom-section">
        <div class="cover-quote-title">
            <h1>عرض سعر</h1>
            <h2>ALFA <?= strtoupper($quote_type) ?></h2>
        </div>
        
        <div class="quote-meta">
            <span><strong>رقم العرض:</strong> <?= htmlspecialchars($data['quote_number']) ?></span>
            <span><strong>التاريخ:</strong> <?= $formattedDate ?></span>
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
        
        <div class="sidebar-specs">
            <span>عدد المصاعد: <?= htmlspecialchars($data['elevators_count']) ?></span>
            <span>|</span>
            <span>عدد الوقفات: <?= htmlspecialchars($data['stops_count']) ?></span>
            <span>|</span>
            <span>الحمولة: <?= htmlspecialchars($data['capacity']) ?> kg</span>
        </div>
    </div>
    
    <div class="page-number">1</div>
</div>

</body>
</html>