<?php
/* contract.php — عقد كلاسيكي رسمي 4 صفحات A4 */

$baserow_url = "https://base.alfagolden.com";
$table_id    = 704;
$token       = "h5qAt85gtiJDAzpH51WrXPywhmnhrPWy";
$quote_id    = isset($_GET['quote_id']) ? intval($_GET['quote_id']) : 1;

const CURRENCY = "ريال سعودي";

/* الطرف الأول (افتراضي عند غياب نص الحقل) */
const PARTY1_NAME_FALLBACK    = "شركة ألفا الذهبية للمصاعد";
const PARTY1_CR_FALLBACK      = "4650236192";
const PARTY1_ADDRESS_FALLBACK = "المدينة المنورة – طريق الهجرة حي القصواء";

/* ========= Helpers ========= */
function fetchQuote($id,$base,$tbl,$tok){
  $url="$base/api/database/rows/table/$tbl/$id/?user_field_names=true";
  $ch=curl_init($url);
  curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_HTTPHEADER=>["Authorization: Token $tok"]]);
  $res=curl_exec($ch); $code=curl_getinfo($ch,CURLINFO_HTTP_CODE); curl_close($ch);
  return $code===200? json_decode($res,true):null;
}

function getVal($field,$default=''){
  if ($field===null || $field==='') return $default;
  if (is_string($field)) return trim($field);
  if (is_array($field)){
    if (isset($field[0])){
      $f=$field[0];
      if (is_array($f)){
        if (array_key_exists('value',$f)){
          $v=$f['value']; return is_array($v)? trim((string)($v['value']??$default)) : trim((string)$v);
        }
        return trim((string)($f['value'] ?? $f['name'] ?? $f['label'] ?? $default));
      }
      return trim((string)$f);
    }
    if (array_key_exists('value',$field)){
      $v=$field['value']; return is_array($v)? trim((string)($v['value']??$default)) : trim((string)$v);
    }
  }
  return $default;
}

function ymd($iso){
  if(!$iso) return '';
  try{ $d=new DateTime($iso); return $d->format('d / m / Y'); }catch(Exception $e){ return $iso; }
}

function nfmt($n){ 
  $v=is_numeric($n)?(float)$n:0; 
  return number_format($v,0,'.',' '); // فواصل كل 3 خانات
}

function ksaPhoneView($s){
  $s = trim((string)$s);
  if ($s==='') return '';
  if (strpos($s,'+966')===0) return '0'.substr($s,4);
  if (strpos($s,'966')===0)  return '0'.substr($s,3);
  if ($s[0]==='5') return '0'.$s;
  return $s;
}

/* ========= Fetch ========= */
$q = fetchQuote($quote_id,$baserow_url,$table_id,$token);
if(!$q) die("لا يمكن الحصول على بيانات السجل #$quote_id.");

/* عام */
$contract_no   = trim((string)($q['رقم العقد'] ?? ''));
$contract_date = ymd($q['تاريخ العقد'] ?? '');

/* الطرف الثاني */
$client_name   = getVal($q['اسم العميل'] ?? '');
$client_gender = getVal($q['الجنس'] ?? '');
$client_id     = getVal($q['الهوية'] ?? '');
$client_phone  = ksaPhoneView(getVal($q['رقم الجوال'] ?? ''));
$addr_offer    = getVal($q['العنوان'] ?? '');
$addr_client   = getVal($q['عنوان العميل'] ?? '');
$title_before  = getVal($q['قبل الاسم'] ?? '');
$title_after   = getVal($q['بعد الاسم'] ?? '');

/* الطرف الأول + آيبان + جملة الافتتاح */
$party1_text   = getVal($q['الطرف الأول'] ?? '');
if ($party1_text===''){
  $party1_text = PARTY1_NAME_FALLBACK.' — سجل تجاري '.PARTY1_CR_FALLBACK.' — العنوان: '.PARTY1_ADDRESS_FALLBACK;
}

/* الآيبان من قاعدة البيانات - حقل 7066 */
$iban_from_db = getVal($q['الايبان'] ?? '');

$opening_clause = getVal($q['جملة البداية للعقد'] ?? '');

