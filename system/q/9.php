<?php
// ================== الإعدادات ==================
$baserow_url = "https://base.alfagolden.com";
$table_id = 704;
$token = "h5qAt85gtiJDAzpH51WrXPywhmnhrPWy";
$quote_id = isset($_GET['quote_id']) ? intval($_GET['quote_id']) : 1;

// ================== دوال Baserow ==================
function fetchQuoteData($quote_id, $baserow_url, $table_id, $token) {
    // نستخدم user_field_names=true حتى نتعامل مباشرة مع أسماء الحقول العربية
    $url = "{$baserow_url}/api/database/rows/table/{$table_id}/{$quote_id}/?user_field_names=true";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ["Authorization: Token {$token}"],
        CURLOPT_TIMEOUT        => 30
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ($httpCode == 200) ? json_decode($response, true) : null;
}

function clearOldContractPdfUrl($quote_id, $baserow_url, $table_id, $token) {
    $url = "{$baserow_url}/api/database/rows/table/{$table_id}/{$quote_id}/?user_field_names=true";
    $data = [
        'رابط العقدpdf'   => '',
        // لو موجود الحقل التالي في الجدول يفضَّل تصفيره أيضاً
        'contractpdftime' => ''
    ];
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => 'PATCH',
        CURLOPT_HTTPHEADER     => [
            "Authorization: Token {$token}",
            "Content-Type: application/json"
        ],
        CURLOPT_POSTFIELDS     => json_encode($data, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT        => 30
    ]);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ($code === 200);
}

// ================== فحص الرابط ==================
function headOk($url) {
    if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) return false;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_NOBODY         => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0
    ]);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ($code >= 200 && $code < 300);
}

// ================== منطق الجاهزية (بحسب حقولك المحددة) ==================
// الحقول المطلوبة هنا:
//   رابط العقدpdf   (7068)
//   تاريخ العقد      (7035)
// منطق الزرين المطلوب: إذا لا يوجد رابط صالح => الأزرار تُنشئ أولاً،
// وإذا وُجد رابط صالح => الأزرار تعمل طبيعي (تحميل/مشاركة) بدون توليد.
function checkContractPdfStatus($row) {
    $pdf_url       = $row['رابط العقدpdf'] ?? '';
    $contract_date = $row['تاريخ العقد'] ?? '';
    $pdf_time      = $row['contractpdftime'] ?? '';// إن وُجد

    // 1) لو الرابط موجود ويعمل (HEAD 200) نعتبره جاهز بغضّ النظر عن الطوابع الزمنية
    if (!empty($pdf_url) && filter_var($pdf_url, FILTER_VALIDATE_URL) && headOk($pdf_url)) {
        return [
            'status'        => 'ready',
            'url'           => $pdf_url,
            'auto_generate' => false
        ];
    }

    // 2) إن لم يكن الرابط جاهزًا: نحاول استخدام منطق الوقت إن وُجد (اختياري)
    if (!empty($pdf_time) && !empty($contract_date)) {
        try {
            $pdf_ts = new DateTime($pdf_time);
            $mod_ts = new DateTime($contract_date);
            $diff   = $mod_ts->getTimestamp() - $pdf_ts->getTimestamp();
            if ($diff > 120) {
                return [
                    'status'         => 'generate_needed',
                    'reason'         => 'file_outdated',
                    'auto_generate'  => true,
                    'clear_old_url'  => true,
                    'age_diff'       => $diff
                ];
            }
        } catch (Exception $e) {
            // تجاهل وننتقل للتوليد
        }
    }

    // 3) الحالة الافتراضية: نحتاج توليد
    return [
        'status'        => 'generate_needed',
        'reason'        => 'no_valid_url',
        'auto_generate' => true,
        'clear_old_url' => true
    ];
}

// ================== AJAX: مسح الرابط القديم ==================
if (isset($_POST['action']) && $_POST['action'] === 'clear_old_contract_pdf') {
    header('Content-Type: application/json');
    $ok = clearOldContractPdfUrl($quote_id, $baserow_url, $table_id, $token);
    echo json_encode(['success' => $ok]);
    exit;
}

