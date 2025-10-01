<?php
/******************************************************
 * صفحة مبسطة: عرض عروض الأسعار + زر n8n لكل عرض
 * ملف واحد: quotes_n8n.php
 ******************************************************/

/*============= ضبط API و Webhook =============*/
$API_CONFIG = [
    'baseUrl' => 'https://base.alfagolden.com',
    'token' => 'h5qAt85gtiJDAzpH51WrXPywhmnhrPWy',
    'quotesTableId' => 704,
    'usersTableId' => 702
];

$N8N_WEBHOOK_URL = 'https://n8n.alfagolden.com/webhook-test/77b0d8f1-96d8-42f2-a25d-c4e0b857e56f';

/*============= حقول نحتاجها للعرض فقط =============*/
$FIELDS = [
    'quotes' => [
        'client' => 'field_6977',
        'date' => 'field_6789',
        'totalPrice' => 'field_6984',
        'brand' => 'field_6973',
        'quoteNumber' => 'field_6783'
    ],
    'users' => [
        'name' => 'field_6912'
    ]
];

/*============= دوال مساعدة بسيطة =============*/
function makeApiRequest($endpoint, $method = 'GET', $data = null) {
    global $API_CONFIG;
    $url = rtrim($API_CONFIG['baseUrl'], '/') . '/api/database/' . ltrim($endpoint, '/');
    $opts = [
        'http' => [
            'method' => $method,
            'header' => [
                'Authorization: Token ' . $API_CONFIG['token'],
                'Content-Type: application/json'
            ],
            'ignore_errors' => true
        ]
    ];
    if ($data !== null) $opts['http']['content'] = json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $ctx = stream_context_create($opts);
    $resp = @file_get_contents($url, false, $ctx);
    if ($resp === false) throw new Exception('API request failed');
    return json_decode($resp, true);
}

function loadQuotes() {
    global $API_CONFIG;
    try {
        $res = makeApiRequest("rows/table/{$API_CONFIG['quotesTableId']}/");
        return $res['results'] ?? [];
    } catch (Exception $e) {
        return [];
    }
}

function convertToEnglishNumbers($str) {
    if (!$str) return '';
    $ar = '٠١٢٣٤٥٦٧٨٩'; $en = '0123456789';
    return str_replace(str_split($ar), str_split($en), (string)$str);
}
function formatDate($dateString) {
    if (!$dateString) return 'غير محدد';
    $d = new DateTime($dateString);
    return convertToEnglishNumbers($d->format('Y-m-d'));
}
function formatPrice($price) {
    if ($price === null || $price === '') return 'غير محدد';
    return convertToEnglishNumbers(number_format((float)$price)) . ' ر.س';
}
function getClientName($clientArray) {
    return (is_array($clientArray) && !empty($clientArray)) ? ($clientArray[0]['value'] ?? 'غير محدد') : 'غير محدد';
}
function getBrandName($brandArray) {
    if (!is_array($brandArray) || empty($brandArray)) return 'غير محدد';
    $b = $brandArray[0] ?? null;
    if (!$b) return 'غير محدد';
    if (isset($b['value'])) {
        return is_array($b['value']) && isset($b['value']['value']) ? $b['value']['value'] : $b['value'];
    }
    return 'غير محدد';
}

/*============= سيرفر سايد: استدعاء n8n =============*/
function callN8NWebhook($payload) {
    global $N8N_WEBHOOK_URL;
    $ch = curl_init($N8N_WEBHOOK_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60
    ]);
    $body = curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, $err, $body];
}