/* أسعار */
$price_before   = nfmt($q['السعر قبل ضريبة القيمة المضافة (VAT)'] ?? 0);
$vat_amount     = nfmt($q['ضريبة القيمة المضافة (VAT) 15%'] ?? 0);
$total_with_vat = nfmt($q['السعر شامل ضريبة القيمة المضافة (VAT) 15%'] ?? 0);
$discount       = nfmt($q['مبلغ التخفيض'] ?? 0);

/* تفاصيل السعر */
$pricing = [];
if(!empty($q['تفاصيل السعر'])) $pricing = json_decode((string)$q['تفاصيل السعر'],true) ?: [];
$additions=[];
if(!empty($pricing['additions'])){
  foreach($pricing['additions'] as $a){
    $additions[]=[
      'name'=>trim((string)($a['name']??'')),
      'price'=>nfmt($a['price']??0),
      'calc'=>trim((string)($a['calculation']??'')),
      'total'=>nfmt($a['total']??0),
      'effect'=>trim((string)($a['effect']??'')),
    ];
  }
}

/* الدفعات المحسوبة تلقائياً */
$totalNum = (float)str_replace([' ',','],'',$total_with_vat);
$payments = [
  ['title'=>'الدفعة الأولى','pct'=>35,'stage'=>'الأولى','desc'=>'عند توقيع العقد لتركيب مرحلة السكك وإطارات الأبواب'],
  ['title'=>'الدفعة الثانية','pct'=>30,'stage'=>'الثانية','desc'=>'بعد الانتهاء من المرحلة الأولى (السكك والأبواب)'],
  ['title'=>'الدفعة الثالثة','pct'=>30,'stage'=>'الثالثة','desc'=>'بعد الانتهاء من المرحلة الثانية (الميكانيكا)'],
  ['title'=>'الدفعة الرابعة','pct'=>5 ,'stage'=>'الأخيرة (التسليم)','desc'=>'بعد التسليم النهائي'],
];

/* نصوص المواد 1..11 */
$fields_map = [
  1=>'المادة-1', 2=>'المادة- 2', 3=>'المادة- 3', 4=>'المادة- 4',
  5=>'المادة- 5', 6=>'المادة- 6', 7=>'المادة- 7', 8=>'المادة- 8',
  9=>'المادة- 9', 10=>'المادة- 10', 11=>'المادة- 11'
];
$art=[];
foreach($fields_map as $no=>$name){ $art[$no]=trim((string)getVal($q[$name] ?? '')); }

/* عناوين المواد المطلوبة */
$t = [
  1=>'المقدمة',
  2=>'الغرض من هذا العقد ونطاق العمل',
  3=>'مدة التوريد والتركيب',
  4=>'قيمة العقد',
  5=>'ضمان الأعمال',
  6=>'الدفعات',
  7=>'المواصفات الفنية',
  8=>'أعمال تحضيرية',
  9=>'شروط عامة',
 10=>'نسخ العقد',
];

/* تسمية الهوية/سجل تجاري */
$identity_label = ($client_gender==='مؤسسة' || $client_gender==='شركة') ? 'سجل تجاري رقم' : 'هوية رقم';

/* استخراج مواصفات فنية للمادة 7 */
$elevators_count = $q['عدد المصاعد'] ?? '';
$stops_count = getVal($q['عدد الوقفات']);
$capacity = getVal($q['الحمولة']);
$people_count = getVal($q['عدد الاشخاص']);
$entrance_count = $q['عدد جهات الدخول'] ?? '';
$machine_position = getVal($q['وضع الماكينة']);
$machine_type = getVal($q['نوع المكينة']);
$control_device = getVal($q['جهاز تشغيل المصعد']);
$operation_method = $q['طريقة التشغيل'] ?? '';
$electrical_current = $q['التيار الكهربائي'] ?? '';
$well_material = getVal($q['البئر - مبني من']);
$well_dimensions = getVal($q['البئر - المقاس الداخلي']);
$door_operation_method = $q['طريقة تشغيل الأبواب'] ?? '';
$cabin_finishing = $q['الصاعدة - التشطيب'] ?? '';
$cabin_dimensions = getVal($q['الصاعدة - المقاسات الداخلية']);
$brand = getVal($q['البراند']);
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<title>عقد رقم <?= htmlspecialchars($contract_no ?: $quote_id) ?> - توريد وتركيب مصعد</title>


