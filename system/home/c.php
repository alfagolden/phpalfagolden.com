<?php
session_start();

// --- [ بداية التحقق من تسجيل الدخول ] ---
$session_duration = 7 * 24 * 60 * 60; // أسبوع

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header('Location: https://alfagolden.com/system/login.php');
    exit;
}

// التحقق من انتهاء صلاحية الجلسة
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > $session_duration) {
    session_unset();
    session_destroy();
    header('Location: https://alfagolden.com/system/login.php');
    exit;
}

// تحديث وقت النشاط
$_SESSION['login_time'] = time();

// التكوين
$API_CONFIG = [
    'baseUrl' => 'https://base.alfagolden.com',
    'token' => 'h5qAt85gtiJDAzpH51WrXPywhmnhrPWy',
    'quotesTableId' => 704,
    'usersTableId' => 702
];

// خرائط الحقول
$FIELDS = [
    'quotes' => [
        'client' => 'field_6786',
        'date' => 'field_6789', 
        'totalPrice' => 'field_6984',
        'brand' => 'field_6973',
        'createdBy' => 'field_6990',
        'quoteNumber' => 'field_6783',
        'status' => 'field_7013',
        'responseDate' => 'field_7014',
        'rejectReason' => 'field_7015'
    ],
    'users' => [
        'name' => 'field_6912'
    ]
];

// وظائف مساعدة للـ API
function makeApiRequest($endpoint, $method = 'GET', $data = null) {
    global $API_CONFIG;
    
    $url = $API_CONFIG['baseUrl'] . '/api/database/' . $endpoint;
    $options = [
        'http' => [
            'method' => $method,
            'header' => [
                'Authorization: Token ' . $API_CONFIG['token'],
                'Content-Type: application/json'
            ]
        ]
    ];
    
    if ($data) {
        $options['http']['content'] = json_encode($data);
    }
    
    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    
    if ($response === false) {
        throw new Exception('API request failed');
    }
    
    return json_decode($response, true);
}

// جلب بيانات المستخدم الحالي
function getCurrentUser($userId) {
    global $API_CONFIG;
    try {
        $response = makeApiRequest("rows/table/{$API_CONFIG['usersTableId']}/{$userId}/");
        return $response;
    } catch (Exception $e) {
        return null;
    }
}

// جلب عروض الأسعار الخاصة بالعميل
function getClientQuotes($userId) {
    global $API_CONFIG, $FIELDS;
    try {
        // فلترة العروض بناءً على ID المستخدم لأن الحقل من نوع link_row
        $endpoint = "rows/table/{$API_CONFIG['quotesTableId']}/?filter__{$FIELDS['quotes']['client']}__link_row_has=" . $userId;
        $response = makeApiRequest($endpoint);
        return $response['results'] ?? [];
    } catch (Exception $e) {
        return [];
    }
}

// تحديث عرض السعر
function updateQuote($quoteId, $data) {
    global $API_CONFIG;
    try {
        $url = $API_CONFIG['baseUrl'] . '/api/database/rows/table/' . $API_CONFIG['quotesTableId'] . '/' . $quoteId . '/';
        $options = [
            'http' => [
                'method' => 'PATCH',
                'header' => [
                    'Authorization: Token ' . $API_CONFIG['token'],
                    'Content-Type: application/json'
                ],
                'content' => json_encode($data)
            ]
        ];
        
        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);
        
        return $response !== false;
    } catch (Exception $e) {
        return false;
    }
}

// التحقق من صلاحية المستخدم للعرض
function canUserAccessQuote($quote, $userId) {
    global $FIELDS;
    
    $client_field = $quote[$FIELDS['quotes']['client']] ?? [];
    $client_ids = [];

    if (is_array($client_field)) {
        foreach ($client_field as $client_item) {
            if (is_array($client_item) && isset($client_item['id'])) {
                $client_ids[] = intval($client_item['id']);
            } elseif (is_numeric($client_item)) {
                $client_ids[] = intval($client_item);
            }
        }
    } elseif (is_numeric($client_field)) {
        $client_ids[] = intval($client_field);
    }

    return in_array(intval($userId), $client_ids);
}