/*============= AJAX endpoint =============*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['__ajax']) && $_POST['__ajax'] === '1') {
    header('Content-Type: application/json; charset=utf-8');
    // إرسال id واحد
    if (isset($_POST['mode']) && $_POST['mode'] === 'single') {
        $id = (int)($_POST['quote_id'] ?? 0);
        if ($id <= 0) { echo json_encode(['ok'=>false,'msg'=>'معرّف غير صالح']); exit; }
        [$code, $err, $body] = callN8NWebhook(['quote_id' => $id]);
        $ok = ($err === '' && $code >= 200 && $code < 300);
        echo json_encode(['ok'=>$ok,'code'=>$code,'err'=>$err,'body'=>$body], JSON_UNESCAPED_UNICODE);
        exit;
    }
    // إرسال كل المعرفات دفعة واحدة
    if (isset($_POST['mode']) && $_POST['mode'] === 'all') {
        $ids = isset($_POST['ids']) ? json_decode($_POST['ids'], true) : [];
        if (!is_array($ids) || empty($ids)) { echo json_encode(['ok'=>false,'msg'=>'لا توجد عروض']); exit; }
        [$code, $err, $body] = callN8NWebhook(['quote_ids' => array_values(array_map('intval', $ids))]);
        $ok = ($err === '' && $code >= 200 && $code < 300);
        echo json_encode(['ok'=>$ok,'code'=>$code,'err'=>$err,'body'=>$body], JSON_UNESCAPED_UNICODE);
        exit;
    }
    echo json_encode(['ok'=>false,'msg'=>'طلب غير معروف']); exit;
}

/*============= تحميل البيانات للعرض =============*/
$quotes = loadQuotes();
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>عروض الأسعار + n8n</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<style>
  :root{--gold:#977e2b;--gold2:#b89635;--border:#e5e7eb;--bg:#f8f9fa;--txt:#2c2c2c}
  body{margin:0;background:var(--bg);color:var(--txt);font-family:system-ui,-apple-system,Segoe UI,Roboto,'Cairo',sans-serif}
  .wrap{max-width:1100px;margin:0 auto;padding:16px}
  .card{background:#fff;border:1px solid var(--border);border-radius:12px;padding:16px}
  h1{margin:0 0 12px;font-size:20px}
  .btn{cursor:pointer;border:0;border-radius:8px;padding:10px 14px;font-weight:600}
  .btn-gold{background:var(--gold);color:#fff}
  .btn-gold:hover{background:var(--gold2)}
  .btn-gray{background:#666;color:#fff}
  .btn-gray:hover{background:#555}
  table{width:100%;border-collapse:collapse}
  th,td{border-bottom:1px solid var(--border);padding:10px;text-align:right;vertical-align:middle}
  th{background:#fafafa;font-weight:700}
  .id{font-weight:700;color:var(--gold)}
  .price{font-weight:700}
  .row-actions{display:flex;gap:8px;flex-wrap:wrap}
  .chip{display:inline-block;padding:4px 8px;border:1px solid var(--gold);border-radius:999px;background:rgba(151,126,43,.08);color:var(--gold);font-size:12px}
  .overlay{position:fixed;inset:0;background:rgba(0,0,0,.55);display:none;align-items:center;justify-content:center;z-index:9999}
  .spinner{width:50px;height:50px;border:6px solid #ddd;border-top:6px solid var(--gold);border-radius:50%;animation:spin 1s linear infinite}
  @keyframes spin{to{transform:rotate(360deg)}}
  .msg{margin-top:12px;padding:10px;border-radius:8px;font-weight:600}
  .ok{background:#e8f7ee;color:#176a2f;border:1px solid #bde5c8}
  .err{background:#fdeaea;color:#a81818;border:1px solid #f3c2c2}
  .muted{color:#666}
</style>
</head>
<body>
<div class="overlay" id="overlay"><div class="spinner"></div></div>

<div class="wrap">
  <div class="card">
    <h1>قائمة عروض الأسعار</h1>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px">
      <button class="btn btn-gold" id="sendAllBtn"><i class="fa fa-paper-plane"></i> إرسال كل العروض إلى n8n</button>
      <span class="muted">Webhook: <?=htmlspecialchars($N8N_WEBHOOK_URL, ENT_QUOTES, 'UTF-8')?></span>
    </div>

    <div id="msgBox"></div>

    <div style="overflow-x:auto">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>رقم العرض</th>
            <th>التاريخ</th>
            <th>العميل</th>
            <th>البراند</th>
            <th>القيمة</th>
            <th>إرسال إلى n8n</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($quotes)): ?>
            <tr><td colspan="7" style="text-align:center;padding:30px;color:#777">لا توجد عروض.</td></tr>
          <?php else: foreach ($quotes as $q):
              $id = (int)($q['id'] ?? 0);
              $qNum = $q[$FIELDS['quotes']['quoteNumber']] ?? $id;
              $date = formatDate($q[$FIELDS['quotes']['date']] ?? '');
              $client = getClientName($q[$FIELDS['quotes']['client']] ?? null);
              $brand = getBrandName($q[$FIELDS['quotes']['brand']] ?? null);
              $price = formatPrice($q[$FIELDS['quotes']['totalPrice']] ?? null);
          ?>
            <tr data-id="<?=$id?>">
              <td class="id">#<?=convertToEnglishNumbers($id)?></td>
              <td><?=htmlspecialchars(convertToEnglishNumbers($qNum))?></td>
              <td><?=htmlspecialchars($date)?></td>
              <td><?=htmlspecialchars($client)?></td>
              <td><span class="chip"><?=htmlspecialchars($brand)?></span></td>
              <td class="price"><?=htmlspecialchars($price)?></td>
              <td>
                <div class="row-actions">
                  <button class="btn btn-gray sendOneBtn" data-id="<?=$id?>"><i class="fa fa-paper-plane"></i> n8n</button>
                </div>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
(function(){
  const overlay = document.getElementById('overlay');
  const msgBox  = document.getElementById('msgBox');
  const toJSON  = r => r.json();

  function showOverlay(show){ overlay.style.display = show ? 'flex' : 'none'; }
  function flashMsg(text, ok=true){
    msgBox.innerHTML = '<div class="msg '+(ok?'ok':'err')+'">'+text+'</div>';
    setTimeout(()=>{ msgBox.innerHTML=''; }, 5000);
  }

  // إرسال id واحد
  async function sendOne(id){
    showOverlay(true);
    try{
      const form = new FormData();
      form.append('__ajax','1');
      form.append('mode','single');
      form.append('quote_id', id);
      const res = await fetch(location.href, { method:'POST', body:form });
      const data = await res.json();
      if(data.ok){
        flashMsg('تم الإرسال بنجاح للعرض #' + id + ' ✅', true);
      }else{
        flashMsg('فشل الإرسال للعرض #' + id + ' ❌ (HTTP '+(data.code||'?')+')', false);
      }
    }catch(e){
      flashMsg('خطأ اتصال أثناء الإرسال ❌', false);
    }finally{
      showOverlay(false);
    }
  }

  // إرسال كل العروض (IDs دفعة واحدة)
  async function sendAll(){
    // اجمع كل المعرفات من الجدول
    const ids = Array.from(document.querySelectorAll('tbody tr[data-id]')).map(tr => parseInt(tr.dataset.id,10)).filter(Boolean);
    if(!ids.length){ flashMsg('لا توجد عروض لإرسالها', false); return; }

    if(!confirm('إرسال '+ids.length+' عرض/عروض إلى n8n؟')) return;

    showOverlay(true);
    try{
      const form = new FormData();
      form.append('__ajax','1');
      form.append('mode','all');
      form.append('ids', JSON.stringify(ids));
      const res = await fetch(location.href, { method:'POST', body:form });
      const data = await res.json();
      if(data.ok){
        flashMsg('تم إرسال جميع العروض بنجاح ✅', true);
      }else{
        flashMsg('فشل إرسال الكل ❌ (HTTP '+(data.code||'?')+')', false);
      }
    }catch(e){
      flashMsg('خطأ اتصال أثناء إرسال الكل ❌', false);
    }finally{
      showOverlay(false);
    }
  }

  // ربط الأزرار
  document.querySelectorAll('.sendOneBtn').forEach(btn=>{
    btn.addEventListener('click', ()=> sendOne(parseInt(btn.dataset.id,10)));
  });
  document.getElementById('sendAllBtn').addEventListener('click', sendAll);
})();
</script>
</body>
</html>
