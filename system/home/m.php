<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>ألفا الذهبية - لوحة مختصرة</title>

  <!-- Google Font + Font Awesome -->
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

  <!-- Tailwind CSS v4 (Browser CDN) -->
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

  <style type="text/tailwindcss">
    /* إعدادات سريعة للثيم (نفس الروح السابقة) */
    @theme {
      --color-gold: #977e2b;
      --color-gold-hover: #b89635;
      --color-gold-light: rgba(151, 126, 43, 0.1);
      --color-dark-gray: #2c2c2c;
      --color-medium-gray: #666;
      --color-light-gray: #f8f9fa;
      --font-family-cairo: 'Cairo', sans-serif;
      --radius-md: 12px;
    }

    @layer base {
      html, body { @apply overflow-x-hidden; }
      body {
        font-family: var(--font-family-cairo);
        background-color: var(--color-light-gray);
        color: var(--color-dark-gray);
      }
    }

    @layer components {
      .actions-grid {
        @apply grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 max-w-5xl mx-auto;
      }
      .action-card {
        @apply block bg-white rounded-[--radius-md] p-8 shadow-md text-center transition-all duration-300 cursor-pointer border-2 border-transparent no-underline text-inherit;
      }
      .action-card:hover { @apply -translate-y-1.5 shadow-lg; border-color: var(--color-gold); }
      .action-icon {
        @apply w-[70px] h-[70px] rounded-full flex items-center justify-center mx-auto mb-4 text-3xl;
        background-color: var(--color-gold-light);
        color: var(--color-gold);
      }
      .action-title { @apply text-xl font-semibold mb-2; }
      .action-desc  { @apply text-sm text-[color:var(--color-medium-gray)]; }
      .page-wrap    { @apply max-w-7xl mx-auto p-6 lg:p-10 min-h-screen; }
      .section-card { @apply bg-white rounded-[--radius-md] p-8 shadow-md mb-8; }
      .section-title{ @apply text-2xl font-bold mb-2; color: var(--color-gold); }
      .section-sub  { @apply text-sm text-[color:var(--color-medium-gray)] mb-6; }
    }
  </style>
</head>
<body>
  <div class="page-wrap">
    <div class="section-card">
      <h2 class="section-title">روابط الإدارة السريعة</h2>
      <p class="section-sub">اختر أحد الأقسام التالية للانتقال مباشرةً إلى صفحة الإدارة.</p>

      <div class="actions-grid">
        <!-- إدارة المنتجات -->
        <a href="https://alfagolden.com/system/m/mm.php" class="action-card">
          <div class="action-icon">
            <i class="fa-solid fa-box"></i>
          </div>
          <div class="action-title">إدارة المنتجات</div>
          <div class="action-desc">إضافة وتعديل وحذف المنتجات وتنظيم التصنيفات</div>
        </a>

        <!-- إدارة مشاريعنا -->
        <a href="https://alfagolden.com/system/m/mp.php" class="action-card">
          <div class="action-icon">
            <i class="fa-solid fa-briefcase"></i>
          </div>
          <div class="action-title">إدارة مشاريعنا</div>
          <div class="action-desc">إدارة وعرض المشاريع وتحديث حالتها ومحتواها</div>
        </a>

        <!-- البنر الرئيسي -->
        <a href="https://alfagolden.com/system/m/mb.php" class="action-card">
          <div class="action-icon">
            <i class="fa-solid fa-image"></i>
          </div>
          <div class="action-title">البنر الرئيسي</div>
          <div class="action-desc">تحديث صور البنر والنصوص والأزرار في الصفحة الرئيسية</div>
        </a>
      </div>
    </div>
  </div>
</body>
</html>