// وظائف مساعدة لتنسيق البيانات
function convertToEnglishNumbers($str) {
    if (!$str) return '';
    $arabicNumbers = '٠١٢٣٤٥٦٧٨٩';
    $englishNumbers = '0123456789';
    return str_replace(str_split($arabicNumbers), str_split($englishNumbers), (string)$str);
}

function formatDate($dateString) {
    if (!$dateString) return 'غير محدد';
    // استخدام توقيت السعودية +3
    $date = new DateTime($dateString, new DateTimeZone('Asia/Riyadh'));
    return convertToEnglishNumbers($date->format('Y/m/d'));
}

function formatPrice($price) {
    if (!$price) return 'غير محدد';
    $formatted = number_format(round($price), 0, '.', ',');
    $englishFormatted = convertToEnglishNumbers($formatted);
    return $englishFormatted;
}

function getGreeting() {
    // استخدام توقيت السعودية +3
    date_default_timezone_set('Asia/Riyadh');
    $timeFormat = date('A');
    return ($timeFormat === 'AM') ? 'صباح الخير' : 'مساء الخير';
}

// جلب بيانات المستخدم الحالي
$currentUser = getCurrentUser($_SESSION['user_id']);
$userName = 'العميل الكريم';

if ($currentUser && isset($currentUser[$FIELDS['users']['name']])) {
    $userName = $currentUser[$FIELDS['users']['name']];
}

// جلب عروض الأسعار الخاصة بالعميل
$quotes = getClientQuotes($_SESSION['user_id']);

// فلترة العروض للتأكد من صلاحية الوصول
$quotes = array_filter($quotes, function($quote) {
    return canUserAccessQuote($quote, $_SESSION['user_id']);
});

// ترتيب العروض من الأحدث للأقدم
usort($quotes, function($a, $b) use ($FIELDS) {
    $aDate = new DateTime($a[$FIELDS['quotes']['date']] ?? '1970-01-01');
    $bDate = new DateTime($b[$FIELDS['quotes']['date']] ?? '1970-01-01');
    return $bDate <=> $aDate;
});

