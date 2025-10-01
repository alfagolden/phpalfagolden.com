<?php
/******************************************************
 * ONLYOFFICE PHP Integration (Dynamic + Browser) - Enhanced Arabic Support
 * ملف واحد: dx.php (نسخة محسّنة - دعم الأسماء العربية + نظام الذاكرة)
 * - بدون ?file=     => قائمة القوالب (DOCX) + إنشاء/رفع مع أسماء عربية
 * - مع   ?file=     => محرّر ONLYOFFICE بملء الشاشة
 * - نظام JSON       => ذاكرة للترجمة بين ID والأسماء المعروضة
 * - تعديل الأسماء   => إمكانية تغيير أسماء الملفات في أي وقت
 ******************************************************/

// ======= إعدادات أساسية =======
$onlyoffice_server_url = "https://office.alfagolden.com";
$base_http             = "https://alfagolden.com/system/docs";
$base_dir              = "/var/www/files/main/public/system/docs";
$jwt_secret            = "EzZhbzey1tTFYqbCllCIWQNC7RmDbLbZ";
$memory_file           = $base_dir . "/files_memory.json";

// ======= دوال إدارة الذاكرة JSON =======
function load_files_memory($memory_file) {
    if (!file_exists($memory_file)) {
        return ["files" => []];
    }
    $content = @file_get_contents($memory_file);
    $data = json_decode($content, true);
    return $data ?: ["files" => []];
}

function save_files_memory($memory_file, $data) {
    $dir = dirname($memory_file);
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    return @file_put_contents($memory_file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

function generate_unique_id($memory) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    do {
        $id = '';
        for ($i = 0; $i < 3; $i++) {
            $id .= $chars[rand(0, strlen($chars) - 1)];
        }
    } while (isset($memory['files'][$id]));
    return $id;
}

function get_file_by_id($memory, $id) {
    return isset($memory['files'][$id]) ? $memory['files'][$id] : null;
}

function get_id_by_display_name($memory, $display_name) {
    foreach ($memory['files'] as $id => $file) {
        if ($file['display_name'] === $display_name) {
            return $id;
        }
    }
    return null;
}

function add_file_to_memory($memory_file, $id, $display_name) {
    $memory = load_files_memory($memory_file);
    $memory['files'][$id] = [
        'display_name' => $display_name,
        'created' => date('Y-m-d H:i:s'),
        'modified' => date('Y-m-d H:i:s')
    ];
    save_files_memory($memory_file, $memory);
    return $memory;
}

function update_file_name($memory_file, $id, $new_display_name) {
    $memory = load_files_memory($memory_file);
    if (isset($memory['files'][$id])) {
        $memory['files'][$id]['display_name'] = $new_display_name;
        $memory['files'][$id]['modified'] = date('Y-m-d H:i:s');
        save_files_memory($memory_file, $memory);
        return true;
    }
    return false;
}

function remove_file_from_memory($memory_file, $id) {
    $memory = load_files_memory($memory_file);
    if (isset($memory['files'][$id])) {
        unset($memory['files'][$id]);
        save_files_memory($memory_file, $memory);
        return true;
    }
    return false;
}

// ======= دوال مساعدة عامة =======
function base64UrlEncode($d){ return rtrim(strtr(base64_encode($d), '+/', '-_'), '='); }
function base64UrlDecode($d){ return base64_decode(strtr($d, '-_', '+/')); }
function createJwtToken(array $payload, $secret){
    $h = base64UrlEncode(json_encode(['alg'=>'HS256','typ'=>'JWT'], JSON_UNESCAPED_SLASHES));
    $p = base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));
    $s = base64UrlEncode(hash_hmac('sha256', "$h.$p", $secret, true));
    return "$h.$p.$s";
}
function verifyJwtToken($token, $secret){
    $parts = explode('.', $token);
    if(count($parts)!==3) return [false,"Malformed token"];
    list($h,$p,$s) = $parts;
    $hdr = json_decode(base64UrlDecode($h), true);
    if(!$hdr || ($hdr['alg']??'')!=='HS256') return [false,"Unsupported alg"];
    $calc = base64UrlEncode(hash_hmac('sha256', "$h.$p", $secret, true));
    if(!hash_equals($calc,$s)) return [false,"Signature mismatch"];
    return [true, json_decode(base64UrlDecode($p), true)];
}
function http_download($url,$timeout=60){
    $ch=curl_init($url);
    curl_setopt_array($ch,[
        CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_FOLLOWLOCATION=>true,
        CURLOPT_MAXREDIRS=>5,
        CURLOPT_TIMEOUT=>$timeout,
        CURLOPT_SSL_VERIFYPEER=>true,
        CURLOPT_SSL_VERIFYHOST=>2,
        CURLOPT_HTTPHEADER=>['User-Agent: ONLYOFFICE-PHP/1.0'],
    ]);
    $body=curl_exec($ch);
    $code=curl_getinfo($ch,CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code,$body];
}
function getBearerTokenFromHeaders(){
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    foreach(['Authorization','authorization'] as $h){
        if(!empty($headers[$h]) && stripos($headers[$h],'Bearer ')===0){
            return trim(substr($headers[$h],7));
        }
    }
    return null;
}

