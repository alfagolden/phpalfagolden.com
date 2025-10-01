<?php
session_start();

// صفحة 3.php - اختيار المصعد وحساب السعر
$baserow_url = 'https://base.alfagolden.com';
$api_token = 'h5qAt85gtiJDAzpH51WrXPywhmnhrPWy';

// معرفات الجداول
$elevators_table_id = 711;
$additions_table_id = 712;
$quotes_table_id = 704;
$actions_table_id = 707;

// حقول جدول عروض الأسعار
$quote_fields = [
    'elevator' => 6967,
    'elevators_additions' => 6971,
    'elevators_count' => 6794,
    'stops_count' => 6797,
    'total_price' => 6984,
    'price_details' => 6985,
    'discount_amount' => 6986,
    'increase_amount' => 7071
];

$user_id = $_SESSION['user_id'] ?? null;
$quote_id = $_GET['quote_id'] ?? null;

if (!$user_id) { header('Location: ../login.php'); exit; }
if (!$quote_id) { header('Location: 1.php'); exit; }

function makeBaserowRequest($url, $method = 'GET', $data = null) {
    global $api_token;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Token ' . $api_token, 'Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    if ($method === 'POST') { 
        curl_setopt($ch, CURLOPT_POST, true); 
        if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data)); 
    }
    elseif ($method === 'PATCH') { 
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH'); 
        if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data)); 
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if (!empty($curl_error)) {
        error_log("CURL Error: " . $curl_error . " for URL: " . $url);
        return ['error' => true, 'message' => 'خطأ في الاتصال: ' . $curl_error];
    }
    
    if ($http_code >= 200 && $http_code < 300) {
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON Decode Error: " . json_last_error_msg() . " for response: " . substr($response, 0, 500));
            return ['error' => true, 'message' => 'خطأ في فك ترميز الاستجابة'];
        }
        return $decoded;
    }
    
    error_log("HTTP Error $http_code for URL: $url, Response: " . substr($response, 0, 500));
    
    return ['error' => true, 'message' => 'خطأ في الاتصال - كود: ' . $http_code, 'details' => substr($response, 0, 200)];
}

