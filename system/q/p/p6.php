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

function calculatePriceBeforeTax($total) { return $total / 1.15; }

$data = [
    'quote_number' => $quote_data['الرقم التست'] ?? '',
    'date' => $quote_data['تاريخ'] ?? '',
    'supply_installation' => $quote_data['التوريد والتركيب'] ?? '',
    'warranty_maintenance' => $quote_data['الضمان والصيانة المجانية'] ?? '',
    'closing_sentence' => $quote_data['الجملة الختامية'] ?? '',
    'total_price' => $quote_data['السعر الإجمالي'] ?? 0,
    'discount_amount' => $quote_data['مبلغ التخفيض'] ?? 0
];

$formattedDate = formatGregorianDate($data['date']);

$totalPriceWithTax = (float)$data['total_price'];
$discountAmount = (float)$data['discount_amount'];
$priceBeforeDiscount = $totalPriceWithTax + $discountAmount;
$priceBeforeTax = calculatePriceBeforeTax($totalPriceWithTax);
$taxAmount = $totalPriceWithTax - $priceBeforeTax;
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التوريد والأسعار - عرض سعر رقم <?= htmlspecialchars($data['quote_number']) ?></title>
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

        .price-breakdown {
            margin: 8mm 0;
            background: var(--light-gray);
            border-radius: 4px;
            overflow: hidden;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 4mm 8mm;
            border-bottom: 1px solid var(--border-light);
            font-size: 14px;
            background: var(--white);
        }

        .price-row:first-child {
            background: var(--light-gray);
        }

        .price-row:last-child {
            border-bottom: none;
            background: var(--primary-gold);
            color: var(--white);
            font-weight: 700;
            font-size: 15px;
        }

        .price-label {
            font-weight: 600;
            color: var(--text-dark);
        }

        .price-row:last-child .price-label {
            color: var(--white);
        }

        .price-value {
            font-weight: 600;
            color: var(--primary-gold);
            display: flex;
            align-items: center;
            gap: 2mm;
        }

        .price-row:last-child .price-value {
            color: var(--white);
        }

        .payment-schedule {
            margin: 8mm 0;
            background: var(--light-gray);
            border-radius: 4px;
            overflow: hidden;
        }

        .payment-schedule table {
            width: 100%;
            border-collapse: collapse;
        }

        .payment-schedule th {
            background: var(--primary-gold);
            color: var(--white);
            padding: 5mm;
            text-align: right;
            font-weight: 600;
            font-size: 12px;
        }

        .payment-schedule td {
            padding: 4mm;
            text-align: right;
            border-bottom: 1px solid var(--border-light);
            background: var(--white);
            font-size: 12px;
            vertical-align: middle;
        }

        .currency-icon {
            width: 14px;
            height: 14px;
            vertical-align: middle;
        }

        .timing-info {
            background: var(--light-gray);
            padding: 5mm;
            margin: 6mm 0;
            border-radius: 4px;
            text-align: center;
            line-height: 1.6;
            font-weight: 500;
            font-size: 13px;
            border-right: 3mm solid var(--primary-gold);
        }

        .company-signature {
            font-weight: 700;
            color: var(--primary-gold);
            font-size: 15px;
            margin: 10mm 0 0;
            text-align: center;
            padding-top: 8mm;
            border-top: 2px solid var(--primary-gold);
        }

        .department-name {
            color: var(--text-medium);
            font-size: 13px;
            margin-top: 2mm;
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

    <?php if (!empty($data['supply_installation'])): ?>
    <div class="section-title">التوريد والتركيب</div>
    <div class="long-text">
        <?= nl2br(htmlspecialchars($data['supply_installation'])) ?>
    </div>
    <?php endif; ?>

    <div class="section-title">الأسعار والدفعات</div>
    
    <div class="price-breakdown">
        <?php if ($discountAmount > 0): ?>
        <div class="price-row">
            <span class="price-label">السعر قبل الخصم:</span>
            <span class="price-value"><?= number_format($priceBeforeDiscount) ?> <img src="https://alfagolden.com/images/sar.svg" class="currency-icon" alt="ريال سعودي"></span>
        </div>
        <div class="price-row">
            <span class="price-label">مبلغ الخصم:</span>
            <span class="price-value">-<?= number_format($discountAmount) ?> <img src="https://alfagolden.com/images/sar.svg" class="currency-icon" alt="ريال سعودي"></span>
        </div>
        <?php endif; ?>
        
        <div class="price-row">
            <span class="price-label">السعر بدون ضريبة:</span>
            <span class="price-value"><?= number_format($priceBeforeTax) ?> <img src="https://alfagolden.com/images/sar.svg" class="currency-icon" alt="ريال سعودي"></span>
        </div>
        
        <div class="price-row">
            <span class="price-label">ضريبة القيمة المضافة (15%):</span>
            <span class="price-value"><?= number_format($taxAmount) ?> <img src="https://alfagolden.com/images/sar.svg" class="currency-icon" alt="ريال سعودي"></span>
        </div>
        
        <div class="price-row">
            <span class="price-label">السعر الإجمالي شامل الضريبة:</span>
            <span class="price-value"><?= number_format($totalPriceWithTax) ?> <img src="https://alfagolden.com/images/sar.svg" class="currency-icon" alt="ريال سعودي"></span>
        </div>
    </div>
    
    <div class="payment-schedule">
        <table>
            <thead>
                <tr>
                    <th>الدفعة</th>
                    <th>النسبة</th>
                    <th>المبلغ</th>
                    <th>التوقيت</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>الأولى</strong></td>
                    <td><strong>35%</strong></td>
                    <td><strong><?= number_format($totalPriceWithTax * 0.35) ?> <img src="https://alfagolden.com/images/sar.svg" class="currency-icon" alt="ريال سعودي"></strong></td>
                    <td>عند توقيع العقد</td>
                </tr>
                <tr>
                    <td><strong>الثانية</strong></td>
                    <td><strong>30%</strong></td>
                    <td><strong><?= number_format($totalPriceWithTax * 0.30) ?> <img src="https://alfagolden.com/images/sar.svg" class="currency-icon" alt="ريال سعودي"></strong></td>
                    <td>بعد الانتهاء من المرحلة الأولى سكك وأبواب</td>
                </tr>
                <tr>
                    <td><strong>الثالثة</strong></td>
                    <td><strong>30%</strong></td>
                    <td><strong><?= number_format($totalPriceWithTax * 0.30) ?> <img src="https://alfagolden.com/images/sar.svg" class="currency-icon" alt="ريال سعودي"></strong></td>
                    <td>بعد الانتهاء من المرحلة الثانية ميكانيكا</td>
                </tr>
                <tr>
                    <td><strong>الرابعة</strong></td>
                    <td><strong>5%</strong></td>
                    <td><strong><?= number_format($totalPriceWithTax * 0.05) ?> <img src="https://alfagolden.com/images/sar.svg" class="currency-icon" alt="ريال سعودي"></strong></td>
                    <td>عند الانتهاء من مرحلة الكهرباء (التسليم والتشغيل)</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="timing-info">
        <strong>مدة التوريد:</strong> 45 يوم من تاريخ استلام الدفعة الأولى<br>
        <strong>مدة التركيب:</strong> 40 يوم من تاريخ استلام الموقع جاهزاً للتركيب
    </div>

    <?php if (!empty($data['warranty_maintenance'])): ?>
    <div class="section-title">الضمان والصيانة المجانية</div>
    <div class="long-text">
        <?= nl2br(htmlspecialchars($data['warranty_maintenance'])) ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($data['closing_sentence'])): ?>
    <div class="long-text">
        <?= nl2br(htmlspecialchars($data['closing_sentence'])) ?>
    </div>
    <?php endif; ?>
    
    <div class="company-signature">
        <div>إدارة العقود والمبيعات</div>
        <div class="department-name">شركة ألفا الذهبية للمصاعد</div>
    </div>

    <div class="page-number">6</div>
</div>

</body>
</html>