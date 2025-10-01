<?php
// مكون الهيدر المحسّن - المحرك الرئيسي لنظام الترجمة
// إعدادات قاعدة البيانات للمكون
if (!defined('NOCODB_TOKEN')) {
    define('NOCODB_TOKEN', 'fwVaKHr6zbDns5iW9u8annJUf5LCBJXjqPfujIpV');
    define('NOCODB_API_URL', 'https://app.nocodb.com/api/v2/tables/');
}

// دالة جلب البيانات للمكون
function fetchNocoDB_Header($tableId, $viewId = '') {
    $url = NOCODB_API_URL . $tableId . '/records';
    if (!empty($viewId)) {
        $url .= '?viewId=' . $viewId;
    }
    
    $options = [
        'http' => [
            'header' => "xc-token: " . NOCODB_TOKEN . "\r\n" .
                       "Content-Type: application/json\r\n",
            'method' => 'GET',
            'timeout' => 10
        ]
    ];
    
    $context = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);
    
    if ($result === FALSE) {
        return [];
    }
    
    $data = json_decode($result, true);
    return isset($data['list']) ? $data['list'] : [];
}

// جلب بيانات الفئات للقائمة
$categoriesData_Header = fetchNocoDB_Header('m1g39mqv5mtdwad');

// تنظيف البيانات
function sanitizeData_Header($data) {
    if (is_array($data)) {
        return array_map('sanitizeData_Header', $data);
    }
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}

$categoriesData_Header = sanitizeData_Header($categoriesData_Header);
?>

<style>
/* أنماط مكون الهيدر - نفس التصميم الأصلي مع تحسينات */
:root {
    --gold: #977e2b;
    --gold-hover: #b89635;
    --gold-light: #d4b85a;
    --dark-gray: #2c2c2c;
    --medium-gray: #666;
    --white: #ffffff;
    --spacing-xs: 0.25rem;
    --spacing-sm: 0.5rem;
    --spacing-md: 1rem;
    --spacing-lg: 1.5rem;
    --spacing-xl: 3rem;
    --font-size-sm: 0.875rem;
    --border-radius-md: 0.5rem;
    --border-radius-lg: 0.75rem;
    --border-radius-xl: 1rem;
    --shadow-md: 0 4px 8px rgba(0, 0, 0, 0.12), 0 2px 4px rgba(0, 0, 0, 0.08);
    --shadow-lg: 0 15px 25px rgba(0, 0, 0, 0.15), 0 5px 10px rgba(0, 0, 0, 0.05);
    --transition-fast: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    --transition-normal: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    --z-header: 1000;
}

/* إعدادات أساسية للمكون */
.header-component * {
    box-sizing: border-box;
}

.header-component {
    font-family: 'Cairo', 'Tajawal', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* الهيدر يبقى دائماً LTR بغض النظر عن اللغة */
.header-component header {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: var(--z-header);
    box-shadow: var(--shadow-md);
    transition: var(--transition-normal);
    direction: ltr !important; /* دائماً LTR للحفاظ على ثبات المواضع */
}

/* إضافة مسافة للمحتوى تحت الهيدر */
body {
    padding-top: 80px;
}

.header-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 var(--spacing-lg);
    display: flex;
    justify-content: space-between;
    align-items: center;
    height: 120px;
    position: relative;
}

/* الشعار دائماً على اليسار في جميع اللغات */
.header-logo {
    flex: 0 0 auto;
    z-index: 10;
    margin: 0;
    position: relative;
}

.header-logo img {
    height: 100px;
    object-fit: contain;
    transition: var(--transition-normal);
}

.header-logo img:hover {
    transform: scale(1.05);
}

/* Navigation في الوسط */
.header-nav {
    display: flex;
    align-items: center;
    flex: 1;
    justify-content: center;
}

.main-menu {
    display: flex;
    list-style: none;
    gap: var(--spacing-lg);
    align-items: center;
    margin: 0;
    padding: 0;
}

.main-menu > li {
    position: relative;
}

.main-menu a {
    color: var(--dark-gray);
    font-weight: 600;
    font-size: var(--font-size-sm);
    transition: var(--transition-normal);
    padding: var(--spacing-sm) 0;
    display: flex;
    align-items: center;
    gap: var(--spacing-xs);
    position: relative;
    text-decoration: none;
    cursor: pointer;
}

