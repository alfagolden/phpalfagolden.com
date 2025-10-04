<?php
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
        'client' => 'field_6977',
        'date' => 'field_6789', 
        'totalPrice' => 'field_6984',
        'brand' => 'field_6973',
        'createdBy' => 'field_6990',
        'quoteNumber' => 'field_6783',
        'clientApprovalStatus' => 'field_7013',
        'approvalTime' => 'field_7014',
        'rejectionReason' => 'field_7015'
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

// تحديث حالة العرض
function updateQuoteStatus($quoteId, $status, $rejectionReason = '') {
    global $API_CONFIG, $FIELDS;
    
    $updateData = [
        $FIELDS['quotes']['clientApprovalStatus'] => $status,
        $FIELDS['quotes']['approvalTime'] => date('Y-m-d\TH:i:s')
    ];
    
    if ($status === 'مرفوض' && !empty($rejectionReason)) {
        $updateData[$FIELDS['quotes']['rejectionReason']] = $rejectionReason;
    } elseif ($status !== 'مرفوض') {
        $updateData[$FIELDS['quotes']['rejectionReason']] = '';
    }
    
    try {
        $url = $API_CONFIG['baseUrl'] . '/api/database/rows/table/' . $API_CONFIG['quotesTableId'] . '/' . $quoteId . '/';
        $options = [
            'http' => [
                'method' => 'PATCH',
                'header' => [
                    'Authorization: Token ' . $API_CONFIG['token'],
                    'Content-Type: application/json'
                ],
                'content' => json_encode($updateData)
            ]
        ];
        
        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);
        
        return $response !== false;
    } catch (Exception $e) {
        return false;
    }
}

// معالجة تحديث الحالة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $quoteId = intval($_POST['quote_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $rejectionReason = $_POST['rejection_reason'] ?? '';
    
    if ($quoteId > 0 && in_array($status, ['انتظار الرد', 'موافق', 'مرفوض'])) {
        $result = updateQuoteStatus($quoteId, $status, $rejectionReason);
        
        if (isset($_POST['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'تم تحديث الحالة بنجاح' : 'حدث خطأ في التحديث'
            ]);
            exit;
        }
    }
}

// تحميل عروض الأسعار
function loadQuotes() {
    global $API_CONFIG;
    try {
        $response = makeApiRequest("rows/table/{$API_CONFIG['quotesTableId']}/");
        return $response['results'] ?? [];
    } catch (Exception $e) {
        return [];
    }
}

// تحميل المستخدمين
function loadUsers() {
    global $API_CONFIG;
    try {
        $response = makeApiRequest("rows/table/{$API_CONFIG['usersTableId']}/");
        return $response['results'] ?? [];
    } catch (Exception $e) {
        return [];
    }
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
    $date = new DateTime($dateString);
    return convertToEnglishNumbers($date->format('d/m/Y'));
}

function formatPrice($price) {
    if (!$price) return 'غير محدد';
    $formatted = number_format(round($price));
    $englishFormatted = convertToEnglishNumbers($formatted);
    return $englishFormatted . ' <img src="https://alfagolden.com/images/sar.svg" alt="ريال سعودي" class="w-5 h-5 inline-block">';
}

function getClientName($clientArray) {
    if (!$clientArray || !is_array($clientArray) || empty($clientArray)) return 'غير محدد';
    return $clientArray[0]['value'] ?? 'غير محدد';
}

function getBrandName($brandArray) {
    if (!$brandArray || !is_array($brandArray) || empty($brandArray)) return 'غير محدد';
    $brandData = $brandArray[0];
    if (isset($brandData['value'])) {
        return is_array($brandData['value']) && isset($brandData['value']['value']) 
               ? $brandData['value']['value'] 
               : $brandData['value'];
    }
    return 'غير محدد';
}

function getUserName($userArray, $users) {
    if (!$userArray || !is_array($userArray) || empty($userArray)) return 'غير محدد';
    $userId = $userArray[0]['id'] ?? null;
    
    foreach ($users as $user) {
        if ($user['id'] === $userId) {
            global $FIELDS;
            return $user[$FIELDS['users']['name']] ?? 'غير محدد';
        }
    }
    return 'غير محدد';
}

function getQuoteStatus($quote) {
    global $FIELDS;
    
    $status = $quote[$FIELDS['quotes']['clientApprovalStatus']] ?? 'انتظار الرد';
    $rejectionReason = $quote[$FIELDS['quotes']['rejectionReason']] ?? '';
    $approvalTime = $quote[$FIELDS['quotes']['approvalTime']] ?? '';
    
    if (empty($status)) {
        $status = 'انتظار الرد';
    }
    
    return [
        'status' => $status,
        'rejection_reason' => $rejectionReason,
        'approval_time' => $approvalTime
    ];
}

// تحميل البيانات
$quotes = loadQuotes();
$users = loadUsers();

// معالجة الفلاتر إذا تم إرسالها
$filteredQuotes = $quotes;
$activeFilters = [
    'number' => [],
    'date' => ['from' => null, 'to' => null],
    'client' => [],
    'brand' => [],
    'user' => [],
    'status' => []
];