// معالجة طلبات AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    try {
        switch ($_POST['action']) {
            case 'load_elevators':
                $url = $baserow_url . '/api/database/rows/table/' . $elevators_table_id . '/?user_field_names=true&size=200';
                $response = makeBaserowRequest($url);
                
                if (isset($response['error'])) {
                    echo json_encode(['success' => false, 'message' => $response['message']]);
                    exit;
                }
                
                $elevators = [];
                foreach ($response['results'] as $elevator) {
                    $elevators[] = [
                        'id' => $elevator['id'],
                        'price' => floatval($elevator['السعر'] ?? 0),
                        'capacity' => floatval($elevator['الحمولة'] ?? 0),
                        'stops' => $elevator['الوقفات'] ?? '',
                        'door_type' => $elevator['نوع الأبواب']['value'] ?? null,
                        'brand' => $elevator['البراند']['value'] ?? null,
                        'gear' => $elevator['الجير']['value'] ?? null
                    ];
                }
                
                echo json_encode(['success' => true, 'elevators' => $elevators]);
                break;
                
            case 'add_elevator':
                $price = floatval($_POST['price'] ?? 0);
                $capacity = floatval($_POST['capacity'] ?? 0);
                $stops = $_POST['stops'] ?? '';
                $door_type = $_POST['door_type'] ?? '';
                $brand = $_POST['brand'] ?? '';
                $gear = $_POST['gear'] ?? '';
                
                if ($price <= 0 || $capacity <= 0 || empty($stops)) {
                    echo json_encode(['success' => false, 'message' => 'يرجى ملء جميع الحقول بقيم صحيحة']);
                    exit;
                }
                
                $data = [
                    'السعر' => $price,
                    'الحمولة' => $capacity,
                    'الوقفات' => strval($stops)
                ];
                
                if (!empty($door_type)) {
                    $data['نوع الأبواب'] = $door_type;
                }
                
                if (!empty($brand)) {
                    $data['البراند'] = $brand;
                }
                
                if (!empty($gear)) {
                    $data['الجير'] = $gear;
                }
                
                $url = $baserow_url . '/api/database/rows/table/' . $elevators_table_id . '/?user_field_names=true';
                $response = makeBaserowRequest($url, 'POST', $data);
                
                if (isset($response['error'])) {
                    error_log("Add elevator error: " . json_encode($response));
                    echo json_encode(['success' => false, 'message' => 'خطأ في إضافة المصعد: ' . ($response['message'] ?? 'غير محدد')]);
                    exit;
                }
                
                $new_elevator = [
                    'id' => $response['id'],
                    'price' => floatval($response['السعر'] ?? 0),
                    'capacity' => floatval($response['الحمولة'] ?? 0),
                    'stops' => $response['الوقفات'] ?? '',
                    'door_type' => $response['نوع الأبواب']['value'] ?? null,
                    'brand' => $response['البراند']['value'] ?? null,
                    'gear' => $response['الجير']['value'] ?? null
                ];
                
                echo json_encode(['success' => true, 'message' => 'تم إضافة المصعد بنجاح', 'elevator' => $new_elevator]);
                break;
                
            case 'load_additions':
                $url = $baserow_url . '/api/database/rows/table/' . $additions_table_id . '/?size=200';
                $response = makeBaserowRequest($url);
                
                if (isset($response['error'])) {
                    echo json_encode(['success' => false, 'message' => $response['message']]);
                    exit;
                }
                
                $additions = [];
                foreach ($response['results'] as $addition) {
                    $additions[] = [
                        'id' => $addition['id'],
                        'name' => $addition['field_6964'] ?? '',
                        'price' => floatval($addition['field_6969'] ?? 0),
                        'price_effect' => $addition['field_6970'] ?? 'مرة واحدة',
                        'door_type' => $addition['field_7069'] ?? ''
                    ];
                }
                
                echo json_encode(['success' => true, 'additions' => $additions]);
                break;
                
            case 'select_elevator':
                $elevator_id = intval($_POST['elevator_id'] ?? 0);
                
                if (!$elevator_id) {
                    echo json_encode(['success' => false, 'message' => 'معرف المصعد مفقود']);
                    exit;
                }
                
                $data = ['field_' . $quote_fields['elevator'] => [$elevator_id]];
                $url = $baserow_url . '/api/database/rows/table/' . $quotes_table_id . '/' . $quote_id . '/';
                $response = makeBaserowRequest($url, 'PATCH', $data);
                
                if (isset($response['error'])) {
                    echo json_encode(['success' => false, 'message' => 'خطأ في حفظ المصعد']);
                    exit;
                }
                
                echo json_encode(['success' => true, 'message' => 'تم اختيار المصعد بنجاح']);
                break;
                
            case 'calculate_final_price':
                $elevators_count = max(1, intval($_POST['elevators_count'] ?? 1));
                $selected_additions = json_decode($_POST['selected_additions'] ?? '[]', true);
                $discount_amount = max(0, floatval($_POST['discount_amount'] ?? 0));
                $increase_amount = max(0, floatval($_POST['increase_amount'] ?? 0));
                
                if (!is_array($selected_additions)) {
                    $selected_additions = [];
                }
                
                $quote_url = $baserow_url . '/api/database/rows/table/' . $quotes_table_id . '/' . $quote_id . '/';
                $quote_response = makeBaserowRequest($quote_url);
                
                if (isset($quote_response['error'])) {
                    echo json_encode(['success' => false, 'message' => 'خطأ في جلب بيانات العرض: ' . $quote_response['message']]);
                    exit;
                }
                
                $elevator_data = $quote_response['field_' . $quote_fields['elevator']] ?? [];
                if (empty($elevator_data)) {
                    echo json_encode(['success' => false, 'message' => 'يرجى اختيار المصعد أولاً']);
                    exit;
                }
                
                $elevator_id = $elevator_data[0]['id'] ?? null;
                if (!$elevator_id) {
                    echo json_encode(['success' => false, 'message' => 'معرف المصعد غير صالح']);
                    exit;
                }
                
                $elevator_url = $baserow_url . '/api/database/rows/table/' . $elevators_table_id . '/' . $elevator_id . '/?user_field_names=true';
                $elevator_response = makeBaserowRequest($elevator_url);
                
                if (isset($elevator_response['error'])) {
                    echo json_encode(['success' => false, 'message' => 'خطأ في جلب بيانات المصعد: ' . $elevator_response['message']]);
                    exit;
                }
                
                if (!isset($elevator_response['السعر']) && !isset($elevator_response['الوقفات'])) {
                    echo json_encode(['success' => false, 'message' => 'بيانات المصعد غير مكتملة']);
                    exit;
                }
                
                $elevator_price = floatval($elevator_response['السعر'] ?? 0);
                $elevator_stops = max(1, intval($elevator_response['الوقفات'] ?? 1));
                
                if ($elevator_price <= 0) {
                    echo json_encode(['success' => false, 'message' => 'سعر المصعد غير صالح']);
                    exit;
                }
                
                $base_price = $elevator_price * $elevators_count;
                
                $additions_total = 0;
                $additions_details = [];
                
                if (!empty($selected_additions)) {
                    foreach ($selected_additions as $addition_id) {
                        $addition_url = $baserow_url . '/api/database/rows/table/' . $additions_table_id . '/' . $addition_id . '/';
                        $addition_response = makeBaserowRequest($addition_url);
                        
                        if (isset($addition_response['error'])) continue;
                        
                        $addition_name = $addition_response['field_6964'] ?? 'إضافة غير محددة';
                        $addition_price = floatval($addition_response['field_6969'] ?? 0);
                        $price_effect = $addition_response['field_6970'] ?? 'مرة واحدة';
                        
                        if ($addition_price <= 0) continue;
                        
                        $addition_total = 0;
                        if ($price_effect === 'لكل دور') {
                            $addition_total = $addition_price * $elevator_stops * $elevators_count;
                        } else {
                            $addition_total = $addition_price;
                        }
                        
                        $additions_total += $addition_total;
                        $additions_details[] = [
                            'name' => $addition_name,
                            'price' => $addition_price,
                            'effect' => $price_effect,
                            'total' => $addition_total,
                            'calculation' => $price_effect === 'لكل دور' ? 
                                "$addition_price × $elevator_stops × $elevators_count" : 
                                "$addition_price (مرة واحدة)"
                        ];
                    }
                }
                
                $subtotal = $base_price + $additions_total;
                
                $max_discount = ($subtotal * 0.05) + $increase_amount;
                if ($discount_amount > $max_discount) {
                    $discount_amount = $max_discount;
                }
                
                $final_total = $subtotal - $discount_amount + $increase_amount;
                
                $price_details = [
                    'elevator_id' => $elevator_id,
                    'elevator_price' => $elevator_price,
                    'elevators_count' => $elevators_count,
                    'elevator_stops' => $elevator_stops,
                    'base_price' => $base_price,
                    'additions' => $additions_details,
                    'additions_total' => $additions_total,
                    'subtotal' => $subtotal,
                    'discount_amount' => $discount_amount,
                    'increase_amount' => $increase_amount,
                    'max_discount_allowed' => $max_discount,
                    'final_total' => $final_total,
                    'calculated_at' => date('Y-m-d H:i:s')
                ];
                
                $update_data = [
                    'field_' . $quote_fields['elevators_count'] => intval($elevators_count),
                    'field_' . $quote_fields['total_price'] => round($final_total, 2)
                ];
                
                if ($elevator_stops > 0) {
                    $update_data['field_' . $quote_fields['stops_count']] = intval($elevator_stops);
                }
                
                if ($discount_amount > 0) {
                    $update_data['field_' . $quote_fields['discount_amount']] = round($discount_amount, 2);
                }
                
                if ($increase_amount > 0) {
                    $update_data['field_' . $quote_fields['increase_amount']] = round($increase_amount, 2);
                }
                
                if (!empty($price_details)) {
                    $update_data['field_' . $quote_fields['price_details']] = json_encode($price_details, JSON_UNESCAPED_UNICODE);
                }
                
                if (!empty($selected_additions)) {
                    $update_data['field_' . $quote_fields['elevators_additions']] = array_map('intval', $selected_additions);
                }
                
                error_log("Update data being sent: " . json_encode($update_data));
                
                $update_response = makeBaserowRequest($quote_url, 'PATCH', $update_data);
                
                if (isset($update_response['error'])) {
                    error_log("Main update failed, trying minimal data");
                    
                    $minimal_data = [
                        'field_' . $quote_fields['elevators_count'] => intval($elevators_count),
                        'field_' . $quote_fields['total_price'] => round($final_total, 2)
                    ];
                    
                    $retry_response = makeBaserowRequest($quote_url, 'PATCH', $minimal_data);
                    
                    if (isset($retry_response['error'])) {
                        error_log("Even minimal update failed: " . json_encode($retry_response));
                        echo json_encode([
                            'success' => false, 
                            'message' => 'خطأ في حفظ البيانات. يرجى المحاولة مرة أخرى أو التواصل مع الدعم الفني',
                            'details' => $update_response['message'] ?? 'غير محدد'
                        ]);
                        exit;
                    }
                    
                    if ($discount_amount > 0) {
                        makeBaserowRequest($quote_url, 'PATCH', ['field_' . $quote_fields['discount_amount'] => round($discount_amount, 2)]);
                    }
                    
                    if ($increase_amount > 0) {
                        makeBaserowRequest($quote_url, 'PATCH', ['field_' . $quote_fields['increase_amount'] => round($increase_amount, 2)]);
                    }
                    
                    if (!empty($selected_additions)) {
                        makeBaserowRequest($quote_url, 'PATCH', ['field_' . $quote_fields['elevators_additions'] => array_map('intval', $selected_additions)]);
                    }
                    
                    if (!empty($price_details)) {
                        makeBaserowRequest($quote_url, 'PATCH', ['field_' . $quote_fields['price_details'] => json_encode($price_details, JSON_UNESCAPED_UNICODE)]);
                    }
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'تم حفظ جميع البيانات بنجاح',
                    'calculation' => $price_details,
                    'redirect' => '4.php?quote_id=' . $quote_id
                ]);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'عملية غير مدعومة']);
                break;
        }
    } catch (Exception $e) {
        error_log("AJAX Exception: " . $e->getMessage());
        error_log("AJAX Trace: " . $e->getTraceAsString());
        echo json_encode(['success' => false, 'message' => 'حدث خطأ غير متوقع: ' . $e->getMessage()]);
    }
    exit;
}

