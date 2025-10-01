<?php
// التحقق من وجود الثوابت قبل تعريفها لتجنب إعادة التعريف
if (!defined('NOCODB_TOKEN')) {
    define('NOCODB_TOKEN', 'fwVaKHr6zbDns5iW9u8annJUf5LCBJXjqPfujIpV');
}
if (!defined('NOCODB_API_URL')) {
    define('NOCODB_API_URL', 'https://app.nocodb.com/api/v2/tables/');
}

function fetchAllNocoDB($tableId) {
    $allData = [];
    $page = 1;
    $pageSize = 100;
    
    do {
        $offset = ($page - 1) * $pageSize;
        $url = NOCODB_API_URL . $tableId . "/records?limit=$pageSize&offset=$offset";
        
        $options = [
            'http' => [
                'header' => "xc-token: " . NOCODB_TOKEN . "\r\n",
                'method' => 'GET',
                'timeout' => 30
            ]
        ];
        
        $context = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);
        
        if ($result === FALSE) {
            error_log("Failed to fetch data from NocoDB: " . $url);
            break;
        }
        
        $data = json_decode($result, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON decode error: " . json_last_error_msg());
            break;
        }
        
        $pageData = isset($data['list']) ? $data['list'] : [];
        $pageInfo = isset($data['pageInfo']) ? $data['pageInfo'] : [];
        
        if (!empty($pageData)) {
            $allData = array_merge($allData, $pageData);
        }
        
        $page++;
        $hasMore = !empty($pageData) && count($pageData) == $pageSize;
        if (isset($pageInfo['isLastPage'])) {
            $hasMore = !$pageInfo['isLastPage'];
        }
        
    } while ($hasMore && $page <= 50);
    
    return $allData;
}

// جلب البيانات
$selectedCategoryId = isset($_GET['s']) ? intval($_GET['s']) : 0;
$allProductsData = fetchAllNocoDB('m4twrspf9oj7rvi');
$categoriesData = fetchAllNocoDB('m1g39mqv5mtdwad');

// تحديد المنتجات حسب القسم
$productsData = [];
$selectedCategoryName = 'جميع المنتجات';
$selectedCategoryNameEn = 'All Products';

if ($selectedCategoryId == 0) {
    $productsData = $allProductsData;
} else {
    foreach ($allProductsData as $product) {
        if (isset($product['معرف_القسم']) && intval($product['معرف_القسم']) == $selectedCategoryId) {
            $productsData[] = $product;
        }
    }
    // البحث عن اسم القسم
    foreach ($categoriesData as $category) {
        if (isset($category['معرف_القسم']) && intval($category['معرف_القسم']) == $selectedCategoryId) {
            $selectedCategoryName = $category['الاسم'] ?? '';
            $selectedCategoryNameEn = $category['name'] ?? '';
            break;
        }
    }
}
?>

<style>
:root {
    --gold: #D4AF37;
    --gold-hover: #B8941F;
    --dark-gray: #2C3E50;
    --medium-gray: #7F8C8D;
    --light-gray: #F8F9FA;
    --white: #FFFFFF;
    --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    --transition-normal: all 0.3s ease;
    --border-radius: 12px;
}

* {
    box-sizing: border-box;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

/* عنوان القسم */
.page-header {
    background: linear-gradient(135deg, var(--light-gray) 0%, var(--white) 100%);
    padding: 40px 0;
    text-align: center;
    border-bottom: 1px solid rgba(0,0,0,0.05);
}

.page-title {
    font-size: 2.5rem;
    font-weight: 800;
    color: var(--dark-gray);
    margin-bottom: 10px;
    line-height: 1.2;
}

.page-subtitle {
    color: var(--medium-gray);
    font-size: 1.1rem;
    margin: 0;
}

/* منطقة المنتجات */
.products-component {
    padding: 40px 0;
    background: var(--white);
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.product-card {
    background: var(--white);
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: var(--shadow-md);
    transition: var(--transition-normal);
    cursor: pointer;
    position: relative;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-xl);
}

.product-image {
    width: 100%;
    height: 220px;
    background: #fafafa;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    position: relative;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 15px;
    transition: var(--transition-normal);
}

.product-card:hover .product-image img {
    transform: scale(1.05);
}

/* اسم المنتج على الصورة */
.product-name-overlay {
    position: absolute;
    bottom: 10px;
    left: 10px;
    background: rgba(0, 0, 0, 0.75);
    color: var(--white);
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    line-height: 1.3;
    max-width: calc(100% - 20px);
    backdrop-filter: blur(5px);
    -webkit-backdrop-filter: blur(5px);
    transition: var(--transition-normal);
    z-index: 2;
}

.product-card:hover .product-name-overlay {
    background: rgba(212, 175, 55, 0.9);
    color: var(--white);
    transform: translateY(-2px);
}

.product-name-overlay .product-name {
    margin: 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
    word-break: break-word;
}

.load-more-btn {
    display: block;
    margin: 0 auto;
    padding: 15px 30px;
    background: var(--gold);
    color: var(--white);
    border: none;
    border-radius: 25px;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition-normal);
    font-size: 14px;
}