function ensure_backup_dir($base_dir){
    $bdir = $base_dir.'/بكب';
    if(!is_dir($bdir)) @mkdir($bdir, 0775, true);
    return $bdir;
}
function create_blank_docx($path){
    if(!class_exists('ZipArchive')) return false;
    $zip = new ZipArchive();
    if($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE)!==true) return false;

    $zip->addFromString('[Content_Types].xml', <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
</Types>
XML);

    $zip->addFromString('_rels/.rels', <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>
XML);

    $zip->addFromString('word/document.xml', <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:body><w:p/></w:body>
</w:document>
XML);

    $zip->close();
    @chmod($path, 0664);
    return true;
}

// ======= معالجة الطلب الحالي =======
$requested_id = isset($_GET['file']) ? basename($_GET['file']) : null;
$memory = load_files_memory($memory_file);
$file_info = $requested_id ? get_file_by_id($memory, $requested_id) : null;

if ($requested_id && !$file_info) {
    // Try to generate from template if it's a numeric ID
    if (is_numeric($requested_id)) {
        require_once __DIR__ . '/template_processor.php';
        $result = generateWordDocument($requested_id);
        
        if ($result['success']) {
            // Add to memory
            add_file_to_memory($memory_file, $requested_id, "عرض السعر #" . $requested_id);
            $file_info = get_file_by_id($memory, $requested_id);
        } else {
            http_response_code(500);
            echo "خطأ في إنشاء المستند: " . $result['error'];
            exit;
        }
    } else {
        http_response_code(404);
        echo "الملف غير موجود";
        exit;
    }
}

$document_filename = $requested_id ? ($requested_id . '.docx') : null;
$document_public_url = $document_filename ? ($base_http . '/' . $document_filename) : null;
$document_local_path = $document_filename ? ($base_dir . '/' . $document_filename) : null;
$self_url = $requested_id ? ($base_http . '/dx.php?file=' . rawurlencode($requested_id)) : ($base_http . '/dx.php');

// Check if file exists, if not generate it
if ($requested_id && !file_exists($document_local_path)) {
    require_once __DIR__ . '/template_processor.php';
    $result = generateWordDocument($requested_id);
    
    if (!$result['success']) {
        http_response_code(500);
        echo "خطأ في إنشاء المستند: " . $result['error'];
        exit;
    }
}


// ======= معالجة الحفظ (Callback من ONLYOFFICE) =======
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_GET['callback'])){
    header('X-Content-Type-Options: nosniff');

    $raw  = file_get_contents('php://input');
    $json = json_decode($raw, true) ?: [];
    $token = getBearerTokenFromHeaders();
    if(!$token && isset($json['token'])) $token = $json['token'];

    if(!$token){
        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(["error"=>1, "message"=>"No token provided"]);
        exit;
    }
    list($ok,) = verifyJwtToken($token, $jwt_secret);
    if(!$ok){
        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(["error"=>1, "message"=>"JWT verification failed"]);
        exit;
    }

    $status  = $json['status'] ?? null;
    $fileUrl = $json['url'] ?? ($json['downloadUri'] ?? null);

    if(in_array($status,[2,6],true) && $fileUrl && $document_local_path){
        list($code,$body) = http_download($fileUrl,60);
        if(!($code>=200 && $code<300) || $body===false){
            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(["error"=>1,"message"=>"Download failed"]);
            exit;
        }

        $dir = dirname($document_local_path);
        if(!is_dir($dir) && !@mkdir($dir,0775,true)){
            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(["error"=>1,"message"=>"Storage path not writable"]);
            exit;
        }

        // نسخ احتياطي منظم
        $bdir = ensure_backup_dir($base_dir);
        if(file_exists($document_local_path)){
            @copy($document_local_path, $bdir.'/'.$document_filename.'.bak-'.date('Ymd-His'));
        }

        if(@file_put_contents($document_local_path,$body)===false){
            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(["error"=>1,"message"=>"Write failed"]);
            exit;
        }
        @chmod($document_local_path,0664);

        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(["error"=>0]);
        exit;
    }

    http_response_code(200);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(["error"=>0]);
    exit;
}

