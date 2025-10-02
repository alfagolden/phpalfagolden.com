<?php
// مكون قسم الكتالوجات - مع إصلاح مشاكل اللغة والترجمة الصحيحة
// إعدادات قاعدة البيانات للمكون (تم التبديل إلى Base API لـ base.alfa - جدول 698)
// التكوين
$API_CONFIG = [
    'baseUrl' => 'https://base.alfagolden.com',
    'token' => 'h5qAt85gtiJDAzpH51WrXPywhmnhrPWy',
    'catalogsTableId' => 698  // جدول الكتالوجات
];
// خرائط الحقول لجدول الكتالوجات (بناءً على السيكما المقدمة)
$FIELDS = [
    'catalogs' => [
        'name_ar' => 'field_6754',  // الاسم
        'image' => 'field_6755',    // الصورة
        'location' => 'field_6756', // الموقع
        'link' => 'field_6757',     // الرابط
        'file_id' => 'field_6758',  // معرف الملف
        'order' => 'field_6759',    // ترتيب
        'sub_order' => 'field_6760', // ترتيب فرعي
        'sub_name_ar' => 'field_6761', // الاسم الفرعي
        'name_en' => 'field_6762',  // name (الاسم الإنجليزي)
        'status' => 'field_7072',   // الحالة
        'status_en' => 'field_7073', // الحالة-en
        'sub_name_en' => 'field_7075', // الاسم الفرعي-en
        'description_ar' => 'field_7076', // نص
        'description_en' => 'field_7077' // نص-en
    ]
];
// وظائف مساعدة للـ API (مستمدة من الكود المقدم)
function makeApiRequest($endpoint, $method = 'GET', $data = null) {
    global $API_CONFIG;
   
    $url = $API_CONFIG['baseUrl'] . '/api/database/' . $endpoint;
    $options = [
        'http' => [
            'method' => $method,
            'header' => [
                'Authorization: Token ' . $API_CONFIG['token'],
                'Content-Type: application/json'
            ]
        ]
    ];
    // $response['results']
    if ($data) {
        $options['http']['content'] = json_encode($data);
    }
   
   
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
   
    if ($response === false) {
        return null;
    }
   
    $decoded = json_decode($response, true);
    return $decoded ?: null;
}
// جلب بيانات الكتالوجات من Base (جدول 698 فقط، فلترة على الحالة النشطة، ترتيب، حد 8)
function fetchCatalogsFromBase($tableId) {
    global $API_CONFIG;
    try {
        // جلب السجلات مع فلترة الحالة (افتراضياً 'active' أو 'نشط'، قم بتعديل حسب القيم الفعلية)
        $response = makeApiRequest("rows/table/{$tableId}/user_field_names=false/?filter_field_6756__equal=كتلوجات"); // فلت عالة////////
        if (!$response || !isset($response['results'])) {
            return [];
        }
        $results = $response['results'];
   
        
        // حد 8 سجلات فقط
        return $results;
    } catch (Exception $e) {
        error_log("خطأ في جلب الكتالوجات: " . $e->getMessage());
        return [];
    }
}
// جلب بيانات الكتالوجات من base.alfa
$catalogData_Catalogs = fetchCatalogsFromBase($API_CONFIG['catalogsTableId']);
// تنظيف البيانات
function sanitizeData_Catalogs($data) {
    if (is_array($data)) {
        return array_map('sanitizeData_Catalogs', $data);
    }
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}
$catalogData_Catalogs = array_map(function($item) {
    // تنظيف كل حقل يدوياً للحفاظ على الهيكل
    foreach ($item as $key => $value) {
        if (is_string($value)) {
            $item[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        } elseif (is_array($value)) {
            // للحقول المعقدة مثل الصورة أو الروابط، افترض أنها string مباشرة كما في السيكما
            $item[$key] = htmlspecialchars(implode(', ', (array)$value), ENT_QUOTES, 'UTF-8');
        }
    }
    return $item;
}, $catalogData_Catalogs);
// دالة تحديد معرف المعرض بناءً على اسم الكتالوج
function getCatalogGalleryId($catalogName) {
    // البحث بالكلمات المفتاحية لضمان المطابقة الصحيحة
    if (strpos($catalogName, 'الملف التعريفي للمقاولات') !== false || strpos($catalogName, 'Profile') !== false) {
        return 10;
    }
    if (strpos($catalogName, 'ألفا برو') !== false || strpos($catalogName, 'ألــفــا بــرو') !== false) {
        return 11;
    }
    if (strpos($catalogName, 'FUJI') !== false || strpos($catalogName, 'فوجي') !== false) {
        return 12;
    }
    if (strpos($catalogName, 'MITSUTECH') !== false || strpos($catalogName, 'ميتسوتك') !== false || strpos($catalogName, 'MITSU') !== false) {
        return 13;
    }
    if (strpos($catalogName, 'ألفا إيليت') !== false || strpos($catalogName, 'ألـفـا إيــلــيــت') !== false) {
        return 14;
    }
   
    // القيمة الافتراضية
    return 1;
}
?>
<style>
/* أنماط مكون قسم الكتالوجات */
:root {
    --gold: #977e2b;
    --gold-hover: #b89635;
    --dark-gray: #2c2c2c;
    --medium-gray: #666;
    --light-gray: #f8f9fa;
    --white: #ffffff;
    --spacing-md: 1rem;
    --spacing-lg: 1.5rem;
    --spacing-xl: 3rem;
    --spacing-2xl: 4rem;
    --font-size-lg: 1.125rem;
    --font-size-2xl: 1.5rem;
    --font-size-4xl: 2.25rem;
    --border-radius-2xl: 1.5rem;
    --shadow-md: 0 4px 8px rgba(0, 0, 0, 0.12), 0 2px 4px rgba(0, 0, 0, 0.08);
    --shadow-xl: 0 20px 40px rgba(0, 0, 0, 0.1);
    --transition-normal: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    --transition-slow: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}
/* إعدادات أساسية للمكون */
.catalogs-component * {
    box-sizing: border-box;
}
.catalogs-component {
    font-family: 'Cairo', 'Tajawal', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}
/* قسم الكتالوجات */
.catalogs {
    background: var(--light-gray);
    margin-top: 0;
    padding-bottom: var(--spacing-2xl);
}
.container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 var(--spacing-lg);
}
.section-title {
    text-align: center;
    margin-bottom: var(--spacing-2xl);
    padding-top: var(--spacing-2xl);
}
.section-title h2 {
    font-size: var(--font-size-4xl);
    color: var(--dark-gray);
    font-weight: 900;
    position: relative;
    display: inline-block;
    padding-bottom: var(--spacing-lg);
    margin-bottom: var(--spacing-md);
}
.section-title h2:after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 80px;
    height: 4px;
    background: linear-gradient(90deg, var(--gold), var(--gold-hover));
    border-radius: 2px;
}
.section-title p {
    color: var(--medium-gray);
    font-size: var(--font-size-lg);
    max-width: 600px;
    margin: 0 auto;
    line-height: 1.7;
}
.catalogs-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: var(--spacing-xl);
}
.catalog-item {
    background: var(--white);
    border-radius: var(--border-radius-2xl);
    overflow: hidden;
    box-shadow: var(--shadow-md);
    transition: var(--transition-normal);
    cursor: pointer;
    position: relative;
    transform: translateY(0);
    display: flex;
    flex-direction: column;
    text-decoration: none;
    color: inherit;
}
.catalog-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, var(--gold), var(--gold-hover));
    opacity: 0;
    transition: var(--transition-normal);
    z-index: 1;
}
.catalog-item:hover {
    transform: translateY(-10px);
    box-shadow: var(--shadow-xl);
}
.catalog-item:hover::before {
    opacity: 0.9;
}
.catalog-item img {
    width: 100%;
    height: auto;
    object-fit: contain;
    transition: var(--transition-slow);
    flex-shrink: 0;
    display: block;
}
.catalog-item:hover img {
    transform: scale(1.05);
}
.catalog-item h3 {
    padding: var(--spacing-lg) var(--spacing-xl);
    text-align: center;
    color: var(--dark-gray);
    font-weight: 700;
    font-size: var(--font-size-lg);
    position: relative;
    z-index: 2;
    transition: var(--transition-normal);
    flex-grow: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0;
}
.catalog-item:hover h3 {
    color: var(--white);
}
/* تأثير خاص لروابط المعرض */
.gallery-trigger-link {
    position: relative;
    overflow: hidden;
}
.gallery-trigger-link::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(151, 126, 43, 0.2), transparent);
    transition: left 0.5s;
    z-index: 0;
}
.gallery-trigger-link:hover::after {
    left: 100%;
}
/* الاستجابة للشاشات المختلفة */
@media (max-width: 768px) {
    .catalogs-grid {
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: var(--spacing-lg);
    }
   
    .section-title h2 {
        font-size: var(--font-size-2xl);
    }
}
@media (max-width: 480px) {
    .container {
        padding: 0 var(--spacing-md);
    }
   
    .section-title h2 {
        font-size: 1.5rem;
    }
}
/* للعرض المستقل */
.catalogs-standalone {
    background: #f8f9fa;
    min-height: 100vh;
    padding: 2rem 0;
}
.demo-info {
    text-align: center;
    margin-bottom: 2rem;
    color: #666;
    padding: 0 1rem;
}
.demo-info h1 {
    color: var(--gold);
    margin-bottom: 1rem;
    font-size: 2rem;
}
</style>
<!-- HTML مكون قسم الكتالوجات مع إصلاح مشاكل اللغة -->
<div class="catalogs-component">
    <section class="catalogs" data-aos="fade-up">
        <div class="container">
            <div class="section-title fade-in">
                <h2 id="catalogsTitle">الكتالوجات</h2>
                <p id="catalogsDescription">استعرض مجموعتنا المتنوعة من الكتالوجات المتخصصة</p>
            </div>
            <div class="catalogs-grid">
                <?php if (!empty($catalogData_Catalogs)): ?>
                    <?php foreach ($catalogData_Catalogs as $index => $catalog): ?>
                        <a href="#"
                           class="catalog-item gallery-trigger-link scale-in will-change-transform"
                           data-gallery-id="<?php echo getCatalogGalleryId($catalog[$FIELDS['catalogs']['name_ar']] ?? ''); ?>"
                           data-aos="zoom-in"
                           data-aos-delay="<?php echo $index * 100; ?>"
                           aria-label="فتح معرض <?php echo !empty($catalog[$FIELDS['catalogs']['name_en']]) ? $catalog[$FIELDS['catalogs']['name_en']] : ($catalog[$FIELDS['catalogs']['name_ar']] ?? ''); ?>"
                           data-name-ar="<?php echo htmlspecialchars($catalog[$FIELDS['catalogs']['name_ar']] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                           data-name-en="<?php echo htmlspecialchars($catalog[$FIELDS['catalogs']['name_en']] ?? $catalog[$FIELDS['catalogs']['name_ar']] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            <img src="<?php echo $catalog[$FIELDS['catalogs']['image']] ?? ''; ?>"
                                 alt="كتالوج <?php echo !empty($catalog[$FIELDS['catalogs']['name_en']]) ? $catalog[$FIELDS['catalogs']['name_en']] : ($catalog[$FIELDS['catalogs']['name_ar']] ?? ''); ?>"
                                 loading="lazy">
                            <h3 class="catalog-name"
                                data-ar="<?php echo htmlspecialchars($catalog[$FIELDS['catalogs']['name_ar']] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                data-en="<?php echo htmlspecialchars($catalog[$FIELDS['catalogs']['name_en']] ?? $catalog[$FIELDS['catalogs']['name_ar']] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo $catalog[$FIELDS['catalogs']['name_ar']] ?? ''; ?>
                            </h3>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- كتالوجات افتراضية للعرض -->
                    <a href="#" class="catalog-item gallery-trigger-link scale-in" data-gallery-id="10" data-aos="zoom-in">
                        <img src="https://via.placeholder.com/400x300/977e2b/ffffff?text=كتالوج+المصاعد" alt="كتالوج المصاعد" loading="lazy">
                        <h3 class="catalog-name" data-ar="كتالوج المصاعد" data-en="Elevators Catalog">كتالوج المصاعد</h3>
                    </a>
                    <a href="#" class="catalog-item gallery-trigger-link scale-in" data-gallery-id="11" data-aos="zoom-in" data-aos-delay="100">
                        <img src="https://via.placeholder.com/400x300/b89635/ffffff?text=كتالوج+المقاولات" alt="كتالوج المقاولات" loading="lazy">
                        <h3 class="catalog-name" data-ar="كتالوج المقاولات" data-en="Contracting Catalog">كتالوج المقاولات</h3>
                    </a>
                    <a href="#" class="catalog-item gallery-trigger-link scale-in" data-gallery-id="12" data-aos="zoom-in" data-aos-delay="200">
                        <img src="https://via.placeholder.com/400x300/d4b85a/ffffff?text=كتالوج+الخدمات" alt="كتالوج الخدمات" loading="lazy">
                        <h3 class="catalog-name" data-ar="كتالوج الخدمات" data-en="Services Catalog">كتالوج الخدمات</h3>
                    </a>
                    <a href="#" class="catalog-item gallery-trigger-link scale-in" data-gallery-id="13" data-aos="zoom-in" data-aos-delay="300">
                        <img src="https://via.placeholder.com/400x300/977e2b/ffffff?text=كتالوج+المنتجات" alt="كتالوج المنتجات" loading="lazy">
                        <h3 class="catalog-name" data-ar="كتالوج المنتجات" data-en="Products Catalog">كتالوج المنتجات</h3>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>
<script>
// JavaScript مكون قسم الكتالوجات مع إصلاح مشاكل اللغة
(function() {
    'use strict';
   
    let currentLanguage = 'ar'; // اللغة الحالية
    let isInitialized = false; // تجنب التهيئة المتكررة
   
    // ترجمات العناوين والأوصاف
    const translations = {
        ar: {
            title: 'الكتالوجات',
            description: 'استعرض مجموعتنا المتنوعة من الكتالوجات المتخصصة'
        },
        en: {
            title: 'Catalogs',
            description: 'Browse our diverse collection of specialized catalogs'
        }
    };
   
    // تحديث النصوص الأساسية حسب اللغة
    function updateMainTexts(language) {
        const titleElement = document.getElementById('catalogsTitle');
        const descElement = document.getElementById('catalogsDescription');
       
        if (titleElement && translations[language]) {
            titleElement.textContent = translations[language].title;
        }
       
        if (descElement && translations[language]) {
            descElement.textContent = translations[language].description;
        }
    }
   
    // تحديث أسماء الكتالوجات حسب اللغة
    function updateCatalogNames(language) {
        const catalogNames = document.querySelectorAll('.catalog-name');
        catalogNames.forEach(element => {
            const nameAr = element.getAttribute('data-ar');
            const nameEn = element.getAttribute('data-en');
           
            if (language === 'ar' && nameAr) {
                element.textContent = nameAr;
            } else if (language === 'en' && nameEn) {
                element.textContent = nameEn;
            }
        });
    }
   
    // معالجة تغيير اللغة
    function handleLanguageChange(event) {
        const newLanguage = event.detail.language;
        console.log('الكتالوجات: تم استلام تغيير اللغة:', newLanguage);
       
        currentLanguage = newLanguage;
       
        // تحديث النصوص
        updateMainTexts(newLanguage);
        updateCatalogNames(newLanguage);
    }
   
    // تهيئة المكون
    function initializeComponent() {
        if (isInitialized) return;
       
        // الحصول على اللغة الحالية من النظام المركزي
        if (window.TranslationManager && window.TranslationManager.getCurrentLanguage) {
            currentLanguage = window.TranslationManager.getCurrentLanguage();
        } else {
            // fallback للنظام القديم
            try {
                const savedLang = localStorage.getItem('siteLanguage');
                if (savedLang && (savedLang === 'ar' || savedLang === 'en')) {
                    currentLanguage = savedLang;
                }
            } catch (error) {
                console.warn('خطأ في تحميل اللغة:', error);
            }
        }
       
        // تحديث النصوص للغة الحالية
        updateMainTexts(currentLanguage);
        updateCatalogNames(currentLanguage);
       
        // إعداد معالجات الأحداث
        setupEventHandlers();
        setupAnimations();
       
        isInitialized = true;
        console.log('تم تهيئة مكون الكتالوجات - اللغة الحالية:', currentLanguage);
    }
   
    // إعداد معالجات الأحداث
    function setupEventHandlers() {
        // معالجة النقر على روابط المعرض
        document.addEventListener('click', function(e) {
            const galleryLink = e.target.closest('.gallery-trigger-link');
            if (galleryLink && galleryLink.closest('.catalogs-component')) {
                e.preventDefault();
                e.stopPropagation();
               
                const galleryId = galleryLink.getAttribute('data-gallery-id');
                if (galleryId) {
                    if (typeof window.openImageGallery === 'function') {
                        console.log('فتح معرض كتالوج رقم:', galleryId);
                        window.openImageGallery(galleryId);
                    } else {
                        console.error('دالة فتح المعرض غير متوفرة - تأكد من تحميل مكون المعرض');
                        alert('مكون المعرض غير متوفر حالياً');
                    }
                }
            }
        });
       
        // معالجة أخطاء الصور
        document.addEventListener('error', (e) => {
            if (e.target.tagName === 'IMG' && e.target.closest('.catalogs')) {
                e.target.src = 'https://via.placeholder.com/400x300/977e2b/ffffff?text=صورة+غير+متوفرة';
                e.target.alt = 'صورة غير متوفرة';
            }
        }, true);
    }
   
    // إعداد التأثيرات والحركات
    function setupAnimations() {
        const catalogItems = document.querySelectorAll('.catalog-item');
       
        // إضافة تأثيرات hover مخصصة
        catalogItems.forEach((item, index) => {
            // تأخير التحريك لكل عنصر
            setTimeout(() => {
                item.classList.add('fade-in-catalog');
            }, index * 100);
           
            // تأثيرات النقر للكتالوجات
            item.addEventListener('click', function(e) {
                // تأثير النقر
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
            });
           
            // تأثير خاص لروابط المعرض
            item.addEventListener('mouseenter', function() {
                this.style.willChange = 'transform';
                this.style.backgroundColor = 'rgba(151, 126, 43, 0.05)';
            });
           
            item.addEventListener('mouseleave', function() {
                this.style.willChange = 'auto';
                this.style.backgroundColor = '';
            });
           
            // تأثير النقر للمعرض
            item.addEventListener('mousedown', function() {
                this.style.transform = 'scale(0.95)';
            });
           
            item.addEventListener('mouseup', function() {
                this.style.transform = '';
            });
        });
       
        // تأثير الظهور عند التمرير
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '50px'
            });
           
            document.querySelectorAll('.catalog-item').forEach(item => {
                observer.observe(item);
            });
        }
    }
   
    // الاستماع لتغييرات اللغة من النظام المركزي
    document.addEventListener('siteLanguageChanged', handleLanguageChange);
    document.addEventListener('languageChanged', handleLanguageChange); // التوافق مع النظام القديم
   
    // تشغيل المكون عند تحميل DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeComponent);
    } else {
        initializeComponent();
    }
   
    // إتاحة الوصول للوظائف من النطاق العام للتشخيص
    window.CatalogsManager = {
        updateLanguage: handleLanguageChange,
        getCurrentLanguage: () => currentLanguage,
        isInitialized: () => isInitialized
    };
   
    console.log('تم تحميل مكون الكتالوجات مع إصلاح مشاكل اللغة');
})();
</script>
<?php
// في حالة الوصول المباشر للملف
if (basename($_SERVER['PHP_SELF']) == 'catalogs-section.php') {
    echo '<!DOCTYPE html>
    <html lang="ar" dir="rtl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>مكون قسم الكتالوجات مع إصلاح اللغة - ألفا الذهبية</title>
        <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200;300;400;500;600;700;800;900&family=Tajawal:wght@200;300;400;500;700;800;900&display=swap" rel="stylesheet">
    </head>
    <body class="catalogs-standalone">
        <div class="demo-info">
            <h1>مكون قسم الكتالوجات مع إصلاح مشاكل اللغة</h1>
            <p>يعرض النصوص الصحيحة حسب اللغة المختارة</p>
            <p><strong>الإصلاحات:</strong></p>
            <ul style="text-align: right; max-width: 600px; margin: 0 auto;">
                <li>عرض النص العربي عندما تكون اللغة عربية</li>
                <li>عرض النص الإنجليزي من حقل "name" عندما تكون اللغة إنجليزية</li>
                <li>ترجمة العنوان والوصف حسب اللغة المختارة</li>
                <li>تحديث تلقائي عند تغيير اللغة دون إعادة التحميل</li>
                <li>حفظ واستعراض حالة اللغة من النظام المركزي</li>
            </ul>
        </div>
        ';
   
    echo '
    </body>
    </html>';
}
?>