// جلب البيانات الحالية للعرض
$quote_url = $baserow_url . '/api/database/rows/table/' . $quotes_table_id . '/' . $quote_id . '/';
$quote_response = makeBaserowRequest($quote_url);
$current_quote = $quote_response && !isset($quote_response['error']) ? $quote_response : null;

$current_elevator_id = null;
$current_elevators_count = 1;
$current_discount = 0;
$current_increase = 0;
$current_selected_additions = [9];

if ($current_quote) {
    $elevator_data = $current_quote['field_' . $quote_fields['elevator']] ?? [];
    if (!empty($elevator_data) && is_array($elevator_data)) {
        $current_elevator_id = $elevator_data[0]['id'] ?? null;
    }
    
    $current_elevators_count = max(1, intval($current_quote['field_' . $quote_fields['elevators_count']] ?? 1));
    $current_discount = floatval($current_quote['field_' . $quote_fields['discount_amount']] ?? 0);
    $current_increase = floatval($current_quote['field_' . $quote_fields['increase_amount']] ?? 0);
    
    $additions_data = $current_quote['field_' . $quote_fields['elevators_additions']] ?? [];
    if (is_array($additions_data) && !empty($additions_data)) {
        $current_selected_additions = [];
        foreach ($additions_data as $addition) {
            if (is_array($addition) && isset($addition['id'])) {
                $current_selected_additions[] = $addition['id'];
            }
        }
        if (!in_array(9, $current_selected_additions)) {
            $current_selected_additions[] = 9;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اختيار المصعد وحساب السعر</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --gold: #977e2b;
            --gold-hover: #b89635;
            --gold-light: rgba(151, 126, 43, 0.08);
            --dark-gray: #2c2c2c;
            --medium-gray: #5a5a5a;
            --light-gray: #fafafa;
            --white: #ffffff;
            --border-color: #e0e0e0;
            --radius-sm: 6px;
            --radius-md: 12px;
            --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition-fast: all 0.2s ease-in-out;
            --success-color: #28a745;
            --success-light: #f0fdf4;
            --error-color: #dc3545;
            --error-light: #fef2f2;
            --increase-color: #28a745;
            --increase-light: #f0f9f0;
            --discount-color: #dc3545;
            --discount-light: #fdf2f2;
        }

        body { font-family: 'Cairo', sans-serif; background: var(--light-gray); color: var(--dark-gray); font-size: 14px; }
        .page-loader { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.9); display: flex; align-items: center; justify-content: center; z-index: 9999; }
        .spinner { width: 24px; height: 24px; border: 2px solid var(--border-color); border-top-color: var(--gold); border-radius: 50%; animation: spin 1s linear infinite; }
        .btn-spinner { width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.5); border-top-color: var(--white); }
        @keyframes spin { 100% { transform: rotate(360deg); } }

        .container { max-width: 1200px; padding: 20px; }
        .main-card { background: var(--white); border-radius: var(--radius-md); box-shadow: var(--shadow-md); border: 1px solid var(--border-color); overflow: hidden; margin-bottom: 20px; }
        .card-header { padding: 20px 24px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; }
        .card-title { font-size: 18px; font-weight: 600; margin: 0; display: flex; align-items: center; gap: 8px; }
        .card-title i { color: var(--gold); }
        .card-body { padding: 24px; }

        .section { margin-bottom: 32px; }
        .section-title { font-size: 16px; font-weight: 600; color: var(--dark-gray); margin-bottom: 16px; display: flex; align-items: center; gap: 8px; padding-bottom: 8px; border-bottom: 2px solid var(--gold-light); }
        .section-title i { color: var(--gold); }

        .filters-container { display: flex; flex-wrap: wrap; align-items: flex-start; gap: 16px; margin-bottom: 24px; padding: 16px; background: #f6f6f6; border-radius: var(--radius-sm); border: 1px solid var(--border-color); }
        .filter-group { display: flex; flex-direction: column; gap: 8px; }
        .filter-label { font-size: 13px; font-weight: 500; color: var(--medium-gray); white-space: nowrap; }
        .filter-options { display: flex; flex-wrap: wrap; gap: 6px; }
        .filter-option { padding: 6px 12px; background: var(--white); border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 13px; cursor: pointer; transition: var(--transition-fast); white-space: nowrap; }
        .filter-option:hover { border-color: var(--gold); background: var(--gold-light); }
        .filter-option.active { background: var(--gold); color: var(--white); border-color: var(--gold); }
        
        .table-responsive { overflow-x: auto; }
        .modern-table { width: 100%; border-collapse: collapse; }
        .modern-table th, .modern-table td { padding: 12px 15px; text-align: right; border-bottom: 1px solid var(--border-color); white-space: nowrap; vertical-align: middle; }
        .modern-table thead th { background-color: #f6f6f6; font-size: 12px; font-weight: 600; color: var(--medium-gray); text-transform: uppercase; }
        .modern-table tbody tr:nth-child(2n) { background-color: var(--light-gray); }
        .modern-table tbody tr:hover { background-color: var(--gold-light); }
        .modern-table .brand-tag { background-color: var(--gold-light); color: var(--gold); padding: 4px 8px; border-radius: 4px; font-weight: 500; }
        .modern-table .not-specified { color: #9ca3af; }
        .price-wrapper { display: flex; align-items: center; gap: 6px; font-weight: 600; color: var(--dark-gray); }
        .currency-icon { width: 14px; height: 14px; vertical-align: middle; }

        .btn-select { background: var(--gold); color: var(--white); border: none; padding: 8px 16px; border-radius: var(--radius-sm); font-size: 13px; font-weight: 500; cursor: pointer; transition: var(--transition-fast); display: flex; align-items: center; justify-content: center; gap: 6px; min-width: 80px; }
        .btn-select:hover { background: var(--gold-hover); }
        .btn-select:disabled { background: var(--medium-gray); cursor: not-allowed; }
        .btn-select.selected { background: var(--success-color); }
        
        .btn-add-elevator { background: var(--gold); color: var(--white); border: none; padding: 10px 20px; border-radius: var(--radius-sm); font-size: 14px; font-weight: 500; cursor: pointer; transition: var(--transition-fast); display: flex; align-items: center; gap: 8px; }
        .btn-add-elevator:hover { background: var(--gold-hover); }
        
        .no-results-row td { text-align: center !important; padding: 40px; color: var(--medium-gray); }
        .no-results-row i { font-size: 32px; color: #d1d5db; margin-bottom: 8px; display: block; }

        .form-group { margin-bottom: 20px; }
        .form-label { display: block; font-size: 14px; font-weight: 500; color: var(--medium-gray); margin-bottom: 8px; }
        .form-control { width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 14px; transition: var(--transition-fast); }
        .form-control:focus { outline: none; border-color: var(--gold); box-shadow: 0 0 0 3px var(--gold-light); }

        .modal { display: none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); }
        .modal.show { display: flex; align-items: center; justify-content: center; }
        .modal-content { background-color: var(--white); margin: auto; padding: 0; border-radius: var(--radius-md); box-shadow: 0 4px 20px rgba(0,0,0,0.2); width: 90%; max-width: 600px; }
        .modal-header { padding: 20px 24px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; }
        .modal-header h3 { margin: 0; font-size: 18px; font-weight: 600; display: flex; align-items: center; gap: 8px; }
        .modal-close { background: none; border: none; font-size: 24px; cursor: pointer; color: var(--medium-gray); }
        .modal-close:hover { color: var(--dark-gray); }
        .modal-body { padding: 24px; }
        .modal-footer { padding: 16px 24px; border-top: 1px solid var(--border-color); display: flex; gap: 12px; justify-content: flex-end; }

        .additions-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .addition-card { background: var(--white); border: 2px solid var(--border-color); border-radius: var(--radius-sm); padding: 16px; cursor: pointer; transition: var(--transition-fast); position: relative; }
        .addition-card:hover { border-color: var(--gold); box-shadow: 0 2px 8px rgba(151, 126, 43, 0.1); }
        .addition-card.selected { border-color: var(--gold); background: var(--gold-light); }
        .addition-card.selected::before { content: ''; position: absolute; top: 8px; left: 8px; width: 20px; height: 20px; background: var(--gold); border-radius: 50%; }
        .addition-card.selected::after { content: '✓'; position: absolute; top: 8px; left: 8px; width: 20px; height: 20px; color: var(--white); font-size: 12px; font-weight: bold; display: flex; align-items: center; justify-content: center; }
        .addition-name { font-size: 16px; font-weight: 600; color: var(--dark-gray); margin-bottom: 8px; }
        .addition-price { display: flex; align-items: center; gap: 6px; margin-bottom: 8px; }
        .price-value { font-size: 18px; font-weight: 600; color: var(--gold); }
        .addition-effect { font-size: 13px; color: var(--medium-gray); background: #f3f4f6; padding: 4px 8px; border-radius: 4px; display: inline-block; }

        .calculation-card { background: linear-gradient(135deg, #f8fafc, #e2e8f0); border: 1px solid #cbd5e1; border-radius: var(--radius-sm); padding: 20px; margin: 20px 0; }
        .calc-title { font-size: 16px; font-weight: 600; color: var(--dark-gray); margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
        .calc-row { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #e2e8f0; }
        .calc-row:last-child { border-bottom: 2px solid var(--gold); font-weight: 600; color: var(--gold); font-size: 16px; }
        .calc-label { flex: 1; }
        .calc-value { font-weight: 500; display: flex; align-items: center; gap: 4px; }

        .discount-toggle { background: none; border: 1px dashed var(--discount-color); color: var(--discount-color); padding: 6px 12px; border-radius: var(--radius-sm); font-size: 12px; cursor: pointer; transition: var(--transition-fast); margin: 12px 0; }
        .discount-toggle:hover { background: var(--discount-light); }
        .discount-section { display: none; background: var(--discount-light); border: 1px solid #f5b5b5; border-radius: var(--radius-sm); padding: 12px; margin-top: 8px; }
        .discount-section.show { display: block; }
        .discount-info { font-size: 12px; color: var(--discount-color); margin-top: 4px; }

        .increase-toggle { background: none; border: 1px dashed var(--increase-color); color: var(--increase-color); padding: 6px 12px; border-radius: var(--radius-sm); font-size: 12px; cursor: pointer; transition: var(--transition-fast); margin: 12px 0; }
        .increase-toggle:hover { background: var(--increase-light); }
        .increase-section { display: none; background: var(--increase-light); border: 1px solid #b5e5b5; border-radius: var(--radius-sm); padding: 12px; margin-top: 8px; }
        .increase-section.show { display: block; }
        .increase-info { font-size: 12px; color: var(--increase-color); margin-top: 4px; }

        .message { padding: 12px; border-radius: var(--radius-sm); margin-bottom: 16px; font-size: 13px; display: none; align-items: center; gap: 8px; }
        .message.show { display: flex; }
        .message.success { background: var(--success-light); color: #166534; border: 1px solid #bbf7d0; }
        .message.error { background: var(--error-light); color: #991b1b; border: 1px solid #fecaca; }
        .message.info { background: #dbeafe; color: #1e40af; border: 1px solid #93c5fd; }

        .action-buttons { display: flex; gap: 16px; justify-content: flex-end; margin-top: 32px; }
        .btn { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 24px; border: none; border-radius: var(--radius-sm); font-size: 14px; font-weight: 600; cursor: pointer; transition: var(--transition-fast); }
        .btn-primary { background: var(--gold); color: var(--white); }
        .btn-primary:hover { background: var(--gold-hover); }
        .btn-success { background: var(--success-color); color: var(--white); }
        .btn-success:hover { background: #218838; }
        .btn-secondary { background: var(--medium-gray); color: var(--white); }
        .btn-secondary:hover { background: #4a4a4a; }
        .btn:disabled { background: var(--medium-gray); cursor: not-allowed; }

        .hidden { display: none !important; }
        
        .required-mark { color: var(--error-color); margin-right: 4px; }

        @media (max-width: 768px) {
            .additions-grid { grid-template-columns: 1fr; }
            .filters-container { flex-direction: column; align-items: stretch; }
            .filter-group { flex-direction: column; align-items: flex-start; gap: 8px; }
            .filter-options { flex-wrap: wrap; }
            .action-buttons { flex-direction: column; }
            .modal-content { width: 95%; }
        }
    </style>
</head>
<body>
    <div id="pageLoader" class="page-loader"><div class="spinner"></div></div>

    <div class="container">
        <!-- قسم اختيار المصعد -->
        <div class="main-card" id="elevatorSection">
            <div class="card-header">
                <h1 class="card-title"><i class="fas fa-elevator"></i> اختيار المصعد</h1>
                <button class="btn-add-elevator" onclick="openAddElevatorModal()">
                    <i class="fas fa-plus"></i> إضافة مصعد جديد
                </button>
            </div>
            
            <div class="card-body">
                <div id="messageContainer" class="message"></div>
                
                <div class="filters-container" id="filtersContainer">
                    <!-- سيتم تحميل الفلاتر هنا ديناميكياً -->
                </div>

                <div class="table-responsive">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>البراند</th>
                                <th>الوقفات</th>
                                <th>نوع الأبواب</th>
                                <th>الجير</th>
                                <th>الحمولة (كجم)</th>
                                <th>السعر</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="elevatorsTableBody">
                            <!-- سيتم تحميل المصاعد هنا -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- قسم التفاصيل والحساب -->
        <div class="main-card" id="detailsSection" style="display: none;">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-calculator"></i> تفاصيل الطلب وحساب السعر</h2>
            </div>
            
            <div class="card-body">
                <div id="detailsMessage" class="message"></div>

                <!-- عدد المصاعد -->
                <div class="section">
                    <div class="section-title">
                        <i class="fas fa-building"></i>
                        عدد المصاعد
                    </div>
                    <div class="form-group">
                        <label class="form-label">عدد المصاعد المطلوبة</label>
                        <input type="number" id="elevatorsCount" class="form-control" min="1" max="10" 
                               value="<?php echo $current_elevators_count; ?>" style="max-width: 200px;">
                    </div>
                </div>

                <!-- الإضافات -->
                <div class="section">
                    <div class="section-title">
                        <i class="fas fa-plus-circle"></i>
                        إضافات المصعد (اختيارية)
                    </div>
                    <div id="additionsGrid" class="additions-grid">
                        <!-- سيتم تحميل الإضافات هنا -->
                    </div>
                </div>

                <!-- حساب السعر -->
                <div class="calculation-card" id="calculationCard">
                    <div class="calc-title">
                        <i class="fas fa-receipt"></i>
                        تفاصيل السعر
                    </div>
                    <div id="calculationContent">
                        <!-- سيتم عرض الحساب هنا -->
                    </div>
                    
                    <!-- التخفيض -->
                    <button type="button" class="discount-toggle" onclick="toggleDiscount()">
                        <i class="fas fa-tag"></i> إضافة تخفيض
                    </button>
                    <div class="discount-section" id="discountSection">
                        <div class="form-group" style="margin: 0;">
                            <label class="form-label">مبلغ التخفيض</label>
                            <input type="number" id="discountAmount" class="form-control" min="0" step="1" 
                                   value="<?php echo $current_discount; ?>" placeholder="0" style="max-width: 200px;">
                            <div class="discount-info" id="discountInfo">الحد الأقصى: 0 ريال</div>
                        </div>
                    </div>

                    <!-- الزيادة -->
                    <button type="button" class="increase-toggle" onclick="toggleIncrease()">
                        <i class="fas fa-plus-square"></i> إضافة زيادة
                    </button>
                    <div class="increase-section" id="increaseSection">
                        <div class="form-group" style="margin: 0;">
                            <label class="form-label">مبلغ الزيادة</label>
                            <input type="number" id="increaseAmount" class="form-control" min="0" step="1" 
                                   value="<?php echo $current_increase; ?>" placeholder="0" style="max-width: 200px;">
                            <div class="increase-info">يمكن إضافة أي مبلغ إضافي للسعر</div>
                        </div>
                    </div>
                </div>

                <div class="action-buttons">
                    <button type="button" id="calculateBtn" class="btn btn-primary" onclick="calculateFinalPrice()">
                        <i class="fas fa-calculator"></i>
                        <span class="btn-text">حساب وحفظ السعر النهائي</span>
                        <div class="spinner btn-spinner hidden"></div>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal إضافة مصعد -->
    <div id="addElevatorModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> إضافة مصعد جديد</h3>
                <button class="modal-close" onclick="closeAddElevatorModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="modalMessage" class="message"></div>
                <div class="form-group">
                    <label class="form-label"><span class="required-mark">*</span>السعر</label>
                    <input type="number" id="newElevatorPrice" class="form-control" min="1" step="1" placeholder="أدخل السعر" required>
                </div>
                <div class="form-group">
                    <label class="form-label"><span class="required-mark">*</span>الحمولة (كجم)</label>
                    <input type="number" id="newElevatorCapacity" class="form-control" min="1" step="1" placeholder="مثال: 630" required>
                </div>
                <div class="form-group">
                    <label class="form-label"><span class="required-mark">*</span>عدد الوقفات</label>
                    <input type="number" id="newElevatorStops" class="form-control" min="1" step="1" placeholder="مثال: 4" required>
                </div>
                <div class="form-group">
                    <label class="form-label">نوع الأبواب</label>
                    <select id="newElevatorDoorType" class="form-control">
                        <option value="">اختر نوع الأبواب (اختياري)</option>
                        <option value="أوتوماتيك">أوتوماتيك</option>
                        <option value="نصف أوتوماتيك">نصف أوتوماتيك</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">البراند</label>
                    <select id="newElevatorBrand" class="form-control">
                        <option value="">اختر البراند (اختياري)</option>
                        <option value="ALFA PRO">ALFA PRO</option>
                        <option value="ALFA ELITE">ALFA ELITE</option>
                        <option value="MITSUTECH">MITSUTECH</option>
                        <option value="FUJI">FUJI</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">الجير</label>
                    <select id="newElevatorGear" class="form-control">
                        <option value="">اختر الجير (اختياري)</option>
                        <option value="MR">MR</option>
                        <option value="MRL">MRL</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeAddElevatorModal()">إلغاء</button>
                <button class="btn btn-primary" id="saveElevatorBtn" onclick="saveNewElevator()">
                    <span class="btn-text">حفظ المصعد</span>
                    <div class="spinner btn-spinner hidden"></div>
                </button>
            </div>
        </div>
    </div>

    <script>
        class ElevatorPricingManager {
            constructor() {
                this.allElevators = [];
                this.allAdditions = [];
                this.selectedAdditions = new Set(<?php echo json_encode($current_selected_additions); ?>);
                this.selectedElevator = null;
                this.filters = { brand: '', stops: '', door_type: '', gear: '', capacity: '' };
                this.isLoading = false;
                this.init();
            }
            
            init() {
                this.bindEvents();
                this.loadAllData();
                setTimeout(() => { document.getElementById('pageLoader').style.display = 'none'; }, 800);
            }
            
            bindEvents() {
                document.getElementById('elevatorsCount').addEventListener('input', () => {
                    this.updateCalculation();
                });
                
                document.getElementById('discountAmount').addEventListener('input', () => {
                    this.validateDiscount();
                    this.updateCalculation();
                });

                document.getElementById('increaseAmount').addEventListener('input', () => {
                    this.updateCalculation();
                });
            }
            
            async loadAllData() {
                this.isLoading = true;
                this.renderTableSpinner();
                
                try {
                    await this.loadElevators();
                    await this.loadAdditions();
                    this.buildDynamicFilters();
                    this.applyFilters();
                    this.renderAdditions();
                    
                    <?php if ($current_elevator_id): ?>
                        this.selectedElevator = this.allElevators.find(e => e.id === <?php echo $current_elevator_id; ?>);
                        if (this.selectedElevator) {
                            this.applyElevatorFilters(this.selectedElevator);
                            this.showDetailsSection();
                        }
                    <?php endif; ?>
                } catch (error) {
                    this.showMessage('خطأ في تحميل البيانات', 'error');
                } finally {
                    this.isLoading = false;
                }
            }
            
            async loadElevators() {
                const response = await this.makeRequest('load_elevators', {});
                if (response.success) {
                    this.allElevators = response.elevators;
                } else {
                    throw new Error(response.message || 'خطأ في تحميل المصاعد');
                }
            }
            
            async loadAdditions() {
                const response = await this.makeRequest('load_additions', {});
                if (response.success) {
                    this.allAdditions = response.additions;
                } else {
                    throw new Error(response.message || 'خطأ في تحميل الإضافات');
                }
            }
            
            buildDynamicFilters() {
                const filtersContainer = document.getElementById('filtersContainer');
                
                const filterConfigs = [
                    { key: 'brand', label: 'البراند' },
                    { key: 'stops', label: 'الوقفات' },
                    { key: 'door_type', label: 'الأبواب' },
                    { key: 'gear', label: 'الجير' },
                    { key: 'capacity', label: 'الحمولة' }
                ];
                
                let filtersHtml = '';
                
                filterConfigs.forEach(config => {
                    const uniqueValues = [...new Set(
                        this.allElevators
                            .map(e => e[config.key])
                            .filter(v => v !== null && v !== undefined && v !== '')
                    )].sort((a, b) => {
                        if (typeof a === 'number' && typeof b === 'number') {
                            return a - b;
                        }
                        return String(a).localeCompare(String(b), 'ar');
                    });
                    
                    if (uniqueValues.length > 0) {
                        filtersHtml += `
                            <div class="filter-group">
                                <div class="filter-label">${config.label}:</div>
                                <div class="filter-options">
                                    <div class="filter-option ${this.filters[config.key] === '' ? 'active' : ''}" data-filter="${config.key}" data-value="">الكل</div>
                                    ${uniqueValues.map(value => 
                                        `<div class="filter-option ${String(this.filters[config.key]) === String(value) ? 'active' : ''}" data-filter="${config.key}" data-value="${value}">${value}</div>`
                                    ).join('')}
                                </div>
                            </div>
                        `;
                    }
                });
                
                filtersContainer.innerHTML = filtersHtml;
                
                document.querySelectorAll('.filter-option').forEach(option => {
                    option.addEventListener('click', (e) => {
                        if (this.isLoading) return;
                        const target = e.currentTarget;
                        target.parentElement.querySelectorAll('.filter-option').forEach(opt => opt.classList.remove('active'));
                        target.classList.add('active');
                        this.filters[target.dataset.filter] = target.dataset.value;
                        this.applyFilters();
                    });
                });
            }
            
            applyElevatorFilters(elevator) {
                this.filters = {
                    brand: elevator.brand || '',
                    stops: elevator.stops || '',
                    door_type: elevator.door_type || '',
                    gear: elevator.gear || '',
                    capacity: String(elevator.capacity) || ''
                };
                
                this.buildDynamicFilters();
                this.applyFilters();
            }
            
            filterAdditionsByDoorType() {
                if (!this.selectedElevator) {
                    return this.allAdditions;
                }
                
                const elevatorDoorType = this.selectedElevator.door_type;
                
                return this.allAdditions.filter(addition => {
                    if (!addition.door_type || addition.door_type.trim() === '') {
                        return true;
                    }
                    return addition.door_type === elevatorDoorType;
                });
            }
            
            applyFilters() {
                const filteredElevators = this.allElevators.filter(elevator => 
                    Object.keys(this.filters).every(key => 
                        !this.filters[key] || String(elevator[key]) === String(this.filters[key])
                    )
                );
                this.renderTable(filteredElevators);
            }

            renderTableSpinner() {
                const tableBody = document.getElementById('elevatorsTableBody');
                tableBody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 40px;"><div class="spinner"></div></td></tr>`;
            }
            
            renderTable(elevators) {
                const tableBody = document.getElementById('elevatorsTableBody');
                tableBody.innerHTML = '';

                if (elevators.length === 0) {
                    tableBody.innerHTML = `<tr class="no-results-row"><td colspan="7"><i class="fas fa-search"></i><span>لا توجد مصاعد تطابق بحثك.</span></td></tr>`;
                    return;
                }

                tableBody.innerHTML = elevators.map(e => this.createRowHtml(e)).join('');
                
                tableBody.querySelectorAll('.btn-select').forEach(button => {
                    button.addEventListener('click', (e) => this.handleElevatorSelection(e));
                });
            }

            createRowHtml(elevator) {
                const notSpecified = '<span class="not-specified">غير محدد</span>';
                const isSelected = this.selectedElevator && this.selectedElevator.id === elevator.id;
                
                return `
                    <tr ${isSelected ? 'style="background-color: var(--gold-light);"' : ''}>
                        <td>${elevator.brand ? `<span class="brand-tag">${elevator.brand}</span>` : notSpecified}</td>
                        <td>${elevator.stops || notSpecified}</td>
                        <td>${elevator.door_type || notSpecified}</td>
                        <td>${elevator.gear || notSpecified}</td>
                        <td>${elevator.capacity || notSpecified}</td>
                        <td>
                            <div class="price-wrapper">
                                <span>${this.formatPrice(elevator.price)}</span>
                                <img src="https://alfagolden.com/images/sar.svg" class="currency-icon" alt="عملة ريال سعودي">
                            </div>
                        </td>
                        <td>
                            <button class="btn-select ${isSelected ? 'selected' : ''}" data-elevator-id="${elevator.id}">
                                <span class="btn-text">${isSelected ? 'محدد' : 'اختيار'}</span>
                                <div class="spinner btn-spinner hidden"></div>
                            </button>
                        </td>
                    </tr>
                `;
            }
            
            async handleElevatorSelection(event) {
                if (this.isLoading) return;

                const button = event.currentTarget;
                const elevatorId = parseInt(button.dataset.elevatorId);
                
                this.setLoading(button, true);

                try {
                    const response = await this.makeRequest('select_elevator', {
                        elevator_id: elevatorId
                    });
                    
                    if (response.success) {
                        this.selectedElevator = this.allElevators.find(e => e.id === elevatorId);
                        this.showMessage('تم اختيار المصعد بنجاح', 'success');
                        
                        this.applyElevatorFilters(this.selectedElevator);
                        
                        this.showDetailsSection();
                    } else {
                        this.showMessage('خطأ: ' + response.message, 'error');
                    }
                } catch (error) {
                    this.showMessage('حدث خطأ فني غير متوقع', 'error');
                } finally {
                    this.setLoading(button, false);
                }
            }
            
            showDetailsSection() {
                document.getElementById('detailsSection').style.display = 'block';
                document.getElementById('detailsSection').scrollIntoView({ behavior: 'smooth' });
                this.renderAdditions();
                this.updateCalculation();
            }
            
            renderAdditions() {
                const grid = document.getElementById('additionsGrid');
                const filteredAdditions = this.filterAdditionsByDoorType();
                
                if (filteredAdditions.length === 0) {
                    grid.innerHTML = '<p style="text-align: center; color: var(--medium-gray); grid-column: 1 / -1;">لا توجد إضافات متاحة لهذا النوع من المصاعد</p>';
                    return;
                }
                
                grid.innerHTML = filteredAdditions.map(addition => `
                    <div class="addition-card ${this.selectedAdditions.has(addition.id) ? 'selected' : ''}" 
                         data-addition-id="${addition.id}" onclick="pricingManager.toggleAddition(${addition.id})">
                        <div class="addition-name">${addition.name}</div>
                        <div class="addition-price">
                            <span class="price-value">${this.formatPrice(addition.price)}</span>
                            <img src="https://alfagolden.com/images/sar.svg" class="currency-icon" alt="ريال سعودي">
                        </div>
                        <div class="addition-effect">${addition.price_effect}</div>
                    </div>
                `).join('');
            }
            
            toggleAddition(additionId) {
                if (this.selectedAdditions.has(additionId)) {
                    this.selectedAdditions.delete(additionId);
                } else {
                    this.selectedAdditions.add(additionId);
                }
                
                const element = document.querySelector(`[data-addition-id="${additionId}"]`);
                if (element) {
                    element.classList.toggle('selected', this.selectedAdditions.has(additionId));
                }
                
                this.updateCalculation();
            }
            
            updateCalculation() {
                if (!this.selectedElevator) return;
                
                const elevatorsCount = Math.max(1, parseInt(document.getElementById('elevatorsCount').value) || 1);
                const discountAmount = parseFloat(document.getElementById('discountAmount').value) || 0;
                const increaseAmount = parseFloat(document.getElementById('increaseAmount').value) || 0;
                
                const basePrice = this.selectedElevator.price * elevatorsCount;
                
                let additionsTotal = 0;
                let additionsHtml = '';
                
                const elevatorStops = parseInt(this.selectedElevator.stops) || 1;
                
                this.selectedAdditions.forEach(additionId => {
                    const addition = this.allAdditions.find(a => a.id === additionId);
                    if (!addition) return;
                    
                    let additionTotal = 0;
                    let calculationText = '';
                    
                    if (addition.price_effect === 'لكل دور') {
                        additionTotal = addition.price * elevatorStops * elevatorsCount;
                        calculationText = `${this.formatPrice(addition.price)} × ${elevatorStops} × ${elevatorsCount}`;
                    } else {
                        additionTotal = addition.price;
                        calculationText = `${this.formatPrice(addition.price)} (مرة واحدة)`;
                    }
                    
                    additionsTotal += additionTotal;
                    additionsHtml += `
                        <div class="calc-row">
                            <div class="calc-label">${addition.name} (${calculationText})</div>
                            <div class="calc-value">
                                ${this.formatPrice(additionTotal)}
                                <img src="https://alfagolden.com/images/sar.svg" class="currency-icon" alt="ريال">
                            </div>
                        </div>
                    `;
                });
                
                const subtotal = basePrice + additionsTotal;
                const maxDiscount = (subtotal * 0.05) + increaseAmount;
                const finalTotal = subtotal - discountAmount + increaseAmount;
                
                document.getElementById('discountInfo').textContent = `الحد الأقصى: ${this.formatPrice(maxDiscount)} ريال`;
                
                let calculationHtml = `
                    <div class="calc-row">
                        <div class="calc-label">السعر الأساسي (${this.formatPrice(this.selectedElevator.price)} × ${elevatorsCount})</div>
                        <div class="calc-value">
                            ${this.formatPrice(basePrice)}
                            <img src="https://alfagolden.com/images/sar.svg" class="currency-icon" alt="ريال">
                        </div>
                    </div>
                `;
                
                if (additionsHtml) {
                    calculationHtml += additionsHtml;
                    calculationHtml += `
                        <div class="calc-row">
                            <div class="calc-label">مجموع الإضافات</div>
                            <div class="calc-value">
                                ${this.formatPrice(additionsTotal)}
                                <img src="https://alfagolden.com/images/sar.svg" class="currency-icon" alt="ريال">
                            </div>
                        </div>
                    `;
                }
                
                if (discountAmount > 0) {
                    calculationHtml += `
                        <div class="calc-row">
                            <div class="calc-label">التخفيض</div>
                            <div class="calc-value" style="color: var(--discount-color);">
                                -${this.formatPrice(discountAmount)}
                                <img src="https://alfagolden.com/images/sar.svg" class="currency-icon" alt="ريال">
                            </div>
                        </div>
                    `;
                }

                if (increaseAmount > 0) {
                    calculationHtml += `
                        <div class="calc-row">
                            <div class="calc-label">الزيادة</div>
                            <div class="calc-value" style="color: var(--increase-color);">
                                +${this.formatPrice(increaseAmount)}
                                <img src="https://alfagolden.com/images/sar.svg" class="currency-icon" alt="ريال">
                            </div>
                        </div>
                    `;
                }
                
                calculationHtml += `
                    <div class="calc-row">
                        <div class="calc-label">المجموع النهائي</div>
                        <div class="calc-value">
                            ${this.formatPrice(finalTotal)}
                            <img src="https://alfagolden.com/images/sar.svg" class="currency-icon" alt="ريال">
                        </div>
                    </div>
                `;
                
                document.getElementById('calculationContent').innerHTML = calculationHtml;
            }
            
            validateDiscount() {
                if (!this.selectedElevator) return;
                
                const elevatorsCount = Math.max(1, parseInt(document.getElementById('elevatorsCount').value) || 1);
                const increaseAmount = parseFloat(document.getElementById('increaseAmount').value) || 0;
                const basePrice = this.selectedElevator.price * elevatorsCount;
                
                let additionsTotal = 0;
                const elevatorStops = parseInt(this.selectedElevator.stops) || 1;
                
                this.selectedAdditions.forEach(additionId => {
                    const addition = this.allAdditions.find(a => a.id === additionId);
                    if (!addition) return;
                    
                    if (addition.price_effect === 'لكل دور') {
                        additionsTotal += addition.price * elevatorStops * elevatorsCount;
                    } else {
                        additionsTotal += addition.price;
                    }
                });
                
                const subtotal = basePrice + additionsTotal;
                const maxDiscount = (subtotal * 0.05) + increaseAmount;
                const discountInput = document.getElementById('discountAmount');
                
                if (parseFloat(discountInput.value) > maxDiscount) {
                    discountInput.value = Math.floor(maxDiscount);
                }
            }
            
            async calculateFinalPrice() {
                if (!this.selectedElevator) {
                    this.showDetailsMessage('يرجى اختيار المصعد أولاً', 'error');
                    return;
                }
                
                const button = document.getElementById('calculateBtn');
                this.setLoading(button, true);
                
                try {
                    const elevatorsCount = parseInt(document.getElementById('elevatorsCount').value) || 1;
                    const discountAmount = parseFloat(document.getElementById('discountAmount').value) || 0;
                    const increaseAmount = parseFloat(document.getElementById('increaseAmount').value) || 0;
                    
                    const data = {
                        elevators_count: elevatorsCount,
                        selected_additions: JSON.stringify(Array.from(this.selectedAdditions)),
                        discount_amount: discountAmount,
                        increase_amount: increaseAmount
                    };
                    
                    console.log('Sending data:', data);
                    
                    const response = await this.makeRequest('calculate_final_price', data);
                    
                    console.log('Response:', response);
                    
                    if (response.success) {
                        this.showDetailsMessage('تم حفظ جميع البيانات بنجاح', 'success');
                        setTimeout(() => {
                            if (response.redirect) {
                                window.location.href = response.redirect;
                            }
                        }, 1500);
                    } else {
                        const errorMsg = response.message || 'حدث خطأ غير محدد';
                        this.showDetailsMessage('خطأ: ' + errorMsg, 'error');
                        
                        if (response.details) {
                            console.error('Error details:', response.details);
                        }
                    }
                } catch (error) {
                    console.error('Calculation error:', error);
                    this.showDetailsMessage('حدث خطأ في الاتصال بالخادم', 'error');
                } finally {
                    this.setLoading(button, false);
                }
            }
            
            async addNewElevator(elevatorData) {
                try {
                    const response = await this.makeRequest('add_elevator', elevatorData);
                    
                    if (response.success) {
                        this.allElevators.push(response.elevator);
                        return { success: true, elevator: response.elevator };
                    } else {
                        return { success: false, message: response.message };
                    }
                } catch (error) {
                    return { success: false, message: 'حدث خطأ في الاتصال' };
                }
            }
            
            async selectElevatorById(elevatorId) {
                try {
                    const response = await this.makeRequest('select_elevator', {
                        elevator_id: elevatorId
                    });
                    
                    if (response.success) {
                        this.selectedElevator = this.allElevators.find(e => e.id === elevatorId);
                        
                        await new Promise(resolve => setTimeout(resolve, 300));
                        
                        this.applyElevatorFilters(this.selectedElevator);
                        
                        await new Promise(resolve => setTimeout(resolve, 100));
                        
                        this.showDetailsSection();
                        return true;
                    }
                    return false;
                } catch (error) {
                    return false;
                }
            }
            
            formatPrice(price) {
                if (price === undefined || price === null) return '0';
                return new Intl.NumberFormat('en-US', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0
                }).format(price);
            }
            
            async makeRequest(action, data) {
                console.log(`Making request: ${action}`, data);
                
                const formData = new FormData();
                formData.append('ajax', '1');
                formData.append('action', action);
                Object.keys(data).forEach(key => formData.append(key, data[key]));
                
                try {
                    const response = await fetch(window.location.href, { method: 'POST', body: formData });
                    const result = await response.json();
                    
                    console.log(`Response for ${action}:`, result);
                    return result;
                } catch (error) {
                    console.error(`Error in ${action}:`, error);
                    throw error;
                }
            }
            
            showMessage(message, type) {
                const container = document.getElementById('messageContainer');
                const iconClass = type === 'success' ? 'fa-check-circle' : 'fa-times-circle';
                container.className = `message ${type} show`;
                container.innerHTML = `<i class="fas ${iconClass}"></i> ${message}`;
                setTimeout(() => container.classList.remove('show'), 4000);
            }
            
            showDetailsMessage(message, type) {
                const container = document.getElementById('detailsMessage');
                const iconClass = type === 'success' ? 'fa-check-circle' : 'fa-times-circle';
                container.className = `message ${type} show`;
                container.innerHTML = `<i class="fas ${iconClass}"></i> ${message}`;
                setTimeout(() => container.classList.remove('show'), 4000);
            }
            
            showModalMessage(message, type) {
                const container = document.getElementById('modalMessage');
                const iconClass = type === 'success' ? 'fa-check-circle' : 'fa-times-circle';
                container.className = `message ${type} show`;
                container.innerHTML = `<i class="fas ${iconClass}"></i> ${message}`;
                setTimeout(() => container.classList.remove('show'), 4000);
            }
            
            setLoading(button, isLoading) {
                this.isLoading = isLoading;
                const text = button.querySelector('.btn-text');
                const spinner = button.querySelector('.spinner');

                button.disabled = isLoading;
                text.classList.toggle('hidden', isLoading);
                spinner.classList.toggle('hidden', !isLoading);

                document.querySelectorAll('.btn-select').forEach(b => {
                    if (b !== button) b.disabled = isLoading;
                });
            }
        }
        
        let pricingManager;
        
        function toggleDiscount() {
            const section = document.getElementById('discountSection');
            section.classList.toggle('show');
            if (section.classList.contains('show')) {
                document.getElementById('discountAmount').focus();
            }
        }

        function toggleIncrease() {
            const section = document.getElementById('increaseSection');
            section.classList.toggle('show');
            if (section.classList.contains('show')) {
                document.getElementById('increaseAmount').focus();
            }
        }
        
        function calculateFinalPrice() {
            pricingManager.calculateFinalPrice();
        }
        
        function openAddElevatorModal() {
            document.getElementById('addElevatorModal').classList.add('show');
            document.getElementById('modalMessage').classList.remove('show');
            document.getElementById('newElevatorPrice').value = '';
            document.getElementById('newElevatorCapacity').value = '';
            document.getElementById('newElevatorStops').value = '';
            document.getElementById('newElevatorDoorType').value = '';
            document.getElementById('newElevatorBrand').value = '';
            document.getElementById('newElevatorGear').value = '';
        }
        
        function closeAddElevatorModal() {
            document.getElementById('addElevatorModal').classList.remove('show');
        }
        
        async function saveNewElevator() {
            const price = parseFloat(document.getElementById('newElevatorPrice').value);
            const capacity = parseFloat(document.getElementById('newElevatorCapacity').value);
            const stops = document.getElementById('newElevatorStops').value.trim();
            const door_type = document.getElementById('newElevatorDoorType').value;
            const brand = document.getElementById('newElevatorBrand').value;
            const gear = document.getElementById('newElevatorGear').value;
            
            if (!price || price <= 0) {
                pricingManager.showModalMessage('يرجى إدخال السعر بشكل صحيح', 'error');
                return;
            }
            
            if (!capacity || capacity <= 0) {
                pricingManager.showModalMessage('يرجى إدخال الحمولة بشكل صحيح', 'error');
                return;
            }
            
            if (!stops || stops === '') {
                pricingManager.showModalMessage('يرجى إدخال عدد الوقفات', 'error');
                return;
            }
            
            const button = document.getElementById('saveElevatorBtn');
            const text = button.querySelector('.btn-text');
            const spinner = button.querySelector('.spinner');
            
            button.disabled = true;
            text.classList.add('hidden');
            spinner.classList.remove('hidden');
            
            const result = await pricingManager.addNewElevator({
                price, capacity, stops, door_type, brand, gear
            });
            
            button.disabled = false;
            text.classList.remove('hidden');
            spinner.classList.add('hidden');
            
            if (result.success) {
                pricingManager.showModalMessage('تم إضافة المصعد بنجاح', 'success');
                
                setTimeout(async () => {
                    closeAddElevatorModal();
                    pricingManager.showMessage('جاري تحديد المصعد الجديد...', 'info');
                    
                    await new Promise(resolve => setTimeout(resolve, 500));
                    
                    const selected = await pricingManager.selectElevatorById(result.elevator.id);
                    
                    if (selected) {
                        pricingManager.showMessage('تم إضافة وتحديد المصعد الجديد بنجاح', 'success');
                    }
                }, 800);
            } else {
                pricingManager.showModalMessage(result.message || 'حدث خطأ في إضافة المصعد', 'error');
            }
        }
        
        document.addEventListener('DOMContentLoaded', () => {
            pricingManager = new ElevatorPricingManager();
        });
        
        window.onclick = function(event) {
            const modal = document.getElementById('addElevatorModal');
            if (event.target == modal) {
                closeAddElevatorModal();
            }
        }
    </script>
</body>
</html>