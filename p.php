<?php
// تشغيل عرض الأخطاء للتشخيص (يمكن حذفها لاحقاً)
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// جلب جميع البيانات مرة واحدة
$allProductsData = fetchAllNocoDB('m4twrspf9oj7rvi');
$categoriesData = fetchAllNocoDB('m1g39mqv5mtdwad');

// تحديد القسم المطلوب من URL (للربط المباشر فقط)
$selectedCategoryId = isset($_GET['s']) ? intval($_GET['s']) : 0;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>شركة ألفا الذهبية للمصاعد والمقاولات</title>
    
    <!-- تحميل الخطوط العربية -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200;300;400;500;600;700;800;900&family=Tajawal:wght@200;300;400;500;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Swiper CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
    
    <style>
        :root {
            --gold: #D4AF37;
            --gold-hover: #B8941F;
            --gold-light: rgba(212, 175, 55, 0.1);
            --dark-gray: #2C3E50;
            --medium-gray: #7F8C8D;
            --light-gray: #F8F9FA;
            --white: #FFFFFF;
            --overlay: rgba(0, 0, 0, 0.95);
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --shadow-strong: 0 20px 60px rgba(0, 0, 0, 0.5);
            --transition-normal: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-fast: all 0.15s ease;
            --transition-smooth: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            --border-radius: 12px;
            --border-radius-lg: 16px;
            --border-radius-xl: 24px;
        }

        /* CSS Reset وإزالة الحواف البيضاء */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html, body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            overflow-x: hidden;
            font-family: 'Cairo', 'Tajawal', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #fff;
        }
        
        /* إزالة الحواف من جميع العناصر */
        body {
            margin: 0 !important;
            padding: 0 !important;
            border: none !important;
        }
        
        /* المحتوى الرئيسي مع المسافة العلوية المناسبة للهيدر */
        main {
            margin: 0;
            padding: 0;
            padding-top: 120px; /* نفس ارتفاع الهيدر للشاشات العادية */
        }
        
        /* للشاشات الأصغر من 768px - ارتفاع الهيدر 70px */
        @media (max-width: 768px) {
            main {
                padding-top: 70px;
            }
        }
        
        /* للشاشات الأصغر من 480px - ارتفاع الهيدر 60px */
        @media (max-width: 480px) {
            main {
                padding-top: 60px;
            }
        }
        
        /* تحسين عرض النصوص العربية */
        body {
            font-feature-settings: "kern" 1;
            text-rendering: optimizeLegibility;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
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
            transition: var(--transition-normal);
        }

        .page-subtitle {
            color: var(--medium-gray);
            font-size: 1.1rem;
            margin: 0;
        }

        /* شريط الأقسام مع Swiper محسن */
        .categories-filter {
            background: var(--white);
            padding: 20px 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            position: sticky;
            top: 80px;
            z-index: 100;
            box-shadow: var(--shadow-sm);
        }

        .categories-swiper {
            padding: 10px 0;
            overflow: hidden;
            position: relative;
        }

        .categories-swiper .swiper-wrapper {
            align-items: center;
        }

        .categories-swiper .swiper-slide {
            width: auto;
            height: auto;
            flex-shrink: 0;
        }

        .category-filter-btn {
            background: var(--white);
            border: 2px solid var(--light-gray);
            border-radius: 25px;
            padding: 12px 24px;
            font-size: 14px;
            font-weight: 600;
            color: var(--dark-gray);
            cursor: pointer;
            transition: var(--transition-normal);
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 8px;
            min-height: 48px;
            position: relative;
            overflow: hidden;
        }

        .category-filter-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }

        .category-filter-btn:hover::before {
            left: 100%;
        }

        .category-filter-btn:hover {
            border-color: var(--gold);
            background: var(--gold-light);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .category-filter-btn.active {
            background: var(--gold);
            border-color: var(--gold);
            color: var(--white);
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
        }

        .category-filter-btn .count {
            background: rgba(0,0,0,0.1);
            border-radius: 12px;
            padding: 2px 8px;
            font-size: 12px;
            margin-right: 5px;
            transition: var(--transition-normal);
        }

        .category-filter-btn.active .count {
            background: rgba(255,255,255,0.2);
        }

        /* تخصيص Swiper للأقسام - محسن */
        .categories-swiper .swiper-button-next,
        .categories-swiper .swiper-button-prev {
            background: var(--white);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            margin-top: -20px;
            box-shadow: var(--shadow-md);
            border: 1px solid rgba(0,0,0,0.1);
            transition: var(--transition-normal);
            top: 50%;
            z-index: 10;
        }

        .categories-swiper .swiper-button-next {
            left: 10px;
        }

        .categories-swiper .swiper-button-prev {
            right: 10px;
        }

        .categories-swiper .swiper-button-next:hover,
        .categories-swiper .swiper-button-prev:hover {
            background: var(--gold);
            transform: scale(1.1);
        }

        .categories-swiper .swiper-button-next::after,
        .categories-swiper .swiper-button-prev::after {
            font-size: 14px;
            font-weight: bold;
            color: var(--dark-gray);
        }

        .categories-swiper .swiper-button-next:hover::after,
        .categories-swiper .swiper-button-prev:hover::after {
            color: var(--white);
        }

        /* إخفاء أزرار التنقل على الشاشات الصغيرة */
        @media (max-width: 768px) {
            .categories-swiper .swiper-button-next,
            .categories-swiper .swiper-button-prev {
                display: none;
            }
        }

        /* منطقة المنتجات */
        .products-component {
            padding: 40px 0;
            background: var(--white);
            min-height: 60vh;
        }

        /* إضافة مؤشر التحميل */
        .loading-indicator {
            text-align: center;
            padding: 40px;
            color: var(--medium-gray);
            display: none;
        }

        .loading-indicator.active {
            display: block;
        }

        .loading-indicator i {
            font-size: 2rem;
            margin-bottom: 10px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .products-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .products-info {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .products-count {
            color: var(--medium-gray);
            font-size: 14px;
            background: var(--light-gray);
            padding: 8px 15px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: var(--transition-normal);
        }

        .search-box {
            display: flex;
            align-items: center;
            background: var(--light-gray);
            border-radius: 25px;
            padding: 8px 15px;
            min-width: 250px;
            transition: var(--transition-normal);
            border: 2px solid transparent;
        }

        .search-box:focus-within {
            background: var(--white);
            box-shadow: var(--shadow-md);
            border-color: var(--gold-light);
        }

        .search-box input {
            border: none;
            background: transparent;
            outline: none;
            flex: 1;
            padding: 5px;
            font-size: 14px;
            color: var(--dark-gray);
        }

        .search-box input::placeholder {
            color: var(--medium-gray);
        }

        .search-box i {
            color: var(--medium-gray);
            margin-left: 5px;
            transition: var(--transition-normal);
        }

        .search-box:focus-within i {
            color: var(--gold);
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
            opacity: 1;
            transition: opacity var(--transition-normal);
        }

        .products-grid.filtering {
            opacity: 0.6;
        }

        .product-card {
            background: var(--white);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow-md);
            transition: var(--transition-normal);
            cursor: pointer;
            position: relative;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.6s ease forwards;
            border: 1px solid transparent;
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-xl);
            border-color: var(--gold-light);
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .product-image {
            width: 100%;
            height: 200px;
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
            opacity: 0;
        }

        .product-image img.loaded {
            opacity: 1;
        }

        .product-card:hover .product-image img {
            transform: scale(1.05);
        }

        .product-image .image-placeholder {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: var(--medium-gray);
            font-size: 2rem;
        }

        /* اسم المنتج على الصورة - التصميم الجديد */
        .product-name-overlay {
            position: absolute;
            bottom: 8px;
            left: 8px;
            background: rgba(0, 0, 0, 0.75);
            color: var(--white);
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            line-height: 1.2;
            max-width: calc(100% - 16px);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: var(--transition-normal);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
            z-index: 2;
        }

        .product-card:hover .product-name-overlay {
            background: rgba(212, 175, 55, 0.9);
            border-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
        }

        /* إزالة الحاوية السفلى للاسم لأننا نعرضه على الصورة الآن */
        .product-info {
            display: none;
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
            position: relative;
            overflow: hidden;
        }

        .load-more-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }

        .load-more-btn:hover::before {
            left: 100%;
        }

        .load-more-btn:hover {
            background: var(--gold-hover);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .load-more-btn:disabled {
            background: var(--medium-gray);
            cursor: not-allowed;
            transform: none;
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
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
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

        /* ==== قسم الأقسام الأخرى المحسن ==== */
        .other-categories-section {
            background: var(--light-gray);
            padding: 60px 0;
            margin-top: 40px;
        }

        .section-title {
            text-align: center;
            margin-bottom: 50px;
        }

        .section-title h2 {
            font-size: 2.5rem;
            color: var(--dark-gray);
            font-weight: 900;
            position: relative;
            display: inline-block;
            padding-bottom: 20px;
            margin-bottom: 15px;
        }

        .section-title h2::after {
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
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.7;
        }

        .other-categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
        }

        .other-category-card {
            background: var(--white);
            border-radius: var(--border-radius-xl);
            padding: 25px;
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
            min-height: 280px;
            text-decoration: none;
            color: inherit;
            opacity: 0;
            transform: translateY(30px);
            animation: fadeInUpOther 0.8s ease forwards;
        }

        @keyframes fadeInUpOther {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .other-category-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(212, 175, 55, 0.1) 0%, transparent 70%);
            opacity: 0;
            transition: var(--transition-smooth);
            transform: scale(0);
        }

        .other-category-card:hover::before {
            opacity: 1;
            transform: scale(1);
        }

        .other-category-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-xl);
            border-color: rgba(212, 175, 55, 0.2);
        }

        .other-category-image {
            width: 120px;
            height: 120px;
            margin: 0 auto 20px;
            border-radius: 50%;
            overflow: hidden;
            background: #fafafa;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            transition: var(--transition-normal);
            border: 3px solid transparent;
        }

        .other-category-card:hover .other-category-image {
            transform: scale(1.05);
            border-color: var(--gold-light);
        }

        .other-category-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 15px;
            transition: var(--transition-normal);
        }

        .other-category-image .image-placeholder {
            color: var(--medium-gray);
            font-size: 2.5rem;
        }

        .other-category-name {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--dark-gray);
            margin: 0 0 15px 0;
            line-height: 1.3;
            transition: var(--transition-normal);
            position: relative;
            z-index: 1;
        }

        .other-category-card:hover .other-category-name {
            color: var(--gold);
        }

        .other-category-count {
            color: var(--medium-gray);
            font-size: 14px;
            margin: 0;
            background: var(--light-gray);
            padding: 10px 20px;
            border-radius: 25px;
            display: inline-block;
            transition: var(--transition-normal);
            position: relative;
            z-index: 1;
            font-weight: 600;
        }

        .other-category-card:hover .other-category-count {
            background: var(--gold-light);
            color: var(--gold);
        }

        /* تأثير hover للصورة المعلقة - للشريط العلوي فقط */
        .hover-image-preview {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 300px;
            height: 300px;
            background: var(--white);
            border-radius: var(--border-radius-xl);
            box-shadow: var(--shadow-strong);
            opacity: 0;
            visibility: hidden;
            transition: var(--transition-smooth);
            z-index: 1000;
            pointer-events: none;
            border: 3px solid var(--gold);
            overflow: hidden;
        }

        .hover-image-preview.active {
            opacity: 1;
            visibility: visible;
        }

        .hover-image-preview img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 20px;
        }

        /* مودال المنتج محسن */
        .product-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--overlay);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition-smooth);
            backdrop-filter: blur(10px);
        }

        .product-modal.active {
            opacity: 1;
            visibility: visible;
        }

        .modal-content {
            background: var(--white);
            border-radius: var(--border-radius-xl);
            max-width: 90vw;
            max-height: 90vh;
            width: 800px;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-strong);
            transform: scale(0.9);
            transition: var(--transition-smooth);
        }

        .product-modal.active .modal-content {
            transform: scale(1);
        }

        .modal-close {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(0, 0, 0, 0.5);
            color: var(--white);
            border: none;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10;
            transition: var(--transition-normal);
            font-size: 18px;
            backdrop-filter: blur(10px);
        }

        .modal-close:hover {
            background: rgba(0, 0, 0, 0.8);
            transform: scale(1.1);
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
            backdrop-filter: blur(10px);
        }

        .modal-nav:hover {
            background: rgba(0, 0, 0, 0.8);
            transform: translateY(-50%) scale(1.1);
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
                height: 150px;
            }

            .product-name-overlay {
                font-size: 11px;
                padding: 5px 10px;
                bottom: 6px;
                left: 6px;
            }

            .modal-content {
                width: 95vw;
                height: 90vh;
            }

            .modal-image-container {
                height: 60vh;
            }
            
            .search-box {
                min-width: 200px;
            }

            .products-header {
                flex-direction: column;
                align-items: stretch;
            }

            .categories-filter {
                top: 70px;
            }

            .other-categories-grid {
                grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
                gap: 20px;
            }

            .other-category-card {
                min-height: 240px;
                padding: 20px;
            }

            .other-category-image {
                width: 100px;
                height: 100px;
            }

            .section-title h2 {
                font-size: 2rem;
            }

            .hover-image-preview {
                display: none;
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
                height: 130px;
            }

            .product-name-overlay {
                font-size: 10px;
                padding: 4px 8px;
                bottom: 4px;
                left: 4px;
            }

            .search-box {
                min-width: 100%;
            }

            .categories-filter {
                top: 60px;
            }

            .other-categories-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .other-category-card {
                min-height: 220px;
                padding: 15px;
            }

            .other-category-image {
                width: 80px;
                height: 80px;
            }

            .section-title h2 {
                font-size: 1.5rem;
            }

            .other-category-name {
                font-size: 1.1rem;
            }
        }

        /* تحسينات للأداء */
        .fade-transition {
            transition: opacity 0.3s ease;
        }

        .lazy-loading {
            opacity: 0.6;
        }

        /* إضافة أنيميشن للعد */
        .count-animation {
            animation: countPulse 0.3s ease;
        }

        @keyframes countPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
    </style>