// ================== AJAX: إنشاء PDF (جرب المزبوط ثم التجربة) ==================
if (isset($_POST['action']) && $_POST['action'] === 'generate_contract_pdf') {
    header('Content-Type: application/json');
    try {
        if (!empty($_POST['clear_old']) && $_POST['clear_old'] === 'true') {
            clearOldContractPdfUrl($quote_id, $baserow_url, $table_id, $token);
            usleep(600000); // 0.6s
        }

        // أولاً: المزبوط (production)
        $prod = "https://n8n.alfagolden.com/webhook/3e6d3144?quote_id={$quote_id}";
        // ثانياً: لو فشل الإنتاجي، نجرب التجربة (test)
        $test = "https://n8n.alfagolden.com/webhook/3e6d3144?quote_id={$quote_id}";

        $ok = false;
        foreach ([$prod, $test] as $url) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 60,
                CURLOPT_FOLLOWLOCATION => true
            ]);
            curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($code === 200) { $ok = true; break; }
        }

        if (!$ok) throw new Exception('فشل استدعاء الويب هوك.');

        // نرجع الحالة الحالية، والواجهة تكمل polling
        $row = fetchQuoteData($quote_id, $baserow_url, $table_id, $token);
        $st  = $row ? checkContractPdfStatus($row) : ['status'=>'generate_needed','auto_generate'=>true];
        echo json_encode([
            'success'       => true,
            'status'        => $st['status'],
            'url'           => $st['url'] ?? '',
            'auto_generate' => $st['auto_generate'] ?? false,
            'reason'        => $st['reason'] ?? ''
        ]);
    } catch (Exception $e) {
        echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
    }
    exit;
}

// ================== AJAX: فحص الحالة ==================
if (isset($_POST['action']) && $_POST['action'] === 'check_contract_status') {
    header('Content-Type: application/json');
    $row = fetchQuoteData($quote_id, $baserow_url, $table_id, $token);
    $st  = $row ? checkContractPdfStatus($row) : ['status'=>'generate_needed','auto_generate'=>true];
    echo json_encode([
        'status'        => $st['status'],
        'url'           => $st['url'] ?? '',
        'auto_generate' => $st['auto_generate'] ?? false,
        'reason'        => $st['reason'] ?? '',
        'age_diff'      => $st['age_diff'] ?? null
    ]);
    exit;
}

// ================== تحميل أولي ==================
$row = fetchQuoteData($quote_id, $baserow_url, $table_id, $token);
if (!$row) die("لا يمكن الحصول على بيانات العقد. تأكد من صحة رقم العقد.");
$st = checkContractPdfStatus($row);

