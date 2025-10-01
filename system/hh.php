<?php
// تأكد من بدء الجلسة قبل أي مخرجات HTML
session_set_cookie_params([
    'lifetime' => 7*24*60*60, 'path' => '/', 'domain' => '.alfagolden.com',
    'secure' => true, 'httponly' => true, 'samesite' => 'Lax'
]);
session_start();

// التحقق من أن المستخدم مسجل دخوله
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header('Location: https://alfagolden.com/system/login.php');
    exit;
}

// التحقق من صلاحية الجلسة
$session_timeout_duration = 7 * 24 * 60 * 60; // أسبوع
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > $session_timeout_duration)) {
    session_unset();
    session_destroy();
    header('Location: https://alfagolden.com/?session_expired=true'); 
    exit;
}
$_SESSION['login_time'] = time(); // تحديث وقت آخر نشاط

// --- دالة مساعدة لجلب رابط الصفحة الرئيسية من Baserow (محدثة لاستخدام ID الحقل) ---
function get_user_homepage_url($user_id) {
    $baserow_api_url = 'https://base.alfagolden.com/api/database/rows/table/702/';
    $baserow_token = 'h5qAt85gtiJDAzpH51WrXPywhmnhrPWy';
    $field_id = 'field_7008'; // استخدام ID الحقل لضمان الدقة
    
    $ch = curl_init();
    // إزالة user_field_names=true و استخدام ID الحقل في include
    $request_url = $baserow_api_url . $user_id . '/?include=' . $field_id;
    curl_setopt($ch, CURLOPT_URL, $request_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Token ' . $baserow_token, 'Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // !!! غير آمن في الإنتاج، يجب ضبطه على true !!!
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    
    $response_body = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        $error_details = "CURL Error: " . $curl_error . " | Request URL: " . $request_url;
        error_log($error_details);
        return ['success' => false, 'error' => $error_details];
    }

    if ($http_code == 200) {
        $data = json_decode($response_body, true);
        // التحقق من وجود الحقل field_7008 وأنه مصفوفة تحتوي على قيمة
        if (isset($data[$field_id]) && is_array($data[$field_id]) && !empty($data[$field_id][0]['value'])) {
            return ['success' => true, 'url' => $data[$field_id][0]['value']]; // نجح، أرجع الرابط
        } else {
            $error_details = "Success (200 OK) but '{$field_id}' is empty or not found in response for User ID {$user_id}. Response: " . $response_body;
            error_log($error_details);
            return ['success' => false, 'error' => $error_details];
        }
    } else {
        $error_details = "Baserow API Error. HTTP Status: {$http_code} | Request URL: " . $request_url . " | Response: " . $response_body;
        error_log($error_details);
        return ['success' => false, 'error' => $error_details];
    }
}

// --- جزء PHP الذي يتعامل مع طلب AJAX ---
if (isset($_GET['action']) && $_GET['action'] === 'get_homepage') {
    header('Content-Type: application/json');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    
    $result = get_user_homepage_url($_SESSION['user_id']);
    
    if ($result['success']) {
        echo json_encode(['success' => true, 'url' => $result['url']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'فشل جلب رابط الصفحة الرئيسية.', 'details' => $result['error']]);
    }
    exit; // إنهاء التنفيذ بعد إرجاع JSON
}