.main-menu a:hover {
    color: var(--gold);
}

.main-menu > li > a::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 0;
    height: 2px;
    background: linear-gradient(90deg, var(--gold), var(--gold-hover));
    transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 1px;
}

.main-menu > li:hover > a::after {
    width: 100%;
}

/* القائمة المنسدلة */
.dropdown {
    position: absolute;
    top: calc(100% + 15px);
    right: 0;
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(15px);
    -webkit-backdrop-filter: blur(15px);
    box-shadow: var(--shadow-lg);
    border-radius: var(--border-radius-xl);
    min-width: 300px;
    max-height: 0;
    overflow: hidden;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-30px) scale(0.8);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    padding: 0;
    border: 1px solid rgba(151, 126, 43, 0.1);
    list-style: none;
}

.main-menu li:hover .dropdown {
    opacity: 1;
    visibility: visible;
    transform: translateY(0) scale(1);
    max-height: 400px;
    padding: var(--spacing-md) 0;
    overflow-y: auto;
}

.dropdown a {
    padding: var(--spacing-md) var(--spacing-lg);
    font-weight: 500;
    font-size: var(--font-size-sm);
    transition: var(--transition-fast);
    border-radius: var(--border-radius-md);
    margin: 0 var(--spacing-sm);
    display: block;
}

.dropdown a:hover {
    background: linear-gradient(135deg, rgba(151, 126, 43, 0.1), rgba(151, 126, 43, 0.05));
    padding-right: calc(var(--spacing-lg) + var(--spacing-xs));
    color: var(--gold);
}

/* تخصيص شريط التمرير */
.dropdown::-webkit-scrollbar {
    width: 6px;
}

.dropdown::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.05);
    border-radius: 3px;
}

.dropdown::-webkit-scrollbar-thumb {
    background: var(--gold);
    border-radius: 3px;
}

.dropdown::-webkit-scrollbar-thumb:hover {
    background: var(--gold-hover);
}

/* ==== زر تبديل اللغة المباشر بتصميم احترافي ==== */
.language-switcher {
    position: relative;
    margin-left: var(--spacing-md);
}

.language-btn {
    background: rgba(255, 255, 255, 0.12);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(151, 126, 43, 0.2);
    border-radius: 20px;
    padding: 8px 16px;
    cursor: pointer;
    transition: var(--transition-normal);
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 500;
    color: var(--dark-gray);
    min-width: 85px;
    height: 36px;
    justify-content: center;
    position: relative;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(151, 126, 43, 0.08);
}

.language-btn:hover {
    background: rgba(151, 126, 43, 0.1);
    border-color: rgba(151, 126, 43, 0.35);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(151, 126, 43, 0.18);
}

.language-btn:active {
    transform: translateY(0);
    box-shadow: 0 2px 6px rgba(151, 126, 43, 0.12);
}

/* أيقونة العالم الاحترافية للغات */
.language-icon {
    width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--gold);
    font-size: 16px;
    transition: var(--transition-fast);
}

/* نص اللغة */
.language-text {
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.2px;
    white-space: nowrap;
}

/* زر القائمة المحمولة - مخفي افتراضياً */
.mobile-menu-toggle {
    display: none !important;
    background: none;
    border: 1px solid rgba(151, 126, 43, 0.2);
    font-size: 20px;
    color: var(--dark-gray);
    cursor: pointer;
    padding: var(--spacing-sm);
    transition: var(--transition-normal);
    border-radius: var(--border-radius-md);
    z-index: 1001;
    position: relative;
    min-width: 44px;
    min-height: 44px;
    align-items: center;
    justify-content: center;
}

.mobile-menu-toggle:hover {
    color: var(--gold);
    background: rgba(151, 126, 43, 0.1);
}

.mobile-menu-toggle:focus {
    outline: 2px solid var(--gold);
    outline-offset: 2px;
}

/* القائمة المحمولة أيضاً تبقى LTR */
.mobile-menu {
    position: fixed;
    top: 80px;
    left: 0;
    right: 0;
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    box-shadow: var(--shadow-lg);
    transform: translateY(-100%);
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 999;
    max-height: calc(100vh - 80px);
    overflow-y: auto;
    list-style: none;
    margin: 0;
    padding: var(--spacing-lg) 0;
    direction: ltr !important; /* القائمة المحمولة أيضاً LTR */
}