<style>
:root{
  --primary-color:#2c2c2c;
  --secondary-color:#555;
  --accent-gold:#977e2b;
  --gold-hover:#b89635;
  --light-gray:#f7f7f7;
  --medium-gray:#e5e5e5;
  --border-gray:#ddd;
  --text-dark:#333;
  --text-medium:#666;
  --text-light:#888;
}

@page{size:A4;margin:0;-webkit-print-color-adjust:exact;print-color-adjust:exact}

*{box-sizing:border-box;-webkit-print-color-adjust:exact !important;color-adjust:exact !important;print-color-adjust:exact !important}

body {
    margin: 0;
    font-family: arial;
    color: var(--text-dark);
    background: #fff;
    font-size: 9.5pt;
    line-height: 1.4;
    direction: rtl;
}

.page{
  width:210mm;height:297mm;page-break-after:always;display:flex;flex-direction:column;
  position:relative;overflow:hidden;background:#fff;
}
.page:last-child{page-break-after:auto}

/* رأس الصفحة */
.page-header{
  height:15mm;width:100%;background:linear-gradient(90deg,var(--accent-gold) 0%,var(--gold-hover) 100%);
  display:flex;align-items:center;justify-content:space-between;padding:0 8mm;
  position:relative;z-index:10;
}
.header-info{display:flex;flex-direction:column;color:#fff;font-size:9pt;font-weight:600}
.header-logo{height:11mm;width:auto}

/* محتوى الصفحة */
.page-content{
  flex:1;padding:6mm 8mm 12mm 8mm;
}

/* عنوان العقد الرئيسي */
.contract-title{
  text-align:center;margin-bottom:5mm;padding-bottom:3mm;
  border-bottom:1px solid var(--border-gray);
}
.contract-title h1{
  font-size:20pt;font-weight:700;color:var(--primary-color);
  margin:0;letter-spacing:0.3pt;
}

/* الطرفان */
.parties-section{
  margin-bottom:4mm;
}
.parties-table{
  width:100%;border-collapse:collapse;border:1px solid var(--border-gray);
}
.parties-table td{
  padding:2mm;vertical-align:middle;border-bottom:1px solid var(--border-gray);
  font-size:10pt;line-height:1.3;text-align:center;
}
.parties-table tr:last-child td{border-bottom:none}
.parties-table .label{
  width:18%;background:var(--light-gray);font-weight:600;
  color:var(--text-dark);border-left:1px solid var(--border-gray);
}
.parties-table .content{
  background:#fff;color:var(--text-dark);
}

/* جملة البداية */
.opening-clause{
  padding:3mm 0;margin-bottom:4mm;text-align:justify;
  font-size:10pt;line-height:1.5;color:var(--text-dark);
}

/* عناوين المواد بدون إطارات */
.article-section{
  margin-bottom:3mm;
}
.article-title{
  background:var(--light-gray);color:var(--text-dark);padding:2mm 4mm;
  font-size:10pt;font-weight:600;border-bottom:2px solid var(--accent-gold);
  margin-bottom:2mm;
}
.article-content{
  padding:0 1mm;background:#fff;font-size:10pt;line-height:1.5;
  text-align:justify;color:var(--text-dark);
}

/* الجداول */
.data-table{
  width:100%;border-collapse:collapse;border:1px solid var(--border-gray);
  margin:2mm 0;
}
.data-table th{
  background:var(--medium-gray);padding:1.5mm;text-align:center;
  font-weight:600;color:var(--text-dark);border:1px solid var(--border-gray);
  font-size:10pt;vertical-align:middle;
}
.data-table td{
  padding:1.5mm;border:1px solid var(--border-gray);
  text-align:center;font-size:10pt;background:#fff;vertical-align:middle;
}
.data-table .label-col{
  background:var(--light-gray);font-weight:600;
  width:35%;
}
.data-table .value-col{
  font-weight:500;
}

/* قيم الأسعار */
.price-value{
  display:flex;align-items:center;justify-content:center;
  gap:1.5mm;direction:ltr;font-weight:600;color:var(--accent-gold);
}
.sar-icon{width:3.5mm;height:3.5mm;object-fit:contain;flex-shrink:0}

/* المواصفات الفنية */
.specs-grid{
  display:grid;grid-template-columns:repeat(6,1fr);gap:1.5mm;margin:2mm 0;
}
.spec-item{
  border:1px solid var(--border-gray);padding:1.5mm;background:var(--light-gray);
  text-align:center;
}
.spec-label{
  font-size:6.5pt;color:var(--text-medium);margin-bottom:0.5mm;
  font-weight:600;
}
.spec-value{
  font-size:9.5pt;color:var(--text-dark);font-weight:600;
}

.tech-specs{
  border:1px solid var(--border-gray);background:#fff;
  margin-top:2mm;
}
.tech-specs-header{
  background:var(--medium-gray);padding:1.5mm;font-weight:600;
  color:var(--text-dark);font-size:10pt;border-bottom:1px solid var(--border-gray);
  text-align:center;
}
.tech-specs-content{
  padding:0;
}
.tech-item{
  display:flex;padding:1mm 1.5mm;border-bottom:1px solid var(--border-gray);
  font-size:10pt;
}
.tech-item:last-child{border-bottom:none}
.tech-item .label{
  width:18%;color:var(--text-medium);font-weight:600;
  padding-left:1.5mm;
}
.tech-item .value{
  width:82%;color:var(--text-dark);font-weight:500;
}

/* ملاحظات */
.note{
  background:#fffbf0;border:1px solid #f0e68c;padding:2.5mm;
  margin:2mm 0;font-size:10pt;line-height:1.4;
  color:var(--text-dark);
}

/* التواقيع */
.signatures{
  margin-top:8mm;display:flex;justify-content:space-between;
  gap:12mm;
}
.signature-section{
  width:45%;text-align:center;
}
.signature-title{
  font-size:10pt;font-weight:600;color:var(--primary-color);
  margin-bottom:6mm;padding-bottom:1.5mm;
  border-bottom:1px solid var(--border-gray);
}
.signature-name{
  font-size:9pt;font-weight:500;color:var(--text-dark);
  margin-bottom:12mm;
}
.signature-line{
  border-bottom:1px solid var(--text-dark);
  height:1px;margin:0 12mm;
}

/* ترقيم الصفحات */
.page-number{
  position:absolute;bottom:4mm;left:50%;transform:translateX(-50%);
  width:15mm;height:6mm;background:var(--light-gray);color:var(--text-dark);
  display:flex;align-items:center;justify-content:center;
  font-weight:600;font-size:10pt;border:1px solid var(--border-gray);
}

/* تحسينات الطباعة */
@media print{
  .page{box-shadow:none}
  body{background:#fff}
}

/* تباعد العناصر */
.mb-2{margin-bottom:2mm}
.mb-3{margin-bottom:3mm}
.mb-4{margin-bottom:4mm}

/* النصوص */
.text-center{text-align:center}
.text-right{text-align:right}
.text-justify{text-align:justify}
.font-medium{font-weight:500}
.font-semibold{font-weight:600}
.font-bold{font-weight:700}
</style>









</head>
<body>

<!-- صفحة 1: الطرفان + جملة البداية + المواد (1)-(3) -->
<div class="page">
  <div class="page-header">
    <div class="header-info">
      <span>رقم العقد: <?= htmlspecialchars($contract_no ?: $quote_id) ?></span>
      <span>التاريخ: <?= htmlspecialchars($contract_date ?: '—') ?></span>
    </div>
    <img src="https://alfagolden.com/llogo.png" alt="شعار ألفا الذهبية" class="header-logo" />
  </div>
  
  <div class="page-content">
    <div class="contract-title">
      <h1>عقد اتفاق توريد وتركيب مصعد كهربائي</h1>
    </div>

    <!-- الطرفان -->
    <div class="parties-section">
      <table class="parties-table">
        <tr>
          <td class="label">الطرف الأول</td>
          <td class="content"><?= nl2br(htmlspecialchars($party1_text)) ?></td>
        </tr>
        <tr>
          <td class="label">الطرف الثاني</td>
          <td class="content">
            <div class="font-semibold"><?= htmlspecialchars($client_name ?: '—') ?></div>
            <div>
              العنوان: <?= htmlspecialchars($addr_client ?: $addr_offer ?: '—') ?>
              <?php if($client_id): ?> — <?= $identity_label ?>: <?= htmlspecialchars($client_id) ?><?php endif; ?>
              <?php if($client_phone): ?> — جوال: <?= htmlspecialchars($client_phone) ?><?php endif; ?>
            </div>
          </td>
        </tr>
      </table>
    </div>

    <!-- جملة البداية -->
    <?php if(!empty($opening_clause)): ?>
    <div class="opening-clause">
      <?= nl2br(htmlspecialchars($opening_clause)) ?>
    </div>
    <?php endif; ?>

    <!-- المواد 1-3 -->
    <?php for($i=1; $i<=3; $i++): if(!empty($art[$i])): ?>
    <div class="article-section">
      <div class="article-title">
        المادة رقم (<?= $i ?>) — <?= $t[$i] ?? '' ?>
      </div>
      <div class="article-content">
        <?= nl2br(htmlspecialchars($art[$i])) ?>
      </div>
    </div>
    <?php endif; endfor; ?>
  </div>
  
  <div class="page-number">1/4</div>
</div>

<!-- صفحة 2: المواد (4)-(6) -->
<div class="page">
  <div class="page-header">
    <div class="header-info">
      <span>رقم العقد: <?= htmlspecialchars($contract_no ?: $quote_id) ?></span>
      <span>التاريخ: <?= htmlspecialchars($contract_date ?: '—') ?></span>
    </div>
    <img src="https://alfagolden.com/llogo.png" alt="شعار ألفا الذهبية" class="header-logo" />
  </div>
  
  <div class="page-content">
    <!-- المادة 4: قيمة العقد -->
    <div class="article-section">
      <div class="article-title">
        المادة رقم (4) — <?= $t[4] ?>
      </div>
      <div class="article-content">
        <table class="data-table">
          <tbody>
            <?php if((float)str_replace([' ',','],'',$discount)>0): ?>
            <tr>
              <td class="label-col">مبلغ الخصم</td>
              <td class="value-col">
                <span class="price-value">
                  <img src="https://alfagolden.com/images/sar.svg" alt="ر.س" class="sar-icon" />
                  -<?= htmlspecialchars($discount) ?>
                </span>
              </td>
            </tr>
            <?php endif; ?>
            <tr>
              <td class="label-col">قيمة العقد قبل الضريبة</td>
              <td class="value-col">
                <span class="price-value">
                  <img src="https://alfagolden.com/images/sar.svg" alt="ر.س" class="sar-icon" />
                  <?= htmlspecialchars($price_before) ?>
                </span>
              </td>
            </tr>
            <tr>
              <td class="label-col">ضريبة القيمة المضافة (15%)</td>
              <td class="value-col">
                <span class="price-value">
                  <img src="https://alfagolden.com/images/sar.svg" alt="ر.س" class="sar-icon" />
                  <?= htmlspecialchars($vat_amount) ?>
                </span>
              </td>
            </tr>
            <tr>
              <td class="label-col">الإجمالي شامل الضريبة</td>
              <td class="value-col">
                <span class="price-value">
                  <img src="https://alfagolden.com/images/sar.svg" alt="ر.س" class="sar-icon" />
                  <?= htmlspecialchars($total_with_vat) ?>
                </span>
              </td>
            </tr>
          </tbody>
        </table>

        <?php if(!empty($additions)): ?>
        <div style="margin-top:3mm">
          <div class="font-semibold mb-2" style="color:var(--primary-color)">إضافات ضمن قيمة العقد:</div>
          <table class="data-table">
            <thead>
              <tr>
                <th>الإضافة</th><th>السعر</th><th>طريقة الحساب/الأثر</th><th>الإجمالي</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($additions as $a): ?>
              <tr>
                <td><?= htmlspecialchars($a['name']) ?></td>
                <td>
                  <span class="price-value">
                    <img src="https://alfagolden.com/images/sar.svg" alt="ر.س" class="sar-icon" />
                    <?= htmlspecialchars($a['price']) ?>
                  </span>
                </td>
                <td><?= htmlspecialchars(trim($a['effect'].' '.($a['calc']? "({$a['calc']})":''))) ?></td>
                <td>
                  <span class="price-value">
                    <img src="https://alfagolden.com/images/sar.svg" alt="ر.س" class="sar-icon" />
                    <?= htmlspecialchars($a['total']) ?>
                  </span>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>

        <?php if(!empty($iban_from_db)): ?>
        <div class="note">
          <div style="margin-top:1.5mm;white-space:pre-line;"><?= htmlspecialchars($iban_from_db) ?></div>
        </div>
        <?php endif; ?>

        <?php if(!empty($art[4])): ?>
        <div style="margin-top:3mm;border-top:1px solid var(--border-gray);padding-top:2mm;">
          <?= nl2br(htmlspecialchars($art[4])) ?>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- المادة 5 -->
    <?php if(!empty($art[5])): ?>
    <div class="article-section">
      <div class="article-title">
        المادة رقم (5) — <?= $t[5] ?? '' ?>
      </div>
      <div class="article-content">
        <?= nl2br(htmlspecialchars($art[5])) ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- المادة 6: الدفعات -->
    <div class="article-section">
      <div class="article-title">
        المادة رقم (6) — <?= $t[6] ?>
      </div>
      <div class="article-content">
        <table class="data-table">
          <thead>
            <tr>
              <th>الدفعة</th><th>النسبة</th><th>المبلغ</th><th>المرحلة</th><th>بيان المرحلة</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach($payments as $p): 
            $pct = (float)$p['pct'];
            $amt = $totalNum ? round($totalNum*$pct/100) : 0;
          ?>
            <tr>
              <td><?= htmlspecialchars($p['title']) ?></td>
              <td><?= htmlspecialchars($pct) ?>%</td>
              <td>
                <span class="price-value">
                  <img src="https://alfagolden.com/images/sar.svg" alt="ر.س" class="sar-icon" />
                  <?= nfmt($amt) ?>
                </span>
              </td>
              <td><?= htmlspecialchars($p['stage']) ?></td>
              <td><?= htmlspecialchars($p['desc']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <?php if(!empty($art[6])): ?>
        <div style="margin-top:3mm;border-top:1px solid var(--border-gray);padding-top:2mm;">
          <?= nl2br(htmlspecialchars($art[6])) ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  
  <div class="page-number">2/4</div>
</div>

<!-- صفحة 3: المادة 7 (المواصفات الفنية) + المادة 8 -->
<div class="page">
  <div class="page-header">
    <div class="header-info">
      <span>رقم العقد: <?= htmlspecialchars($contract_no ?: $quote_id) ?></span>
      <span>التاريخ: <?= htmlspecialchars($contract_date ?: '—') ?></span>
    </div>
    <img src="https://alfagolden.com/llogo.png" alt="شعار ألفا الذهبية" class="header-logo" />
  </div>
  
  <div class="page-content">
    <!-- المادة 7: المواصفات الفنية -->
    <div class="article-section">
      <div class="article-title">
        المادة رقم (7) — <?= $t[7] ?>
      </div>
      <div class="article-content">
        <!-- المواصفات الأساسية في صف واحد -->
        <div class="specs-grid">
          <div class="spec-item">
            <div class="spec-label">عدد المصاعد</div>
            <div class="spec-value"><?= htmlspecialchars($elevators_count ?: '—') ?></div>
          </div>
          <div class="spec-item">
            <div class="spec-label">عدد الوقفات</div>
            <div class="spec-value"><?= htmlspecialchars($stops_count ?: '—') ?></div>
          </div>
          <div class="spec-item">
            <div class="spec-label">الحمولة</div>
            <div class="spec-value"><?= htmlspecialchars($capacity ?: '—') ?> كج</div>
          </div>
          <div class="spec-item">
            <div class="spec-label">عدد الأشخاص</div>
            <div class="spec-value"><?= htmlspecialchars($people_count ?: '—') ?></div>
          </div>
          <div class="spec-item">
            <div class="spec-label">جهات الدخول</div>
            <div class="spec-value"><?= htmlspecialchars($entrance_count ?: '—') ?></div>
          </div>
          <div class="spec-item">
            <div class="spec-label">البراند</div>
            <div class="spec-value"><?= htmlspecialchars($brand ?: '—') ?></div>
          </div>
        </div>

        <!-- المواصفات التقنية التفصيلية -->
        <div class="tech-specs">
          <div class="tech-specs-content">
            <div class="tech-item">
              <div class="label">وضع الماكينة:</div>
              <div class="value"><?= htmlspecialchars($machine_position ?: '—') ?></div>
            </div>
            <div class="tech-item">
              <div class="label">نوع المكينة:</div>
              <div class="value"><?= htmlspecialchars($machine_type ?: '—') ?></div>
            </div>
            <div class="tech-item">
              <div class="label">جهاز التشغيل:</div>
              <div class="value"><?= htmlspecialchars($control_device ?: '—') ?></div>
            </div>
            <div class="tech-item">
              <div class="label">طريقة التشغيل:</div>
              <div class="value"><?= htmlspecialchars($operation_method ?: '—') ?></div>
            </div>
            <div class="tech-item">
              <div class="label">التيار الكهربائي:</div>
              <div class="value"><?= htmlspecialchars($electrical_current ?: '—') ?></div>
            </div>
            <div class="tech-item">
              <div class="label">البئر - مبني من:</div>
              <div class="value"><?= htmlspecialchars($well_material ?: '—') ?></div>
            </div>
            <div class="tech-item">
              <div class="label">البئر - المقاس الداخلي:</div>
              <div class="value"><?= htmlspecialchars($well_dimensions ?: '—') ?></div>
            </div>
            <div class="tech-item">
              <div class="label">طريقة تشغيل الأبواب:</div>
              <div class="value"><?= htmlspecialchars($door_operation_method ?: '—') ?></div>
            </div>
            <div class="tech-item">
              <div class="label">تشطيب الصاعدة:</div>
              <div class="value"><?= htmlspecialchars($cabin_finishing ?: '—') ?></div>
            </div>
            <div class="tech-item">
              <div class="label">مقاسات الصاعدة الداخلية:</div>
              <div class="value"><?= htmlspecialchars($cabin_dimensions ?: '—') ?></div>
            </div>
          </div>
        </div>

        <?php if(!empty($art[7])): ?>
        <div style="margin-top:3mm;border-top:1px solid var(--border-gray);padding-top:2mm;">
          <?= nl2br(htmlspecialchars($art[7])) ?>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- المادة 8 -->
    <?php if(!empty($art[8])): ?>
    <div class="article-section">
      <div class="article-title">
        المادة رقم (8) — <?= $t[8] ?? '' ?>
      </div>
      <div class="article-content">
        <?= nl2br(htmlspecialchars($art[8])) ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
  
  <div class="page-number">3/4</div>
</div>

<!-- صفحة 4: المواد (9)-(10) + التواقيع -->
<div class="page">
  <div class="page-header">
    <div class="header-info">
      <span>رقم العقد: <?= htmlspecialchars($contract_no ?: $quote_id) ?></span>
      <span>التاريخ: <?= htmlspecialchars($contract_date ?: '—') ?></span>
    </div>
    <img src="https://alfagolden.com/llogo.png" alt="شعار ألفا الذهبية" class="header-logo" />
  </div>
  
  <div class="page-content">
    <?php for($i=9; $i<=10; $i++): if(!empty($art[$i])): ?>
    <div class="article-section">
      <div class="article-title">
        المادة رقم (<?= $i ?>) — <?= $t[$i] ?? '' ?>
      </div>
      <div class="article-content">
        <?= nl2br(htmlspecialchars($art[$i])) ?>
      </div>
    </div>
    <?php endif; endfor; ?>

    <!-- التواقيع -->
    <div class="signatures">
      <div class="signature-section">
        <div class="signature-title">الطرف الأول</div>
        <div class="signature-name"><?= htmlspecialchars(PARTY1_NAME_FALLBACK) ?></div>
        <div class="signature-line"></div>
      </div>
      <div class="signature-section">
        <div class="signature-title">الطرف الثـاني</div>
        <div class="signature-name"><?= htmlspecialchars($client_name ?: 'ــــــــــــ') ?></div>
        <div class="signature-line"></div>
      </div>
    </div>
  </div>
  
  <div class="page-number">4/4</div>
</div>

</body>
</html>