// تجهيز اسم ملف (اختياري، لن نعرض بيانات على الصفحة)
function extractValue($field, $default = '') {
    return (is_array($field) && !empty($field) && isset($field[0]['value']))
        ? (is_array($field[0]['value']) ? ($field[0]['value']['value'] ?? $default) : $field[0]['value'])
        : ($field ?: $default);
}
$title_before = extractValue($row['قبل الاسم'] ?? '');
$client_name  = extractValue($row['اسم العميل'] ?? '');
$contract_no  = $row['رقم العقد'] ?? $row['الرقم التست'] ?? '';
$clean = function($s){ return preg_replace('/[\/\\:*?"<>|]/u', '', (string)$s); };
$download_filename = "عقد اتفاق ({$clean($title_before)} {$clean($client_name)}) #{$clean($contract_no)}.pdf";
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width,initial-scale=1.0" />
<title>تصدير العقد PDF</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
<style>
:root{--gold:#977e2b;--gold-hover:#b89635;--border:#e5e7eb;--shadow:0 2px 8px rgba(0,0,0,.1)}
*{box-sizing:border-box} body{margin:0;background:#f8f9fa;font-family:system-ui,'Cairo',sans-serif}
.wrap{max-width:720px;margin:48px auto;padding:16px}
.card{background:#fff;border:1px solid var(--border);border-radius:12px;box-shadow:var(--shadow);padding:28px;text-align:center}
.btns{display:flex;gap:12px;justify-content:center;flex-wrap:wrap}
.btn{background:var(--gold);color:#fff;border:none;border-radius:10px;padding:14px 22px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:10px;min-width:180px;justify-content:center;box-shadow:0 4px 15px rgba(151,126,43,.3);transition:.25s}
.btn:hover{background:var(--gold-hover);transform:translateY(-2px)}
.btn.secondary{background:#6b7280}
.btn.loading{background:#777;cursor:not-allowed}
.btn.loading i{animation:spin 1s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
.note{margin-top:12px;color:#555;font-size:13px;min-height:18px}
</style>
</head>
<body>
<div class="wrap">
  <div class="card">
    <div class="btns">
      <button class="btn" id="downloadBtn"><i class="fas fa-file-pdf"></i><span id="downloadTxt">جارٍ التحضير...</span></button>
      <button class="btn secondary" id="shareBtn"><i class="fas fa-share-alt"></i><span>مشاركة</span></button>
    </div>
    <div class="note" id="note"></div>
  </div>
</div>

<script>
// حالة البداية من السيرفر (بدون عرض أي بيانات أخرى)
const state = {
  quote_id: '<?= $quote_id ?>',
  pdf_status: '<?= $st['status'] ?>',
  pdf_url: '<?= $st['url'] ?? '' ?>',
  auto_generate: <?= !empty($st['auto_generate']) ? 'true':'false' ?>,
  clear_old_url: <?= !empty($st['clear_old_url']) ? 'true':'false' ?>,
  filename: <?= json_encode($download_filename, JSON_UNESCAPED_UNICODE) ?>
};

const dlBtn = document.getElementById('downloadBtn');
const dlTxt = document.getElementById('downloadTxt');
const shareBtn = document.getElementById('shareBtn');
const note = document.getElementById('note');

function setBtnLoading(){ dlBtn.classList.add('loading'); dlTxt.textContent='جارٍ الإنشاء...'; dlBtn.querySelector('i').className='fas fa-spinner'; }
function setBtnReady(){ dlBtn.classList.remove('loading'); dlTxt.textContent='تحميل ملف العقد'; dlBtn.querySelector('i').className='fas fa-file-pdf'; }
function setBtnRetry(){ dlBtn.classList.remove('loading'); dlTxt.textContent='إعادة المحاولة'; dlBtn.querySelector('i').className='fas fa-redo'; }
function setNote(msg){ note.textContent = msg || ''; }

async function postForm(action, extra={}){
  const fd = new FormData(); fd.append('action', action); for(const [k,v] of Object.entries(extra)) fd.append(k,v);
  const res = await fetch(location.href, { method:'POST', body: fd });
  return res.json();
}

async function ensurePdf(){
  setBtnLoading(); setNote('يجري تجهيز ملف العقد…');
  try{
    const payload = {}; if (state.clear_old_url) payload.clear_old = 'true';
    const res = await postForm('generate_contract_pdf', payload);
    if(!res.success) throw new Error(res.message || 'فشل الإنشاء');

    state.pdf_status = res.status; state.pdf_url = res.url || '';
    if (state.pdf_status === 'ready' && state.pdf_url){ setBtnReady(); setNote('تم تجهيز الملف.'); }
    else { pollStatus(); }
  }catch(e){ console.error(e); setBtnRetry(); setNote('تعذر الإنشاء. حاول مجددًا.'); }
}

async function pollStatus(){
  try{
    const res = await postForm('check_contract_status');
    if (res.status === 'ready' && res.url){ state.pdf_status='ready'; state.pdf_url=res.url; setBtnReady(); setNote('جاهز للتحميل.'); }
    else { setTimeout(pollStatus, 3000); }
  }catch(e){ console.error(e); setBtnRetry(); setNote('تعذر التحقق من الحالة.'); }
}

function downloadNow(url){ const a=document.createElement('a'); a.style.display='none'; a.href=url; a.download=state.filename; document.body.appendChild(a); a.click(); document.body.removeChild(a); }

async function shareNow(){
  try{
    if (!state.pdf_url){ setNote('لا يوجد رابط للمشاركة بعد.'); return; }
    if (navigator.share){ await navigator.share({ title:'عقد PDF', text:'ملف العقد', url: state.pdf_url }); }
    else { await navigator.clipboard.writeText(state.pdf_url); setNote('تم نسخ رابط المشاركة للحافظة.'); setTimeout(()=>setNote(''), 2500); }
  }catch(e){ console.error(e); setNote('تعذر المشاركة الآن.'); }
}

// سلوك الصفحة حسب المطلوب:
// إن كان لا يوجد رابط صالح في "رابط العقدpdf" => كلا الزرين يبدأان التوليد.
// وإن وُجد رابط صالح => يعملان طبيعيًا (تحميل/مشاركة).
window.addEventListener('load', async ()=>{
  if (state.pdf_status === 'ready' && state.pdf_url){ setBtnReady(); setNote('ملف العقد جاهز.'); }
  else { dlTxt.textContent = 'إنشاء ملف العقد'; if (state.auto_generate) await ensurePdf(); }
});

dlBtn.addEventListener('click', async ()=>{
  if (state.pdf_status === 'ready' && state.pdf_url) downloadNow(state.pdf_url);
  else await ensurePdf();
});

shareBtn.addEventListener('click', async ()=>{
  if (state.pdf_status === 'ready' && state.pdf_url) await shareNow();
  else await ensurePdf();
});
</script>
</body>
</html>