.load-more-btn:hover {
    background: var(--gold-hover);
    transform: translateY(-2px);
}

.no-products {
    text-align: center;
    padding: 80px 20px;
    color: var(--medium-gray);
}

.no-products i {
    font-size: 4rem;
    color: var(--gold);
    margin-bottom: 20px;
    display: block;
}

.no-products h3 {
    margin: 0 0 10px 0;
    font-size: 1.5rem;
    color: var(--dark-gray);
}

.no-products p {
    margin: 0;
    font-size: 1rem;
}

/* منطقة الأقسام الأخرى */
.categories-section {
    background: var(--light-gray);
    padding: 40px 0;
    margin-top: 40px;
}

.categories-title {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 30px;
    color: var(--dark-gray);
    text-align: center;
}

/* تحسين مظهر الكونتينر للإشارة لإمكانية التمرير */
.categories-container {
    position: relative;
    overflow: hidden;
}

.categories-container::before,
.categories-container::after {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    width: 30px;
    z-index: 5;
    pointer-events: none;
    transition: var(--transition-normal);
}

.categories-container::before {
    left: 0;
    background: linear-gradient(to left, transparent, rgba(248, 249, 250, 0.8));
}

.categories-container::after {
    right: 0;
    background: linear-gradient(to right, transparent, rgba(248, 249, 250, 0.8));
}

/* إخفاء التدرج على الشاشات الصغيرة */
@media (max-width: 768px) {
    .categories-container::before,
    .categories-container::after {
        display: none;
    }
}

/* إضافة تحسينات للتمرير على كروت الأقسام */
.categories-scroll {
    display: flex;
    gap: 15px;
    overflow-x: auto;
    scroll-behavior: smooth;
    padding: 10px 0;
    /* إخفاء شريط التمرير */
    scrollbar-width: none;
    -ms-overflow-style: none;
    /* تحسين التمرير للماوس والتتش */
    -webkit-overflow-scrolling: touch;
    overscroll-behavior-x: contain;
    /* منع التحديد العشوائي للنص أثناء التمرير */
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
    /* دعم السحب بالماوس */
    cursor: grab;
}

.categories-scroll:active {
    cursor: grabbing;
}

.categories-scroll::-webkit-scrollbar {
    display: none;
}

/* تحسين التمرير على الأجهزة اللوحية والهواتف */
.categories-scroll {
    scroll-snap-type: x proximity;
}

.category-card {
    scroll-snap-align: start;
    /* منع سحب الصور */
    -webkit-user-drag: none;
    -khtml-user-drag: none;
    -moz-user-drag: none;
    -o-user-drag: none;
    user-drag: none;
    /* منع التحديد أثناء السحب */
    pointer-events: auto;
}

/* تحسين تجربة السحب */
.category-card * {
    pointer-events: none;
}

.category-card {
    pointer-events: auto;
}

/* إضافة padding في البداية والنهاية لإظهار جزء من الكرت */
.categories-scroll::before,
.categories-scroll::after {
    content: '';
    flex-shrink: 0;
    width: 10px;
}

.category-card {
    background: var(--white);
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: var(--shadow-md);
    transition: var(--transition-normal);
    cursor: pointer;
    flex-shrink: 0;
    width: 140px;
    position: relative;
}

.category-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-xl);
}

.category-image {
    width: 100%;
    height: 120px;
    overflow: hidden;
    position: relative;
    background: #fafafa;
    display: flex;
    align-items: center;
    justify-content: center;
}

.category-image img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 10px;
    transition: var(--transition-normal);
}

.category-card:hover .category-image img {
    transform: scale(1.1);
}