.mobile-menu.active {
    transform: translateY(0);
    opacity: 1;
    visibility: visible;
}

.mobile-menu li {
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.mobile-menu li:last-child {
    border-bottom: none;
}

.mobile-menu > li > a {
    display: block;
    padding: var(--spacing-lg);
    color: var(--dark-gray);
    text-decoration: none;
    font-weight: 500;
    transition: var(--transition-fast);
    position: relative;
    cursor: pointer;
}

.mobile-menu > li > a:hover {
    background: linear-gradient(135deg, rgba(151, 126, 43, 0.1), rgba(151, 126, 43, 0.05));
    color: var(--gold);
    padding-right: calc(var(--spacing-lg) + var(--spacing-xs));
}

/* المنتجات في القائمة المحمولة */
.mobile-products-item {
    position: relative;
}

.mobile-products-toggle {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    padding: var(--spacing-lg) !important;
    cursor: pointer;
}

.mobile-products-toggle::after {
    content: '\f078';
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
    transition: transform 0.3s ease;
    margin-left: var(--spacing-sm);
}

.mobile-products-item.active .mobile-products-toggle::after {
    transform: rotate(180deg);
}

/* الفئات في القائمة المحمولة */
.mobile-categories-list {
    list-style: none;
    margin: 0;
    padding: 0;
    background: rgba(151, 126, 43, 0.05);
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
}

.mobile-products-item.active .mobile-categories-list {
    max-height: 500px;
}

.mobile-categories-list a {
    padding: var(--spacing-md) calc(var(--spacing-lg) * 2) !important;
    font-size: 0.9em !important;
    color: var(--medium-gray) !important;
    display: block;
    text-decoration: none;
    transition: var(--transition-fast);
}

.mobile-categories-list a:hover {
    color: var(--gold) !important;
    background: rgba(151, 126, 43, 0.1) !important;
    padding-right: calc(var(--spacing-lg) * 2 + var(--spacing-xs)) !important;
}

/* اللغة في القائمة المحمولة - زر تبديل مباشر */
.mobile-language-switcher {
    padding: var(--spacing-lg);
    border-top: 1px solid rgba(0, 0, 0, 0.05);
    margin-top: var(--spacing-md);
    display: flex;
    justify-content: center;
}

.mobile-language-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--spacing-sm);
    padding: var(--spacing-md) var(--spacing-lg);
    border: 1px solid rgba(151, 126, 43, 0.2);
    border-radius: var(--border-radius-md);
    background: rgba(255, 255, 255, 0.8);
    cursor: pointer;
    transition: var(--transition-normal);
    font-size: var(--font-size-sm);
    font-weight: 500;
    color: var(--dark-gray);
    min-width: 120px;
}

.mobile-language-btn:hover {
    border-color: var(--gold);
    background: rgba(151, 126, 43, 0.1);
    color: var(--gold);
}

/* الاستجابة للشاشات المختلفة */
@media (max-width: 1200px) {
    .main-menu {
        gap: var(--spacing-md);
    }
}

@media (max-width: 992px) {
    .main-menu {
        gap: var(--spacing-sm);
    }
    
    .header-container {
        padding: 0 var(--spacing-md);
    }
    
    .dropdown {
        min-width: 250px;
    }
}

/* إعدادات الشاشات الصغيرة */
@media (max-width: 768px) {
    body {
        padding-top: 70px;
    }
    
    /* إخفاء القائمة الرئيسية وإظهار البرقر */
    .main-menu {
        display: none;
    }
    
    .mobile-menu-toggle {
        display: flex !important;
    }
    
    .header-container {
        height: 90px;
        justify-content: space-between;
    }
    
    .mobile-menu {
        top: 70px;
        max-height: calc(100vh - 70px);
    }
    
    /* الشعار يبقى على اليسار دائماً في جميع الأحوال */
    .header-logo {
        order: 1; /* اليسار دائماً */
        margin: 0;
        flex: 0 0 auto;
    }
    
    .header-logo img {
        height: 75px;
    }
    
    /* إخفاء قائمة التنقل العادية */
    .header-nav {
        display: none;
    }
    
    /* زر اللغة في الجوال */
    .language-switcher {
        order: 2;
        margin-left: var(--spacing-sm);
        margin-right: var(--spacing-sm);
    }
    
    .language-btn {
        min-width: 70px;
        height: 32px;
        padding: 6px 12px;
        border-radius: 16px;
    }
    
    .language-icon {
        width: 16px;
        height: 16px;
    }
    
    .language-text {
        font-size: 11px;
    }
}

