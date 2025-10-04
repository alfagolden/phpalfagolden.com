<?php
const API_TOKEN = 'h5qAt85gtiJDAzpH51WrXPywhmnhrPWy';
const BASE_URL = 'https://base.alfagolden.com/api/database/rows/table/';
const PRODUCT_TABLE_ID = 696;

header('Content-Type: application/json');

if (!isset($_GET['category_id'])) {
    echo json_encode([]);
    exit;
}

$category_id = (int)$_GET['category_id'];
$ch = curl_init(BASE_URL . PRODUCT_TABLE_ID . '/?filter__field_7126__equals=' . $category_id . '&user_field_names=false');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Token ' . API_TOKEN,
    'Content-Type: application/json'
]);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

if ($http_code === 200) {
    $data = json_decode($response, true);
    echo json_encode($data['results'] ?? []);
} else {
    error_log("❌ فشل جلب المنتجات للقسم $category_id: HTTP $http_code, خطأ cURL: $curl_error");
    echo json_encode([]);
}
?>