.category-info {
    padding: 12px;
    text-align: center;
}

.category-name {
    font-size: 12px;
    font-weight: 600;
    color: var(--dark-gray);
    margin: 0;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
}

.category-card:hover .category-name {
    color: var(--gold);
}

/* أزرار التنقل في الأقسام */
.categories-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(255, 255, 255, 0.95);
    border: none;
    width: 45px;
    height: 45px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: var(--transition-normal);
    box-shadow: var(--shadow-lg);
    z-index: 10;
    opacity: 0.8;
    visibility: visible;
    color: var(--dark-gray);
    font-size: 16px;
}

.categories-nav:hover {
    background: var(--gold);
    color: var(--white);
    opacity: 1;
    transform: translateY(-50%) scale(1.1);
}

.categories-nav.prev {
    right: 10px;
}

.categories-nav.next {
    left: 10px;
}

/* إظهار الأزرار بشكل دائم على الشاشات الأكبر */
@media (min-width: 769px) {
    .categories-nav {
        opacity: 0.6;
    }
    
    .categories-container:hover .categories-nav {
        opacity: 0.8;
    }
    
    .categories-nav:hover {
        opacity: 1;
    }
}

/* إخفاء الأزرار على الشاشات الصغيرة لتحسين تجربة اللمس */
@media (max-width: 768px) {
    .categories-nav {
        display: none;
    }
}

/* مودال المنتج */
.product-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.9);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 2000;
    opacity: 0;
    visibility: hidden;
    transition: var(--transition-normal);
}

.product-modal.active {
    opacity: 1;
    visibility: visible;
}

.modal-content {
    background: var(--white);
    border-radius: 20px;
    max-width: 90vw;
    max-height: 90vh;
    width: 800px;
    position: relative;
    overflow: hidden;
    box-shadow: var(--shadow-xl);
}

.modal-close {
    position: absolute;
    top: 20px;
    left: 20px;
    background: rgba(0, 0, 0, 0.5);
    color: var(--white);
    border: none;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 10;
    transition: var(--transition-normal);
    font-size: 18px;
}

.modal-close:hover {
    background: rgba(0, 0, 0, 0.8);
}

.modal-image-container {
    position: relative;
    height: 400px;
    background: #fafafa;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.modal-image {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    transition: transform 0.3s ease;
    cursor: zoom-in;
}

.modal-image.zoomed {
    transform: scale(2);
    cursor: zoom-out;
}

.modal-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(0, 0, 0, 0.5);
    color: var(--white);
    border: none;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: var(--transition-normal);
    font-size: 20px;
}

.modal-nav:hover {
    background: rgba(0, 0, 0, 0.8);
}

.modal-prev {
    right: 20px;
}

.modal-next {
    left: 20px;
}

.modal-info {
    padding: 30px;
    text-align: center;
}

.modal-product-name {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--dark-gray);
    margin: 0;
    line-height: 1.3;
}

/* استجابة للشاشات الصغيرة */
@media (max-width: 768px) {
    .page-title {
        font-size: 2rem;
    }
    
    .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 15px;
    }
    
    .product-image {
        height: 180px;
    }

    .modal-content {
        width: 95vw;
        height: 90vh;
    }

    .modal-image-container {
        height: 60vh;
    }
    
    .category-card {
        width: 120px;
    }
    
    .category-image {
        height: 100px;
    }
    
    .categories-title {
        font-size: 1.5rem;
    }
    
    .product-name-overlay {
        font-size: 11px;
        padding: 5px 10px;
        bottom: 8px;
        left: 8px;
    }
}

@media (max-width: 480px) {
    .container {
        padding: 0 15px;
    }
    
    .page-header {
        padding: 30px 0;
    }
    
    .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 12px;
    }
    
    .product-image {
        height: 160px;
    }
    
    .category-card {
        width: 110px;
    }
    
    .category-image {
        height: 90px;
    }
    
    .product-name-overlay {
        font-size: 10px;
        padding: 4px 8px;
        bottom: 6px;
        left: 6px;
    }
}
</style>

<!-- عنوان القسم -->
<section class="page-header">
    <div class="container">
        <h1 class="page-title" id="categoryTitle" data-ar="<?php echo htmlspecialchars($selectedCategoryName); ?>" data-en="<?php echo htmlspecialchars($selectedCategoryNameEn); ?>"><?php echo htmlspecialchars($selectedCategoryName); ?></h1>
        <p class="page-subtitle" id="categoryDescription">
            استعرض مجموعتنا المتميزة من المنتجات عالية الجودة
        </p>
    </div>
