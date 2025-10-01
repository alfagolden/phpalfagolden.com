<?php
/*********************************************************************
 * Unified Quote Editor - v3 (Intelligent UX + Dynamic Settings)
 *
 * - Intelligent client/project selection (no manual "new/existing").
 * - Robust error handling to prevent JSON syntax errors.
 * - Professional UI/UX inspired by original files.
 * - Full dynamic settings management from Baserow table 705.
 *********************************************************************/

// Production safety: Disable direct error printing to avoid breaking JSON
ini_set('display_errors', 0);
error_reporting(0);

// ===== API CONFIG =====
$BASEROW_API_BASE  = rtrim(getenv('BASEROW_API_BASE') ?: 'https://base.alfagolden.com', '/');
$BASEROW_API_TOKEN = 'h5qAt85gtiJDAzpH51WrXPywhmnhrPWy'; // Your temporary token

// ===== TABLE & FIELD IDs =====
const TBL_QUOTES    = 704;
const TBL_CLIENTS   = 702;
const TBL_PROJECTS  = 706;
const TBL_SETTINGS  = 705;

// IMPORTANT: Specify the field ID in your settings table (705) that holds the JSON data.
// If you don't have one, create a "Long Text" field and put its ID here.
const SETTINGS_JSON_FIELD_ID = 7086; // <--- قم بتغيير هذا الرقم إلى ID الحقل الصحيح لديك

// By default, the script will use the first row in the settings table.
// If you want to use a specific row, set its ID here.
const SETTINGS_ROW_ID = 1;

// ====== Core API Functions ======
function api($method, $path, $query = [], $data = null) {
    global $BASEROW_API_BASE, $BASEROW_API_TOKEN;
    $url = $BASEROW_API_BASE . $path;
    if (!empty($query)) $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($query);
    
    $ch = curl_init($url);
    $headers = [
        'Authorization: Token ' . $BASEROW_API_TOKEN,
        'Content-Type: application/json',
        'Accept: application/json',
    ];
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
    ]);
    if (!is_null($data)) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
    
    $resp = curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $json_resp = json_decode($resp, true);

    return [
        'ok'   => ($err === '' && $code >= 200 && $code < 300),
        'code' => $code,
        'err'  => $err,
        'raw'  => $resp,
        'json' => $json_resp,
    ];
}

function list_rows($tableId, $search = '', $pageSize = 20) {
    $query = ['size' => $pageSize, 'search' => $search, 'user_field_names' => true];
    return api('GET', "/api/database/rows/table/{$tableId}/", $query);
}
function get_row($tableId, $rowId) {
    return api('GET', "/api/database/rows/table/{$tableId}/{$rowId}/");
}
function create_row($tableId, $payload) {
    return api('POST', "/api/database/rows/table/{$tableId}/", ['user_field_names' => 'false'], $payload);
}
function update_row($tableId, $rowId, $payload) {
    return api('PATCH', "/api/database/rows/table/{$tableId}/{$rowId}/", ['user_field_names' => 'false'], $payload);
}