@media (max-width: 480px) {
    body {
        padding-top: 90px;
    }
    
    .header-container {
        height: 90px;
        padding: 0 var(--spacing-sm);
        justify-content: space-between;
    }
    
    .mobile-menu {
        top: 60px;
        max-height: calc(100vh - 60px);
    }
    
    .header-logo img {
        height: 65px;
    }
    
    .language-btn {
        min-width: 65px;
        height: 30px;
        padding: 4px 10px;
    }
    
    .language-icon {
        width: 14px;
        height: 14px;
    }
    
    .language-text {
        font-size: 10px;
    }
}

/* تأكيد إضافي للشاشات الكبيرة */
@media (min-width: 769px) {
    .mobile-menu-toggle {
        display: none !important;
    }
    
    .header-nav {
        display: flex !important;
    }
    
    .mobile-menu {
        display: none !important;
    }
    
    .mobile-language-switcher {
        display: none;
    }
}

/* للعرض المستقل */
.header-standalone {
    padding-top: 100px;
    min-height: 100vh;
    background: #f8f9fa;
}
</style>

<!-- تحميل Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<!-- HTML مكون الهيدر المحسّن -->
<div class="header-component">
    <header id="header">
        <div class="header-container">
            <div class="header-logo">
                <a href="https://alfagolden.com/" aria-label="الصفحة الرئيسية">
                    <img src="/logo.png" alt="شركة ألفا الذهبية" loading="lazy">
                </a>
            </div>
            
            <nav class="header-nav" role="navigation" aria-label="القائمة الرئيسية">
                <ul class="main-menu">
                    <li><a href="https://alfagolden.com/" data-translate="home">الصفحة الرئيسية</a></li>
                    <li>
                        <a href="#" aria-haspopup="true" aria-expanded="false" data-translate="products">
                            المنتجات <i class="fas fa-chevron-down" aria-hidden="true"></i>
                        </a>
                        <?php if (!empty($categoriesData_Header)): ?>
                            <ul class="dropdown" role="menu" id="categoriesDropdown">
                                <?php foreach ($categoriesData_Header as $category): ?>
                                    <li role="none">
                                        <a href="<?php echo !empty($category['الرابط']) ? $category['الرابط'] : '#'; ?>" 
                                           role="menuitem"
                                           data-translate="category-<?php echo $category['معرف_القسم'] ?? ''; ?>"
                                           data-name-ar="<?php echo htmlspecialchars($category['الاسم'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                           data-name-en="<?php echo htmlspecialchars($category['name'] ?? $category['الاسم'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo $category['الاسم'] ?? ''; ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </li>
                    <li><a href="#" class="gallery-trigger-link" data-gallery-id="1" data-translate="our-shares">مشاركاتنا</a></li>
                    <li><a href="#" class="gallery-trigger-link" data-gallery-id="2" data-translate="our-agencies">وكالاتنا</a></li>
                    <li><a href="#" class="gallery-trigger-link" data-gallery-id="3" data-translate="latest-news">آخر الأخبار</a></li>
                    <li><a href="#" class="gallery-trigger-link" data-gallery-id="4" data-translate="quality-certificates">شهادات الجودة</a></li>
                    <li><a href="https://alfagolden.com/op.php" data-translate="our-projects">مشاريعنا</a></li>
                    <li><a href="#" class="gallery-trigger-link" data-gallery-id="6" data-translate="our-profile">الملف التعريفي</a></li>
                    <li><a href="https://api.whatsapp.com/send/?phone=966506086333&text&type=phone_number&app_absent=0" data-translate="contact-us">اتصل بنا</a></li>
                </ul>
            </nav>
            
            <!-- زر تبديل اللغة المباشر -->
            <div class="language-switcher" id="languageSwitcher">
                <button class="language-btn" id="languageBtn" aria-label="تغيير اللغة" type="button">
                    <div class="language-icon" id="languageIcon">
                        <i class="fas fa-globe"></i>
                    </div>
                    <span class="language-text" id="languageText">English</span>
                </button>
            </div>
            
            <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="فتح القائمة" type="button">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </header>
    
    <!-- القائمة المحمولة -->
    <ul class="mobile-menu" id="mobileMenu">
        <li><a href="https://alfagolden.com/" data-translate="home">الصفحة الرئيسية</a></li>
        <li class="mobile-products-item" id="mobileProductsItem">
            <a href="#" class="mobile-products-toggle" id="mobileProductsToggle" data-translate="products">
                المنتجات
            </a>
            <?php if (!empty($categoriesData_Header)): ?>
                <ul class="mobile-categories-list" id="mobileCategoriesList">
                    <?php foreach ($categoriesData_Header as $category): ?>
                        <li>
                            <a href="<?php echo !empty($category['الرابط']) ? $category['الرابط'] : '#'; ?>"
                               data-translate="category-<?php echo $category['معرف_القسم'] ?? ''; ?>"
                               data-name-ar="<?php echo htmlspecialchars($category['الاسم'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                               data-name-en="<?php echo htmlspecialchars($category['name'] ?? $category['الاسم'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo $category['الاسم'] ?? ''; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </li>
        <li><a href="#" class="gallery-trigger-link mobile-gallery-link" data-gallery-id="1" data-translate="our-shares">مشاركاتنا</a></li>
        <li><a href="#" class="gallery-trigger-link mobile-gallery-link" data-gallery-id="2" data-translate="our-agencies">وكالاتنا</a></li>
        <li><a href="#" class="gallery-trigger-link mobile-gallery-link" data-gallery-id="3" data-translate="latest-news">آخر الأخبار</a></li>
        <li><a href="#" class="gallery-trigger-link mobile-gallery-link" data-gallery-id="4" data-translate="quality-certificates">شهادات الجودة</a></li>
        <li><a href="https://alfagolden.com/op.php" data-translate="our-projects">مشاريعنا</a></li>
        <li><a href="#" class="gallery-trigger-link mobile-gallery-link" data-gallery-id="6" data-translate="our-profile">الملف التعريفي</a></li>
        <li><a href="https://v1.alfagolden.com/contactus" data-translate="contact-us">اتصل بنا</a></li>
        
        <!-- تبديل اللغة في القائمة المحمولة - زر مباشر -->
        <li class="mobile-language-switcher">
            <button class="mobile-language-btn" id="mobileLanguageBtn">
                <div class="language-icon" id="mobileLanguageIcon">
                    <i class="fas fa-globe"></i>
                </div>
                <span id="mobileLanguageText">English</span>
            </button>
        </li>
    </ul>
</div>

<script>
// نظام الترجمة المركزي - الهيدر كمحرك رئيسي
window.TranslationManager = (function() {
    'use strict';
    
    // === إعدادات الترجمة ===
    const translations = {
        ar: {
            'home': 'الصفحة الرئيسية',
            'products': 'المنتجات',
            'our-shares': 'مشاركاتنا',
            'our-agencies': 'وكالاتنا',
            'latest-news': 'آخر الأخبار',
            'quality-certificates': 'شهادات الجودة',
            'our-projects': 'مشاريعنا',
            'our-profile': 'الملف التعريفي',
            'contact-us': 'اتصل بنا',
            'select-language': 'اختيار اللغة'
        },
        en: {
            'home': 'Home',
            'products': 'Products',
            'our-shares': 'Our Shares',
            'our-agencies': 'Our Agencies',
            'latest-news': 'Latest News',
            'quality-certificates': 'Quality Certificates',
            'our-projects': 'Our Projects',
            'our-profile': 'Our Profile',
            'contact-us': 'Contact Us',
            'select-language': 'Select Language'
        }
    };
    
    let currentLanguage = 'ar'; // اللغة الحالية
    let isInitialized = false; // تأكد من عدم التهيئة المتكررة
    
    // === تحميل اللغة المحفوظة ===
    function loadSavedLanguage() {
        try {
            const savedLang = localStorage.getItem('siteLanguage');
            if (savedLang && (savedLang === 'ar' || savedLang === 'en')) {
                currentLanguage = savedLang;
                return savedLang;
            }
        } catch (error) {
            console.warn('خطأ في تحميل اللغة المحفوظة:', error);
        }
        return 'ar';
    }
    
    // === حفظ اللغة ===
    function saveLanguage(lang) {
        try {
            localStorage.setItem('siteLanguage', lang);
        } catch (error) {
            console.warn('خطأ في حفظ اللغة:', error);
        }
    }
    
    // === إرسال إشعار عام لتغيير اللغة ===
    function broadcastLanguageChange(language) {
        // إشعار مخصص للكومبوننتس
        const event = new CustomEvent('siteLanguageChanged', {
            detail: { 
                language: language, 
                isRTL: language === 'ar',
                translations: translations[language] || translations.ar
            }
        });
        document.dispatchEvent(event);
        
        // إشعار إضافي للتوافق مع الكود القديم
        const oldEvent = new CustomEvent('languageChanged', {
            detail: { 
                language: language, 
                isRTL: language === 'ar'
            }
        });
        document.dispatchEvent(oldEvent);
        
        console.log('تم بث تغيير اللغة:', language);
    }
    
    // === تحديث أسماء الفئات حسب اللغة ===
    function updateCategoryNames() {
        // تحديث القائمة الرئيسية
        const dropdownLinks = document.querySelectorAll('#categoriesDropdown a[data-name-ar][data-name-en]');
        dropdownLinks.forEach(link => {
            const nameAr = link.getAttribute('data-name-ar');
            const nameEn = link.getAttribute('data-name-en');
            
            if (currentLanguage === 'ar') {
                link.textContent = nameAr;
            } else {
                link.textContent = nameEn;
            }
        });
        
        // تحديث القائمة المحمولة
        const mobileLinks = document.querySelectorAll('#mobileCategoriesList a[data-name-ar][data-name-en]');
        mobileLinks.forEach(link => {
            const nameAr = link.getAttribute('data-name-ar');
            const nameEn = link.getAttribute('data-name-en');
            
            if (currentLanguage === 'ar') {
                link.textContent = nameAr;
            } else {
                link.textContent = nameEn;
            }
        });
    }
    
    // === تحديث نصوص الهيدر ===
    function updateHeaderTexts() {
        const elementsToTranslate = document.querySelectorAll('.header-component [data-translate]');
        elementsToTranslate.forEach(element => {
            const key = element.getAttribute('data-translate');
            if (translations[currentLanguage] && translations[currentLanguage][key]) {
                element.textContent = translations[currentLanguage][key];
            }
        });
    }
    
    // === تحديث عرض اللغة ===
    function updateLanguageDisplay() {
        const languageText = document.getElementById('languageText');
        const mobileLanguageText = document.getElementById('mobileLanguageText');
        
        if (languageText) {
            if (currentLanguage === 'ar') {
                languageText.textContent = 'English';
            } else {
                languageText.textContent = 'العربية';
            }
        }
        
        if (mobileLanguageText) {
            if (currentLanguage === 'ar') {
                mobileLanguageText.textContent = 'English';
            } else {
                mobileLanguageText.textContent = 'العربية';
            }
        }
    }
    
    // === تغيير اللغة ===
    function changeLanguage(newLang) {
        if (newLang === currentLanguage || !isInitialized) return;
        
        console.log('تغيير اللغة من', currentLanguage, 'إلى', newLang);
        
        currentLanguage = newLang;
        saveLanguage(newLang);
        
        // تحديث الهيدر أولاً
        updateHeaderTexts();
        updateLanguageDisplay();
        updateCategoryNames();
        
        // ثم إرسال الإشعار للكومبوننتس الأخرى
        setTimeout(() => {
            broadcastLanguageChange(newLang);
        }, 50);
    }
    
    // === تبديل اللغة ===
    function toggleLanguage() {
        const newLang = currentLanguage === 'ar' ? 'en' : 'ar';
        changeLanguage(newLang);
    }
    
    // === واجهة عامة ===
    return {
        init: function() {
            if (isInitialized) return;
            
            // تحميل اللغة المحفوظة
            currentLanguage = loadSavedLanguage();
            
            // تحديث الهيدر
            updateHeaderTexts();
            updateLanguageDisplay();
            updateCategoryNames();
            
            // إعداد أزرار اللغة
            const languageBtn = document.getElementById('languageBtn');
            const mobileLanguageBtn = document.getElementById('mobileLanguageBtn');
            
            if (languageBtn) {
                languageBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    toggleLanguage();
                });
            }
            
            if (mobileLanguageBtn) {
                mobileLanguageBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    toggleLanguage();
                    // إغلاق القائمة المحمولة
                    if (window.HeaderManager && window.HeaderManager.closeMobileMenu) {
                        window.HeaderManager.closeMobileMenu();
                    }
                });
            }
            
            isInitialized = true;
            
            // إرسال الحالة الأولية
            setTimeout(() => {
                broadcastLanguageChange(currentLanguage);
            }, 100);
            
            console.log('تم تهيئة نظام الترجمة - اللغة الحالية:', currentLanguage);
        },
        
        getCurrentLanguage: () => currentLanguage,
        changeLanguage: changeLanguage,
        toggleLanguage: toggleLanguage,
        isRTL: () => currentLanguage === 'ar',
        getTranslations: () => translations[currentLanguage] || translations.ar
    };
})();