// --- جزء PHP الذي يقوم بتجهيز الصفحة عند التحميل الأولي ---
$initial_load_result = get_user_homepage_url($_SESSION['user_id']);
$load_error = !$initial_load_result['success'];
$initial_iframe_src = $load_error ? 'about:blank' : $initial_load_result['url'];
$initial_error_details = $load_error ? $initial_load_result['error'] : '';

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ألفا الذهبية - الصفحة الرئيسية</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --gold: #977e2b; --gold-hover: #b89635;
            --dark-gray: #2c2c2c; --medium-gray: #666666; --light-gray: #f8f9fa;
            --border-color: #e5e7eb; --white: #ffffff;
            --error: #dc3545; --logout-btn-bg: #364153; --logout-btn-hover-bg: #4a5568;
            --space-sm: 8px; /* المسافة المستخدمة في gap */
            --space-md: 16px; 
            --space-lg: 24px;
            --transition-normal: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --navbar-max-width: 1280px;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Cairo', sans-serif; font-size: 14px; direction: rtl; color: var(--dark-gray); background-color: var(--light-gray); display: flex; flex-direction: column; height: 100vh; overflow: hidden; }
        .main-navbar { background: var(--white); box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); height: 70px; display: flex; align-items: center; flex-shrink: 0; z-index: 1000; border-bottom: 1px solid var(--border-color); }
        .navbar-inner { width: 100%; max-width: var(--navbar-max-width); margin: 0 auto; display: flex; align-items: center; justify-content: space-between; padding: 0 var(--space-lg); }
        .navbar-section { display: flex; align-items: center; gap: var(--space-md); }
        .navbar-brand { display: flex; align-items: center; gap: var(--space-md); /* زيادة المسافة قليلاً */ font-size: 18px; font-weight: 700; color: var(--gold); cursor: pointer; text-decoration: none; transition: var(--transition-normal); }
        .navbar-brand:hover { color: var(--gold-hover); }
        .navbar-brand .logo-img { width: 40px; height: 40px; border-radius: 6px; object-fit: contain; }
        .navbar-brand .brand-spinner { width: 24px; height: 24px; border: 2px solid rgba(151, 126, 43, 0.3); border-top: 2px solid var(--gold); border-radius: 50%; animation: spin 1s linear infinite; }
        
        /* الأزرار الأساسية */
        .btn { 
            display: inline-flex; align-items: center; justify-content: center; 
            gap: var(--space-sm); /* المسافة بين الأيقونة والنص */
            padding: 8px 16px; min-height: 40px; border: none; border-radius: 6px; 
            font-size: 14px; font-weight: 600; cursor: pointer; 
            transition: var(--transition-normal); text-decoration: none; white-space: nowrap; 
            font-family: 'Cairo', sans-serif; 
        }

        /* تعديل المسافات داخل زر الخروج */
        .btn .fa-sign-out-alt {
            font-size: 16px; /* حجم الأيقونة */
        }
        .btn .btn-text {
            line-height: 1; /* لضمان عدم وجود ارتفاع إضافي من النص */
        }
        
        .btn-secondary { background: var(--medium-gray); color: var(--white); }
        .btn-secondary:hover { background: #555; transform: translateY(-1px); }
        .btn-danger { background: var(--logout-btn-bg); color: var(--white); }
        .btn-danger:hover { background: var(--logout-btn-hover-bg); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(54, 65, 83, 0.3); }
        .btn-icon { width: 40px; height: 40px; padding: 0; border-radius: 6px; }
        .btn-icon .fas { font-size: 16px; }
        .btn-spinner { width: 16px; height: 16px; border: 2px solid rgba(255, 255, 255, 0.3); border-top: 2px solid var(--white); border-radius: 50%; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .btn.loading, .navbar-brand.loading { pointer-events: none; opacity: 0.7; }
        .content-iframe { flex: 1; width: 100%; border: none; display: block; background-color: var(--white); }
        .page-loader { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(4px); display: none; align-items: center; justify-content: center; z-index: 9999; flex-direction: column; gap: var(--space-md); }
        .page-loader.show { display: flex; }
        .spinner { width: 32px; height: 32px; border: 3px solid var(--border-color); border-top: 3px solid var(--gold); border-radius: 50%; animation: spin 1s linear infinite; }
        .loader-text { color: var(--medium-gray); font-size: 14px; font-weight: 500; }
        .error-message { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background-color: var(--error); color: var(--white); padding: var(--space-lg); border-radius: 8px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2); z-index: 10000; text-align: center; font-size: 16px; display: none; }
    </style>
</head>
<body>
    <div class="page-loader" id="pageLoader"><div class="spinner"></div><div class="loader-text">جاري التحميل...</div></div>
    <div class="error-message" id="errorMessage">حدث خطأ في تحميل الصفحة الرئيسية. سيتم إعادة توجيهك...</div>
    <nav class="main-navbar">
        <div class="navbar-inner">
            <div class="navbar-section">
                <a href="#" class="navbar-brand" id="homeLink">
                    <img src="https://alfagolden.com/iconalfa.png" alt="ألفا الذهبية" class="logo-img">
                    <span class="brand-text">ألفا الذهبية</span>
                </a>
            </div>
            <div class="navbar-section">
                <button id="profileBtn" class="btn btn-secondary btn-icon" title="الملف الشخصي"><i class="fas fa-user-circle"></i></button>
                <button id="logoutBtn" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="btn-text">خروج</span>
                </button>
            </div>
        </div>
    </nav>
    <iframe id="mainIframe" class="content-iframe" src="<?php echo htmlspecialchars($initial_iframe_src); ?>" title="لوحة تحكم ألفا الذهبية"></iframe>

    <script>
        const redirectOnErrorUrl = "https://alfagolden.com/";
        const ajaxEndpoint = "?action=get_homepage";

        const elements = {
            homeLink: document.getElementById('homeLink'),
            profileBtn: document.getElementById('profileBtn'),
            logoutBtn: document.getElementById('logoutBtn'),
            mainIframe: document.getElementById('mainIframe'),
            pageLoader: document.getElementById('pageLoader'),
            errorMessage: document.getElementById('errorMessage'),
            brandLogo: document.querySelector('.navbar-brand .logo-img'),
            brandText: document.querySelector('.navbar-brand .brand-text')
        };
        
        let isLoading = false;

        function showLoader() { if (!isLoading) { isLoading = true; elements.pageLoader.classList.add('show'); } }
        function hideLoader() { if (isLoading) { isLoading = false; elements.pageLoader.classList.remove('show'); } }
        
        function setBrandLoading(loading = true) {
            elements.homeLink.classList.toggle('loading', loading);
            if (elements.brandLogo) elements.brandLogo.style.display = loading ? 'none' : 'block';
            if (elements.brandText) elements.brandText.style.display = loading ? 'none' : 'block';
            let spinner = elements.homeLink.querySelector('.brand-spinner');
            if (loading && !spinner) {
                spinner = document.createElement('div');
                spinner.className = 'brand-spinner';
                elements.homeLink.prepend(spinner);
            } else if (spinner) {
                spinner.style.display = loading ? 'block' : 'none';
            }
        }

        function handleCriticalError(message, details = '') {
            console.error("Critical Error:", message);
            if (details) {
                console.error("Error Details:", details);
            }
            
            elements.errorMessage.style.display = 'block';
            hideLoader();
            setBrandLoading(false);
            elements.mainIframe.style.display = 'none';

            setTimeout(() => {
                window.location.href = redirectOnErrorUrl;
            }, 10000); // 10 ثواني
        }

        // معالجة التحميل الأولي
        document.addEventListener('DOMContentLoaded', function() {
            const initialLoadError = <?php echo json_encode($load_error); ?>;
            const initialErrorDetails = <?php echo json_encode($initial_error_details); ?>;
            
            if (initialLoadError) {
                handleCriticalError("خطأ في التحميل الأولي: لم يتم العثور على رابط الصفحة الرئيسية.", initialErrorDetails);
                return; 
            }

            // إظهار شاشة التحميل فقط عند تحميل الصفحة لأول مرة
            showLoader(); 

            // إخفاء شاشة التحميل عند انتهاء تحميل محتوى الـ Iframe
            elements.mainIframe.addEventListener('load', hideLoader);
            elements.mainIframe.addEventListener('error', () => {
                handleCriticalError("خطأ في الـ Iframe: لم يتمكن من تحميل الرابط الأولي.", `Iframe SRC: ${elements.mainIframe.src}`);
            });

            // التعامل مع النقر على الشعار لجلب الرابط ديناميكياً
            elements.homeLink.addEventListener('click', async (e) => {
                e.preventDefault();
                if (elements.homeLink.classList.contains('loading')) return;

                setBrandLoading(true); // استخدام مؤشر التحميل الخاص بالشعار فقط
                
                try {
                    const response = await fetch(`${ajaxEndpoint}&_t=${Date.now()}`);
                    if (!response.ok) {
                        throw new Error(`Fetch Error: HTTP status ${response.status}`);
                    }
                    const data = await response.json();
                    
                    if (data.success && data.url) {
                        // --- التعديل الأول: تم حذف `showLoader()` من هنا ---
                        // تم حذف استدعاء شاشة التحميل الكاملة للحفاظ على ثبات النافبار
                        elements.mainIframe.src = data.url;
                    } else {
                        throw new Error(data.message || "فشل جلب الرابط من الخادم.", { cause: data.details });
                    }
                } catch (error) {
                    handleCriticalError(error.message, error.cause || 'No additional details from server.');
                } finally {
                    setBrandLoading(false); 
                }
            });
            
            elements.profileBtn.addEventListener('click', function(e) {
                e.preventDefault();
                // --- التعديل الثاني: تم حذف `showLoader()` من هنا ---
                // تم حذف استدعاء شاشة التحميل الكاملة للحفاظ على ثبات النافبار
                elements.mainIframe.src = "https://alfagolden.com/system/profile.php";
            });
            
            elements.logoutBtn.addEventListener('click', function(e) {
                e.preventDefault();
                window.location.href = "https://alfagolden.com/system/logout.php";
            });
        });
    </script>
</body>
</html>