// ======= معالجة عمليات المتصفح =======
if($_SERVER['REQUEST_METHOD']==='POST' && !isset($_GET['callback'])){
    // إنشاء ملف جديد
    if(isset($_POST['action']) && $_POST['action']==='create'){
        $display_name = trim($_POST['name'] ?? 'قالب جديد');
        if(empty($display_name)) $display_name = 'قالب جديد';
        
        // Check if a specific ID was provided
        $id = trim($_POST['id'] ?? '');
        if(empty($id)) {
            $id = generate_unique_id($memory);
        }
        
        // Use template processor to generate document from template
        require_once __DIR__ . '/template_processor.php';
        $result = generateWordDocument($id);
        
        if ($result['success']) {
            add_file_to_memory($memory_file, $id, $display_name);
            header("Location: ".$base_http."/dx.php?file=".rawurlencode($id));
            exit;
        } else {
            die("تعذّر إنشاء المستند من القالب: " . $result['error']);
        }
    }
    
    // رفع ملف
    if(isset($_POST['action']) && $_POST['action']==='upload' && isset($_FILES['upload'])){
        if($_FILES['upload']['error']!==UPLOAD_ERR_OK){
            $errMap = [
              UPLOAD_ERR_INI_SIZE=>"حجم الملف يتجاوز upload_max_filesize",
              UPLOAD_ERR_FORM_SIZE=>"حجم الملف يتجاوز MAX_FILE_SIZE",
              UPLOAD_ERR_PARTIAL=>"تم رفع جزء من الملف فقط",
              UPLOAD_ERR_NO_FILE=>"لم يتم اختيار ملف",
              UPLOAD_ERR_NO_TMP_DIR=>"المجلد المؤقت مفقود",
              UPLOAD_ERR_CANT_WRITE=>"تعذّر الكتابة على القرص",
              UPLOAD_ERR_EXTENSION=>"إيقاف بواسطة إضافة PHP"
            ];
            $code = $_FILES['upload']['error'];
            die("فشل الرفع ($code): ".($errMap[$code] ?? 'خطأ غير معروف'));
        }
        
        $display_name = trim($_POST['name'] ?? '');
        if(empty($display_name)) {
            $display_name = pathinfo($_FILES['upload']['name'], PATHINFO_FILENAME);
        }
        
        $id = generate_unique_id($memory);
        $filename = $id . '.docx';
        $path = $base_dir . '/' . $filename;

        $ok = @move_uploaded_file($_FILES['upload']['tmp_name'], $path);
        if(!$ok){
            $ok = @copy($_FILES['upload']['tmp_name'], $path);
            if(!$ok){
                $in=@fopen($_FILES['upload']['tmp_name'],'rb'); $out=@fopen($path,'wb');
                if($in && $out){ $ok = stream_copy_to_stream($in,$out) > 0; }
                if(isset($in)) @fclose($in); if(isset($out)) @fclose($out);
            }
        }
        if(!$ok){ die("تعذّر حفظ الملف المرفوع."); }

        @chmod($path, 0664);
        add_file_to_memory($memory_file, $id, $display_name);
        
        header("Location: ".$base_http."/dx.php?file=".rawurlencode($id));
        exit;
    }
    
    // تعديل اسم ملف
    if(isset($_POST['action']) && $_POST['action']==='rename'){
        $id = trim($_POST['id'] ?? '');
        $new_name = trim($_POST['new_name'] ?? '');
        
        if(!empty($id) && !empty($new_name) && get_file_by_id($memory, $id)){
            update_file_name($memory_file, $id, $new_name);
        }
        
        header("Location: ".$base_http."/dx.php");
        exit;
    }
    
    // حذف ملف
    if(isset($_POST['action']) && $_POST['action']==='delete'){
        $id = trim($_POST['id'] ?? '');
        $file = get_file_by_id($memory, $id);
        
        if($file && $id){
            $filename = $id . '.docx';
            $path = $base_dir . '/' . $filename;
            
            // نقل إلى المحذوفات
            $bdir = ensure_backup_dir($base_dir);
            if(file_exists($path)){
                @rename($path, $bdir.'/deleted-'.$filename.'-'.date('Ymd-His'));
            }
            
            remove_file_from_memory($memory_file, $id);
        }
        
        header("Location: ".$base_http."/dx.php");
        exit;
    }
    
    // خلاف ذلك نرجع للقائمة
    header("Location: ".$base_http."/dx.php");
    exit;
}

// ======= عرض HTML =======
// Serve file directly ONLY if explicitly requested (not for editor view)
// Check if this is a download request (editor parameter must be explicitly set to use editor)
if ($requested_id && file_exists($document_local_path) && !isset($_GET['editor']) && isset($_GET['download'])) {
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="' . $document_filename . '"');
    header('Content-Length: ' . filesize($document_local_path));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    readfile($document_local_path);
    exit;
}

?><!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>إدارة قوالب المستندات - دعم عربي محسن</title>
<!-- CDN Libraries -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/inter-ui/3.19.3/inter.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.29.0/feather.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.29.0/feather.min.js"></script>