// JavaScript مكون الهيدر
document.addEventListener('DOMContentLoaded', function() {
    'use strict';
    
    // === متغيرات التحكم ===
    let isMenuOpen = false;
    
    // === العناصر ===
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const mobileMenu = document.getElementById('mobileMenu');
    const mobileProductsToggle = document.getElementById('mobileProductsToggle');
    const mobileProductsItem = document.getElementById('mobileProductsItem');
    
    // === التحقق من وجود العناصر ===
    if (!mobileMenuToggle || !mobileMenu) {
        console.warn('عناصر القائمة المحمولة غير موجودة');
        return;
    }
    
    // === فتح/إغلاق القائمة المحمولة ===
    function toggleMobileMenu() {
        if (isMenuOpen) {
            closeMobileMenu();
        } else {
            openMobileMenu();
        }
    }
    
    function openMobileMenu() {
        mobileMenu.classList.add('active');
        mobileMenuToggle.innerHTML = '<i class="fas fa-times"></i>';
        mobileMenuToggle.setAttribute('aria-label', 'إغلاق القائمة');
        document.body.style.overflow = 'hidden';
        isMenuOpen = true;
    }
    
    function closeMobileMenu() {
        mobileMenu.classList.remove('active');
        mobileMenuToggle.innerHTML = '<i class="fas fa-bars"></i>';
        mobileMenuToggle.setAttribute('aria-label', 'فتح القائمة');
        document.body.style.overflow = '';
        isMenuOpen = false;
        
        // إغلاق قائمة المنتجات عند إغلاق القائمة الرئيسية
        if (mobileProductsItem) {
            mobileProductsItem.classList.remove('active');
        }
    }
    
    // === معالجة النقر على أزرار المعرض بشكل محسّن ===
    function handleGalleryClick(e, isMobile = false) {
        e.preventDefault();
        e.stopPropagation();
        
        const galleryId = e.currentTarget.getAttribute('data-gallery-id');
        if (!galleryId) return;
        
        if (isMobile) {
            // إغلاق القائمة المحمولة أولاً
            closeMobileMenu();
            
            // تأخير قصير لضمان إغلاق القائمة قبل فتح المعرض
            setTimeout(() => {
                openGallery(galleryId);
            }, 300);
        } else {
            openGallery(galleryId);
        }
    }
    
    function openGallery(galleryId) {
        if (typeof window.openImageGallery === 'function') {
            console.log('فتح معرض رقم:', galleryId);
            window.openImageGallery(galleryId);
        } else {
            console.error('دالة فتح المعرض غير متوفرة - تأكد من تحميل مكون المعرض');
            alert('مكون المعرض غير متوفر حالياً');
        }
    }
    
    // === إعداد مستمعي الأحداث ===
    
    // القائمة المحمولة
    mobileMenuToggle.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        toggleMobileMenu();
    });
    
    // المنتجات في القائمة المحمولة
    if (mobileProductsToggle && mobileProductsItem) {
        mobileProductsToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            mobileProductsItem.classList.toggle('active');
        });
    }
    
    // أزرار المعرض في القائمة العادية
    document.querySelectorAll('.gallery-trigger-link:not(.mobile-gallery-link)').forEach(link => {
        link.addEventListener('click', (e) => handleGalleryClick(e, false));
    });
    
    // أزرار المعرض في القائمة المحمولة - معالجة خاصة
    document.querySelectorAll('.mobile-gallery-link').forEach(link => {
        // إزالة أي مستمعين سابقين
        link.removeEventListener('click', handleGalleryClick);
        
        // إضافة معالج محسّن للجوال
        link.addEventListener('click', (e) => handleGalleryClick(e, true));
        
        // معالجة خاصة للمس
        link.addEventListener('touchstart', function(e) {
            e.stopPropagation();
        }, { passive: true });
        
        link.addEventListener('touchend', function(e) {
            e.stopPropagation();
        }, { passive: true });
    });
    
    // إغلاق القائمة المحمولة عند النقر خارجها
    document.addEventListener('click', function(e) {
        // إغلاق القائمة المحمولة
        if (isMenuOpen && 
            !mobileMenu.contains(e.target) && 
            !mobileMenuToggle.contains(e.target)) {
            closeMobileMenu();
        }
    });
    
    // إغلاق القوائم بمفتاح Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (isMenuOpen) {
                closeMobileMenu();
            }
        }
    });
    
    // إغلاق القائمة عند تغيير حجم الشاشة
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768 && isMenuOpen) {
            closeMobileMenu();
        }
    });
    
    // إغلاق القائمة عند النقر على الروابط العادية (ليس المنتجات أو المعرض)
    const mobileMenuLinks = mobileMenu.querySelectorAll('a:not(.mobile-products-toggle):not(.mobile-categories-list a):not(.gallery-trigger-link)');
    mobileMenuLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            if (this.getAttribute('href') !== '#') {
                closeMobileMenu();
            }
        });
    });
    
    // إغلاق القائمة عند النقر على روابط الفئات
    const categoryLinks = mobileMenu.querySelectorAll('.mobile-categories-list a');
    categoryLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            if (this.getAttribute('href') !== '#') {
                closeMobileMenu();
            }
        });
    });
    
    // التعامل مع القوائم المنسدلة في سطح المكتب
    const dropdownLinks = document.querySelectorAll('.main-menu > li > a[aria-haspopup="true"]');
    dropdownLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
        });
    });
    
    // منع انتشار الأحداث في القائمة المحمولة
    mobileMenu.addEventListener('click', function(e) {
        e.stopPropagation();
    });
    
    // === تهيئة نظام الترجمة ===
    TranslationManager.init();
    
    // === إتاحة الوصول للوظائف من النطاق العام ===
    window.HeaderManager = {
        toggleMobileMenu: toggleMobileMenu,
        closeMobileMenu: closeMobileMenu,
        getCurrentLanguage: () => TranslationManager.getCurrentLanguage(),
        isRTL: () => TranslationManager.isRTL()
    };
    
    // === رسالة تأكيد التحميل ===
    console.log('تم تحميل مكون الهيدر - المحرك الرئيسي للترجمة');
});
</script>

