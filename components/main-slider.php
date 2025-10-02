<?php
// مكون السلايدر الرئيسي - مستقل بالكامل مع دعم الترجمة

// إعدادات قاعدة البيانات للمكون
// دالة جلب البيانات للمكون
//baserow
$api_config_slider = [
    'baseUrl' => 'https://base.alfagolden.com',
    'token' => 'h5qAt85gtiJDAzpH51WrXPywhmnhrPWy',
    'sliderTableId' => 698 // جدول السلايدر
];
$FIELDS = [
    'slider' => [
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
//end baserow
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
        return null;
    }
    $decoded = json_decode($response, true);
    if (!$decoded) {
        error_log("فشل فك JSON: " . print_r($response, true));
    }
    return $decoded;
}   

    // جلب بيانات السلايدر
    $sliderData_MainSlider = makeApiRequestSlider('rows/table/' . $api_config_slider['sliderTableId'] . '/', 'GET', null, $params);

// تنظيف البيانات
function sanitizeData_Slider($data) {
    if (is_array($data)) {
        return array_map('sanitizeData_Slider', $data);
    }
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}

$sliderData_MainSlider = sanitizeData_Slider($sliderData_MainSlider);

/* ===========================
   ترتيب السجلات حسب "الاسم"
   ===========================
   - ترتيب أبجدي تصاعدي (أ -> ي)
   - السجلات بدون اسم تُدفع لآخر القائمة
*/
usort($sliderData_MainSlider, function($a, $b) {
    $an = isset($a[$FIELDS['slider']['name_ar']]) ? mb_strtolower(trim($a[$FIELDS['slider']['name_ar']]), 'UTF-8') : '';
    $bn = isset($b[$FIELDS['slider']['name_ar']]) ? mb_strtolower(trim($b[$FIELDS['slider']['name_ar']]), 'UTF-8') : '';

    // ضع العناصر الفارغة في النهاية
    if ($an === '' && $bn === '') return 0;
    if ($an === '') return 1;
    if ($bn === '') return -1;

    return $an <=> $bn; // استخدم ($bn <=> $an) للترتيب التنازلي
});
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

/* إعدادات أساسية للمكون */
.main-slider-component * {
    box-sizing: border-box;
}
.main-slider-component {
    font-family: 'Cairo', 'Tajawal', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* السلايدر الرئيسي */
.main-slider {
    width: 100%;
    position: relative;
    height: auto;
    max-height: none;
    overflow: hidden;
}
.main-slider .swiper {
    width: 100%;
    height: auto;
}
.main-slider .swiper-slide {
    position: relative;
    overflow: hidden;
    height: auto;
}
.main-slider .swiper-slide img {
    width: 100%;
    height: auto;
    object-fit: contain;
    transform: scale(1.05);
    transition: transform 6s ease-out;
    display: block;
}
.main-slider .swiper-slide-active img {
    transform: scale(1);
}

/* أزرار التنقل */
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

/* النقاط */
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

/* الاستجابة للشاشات المختلفة */
@media (max-width: 768px) {
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

/* للعرض المستقل */
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

<!-- HTML مكون السلايدر الرئيسي مع دعم الترجمة -->
<div class="main-slider-component">
    <section class="main-slider" aria-label="الصور الرئيسية">
        <div class="swiper mainSwiper">
            <div class="swiper-wrapper">
                <?php if (!empty($sliderData_MainSlider)): ?>
                    <?php foreach ($sliderData_MainSlider as $index => $slide): ?>
                        <div class="swiper-slide">
                            <img src="<?php echo $slide[$FIELDS['slider']['image']] ?? ''; ?>"
                                 alt="<?php echo $slide[$FIELDS['slider']['name_ar']] ?? 'صورة ' . ($index + 1); ?>"
                                 loading="<?php echo $index === 0 ? 'eager' : 'lazy'; ?>">
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- صور افتراضية للعرض -->
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
// JavaScript مكون السلايدر الرئيسي مع دعم الترجمة المحسّن
(function() {
    'use strict';

    // التأكد من تحميل Swiper
    if (typeof Swiper === 'undefined') {
        console.error('Swiper library is not loaded');
        return;
    }

    // === نظام إدارة اللغة المحلي ===
    let currentLanguage = 'ar';
    let swiperInstance = null;

    // تحميل اللغة من localStorage
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

    // تحديث النصوص - فقط النصوص وليس الصور
    function updateTexts(language) {
        currentLanguage = language;

        // تحديث نصوص الأزرار والتسميات
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

        // تحديث رسائل إمكانية الوصول للسلايدر
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

    // الاستماع لتغيير اللغة من الهيدر
    document.addEventListener('languageChanged', function(event) {
        updateTexts(event.detail.language);
        // لا نعيد تهيئة السلايدر هنا لتجنب اختفاء الصور
    });

    // تهيئة السلايدر الرئيسي
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
            // إعدادات الاستجابة
            breakpoints: {
                320: { spaceBetween: 0 },
                768: { spaceBetween: 0 },
                1024: { spaceBetween: 0 }
            }
        });

        return swiperInstance;
    }

    // تهيئة المكون عند التحميل
    function initializeComponent() {
        // تحميل اللغة أولاً
        currentLanguage = loadLanguageFromStorage();

        // تهيئة السلايدر
        initMainSwiper();

        // تحديث النصوص بعد التهيئة
        updateTexts(currentLanguage);

        // إعداد معالجات الأحداث
        setupEventHandlers();
    }

    // إعداد معالجات الأحداث
    function setupEventHandlers() {
        // إعادة تهيئة عند تغيير حجم النافذة
        window.addEventListener('resize', () => {
            if (swiperInstance) {
                swiperInstance.update();
            }
        });

        // معالجة أخطاء الصور
        document.addEventListener('error', (e) => {
            if (e.target.tagName === 'IMG' && e.target.closest('.main-slider')) {
                e.target.src = 'https://via.placeholder.com/1200x600/977e2b/ffffff?text=صورة+غير+متوفرة';
                e.target.alt = 'صورة غير متوفرة';
            }
        }, true);
    }

    // تشغيل المكون عند تحميل DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeComponent);
    } else {
        initializeComponent();
    }

    // رسالة تأكيد تحميل المكون
    console.log('تم تحميل مكون السلايدر الرئيسي مع دعم الترجمة المحسّن');
})();
</script>

<?php
// في حالة الوصول المباشر للملف
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
    ';
    // سيتم عرض المكون هنا تلقائياً
    echo '
    </body>
    </html>';
}
?>