<style>
  :root{
    /* Modern Design System */
    --bg-primary: #fafafa;
    --bg-secondary: #ffffff;
    --bg-tertiary: #f6f7f9;
    --border-light: #e8eaed;
    --border-medium: #dadce0;
    --text-primary: #202124;
    --text-secondary: #5f6368;
    --text-muted: #80868b;
    --accent: #1a73e8;
    --accent-hover: #1557b0;
    --accent-light: #e8f0fe;
    --success: #1e8e3e;
    --success-light: #e8f5e8;
    --warning: #f57c00;
    --warning-light: #fff3e0;
    --danger: #d93025;
    --danger-light: #fce8e6;
    --radius: 12px;
    --radius-lg: 16px;
    --shadow-sm: 0 1px 3px rgba(60,64,67,0.15);
    --shadow-md: 0 4px 12px rgba(60,64,67,0.15);
    --shadow-lg: 0 8px 24px rgba(60,64,67,0.2);
    --transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  }

  @media (prefers-color-scheme: dark) {
    :root{
      --bg-primary: #1a1a1a;
      --bg-secondary: #2d2d2d;
      --bg-tertiary: #262626;
      --border-light: #404040;
      --border-medium: #525252;
      --text-primary: #ffffff;
      --text-secondary: #a3a3a3;
      --text-muted: #737373;
      --accent-light: #1e3a8a;
      --success-light: #0d4d14;
      --warning-light: #5d2c00;
      --danger-light: #5d1d1a;
    }
  }

  * { box-sizing: border-box; margin: 0; padding: 0; }
  
  body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, "Noto Kufi Arabic", sans-serif;
    background: var(--bg-primary);
    color: var(--text-primary);
    line-height: 1.5;
    font-feature-settings: 'kern' 1, 'liga' 1;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
  }

  .container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 24px;
    min-height: 100vh;
  }

  .header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 32px;
    padding-bottom: 24px;
    border-bottom: 1px solid var(--border-light);
  }

  .header-title {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .header-title h1 {
    font-size: 28px;
    font-weight: 700;
    letter-spacing: -0.02em;
  }

  .header-icon {
    width: 40px;
    height: 40px;
    background: var(--accent-light);
    border-radius: var(--radius);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--accent);
  }

  /* Buttons */
  .btn-primary {
    background: var(--accent);
    color: white;
    border: none;
    border-radius: var(--radius);
    padding: 12px 24px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    gap: 8px;
    box-shadow: var(--shadow-sm);
  }

  .btn-primary:hover {
    background: var(--accent-hover);
    transform: translateY(-1px);
    box-shadow: var(--shadow-md);
  }

  .btn-secondary {
    background: var(--bg-secondary);
    color: var(--text-primary);
    border: 1px solid var(--border-medium);
    border-radius: var(--radius);
    padding: 12px 24px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .btn-secondary:hover {
    background: var(--bg-tertiary);
    border-color: var(--accent);
    transform: translateY(-1px);
  }

  .btn-small {
    padding: 8px 12px;
    font-size: 13px;
    border-radius: 8px;
  }

  .btn-warning {
    background: var(--warning);
    color: white;
  }

  .btn-warning:hover {
    background: #e65100;
  }

  .btn-danger {
    background: var(--danger);
    color: white;
  }

  .btn-danger:hover {
    background: #b71c1c;
  }

  /* Document Grid */
  .documents-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
    margin-top: 24px;
  }

  .document-card {
    background: var(--bg-secondary);
    border: 1px solid var(--border-light);
    border-radius: var(--radius-lg);
    padding: 20px;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
  }

  .document-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--accent), #4285f4);
    transform: scaleX(0);
    transition: var(--transition);
  }

  .document-card:hover::before {
    transform: scaleX(1);
  }

  .document-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
    border-color: var(--accent);
  }

  .document-header {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 16px;
  }

  .document-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #4285f4, #1a73e8);
    border-radius: var(--radius);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 18px;
    flex-shrink: 0;
  }

  .document-title-section {
    flex: 1;
    min-width: 0;
  }

  .document-title {
    font-weight: 600;
    font-size: 16px;
    color: var(--text-primary);
    margin-bottom: 4px;
    word-wrap: break-word;
    line-height: 1.4;
  }

  .document-id {
    font-size: 11px;
    color: var(--text-muted);
    font-family: monospace;
    background: var(--bg-tertiary);
    padding: 2px 6px;
    border-radius: 4px;
    display: inline-block;
  }

  .document-actions {
    display: flex;
    gap: 8px;
    margin-top: 16px;
    flex-wrap: wrap;
  }

  .document-meta {
    color: var(--text-secondary);
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
    flex-wrap: wrap;
  }

  .meta-item {
    display: flex;
    align-items: center;
    gap: 4px;
  }

  /* Action buttons in cards */
  .btn-card {
    padding: 6px 12px;
    font-size: 12px;
    border-radius: 6px;
    font-weight: 500;
    text-decoration: none;
    transition: var(--transition);
    border: 1px solid transparent;
    display: inline-flex;
    align-items: center;
    gap: 4px;
  }

  .btn-card.edit {
    background: var(--accent-light);
    color: var(--accent);
    border-color: var(--accent);
  }

  .btn-card.rename {
    background: var(--warning-light);
    color: var(--warning);
    border-color: var(--warning);
  }

  .btn-card.delete {
    background: var(--danger-light);
    color: var(--danger);
    border-color: var(--danger);
  }

  .btn-card:hover {
    transform: translateY(-1px);
    box-shadow: var(--shadow-sm);
  }

  /* Empty State */
  .empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--text-secondary);
  }

  .empty-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 20px;
    background: var(--bg-tertiary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-muted);
  }

  /* Modal Styles */
  .modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    z-index: 1000;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
  }

  .modal {
    background: var(--bg-secondary);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-lg);
    width: 100%;
    max-width: 500px;
    position: relative;
    animation: modalShow 0.2s ease-out;
  }

  @keyframes modalShow {
    from { opacity: 0; transform: scale(0.95) translateY(-10px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
  }

  .modal-header {
    padding: 24px 24px 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .modal-title {
    font-size: 20px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .modal-close {
    background: none;
    border: none;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: var(--text-secondary);
    transition: var(--transition);
  }

  .modal-close:hover {
    background: var(--bg-tertiary);
  }

  .modal-body {
    padding: 24px;
  }

  .modal-tabs {
    display: flex;
    gap: 4px;
    margin-bottom: 24px;
    background: var(--bg-tertiary);
    border-radius: var(--radius);
    padding: 4px;
  }

  .tab-btn {
    flex: 1;
    background: none;
    border: none;
    padding: 12px 16px;
    border-radius: 8px;
    cursor: pointer;
    transition: var(--transition);
    font-weight: 500;
    color: var(--text-secondary);
  }

  .tab-btn.active {
    background: var(--bg-secondary);
    color: var(--text-primary);
    box-shadow: var(--shadow-sm);
  }

  .tab-content {
    display: none;
  }

  .tab-content.active {
    display: block;
  }

  /* Form Styles */
  .form-group {
    margin-bottom: 20px;
  }

  .form-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: var(--text-primary);
  }

  .form-input {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid var(--border-medium);
    border-radius: var(--radius);
    font-size: 14px;
    background: var(--bg-secondary);
    color: var(--text-primary);
    transition: var(--transition);
  }

  .form-input:focus {
    outline: none;
    border-color: var(--accent);
    box-shadow: 0 0 0 3px var(--accent-light);
  }

  .file-drop {
    border: 2px dashed var(--border-medium);
    border-radius: var(--radius);
    padding: 32px;
    text-align: center;
    transition: var(--transition);
    cursor: pointer;
    position: relative;
  }

  .file-drop:hover,
  .file-drop.dragover {
    border-color: var(--accent);
    background: var(--accent-light);
  }

  .file-drop input {
    position: absolute;
    inset: 0;
    opacity: 0;
    cursor: pointer;
  }

  .file-drop-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
  }

  .file-drop-icon {
    width: 48px;
    height: 48px;
    color: var(--text-secondary);
  }

  .file-selected {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: var(--success-light);
    border-radius: var(--radius);
    margin-top: 12px;
  }

  /* Loading Spinner */
  .loading-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(4px);
    z-index: 2000;
    display: none;
    align-items: center;
    justify-content: center;
  }

  .loading-content {
    background: var(--bg-secondary);
    border-radius: var(--radius-lg);
    padding: 32px;
    text-align: center;
    box-shadow: var(--shadow-lg);
    min-width: 200px;
  }

  .spinner {
    width: 40px;
    height: 40px;
    border: 3px solid var(--border-light);
    border-top-color: var(--accent);
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 16px;
  }

  @keyframes spin {
    to { transform: rotate(360deg); }
  }

  /* ===== EDITOR STYLES ===== */
  .editor-topbar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: 20px;
    background: var(--bg-secondary);
    border-bottom: 1px solid var(--border-light);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 8px;
    z-index: 10;
    backdrop-filter: blur(10px);
    font-size: 11px;
  }

  .editor-title {
    font-weight: 500;
    color: var(--text-primary);
    font-size: 11px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 60%;
  }

  .btn-mini {
    background: var(--bg-tertiary);
    color: var(--text-primary);
    border: 1px solid var(--border-light);
    border-radius: 4px;
    padding: 2px 6px;
    font-weight: 500;
    font-size: 10px;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    gap: 3px;
    height: 16px;
    line-height: 1;
    text-decoration: none;
  }

  .btn-mini:hover {
    background: var(--accent-light);
    border-color: var(--accent);
    color: var(--accent);
  }

  .btn-mini i {
    width: 10px;
    height: 10px;
  }

  .editor-wrapper {
    position: fixed;
    top: 20px;
    left: 0;
    right: 0;
    bottom: 0;
  }

  #editor {
    width: 100%;
    height: 100%;
  }

  /* Responsive */
  @media (max-width: 768px) {
    .container {
      padding: 16px;
    }

    .header {
      flex-direction: column;
      gap: 16px;
      align-items: flex-start;
    }

    .documents-grid {
      grid-template-columns: 1fr;
    }

    .modal {
      margin: 20px;
    }

    .document-actions {
      justify-content: flex-start;
    }

    .btn-card {
      font-size: 11px;
      padding: 4px 8px;
    }
  }

  /* RTL Adjustments */
  [dir="rtl"] .feather {
    transform: scaleX(-1);
  }

  /* Inline editing styles */
  .inline-edit {
    display: none;
  }

  .inline-edit.active {
    display: flex;
    gap: 8px;
    margin-top: 8px;
  }

  .inline-edit input {
    flex: 1;
    padding: 6px 8px;
    border: 1px solid var(--border-medium);
    border-radius: 4px;
    font-size: 14px;
  }
