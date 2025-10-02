
<?php
// مكون قسم الكتالوجات - فلتر ثابت على موقع "كتالوجات" فقط في حقل "الموقع"
// إعدادات قاعدة البيانات للمكون (Baserow API - جدول 698)
// التكوين
$API_CONFIG = [
    'baseUrl' => 'https://base.alfagolden.com',
    'token' => 'h5qAt85gtiJDAzpH51WrXPywhmnhrPWy',
    'catalogsTableId' => 698 // جدول الكتالوجات
];
// خرائط الحقول لجدول الكتالوجات (بناءً على السيكما المقدمة)
$FIELDS = [
    'catalogs' => [
        'name_ar' => 'field_6754', // الاسم
        'image' => 'field_6755', // الصورة
        'location' => 'field_6756', // الموقع (فلترة ثابتة عليه)
        'link' => 'field_6757', // الرابط
        'file_id' => 'field_6758', // معرف الملف
        'order' => 'field_6759', // ترتيب
        'sub_order' => 'field_6760', // ترتيب فرعي
        'sub_name_ar' => 'field_6761', // الاسم الفرعي
        'name_en' => 'field_6762', // name (الاسم الإنجليزي)
        'status' => 'field_7072', // الحالة
        'status_en' => 'field_7073', // الحالة-en
        'sub_name_en' => 'field_7075', // الاسم الفرعي-en
        'description_ar' => 'field_7076', // نص
        'description_en' => 'field_7077' // نص-en
    ]
];
function makeApiRequest($endpoint, $method = 'GET', $data = null, $params = []) {
    global $API_CONFIG;
    $url = $API_CONFIG['baseUrl'] . '/api/database/' . $endpoint;
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    $options = [
        'http' => [
            'method' => $method,
            'header' => [
                'Authorization: Token ' . $API_CONFIG['token'],
                'Content-Type: application/json'
            ]
        ]
    ];
    if ($data) {
        $options['http']['content'] = json_encode($data);
    }
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        error_log("فشل طلب API: $url");
        return null;
    }
    $decoded = json_decode($response, true);
    if (!$decoded) {
        error_log("فشل فك JSON: " . print_r($response, true));
    }
    return $decoded;
}