</head>

<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=AW-11417531842"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'AW-11417531842');
</script>



<body>
    <?php include 'components/loader.php'; ?>
    <?php include 'components/header.php'; ?>

    <main role="main">
        <!-- عنوان القسم -->
        <section class="page-header">
            <div class="container">
                <h1 class="page-title" id="pageTitle">جميع المنتجات</h1>
                <p class="page-subtitle" id="pageSubtitle">
                    استعرض مجموعتنا المتميزة من المنتجات عالية الجودة
                </p>
            </div>
        </section>

        <!-- شريط الأقسام مع Swiper -->
        <div class="categories-filter">
            <div class="container">
                <div class="swiper categories-swiper">
                    <div class="swiper-wrapper" id="categoriesWrapper">
                        <!-- سيتم ملؤها بواسطة JavaScript -->
                    </div>
                    <div class="swiper-button-next"></div>
                    <div class="swiper-button-prev"></div>
                </div>
            </div>
        </div>

        <!-- المنتجات -->
        <div class="products-component">
            <section class="products-section" id="productsSection">
                <div class="container">
                    <!-- رأس المنتجات -->
                    <div class="products-header">
                        <div class="products-info">
                            <div class="products-count" id="productsCount">
                                <i class="fas fa-box"></i>
                                <span id="loadingText">جاري التحميل...</span>
                            </div>
                        </div>
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="searchInput" placeholder="البحث في المنتجات...">
                        </div>
                    </div>

                    <!-- مؤشر التحميل -->
                    <div class="loading-indicator" id="loadingIndicator">
                        <i class="fas fa-spinner"></i>
                        <p id="loadingMessage">جاري تحميل المنتجات...</p>
                    </div>

                    <!-- شبكة المنتجات -->
                    <div class="products-grid" id="productsGrid">
                        <!-- سيتم ملؤها بواسطة JavaScript -->
                    </div>

                    <!-- زر تحميل المزيد -->
                    <button class="load-more-btn" id="loadMoreBtn" style="display: none;">
                        <span id="loadMoreText">تحميل المزيد</span>
                    </button>

                    <!-- رسالة عدم وجود منتجات -->
                    <div class="no-products" id="noProducts" style="display: none;">
                        <i class="fas fa-search"></i>
                        <h3 id="noResultsTitle">لا توجد نتائج</h3>
                        <p id="noResultsMessage">لم يتم العثور على منتجات تطابق البحث أو الفلتر المحدد</p>
                    </div>
                </div>
            </section>

            <!-- قسم الأقسام الأخرى المحسن - مع الصور -->
            <section class="other-categories-section">
                <div class="container">
                    <div class="section-title">
                        <h2 id="otherCategoriesTitle">تصفح أقسام أخرى</h2>
                        <p id="otherCategoriesDescription">استكشف المزيد من منتجاتنا المتنوعة في جميع الأقسام</p>
                    </div>
                    
                    <div class="other-categories-grid" id="otherCategoriesGrid">
                        <!-- سيتم ملؤها بواسطة JavaScript -->
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

            <!-- معاينة الصورة عند التأشير -->
            <div class="hover-image-preview" id="hoverImagePreview">
                <img src="" alt="">
            </div>
        </div>

        <?php include 'components/image-gallery.php'; ?>
    </main>
    
    <?php include 'components/footer.php'; ?>
    <?php include 'components/whatsapp-button.php'; ?>

    <!-- Swiper JS -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<script>