</style>
</head>
<body>

<?php if(!$requested_id): ?>
  <!-- Main Dashboard -->
  <div class="container">
    <header class="header">
      <div class="header-title">
        <div class="header-icon">
          <i data-feather="file-text"></i>
        </div>
        <h1>إدارة قوالب المستندات</h1>
      </div>
      
      <div class="main-action">
        <button class="btn-primary" onclick="showDocumentModal()">
          <i data-feather="plus"></i>
          قالب جديد
        </button>
      </div>
    </header>

    <!-- Documents Grid -->
    <?php if(empty($memory['files'])): ?>
      <div class="empty-state">
        <div class="empty-icon">
          <i data-feather="file-text" size="32"></i>
        </div>
        <h3>لا توجد قوالب بعد</h3>
        <p>ابدأ بإنشاء قالب جديد أو رفع ملف موجود - يدعم الأسماء العربية والإنجليزية</p>
        <button class="btn-primary" onclick="showDocumentModal()" style="margin-top: 20px;">
          <i data-feather="plus"></i>
          إنشاء أول قالب
        </button>
      </div>
    <?php else: ?>
      <div class="documents-grid">
        <?php
          $files = $memory['files'];
          ksort($files, SORT_NATURAL|SORT_FLAG_CASE);
          
          foreach($files as $id => $file_data){
          if (is_numeric($id)) {
      continue;
    }
            $full_path = $base_dir.'/'.$id.'.docx';
            $sz = @filesize($full_path);
            $kb = is_numeric($sz) ? number_format($sz/1024, 1) : '0';
            $created = $file_data['created'] ?? '-';
            $modified = $file_data['modified'] ?? '-';
            $display_name = $file_data['display_name'] ?? 'بلا عنوان';
            
            echo '<div class="document-card">
                    <div class="document-header">
                      <div class="document-icon">📄</div>
                      <div class="document-title-section">
                        <div class="document-title" id="title-'.$id.'">'.htmlspecialchars($display_name).'</div>
                        <div class="document-id">ID: '.$id.'</div>
                        <div class="inline-edit" id="edit-'.$id.'">
                          <input type="text" value="'.htmlspecialchars($display_name).'" id="input-'.$id.'">
                          <button class="btn-small btn-primary" onclick="saveRename(\''.$id.'\')">
                            <i data-feather="check" size="12"></i>
                          </button>
                          <button class="btn-small btn-secondary" onclick="cancelRename(\''.$id.'\')">
                            <i data-feather="x" size="12"></i>
                          </button>
                        </div>
                      </div>
                    </div>
                    
                    <div class="document-meta">
                      <div class="meta-item">
                        <i data-feather="hard-drive" size="14"></i>
                        <span>'.$kb.' KB</span>
                      </div>
                      <div class="meta-item">
                        <i data-feather="calendar" size="14"></i>
                        <span>'.date('Y-m-d', strtotime($created)).'</span>
                      </div>
                      <div class="meta-item">
                        <i data-feather="clock" size="14"></i>
                        <span>'.date('H:i', strtotime($modified)).'</span>
                      </div>
                    </div>
                    
                    <div class="document-actions">
                      <a href="'.htmlspecialchars($base_http.'/dx.php?file='.rawurlencode($id)).'" class="btn-card edit">
                        <i data-feather="edit-3" size="12"></i>
                        تحرير
                      </a>
                      <button class="btn-card rename" onclick="startRename(\''.$id.'\')">
                        <i data-feather="edit-2" size="12"></i>
                        تسمية
                      </button>
                      <button class="btn-card delete" onclick="confirmDelete(\''.$id.'\', \''.htmlspecialchars(addslashes($display_name)).'\')">
                        <i data-feather="trash-2" size="12"></i>
                        حذف
                      </button>
                    </div>
                  </div>';
          }
        ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- New Document Modal -->
  <div id="documentModal" class="modal-overlay">
    <div class="modal">
      <div class="modal-header">
        <div class="modal-title">
          <i data-feather="file-plus"></i>
          إنشاء قالب جديد
        </div>
        <button class="modal-close" onclick="hideDocumentModal()">
          <i data-feather="x"></i>
        </button>
      </div>
      
      <div class="modal-body">
        <div class="modal-tabs">
          <button class="tab-btn active" onclick="switchTab('create')">إنشاء فارغ</button>
          <button class="tab-btn" onclick="switchTab('upload')">رفع ملف</button>
        </div>

        <!-- Create Tab -->
        <div id="createTab" class="tab-content active">
          <form id="createForm" method="post">
            <input type="hidden" name="action" value="create">
            <div class="form-group">
              <label class="form-label">اسم القالب (عربي أو إنجليزي)</label>
              <input type="text" name="name" class="form-input" placeholder="مثال: قالب الفواتير الشهرية" required>
              <small style="color: var(--text-muted); font-size: 12px; margin-top: 4px; display: block;">
                يمكنك استخدام أي اسم بالعربية أو الإنجليزية - سيتم إنشاء ID فريد تلقائياً
              </small>
            </div>
            <button type="submit" class="btn-primary" style="width: 100%;">
              <i data-feather="file-plus"></i>
              إنشاء قالب فارغ
            </button>
          </form>
        </div>

        <!-- Upload Tab -->
        <div id="uploadTab" class="tab-content">
          <form id="uploadForm" method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload">
            
            <div class="form-group">
              <label class="form-label">اسم القالب (عربي أو إنجليزي)</label>
              <input type="text" name="name" id="uploadName" class="form-input" placeholder="اتركه فارغاً لاستخدام اسم الملف الأصلي">
              <small style="color: var(--text-muted); font-size: 12px; margin-top: 4px; display: block;">
                يمكنك تغيير الاسم لاحقاً في أي وقت
              </small>
            </div>

            <div class="form-group">
              <label class="form-label">ملف القالب</label>
              <div class="file-drop" onclick="document.getElementById('fileInput').click()">
                <input type="file" name="upload" id="fileInput" accept=".docx" required onchange="handleFileSelect(this)">
                <div class="file-drop-content">
                  <i data-feather="upload-cloud" class="file-drop-icon"></i>
                  <div>
                    <strong>اضغط لاختيار ملف</strong> أو اسحب وأفلت هنا
                  </div>
                  <small style="color: var(--text-muted);">DOCX فقط</small>
                </div>
              </div>
              <div id="fileSelected" class="file-selected" style="display: none;">
                <i data-feather="check-circle" style="color: var(--success);"></i>
                <span id="fileName"></span>
              </div>
            </div>

            <button type="submit" class="btn-primary" style="width: 100%;">
              <i data-feather="upload"></i>
              رفع القالب
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Loading Overlay -->
  <div id="loadingOverlay" class="loading-overlay">
    <div class="loading-content">
      <div class="spinner"></div>
      <div id="loadingMessage">جاري المعالجة...</div>
    </div>
  </div>