</section>

<!-- المنتجات -->
<div class="products-component">
    <section class="products-section" id="productsSection">
        <div class="container">
            <?php if (empty($productsData)): ?>
                <div class="no-products">
                    <i class="fas fa-box-open"></i>
                    <h3 id="noProductsTitle">لا توجد منتجات متاحة</h3>
                    <p id="noProductsText">عذراً، لا توجد منتجات في هذا القسم حالياً</p>
                </div>
            <?php else: ?>
                <div class="products-grid" id="productsGrid">
                    <?php 
                    $displayCount = 12;
                    $currentProducts = array_slice($productsData, 0, $displayCount);
                    foreach ($currentProducts as $index => $product): ?>
                        <div class="product-card" onclick="openProductModal(<?php echo $index; ?>)">
                            <div class="product-image">
                                <img src="<?php echo htmlspecialchars($product['الصورة'] ?? '/placeholder.jpg'); ?>" 
                                     alt="<?php echo htmlspecialchars($product['الاسم'] ?? ''); ?>"
                                     loading="lazy"
                                     onerror="this.src='/placeholder.jpg'">
                                <div class="product-name-overlay">
                                    <h3 class="product-name"><?php echo htmlspecialchars($product['الاسم'] ?? ''); ?></h3>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (count($productsData) > $displayCount): ?>
                    <button class="load-more-btn" id="loadMoreBtn" onclick="loadMoreProducts()">
                        تحميل المزيد
                    </button>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- الأقسام الأخرى -->
    <section class="categories-section">
        <div class="container">
            <h2 class="categories-title" id="otherCategoriesTitle">تصفح أقسام أخرى</h2>
            
            <div class="categories-container">
                <button class="categories-nav prev" onclick="scrollCategories(1)" title="التالي">
                    <i class="fas fa-chevron-right"></i>
                </button>
                <button class="categories-nav next" onclick="scrollCategories(-1)" title="السابق">
                    <i class="fas fa-chevron-left"></i>
                </button>
                
                <div class="categories-scroll" id="categoriesScroll">
                    <?php 
                    // إضافة "جميع المنتجات" كأول عنصر
                    $allCategories = array_merge([
                        ['معرف_القسم' => 0, 'الاسم' => 'جميع المنتجات', 'name' => 'All Products', 'الصورة' => '/placeholder.jpg']
                    ], $categoriesData);
                    
                    foreach ($allCategories as $category): ?>
                        <div class="category-card" onclick="handleCategoryCardClick(<?php echo $category['معرف_القسم']; ?>, event)" data-ar-name="<?php echo htmlspecialchars($category['الاسم'] ?? ''); ?>" data-en-name="<?php echo htmlspecialchars($category['name'] ?? ''); ?>">
                            <div class="category-image">
                                <img src="<?php echo htmlspecialchars($category['الصورة'] ?? '/placeholder.jpg'); ?>" 
                                     alt="<?php echo htmlspecialchars($category['الاسم'] ?? ''); ?>"
                                     loading="lazy"
                                     onerror="this.src='/placeholder.jpg'">
                            </div>
                            <div class="category-info">
                                <h4 class="category-name"><?php echo htmlspecialchars($category['الاسم'] ?? ''); ?></h4>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- مودال المنتج -->
    <div class="product-modal" id="productModal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeProductModal()">
                <i class="fas fa-times"></i>
            </button>
            
            <div class="modal-image-container">
                <img class="modal-image" id="modalImage" src="" alt="" onclick="toggleZoom()">
                <button class="modal-nav modal-prev" onclick="navigateProduct(-1)">
                    <i class="fas fa-chevron-right"></i>
                </button>
                <button class="modal-nav modal-next" onclick="navigateProduct(1)">
                    <i class="fas fa-chevron-left"></i>
                </button>
            </div>
            
            <div class="modal-info">
                <h2 class="modal-product-name" id="modalProductName"></h2>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';
    
    let currentLanguage = 'ar'; // اللغة الحالية
    let isInitialized = false; // تجنب التهيئة المتكررة
    
    // ترجمات النصوص
    const translations = {
        ar: {
            categoryDescription: 'استعرض مجموعتنا المتميزة من المنتجات عالية الجودة',
            noProductsTitle: 'لا توجد منتجات متاحة',
            noProductsText: 'عذراً، لا توجد منتجات في هذا القسم حالياً',
            otherCategoriesTitle: 'تصفح أقسام أخرى',
            loadMoreBtn: 'تحميل المزيد'
        },
        en: {
            categoryDescription: 'Browse our distinguished collection of high-quality products',
            noProductsTitle: 'No Products Available',
            noProductsText: 'Sorry, there are no products in this category at the moment',
            otherCategoriesTitle: 'Browse Other Categories',
            loadMoreBtn: 'Load More'
        }
    };
    
    // تحديث النصوص حسب اللغة
    function updateTexts(language) {
        if (!translations[language]) return;
        
        // تحديث وصف القسم
        const categoryDesc = document.getElementById('categoryDescription');
        if (categoryDesc && translations[language].categoryDescription) {
            categoryDesc.textContent = translations[language].categoryDescription;
        }
        
        // تحديث رسائل عدم وجود منتجات
        const noProductsTitle = document.getElementById('noProductsTitle');
        if (noProductsTitle && translations[language].noProductsTitle) {
            noProductsTitle.textContent = translations[language].noProductsTitle;
        }
        
        const noProductsText = document.getElementById('noProductsText');
        if (noProductsText && translations[language].noProductsText) {
            noProductsText.textContent = translations[language].noProductsText;
        }
        
        // تحديث عنوان الأقسام الأخرى
        const otherCategoriesTitle = document.getElementById('otherCategoriesTitle');
        if (otherCategoriesTitle && translations[language].otherCategoriesTitle) {
            otherCategoriesTitle.textContent = translations[language].otherCategoriesTitle;
        }
        
        // تحديث زر تحميل المزيد
        const loadMoreBtn = document.getElementById('loadMoreBtn');
        if (loadMoreBtn && translations[language].loadMoreBtn) {
            loadMoreBtn.textContent = translations[language].loadMoreBtn;
        }
        
        // تحديث عنوان القسم
        const categoryTitle = document.getElementById('categoryTitle');
        if (categoryTitle) {
            if (language === 'ar') {
                const arName = categoryTitle.getAttribute('data-ar');
                if (arName) categoryTitle.textContent = arName;
            } else {
                const enName = categoryTitle.getAttribute('data-en');
                if (enName) categoryTitle.textContent = enName;
            }
        }
        
        // تحديث أسماء الأقسام
        updateCategoryNames(language);
    }
    
    // تحديث أسماء الأقسام
    function updateCategoryNames(language) {
        const categoryCards = document.querySelectorAll('.category-card');
        categoryCards.forEach(card => {
            const nameElement = card.querySelector('.category-name');
            if (nameElement) {
                if (language === 'ar') {
                    const arName = card.getAttribute('data-ar-name');
                    if (arName) nameElement.textContent = arName;
                } else {
                    const enName = card.getAttribute('data-en-name');
                    if (enName) nameElement.textContent = enName;
                }
            }
        });
    }
    
    // معالجة تغيير اللغة
    function handleLanguageChange(event) {
        const newLanguage = event.detail.language;
        console.log('صفحة المنتجات: تم استلام تغيير اللغة:', newLanguage);
        
        currentLanguage = newLanguage;
        updateTexts(newLanguage);
        
        console.log('تم تحديث صفحة المنتجات للغة:', newLanguage);
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
        updateTexts(currentLanguage);
        
        // تهيئة باقي وظائف الصفحة
        initializePageFunctions();
        
        isInitialized = true;
        console.log('تم تهيئة صفحة المنتجات - اللغة الحالية:', currentLanguage);
    }
    
    // تهيئة وظائف الصفحة الأساسية
    function initializePageFunctions() {
        // تحسين التمرير باستخدام عجلة الماوس والسحب
        initCategoriesScroll();
        
        // أحداث لوحة المفاتيح
        document.addEventListener('keydown', (e) => {
            const modal = document.getElementById('productModal');
            if (modal.classList.contains('active')) {
                switch(e.key) {
                    case 'Escape':
                        closeProductModal();
                        break;
                    case 'ArrowLeft':
                        navigateProduct(1);
                        break;
                    case 'ArrowRight':
                        navigateProduct(-1);
                        break;
                }
            }
        });

        // إغلاق المودال عند النقر خارجه
        document.getElementById('productModal').addEventListener('click', (e) => {
            if (e.target === e.currentTarget) {
                closeProductModal();
            }
        });
    }
    
    const allProducts = <?php echo json_encode($productsData, JSON_UNESCAPED_UNICODE); ?>;
    let displayedCount = 12;
    const loadIncrement = 12;
    let currentProductIndex = 0;
    let isZoomed = false;
    
    // تحميل المزيد من المنتجات
    function loadMoreProducts() {
        const productsGrid = document.getElementById('productsGrid');
        const loadMoreBtn = document.getElementById('loadMoreBtn');
        
        const nextProducts = allProducts.slice(displayedCount, displayedCount + loadIncrement);
        
        nextProducts.forEach((product, index) => {
            const productCard = document.createElement('div');
            productCard.className = 'product-card';
            productCard.onclick = () => openProductModal(displayedCount + index);
            productCard.innerHTML = `
                <div class="product-image">
                    <img src="${product['الصورة'] || '/placeholder.jpg'}" 
                         alt="${product['الاسم'] || ''}"
                         loading="lazy"
                         onerror="this.src='/placeholder.jpg'">
                    <div class="product-name-overlay">
                        <h3 class="product-name">${product['الاسم'] || ''}</h3>
                    </div>
                </div>
            `;
            productsGrid.appendChild(productCard);
        });
        
        displayedCount += loadIncrement;
        
        if (displayedCount >= allProducts.length) {
            loadMoreBtn.style.display = 'none';
        }
    }

    // فتح مودال المنتج
    function openProductModal(index) {
        currentProductIndex = index;
        const modal = document.getElementById('productModal');
        const modalImage = document.getElementById('modalImage');
        const modalProductName = document.getElementById('modalProductName');
        
        if (allProducts[index]) {
            modalImage.src = allProducts[index]['الصورة'] || '/placeholder.jpg';
            modalImage.alt = allProducts[index]['الاسم'] || '';
            modalProductName.textContent = allProducts[index]['الاسم'] || '';
            
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    // إغلاق مودال المنتج
    function closeProductModal() {
        const modal = document.getElementById('productModal');
        modal.classList.remove('active');
        document.body.style.overflow = '';
        isZoomed = false;
        document.getElementById('modalImage').classList.remove('zoomed');
    }

    // التنقل بين المنتجات في المودال
    function navigateProduct(direction) {
        const newIndex = currentProductIndex + direction;
        if (newIndex >= 0 && newIndex < allProducts.length) {
            openProductModal(newIndex);
        }
    }

    // تكبير الصورة في المودال
    function toggleZoom() {
        const modalImage = document.getElementById('modalImage');
        isZoomed = !isZoomed;
        modalImage.classList.toggle('zoomed', isZoomed);
    }

    // التمرير في الأقسام - مع إصلاح الاتجاه للعربية RTL
    function scrollCategories(direction) {
        const scroll = document.getElementById('categoriesScroll');
        const scrollAmount = 280; // تقليل المسافة قليلاً لتحكم أدق
        
        // في RTL: اليسار = للخلف (موجب)، اليمين = للأمام (سالب)
        const scrollValue = direction * scrollAmount;
        
        scroll.scrollBy({
            left: scrollValue,
            behavior: 'smooth'
        });
    }

    // معالجة النقر على الكروت مع دعم السحب
    function handleCategoryCardClick(categoryId, event) {
        // التحقق من أن هذا نقر حقيقي وليس سحب
        if (event.detail === 1) { // نقرة واحدة فقط
            setTimeout(() => {
                const scroll = document.getElementById('categoriesScroll');
                if (!scroll.isDragging) {
                    navigateToCategory(categoryId);
                }
            }, 50);
        }
    }

    // تحسين التمرير باستخدام عجلة الماوس والسحب
    function initCategoriesScroll() {
        const scroll = document.getElementById('categoriesScroll');
        
        // متغيرات للسحب
        let isMouseDown = false;
        let isDragging = false;
        let startX = 0;
        let scrollLeft = 0;
        let dragDistance = 0;

        // إضافة خاصية isDragging للعنصر
        scroll.isDragging = false;

        // دعم التمرير بعجلة الماوس (أفقي وعمودي)
        scroll.addEventListener('wheel', (e) => {
            e.preventDefault();
            
            // إذا كان التمرير أفقي، استخدمه مباشرة
            if (Math.abs(e.deltaX) > Math.abs(e.deltaY)) {
                scroll.scrollLeft += e.deltaX;
            } else {
                // تحويل التمرير العمودي إلى أفقي
                const delta = e.deltaY > 0 ? 1 : -1;
                scrollCategories(delta);
            }
        }, { passive: false });

        // دعم السحب بالماوس
        scroll.addEventListener('mousedown', (e) => {
            isMouseDown = true;
            isDragging = false;
            dragDistance = 0;
            scroll.style.cursor = 'grabbing';
            scroll.style.userSelect = 'none';
            startX = e.pageX - scroll.offsetLeft;
            scrollLeft = scroll.scrollLeft;
            
            // منع السلوك الافتراضي للروابط والنقرات أثناء السحب
            e.preventDefault();
        });

        scroll.addEventListener('mouseleave', () => {
            isMouseDown = false;
            isDragging = false;
            scroll.isDragging = false;
            scroll.style.cursor = 'grab';
        });

        scroll.addEventListener('mouseup', (e) => {
            isMouseDown = false;
            scroll.style.cursor = 'grab';
            scroll.style.userSelect = '';
            
            // تأخير قصير لتحديد ما إذا كان سحب أم نقر
            setTimeout(() => {
                scroll.isDragging = isDragging;
                if (!isDragging) {
                    // إذا لم يكن سحب، السماح بالنقر
                    scroll.isDragging = false;
                }
            }, 100);
            
            isDragging = false;
        });

        scroll.addEventListener('mousemove', (e) => {
            if (!isMouseDown) return;
            e.preventDefault();
            
            const x = e.pageX - scroll.offsetLeft;
            const walk = (x - startX) * 1.8; // تسريع التمرير قليلاً
            dragDistance += Math.abs(walk);
            
            // إذا تحرك أكثر من 5 بكسل، اعتبره سحب
            if (dragDistance > 5) {
                isDragging = true;
                scroll.isDragging = true;
            }
            
            scroll.scrollLeft = scrollLeft - walk;
        });

        // تحسين التمرير باللمس
        let isTouchScrolling = false;
        let touchStartX = 0;
        let touchScrollLeft = 0;
        let touchDragDistance = 0;

        scroll.addEventListener('touchstart', (e) => {
            isTouchScrolling = true;
            touchDragDistance = 0;
            touchStartX = e.touches[0].pageX - scroll.offsetLeft;
            touchScrollLeft = scroll.scrollLeft;
            scroll.isDragging = false;
        }, { passive: true });

        scroll.addEventListener('touchmove', (e) => {
            if (!isTouchScrolling) return;
            
            const x = e.touches[0].pageX - scroll.offsetLeft;
            const walk = (x - touchStartX) * 1.5;
            touchDragDistance += Math.abs(walk);
            
            if (touchDragDistance > 5) {
                scroll.isDragging = true;
            }
            
            scroll.scrollLeft = touchScrollLeft - walk;
        }, { passive: true });

        scroll.addEventListener('touchend', () => {
            isTouchScrolling = false;
            
            setTimeout(() => {
                if (touchDragDistance <= 5) {
                    scroll.isDragging = false;
                }
            }, 100);
        }, { passive: true });

        // تطبيق cursor grab في البداية
        scroll.style.cursor = 'grab';
    }

    // التنقل للقسم
    function navigateToCategory(categoryId) {
        if (categoryId === 0) {
            window.location.href = '/p';
        } else {
            window.location.href = `/p?s=${categoryId}`;
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

    // إضافة الوظائف للنطاق العام
    window.loadMoreProducts = loadMoreProducts;
    window.openProductModal = openProductModal;
    window.closeProductModal = closeProductModal;
    window.navigateProduct = navigateProduct;
    window.toggleZoom = toggleZoom;
    window.scrollCategories = scrollCategories;
    window.navigateToCategory = navigateToCategory;
    window.handleCategoryCardClick = handleCategoryCardClick;

    // إتاحة الوصول للمكون من النطاق العام للتشخيص
    window.ProductsPageManager = {
        updateLanguage: handleLanguageChange,
        getCurrentLanguage: () => currentLanguage,
        isInitialized: () => isInitialized,
        reinit: initializeComponent
    };

    console.log('تم تحميل صفحة المنتجات مع نظام الترجمة الكامل');
})();
</script>