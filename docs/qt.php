<?php
/******************************************************
 * ONLYOFFICE PHP Integration (Production)
 * ملف واحد: qt.php
 * وظيفة: عرض المحرّر + استقبال الحفظ (callback) + التحقق من JWT + حفظ الملف
 * هام: تأكد من تمرير Authorization إلى PHP في Nginx:
 *      fastcgi_param HTTP_AUTHORIZATION $http_authorization;
 ******************************************************/

// ======= إعداداتك =======
$onlyoffice_server_url = "https://office.alfagolden.com";
$document_public_url   = "https://alfagolden.com/docs/qt.docx";
$document_local_path   = "/var/www/files/main/public/docs/qt.docx";
$self_url              = "https://alfagolden.com/docs/qt.php";
$jwt_secret            = "EzZhbzey1tTFYqbCllCIWQNC7RmDbLbZ";

// (اختياري) عطّل طباعة الأخطاء للمتصفح
// error_reporting(0);
// ini_set('display_errors', 0);

// ======= دوال مساعدة =======
function base64UrlEncode($data){ return rtrim(strtr(base64_encode($data), '+/', '-_'), '='); }
function base64UrlDecode($data){ return base64_decode(strtr($data, '-_', '+/')); }

function createJwtToken(array $payload, $secret){
    $header  = ['alg' => 'HS256', 'typ' => 'JWT'];
    $h = base64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES));
    $p = base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));
    $sig = hash_hmac('sha256', "$h.$p", $secret, true);
    $s = base64UrlEncode($sig);
    return "$h.$p.$s";
}

function verifyJwtToken($token, $secret){
    $parts = explode('.', $token);
    if(count($parts) !== 3) return [false, "Malformed token"];
    list($h, $p, $s) = $parts;
    $header = json_decode(base64UrlDecode($h), true);
    if(!$header || ($header['alg'] ?? '') !== 'HS256') return [false, "Unsupported alg"];
    $calc = base64UrlEncode(hash_hmac('sha256', "$h.$p", $secret, true));
    if(!hash_equals($calc, $s)) return [false, "Signature mismatch"];
    $payload = json_decode(base64UrlDecode($p), true);
    return [true, $payload];
}

function http_download($url, $timeout=60){
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HTTPHEADER     => ['User-Agent: ONLYOFFICE-PHP/1.0'],
    ]);
    $body = curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $ct   = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);
    return [$code, $err, $body, $ct];
}

function getBearerTokenFromHeaders(){
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    foreach(['Authorization','authorization'] as $h){
        if(!empty($headers[$h]) && stripos($headers[$h], 'Bearer ') === 0){
            return trim(substr($headers[$h], 7));
        }
    }
    return null;
}

// ======= معالجة الحفظ (Callback) =======
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    header('X-Content-Type-Options: nosniff');

    $raw  = file_get_contents('php://input');
    $json = json_decode($raw, true) ?: [];
    $token = getBearerTokenFromHeaders();
    if(!$token && isset($json['token'])) $token = $json['token'];

    // تحقق JWT
    if(!$token){
        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(["error"=>1, "message"=>"No token provided"]);
        exit;
    }
    list($ok, $payload_or_err) = verifyJwtToken($token, $jwt_secret);
    if(!$ok){
        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(["error"=>1, "message"=>"JWT verification failed"]);
        exit;
    }

    // حالات OnlyOffice
    $status  = $json['status'] ?? null;
    $fileUrl = $json['url'] ?? ($json['downloadUri'] ?? null);

    // حفظ فقط عندما يكون جاهز للحفظ أو ForceSave
    if(in_array($status, [2,6], true) && $fileUrl){
        // نزّل الملف من Document Server
        list($code, $err, $body, $ct) = http_download($fileUrl, 60);
        if(!($code >= 200 && $code < 300) || $body === false){
            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(["error"=>1, "message"=>"Download failed"]);
            exit;
        }

        // تأكد من وجود المجلد ثم اكتب الملف
        $dir = dirname($document_local_path);
        if(!is_dir($dir) && !@mkdir($dir, 0775, true)){
            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(["error"=>1, "message"=>"Storage path not writable"]);
            exit;
        }

        // خذ نسخة احتياطية بسيطة (اختياري)
        if(file_exists($document_local_path)){
            @copy($document_local_path, $document_local_path . ".bak-" . date('Ymd-His'));
        }

        if(@file_put_contents($document_local_path, $body) === false){
            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(["error"=>1, "message"=>"Write failed"]);
            exit;
        }
        @chmod($document_local_path, 0664);

        // نجاح
        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(["error"=>0]);
        exit;
    }

    // حالات أخرى: رجّع نجاح بدون حفظ
    http_response_code(200);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(["error"=>0]);
    exit;
}

// ======= عرض المحرّر (GET) =======

// وثّق مفتاح المستند (key) بثبات مع تغيّر المحتوى فقط
if(file_exists($document_local_path)){
    $document_key = md5_file($document_local_path); // ثابت ضمن الإصدار الحالي
} else {
    $document_key = md5($document_public_url); // احتياط
}

// تكوين المحرّر
$config = [
    "type"         => "desktop",
    "documentType" => "word",
    "document" => [
        "title"    => "qt.docx",
        "url"      => $document_public_url,        // يجب أن يكون Reachable من Document Server
        "fileType" => "docx",
        "key"      => $document_key,
        // "permissions" => ["edit" => true], // اختياري
    ],
    "editorConfig" => [
        "mode"        => "edit",
        "lang"        => "ar",
        "callbackUrl" => $self_url,                // مهم للحفظ
        "user"        => [ "id"=>"user-01", "name"=>"Template Admin" ],
        "customization" => [
            "autosave"  => true,  // حفظ تلقائي
            "forcesave" => true,  // يظهر زر Force Save
        ]
    ]
];

// وقّع الإعداد بـJWT (مطلوب إذا كان JWT مفعّل في Document Server)
$config['token'] = createJwtToken($config, $jwt_secret);
$config['document']['token'] = $config['token'];
$config['editorConfig']['token'] = $config['token'];

// ======= إخراج HTML =======
?><!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>تعديل المستند - qt.docx</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
html,body{height:100%;margin:0}
#editor{height:100vh}
</style>
</head>
<body>
<div id="editor"></div>
<script src="<?php echo htmlspecialchars(rtrim($onlyoffice_server_url,'/').'/web-apps/apps/api/documents/api.js', ENT_QUOTES, 'UTF-8'); ?>"></script>
<script>
  var cfg = <?php echo json_encode($config, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?>;
  new DocsAPI.DocEditor("editor", cfg);
</script>
</body>
</html>