<?php
// في حالة الوصول المباشر للملف
if (basename($_SERVER['PHP_SELF']) == 'header.php') {
    echo '<!DOCTYPE html>
    <html lang="ar" dir="rtl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>مكون الهيدر المحسّن - ألفا الذهبية</title>
        <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200;300;400;500;600;700;800;900&family=Tajawal:wght@200;300;400;500;700;800;900&display=swap" rel="stylesheet">
    </head>
    <body class="header-standalone">
        <div style="padding: 2rem; text-align: center; margin-top: 100px;">
            <h1 style="color: #977e2b; margin-bottom: 1rem;">مكون الهيدر المحسّن</h1>
            <p style="color: #666;">المحرك الرئيسي لنظام الترجمة</p>
            <p><strong>التحسينات الجديدة:</strong></p>
            <ul style="text-align: right; max-width: 600px; margin: 0 auto;">
                <li>نظام ترجمة مركزي مع إرسال إشعارات للكومبوننتس الأخرى</li>
                <li>دعم عرض أسماء الفئات بالإنجليزية من حقل "name"</li>
                <li>تبديل تلقائي لأسماء الفئات عند تغيير اللغة</li>
                <li>إشعارات عامة للكومبوننتس المستقبلية</li>
                <li>نظام حفظ واستراعاد حالة اللغة محسّن</li>
            </ul>
        </div>
    ';
    
    echo '
    </body>
    </html>';
}
?>