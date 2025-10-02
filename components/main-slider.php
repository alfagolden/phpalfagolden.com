<?php
// مكون السلايدر الرئيسي - مستقل بالكامل مع دعم الترجمة

// إعدادات قاعدة البيانات للمكون
$api_config_slider = [
    'baseUrl' => 'https://base.alfagolden.com',
    'token' => 'h5qAt85gtiJDAzpH51WrXPywhmnhrPWy',
    'sliderTableId' => 698
];
$FIELDS = [
    'slider' => [
        'name_ar' => 'field_6754',
        'image' => 'field_6755',
        'location' => 'field_6756',
        'link' => 'field_6757',
        'file_id' => 'field_6758',
        'order' => 'field_6759',
        'sub_order' => 'field_6760',
        'sub_name_ar' => 'field_6761',
        'name_en' => 'field_6762',
        'status' => 'field_7072',
        'status_en' => 'field_7073',
        'sub_name_en' => 'field_7075',
        'description_ar' => 'field_7076',
        'description_en' => 'field_7077'
    ]
];

// دالة جلب البيانات من Baserow
function makeApiRequestSlider($endpoint, $method = 'GET', $data = null, $params = []) {
    global $api_config_slider;
    $url = $api_config_slider['baseUrl'] . '/api/database/' . $endpoint;
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    $options = [
        'http' => [
            'method' => $method,
            'header' => [
                'Authorization: Token ' . $api_config_slider['token'],
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
        return [];
    }
    $decoded = json_decode($response, true);
    if (!$decoded) {
        error_log("فشل فك JSON: " . print_r($response, true));
        return [];
    }
    return isset($decoded['results']) ? $decoded['results'] : $decoded;
}

// تنظيف البيانات
function sanitizeData_Slider($data) {
    if (!is_array($data)) {
        return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
    }
    $sanitized = [];
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $sanitized[$key] = sanitizeData_Slider($value);
        } else {
            $sanitized[$key] = htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
        }
    }
    return $sanitized;
}

// جلب بيانات السلايدر
$params = []; // يمكنك إضافة معايير فلترة لو لازم
$sliderData_MainSlider = makeApiRequestSlider('rows/table/' . $api_config_slider['sliderTableId'] . '/', 'GET', null, $params);

// فحص البيانات
if (!is_array($sliderData_MainSlider)) {
    $sliderData_MainSlider = [];
    error_log("لا توجد بيانات سلايدر متاحة.");
} else {
    $sliderData_MainSlider = sanitizeData_Slider($sliderData_MainSlider);

    // ترتيب السجلات حسب الاسم الأبجدي
    usort($sliderData_MainSlider, function($a, $b) use ($FIELDS) {
        $nameA = $a[$FIELDS['slider']['name_ar']] ?? '';
        $nameB = $b[$FIELDS['slider']['name_ar']] ?? '';
        if (empty($nameA) && empty($nameB)) return 0;
        if (empty($nameA)) return 1;
        if (empty($nameB)) return -1;
        return strcmp($nameA, $nameB);
    });

    // فلترة حسب سلايدر الهيدر
    $siteFilter = isset($siteFilter) ? $siteFilter : 'سلايدر الهيدر';
    if (!empty($siteFilter)) {
        $sliderData_MainSlider = array_filter($sliderData_MainSlider, function($item) use ($siteFilter, $FIELDS) {
            $location = $item[$FIELDS['slider']['location']] ?? '';
            return stripos($location, $siteFilter) !== false;
        });
    }
}
?>