$sortBy = $_POST['sort_by'] ?? $_GET['sort_by'] ?? null;
$sortDir = $_POST['sort_dir'] ?? $_GET['sort_dir'] ?? 'desc';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'filter') {
        $activeFilters = json_decode($_POST['filters'], true) ?? $activeFilters;
        
        $filteredQuotes = array_filter($quotes, function($quote) use ($activeFilters, $users, $FIELDS) {
            // فلتر رقم العرض
            if (!empty($activeFilters['number'])) {
                $quoteNumber = convertToEnglishNumbers((string)($quote[$FIELDS['quotes']['quoteNumber']['value']] ?? $quote['id']));
                if (!in_array($quoteNumber, $activeFilters['number'])) return false;
            }
            
            // فلتر التاريخ
            if ($activeFilters['date']['from'] || $activeFilters['date']['to']) {
                $quoteDate = new DateTime($quote[$FIELDS['quotes']['date']]);
                if ($activeFilters['date']['from'] && $quoteDate < new DateTime($activeFilters['date']['from'])) return false;
                if ($activeFilters['date']['to'] && $quoteDate > new DateTime($activeFilters['date']['to'] . ' 23:59:59')) return false;
            }
            
            // فلتر العميل
            if (!empty($activeFilters['client'])) {
                $clientName = getClientName($quote[$FIELDS['quotes']['client']]);
                if (!in_array($clientName, $activeFilters['client'])) return false;
            }
            
            // فلتر البراند
            if (!empty($activeFilters['brand'])) {
                $brandName = getBrandName($quote[$FIELDS['quotes']['brand']]);
                if (!in_array($brandName, $activeFilters['brand'])) return false;
            }
            
            // فلتر المستخدم
            if (!empty($activeFilters['user'])) {
                $userName = getUserName($quote[$FIELDS['quotes']['createdBy']], $users);
                if (!in_array($userName, $activeFilters['user'])) return false;
            }
            
            // فلتر الحالة
            if (!empty($activeFilters['status'])) {
                $quoteStatus = getQuoteStatus($quote);
                if (!in_array($quoteStatus['status'], $activeFilters['status'])) return false;
            }
            
            return true;
        });
    }
    
    // إرجاع JSON للطلبات AJAX
    if (isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'count' => count($filteredQuotes)]);
        exit;
    }
}

// ترتيب البيانات
usort($filteredQuotes, function($a, $b) use ($FIELDS, $sortBy, $sortDir) {
    $direction = $sortDir === 'asc' ? 1 : -1;
    switch ($sortBy) {
        case 'number':
            $aVal = $a[$FIELDS['quotes']['quoteNumber']] ?? $a['id'];
            $bVal = $b[$FIELDS['quotes']['quoteNumber']] ?? $b['id'];
            return ($aVal <=> $bVal) * $direction;
            break;
        case 'date':
            $aVal = new DateTime($a[$FIELDS['quotes']['date']] ?? '1970-01-01');
            $bVal = new DateTime($b[$FIELDS['quotes']['date']] ?? '1970-01-01');
            return ($aVal <=> $bVal) * $direction;
            break;
        case 'price':
            $aVal = floatval($a[$FIELDS['quotes']['totalPrice']] ?? 0);
            $bVal = floatval($b[$FIELDS['quotes']['totalPrice']] ?? 0);
            return ($aVal <=> $bVal) * $direction;
            break;
        case 'status':
            $aVal = getQuoteStatus($a)['status'];
            $bVal = getQuoteStatus($b)['status'];
            return ($aVal <=> $bVal) * $direction;
            break;
        default:
            $aVal = new DateTime($a[$FIELDS['quotes']['date']] ?? '1970-01-01');
            $bVal = new DateTime($b[$FIELDS['quotes']['date']] ?? '1970-01-01');
            return ($bVal <=> $aVal); // الافتراضي: تنازلي بالتاريخ
            break;
    }
});
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة عروض الأسعار - ألفا الذهبية</title>
    
    <!-- Tailwind CSS v4.0 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Flatpickr CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.css" rel="stylesheet">
    
    <style type="text/tailwindcss">
        @theme {
            --color-gold: #977e2b;
            --color-gold-hover: #b89635;
            --color-gold-light: rgba(151, 126, 43, 0.1);
            --color-dark-gray: #2c2c2c;
            --color-medium-gray: #666;
            --color-light-gray: #f8f9fa;
            --color-border: #e5e7eb;
            --font-family-cairo: 'Cairo', sans-serif;
        }
        
        @layer base {
            body {
                font-family: var(--font-family-cairo);
                background-color: var(--color-light-gray);
                color: var(--color-dark-gray);
            }
            
            body, html {
                overflow-x: hidden !important;
            }
        }

        @layer components {
            .btn-gold {
                @apply bg-gold text-white px-6 py-3 rounded-md font-semibold transition-all duration-300 hover:bg-gold-hover hover:-translate-y-0.5 hover:shadow-lg flex items-center gap-2;
            }
            
            .btn-gray {
                @apply bg-gray-600 text-white px-5 py-2.5 rounded-md font-medium transition-all duration-300 hover:bg-gray-700 hover:-translate-y-0.5 flex items-center gap-2;
            }
            
            .card {
                @apply bg-white rounded-xl shadow-sm border border-border p-6;
            }
            
            .table-container {
                @apply overflow-x-auto;
            }
            
            .modern-table {
                @apply w-full border-collapse text-sm;
            }
            
            .table-header {
                @apply bg-gradient-to-r from-gray-50 to-gray-100 px-4 py-4 text-right font-bold text-dark-gray border-b-2 border-border text-sm cursor-pointer select-none transition-all duration-300 hover:bg-gold-light hover:text-gold whitespace-nowrap;
            }
            
            .table-cell {
                @apply px-4 py-4 border-b border-gray-100 align-middle transition-all duration-300;
            }
            
            .table-row {
                @apply transition-all duration-300;
            }
            
            .quote-id {
                @apply font-bold text-gold text-base;
            }
            
            .quote-date {
                @apply text-medium-gray text-sm font-medium;
            }
            
            .quote-client {
                @apply font-semibold text-dark-gray;
            }
            
            .quote-price {
                @apply font-bold text-dark-gray text-base flex items-center gap-2;
                direction: rtl;
            }
            
            .quote-brand {
                @apply inline-flex items-center px-3 py-1.5 bg-gold-light text-gold rounded-md text-xs font-semibold border border-gold;
            }
            
            .quote-user {
                @apply text-medium-gray text-sm font-medium;
            }
            
            .status-badge {
                @apply inline-flex items-center px-3 py-1.5 rounded-md text-xs font-semibold border;
            }
            
            .status-pending {
                @apply bg-blue-50 text-blue-800 border-blue-200;
            }
            
            .status-approved {
                @apply bg-green-50 text-green-800 border-green-200;
            }
            
            .status-rejected {
                @apply bg-red-50 text-red-800 border-red-200;
            }
            
            .btn-action {
                @apply p-2 border-0 rounded-md cursor-pointer text-sm transition-all duration-300 flex items-center justify-center w-9 h-9;
            }
            
            .btn-view {
                @apply bg-gray-600 text-white hover:bg-gray-700 hover:-translate-y-0.5 hover:shadow-md;
            }
            
            .btn-edit {
                @apply bg-gold text-white hover:bg-gold-hover hover:-translate-y-0.5 hover:shadow-md;
            }
            
            .btn-word {
                @apply bg-blue-600 text-white hover:bg-blue-700 hover:-translate-y-0.5 hover:shadow-md;
            }
            
            .btn-status {
                @apply bg-green-600 text-white hover:bg-green-700 hover:-translate-y-0.5 hover:shadow-md;
            }
            
            .btn-print {
                @apply bg-white border border-red-800 text-red-800 font-bold hover:bg-red-50 hover:text-white hover:bg-red-800 hover:border-red-900 hover:shadow-md;
            }
            
            .btn-contract {
                @apply bg-red-600 text-white hover:bg-red-700 hover:-translate-y-0.5 hover:shadow-md;
            }
            
            .modal-overlay {
                @apply fixed inset-0 bg-black/50 z-50 backdrop-blur-sm flex items-center justify-center;
            }
            
            .modal-content {
                @apply bg-white rounded-xl shadow-xl w-11/12 max-w-md overflow-hidden;
            }
            
            .filter-option {
                @apply p-3 cursor-pointer transition-all duration-300 flex items-center gap-2.5 text-sm rounded-md hover:bg-light-gray;
            }
            
            .filter-option.selected {
                @apply bg-gold-light text-gold font-semibold;
            }
            
            .date-option {
                @apply p-2.5 bg-light-gray border border-border rounded-md cursor-pointer transition-all duration-300 text-center text-sm hover:bg-gold-light hover:border-gold hover:text-gold;
            }
            
            .spinner {
                @apply w-8 h-8 border-4 border-border border-t-gold rounded-full animate-spin;
            }
            
            .message {
                @apply p-4 rounded-md mb-6 text-sm flex items-center gap-3 border font-medium;
            }
            
            .message.success {
                @apply bg-green-50 text-green-800 border-green-200;
            }
            
            .message.error {
                @apply bg-red-50 text-red-800 border-red-200;
            }

            @media (max-width: 768px) {
                .card {
                    @apply p-3 rounded-lg;
                }
                .table-header {
                    @apply px-2 py-3 text-xs;
                }
                .table-cell {
                    @apply px-2 py-3 text-xs;
                }
                .btn-action {
                    @apply w-8 h-8 text-xs;
                }
                .quote-id {
                    @apply text-sm;
                }
                .quote-price {
                    @apply text-sm;
                }
                .status-badge {
                    @apply px-2 py-1 text-xs;
                }
            }
        }
        
        @layer utilities {
            .flatpickr-calendar {
                font-family: var(--font-family-cairo) !important;
                direction: ltr;
                z-index: 9999 !important;
            }
            
            .flatpickr-day.selected {
                background: var(--color-gold) !important;
                border-color: var(--color-gold) !important;
            }
            
            .flatpickr-day:hover {
                background: var(--color-gold-light) !important;
            }
        }
    </style>