function fetchCatalogsFromBase($tableId) {
    global $API_CONFIG, $FIELDS;
    try {
        $siteFilter = 'كتلوجات'; // لو القيمة في الجدول فعلاً "كتلوجات"، سيبها كده. لو "كتالوجات"، غيّرها.
        $results = [];
        $page = 1;
        $pageSize = 100; // جلب 100 سجل في كل صفحة

        do {
            $response = makeApiRequest("rows/table/{$tableId}/", 'GET', null, [
                'page' => $page,
                'size' => $pageSize,
                'filter__field_6756__contains' => $siteFilter, // فلترة على مستوى الـ API
                'order_by' => 'field_6759' // ترتيب حسب حقل order
            ]);
            if (!$response || !isset($response['results'])) {
                error_log("فشل جلب البيانات من Baserow: " . print_r($response, true));
                break;
            }
            $results = array_merge($results, $response['results']);
            $page++;
        } while (isset($response['next'])); // استمر طالما فيه صفحات إضافية

        // فلترة إضافية على العميل لو لازم
        $results = array_filter($results, function($item) use ($siteFilter) {
            $location = $item[$GLOBALS['FIELDS']['catalogs']['location']] ?? '';
            return stripos($location, $siteFilter) !== false;
        });

        return array_values($results);
    } catch (Exception $e) {
        error_log("خطأ في جلب الكتالوجات: " . $e->getMessage());
        return [];
    }
}
// جلب بيانات الكتالوجات (فلترة تلقائية على "كتالوجات")
$catalogData_Catalogs = fetchCatalogsFromBase($API_CONFIG['catalogsTableId']);
// تنظيف البيانات
function sanitizeData_Catalogs($data) {
    if (is_array($data)) {
        return array_map('sanitizeData_Catalogs', $data);
    }
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}
$catalogData_Catalogs = array_map(function($item) {
    foreach ($item as $key => $value) {
        if (is_string($value)) {
            $item[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        } elseif (is_array($value)) {
            $item[$key] = htmlspecialchars(implode(', ', (array)$value), ENT_QUOTES, 'UTF-8');
        }
    }
    return $item;
}, $catalogData_Catalogs);
// دالة تحديد معرف المعرض بناءً على اسم الكتالوج
function getCatalogGalleryId($catalogName) {
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
    return 1;
}
?>
<style>
/* أنماط مكون قسم الكتالوجات (نفس الأصلي، بس شلت أنماط الفلتر الديناميكي) */
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
.catalogs-component * {
    box-sizing: border-box;
}
.catalogs-component {
    font-family: 'Cairo', 'Tajawal', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}
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
/* رسالة فلترة ثابتة */
.filter-info {
    text-align: center;
    margin-bottom: var(--spacing-xl);
    padding: var(--spacing-md);
    background: var(--white);
    border-radius: var(--border-radius-2xl);
    box-shadow: var(--shadow-md);
    color: var(--medium-gray);
    font-size: 0.95rem;
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
.no-results {
    grid-column: 1 / -1;
    text-align: center;
    padding: var(--spacing-2xl);
    color: var(--medium-gray);
    font-size: var(--font-size-lg);
}
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
<!-- HTML مكون قسم الكتالوجات مع فلتر ثابت -->
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
                    <div class="no-results">
                        لا توجد كتالوجات لموقع "كتالوجات" حالياً. تأكد من البيانات في Baserow.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>
<script>
// JavaScript مكون قسم الكتالوجات (نفس الأصلي، شلت الجزء الخاص بالفلتر الديناميكي)
(function() {
    'use strict';
  
    let currentLanguage = 'ar';
    let isInitialized = false;
  
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
  
    function handleLanguageChange(event) {
        const newLanguage = event.detail ? event.detail.language : event.detail;
        console.log('الكتالوجات: تغيير اللغة إلى:', newLanguage);
      
        currentLanguage = newLanguage;
      
        updateMainTexts(newLanguage);
        updateCatalogNames(newLanguage);
    }
  
    function initializeComponent() {
        if (isInitialized) return;
      
        if (window.TranslationManager && window.TranslationManager.getCurrentLanguage) {
            currentLanguage = window.TranslationManager.getCurrentLanguage();
        } else {
            try {
                const savedLang = localStorage.getItem('siteLanguage');
                if (savedLang && (savedLang === 'ar' || savedLang === 'en')) {
                    currentLanguage = savedLang;
                }
            } catch (error) {
                console.warn('خطأ في تحميل اللغة:', error);
            }
        }
      
        updateMainTexts(currentLanguage);
        updateCatalogNames(currentLanguage);
      
        setupEventHandlers();
        setupAnimations();
      
        isInitialized = true;
        console.log('تم تهيئة مكون الكتالوجات (فلتر كتالوجات) - اللغة:', currentLanguage);
    }
  
    function setupEventHandlers() {
        document.addEventListener('click', function(e) {
            const galleryLink = e.target.closest('.gallery-trigger-link');
            if (galleryLink && galleryLink.closest('.catalogs-component')) {
                e.preventDefault();
                e.stopPropagation();
              
                const galleryId = galleryLink.getAttribute('data-gallery-id');
                if (galleryId) {
                    if (typeof window.openImageGallery === 'function') {
                        console.log('فتح معرض كتالوج:', galleryId);
                        window.openImageGallery(galleryId);
                    } else {
                        console.error('دالة المعرض غير متوفرة');
                        alert('مكون المعرض غير متوفر');
                    }
                }
            }
        });
      
        document.addEventListener('error', (e) => {
            if (e.target.tagName === 'IMG' && e.target.closest('.catalogs')) {
                e.target.src = 'https://via.placeholder.com/400x300/977e2b/ffffff?text=صورة+غير+متوفرة';
                e.target.alt = 'صورة غير متوفرة';
            }
        }, true);
    }
  
    function setupAnimations() {
        const catalogItems = document.querySelectorAll('.catalog-item');
      
        catalogItems.forEach((item, index) => {
            setTimeout(() => {
                item.classList.add('fade-in-catalog');
            }, index * 100);
          
            item.addEventListener('click', function(e) {
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
            });
          
            item.addEventListener('mouseenter', function() {
                this.style.willChange = 'transform';
                this.style.backgroundColor = 'rgba(151, 126, 43, 0.05)';
            });
          
            item.addEventListener('mouseleave', function() {
                this.style.willChange = 'auto';
                this.style.backgroundColor = '';
            });
          
            item.addEventListener('mousedown', function() {
                this.style.transform = 'scale(0.95)';
            });
          
            item.addEventListener('mouseup', function() {
                this.style.transform = '';
            });
        });
      
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
  
    document.addEventListener('languageChanged', handleLanguageChange);
  
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeComponent);
    } else {
        initializeComponent();
    }
  
    window.CatalogsManager = {
        updateLanguage: handleLanguageChange,
        getCurrentLanguage: () => currentLanguage,
        isInitialized: () => isInitialized
    };
  
    console.log('تم تحميل مكون الكتالوجات مع فلتر ثابت على "كتالوجات"');
})();
</script>
<?php
// عرض مستقل للاختبار
if (basename($_SERVER['PHP_SELF']) == 'catalogs-section.php') {
    echo '<!DOCTYPE html>
    <html lang="ar" dir="rtl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>مكون الكتالوجات - فلتر على موقع "كتالوجات" فقط</title>
        <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200;300;400;500;600;700;800;900&family=Tajawal:wght@200;300;400;500;700;800;900&display=swap" rel="stylesheet">
    </head>
    <body class="catalogs-standalone">
        <div class="demo-info">
            <h1>مكون الكتالوجات - فلتر ثابت على "كتالوجات"</h1>
            <p>يعرض الكتالوجات الخاصة بموقع "كتالوجات" فقط من حقل "الموقع" في Baserow.</p>
            <ul style="text-align: right; max-width: 600px; margin: 0 auto;">
                <li>فلترة تلقائية: يجيب النتائج اللي في "الموقع" تحتوي على "كتالوجات"</li>
                <li>ترتيب + حد 8 + دعم اللغة</li>
                <li>لو مفيش نتائج، رسالة واضحة</li>
            </ul>
        </div>
        ';
  
    echo '
    </body>
    </html>';
}
?>
<?php
// مكون قسم العملاء - مستقل بالكامل مع دعم الترجمة
// إعدادات قاعدة البيانات للمكون
$API_CONFIG = [
    'baseUrl' => 'https://base.alfagolden.com',
    'token' => 'h5qAt85gtiJDAzpH51WrXPywhmnhrPWy',
    'catalogsTableId' => 698 // جدول الكتالوجات
];
// خرائط الحقول لجدول الكتالوجات (بناءً على السيكما المقدمة)
$FIELDS = [
    'catalogs' => [
        'name_ar' => 'field_6754', // الاسم
        'image' => 'field_6755', // الصورة
        'location' => 'field_6756', // الموقع (فلترة ثابتة عليه)
        'link' => 'field_6757', // الرابط
        'file_id' => 'field_6758', // معرف الملف
        'order' => 'field_6759', // ترتيب
        'sub_order' => 'field_6760', // ترتيب فرعي
        'sub_name_ar' => 'field_6761', // الاسم الفرعي
        'name_en' => 'field_6762', // name (الاسم الإنجليزي)
        'status' => 'field_7072', // الحالة
        'status_en' => 'field_7073', // الحالة-en
        'sub_name_en' => 'field_7075', // الاسم الفرعي-en
        'description_ar' => 'field_7076', // نص
        'description_en' => 'field_7077' // نص-en
    ]
];


// جلب بيانات العملاء
function fetchClientsFromBase($tableId) {
    global $API_CONFIG, $FIELDS;
    try {
        $siteFilter = 'سلايدر العملاء'; // لو القيمة في الجدول فعلاً "كتلوجات"، سيبها كده. لو "كتالوجات"، غيّرها.
        $results = [];
        $page = 1;
        $pageSize = 100; // جلب 100 سجل في كل صفحة

        do {
            $response = makeApiRequest("rows/table/{$tableId}/", 'GET', null, [
                'page' => $page,
                'size' => $pageSize,
                'filter__field_6756__contains' => $siteFilter, // فلترة على مستوى الـ API
                'order_by' => 'field_6759' // ترتيب حسب حقل order
            ]);
            if (!$response || !isset($response['results'])) {
                error_log("فشل جلب البيانات من Baserow: " . print_r($response, true));
                break;
            }
            $results = array_merge($results, $response['results']);
            $page++;
        } while (isset($response['next'])); // استمر طالما فيه صفحات إضافية

        // فلترة إضافية على العميل لو لازم
        $results = array_filter($results, function($item) use ($siteFilter) {
            $location = $item[$GLOBALS['FIELDS']['catalogs']['location']] ?? '';
            return stripos($location, $siteFilter) !== false;
        });

        return array_values($results);
    } catch (Exception $e) {
        error_log("خطأ في جلب الكتالوجات: " . $e->getMessage());
        return [];
    }
}
$clientsData_Clients = fetchClientsFromBase($API_CONFIG['catalogsTableId']);
?>

<style>
/* أنماط مكون قسم العملاء */
:root {
    --gold: #977e2b;
    --gold-hover: #b89635;
    --dark-gray: #2c2c2c;
    --medium-gray: #666;
    --white: #ffffff;
    --spacing-md: 1rem;
    --spacing-lg: 1.5rem;
    --spacing-2xl: 4rem;
    --font-size-lg: 1.125rem;
    --font-size-4xl: 2.25rem;
    --transition-normal: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* إعدادات أساسية للمكون */
.clients-component * {
    box-sizing: border-box;
}

.clients-component {
    font-family: 'Cairo', 'Tajawal', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* قسم العملاء */
.clients {
    background: var(--white);
    overflow: hidden;
    position: relative;
    padding: var(--spacing-2xl) 0;
}

/* إضافة التلاشي الأبيض في النهايات */
.clients::before,
.clients::after {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    width: 150px;
    z-index: 2;
    pointer-events: none;
}

.clients::before {
    right: 0;
    background: linear-gradient(to left, rgba(255, 255, 255, 1) 0%, rgba(255, 255, 255, 0.8) 50%, rgba(255, 255, 255, 0) 100%);
}

.clients::after {
    left: 0;
    background: linear-gradient(to right, rgba(255, 255, 255, 1) 0%, rgba(255, 255, 255, 0.8) 50%, rgba(255, 255, 255, 0) 100%);
}

.container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 var(--spacing-lg);
}

.section-title {
    text-align: center;
    margin-bottom: var(--spacing-2xl);
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

.clients-slider {
    padding: 0;
}

.clients-slider .swiper-slide {
    display: flex;
    align-items: center;
    justify-content: center;
    transition: var(--transition-normal);
}

.client-logo {
    width: 180px;
    height: 180px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    border-radius: 0;
    padding: var(--spacing-md);
    box-shadow: none;
    transition: var(--transition-normal);
    border: none;
}

.client-logo img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    transition: var(--transition-normal);
    display: block;
}

/* الشعار الأوسط أكبر وملون */
.clients-slider .swiper-slide-active .client-logo {
    transform: scale(1.4);
}

.clients-slider .swiper-slide-active .client-logo img {
    filter: grayscale(0%) opacity(1);
}

.client-logo:hover img {
    filter: grayscale(0%) opacity(1);
    transform: scale(1.05);
}

/* الاستجابة للشاشات المختلفة */
@media (max-width: 768px) {
    .clients-slider .swiper-slide-active .client-logo {
        transform: scale(1.3);
    }
    
    .client-logo {
        width: 110px;
        height: 110px;
        padding: var(--spacing-sm);
    }
    
    /* تقليل التلاشي على الموبايل */
    .clients::before,
    .clients::after {
        width: 80px;
    }
    
    .section-title h2 {
        font-size: 1.5rem;
    }
}

@media (max-width: 480px) {
    .container {
        padding: 0 var(--spacing-md);
    }
}

/* للعرض المستقل */
.clients-standalone {
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

<!-- تحميل مكتبة Swiper -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">

<!-- HTML مكون قسم العملاء مع دعم الترجمة -->
<div class="clients-component">
    <section class="clients" data-aos="fade-up">
        <div class="container">
            <div class="section-title fade-in">
                <h2 data-translate="clients-title" data-ar="عملاؤنا" data-en="Our Clients">عملاؤنا</h2>
                <p data-translate="clients-description" data-ar="نفتخر بثقة عملائنا الكرام في خدماتنا المتميزة" data-en="We are proud of our valued clients' trust in our distinguished services">نفتخر بثقة عملائنا الكرام في خدماتنا المتميزة</p>
            </div>
            <div class="swiper clientsSwiper">
                <div class="swiper-wrapper">
                    <?php if (!empty($clientsData_Clients)): ?>
                        <?php foreach ($clientsData_Clients as $client): ?>
                            <div class="swiper-slide">
                                <div class="client-logo will-change-transform">
                                    <img src="<?php echo $client[$FIELDS['catalogs']['image']] ?? ''; ?>" 
                                         alt="شعار عميل <?php echo $client[$FIELDS['catalogs']['name_ar']] ?? ''; ?>"
                                         loading="lazy">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- شعارات عملاء افتراضية للعرض -->
                        <div class="swiper-slide">
                            <div class="client-logo">
                                <img src="https://via.placeholder.com/120x80/977e2b/ffffff?text=عميل+1" alt="شعار عميل 1" loading="lazy">
                            </div>
                        </div>
                        <div class="swiper-slide">
                            <div class="client-logo">
                                <img src="https://via.placeholder.com/120x80/b89635/ffffff?text=عميل+2" alt="شعار عميل 2" loading="lazy">
                            </div>
                        </div>
                        <div class="swiper-slide">
                            <div class="client-logo">
                                <img src="https://via.placeholder.com/120x80/d4b85a/ffffff?text=عميل+3" alt="شعار عميل 3" loading="lazy">
                            </div>
                        </div>
                        <div class="swiper-slide">
                            <div class="client-logo">
                                <img src="https://via.placeholder.com/120x80/977e2b/ffffff?text=عميل+4" alt="شعار عميل 4" loading="lazy">
                            </div>
                        </div>
                        <div class="swiper-slide">
                            <div class="client-logo">
                                <img src="https://via.placeholder.com/120x80/b89635/ffffff?text=عميل+5" alt="شعار عميل 5" loading="lazy">
                            </div>
                        </div>
                        <div class="swiper-slide">
                            <div class="client-logo">
                                <img src="https://via.placeholder.com/120x80/d4b85a/ffffff?text=عميل+6" alt="شعار عميل 6" loading="lazy">
                            </div>
                        </div>
                        <div class="swiper-slide">
                            <div class="client-logo">
                                <img src="https://via.placeholder.com/120x80/977e2b/ffffff?text=عميل+7" alt="شعار عميل 7" loading="lazy">
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- تحميل JavaScript لـ Swiper -->
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<script>
// JavaScript مكون قسم العملاء مع دعم الترجمة المحسّن
(function() {
    'use strict';
    
    // التأكد من تحميل Swiper
    if (typeof Swiper === 'undefined') {
        console.error('Swiper library is not loaded');
        return;
    }
    
    // === نظام إدارة اللغة المركزي ===
    let currentLanguage = 'ar'; // اللغة الافتراضية
    
    // تحميل اللغة من localStorage
    function loadLanguageFromStorage() {
        try {
            const savedLang = localStorage.getItem('siteLanguage');
            if (savedLang && (savedLang === 'ar' || savedLang === 'en')) {
                currentLanguage = savedLang;
                return savedLang;
            }
        } catch (error) {
            console.warn('خطأ في تحميل اللغة:', error);
        }
        return 'ar';
    }
    
    // تحديث النصوص حسب اللغة - دون التأثير على Swiper
    function updateTexts(language) {
        currentLanguage = language;
        const elementsToTranslate = document.querySelectorAll('.clients-component [data-translate]');
        elementsToTranslate.forEach(element => {
            const arText = element.getAttribute('data-ar');
            const enText = element.getAttribute('data-en');
            
            if (language === 'ar' && arText) {
                element.textContent = arText;
            } else if (language === 'en' && enText) {
                element.textContent = enText;
            }
        });
    }
    
    // الاستماع لتغيير اللغة من الهيدر
    document.addEventListener('languageChanged', function(event) {
        updateTexts(event.detail.language);
        // لا نعيد تهيئة Swiper هنا لتجنب اختفاء الصور
    });
    
    // تهيئة سلايدر العملاء
    function initClientsSwiper() {
        const swiperElement = document.querySelector('.clientsSwiper');
        if (!swiperElement) return null;
        
        const clientsSwiper = new Swiper('.clientsSwiper', {
            loop: true,
            autoplay: {
                delay: 700,
                disableOnInteraction: false,
                pauseOnMouseEnter: true
            },
            speed: 800,
            slidesPerView: 3,
            centeredSlides: true,
            spaceBetween: 70,
            breakpoints: {
                320: {
                    slidesPerView: 3,
                    spaceBetween: 25
                },
                640: {
                    slidesPerView: 3,
                    spaceBetween: 35
                },
                768: {
                    slidesPerView: 5,
                    spaceBetween: 45
                },
                1024: {
                    slidesPerView: 7,
                    spaceBetween: 50
                },
                1440: {
                    slidesPerView: 9,
                    spaceBetween: 60
                }
            },
            lazy: {
                loadPrevNext: true
            },
            keyboard: {
                enabled: true
            },
            grabCursor: true
        });
        
        return clientsSwiper;
    }
    
    // تهيئة المكون عند التحميل
    function initializeComponent() {
        // تحميل اللغة أولاً
        currentLanguage = loadLanguageFromStorage();
        updateTexts(currentLanguage);
        
        // ثم تهيئة السلايدر
        initClientsSwiper();
    }
    
    // تشغيل المكون عند تحميل DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeComponent);
    } else {
        initializeComponent();
    }
    
    // معالجة أخطاء الصور
    document.addEventListener('error', (e) => {
        if (e.target.tagName === 'IMG' && e.target.closest('.clients')) {
            e.target.src = 'https://via.placeholder.com/120x80/977e2b/ffffff?text=شعار+غير+متوفر';
            e.target.alt = 'شعار غير متوفر';
        }
    }, true);
    
    // تحسين الأداء
    const clientLogos = document.querySelectorAll('.client-logo');
    clientLogos.forEach(logo => {
        logo.addEventListener('mouseenter', function() {
            this.style.willChange = 'transform';
        });
        
        logo.addEventListener('mouseleave', function() {
            this.style.willChange = 'auto';
        });
    });
})();
</script>

<?php
// في حالة الوصول المباشر للملف
if (basename($_SERVER['PHP_SELF']) == 'clients-section.php') {
    echo '<!DOCTYPE html>
    <html lang="ar" dir="rtl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>مكون قسم العملاء مع الترجمة - ألفا الذهبية</title>
        <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200;300;400;500;600;700;800;900&family=Tajawal:wght@200;300;400;500;700;800;900&display=swap" rel="stylesheet">
    </head>
    <body class="clients-standalone">
        <div class="demo-info">
            <h1>مكون قسم العملاء مع دعم الترجمة</h1>
            <p>هذا هو مكون قسم العملاء يعمل بشكل مستقل مع دعم تبديل اللغة</p>
        </div>
    ';
    
    // سيتم عرض المكون هنا تلقائياً
    
    echo '
    </body>
    </html>';
}
?>