<style>
/* أنماط مكون السلايدر الرئيسي */
:root {
    --gold: #977e2b;
    --gold-hover: #b89635;
    --white: #ffffff;
    --shadow-md: 0 4px 8px rgba(0, 0, 0, 0.12), 0 2px 4px rgba(0, 0, 0, 0.08);
    --shadow-lg: 0 15px 25px rgba(0, 0, 0, 0.15), 0 5px 10px rgba(0, 0, 0, 0.05);
    --transition-normal: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.main-slider-component * {
    box-sizing: border-box;
}
.main-slider-component {
    font-family: 'Cairo', 'Tajawal', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.main-slider {
    width: 100%;
    position: relative;
    overflow: hidden;
    aspect-ratio: 16 / 9; /* نسبة ثابتة لتجنب الفراغات */
}
.main-slider .swiper {
    width: 100%;
    height: 80%;
}
.main-slider .swiper-slide {
    position: relative;
    overflow: hidden;
    width: 100%;
    height: 80%;
    background: #f8f9fa; /* خلفية لملء أي فراغات */
}
.main-slider .swiper-slide img {
    width: 100%;
    height: 100%;
    object-fit: cover; ملء الحاوية مع الحفاظ على النسب
    /* display: block; */
}
.main-slider .swiper-button-next,
.main-slider .swiper-button-prev {
    color: var(--gold);
    background: rgba(255, 255, 255, 0.9);
    width: 50px;
    height: 50px;
    border-radius: 50%;
    backdrop-filter: blur(10px);
    transition: var(--transition-normal);
    box-shadow: var(--shadow-md);
    border: 1px solid rgba(151, 126, 43, 0.2);
}
.main-slider .swiper-button-next:hover,
.main-slider .swiper-button-prev:hover {
    background: var(--gold);
    color: var(--white);
    transform: scale(1.1);
    box-shadow: var(--shadow-lg);
}
.main-slider .swiper-button-next:after,
.main-slider .swiper-button-prev:after {
    font-size: 18px;
    font-weight: 600;
}
.main-slider .swiper-pagination-bullet {
    background: rgba(255, 255, 255, 0.6);
    opacity: 1;
    width: 10px;
    height: 10px;
    transition: var(--transition-normal);
    border: 2px solid transparent;
}
.main-slider .swiper-pagination-bullet-active {
    background: var(--gold);
    width: 25px;
    border-radius: 5px;
    border-color: rgba(255, 255, 255, 0.3);
}
@media (max-width: 768px) {
    .main-slider {
        aspect-ratio: 4 / 3; /* نسبة مختلفة للشاشات الصغيرة */
    }
    .main-slider .swiper-button-next,
    .main-slider .swiper-button-prev {
        width: 40px;
        height: 40px;
    }
    .main-slider .swiper-button-next:after,
    .main-slider .swiper-button-prev:after {
        font-size: 14px;
    }
}
.slider-standalone {
    background: #f8f9fa;
    min-height: 100vh;
    padding: 2rem;
}
.slider-standalone .main-slider {
    max-width: 1200px;
    margin: 0 auto;
    box-shadow: var(--shadow-lg);
    border-radius: 1rem;
    overflow: hidden;
}
.demo-info {
    text-align: center;
    margin-bottom: 2rem;
    color: #666;
}
.demo-info h1 {
    color: var(--gold);
    margin-bottom: 1rem;
    font-size: 2rem;
}
</style>

<!-- تحميل مكتبة Swiper -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">

<!-- HTML مكون السلايدر الرئيسي -->
<div class="main-slider-component">
    <section class="main-slider" aria-label="الصور الرئيسية">
        <div class="swiper mainSwiper">
            <div class="swiper-wrapper">
                <?php if (!empty($sliderData_MainSlider)): ?>
                    <?php foreach ($sliderData_MainSlider as $index => $slide): ?>
                        <div class="swiper-slide">
                            <img src="<?php echo !empty($slide[$FIELDS['slider']['image']]['url']) ? $slide[$FIELDS['slider']['image']]['url'] : (!empty($slide[$FIELDS['slider']['image']]) ? $slide[$FIELDS['slider']['image']] : 'https://via.placeholder.com/1200x600/977e2b/ffffff?text=صورة+غير+متوفرة'); ?>"
                                 alt="<?php echo !empty($slide[$FIELDS['slider']['name_ar']]) ? $slide[$FIELDS['slider']['name_ar']] : 'صورة ' . ($index + 1); ?>"
                                 loading="<?php echo $index === 0 ? 'eager' : 'lazy'; ?>">
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="swiper-slide">
                        <img src="https://via.placeholder.com/1200x600/977e2b/ffffff?text=شركة+ألفا+الذهبية" alt="صورة افتراضية 1" loading="eager">
                    </div>
                    <div class="swiper-slide">
                        <img src="https://via.placeholder.com/1200x600/b89635/ffffff?text=للمصاعد+والمقاولات" alt="صورة افتراضية 2" loading="lazy">
                    </div>
                    <div class="swiper-slide">
                        <img src="https://via.placeholder.com/1200x600/d4b85a/ffffff?text=خدمات+متميزة" alt="صورة افتراضية 3" loading="lazy">
                    </div>
                <?php endif; ?>
            </div>
            <button class="swiper-button-next" aria-label="الصورة التالية" data-translate="next-slide" data-ar="الصورة التالية" data-en="Next Slide"></button>
            <button class="swiper-button-prev" aria-label="الصورة السابقة" data-translate="prev-slide" data-ar="الصورة السابقة" data-en="Previous Slide"></button>
            <div class="swiper-pagination" role="tablist" aria-label="اختيار الصورة" data-translate="select-slide" data-ar="اختيار الصورة" data-en="Select Slide"></div>
        </div>
    </section>
</div>

<!-- تحميل JavaScript لـ Swiper -->
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<script>
// JavaScript مكون السلايدر الرئيسي مع دعم الترجمة
(function() {
    'use strict';

    if (typeof Swiper === 'undefined') {
        console.error('Swiper library is not loaded');
        return;
    }

    let currentLanguage = 'ar';
    let swiperInstance = null;

    function loadLanguageFromStorage() {
        try {
            const savedLang = localStorage.getItem('siteLanguage');
            if (savedLang && (savedLang === 'ar' || savedLang === 'en')) {
                return savedLang;
            }
        } catch (error) {
            console.warn('خطأ في تحميل اللغة:', error);
        }
        return 'ar';
    }

    function updateTexts(language) {
        currentLanguage = language;
        const elementsToTranslate = document.querySelectorAll('.main-slider-component [data-translate]');
        elementsToTranslate.forEach(element => {
            const arText = element.getAttribute('data-ar');
            const enText = element.getAttribute('data-en');
            if (language === 'ar' && arText) {
                if (element.hasAttribute('aria-label')) {
                    element.setAttribute('aria-label', arText);
                } else {
                    element.textContent = arText;
                }
            } else if (language === 'en' && enText) {
                if (element.hasAttribute('aria-label')) {
                    element.setAttribute('aria-label', enText);
                } else {
                    element.textContent = enText;
                }
            }
        });

        if (swiperInstance && swiperInstance.a11y) {
            const messages = language === 'ar' ? {
                prevSlideMessage: 'الصورة السابقة',
                nextSlideMessage: 'الصورة التالية',
                paginationBulletMessage: 'انتقل إلى الصورة {{index}}'
            } : {
                prevSlideMessage: 'Previous slide',
                nextSlideMessage: 'Next slide',
                paginationBulletMessage: 'Go to slide {{index}}'
            };
            Object.assign(swiperInstance.a11y, messages);
        }
    }

    document.addEventListener('languageChanged', function(event) {
        updateTexts(event.detail.language);
    });

    function initMainSwiper() {
        const swiperElement = document.querySelector('.mainSwiper');
        if (!swiperElement) return null;

        swiperInstance = new Swiper('.mainSwiper', {
            loop: true,
            autoplay: {
                delay: 4000,
                disableOnInteraction: false,
                pauseOnMouseEnter: true
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
                bulletClass: 'swiper-pagination-bullet',
                bulletActiveClass: 'swiper-pagination-bullet-active'
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            effect: 'fade',
            fadeEffect: {
                crossFade: true
            },
            speed: 1200,
            lazy: {
                loadPrevNext: true,
                loadPrevNextAmount: 1
            },
            keyboard: {
                enabled: true,
                onlyInViewport: true
            },
            a11y: {
                enabled: true,
                prevSlideMessage: currentLanguage === 'ar' ? 'الصورة السابقة' : 'Previous slide',
                nextSlideMessage: currentLanguage === 'ar' ? 'الصورة التالية' : 'Next slide',
                paginationBulletMessage: currentLanguage === 'ar' ? 'انتقل إلى الصورة {{index}}' : 'Go to slide {{index}}'
            },
            breakpoints: {
                320: { spaceBetween: 0 },
                768: { spaceBetween: 0 },
                1024: { spaceBetween: 0 }
            }
        });

        return swiperInstance;
    }

    function initializeComponent() {
        currentLanguage = loadLanguageFromStorage();
        initMainSwiper();
        updateTexts(currentLanguage);
        setupEventHandlers();
    }

    function setupEventHandlers() {
        window.addEventListener('resize', () => {
            if (swiperInstance) {
                swiperInstance.update();
            }
        });

        document.addEventListener('error', (e) => {
            if (e.target.tagName === 'IMG' && e.target.closest('.main-slider')) {
                e.target.src = 'https://via.placeholder.com/1200x600/977e2b/ffffff?text=صورة+غير+متوفرة';
                e.target.alt = 'صورة غير متوفرة';
            }
        }, true);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeComponent);
    } else {
        initializeComponent();
    }

    console.log('تم تحميل مكون السلايدر الرئيسي مع دعم الترجمة المحسّن');
})();
</script>

<?php
if (basename($_SERVER['PHP_SELF']) == 'main-slider.php') {
    echo '<!DOCTYPE html>
    <html lang="ar" dir="rtl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>مكون السلايدر الرئيسي مع الترجمة - ألفا الذهبية</title>
        <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200;300;400;500;600;700;800;900&family=Tajawal:wght@200;300;400;500;700;800;900&display=swap" rel="stylesheet">
    </head>
    <body class="slider-standalone">
        <div class="demo-info">
            <h1>مكون السلايدر الرئيسي مع دعم الترجمة</h1>
            <p>هذا هو مكون السلايدر الرئيسي يعمل بشكل مستقل مع دعم تبديل اللغة</p>
            <p><strong>المميزات:</strong></p>
            <ul style="text-align: right; max-width: 600px; margin: 0 auto;">
                <li>دعم الترجمة للأزرار والتسميات</li>
                <li>الحفاظ على الصور عند تغيير اللغة</li>
                <li>تحديث رسائل إمكانية الوصول</li>
                <li>مزامنة تلقائية مع نظام اللغة المركزي</li>
                <li>ترتيب الصور أبجديًا حسب الاسم</li>
            </ul>
        </div>
    </body>
    </html>';
}
?>