</head>

<body>
    <!-- Loading Page -->
    <div id="pageLoader" class="fixed inset-0 bg-white/95 backdrop-blur-sm flex items-center justify-center z-50">
        <div class="spinner"></div>
    </div>

    <div class="max-w-full mx-auto p-3 md:p-6 min-h-screen">
        <!-- Header -->
        <div class="card mb-6 flex flex-col md:flex-row justify-between items-center gap-4 md:gap-6">
            <h1 class="text-xl md:text-2xl font-bold text-dark-gray flex items-center gap-3">
                <i class="fas fa-file-invoice-dollar text-gold text-lg md:text-xl"></i>
                إدارة عروض الأسعار
            </h1>
            <div class="flex flex-col md:flex-row gap-3 md:gap-6 items-center w-full md:w-auto">
                <button onclick="clearAllFilters()" class="btn-gray w-full md:w-auto">
                    <i class="fas fa-eraser"></i>
                    مسح الفلاتر
                </button>
                <button onclick="createNewQuote()" class="btn-gold w-full md:w-auto">
                    <i class="fas fa-plus"></i>
                    إنشاء عرض سعر جديد
                </button>
            </div>
        </div>

        <!-- Messages -->
        <div id="messageContainer"></div>

        <!-- Loading -->
        <div id="loading" class="hidden text-center py-16 text-medium-gray">
            <div class="spinner mx-auto mb-5"></div>
            <p>جاري تحميل البيانات...</p>
        </div>

        <!-- Quotes Table -->
        <div id="quotesTableContainer" class="card p-0 overflow-hidden">
            <div class="table-container">
                <table class="modern-table">
                    <thead class="sticky top-0 z-10">
                        <tr>
                            <th data-column="number" class="table-header relative">
                                رقم العرض
                                <button type="button" onclick="sortTable('number')" class="ml-2 align-middle focus:outline-none">
                                    <i class="fas fa-sort<?= ($sortBy === 'number' ? ($sortDir === 'asc' ? '-up' : '-down') : '') ?> opacity-60"></i>
                                </button>
                                <div class="absolute top-1 right-1 w-2 h-2 bg-gold rounded-full opacity-0 transition-all duration-300"></div>
                            </th>
                            <th onclick="openFilter('date')" data-column="date" class="table-header relative">
                                التاريخ
                                <button type="button" onclick="event.stopPropagation();sortTable('date')" class="ml-2 align-middle focus:outline-none">
                                    <i class="fas fa-sort<?= ($sortBy === 'date' ? ($sortDir === 'asc' ? '-up' : '-down') : '') ?> opacity-60"></i>
                                </button>
                                <div class="absolute top-1 right-1 w-2 h-2 bg-gold rounded-full opacity-0 transition-all duration-300"></div>
                            </th>
                            <th onclick="openFilter('client')" data-column="client" class="table-header relative">
                                العميل
                                <i class="fas fa-sort opacity-30 mr-2 transition-all duration-300"></i>
                                <div class="absolute top-1 right-1 w-2 h-2 bg-gold rounded-full opacity-0 transition-all duration-300"></div>
                            </th>
                            <th data-column="price" class="table-header relative">
                                قيمة العرض
                                <button type="button" onclick="sortTable('price')" class="ml-2 align-middle focus:outline-none">
                                    <i class="fas fa-sort<?= ($sortBy === 'price' ? ($sortDir === 'asc' ? '-up' : '-down') : '') ?> opacity-60"></i>
                                </button>
                            </th>
                            <th onclick="openFilter('brand')" data-column="brand" class="table-header relative">
                                البراند
                                <i class="fas fa-sort opacity-30 mr-2 transition-all duration-300"></i>
                                <div class="absolute top-1 right-1 w-2 h-2 bg-gold rounded-full opacity-0 transition-all duration-300"></div>
                            </th>
                            <th onclick="openFilter('user')" data-column="user" class="table-header relative">
                                بواسطة
                                <i class="fas fa-sort opacity-30 mr-2 transition-all duration-300"></i>
                                <div class="absolute top-1 right-1 w-2 h-2 bg-gold rounded-full opacity-0 transition-all duration-300"></div>
                            </th>
                            <th onclick="openFilter('status')" data-column="status" class="table-header relative">
                                حالة العرض
                                <button type="button" onclick="event.stopPropagation();sortTable('status')" class="ml-2 align-middle focus:outline-none">
                                    <i class="fas fa-sort<?= ($sortBy === 'status' ? ($sortDir === 'asc' ? '-up' : '-down') : '') ?> opacity-60"></i>
                                </button>
                                <div class="absolute top-1 right-1 w-2 h-2 bg-gold rounded-full opacity-0 transition-all duration-300"></div>
                            </th>
                            <th class="table-header">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody id="quotesTableBody">
                        <?php if (empty($filteredQuotes)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-20 text-medium-gray">
                                    <i class="fas fa-search text-6xl text-border mb-5 block"></i>
                                    <h3 class="text-xl mb-3 text-dark-gray font-semibold">لا توجد نتائج</h3>
                                    <p class="text-base opacity-80">جرب تغيير الفلاتر أو مسحها</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($filteredQuotes as $quote): ?>

                                <?php
                                                            echo ($quote[0] );
                                $quoteNumber =  $quote[$FIELDS['quoteNumber']['value']] ?? $quote['id'];
                                $quoteDate = formatDate($quote[$FIELDS['quotes']['date']]);
                                $clientName = getClientName($quote[$FIELDS['quotes']['client']]);
                                $totalPrice = formatPrice($quote[$FIELDS['quotes']['totalPrice']]);
                                $brandName = getBrandName($quote[$FIELDS['quotes']['brand']]);
                                $userName = getUserName($quote[$FIELDS['quotes']['createdBy']], $users);
                                $quoteStatus = getQuoteStatus($quote);
                                ?>
                                <tr class="table-row">
                                    <td class="table-cell">
                                        <div class="quote-id">#<?= $quoteNumber ?></div>
                                    </td>
                                    <td class="table-cell">
                                        <div class="quote-date"><?= $quoteDate ?></div>
                                    </td>
                                    <td class="table-cell">
                                        <div class="quote-client"><?= htmlspecialchars($clientName) ?></div>
                                    </td>
                                    <td class="table-cell">
                                        <div class="quote-price"><?= $totalPrice ?></div>
                                    </td>
                                    <td class="table-cell">
                                        <div class="quote-brand"><?= htmlspecialchars($brandName) ?></div>
                                    </td>
                                    <td class="table-cell">
                                        <div class="quote-user"><?= htmlspecialchars($userName) ?></div>
                                    </td>
                                    <td class="table-cell">
                                        <div class="status-badge status-<?= $quoteStatus['status'] === 'موافق' ? 'approved' : ($quoteStatus['status'] === 'مرفوض' ? 'rejected' : 'pending') ?>">
                                            <i class="fas fa-<?= $quoteStatus['status'] === 'موافق' ? 'check-circle' : ($quoteStatus['status'] === 'مرفوض' ? 'times-circle' : 'clock') ?>"></i>
                                            <?= htmlspecialchars($quoteStatus['status']) ?>
                                        </div>
                                        <?php if ($quoteStatus['status'] === 'مرفوض' && !empty($quoteStatus['rejection_reason'])): ?>
                                            <div class="text-xs text-red-600 mt-1" title="<?= htmlspecialchars($quoteStatus['rejection_reason']) ?>">
                                                <?= htmlspecialchars(mb_substr($quoteStatus['rejection_reason'], 0, 30)) ?><?= mb_strlen($quoteStatus['rejection_reason']) > 30 ? '...' : '' ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="table-cell">
                                        <div class="flex gap-1 md:gap-2 flex-wrap">
                                            <button onclick="viewQuote(<?= $quote['id'] ?>)" class="btn-action btn-view" title="عرض">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button onclick="editQuote(<?= $quote['id'] ?>)" class="btn-action btn-edit" title="تعديل">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="editWordQuote(<?= $quote['id'] ?>)" class="btn-action btn-word" title="تعديل Word">
                                                <i class="fas fa-file-word"></i>
                                            </button>
                                            <button onclick="openStatusModal(<?= $quote['id'] ?>, '<?= htmlspecialchars($quoteStatus['status']) ?>', '<?= htmlspecialchars($quoteStatus['rejection_reason']) ?>')" class="btn-action btn-status" title="تحديث الحالة">
                                                <i class="fas fa-check"></i>
                                            </button>

                                            <button onclick="refreshPDF(<?= $quote['id'] ?>)" class="btn-action btn-danger" title="تحميل PDF">
                                                <i class="fa-solid fa-rotate-right"></i>
                                            </button>
                                            <button onclick="exportAsContract(<?= $quote['id'] ?>)" class="btn-action btn-contract" title="تصدير كعقد">
                                                <i class="fas fa-file-contract"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Filter Modal -->
    <div id="filterModal" class="hidden modal-overlay">
        <div class="modal-content">
            <div class="p-5 border-b border-border bg-light-gray flex justify-between items-center">
                <div id="filterTitle" class="text-lg font-bold text-dark-gray">فلتر</div>
                <button onclick="closeFilter()" class="text-xl cursor-pointer text-medium-gray p-1 rounded-md transition-all duration-300 hover:bg-border hover:text-dark-gray">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="filterBody" class="p-5 max-h-96 overflow-y-auto">
                <!-- Filter content will be populated here -->
            </div>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div id="statusModal" class="hidden modal-overlay">
        <div class="modal-content">
            <div class="p-5 border-b border-border bg-light-gray flex justify-between items-center">
                <div class="text-lg font-bold text-dark-gray">تحديث حالة العرض</div>
                <button onclick="closeStatusModal()" class="text-xl cursor-pointer text-medium-gray p-1 rounded-md transition-all duration-300 hover:bg-border hover:text-dark-gray">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-5">
                <form id="statusForm">
                    <input type="hidden" id="statusQuoteId" name="quote_id">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-dark-gray mb-2">الحالة</label>
                        <select id="statusSelect" name="status" class="w-full p-3 border border-border rounded-md text-sm focus:border-gold focus:ring-2 focus:ring-gold-light focus:outline-none">
                            <option value="انتظار الرد">انتظار الرد</option>
                            <option value="موافق">موافق</option>
                            <option value="مرفوض">مرفوض</option>
                        </select>
                    </div>
                    
                    <div id="rejectionReasonDiv" class="mb-4 hidden">
                        <label class="block text-sm font-medium text-dark-gray mb-2">سبب الرفض</label>
                        <textarea id="rejectionReason" name="rejection_reason" rows="3" class="w-full p-3 border border-border rounded-md text-sm focus:border-gold focus:ring-2 focus:ring-gold-light focus:outline-none" placeholder="يرجى إدخال سبب الرفض..."></textarea>
                    </div>
                    
                    <div class="flex gap-3">
                        <button type="button" onclick="updateQuoteStatus()" class="flex-1 bg-gold text-white py-3 px-4 rounded-md font-medium hover:bg-gold-hover transition-colors">
                            <i class="fas fa-save mr-2"></i>
                            حفظ
                        </button>
                        <button type="button" onclick="closeStatusModal()" class="flex-1 bg-gray-500 text-white py-3 px-4 rounded-md font-medium hover:bg-gray-600 transition-colors">
                            <i class="fas fa-times mr-2"></i>
                            إلغاء
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal for viewing/editing quotes -->
    <div id="quoteModal" class="hidden fixed inset-0 bg-black/80 backdrop-blur-sm z-50">
        <div class="w-full h-full bg-white relative">
            <div class="p-3 md:p-6 border-b border-border bg-gradient-to-r from-light-gray to-gray-50 flex justify-between items-center">
                <div id="modalTitle" class="text-lg md:text-xl font-bold text-dark-gray">عرض السعر</div>
                <button onclick="closeModal()" class="text-xl md:text-2xl cursor-pointer text-medium-gray p-2 rounded-md transition-all duration-300 hover:bg-gray-100 hover:text-dark-gray">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <iframe id="modalIframe" class="w-full border-0" style="height: calc(100% - 70px);" src="about:blank"></iframe>
        </div>
    </div>

    <!-- Flatpickr JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/l10n/ar.min.js"></script>

    <script>
        // Global variables
        let currentFilterType = null;
        let datePickerFrom = null;
        let datePickerTo = null;
        let activeFilters = <?= json_encode($activeFilters) ?>;
        
        // Initialize app
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                document.getElementById('pageLoader').style.display = 'none';
            }, 800);
        });

        // Status Modal Functions
        function openStatusModal(quoteId, currentStatus, rejectionReason) {
            document.getElementById('statusQuoteId').value = quoteId;
            document.getElementById('statusSelect').value = currentStatus;
            document.getElementById('rejectionReason').value = rejectionReason || '';
            
            toggleRejectionReason();
            document.getElementById('statusModal').classList.remove('hidden');
        }

        function closeStatusModal() {
            document.getElementById('statusModal').classList.add('hidden');
        }

        function toggleRejectionReason() {
            const status = document.getElementById('statusSelect').value;
            const rejectionDiv = document.getElementById('rejectionReasonDiv');
            
            if (status === 'مرفوض') {
                rejectionDiv.classList.remove('hidden');
            } else {
                rejectionDiv.classList.add('hidden');
            }
        }

        function updateQuoteStatus() {
            const quoteId = document.getElementById('statusQuoteId').value;
            const status = document.getElementById('statusSelect').value;
            const rejectionReason = document.getElementById('rejectionReason').value;
            
            if (status === 'مرفوض' && !rejectionReason.trim()) {
                alert('يرجى إدخال سبب الرفض');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'update_status');
            formData.append('quote_id', quoteId);
            formData.append('status', status);
            formData.append('rejection_reason', rejectionReason);
            formData.append('ajax', '1');
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message, 'success');
                    closeStatusModal();
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showMessage(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('حدث خطأ في التحديث', 'error');
            });
        }

        // Event listener for status select change
        document.getElementById('statusSelect').addEventListener('change', toggleRejectionReason);

        // Filter functions
        function openFilter(type) {
            currentFilterType = type;
            const filterTitles = {
                number: 'فلتر رقم العرض',
                date: 'فلتر التاريخ',
                client: 'فلتر العميل',
                brand: 'فلتر البراند',
                user: 'فلتر المستخدم',
                status: 'فلتر الحالة'
            };

            document.getElementById('filterTitle').textContent = filterTitles[type];
            const filterBody = document.getElementById('filterBody');
            
            if (type === 'date') {
                filterBody.innerHTML = `
                    <div class="mb-5">
                        <label class="block text-sm font-semibold text-medium-gray mb-2.5">فترة زمنية مخصصة</label>
                        <div class="flex gap-2.5 items-center mb-4">
                            <input type="text" id="dateFrom" placeholder="من تاريخ" readonly 
                                   class="flex-1 p-3 border border-border rounded-md text-sm text-center">
                            <span>-</span>
                            <input type="text" id="dateTo" placeholder="إلى تاريخ" readonly 
                                   class="flex-1 p-3 border border-border rounded-md text-sm text-center">
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div class="date-option" onclick="setQuickDateFilter('today')">اليوم</div>
                            <div class="date-option" onclick="setQuickDateFilter('yesterday')">أمس</div>
                            <div class="date-option" onclick="setQuickDateFilter('thisWeek')">هذا الأسبوع</div>
                            <div class="date-option" onclick="setQuickDateFilter('lastWeek')">الأسبوع الماضي</div>
                            <div class="date-option" onclick="setQuickDateFilter('thisMonth')">هذا الشهر</div>
                            <div class="date-option" onclick="setQuickDateFilter('lastMonth')">الشهر الماضي</div>
                            <div class="date-option" onclick="setQuickDateFilter('last30Days')">آخر 30 يوم</div>
                            <div class="date-option" onclick="setQuickDateFilter('last90Days')">آخر 90 يوم</div>
                        </div>
                    </div>
                `;

                setTimeout(() => {
                    datePickerFrom = flatpickr("#dateFrom", {
                        locale: "ar",
                        dateFormat: "Y-m-d",
                        defaultDate: activeFilters.date.from,
                        onChange: function(selectedDates, dateStr) {
                            activeFilters.date.from = dateStr;
                            applyFilters();
                        }
                    });

                    datePickerTo = flatpickr("#dateTo", {
                        locale: "ar",
                        dateFormat: "Y-m-d", 
                        defaultDate: activeFilters.date.to,
                        onChange: function(selectedDates, dateStr) {
                            activeFilters.date.to = dateStr;
                            applyFilters();
                        }
                    });
                }, 100);
            } else {
                // Get options based on current quotes data
                const quotes = <?= json_encode($filteredQuotes) ?>;
                let options = [];
                
                switch (type) {
                    case 'number':
                        options = [...new Set(quotes.map(quote => convertToEnglishNumbers((quote.<?= $FIELDS['quotes']['quoteNumber'] ?> || quote.id).toString())))].sort((a, b) => parseInt(b) - parseInt(a));
                        break;
                    case 'client':
                        options = [...new Set(quotes.map(quote => {
                            const client = quote.<?= $FIELDS['quotes']['client'] ?>;
                            return client && client.length ? client[0].value || 'غير محدد' : 'غير محدد';
                        }).filter(name => name !== 'غير محدد'))].sort();
                        break;
                    case 'brand':
                        options = [...new Set(quotes.map(quote => {
                            const brand = quote.<?= $FIELDS['quotes']['brand'] ?>;
                            if (brand && brand.length) {
                                const brandData = brand[0];
                                if (brandData && brandData.value) {
                                    return typeof brandData.value === 'object' && brandData.value.value ? brandData.value.value : brandData.value;
                                }
                            }
                            return 'غير محدد';
                        }).filter(name => name !== 'غير محدد'))].sort();
                        break;
                    case 'user':
                        const users = <?= json_encode($users) ?>;
                        options = [...new Set(quotes.map(quote => {
                            const userArray = quote.<?= $FIELDS['quotes']['createdBy'] ?>;
                            if (userArray && userArray.length) {
                                const userId = userArray[0].id;
                                const user = users.find(u => u.id === userId);
                                return user ? (user.<?= $FIELDS['users']['name'] ?> || 'غير محدد') : 'غير محدد';
                            }
                            return 'غير محدد';
                        }).filter(name => name !== 'غير محدد'))].sort();
                        break;
                    case 'status':
                        options = ['انتظار الرد', 'موافق', 'مرفوض'];
                        break;
                }

                filterBody.innerHTML = `
                    <div class="mb-4">
                        <input type="text" placeholder="البحث..." oninput="filterOptions(this.value)"
                               class="w-full p-3 border border-border rounded-md text-sm outline-none transition-all duration-300 focus:border-gold focus:ring-2 focus:ring-gold-light">
                    </div>
                    <div id="filterOptions" class="flex flex-col gap-2">
                        ${options.map(option => `
                            <div class="filter-option ${activeFilters[type].includes(option) ? 'selected' : ''}" onclick="toggleFilterOption('${option}')">
                                <input type="checkbox" ${activeFilters[type].includes(option) ? 'checked' : ''} class="accent-gold transform scale-110">
                                <label>${type === 'number' ? '#' + option : option}</label>
                            </div>
                        `).join('')}
                    </div>
                `;
            }

            document.getElementById('filterModal').classList.remove('hidden');
        }

        function closeFilter() {
            document.getElementById('filterModal').classList.add('hidden');
            currentFilterType = null;
            if (datePickerFrom) {
                datePickerFrom.destroy();
                datePickerFrom = null;
            }
            if (datePickerTo) {
                datePickerTo.destroy();
                datePickerTo = null;
            }
        }

        function toggleFilterOption(value) {
            if (!currentFilterType) return;

            const isActive = activeFilters[currentFilterType].includes(value);
            
            if (isActive) {
                activeFilters[currentFilterType] = activeFilters[currentFilterType].filter(item => item !== value);
            } else {
                activeFilters[currentFilterType].push(value);
            }

            // Update checkbox
            const checkbox = event.target.closest('.filter-option').querySelector('input[type="checkbox"]');
            checkbox.checked = !isActive;
            
            // Update visual state
            event.target.closest('.filter-option').classList.toggle('selected', !isActive);

            applyFilters();
        }

        function filterOptions(searchTerm) {
            const options = document.querySelectorAll('#filterOptions .filter-option');
            options.forEach(option => {
                const text = option.textContent.toLowerCase();
                const show = text.includes(searchTerm.toLowerCase());
                option.style.display = show ? 'flex' : 'none';
            });
        }

        function setQuickDateFilter(period) {
            const today = new Date();
            let fromDate, toDate;

            switch (period) {
                case 'today':
                    fromDate = toDate = today;
                    break;
                case 'yesterday':
                    fromDate = toDate = new Date(today.getTime() - 24 * 60 * 60 * 1000);
                    break;
                case 'thisWeek':
                    fromDate = new Date(today.getFullYear(), today.getMonth(), today.getDate() - today.getDay());
                    toDate = today;
                    break;
                case 'lastWeek':
                    fromDate = new Date(today.getFullYear(), today.getMonth(), today.getDate() - today.getDay() - 7);
                    toDate = new Date(today.getFullYear(), today.getMonth(), today.getDate() - today.getDay() - 1);
                    break;
                case 'thisMonth':
                    fromDate = new Date(today.getFullYear(), today.getMonth(), 1);
                    toDate = today;
                    break;
                case 'lastMonth':
                    fromDate = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                    toDate = new Date(today.getFullYear(), today.getMonth(), 0);
                    break;
                case 'last30Days':
                    fromDate = new Date(today.getTime() - 30 * 24 * 60 * 60 * 1000);
                    toDate = today;
                    break;
                case 'last90Days':
                    fromDate = new Date(today.getTime() - 90 * 24 * 60 * 60 * 1000);
                    toDate = today;
                    break;
            }

            if (fromDate && toDate) {
                const fromStr = fromDate.toISOString().split('T')[0];
                const toStr = toDate.toISOString().split('T')[0];
                
                activeFilters.date.from = fromStr;
                activeFilters.date.to = toStr;
                
                if (datePickerFrom) datePickerFrom.setDate(fromStr);
                if (datePickerTo) datePickerTo.setDate(toStr);
                
                applyFilters();
            }
        }

        function applyFilters() {
            // Send filters to server
            const formData = new FormData();
            formData.append('action', 'filter');
            formData.append('filters', JSON.stringify(activeFilters));
            formData.append('ajax', '1');

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload page to show filtered results
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.style.display = 'none';
                    
                    const actionInput = document.createElement('input');
                    actionInput.name = 'action';
                    actionInput.value = 'filter';
                    form.appendChild(actionInput);
                    
                    const filtersInput = document.createElement('input');
                    filtersInput.name = 'filters';
                    filtersInput.value = JSON.stringify(activeFilters);
                    form.appendChild(filtersInput);
                    
                    document.body.appendChild(form);
                    form.submit();
                }
            })
            .catch(error => {
                console.error('Error applying filters:', error);
                showMessage('فشل في تطبيق الفلاتر', 'error');
            });
        }

        function clearAllFilters() {
            activeFilters = {
                number: [],
                date: { from: null, to: null },
                client: [],
                brand: [],
                user: [],
                status: []
            };
            
            // Reload page without filters
            window.location.href = window.location.pathname;
        }

        // Quote actions
        function createNewQuote() {
            openModal('إنشاء عرض سعر جديد', 'https://alfagolden.com/system/q/1.php');
        }

        function viewQuote(quoteId) {
            openModal(`عرض السعر #${quoteId}`, `https://alfagolden.com/system/q/5.php?quote_id=${quoteId}`);
        }

        function editQuote(quoteId) {
            openModal(`تعديل عرض السعر #${quoteId}`, `https://alfagolden.com/system/q/1.php?quote_id=${quoteId}`);
        }

        function editWordQuote(quoteId) {
            // Check if Word file exists, if not create one
            checkAndCreateWordFile(quoteId);
        }

        function checkAndCreateWordFile(quoteId) {
            // Open directly in ONLYOFFICE
            // dx.php will handle: if file exists -> open it, if not -> create from SVZ template
            const wordFileUrl = `https://alfagolden.com/system/docs/dx.php?file=${quoteId}&editor=1`;
            openModal(`تعديل Word #${quoteId}`, wordFileUrl);
        }

        function createNewWordFile(quoteId) {
            // Generate Word file from template using template processor
            const formData = new FormData();
            formData.append('action', 'generate');
            formData.append('quote_id', quoteId);
            
            fetch('https://alfagolden.com/system/docs/template_processor.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // File generated successfully, now open it in ONLYOFFICE
                    const wordFileUrl = `https://alfagolden.com/system/docs/dx.php?file=${quoteId}&editor=1`;
                    openModal(`تعديل Word #${quoteId}`, wordFileUrl);
                } else {
                    showMessage('خطأ في إنشاء المستند: ' + data.error, 'error');
                    // Fallback to the original edit page
                    openModal(`تعديل عرض السعر #${quoteId}`, `https://alfagolden.com/system/q/1.php?quote_id=${quoteId}`);
                }
            })
            .catch(error => {
                console.error('Error generating Word file:', error);
                showMessage('خطأ في إنشاء المستند', 'error');
                // Fallback to the original edit page
                openModal(`تعديل عرض السعر #${quoteId}`, `https://alfagolden.com/system/q/1.php?quote_id=${quoteId}`);
            });
        }

        function customActionQuote(quoteId) {
            openModal(`طباعة عرض السعر #${quoteId}`, `https://alfagolden.com/system/q/6.php?quote_id=${quoteId}`);
        }

        // Modal functions
        function openModal(title, url) {
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('modalIframe').src = url;
            document.getElementById('quoteModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('quoteModal').classList.add('hidden');
            document.getElementById('modalIframe').src = 'about:blank';
            // Reload page to refresh data
            window.location.reload();
        }

        // Utility functions
        function convertToEnglishNumbers(str) {
            if (!str) return '';
            const arabicNumbers = '٠١٢٣٤٥٦٧٨٩';
            const englishNumbers = '0123456789';
            return str.toString().replace(/[٠-٩]/g, char => englishNumbers[arabicNumbers.indexOf(char)]);
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

        function sortTable(column) {
            let currentSortBy = '<?= $sortBy ?>';
            let currentSortDir = '<?= $sortDir ?>';
            let newDir = 'desc';
            if (currentSortBy === column) {
                newDir = currentSortDir === 'desc' ? 'asc' : 'desc';
            }
            // أرسل الطلب مع الفلاتر والترتيب
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';

            const actionInput = document.createElement('input');
            actionInput.value = 'filter';
            form.appendChild(actionInput);

            const filtersInput = document.createElement('input');
            filtersInput.name = 'filters';
            filtersInput.value = JSON.stringify(activeFilters);
            form.appendChild(filtersInput);

            const sortByInput = document.createElement('input');
            sortByInput.name = 'sort_by';
            sortByInput.value = column;
            form.appendChild(sortByInput);

            const sortDirInput = document.createElement('input');
            sortDirInput.name = 'sort_dir';
            sortDirInput.value = newDir;
            form.appendChild(sortDirInput);

            document.body.appendChild(form);
            form.submit();
        }

        // Event listeners
        document.addEventListener('click', function(e) {
            if (e.target === document.getElementById('filterModal')) {
                closeFilter();
            }
            if (e.target === document.getElementById('quoteModal')) {
                closeModal();
            }
            if (e.target === document.getElementById('statusModal')) {
                closeStatusModal();
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                if (!document.getElementById('quoteModal').classList.contains('hidden')) {
                    closeModal();
                } else if (!document.getElementById('filterModal').classList.contains('hidden')) {
                    closeFilter();
                } else if (!document.getElementById('statusModal').classList.contains('hidden')) {
                    closeStatusModal();
                }
            }
        });




        function downloadPDF(quoteId) {
            // Show loading message
            showMessage('جاري إنشاء ملف PDF...', 'success');
            
            // Generate PDF from template
            const formData = new FormData();
            formData.append('action', 'generate_pdf');
            formData.append('quote_id', quoteId);
            console.log(formData)
            fetch('https://alfagolden.com/system/docs/template_processor.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Download the generated PDF
                    showMessage('تم إنشاء ملف PDF بنجاح!', 'success');
                    
                    // Open in new tab
                    window.open(data.file_url, '_blank');
                } else {
                    showMessage('خطأ في إنشاء ملف PDF: ' + data.error, 'error');
                }
            })
            .catch(error => {
                console.error('Error generating PDF:', error);
                showMessage('خطأ في إنشاء ملف PDF', 'error');
            });
        }
        function refreshPDF(quoteId) {
            // Show loading message
            showMessage('جاري إنشاء ملفات جديدة...', 'success');
            
            // Generate PDF from template
            const formData = new FormData();
            formData.append('action', 'Refresh_pdf_word');
            formData.append('quote_id', quoteId);
            console.log(formData)
            fetch('https://alfagolden.com/system/docs/template_processor.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Download the generated PDF
                    showMessage('تم إنشاء ملفات word  &  PDF بنجاح!', 'success');
                    
                    // Open in new tab
                    // window.open(data.file_url, '_blank');
                } else {
                    showMessage('خطأ في إنشاء ملف PDF: ' + data.error, 'error');
                }
            })
            .catch(error => {
                console.error('Error generating PDF:', error);
                showMessage('خطأ في إنشاء ملف PDF', 'error');
            });
        }
        function exportAsContract(quoteId) {
            openModal(`تصدير كعقد #${quoteId}`, `https://alfagolden.com/system/q/7.php?quote_id=${quoteId}`);
        }
    </script>
</body>
</html>