(function() {
    'use strict';
    
    let currentLanguage = 'ar'; // اللغة الحالية
    let isInitialized = false; // تجنب التهيئة المتكررة
    
    // ترجمات شاملة لجميع النصوص
    const translations = {
        ar: {
            // العناوين الرئيسية
            allProducts: 'جميع المنتجات',
            pageSubtitle: 'استعرض مجموعتنا المتميزة من المنتجات عالية الجودة',
            
            // النصوص التفاعلية
            loadingText: 'جاري التحميل...',
            loadingMessage: 'جاري تحميل المنتجات...',
            loadMoreText: 'تحميل المزيد',
            searchPlaceholder: 'البحث في المنتجات...',
            
            // رسائل عدم وجود منتجات
            noResultsTitle: 'لا توجد نتائج',
            noResultsMessage: 'لم يتم العثور على منتجات تطابق البحث أو الفلتر المحدد',
            
            // قسم الأقسام الأخرى
            otherCategoriesTitle: 'تصفح أقسام أخرى',
            otherCategoriesDescription: 'استكشف المزيد من منتجاتنا المتنوعة في جميع الأقسام',
            
            // عدادات وتسميات
            productCount: 'منتج',
            productsCount: 'منتجات',
            searchResults: 'نتائج البحث عن'
        },
        en: {
            // العناوين الرئيسية
            allProducts: 'All Products',
            pageSubtitle: 'Explore our distinguished collection of high-quality products',
            
            // النصوص التفاعلية
            loadingText: 'Loading...',
            loadingMessage: 'Loading products...',
            loadMoreText: 'Load More',
            searchPlaceholder: 'Search products...',
            
            // رسائل عدم وجود منتجات
            noResultsTitle: 'No Results',
            noResultsMessage: 'No products found matching the search or selected filter',
            
            // قسم الأقسام الأخرى
            otherCategoriesTitle: 'Browse Other Categories',
            otherCategoriesDescription: 'Explore more of our diverse products in all categories',
            
            // عدادات وتسميات
            productCount: 'product',
            productsCount: 'products',
            searchResults: 'Search results for'
        }
    };
    
    const STATE = {
        allProducts: <?php echo json_encode($allProductsData, JSON_UNESCAPED_UNICODE); ?>,
        allCategories: <?php echo json_encode($categoriesData, JSON_UNESCAPED_UNICODE); ?>,
        filteredProducts: [],
        displayedProducts: [],
        currentCategoryId: <?php echo $selectedCategoryId; ?>,
        searchTerm: '',
        displayedCount: 0,
        currentProductIndex: 0,
        isZoomed: false,
        isTransitioning: false,
        loadIncrement: 12
    };

    const COMPONENTS = {
        categoriesSwiper: null,
        searchTimeout: null,
        transitionTimeout: null
    };

    const SELECTORS = {
        categoriesWrapper: '#categoriesWrapper',
        productsGrid: '#productsGrid',
        productsCount: '#productsCount',
        searchInput: '#searchInput',
        loadMoreBtn: '#loadMoreBtn',
        noProducts: '#noProducts',
        loadingIndicator: '#loadingIndicator',
        pageTitle: '#pageTitle',
        pageSubtitle: '#pageSubtitle',
        productModal: '#productModal',
        modalImage: '#modalImage',
        modalProductName: '#modalProductName',
        otherCategoriesGrid: '#otherCategoriesGrid',
        otherCategoriesSection: '.other-categories-section',
        hoverImagePreview: '#hoverImagePreview'
    };

    const ELEMENTS = {};

    const CONFIG = {
        TRANSITION_DURATION: 400,
        SEARCH_DELAY: 300,
        ANIMATION_STAGGER: 100,
        SWIPER_SETTINGS: {
            slidesPerView: 'auto',
            spaceBetween: 15,
            freeMode: {
                enabled: true,
                sticky: false,
                momentum: true,
                momentumRatio: 0.25,
                momentumVelocityRatio: 0.25
            },
            mousewheel: {
                forceToAxis: true,
                sensitivity: 1,
                releaseOnEdges: true
            },
            navigation: {
                nextEl: '.categories-swiper .swiper-button-next',
                prevEl: '.categories-swiper .swiper-button-prev',
            },
            breakpoints: {
                320: { spaceBetween: 10, slidesPerView: 'auto' },
                768: { spaceBetween: 15, slidesPerView: 'auto' }
            },
            watchOverflow: true,
            observer: true,
            observeParents: true
        }
    };

    // تحديث النصوص حسب اللغة
    function updateTexts(language) {
        if (!translations[language]) return;
        
        const textMappings = [
            { id: 'pageSubtitle', key: 'pageSubtitle' },
            { id: 'loadingText', key: 'loadingText' },
            { id: 'loadingMessage', key: 'loadingMessage' },
            { id: 'loadMoreText', key: 'loadMoreText' },
            { id: 'noResultsTitle', key: 'noResultsTitle' },
            { id: 'noResultsMessage', key: 'noResultsMessage' },
            { id: 'otherCategoriesTitle', key: 'otherCategoriesTitle' },
            { id: 'otherCategoriesDescription', key: 'otherCategoriesDescription' }
        ];
        
        textMappings.forEach(mapping => {
            const element = document.getElementById(mapping.id);
            if (element && translations[language][mapping.key]) {
                element.textContent = translations[language][mapping.key];
            }
        });
        
        // تحديث placeholder للبحث
        const searchInput = document.getElementById('searchInput');
        if (searchInput && translations[language].searchPlaceholder) {
            searchInput.placeholder = translations[language].searchPlaceholder;
        }
    }
    
    // الحصول على اسم الفئة حسب اللغة
    function getCategoryName(category, language) {
        if (language === 'en' && category.name) {
            return category.name;
        }
        return category.الاسم || category.name || '';
    }
    
    // معالجة تغيير اللغة
    function handleLanguageChange(event) {
        const newLanguage = event.detail.language;
        console.log('صفحة المنتجات: تم استلام تغيير اللغة:', newLanguage);
        
        currentLanguage = newLanguage;
        
        // تحديث جميع النصوص
        updateTexts(newLanguage);
        
        // إعادة رسم الفئات بالأسماء الجديدة
        ProductManager.renderCategories();
        ProductManager.renderOtherCategories();
        
        // تحديث عنوان الصفحة
        ProductManager.updatePageTitle();
        
        // تحديث عدد المنتجات
        ProductManager.updateProductsCount();
        
        // إعادة تهيئة Swiper إذا لزم الأمر
        if (COMPONENTS.categoriesSwiper) {
            COMPONENTS.categoriesSwiper.update();
        }
        
        console.log('تم تحديث صفحة المنتجات للغة:', newLanguage);
    }

    class ProductManager {
        static init() {
            this.cacheElements();
            this.initializeLanguage();
            this.showLoading();
            this.renderCategories();
            this.renderOtherCategories();
            this.initSwiper();
            this.setupEventListeners();
            this.filterProducts();
            this.updateURL();
            this.hideLoading();
            isInitialized = true;
        }
        
        static initializeLanguage() {
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
        }

        static cacheElements() {
            Object.keys(SELECTORS).forEach(key => {
                ELEMENTS[key] = document.querySelector(SELECTORS[key]);
            });
        }

        static showLoading() {
            ELEMENTS.loadingIndicator?.classList.add('active');
            ELEMENTS.productsGrid?.classList.add('filtering');
        }

        static hideLoading() {
            ELEMENTS.loadingIndicator?.classList.remove('active');
            ELEMENTS.productsGrid?.classList.remove('filtering');
        }

        static renderCategories() {
            const allCategoriesList = [
                { معرف_القسم: 0, الاسم: 'جميع المنتجات', name: 'All Products', الصورة: '' },
                ...STATE.allCategories
            ];

            if (!ELEMENTS.categoriesWrapper) return;

            ELEMENTS.categoriesWrapper.innerHTML = allCategoriesList.map(category => {
                const categoryId = parseInt(category.معرف_القسم);
                const count = categoryId === 0 ? STATE.allProducts.length : 
                    STATE.allProducts.filter(p => parseInt(p.معرف_القسم) === categoryId).length;
                
                const categoryName = getCategoryName(category, currentLanguage);
                
                return `
                    <div class="swiper-slide">
                        <button class="category-filter-btn ${categoryId === STATE.currentCategoryId ? 'active' : ''}" 
                                data-category-id="${categoryId}"
                                data-category-image="${category.الصورة || ''}"
                                onmouseenter="ProductManager.showHoverImage(this)"
                                onmouseleave="ProductManager.hideHoverImage()">
                            ${categoryName}
                            <span class="count">${count}</span>
                        </button>
                    </div>
                `;
            }).join('');
        }

        static renderOtherCategories() {
            if (!ELEMENTS.otherCategoriesGrid) return;

            ELEMENTS.otherCategoriesGrid.innerHTML = STATE.allCategories.map((category, index) => {
                const categoryId = parseInt(category.معرف_القسم);
                const count = STATE.allProducts.filter(p => parseInt(p.معرف_القسم) === categoryId).length;
                const categoryName = getCategoryName(category, currentLanguage);
                const countText = currentLanguage === 'ar' ? 'منتج' : 'product';
                
                return `
                    <div class="other-category-card" 
                         data-category-id="${categoryId}"
                         style="animation-delay: ${index * 0.1}s">
                        <div class="other-category-image">
                            ${category.الصورة ? 
                                `<img src="${category.الصورة}" 
                                     alt="${categoryName}" 
                                     loading="lazy"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                                 <div class="image-placeholder" style="display: none;">
                                     <i class="fas fa-image"></i>
                                 </div>` :
                                `<div class="image-placeholder">
                                     <i class="fas fa-image"></i>
                                 </div>`
                            }
                        </div>
                        <h3 class="other-category-name">${categoryName}</h3>
                        <p class="other-category-count">${count} ${countText}</p>
                    </div>
                `;
            }).join('');
        }

        static initSwiper() {
            if (typeof Swiper !== 'undefined' && ELEMENTS.categoriesWrapper) {
                COMPONENTS.categoriesSwiper = new Swiper('.categories-swiper', CONFIG.SWIPER_SETTINGS);
            }
        }

        static showHoverImage(button) {
            if (window.innerWidth <= 768 || !ELEMENTS.hoverImagePreview) return;
            
            const imageUrl = button.dataset.categoryImage;
            if (!imageUrl) return;
            
            const img = ELEMENTS.hoverImagePreview.querySelector('img');
            if (img) {
                img.src = imageUrl;
                ELEMENTS.hoverImagePreview.classList.add('active');
            }
        }

        static hideHoverImage() {
            ELEMENTS.hoverImagePreview?.classList.remove('active');
        }

        static async hideOtherCategoriesTemporarily() {
            const section = document.querySelector(SELECTORS.otherCategoriesSection);
            if (!section || STATE.isTransitioning) return;

            STATE.isTransitioning = true;
            const originalHeight = section.offsetHeight;
            const scrollPosition = window.pageYOffset;
            
            section.style.transition = 'opacity 0.3s ease, visibility 0.3s ease';
            section.style.opacity = '0';
            section.style.visibility = 'hidden';
            
            await this.delay(CONFIG.TRANSITION_DURATION);
            
            section.style.height = '0px';
            section.style.overflow = 'hidden';
            
            return { originalHeight, scrollPosition };
        }

        static async showOtherCategories(originalData = {}) {
            const section = document.querySelector(SELECTORS.otherCategoriesSection);
            if (!section) return;

            const { scrollPosition } = originalData;
            
            section.style.height = '';
            section.style.overflow = '';
            section.style.opacity = '1';
            section.style.visibility = 'visible';
            
            if (scrollPosition !== undefined) {
                window.scrollTo({ top: scrollPosition, behavior: 'instant' });
            }
            
            await this.delay(CONFIG.TRANSITION_DURATION);
            
            section.style.transition = '';
            STATE.isTransitioning = false;
        }

        static async changeCategory(categoryId, isFromOtherCategories = false) {
            if (categoryId === STATE.currentCategoryId || STATE.isTransitioning) return;

            let originalData = null;
            
            if (isFromOtherCategories) {
                originalData = await this.hideOtherCategoriesTemporarily();
            }

            STATE.currentCategoryId = categoryId;
            this.clearProductsGrid();
            await this.filterProducts();
            this.updateURL();
            this.scrollToActiveCategory();

            if (isFromOtherCategories && originalData) {
                await this.delay(2000);
                await this.showOtherCategories(originalData);
            }
        }

        static async filterProducts() {
            this.showLoading();
            
            await this.delay(150);

            if (STATE.currentCategoryId === 0) {
                STATE.filteredProducts = [...STATE.allProducts];
            } else {
                STATE.filteredProducts = STATE.allProducts.filter(product => 
                    parseInt(product.معرف_القسم) === STATE.currentCategoryId
                );
            }

            if (STATE.searchTerm.trim()) {
                const searchTermLower = STATE.searchTerm.toLowerCase();
                STATE.filteredProducts = STATE.filteredProducts.filter(product =>
                    (product.الاسم || '').toLowerCase().includes(searchTermLower)
                );
            }

            STATE.displayedCount = 0;
            STATE.displayedProducts = [];
            
            this.updatePageTitle();
            this.updateProductsCount();
            this.loadMoreProducts();
            this.updateCategoryButtons();
            this.hideLoading();
        }

        static updatePageTitle() {
            if (!ELEMENTS.pageTitle) return;

            let title = translations[currentLanguage].allProducts;
            
            if (STATE.currentCategoryId !== 0) {
                const category = STATE.allCategories.find(c => 
                    parseInt(c.معرف_القسم) === STATE.currentCategoryId
                );
                if (category) {
                    title = getCategoryName(category, currentLanguage);
                }
            }
            
            if (STATE.searchTerm.trim()) {
                title += ` - ${translations[currentLanguage].searchResults} "${STATE.searchTerm}"`;
            }
            
            ELEMENTS.pageTitle.textContent = title;
        }

        static updateProductsCount() {
            if (!ELEMENTS.productsCount) return;

            const count = STATE.filteredProducts.length;
            const productText = count === 1 ? 
                translations[currentLanguage].productCount : 
                translations[currentLanguage].productsCount;
            
            ELEMENTS.productsCount.innerHTML = `
                <i class="fas fa-box"></i>
                <span>${count} ${productText}</span>
            `;
            ELEMENTS.productsCount.classList.add('count-animation');
            setTimeout(() => ELEMENTS.productsCount.classList.remove('count-animation'), 300);
        }

        static updateCategoryButtons() {
            document.querySelectorAll('.category-filter-btn').forEach(btn => {
                const categoryId = parseInt(btn.dataset.categoryId);
                btn.classList.toggle('active', categoryId === STATE.currentCategoryId);
            });
        }

        static loadMoreProducts() {
            const nextProducts = STATE.filteredProducts.slice(
                STATE.displayedCount, 
                STATE.displayedCount + STATE.loadIncrement
            );
            
            if (nextProducts.length === 0) {
                this.toggleElement(ELEMENTS.loadMoreBtn, false);
                this.toggleElement(ELEMENTS.noProducts, STATE.filteredProducts.length === 0);
                return;
            }

            this.toggleElement(ELEMENTS.noProducts, false);

            nextProducts.forEach((product, index) => {
                const productCard = this.createProductCard(product, STATE.displayedCount + index);
                ELEMENTS.productsGrid?.appendChild(productCard);
                
                setTimeout(() => {
                    productCard.style.animationDelay = `${index * 0.1}s`;
                }, 10);
            });

            STATE.displayedProducts.push(...nextProducts);
            STATE.displayedCount += nextProducts.length;

            this.toggleElement(ELEMENTS.loadMoreBtn, STATE.displayedCount < STATE.filteredProducts.length);
        }

        static createProductCard(product, index) {
            const productCard = document.createElement('div');
            productCard.className = 'product-card';
            productCard.onclick = () => this.openProductModal(index);
            
            productCard.innerHTML = `
                <div class="product-image">
                    <div class="image-placeholder">
                        <i class="fas fa-image"></i>
                    </div>
                    <img src="${product.الصورة || '/placeholder.jpg'}" 
                         alt="${product.الاسم || ''}"
                         loading="lazy"
                         onerror="this.style.display='none'"
                         onload="this.classList.add('loaded'); this.previousElementSibling.style.display='none'">
                    <div class="product-name-overlay">${product.الاسم || ''}</div>
                </div>
            `;
            
            return productCard;
        }

        static openProductModal(index) {
            const product = STATE.displayedProducts[index];
            if (!product) return;

            STATE.currentProductIndex = index;
            
            if (ELEMENTS.modalImage) {
                ELEMENTS.modalImage.src = product.الصورة || '/placeholder.jpg';
                ELEMENTS.modalImage.alt = product.الاسم || '';
            }
            
            if (ELEMENTS.modalProductName) {
                ELEMENTS.modalProductName.textContent = product.الاسم || '';
            }
            
            ELEMENTS.productModal?.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        static closeProductModal() {
            ELEMENTS.productModal?.classList.remove('active');
            document.body.style.overflow = '';
            STATE.isZoomed = false;
            ELEMENTS.modalImage?.classList.remove('zoomed');
        }

        static navigateProduct(direction) {
            const newIndex = STATE.currentProductIndex + direction;
            if (newIndex >= 0 && newIndex < STATE.displayedProducts.length) {
                this.openProductModal(newIndex);
            }
        }

        static toggleZoom() {
            STATE.isZoomed = !STATE.isZoomed;
            ELEMENTS.modalImage?.classList.toggle('zoomed', STATE.isZoomed);
        }

        static updateURL() {
            const url = new URL(window.location);
            if (STATE.currentCategoryId === 0) {
                url.searchParams.delete('s');
            } else {
                url.searchParams.set('s', STATE.currentCategoryId);
            }
            window.history.replaceState({}, '', url);
        }

        static scrollToActiveCategory() {
            setTimeout(() => {
                const activeButton = document.querySelector('.category-filter-btn.active');
                if (activeButton && COMPONENTS.categoriesSwiper && ELEMENTS.categoriesWrapper) {
                    const slideIndex = Array.from(ELEMENTS.categoriesWrapper.children)
                        .findIndex(slide => slide.contains(activeButton));
                    if (slideIndex !== -1) {
                        COMPONENTS.categoriesSwiper.slideTo(slideIndex, 300);
                    }
                }
            }, 100);
        }

        static clearProductsGrid() {
            if (ELEMENTS.productsGrid) {
                ELEMENTS.productsGrid.innerHTML = '';
            }
            STATE.displayedProducts = [];
            STATE.displayedCount = 0;
        }

        static setupEventListeners() {
            this.setupCategoryListeners();
            this.setupSearchListener();
            this.setupModalListeners();
            this.setupKeyboardListeners();
            this.setupMouseListeners();
            this.setupLoadMoreListener();
            this.setupLanguageListener();
        }
        
        static setupLanguageListener() {
            // الاستماع لتغييرات اللغة من النظام المركزي
            document.addEventListener('siteLanguageChanged', handleLanguageChange);
            document.addEventListener('languageChanged', handleLanguageChange); // التوافق مع النظام القديم
        }

        static setupCategoryListeners() {
            ELEMENTS.categoriesWrapper?.addEventListener('click', (e) => {
                const btn = e.target.closest('.category-filter-btn');
                if (btn) {
                    const categoryId = parseInt(btn.dataset.categoryId);
                    this.changeCategory(categoryId, false);
                }
            });

            ELEMENTS.otherCategoriesGrid?.addEventListener('click', (e) => {
                const card = e.target.closest('.other-category-card');
                if (card) {
                    const categoryId = parseInt(card.dataset.categoryId);
                    this.changeCategory(categoryId, true);
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            });
        }

        static setupSearchListener() {
            ELEMENTS.searchInput?.addEventListener('input', (e) => {
                clearTimeout(COMPONENTS.searchTimeout);
                COMPONENTS.searchTimeout = setTimeout(() => {
                    STATE.searchTerm = e.target.value.trim();
                    this.clearProductsGrid();
                    this.filterProducts();
                }, CONFIG.SEARCH_DELAY);
            });
        }

        static setupModalListeners() {
            ELEMENTS.productModal?.addEventListener('click', (e) => {
                if (e.target === e.currentTarget) {
                    this.closeProductModal();
                }
            });
        }

        static setupKeyboardListeners() {
            document.addEventListener('keydown', (e) => {
                if (ELEMENTS.productModal?.classList.contains('active')) {
                    switch(e.key) {
                        case 'Escape':
                            this.closeProductModal();
                            break;
                        case 'ArrowLeft':
                            this.navigateProduct(1);
                            break;
                        case 'ArrowRight':
                            this.navigateProduct(-1);
                            break;
                    }
                }
            });
        }

        static setupMouseListeners() {
            document.addEventListener('mousemove', (e) => {
                if (ELEMENTS.hoverImagePreview?.classList.contains('active')) {
                    ELEMENTS.hoverImagePreview.style.left = (e.clientX + 20) + 'px';
                    ELEMENTS.hoverImagePreview.style.top = (e.clientY - 150) + 'px';
                }
            });
        }

        static setupLoadMoreListener() {
            ELEMENTS.loadMoreBtn?.addEventListener('click', () => {
                this.loadMoreProducts();
            });
        }

        static toggleElement(element, show) {
            if (element) {
                element.style.display = show ? 'block' : 'none';
            }
        }

        static delay(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        }
    }

    // إتاحة الوصول للمكون من النطاق العام للتشخيص
    window.ProductsPageManager = {
        updateLanguage: handleLanguageChange,
        getCurrentLanguage: () => currentLanguage,
        isInitialized: () => isInitialized,
        reinit: () => ProductManager.init()
    };

    window.ProductManager = ProductManager;
    window.openProductModal = (index) => ProductManager.openProductModal(index);
    window.closeProductModal = () => ProductManager.closeProductModal();
    window.navigateProduct = (direction) => ProductManager.navigateProduct(direction);
    window.toggleZoom = () => ProductManager.toggleZoom();
    window.showHoverImageForCategory = (button) => ProductManager.showHoverImage(button);
    window.hideHoverImage = () => ProductManager.hideHoverImage();

    document.addEventListener('DOMContentLoaded', () => {
        ProductManager.init();
    });

    console.log('تم تحميل صفحة المنتجات مع نظام الترجمة الكامل');

})();
</script>










</body>
</html>