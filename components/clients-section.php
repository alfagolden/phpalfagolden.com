<?php
// مكون قسم العملاء - مستقل بالكامل مع دعم الترجمة
// إعدادات قاعدة البيانات للمكون
if (!defined('NOCODB_TOKEN')) {
    define('NOCODB_TOKEN', 'fwVaKHr6zbDns5iW9u8annJUf5LCBJXjqPfujIpV');
    define('NOCODB_API_URL', 'https://app.nocodb.com/api/v2/tables/');
}

// دالة جلب البيانات للمكون
function fetchNocoDB_Clients($tableId, $viewId = '') {
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

// جلب بيانات العملاء
$clientsData_Clients = fetchNocoDB_Clients('ma95crsjyfik3ce', 'vwgb3ystgr089gb9');

// تنظيف البيانات
function sanitizeData_Clients($data) {
    if (is_array($data)) {
        return array_map('sanitizeData_Clients', $data);
    }
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}

$clientsData_Clients = sanitizeData_Clients($clientsData_Clients);
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
                                    <img src="<?php echo $client['الصورة'] ?? ''; ?>" 
                                         alt="شعار عميل <?php echo $client['الاسم'] ?? ''; ?>"
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