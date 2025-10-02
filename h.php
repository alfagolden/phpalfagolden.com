<?php
// تشغيل عرض الأخطاء للتشخيص (يمكن حذفها لاحقاً)
error_reporting(E_ALL);
ini_set('display_errors', 1);
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
    
    <style>
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
                padding-top: 90px;
            }
        }
        
        /* للشاشات الأصغر من 480px - ارتفاع الهيدر 60px */
        @media (max-width: 480px) {
            main {
                padding-top: 90px;
            }
        }
        
        /* التأكد من عدم وجود مسافات إضافية على العناصر الأولى */
        .main-slider,
        #main-slider,
        .slider-container {
            margin-top: 0;
            padding-top: 0;
        }
        
        /* تحسين عرض النصوص العربية */
        body {
            font-feature-settings: "kern" 1;
            text-rendering: optimizeLegibility;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        /* التأكد من عدم وجود تداخل مع الهيدر */
        .content-after-header {
            position: relative;
            z-index: 1;
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
    <!--<?php include 'components/header.php'; ?>-->

    <main role="main">
        <?php include 'components/main-slider.php'; ?>
        <?php include 'components/catalogs-section.php'; ?>
        <?php include 'components/clients-section.php'; ?>
        <?php include 'components/products-section.php'; ?>
        <?php include 'components/image-gallery.php'; ?>


    </main>
    
    <?php include 'components/footer.php'; ?>
    <?php include 'components/whatsapp-button.php'; ?>
</body>




  

</html>