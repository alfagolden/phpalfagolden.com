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
    <title>صفحة النهاية - عرض سعر رقم <?= htmlspecialchars($data['quote_number']) ?></title>
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
            background: var(--primary-gold);
            position: relative;
            padding: 0;
            border: 1px solid var(--border-light);
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: var(--white);
        }

        .back-cover-content {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100%;
            padding: 40mm 30mm;
        }

        .company-logo {
            width: 200px;
            height: auto;
            margin-bottom: 20mm;
            filter: brightness(0) invert(1);
        }

        .company-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 15mm;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .contact-info {
            font-size: 16px;
            line-height: 2;
            margin-bottom: 20mm;
            text-align: center;
        }

        .contact-info div {
            margin-bottom: 3mm;
        }

        .brand-info {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15mm;
            padding: 6mm 15mm;
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            border: 2px solid rgba(255,255,255,0.3);
        }

        .quote-footer {
            font-size: 14px;
            color: rgba(255,255,255,0.8);
            margin-top: 15mm;
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
    <div class="back-cover-content">
        <img src="https://alfagolden.com/images/logo.png" alt="شعار شركة ألفا الذهبية" class="company-logo">
        
        <div class="company-title">شركة ألفا الذهبية للمصاعد</div>
        
        <div class="brand-info">ALFA <?= strtoupper($quote_type) ?></div>
        
        <div class="contact-info">
            <div><strong>الهاتف:</strong> 920004500</div>
            <div><strong>الجوال:</strong> 0555555555</div>
            <div><strong>البريد الإلكتروني:</strong> info@alfagolden.com</div>
            <div><strong>الموقع الإلكتروني:</strong> www.alfagolden.com</div>
            <div><strong>العنوان:</strong> الرياض - المملكة العربية السعودية</div>
        </div>
        
        <div class="quote-footer">
            شكراً لثقتكم في شركة ألفا الذهبية للمصاعد<br>
            نحن في خدمتكم دائماً
        </div>
    </div>
    
    <div class="page-number">7</div>
</div>

</body>
</html>