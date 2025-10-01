<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>شركة ألفا الذهبية - الصفحة الرئيسية</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- Tailwind CSS v4.0 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style type="text/tailwindcss">
        /*
        هنا يجب تضمين كامل كود @theme و @layer components من الدليل السابق، مثل:
        @theme { --color-gold: #977e2b; ... }
        @layer base { body { font-family: var(--font-family-cairo); ... } }
        @layer components { .btn-gold { @apply ... } ... }
        @layer utilities { .flatpickr-calendar { ... } ... }
        */

        /*
        مثال صغير على كيفية تعريف @theme و @layer components إذا لم يكن لديك ملف CSS منفصل.
        في بيئة إنتاجية، يفضل استخدام عملية بناء Tailwind (npm run build) لتوليد ملف CSS واحد.
        */
        @theme {
            --color-gold: #977e2b;
            --color-gold-hover: #b89635;
            --color-gold-light: rgba(151, 126, 43, 0.1);
            --color-dark-gray: #2c2c2c;
            --color-medium-gray: #666;
            --color-light-gray: #f8f9fa;
            --color-border: #e5e7eb;
            --color-success: #28a745; /* للمثال فقط، غير مستخدم هنا بشكل مباشر */
            --font-family-cairo: 'Cairo', sans-serif;
            --radius-md: 12px;
        }
        @layer base {
            body {
                font-family: var(--font-family-cairo);
                background-color: var(--color-light-gray);
                color: var(--color-dark-gray);
            }
            html, body {
                @apply overflow-x-hidden;
            }
        }
        @layer components {
            .welcome-section-card { /* Custom name for this specific card style */
                @apply bg-white rounded-md p-10 shadow-md text-center mb-8;
            }
            .welcome-title {
                @apply text-3xl font-bold text-gold mb-4 md:text-2xl;
            }
            .welcome-subtitle {
                @apply text-base text-medium-gray leading-relaxed;
            }
            .quick-actions-grid {
                @apply grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-8 max-w-5xl mx-auto;
            }
            
            .action-card-item {
                @apply block bg-white rounded-md p-9 shadow-md text-center transition-all duration-300 cursor-pointer border-2 border-transparent no-underline text-inherit;
            }
            .action-card-item:hover {
                @apply -translate-y-1.5 shadow-lg border-gold;
            }
            .action-icon-wrapper {
                @apply w-[70px] h-[70px] bg-gold-light rounded-full flex items-center justify-center mx-auto mb-5 text-gold text-3xl md:w-[60px] md:h-[60px] md:text-2xl;
            }
            .action-title-text {
                @apply text-xl font-semibold text-dark-gray mb-3;
            }
            .action-description-text {
                @apply text-sm text-medium-gray leading-normal;
            }
            
            
            body.font-cairo.bg-light-gray.text-dark-gray.antialiased {
    padding-bottom: 50px;
}

        }
    </style>
</head>
<body class="font-cairo bg-light-gray text-dark-gray antialiased">

        
        <div class="quick-actions-grid">
            <a href="https://alfagolden.com/system/home/m.php" class="action-card-item">
                <div class="action-icon-wrapper">
                    <i class="fas fa-globe"></i>
                </div>
                <h3 class="action-title-text">إدارة محتوى الموقع</h3>
                <p class="action-description-text">إدارة وتحديث محتوى الموقع الإلكتروني والصفحات والمقالات</p>
            </a>
            
            <a href="https://alfagolden.com/system/mq.php" class="action-card-item">
                <div class="action-icon-wrapper">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <h3 class="action-title-text">إدارة عروض الأسعار</h3>
                <p class="action-description-text">إنشاء ومراجعة وإدارة جميع عروض الأسعار للعملاء</p>
            </a>
            
            <a href="https://alfagolden.com/system/mu.php" class="action-card-item">
                <div class="action-icon-wrapper">
                    <i class="fas fa-users-cog"></i>
                </div>
                <h3 class="action-title-text">إدارة المستخدمين والصلاحيات</h3>
                <p class="action-description-text">إدارة العملاء والموظفين وصلاحياتهم في النظام</p>
            </a>
            
            <a href="https://alfagolden.com/system/docs/dx.php" class="action-card-item">
    <div class="action-icon-wrapper">
        <i class="fas fa-file-lines"></i>
    </div>
    <h3 class="action-title-text">قوالب المستندات</h3>
    <p class="action-description-text">إنشاء وإدارة قوالب المستندات الجاهزة للاستخدام</p>
</a>
            
        </div>
    </div>
</body>
</html>