<?php else: ?>
  <!-- Editor View -->
  <?php
    if(file_exists($document_local_path)){
        $document_key = md5_file($document_local_path) . '_' . $requested_id;
    } else {
        $document_key = md5($document_public_url) . '_' . $requested_id;
    }
    
    $display_name = $file_info['display_name'] ?? 'مستند بلا عنوان';
    
    $cfg = [
        "type"         => "desktop",
        "documentType" => "word",
        "document" => [
            "title"    => $display_name,
            "url"      => $document_public_url,
            "fileType" => "docx",
            "key"      => $document_key,
        ],
        "editorConfig" => [
            "mode"        => "edit",
            "lang"        => "ar",
            "callbackUrl" => $self_url."&callback=1",
            "user"        => [ "id"=>"user-01", "name"=>"Template Admin" ],
            "customization" => [ "autosave"=>true, "forcesave"=>true ]
        ]
    ];
    $token = createJwtToken($cfg,$jwt_secret);
    $cfg['token'] = $token;
    $cfg['document']['token'] = $token;
    $cfg['editorConfig']['token'] = $token;
  ?>

  <!-- Ultra-thin Editor Topbar -->
  <div class="editor-topbar">
    <a class="btn-mini" href="<?php echo htmlspecialchars($base_http.'/dx.php'); ?>">
      <i data-feather="arrow-right" style="width: 10px; height: 10px;"></i>
      قوالب
    </a>
    <div class="editor-title">
      <?php echo htmlspecialchars($display_name); ?> (<?php echo $requested_id; ?>)
    </div>
    <div style="width: 40px;"></div>
  </div>
  
  <div class="editor-wrapper">
    <div id="editor"></div>
  </div>

  <!-- Loading for Editor -->
  <div id="loadingOverlay" class="loading-overlay" style="display: flex;">
    <div class="loading-content">
      <div class="spinner"></div>
      <div>جاري تحميل المحرر...</div>
    </div>
  </div>

  <script src="<?php echo htmlspecialchars(rtrim($onlyoffice_server_url,'/').'/web-apps/apps/api/documents/api.js', ENT_QUOTES, 'UTF-8'); ?>"></script>
  <script>
    var cfg = <?php echo json_encode($cfg, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?>;
    var editor = new DocsAPI.DocEditor("editor", cfg);

    var checkInterval = setInterval(function(){
      var iframe = document.querySelector('#editor iframe');
      if(!iframe) return;
      try{
        if(iframe.contentWindow && iframe.contentDocument && iframe.contentDocument.readyState === 'complete'){
          clearInterval(checkInterval);
          document.getElementById('loadingOverlay').style.display = 'none';
        }
      }catch(e){
        clearInterval(checkInterval);
        setTimeout(() => {
          document.getElementById('loadingOverlay').style.display = 'none';
        }, 1500);
      }
    }, 200);

    setTimeout(() => {
      document.getElementById('loadingOverlay').style.display = 'none';
    }, 4000);
  </script>
<?php endif; ?>

<script>
  // Initialize Feather icons
  if(typeof feather !== 'undefined') {
    feather.replace();
  }

  <?php if(!$requested_id): ?>
  // Modal functions
  function showDocumentModal() {
    document.getElementById('documentModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
  }

  function hideDocumentModal() {
    document.getElementById('documentModal').style.display = 'none';
    document.body.style.overflow = '';
  }

  // Tab switching
  function switchTab(tab) {
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    document.getElementById(tab + 'Tab').classList.add('active');
  }

  // Rename functions
  function startRename(id) {
    document.getElementById('title-' + id).style.display = 'none';
    document.getElementById('edit-' + id).classList.add('active');
    document.getElementById('input-' + id).focus();
    document.getElementById('input-' + id).select();
  }

  function cancelRename(id) {
    document.getElementById('title-' + id).style.display = 'block';
    document.getElementById('edit-' + id).classList.remove('active');
  }

  function saveRename(id) {
    const newName = document.getElementById('input-' + id).value.trim();
    if(!newName) {
      alert('يرجى إدخال اسم صالح');
      return;
    }

    const form = document.createElement('form');
    form.method = 'post';
    form.innerHTML = `
      <input type="hidden" name="action" value="rename">
      <input type="hidden" name="id" value="${id}">
      <input type="hidden" name="new_name" value="${newName}">
    `;
    document.body.appendChild(form);
    
    showLoading('جاري تحديث الاسم...');
    form.submit();
  }

  function confirmDelete(id, name) {
    if(confirm('هل تريد حذف "' + name + '" نهائياً؟\n\nسيتم نقل الملف إلى مجلد النسخ الاحتياطية.')) {
      const form = document.createElement('form');
      form.method = 'post';
      form.innerHTML = `
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="${id}">
      `;
      document.body.appendChild(form);
      
      showLoading('جاري حذف الملف...');
      form.submit();
    }
  }

  // File selection handler
  function handleFileSelect(input) {
    if(input.files && input.files[0]) {
      const file = input.files[0];
      document.getElementById('fileName').textContent = file.name;
      document.getElementById('fileSelected').style.display = 'flex';
      
      const nameInput = document.getElementById('uploadName');
      if(!nameInput.value) {
        nameInput.value = file.name.replace(/\.[^/.]+$/, "");
      }
    }
  }

  // Drag and drop
  const fileDropArea = document.querySelector('.file-drop');
  if(fileDropArea) {
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
      fileDropArea.addEventListener(eventName, preventDefaults, false);
    });

    ['dragenter', 'dragover'].forEach(eventName => {
      fileDropArea.addEventListener(eventName, () => fileDropArea.classList.add('dragover'), false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
      fileDropArea.addEventListener(eventName, () => fileDropArea.classList.remove('dragover'), false);
    });

    fileDropArea.addEventListener('drop', handleDrop, false);
  }

  function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
  }

  function handleDrop(e) {
    const dt = e.dataTransfer;
    const files = dt.files;
    if(files.length > 0) {
      document.getElementById('fileInput').files = files;
      handleFileSelect(document.getElementById('fileInput'));
    }
  }

  // Form submissions with loading
  document.getElementById('createForm').addEventListener('submit', function() {
    showLoading('جاري إنشاء القالب...');
  });

  document.getElementById('uploadForm').addEventListener('submit', function(e) {
    if(!document.getElementById('fileInput').files[0]) {
      e.preventDefault();
      alert('يرجى اختيار ملف أولاً');
      return;
    }
    showLoading('جاري رفع القالب...');
  });

  function showLoading(message) {
    document.getElementById('loadingMessage').textContent = message;
    document.getElementById('loadingOverlay').style.display = 'flex';
  }

  // Close modal on outside click
  document.getElementById('documentModal').addEventListener('click', function(e) {
    if(e.target === this) {
      hideDocumentModal();
    }
  });

  // Keyboard shortcuts
  document.addEventListener('keydown', function(e) {
    if(e.key === 'Escape') {
      hideDocumentModal();
      // Cancel any active rename
      document.querySelectorAll('.inline-edit.active').forEach(el => {
        const id = el.id.replace('edit-', '');
        cancelRename(id);
      });
    }
    
    // Enter key to save rename
    if(e.key === 'Enter' && e.target.matches('.inline-edit input')) {
      const id = e.target.id.replace('input-', '');
      saveRename(id);
    }
  });

  // Auto-refresh icons after dynamic content
  setTimeout(() => {
    if(typeof feather !== 'undefined') {
      feather.replace();
    }
  }, 100);
  <?php endif; ?>
</script>

</body>
</html>