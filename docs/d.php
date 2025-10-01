<?php
// convert-save-qt.php — يحوّل DOCX→PDF عبر ONLYOFFICE، يحفظ الناتج ويعرضه

// ===== إعداداتك =====
$onlyoffice_converter = "http://127.0.0.1:8080/converter";        // داخل نفس السيرفر
$source_url           = "https://alfagolden.com/docs/qt.docx";    // هذا جرّبته: 200 من داخل الحاوية
$save_to              = "/var/www/files/main/public/docs/qt.pdf"; // أين نحفظ الناتج
$jwt_secret           = "EzZhbzey1tTFYqbCllCIWQNC7RmDbLbZ";       // السر الحقيقي من الحاوية

// ===== JWT HS256 =====
function b64url($s){ return rtrim(strtr(base64_encode($s), '+/', '-_'), '='); }
function jwt_hs256(array $payload, $secret){
  $header = ['alg'=>'HS256','typ'=>'JWT'];
  $h = b64url(json_encode($header, JSON_UNESCAPED_SLASHES));
  $p = b64url(json_encode($payload, JSON_UNESCAPED_SLASHES));
  $sig = hash_hmac('sha256', "$h.$p", $secret, true);
  return "$h.$p.".b64url($sig);
}

// ===== جسم الطلب (نفس الحقول اللي نوقّعها) =====
$req = [
  'async'      => false,
  'filetype'   => 'docx',
  'outputtype' => 'pdf',
  'key'        => 'qt-'.md5($source_url),
  'title'      => 'qt.docx',
  'url'        => $source_url,
];

// التوكن: نفس الحقول + iat
$payload_for_jwt = $req + ['iat' => time()];
$token = jwt_hs256($payload_for_jwt, $jwt_secret);

// نُرسل التوكن في الهيدر (المعتاد). ممكن أيضًا نمرره في الجسم لو إعداد inBody مفعل.
// هنا نضيفه كذلك في الجسم لضمان التوافق.
$req_with_token = $req + ['token' => $token];
$body = json_encode($req_with_token, JSON_UNESCAPED_SLASHES);

// ===== POST /converter =====
$ch = curl_init($onlyoffice_converter);
curl_setopt_array($ch, [
  CURLOPT_POST           => true,
  CURLOPT_HTTPHEADER     => [
    'Content-Type: application/json',
    'Authorization: Bearer '.$token,
    'User-Agent: ONLYOFFICE-PHP/convert',
  ],
  CURLOPT_POSTFIELDS     => $body,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_TIMEOUT        => 90,
]);
$resp = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err  = curl_error($ch);
curl_close($ch);

if($resp === false || $http < 200 || $http >= 300){
  http_response_code(502);
  header('Content-Type: text/plain; charset=utf-8');
  echo "converter error: HTTP $http\n$err\n";
  exit;
}

// ===== استخراج fileUrl (JSON أو XML) =====
$fileUrl = null;
if(($j = json_decode($resp, true))){
  $fileUrl = $j['fileUrl'] ?? null;
}
if(!$fileUrl && preg_match('~<fileUrl>(.*?)</fileUrl>~', $resp, $m)){
  $fileUrl = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
}
if(!$fileUrl){
  http_response_code(500);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['error'=>'no_fileUrl','raw'=>$resp], JSON_UNESCAPED_SLASHES);
  exit;
}

// ===== تنزيل الـ PDF =====
$ch = curl_init($fileUrl);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_TIMEOUT        => 120,
  CURLOPT_SSL_VERIFYPEER => true,   // عندك شهادة صحيحة؛ اتركه true
  CURLOPT_SSL_VERIFYHOST => 2,
  CURLOPT_HTTPHEADER     => ['User-Agent: ONLYOFFICE-PHP/downloader'],
]);
$pdf = curl_exec($ch);
$http= curl_getinfo($ch, CURLINFO_HTTP_CODE);
$ct  = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$err = curl_error($ch);
curl_close($ch);

// تحقق بسيط: لازم >1KB ونوع PDF
if($pdf === false || $http < 200 || $http >= 300 || stripos((string)$ct,'pdf') === false || strlen($pdf) < 1024){
  http_response_code(500);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['error'=>'download_failed_or_small','http'=>$http,'ct'=>$ct,'curl'=>$err,'fileUrl'=>$fileUrl,'size'=>strlen((string)$pdf)], JSON_UNESCAPED_SLASHES);
  exit;
}

// ===== احفظ على القرص ثم اعرض =====
@mkdir(dirname($save_to), 0775, true);
if(file_put_contents($save_to, $pdf) === false){
  http_response_code(500);
  header('Content-Type: text/plain; charset=utf-8');
  echo "write failed: $save_to";
  exit;
}
@chmod($save_to, 0664);

// اعرض (inline). غيّر إلى attachment لو تبغى تنزيل مباشر.
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="qt.pdf"');
header('Cache-Control: no-store');
header('Content-Length: '.strlen($pdf));
echo $pdf;