// معالجة الموافقة والرفض
$message = '';
$message_type = '';
$show_celebration = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $quoteId = intval($_POST['quote_id'] ?? 0);
    
    if ($quoteId > 0) {
        // التحقق من صلاحية الوصول للعرض
        $quote = null;
        foreach ($quotes as $q) {
            if ($q['id'] == $quoteId) {
                $quote = $q;
                break;
            }
        }
        
        if ($quote) {
            if ($action === 'approve') {
                $current_datetime = date('Y-m-d\TH:i:s');
                $update_data = [
                    $FIELDS['quotes']['status'] => 'موافق',
                    $FIELDS['quotes']['responseDate'] => $current_datetime
                ];
                
                // إذا كان هناك سبب رفض سابق، نمحوه
                $update_data[$FIELDS['quotes']['rejectReason']] = null;
                
                $result = updateQuote($quoteId, $update_data);
                
                if ($result) {
                    $message = 'تم تسجيل موافقتكم بنجاح';
                    $message_type = 'success';
                    $show_celebration = true;
                } else {
                    $message = 'حدث خطأ في تسجيل الموافقة';
                    $message_type = 'error';
                }
                
            } elseif ($action === 'reject') {
                $reject_reason = $_POST['reject_reason'] ?? '';
                
                if (empty($reject_reason)) {
                    $message = 'يرجى إدخال سبب الرفض';
                    $message_type = 'error';
                } else {
                    $current_datetime = date('Y-m-d\TH:i:s');
                    $update_data = [
                        $FIELDS['quotes']['status'] => 'مرفوض',
                        $FIELDS['quotes']['responseDate'] => $current_datetime,
                        $FIELDS['quotes']['rejectReason'] => $reject_reason
                    ];
                    
                    $result = updateQuote($quoteId, $update_data);
                    
                    if ($result) {
                        $message = 'تم تسجيل رفضكم بنجاح';
                        $message_type = 'success';
                    } else {
                        $message = 'حدث خطأ في تسجيل الرفض';
                        $message_type = 'error';
                    }
                }
            }
        }
    }
    
    // إرجاع JSON للطلبات AJAX
    if (isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $message_type === 'success',
            'message' => $message,
            'show_celebration' => $show_celebration
        ]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عروض الأسعار - ألفا الذهبية</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
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
            --button-gray: #4a5568;
            --error: #dc3545;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Cairo', sans-serif;
            font-size: 14px;
            line-height: 1.5;
            direction: rtl;
            background: var(--light-gray);
            color: var(--dark-gray);
            padding: 16px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
        }

        .header {
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border-color);
            margin-bottom: 20px;
            padding: 24px;
            text-align: center;
        }

        .greeting-line {
            font-size: 18px;
            font-weight: 600;
            color: var(--gold);
        }

        .quote-card {
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border-color);
            margin-bottom: 16px;
            padding: 20px;
            transition: all 0.3s ease;
        }

        .quote-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .quote-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .quote-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--dark-gray);
        }

        .quote-number {
            font-size: 16px;
            font-weight: 700;
            color: var(--gold);
        }

        .quote-date {
            font-size: 13px;
            color: var(--medium-gray);
            font-weight: 500;
        }

        .quote-price {
            font-size: 18px;
            font-weight: 700;
            color: var(--dark-gray);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .sar-icon {
            width: 20px;
            height: 20px;
        }

        .quote-actions {
            display: flex;
            gap: 12px;
            flex-direction: column;
        }

        .action-row {
            display: flex;
            gap: 12px;
        }

        .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            flex: 1;
            min-height: 44px;
        }

        .btn-approve {
            background: var(--gold);
            color: var(--white);
        }

        .btn-approve:hover {
            background: var(--gold-hover);
            transform: translateY(-1px);
        }

        .btn-reject {
            background: var(--white);
            color: var(--gold);
            border: 2px solid var(--gold);
        }

        .btn-reject:hover {
            background: var(--gold-light);
            transform: translateY(-1px);
        }

        .btn-view {
            background: var(--button-gray);
            color: var(--white);
        }

        .btn-view:hover {
            background: #3a4553;
            transform: translateY(-1px);
        }

        .btn-processing {
            opacity: 0.7;
            pointer-events: none;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .status-approved {
            background: var(--gold-light);
            color: var(--gold);
            border: 1px solid var(--gold);
        }

        .status-rejected {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .status-pending {
            background: #f0f9ff;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }

        .reject-reason {
            background: var(--light-gray);
            padding: 12px;
            border-radius: 8px;
            margin-top: 8px;
            font-size: 13px;
            color: var(--medium-gray);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border-color);
        }

        .empty-icon {
            font-size: 64px;
            color: var(--border-color);
            margin-bottom: 20px;
        }

        .empty-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark-gray);
            margin-bottom: 8px;
        }

        .empty-text {
            font-size: 14px;
            color: var(--medium-gray);
        }

        .message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .message.success {
            background: var(--gold-light);
            color: var(--gold);
            border: 1px solid var(--gold);
        }

        .message.error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .reject-form {
            display: none;
            margin-top: 16px;
            padding: 16px;
            background: var(--gold-light);
            border-radius: 8px;
            border: 1px solid var(--gold);
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: var(--medium-gray);
            margin-bottom: 6px;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Cairo', sans-serif;
            transition: all 0.3s ease;
            resize: vertical;
            min-height: 100px;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--gold);
            box-shadow: 0 0 0 3px var(--gold-light);
        }

        /* تأثيرات الاحتفال */
        .celebration {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 9999;
        }

        .success-pulse {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: var(--gold);
            border-radius: 50%;
            width: 0;
            height: 0;
            animation: success-pulse 0.6s ease-out;
            opacity: 0.6;
        }

        @keyframes success-pulse {
            0% {
                width: 0;
                height: 0;
                opacity: 0.8;
            }
            50% {
                width: 200px;
                height: 200px;
                opacity: 0.3;
            }
            100% {
                width: 400px;
                height: 400px;
                opacity: 0;
            }
        }

        .success-checkmark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: var(--gold);
            font-size: 3rem;
            animation: checkmark-bounce 0.3s ease-out;
            z-index: 10000;
        }

        @keyframes checkmark-bounce {
            0% {
                transform: translate(-50%, -50%) scale(0);
                opacity: 0;
            }
            70% {
                transform: translate(-50%, -50%) scale(1.05);
                opacity: 1;
            }
            100% {
                transform: translate(-50%, -50%) scale(1);
                opacity: 1;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 8px;
            }
            
            .quote-header {
                flex-wrap: wrap;
                gap: 8px;
            }
            
            .quote-number {
                order: 2;
                flex: 1;
                text-align: center;
            }
            
            .quote-title {
                order: 1;
            }
            
            .quote-date {
                order: 3;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="greeting-line"><?= getGreeting() ?> <?= htmlspecialchars($userName) ?></div>
        </div>

        <!-- Messages -->
        <div id="messageContainer">
            <?php if ($message): ?>
                <div class="message <?= $message_type ?>">
                    <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
                    <?= $message ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quotes List -->
        <div id="quotesContainer">
            <?php if (empty($quotes)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <div class="empty-title">لا توجد عروض أسعار</div>
                    <div class="empty-text">لم يتم إنشاء أي عروض أسعار لك بعد</div>
                </div>
            <?php else: ?>
                <?php foreach ($quotes as $quote): ?>
                    <?php
                    $quoteNumber = $quote[$FIELDS['quotes']['quoteNumber']] ?? $quote['id'];
                    $quoteDate = formatDate($quote[$FIELDS['quotes']['date']]);
                    $totalPrice = formatPrice($quote[$FIELDS['quotes']['totalPrice']]);
                    $currentStatus = $quote[$FIELDS['quotes']['status']] ?? 'انتظار الرد';
                    $rejectReason = $quote[$FIELDS['quotes']['rejectReason']] ?? '';
                    $alreadyResponded = !empty($currentStatus) && $currentStatus !== 'انتظار الرد';
                    $isApproved = $currentStatus === 'موافق';
                    $isRejected = $currentStatus === 'مرفوض';
                    ?>
                    <div class="quote-card">
                        <div class="quote-header">
                            <div class="quote-title">عرض سعر</div>
                            <div class="quote-number">#<?= convertToEnglishNumbers($quoteNumber) ?></div>
                            <div class="quote-date"><?= $quoteDate ?></div>
                        </div>
                        
                        <div class="quote-price">
                            <?= $totalPrice ?>
                            <img src="https://alfagolden.com/images/sar.svg" alt="ريال سعودي" class="sar-icon">
                        </div>
                        
                        <?php if ($alreadyResponded): ?>
                            <?php if ($currentStatus === 'موافق'): ?>
                                <div class="status-badge status-approved">
                                    <i class="fas fa-check-circle"></i>
                                    موافق عليه
                                </div>
                            <?php elseif ($currentStatus === 'مرفوض'): ?>
                                <div class="status-badge status-rejected">
                                    <i class="fas fa-times-circle"></i>
                                    مرفوض
                                </div>
                                <?php if ($rejectReason): ?>
                                    <div class="reject-reason">
                                        <strong>سبب الرفض:</strong> <?= htmlspecialchars($rejectReason) ?>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="status-badge status-pending">
                                    <i class="fas fa-clock"></i>
                                    <?= htmlspecialchars($currentStatus) ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="quote-actions">
                                <div class="action-row">
                                    <a href="javascript:void(0)" onclick="viewQuote(<?= $quote['id'] ?>)" class="btn btn-view">
                                        <i class="fas fa-eye"></i>
                                        عرض التفاصيل
                                    </a>
                                    
                                    <?php if ($isApproved): ?>
                                        <button onclick="showRejectForm(<?= $quote['id'] ?>)" class="btn btn-reject">
                                            <i class="fas fa-times"></i>
                                            تغيير إلى مرفوض
                                        </button>
                                    <?php elseif ($isRejected): ?>
                                        <button onclick="approveQuote(<?= $quote['id'] ?>)" class="btn btn-approve">
                                            <i class="fas fa-check"></i>
                                            تغيير إلى موافق
                                        </button>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- نموذج الرفض للتعديل -->
                                <div class="reject-form" id="rejectForm_<?= $quote['id'] ?>">
                                    <h4 style="margin-bottom: 12px; color: var(--gold); font-size: 14px;">
                                        <?= $isApproved ? 'يرجى إخبارنا عن سبب تغيير قراركم:' : 'لطفاً منكم نرجو إخبارنا عن سبب رفضكم للعرض:' ?>
                                    </h4>
                                    <div class="form-group">
                                        <label class="form-label" for="reject_reason_<?= $quote['id'] ?>">سبب الرفض *</label>
                                        <textarea 
                                            id="reject_reason_<?= $quote['id'] ?>" 
                                            name="reject_reason" 
                                            class="form-control" 
                                            placeholder="يرجى كتابة سبب رفض العرض..."
                                        ><?= $isRejected ? htmlspecialchars($rejectReason) : '' ?></textarea>
                                    </div>
                                    <div style="display: flex; gap: 12px;">
                                        <button onclick="submitReject(<?= $quote['id'] ?>)" class="btn btn-reject">
                                            <i class="fas fa-paper-plane"></i>
                                            <?= $isApproved ? 'تأكيد التغيير' : 'تأكيد الرفض' ?>
                                        </button>
                                        <button onclick="hideRejectForm(<?= $quote['id'] ?>)" class="btn btn-view">
                                            <i class="fas fa-arrow-left"></i>
                                            إلغاء
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="quote-actions">
                                <div class="action-row">
                                    <button onclick="approveQuote(<?= $quote['id'] ?>)" class="btn btn-approve">
                                        <i class="fas fa-check"></i>
                                        موافق
                                    </button>
                                    <button onclick="showRejectForm(<?= $quote['id'] ?>)" class="btn btn-reject">
                                        <i class="fas fa-times"></i>
                                        رفض
                                    </button>
                                </div>
                                
                                <div class="reject-form" id="rejectForm_<?= $quote['id'] ?>">
                                    <h4 style="margin-bottom: 12px; color: var(--gold); font-size: 14px;">
                                        لطفاً منكم نرجو إخبارنا عن سبب رفضكم للعرض:
                                    </h4>
                                    <div class="form-group">
                                        <label class="form-label" for="reject_reason_<?= $quote['id'] ?>">سبب الرفض *</label>
                                        <textarea 
                                            id="reject_reason_<?= $quote['id'] ?>" 
                                            name="reject_reason" 
                                            class="form-control" 
                                            placeholder="يرجى كتابة سبب رفض العرض..."
                                        ></textarea>
                                    </div>
                                    <div style="display: flex; gap: 12px;">
                                        <button onclick="submitReject(<?= $quote['id'] ?>)" class="btn btn-reject">
                                            <i class="fas fa-paper-plane"></i>
                                            تأكيد الرفض
                                        </button>
                                        <button onclick="hideRejectForm(<?= $quote['id'] ?>)" class="btn btn-view">
                                            <i class="fas fa-arrow-left"></i>
                                            إلغاء
                                        </button>
                                    </div>
                                </div>
                                
                                <a href="javascript:void(0)" onclick="viewQuote(<?= $quote['id'] ?>)" class="btn btn-view">
                                    <i class="fas fa-eye"></i>
                                    عرض التفاصيل
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal for viewing quotes -->
    <div id="quoteModal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; display: none;">
        <div style="width: 100%; height: 100%; background: var(--white); position: relative;">
            <div style="padding: 16px; border-bottom: 1px solid var(--border-color); background: var(--light-gray); display: flex; justify-content: space-between; align-items: center;">
                <div id="modalTitle" style="font-size: 18px; font-weight: 600; color: var(--dark-gray);">عرض السعر</div>
                <button onclick="closeModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--medium-gray);">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <iframe id="modalIframe" style="width: 100%; height: calc(100% - 60px); border: none;" src="about:blank"></iframe>
        </div>
    </div>

    <!-- Celebration -->
    <?php if ($show_celebration): ?>
        <div class="celebration" id="celebration"></div>
    <?php endif; ?>

    <script>
        function approveQuote(quoteId) {
            if (!confirm('هل أنت متأكد من موافقتك على هذا العرض؟')) {
                return;
            }

            const btn = event.target.closest('.btn-approve');
            btn.classList.add('btn-processing');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري المعالجة...';

            const formData = new FormData();
            formData.append('action', 'approve');
            formData.append('quote_id', quoteId);
            formData.append('ajax', '1');

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.show_celebration) {
                        createCelebration();
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        showMessage(data.message, 'success');
                        setTimeout(() => window.location.reload(), 1000);
                    }
                } else {
                    showMessage(data.message, 'error');
                    btn.classList.remove('btn-processing');
                    btn.innerHTML = '<i class="fas fa-check"></i> موافق';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('حدث خطأ في الاتصال', 'error');
                btn.classList.remove('btn-processing');
                btn.innerHTML = '<i class="fas fa-check"></i> موافق';
            });
        }

        function showRejectForm(quoteId) {
            document.getElementById('rejectForm_' + quoteId).style.display = 'block';
            document.getElementById('reject_reason_' + quoteId).focus();
        }

        function hideRejectForm(quoteId) {
            document.getElementById('rejectForm_' + quoteId).style.display = 'none';
        }

        function submitReject(quoteId) {
            const reason = document.getElementById('reject_reason_' + quoteId).value.trim();
            if (!reason) {
                alert('يرجى إدخال سبب الرفض');
                document.getElementById('reject_reason_' + quoteId).focus();
                return;
            }
            
            const btn = event.target;
            btn.classList.add('btn-processing');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري الإرسال...';

            const formData = new FormData();
            formData.append('action', 'reject');
            formData.append('quote_id', quoteId);
            formData.append('reject_reason', reason);
            formData.append('ajax', '1');

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message, 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showMessage(data.message, 'error');
                    btn.classList.remove('btn-processing');
                    btn.innerHTML = '<i class="fas fa-paper-plane"></i> تأكيد الرفض';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('حدث خطأ في الاتصال', 'error');
                btn.classList.remove('btn-processing');
                btn.innerHTML = '<i class="fas fa-paper-plane"></i> تأكيد الرفض';
            });
        }

        function viewQuote(quoteId) {
            document.getElementById('modalTitle').textContent = `عرض السعر #${quoteId}`;
            document.getElementById('modalIframe').src = `https://alfagolden.com/system/q/5.php?quote_id=${quoteId}`;
            document.getElementById('quoteModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('quoteModal').style.display = 'none';
            document.getElementById('modalIframe').src = 'about:blank';
        }

        function showMessage(message, type) {
            const icon = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-triangle';
            document.getElementById('messageContainer').innerHTML = `
                <div class="message ${type}">
                    <i class="${icon}"></i>
                    ${message}
                </div>
            `;
            setTimeout(() => document.getElementById('messageContainer').innerHTML = '', 4000);
        }

        function createCelebration() {
            const celebration = document.createElement('div');
            celebration.className = 'celebration';
            document.body.appendChild(celebration);
            
            // تأثير النبضة الذهبية
            const pulse = document.createElement('div');
            pulse.className = 'success-pulse';
            celebration.appendChild(pulse);
            
            // علامة التأكيد
            const checkmark = document.createElement('div');
            checkmark.className = 'success-checkmark';
            checkmark.innerHTML = '<i class="fas fa-check-circle"></i>';
            celebration.appendChild(checkmark);
            
            // إزالة التأثير بعد انتهائه
            setTimeout(() => {
                celebration.remove();
            }, 2000);
        }

        // Close modal when clicking outside
        document.addEventListener('click', function(e) {
            if (e.target === document.getElementById('quoteModal')) {
                closeModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && document.getElementById('quoteModal').style.display !== 'none') {
                closeModal();
            }
        });

        <?php if ($show_celebration): ?>
        // تشغيل الاحتفال عند تحميل الصفحة
        document.addEventListener('DOMContentLoaded', function() {
            createCelebration();
        });
        <?php endif; ?>
    </script>
</body>
</html>