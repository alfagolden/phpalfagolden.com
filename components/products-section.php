<?php
// مكون قسم المنتجات - مع نظام الترجمة المركزي
// إعدادات قاعدة البيانات للمكون
if (!defined('NOCODB_TOKEN')) {
    define('NOCODB_TOKEN', 'fwVaKHr6zbDns5iW9u8annJUf5LCBJXjqPfujIpV');
    define('NOCODB_API_URL', 'https://app.nocodb.com/api/v2/tables/');
}

// دالة جلب البيانات للمكون
function fetchNocoDB_Products($tableId, $viewId = '') {
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

// جلب بيانات المنتجات/الفئات
$categoriesData_Products = fetchNocoDB_Products('m1g39mqv5mtdwad');

// تنظيف البيانات
function sanitizeData_Products($data) {
    if (is_array($data)) {
        return array_map('sanitizeData_Products', $data);
    }
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}

$categoriesData_Products = sanitizeData_Products($categoriesData_Products);
?>

<style>
/* أنماط مكون قسم المنتجات */
:root {
    --gold: #977e2b;
    --gold-hover: #b89635;
    --dark-gray: #2c2c2c;
    --medium-gray: #666;
    --light-gray: #f8f9fa;
    --white: #ffffff;
    --spacing-sm: 0.5rem;
    --spacing-md: 1rem;
    --spacing-lg: 1.5rem;
    --spacing-xl: 3rem;
    --spacing-2xl: 4rem;
    --font-size-lg: 1.125rem;
    --font-size-4xl: 2.25rem;
    --border-radius-2xl: 1.5rem;
    --shadow-md: 0 4px 8px rgba(0, 0, 0, 0.12), 0 2px 4px rgba(0, 0, 0, 0.08);
    --shadow-xl: 0 20px 40px rgba(0, 0, 0, 0.1);
    --transition-normal: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    --transition-slow: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

/* إعدادات أساسية للمكون */
.products-component * {
    box-sizing: border-box;
}

.products-component {
    font-family: 'Cairo', 'Tajawal', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* قسم المنتجات */
.product-categories {
    background: var(--light-gray);
    padding: var(--spacing-2xl) 0;
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

.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: var(--spacing-xl);
}

.category-card {
    background: var(--white);
    border-radius: var(--border-radius-2xl);
    padding: var(--spacing-lg);
    text-align: center;
    box-shadow: var(--shadow-md);
    transition: var(--transition-normal);
    cursor: pointer;
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(0, 0, 0, 0.05);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 220px;
    text-decoration: none;
    color: inherit;
}

.category-card::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(151, 126, 43, 0.1) 0%, transparent 70%);
    opacity: 0;
    transition: var(--transition-slow);
    transform: scale(0);
}

.category-card:hover::before {
    opacity: 1;
    transform: scale(1);
}

.category-card:hover {
    transform: translateY(-8px);
    box-shadow: var(--shadow-xl);
    border-color: rgba(151, 126, 43, 0.2);
}

.category-card img {
    width: auto;
    height: auto;
    max-width: 130px;
    max-height: 130px;
    object-fit: contain;
    margin: 0 auto var(--spacing-lg);
    border-radius: 0;
    transition: var(--transition-normal);
    box-shadow: none;
    border: none;
    display: block;
}

.category-card:hover img {
    transform: scale(1.1);
}

.category-card h4 {
    color: var(--dark-gray);
    font-weight: 700;
    transition: var(--transition-normal);
    position: relative;
    z-index: 1;
    line-height: 1.3;
    margin: 0;
    margin-top: auto;
    font-size: clamp(0.875rem, 2vw, 1.125rem);
    text-align: center;
    word-wrap: break-word;
    hyphens: auto;
}

.category-card:hover h4 {
    color: var(--gold);
}

/* الاستجابة للشاشات المختلفة */
@media (max-width: 768px) {
    .categories-grid {
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: var(--spacing-md);
    }
    
    .category-card {
        padding: var(--spacing-md);
        min-height: 180px;
    }
    
    .category-card img {
        max-width: 90px;
        max-height: 90px;
    }
    
    .section-title h2 {
        font-size: 1.5rem;
    }
}

@media (max-width: 480px) {
    .categories-grid {
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    }
    
    .category-card {
        min-height: 160px;
        padding: var(--spacing-sm);
    }
    
    .category-card img {
        max-width: 70px;
        max-height: 70px;
    }
    
    .container {
        padding: 0 var(--spacing-md);
    }
}

/* للعرض المستقل */
.products-standalone {
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

<!-- HTML مكون قسم المنتجات مع الترجمة -->
<div class="products-component">
    <section class="product-categories" data-aos="fade-up">
        <div class="container">
            <div class="section-title fade-in">
                <h2 id="productsTitle">منتجاتنا</h2>
                <p id="productsDescription">تشكيلة واسعة من المنتجات عالية الجودة لتلبية جميع احتياجاتكم</p>
            </div>
            <div class="categories-grid">
                <?php if (!empty($categoriesData_Products)): ?>
                    <?php foreach ($categoriesData_Products as $index => $category): ?>
                        <a href="<?php echo !empty($category['الرابط']) ? $category['الرابط'] : '#'; ?>" 
                           class="category-card scale-in will-change-transform" 
                           data-aos="fade-up" 
                           data-aos-delay="<?php echo $index * 50; ?>"
                           tabindex="0"
                           role="button"
                           data-arabic-name="<?php echo $category['الاسم'] ?? ''; ?>"
                           data-english-name="<?php echo $category['name'] ?? ''; ?>"
                           aria-label="منتجات <?php echo $category['الاسم'] ?? ''; ?>">
                            <img src="<?php echo $category['الصورة'] ?? ''; ?>" 
                                 alt="<?php echo $category['الاسم'] ?? ''; ?>"
                                 loading="lazy">
                            <h4 class="category-name"><?php echo $category['الاسم'] ?? ''; ?></h4>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- فئات منتجات افتراضية للعرض -->
                    <a href="#" class="category-card scale-in" data-aos="fade-up" data-arabic-name="مصاعد كهربائية" data-english-name="Electric Elevators">
                        <img src="https://via.placeholder.com/130x130/977e2b/ffffff?text=مصاعد" alt="مصاعد" loading="lazy">
                        <h4 class="category-name">مصاعد كهربائية</h4>
                    </a>
                    <a href="#" class="category-card scale-in" data-aos="fade-up" data-aos-delay="50" data-arabic-name="مصاعد هيدروليكية" data-english-name="Hydraulic Elevators">
                        <img src="https://via.placeholder.com/130x130/b89635/ffffff?text=هيدروليك" alt="مصاعد هيدروليكية" loading="lazy">
                        <h4 class="category-name">مصاعد هيدروليكية</h4>
                    </a>
                    <a href="#" class="category-card scale-in" data-aos="fade-up" data-aos-delay="100" data-arabic-name="مصاعد بضائع" data-english-name="Freight Elevators">
                        <img src="https://via.placeholder.com/130x130/d4b85a/ffffff?text=بضائع" alt="مصاعد بضائع" loading="lazy">
                        <h4 class="category-name">مصاعد بضائع</h4>
                    </a>
                    <a href="#" class="category-card scale-in" data-aos="fade-up" data-aos-delay="150" data-arabic-name="مقاولات عامة" data-english-name="General Contracting">
                        <img src="https://via.placeholder.com/130x130/977e2b/ffffff?text=مقاولات" alt="مقاولات" loading="lazy">
                        <h4 class="category-name">مقاولات عامة</h4>
                    </a>
                    <a href="#" class="category-card scale-in" data-aos="fade-up" data-aos-delay="200" data-arabic-name="خدمات الصيانة" data-english-name="Maintenance Services">
                        <img src="https://via.placeholder.com/130x130/b89635/ffffff?text=صيانة" alt="صيانة" loading="lazy">
                        <h4 class="category-name">خدمات الصيانة</h4>
                    </a>
                    <a href="#" class="category-card scale-in" data-aos="fade-up" data-aos-delay="250" data-arabic-name="قطع الغيار" data-english-name="Spare Parts">
                        <img src="https://via.placeholder.com/130x130/d4b85a/ffffff?text=قطع" alt="قطع غيار" loading="lazy">
                        <h4 class="category-name">قطع الغيار</h4>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<script>
// JavaScript مكون قسم المنتجات مع نظام الترجمة المركزي
(function() {
    'use strict';
    
    let currentLanguage = 'ar'; // اللغة الحالية
    let isInitialized = false; // تجنب التهيئة المتكررة
    
    // ترجمات العناوين والأوصاف
    const translations = {
        ar: {
            title: 'منتجاتنا',
            description: 'تشكيلة واسعة من المنتجات عالية الجودة لتلبية جميع احتياجاتكم'
        },
        en: {
            title: 'Our Products',
            description: 'A wide range of high-quality products to meet all your needs'
        }
    };
    
    // تحديث النصوص الأساسية حسب اللغة
    function updateMainTexts(language) {
        const titleElement = document.getElementById('productsTitle');
        const descElement = document.getElementById('productsDescription');
        
        if (titleElement && translations[language]) {
            titleElement.textContent = translations[language].title;
        }
        
        if (descElement && translations[language]) {
            descElement.textContent = translations[language].description;
        }
    }
    
    // تحديث أسماء المنتجات حسب اللغة
    function updateProductNames(language) {
        const categoryCards = document.querySelectorAll('.category-card');
        
        categoryCards.forEach(card => {
            const nameElement = card.querySelector('.category-name');
            if (nameElement) {
                if (language === 'ar') {
                    // عرض الاسم العربي
                    const arabicName = card.getAttribute('data-arabic-name');
                    if (arabicName) {
                        nameElement.textContent = arabicName;
                    }
                } else {
                    // عرض الاسم الإنجليزي
                    const englishName = card.getAttribute('data-english-name');
                    if (englishName) {
                        nameElement.textContent = englishName;
                    }
                }
            }
        });
    }
    
    // معالجة تغيير اللغة
    function handleLanguageChange(event) {
        const newLanguage = event.detail.language;
        console.log('المنتجات: تم استلام تغيير اللغة:', newLanguage);
        
        currentLanguage = newLanguage;
        
        // تحديث النصوص الأساسية
        updateMainTexts(newLanguage);
        
        // تحديث أسماء المنتجات
        updateProductNames(newLanguage);
        
        console.log('تم تحديث مكون المنتجات للغة:', newLanguage);
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
        updateProductNames(currentLanguage);
        
        // تهيئة التأثيرات والتفاعل
        initializeInteractions();
        
        isInitialized = true;
        console.log('تم تهيئة مكون المنتجات - اللغة الحالية:', currentLanguage);
    }
    
    // تهيئة التأثيرات والتفاعل
    function initializeInteractions() {
        const categoryCards = document.querySelectorAll('.category-card');
        
        // إضافة تأثيرات متقدمة للبطاقات
        categoryCards.forEach((card, index) => {
            // تأخير الظهور
            setTimeout(() => {
                card.classList.add('fade-in-category');
            }, index * 50);
            
            // تأثير النقر
            card.addEventListener('click', function(e) {
                this.style.transform = 'scale(0.95) translateY(-8px)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
            });
            
            // تأثير التركيز بالكيبورد
            card.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.click();
                }
            });
            
            // تحسين الأداء
            card.addEventListener('mouseenter', function() {
                this.style.willChange = 'transform';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.willChange = 'auto';
            });
        });
        
        // تأثير الهوفر المتطور
        categoryCards.forEach(card => {
            const img = card.querySelector('img');
            const title = card.querySelector('h4');
            
            card.addEventListener('mouseenter', function() {
                if (img) img.style.transform = 'scale(1.1) rotate(2deg)';
                if (title) title.style.transform = 'translateY(-2px)';
            });
            
            card.addEventListener('mouseleave', function() {
                if (img) img.style.transform = '';
                if (title) title.style.transform = '';
            });
        });
        
        // تأثير الظهور عند التمرير (بديل بسيط لـ AOS)
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '50px'
            });
            
            categoryCards.forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                card.style.transition = 'all 0.6s ease-out';
                observer.observe(card);
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
    
    // معالجة أخطاء الصور
    document.addEventListener('error', (e) => {
        if (e.target.tagName === 'IMG' && e.target.closest('.product-categories')) {
            e.target.src = 'https://via.placeholder.com/130x130/977e2b/ffffff?text=صورة+غير+متوفرة';
            e.target.alt = 'صورة غير متوفرة';
        }
    }, true);
    
    // إتاحة الوصول للمكون من النطاق العام للتشخيص
    window.ProductsManager = {
        updateLanguage: handleLanguageChange,
        getCurrentLanguage: () => currentLanguage,
        isInitialized: () => isInitialized,
        reinit: initializeComponent
    };
    
    console.log('تم تحميل مكون المنتجات مع نظام الترجمة المركزي');
})();
</script>

<?php
// في حالة الوصول المباشر للملف
if (basename($_SERVER['PHP_SELF']) == 'products-section.php') {
    echo '<!DOCTYPE html>
    <html lang="ar" dir="rtl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>مكون قسم المنتجات مع الترجمة - ألفا الذهبية</title>
        <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200;300;400;500;600;700;800;900&family=Tajawal:wght@200;300;400;500;700;800;900&display=swap" rel="stylesheet">
    </head>
    <body class="products-standalone">
        <div class="demo-info">
            <h1>مكون قسم المنتجات مع نظام الترجمة</h1>
            <p>الآن يعرض الاسم العربي من حقل "الاسم" والاسم الإنجليزي من حقل "name"</p>
            <p><strong>الميزات:</strong></p>
            <ul style="text-align: right; max-width: 600px; margin: 0 auto;">
                <li>ترجمة العنوان والوصف</li>
                <li>عرض الأسماء العربية/الإنجليزية حسب اللغة</li>
                <li>متوافق مع النظام المركزي للترجمة</li>
                <li>لا توجد إعادة تهيئة عند تغيير اللغة</li>
            </ul>
        </div>
    ';
    
    echo '
    </body>
    </html>';
}
?>