// ====== AJAX Endpoint Logic ======
if (isset($_GET['action'])) {
    header('Content-Type: application/json; charset=UTF-8');
    
    // Global error handler for all AJAX actions
    set_error_handler(function($severity, $message, $file, $line) {
        http_response_code(500);
        echo json_encode(['error' => 'A server error occurred.', 'details' => $message]);
        exit;
    });

    try {
        switch ($_GET['action']) {
            case 'search':
                if (!isset($_GET['table_id'])) throw new Exception("Table ID is missing.");
                $res = list_rows((int)$_GET['table_id'], $_GET['q'] ?? '');
                if (!$res['ok']) throw new Exception($res['raw'] ?? 'Failed to fetch data.');
                
                $items = array_map(function($row) {
                    $label = $row['الاسم'] ?? $row['name'] ?? null;
                    if ($label === null) {
                        foreach ($row as $key => $value) {
                            if (is_string($value) && !empty(trim($value))) {
                                $label = $value;
                                break;
                            }
                        }
                    }
                    $label = $label ?? '#' . $row['id'];
                    return ['id' => $row['id'], 'label' => $label];
                }, $res['json']['results'] ?? []);
                
                echo json_encode(['items' => $items]);
                break;

            case 'get_settings':
                $res = get_row(TBL_SETTINGS, SETTINGS_ROW_ID);
                if (!$res['ok']) {
                    if ($res['code'] === 404) {
                        $default_json = json_encode(['example_options' => ['Option 1', 'Option 2']], JSON_UNESCAPED_UNICODE);
                        $default_settings = ['field_' . SETTINGS_JSON_FIELD_ID => $default_json];
                        $create_res = create_row(TBL_SETTINGS, $default_settings);
                        if ($create_res['ok']) {
                             echo json_encode(json_decode($create_res['json']['field_' . SETTINGS_JSON_FIELD_ID], true));
                        } else {
                            throw new Exception('Failed to create default settings row. Please check table/field IDs and permissions.');
                        }
                    } else {
                        throw new Exception($res['raw'] ?? 'Failed to fetch settings.');
                    }
                } else {
                    $settings_json = $res['json']['field_' . SETTINGS_JSON_FIELD_ID] ?? '{}';
                    echo json_encode(json_decode($settings_json, true));
                }
                break;

            case 'save_settings':
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Invalid request method.');
                $payload = json_decode(file_get_contents('php://input'), true);
                if (json_last_error() !== JSON_ERROR_NONE) throw new Exception('Invalid JSON data provided.');
                
                $data = ['field_' . SETTINGS_JSON_FIELD_ID => json_encode($payload, JSON_UNESCAPED_UNICODE)];
                $res = update_row(TBL_SETTINGS, SETTINGS_ROW_ID, $data);
                if (!$res['ok']) throw new Exception($res['raw'] ?? 'Failed to save settings.');
                
                echo json_encode(['success' => true, 'message' => 'تم حفظ الإعدادات بنجاح.']);
                break;

            default:
                throw new Exception('Unknown action.');
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred.', 'details' => $e->getMessage()]);
    }
    exit;
}

// ====== Main Form Submission Logic ======
$errors = []; $info = []; $saved_quote = null;

function sanitize_num($v) { return is_numeric($v) ? (float)$v : null; }
function link_ids($ids) { return array_values(array_filter(array_map('intval', is_array($ids) ? $ids : [$ids]))); }

function ensure_entity($id, $name, $tableId, $nameFieldId) {
    if (!empty($id) && is_numeric($id)) {
        return (int)$id; // Use existing ID
    }
    if (!empty($name)) {
        // Create new entity
        $res = create_row($tableId, ['field_' . $nameFieldId => $name]);
        if ($res['ok']) return (int)$res['json']['id'];
    }
    return null; // Failure
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_quote') {
    // Assuming name field is 6912 for clients and 6916 for projects
    $client_id = ensure_entity($_POST['client_id'] ?? null, $_POST['client_search'] ?? null, TBL_CLIENTS, 6912);
    if (!$client_id) $errors[] = 'يجب تحديد عميل موجود أو إدخال اسم لعميل جديد.';

    $project_id = ensure_entity($_POST['project_id'] ?? null, $_POST['project_search'] ?? null, TBL_PROJECTS, 6916);
    if (!$project_id) $errors[] = 'يجب تحديد مشروع موجود أو إدخال اسم لمشروع جديد.';

    if (empty($errors)) {
        $quote_id = $_POST['quote_id'] ?? null;
        $payload = [];
        
        // Link client and project
        $payload['field_6786'] = link_ids($client_id);
        $payload['field_6788'] = link_ids($project_id);

        // Collect all other fields (f_XXXX)
        foreach ($_POST as $key => $value) {
            if (str_starts_with($key, 'f_')) {
                $field_id = substr($key, 2);
                if (is_numeric($field_id) && $value !== '') {
                    // For array-type fields, Baserow often expects an array.
                    // This is a simple case; adjust if you have multi-selects.
                    $array_fields = [6796, 6797, 6798, 6799, 6784, 6785]; 
                    if (in_array((int)$field_id, $array_fields)) {
                        $payload['field_' . $field_id] = [$value];
                    } else {
                        $payload['field_' . $field_id] = is_numeric($value) ? sanitize_num($value) : $value;
                    }
                }
            }
        }
        
        if (!empty($quote_id) && is_numeric($quote_id)) {
            $res = update_row(TBL_QUOTES, (int)$quote_id, $payload);
        } else {
            $res = create_row(TBL_QUOTES, $payload);
            if($res['ok']) $quote_id = $res['json']['id'];
        }

        if ($res['ok']) {
            $info[] = 'تم حفظ عرض السعر بنجاح! رقم العرض: ' . $quote_id;
            $saved_quote = $res['json'];
        } else {
            $errors[] = 'فشل حفظ عرض السعر: ' . ($res['raw'] ?? 'خطأ غير معروف');
        }
    }
}
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>إدارة عرض السعر (ذكي ومحسّن)</title>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
  :root{--gold:#977e2b;--gold-hover:#b89635;--gold-light:rgba(151,126,43,.1);--dark:#212529;--medium:#6c757d;--light:#f8f9fa;--white:#fff;--border:#dee2e6;--radius:8px;--shadow:0 4px 12px rgba(0,0,0,.08)}
  body{margin:0;background:var(--light);font-family:'Cairo',sans-serif;color:var(--dark);font-size:14px}
  .wrap{max-width:1200px;margin:30px auto;padding:0 15px}
  header{display:flex;gap:15px;align-items:center;margin-bottom:20px}
  header .logo{width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,var(--gold),var(--gold-hover));box-shadow:0 6px 20px rgba(151,126,43,.4)}
  h1{font-size:24px;margin:0;font-weight:700}
  .tabs{display:flex;border-bottom:1px solid var(--border);margin-bottom:20px}
  .tab-btn{border:none;background:0 0;color:var(--medium);padding:12px 18px;cursor:pointer;transition:.2s;border-bottom:3px solid transparent;font-weight:600;font-size:15px}
  .tab-btn.active,.tab-btn:hover{color:var(--gold);border-bottom-color:var(--gold)}
  .card{background:var(--white);border:1px solid var(--border);border-radius:var(--radius);padding:24px;margin-bottom:20px;box-shadow:var(--shadow)}
  .row{display:grid;grid-template-columns:repeat(12,1fr);gap:20px;margin-bottom:16px}
  .col-3{grid-column:span 3}.col-4{grid-column:span 4}.col-6{grid-column:span 6}.col-12{grid-column:span 12}
  label{display:block;font-size:13px;font-weight:600;color:var(--medium);margin-bottom:8px}
  input,select,textarea{width:100%;background:var(--white);border:1px solid #ced4da;color:var(--dark);padding:10px 14px;border-radius:var(--radius);outline:none;font-family:inherit;font-size:14px}
  input:focus,select:focus,textarea:focus{border-color:var(--gold);box-shadow:0 0 0 3px var(--gold-light)}
  textarea{min-height:90px}
  .btn{padding:12px 24px;border-radius:var(--radius);border:1px solid transparent;background:var(--gold);color:var(--white);cursor:pointer;font-weight:600;font-size:15px;transition:.2s}
  .btn:hover{background:var(--gold-hover);transform:translateY(-1px)}
  .btn.secondary{background:var(--medium);border-color:var(--medium)}
  .actions{display:flex;gap:12px;justify-content:flex-end;margin-top:24px;padding-top:20px;border-top:1px solid var(--border)}
  .alert{padding:15px;margin-bottom:20px;border-radius:var(--radius);border:1px solid transparent}
  .alert.success{background-color:#d1e7dd;border-color:#badbcc;color:#0f5132}
  .alert.error{background-color:#f8d7da;border-color:#f5c2c7;color:#842029}
  .search-box{position:relative}
  .search-results{position:absolute;top:100%;left:0;right:0;background:var(--white);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow);z-index:100;max-height:220px;overflow-y:auto;display:none}
  .search-item{padding:12px 16px;cursor:pointer;border-bottom:1px solid var(--border)}
  .search-item:hover{background:var(--gold-light)}
  .search-item:last-child{border:0}
  .loader{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(255,255,255,.8);display:flex;align-items:center;justify-content:center;z-index:9998;display:none}
  .spinner{width:36px;height:36px;border:4px solid var(--gold-light);border-top-color:var(--gold);border-radius:50%;animation:spin .8s linear infinite}
  @keyframes spin{to{transform:rotate(360deg)}}
  @media(max-width:768px){.col-3,.col-4,.col-6{grid-column:span 12}}
</style>
</head>
<body>
<div class="wrap">
  <header>
    <div class="logo"></div>
    <div>
      <h1>نموذج عرض السعر المحسّن</h1>
      <div class="muted">إدارة العملاء، المشاريع، المواصفات، والإعدادات</div>
    </div>
  </header>

  <div id="loader" class="loader"><div class="spinner"></div></div>

  <div id="alert-container">
    <?php if(!empty($info)): ?><div class="alert success"><?= implode('<br>', $info) ?></div><?php endif; ?>
    <?php if(!empty($errors)): ?><div class="alert error"><?= implode('<br>', $errors) ?></div><?php endif; ?>
  </div>

  <form method="post" id="qform" class="card">
    <input type="hidden" name="action" value="save_quote" />
    <div class="tabs">
      <button type="button" class="tab-btn active" data-tab="main">البيانات الأساسية</button>
      <button type="button" class="tab-btn" data-tab="specs">المواصفات الفنية</button>
      <button type="button" class="tab-btn" data-tab="texts">النصوص والخدمات</button>
      <button type="button" class="tab-btn" data-tab="settings">الإعدادات</button>
    </div>

    <!-- Main Pane -->
    <section data-pane="main" class="pane">
      <div class="row">
        <div class="col-6">
          <label for="client_search">العميل</label>
          <div class="search-box">
            <input type="text" id="client_search" name="client_search" placeholder="ابحث أو أدخل اسم عميل جديد..." autocomplete="off"/>
            <input type="hidden" name="client_id" id="client_id"/>
            <div id="client_results" class="search-results"></div>
          </div>
        </div>
        <div class="col-6">
          <label for="project_search">المشروع</label>
          <div class="search-box">
            <input type="text" id="project_search" name="project_search" placeholder="ابحث أو أدخل اسم مشروع جديد..." autocomplete="off"/>
            <input type="hidden" name="project_id" id="project_id"/>
            <div id="project_results" class="search-results"></div>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-6">
            <label for="quote_id">ID عرض السعر (للتحديث)</label>
            <input type="text" name="quote_id" id="quote_id" placeholder="اتركه فارغًا لإنشاء عرض جديد" />
        </div>
        <div class="col-6">
            <label for="f_6984">السعر الإجمالي</label>
            <input type="number" step="0.01" name="f_6984" id="f_6984"/>
        </div>
      </div>
    </section>

    <!-- Specs Pane -->
    <section data-pane="specs" class="pane" style="display:none">
      <div class="row">
        <?php
        $fields=[
          6794=>'عدد المصاعد',6796=>'وضع الماكينة',6797=>'عدد الوقفات',6798=>'الحمولة',6799=>'عدد الأشخاص',
          6802=>'نوع المكينة',6803=>'جهاز التشغيل',6804=>'طريقة التشغيل',6805=>'مسميات الوقفات',6806=>'التيار',
          6813=>'سكك الصاعدة',6814=>'سكك الموازنة',6815=>'حبال الجر',6816=>'الكابل المرن',6817=>'الإطار الحامل',
          6818=>'تشطيب الصاعدة',6820=>'السقف',6821=>'إضاءة طوارئ',6822=>'جهاز تحريك',6823=>'الأرضية',
          6825=>'لوحة داخلية',6826=>'تشطيب خارجية',6827=>'خارجية رئيسية',6828=>'خارجية أخرى',
          6829=>'أجهزة اتصال',6830=>'أجهزة طوارئ',6831=>'أجهزة إنارة',6832=>'أجهزة أمان',6833=>'ستارة ضوئية',
          6834=>'منظم سرعة',6835=>'مخففات صدمات',6836=>'نهاية مشوار',6837=>'كامة تأمين',6838=>'مزايت',6839=>'مفتاح خارجي',6840=>'توصيلات',
          6998=>'تشغيل أبواب',7000=>'باب داخلي',
          7134=>'نوع المصعد',7135=>'نوع المبنى',7136=>'السرعة',7138=>'تشطيب أبواب'
        ];
        foreach($fields as $fid=>$label):?>
        <div class="col-3">
          <label for="f_<?=$fid?>"><?=htmlspecialchars($label)?></label>
          <input type="text" name="f_<?=$fid?>" id="f_<?=$fid?>" />
        </div>
        <?php endforeach; ?>
      </div>
    </section>
    
    <!-- Texts Pane -->
    <section data-pane="texts" class="pane" style="display:none">
      <div class="row">
        <div class="col-4"><label for="f_7130">عناية قبل الاسم</label><input type="text" name="f_7130" id="f_7130"/></div>
        <div class="col-4"><label for="f_7131">عناية الاسم</label><input type="text" name="f_7131" id="f_7131"/></div>
        <div class="col-4"><label for="f_7132">عناية بعد الاسم</label><input type="text" name="f_7132" id="f_7132"/></div>
      </div>
       <div class="row">
        <div class="col-6"><label for="f_6791">جملة البداية</label><textarea name="f_6791" id="f_6791"></textarea></div>
        <div class="col-6"><label for="f_6978">الجملة التمهيدية</label><textarea name="f_6978" id="f_6978"></textarea></div>
      </div>
      <div class="row">
        <div class="col-6"><label for="f_6974">التوريد والتركيب</label><textarea name="f_6974" id="f_6974"></textarea></div>
        <div class="col-6"><label for="f_6905">أعمال تحضيرية</label><textarea name="f_6905" id="f_6905"></textarea></div>
      </div>
      <div class="row">
        <div class="col-6"><label for="f_6906">الضمان والصيانة</label><textarea name="f_6906" id="f_6906"></textarea></div>
        <div class="col-6"><label for="f_6981">الجملة الختامية</label><textarea name="f_6981" id="f_6981"></textarea></div>
      </div>
    </section>

    <!-- Settings Pane -->
    <section data-pane="settings" class="pane" style="display:none">
      <label for="settings_json">محتوى الإعدادات (JSON)</label>
      <textarea id="settings_json" rows="15" placeholder="جاري تحميل الإعدادات..."></textarea>
      <div class="actions">
        <button type="button" class="btn secondary" id="settings_reload">إعادة تحميل</button>
        <button type="button" class="btn" id="settings_save">حفظ الإعدادات</button>
      </div>
    </section>

    <div class="actions">
      <button type="submit" class="btn">حفظ عرض السعر</button>
    </div>
  </form>
</div>

<script>
class QuoteEditorApp {
    constructor() {
        this.loader = document.getElementById('loader');
        this.alertContainer = document.getElementById('alert-container');
        this.settingsCache = null;

        this.initTabs();
        this.initSmartSearch('client', <?= TBL_CLIENTS ?>);
        this.initSmartSearch('project', <?= TBL_PROJECTS ?>);
        this.initSettings().then(() => this.initDynamicSelects());
    }

    showLoader(show) { this.loader.style.display = show ? 'flex' : 'none'; }
    
    showAlert(message, type = 'success') {
        const alert = document.createElement('div');
        alert.className = `alert ${type}`;
        alert.textContent = message;
        this.alertContainer.innerHTML = '';
        this.alertContainer.appendChild(alert);
        setTimeout(() => alert.remove(), 5000);
    }

    initTabs() {
        const tabs = document.querySelectorAll('.tab-btn');
        const panes = document.querySelectorAll('.pane');
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                panes.forEach(p => p.style.display = p.dataset.pane === tab.dataset.tab ? '' : 'none');
            });
        });
    }

    initSmartSearch(type, tableId) {
        const searchInput = document.getElementById(`${type}_search`);
        const idInput = document.getElementById(`${type}_id`);
        const resultsDiv = document.getElementById(`${type}_results`);
        let debounce;

        searchInput.addEventListener('input', () => {
            idInput.value = ''; // Clear ID on new input, signaling a new entity
            clearTimeout(debounce);
            const query = searchInput.value.trim();
            if (query.length < 2) { resultsDiv.style.display = 'none'; return; }
            
            debounce = setTimeout(async () => {
                try {
                    const res = await fetch(`?action=search&table_id=${tableId}&q=${encodeURIComponent(query)}`);
                    if (!res.ok) throw new Error('Network response was not ok.');
                    const data = await res.json();
                    if (data.error) throw new Error(data.details || data.error);
                    resultsDiv.innerHTML = data.items.map(item => `<div class="search-item" data-id="${item.id}">${item.label}</div>`).join('');
                    resultsDiv.style.display = 'block';
                } catch(e) {
                    console.error(`Search failed for ${type}:`, e);
                }
            }, 300);
        });

        resultsDiv.addEventListener('click', e => {
            if (e.target.classList.contains('search-item')) {
                searchInput.value = e.target.textContent;
                idInput.value = e.target.dataset.id;
                resultsDiv.style.display = 'none';
            }
        });
        
        document.addEventListener('click', (e) => {
            if (!searchInput.parentElement.contains(e.target)) {
                 resultsDiv.style.display = 'none';
            }
        });
    }

    async initSettings() {
        const reloadBtn = document.getElementById('settings_reload');
        const saveBtn = document.getElementById('settings_save');
        const textarea = document.getElementById('settings_json');

        const loadSettings = async () => {
            this.showLoader(true);
            try {
                const res = await fetch('?action=get_settings');
                if (!res.ok) throw new Error((await res.json()).details || 'Failed to load settings.');
                const data = await res.json();
                this.settingsCache = data;
                textarea.value = JSON.stringify(data, null, 2);
            } catch (e) { this.showAlert(`فشل تحميل الإعدادات: ${e.message}`, 'error'); console.error(e); }
            this.showLoader(false);
        };

        reloadBtn.addEventListener('click', loadSettings);
        saveBtn.addEventListener('click', async () => {
            try {
                const data = JSON.parse(textarea.value);
                this.showLoader(true);
                const res = await fetch('?action=save_settings', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });
                if (!res.ok) throw new Error((await res.json()).details || 'Failed to save settings.');
                this.showAlert('تم حفظ الإعدادات بنجاح.', 'success');
                this.settingsCache = data;
                this.initDynamicSelects();
            } catch (e) { this.showAlert(`بيانات JSON غير صالحة أو فشل الحفظ: ${e.message}`, 'error'); console.error(e); }
            finally { this.showLoader(false); }
        });

        await loadSettings();
    }

    initDynamicSelects() {
        if (!this.settingsCache) return;

        const fieldToSettingsKeyMap = {
            'f_6802': 'machine_types',
            'f_6804': 'ops_methods',
            'f_7136': 'speeds',
            // Add other field-to-setting mappings here
            // 'f_XXXX': 'your_settings_key_in_json'
        };

        for (const [fieldId, settingsKey] of Object.entries(fieldToSettingsKeyMap)) {
            const el = document.getElementById(fieldId);
            const options = this.settingsCache[settingsKey];

            if (el && Array.isArray(options)) {
                let select = el.tagName === 'SELECT' ? el : document.createElement('select');
                select.innerHTML = '';
                
                const defaultOption = document.createElement('option');
                defaultOption.value = ''; defaultOption.textContent = 'اختر...';
                select.appendChild(defaultOption);

                options.forEach(opt => {
                    const option = document.createElement('option');
                    option.value = option.textContent = opt;
                    select.appendChild(option);
                });
                
                if (el.tagName !== 'SELECT') {
                    select.id = el.id;
                    select.name = el.name;
                    el.replaceWith(select);
                }
            }
        }
    }
}

document.addEventListener('DOMContentLoaded', () => new QuoteEditorApp());
</script>
</body>
</html>