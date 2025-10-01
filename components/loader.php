<?php
// مكون اللودر - مستقل بالكامل مع التصميم الجديد
?>
<style>
/* أنماط مكون اللودر الجديد */
.loader {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    transition: opacity 0.5s ease;
    font-family: sans-serif;
    overflow: hidden;
}
.loader.hidden {
    opacity: 0;
    pointer-events: none;
}
.loader-container {
    position: relative;
    width: 90px;
    height: 90px;
    overflow: visible;
    perspective: 1000px;
}
.logo-wrapper {
    position: relative;
    width: 100%;
    height: 100%;
}
.logo-wrapper::before {
    content: '';
    position: absolute;
    top: -30%;
    left: -120%;
    width: 240%;
    height: 170%;
    background: linear-gradient(
        97deg,
        transparent 38%,
        rgba(255,255,255,0.44) 49%,
        rgba(255,255,255,0.60) 52%,
        rgba(255,255,255,0.33) 58%,
        transparent 65%
    );
    transform: skewX(-16deg);
    animation: shine 2.3s ease-in-out infinite;
    pointer-events: none;
    z-index: 2;
}
@keyframes shine {
    0%   { left: -120%; opacity: 0; }
    10%  { opacity: 0.62; }
    44%  { opacity: 0.38; }
    60%  { left: 110%; opacity: 0.38; }
    90%  { opacity: 0; }
    100% { left: 110%; opacity: 0; }
}
.logo-wrapper img {
    width: 100%;
    height: 100%;
    display: block;
    pointer-events: none;
    user-select: none;
    position: relative;
    z-index: 1;
}
.shadow {
    position: absolute;
    bottom: -25px;
    left: 50%;
    transform: translateX(-50%);
    width: 80px;
    height: 18px;
    background: radial-gradient(
        ellipse at center,
        rgba(0,0,0,0.3) 0%,
        rgba(0,0,0,0.15) 60%,
        rgba(0,0,0,0.03) 100%
    );
    border-radius: 50%;
    filter: blur(6px);
    opacity: 0.6;
    pointer-events: none;
    z-index: 0;
    will-change: width, height, opacity, filter;
}
/* للعرض المستقل */
body.loader-standalone {
    margin: 0;
    padding: 0;
    height: 100vh;
    overflow: hidden;
}
.loader-standalone .loader {
    position: relative;
    height: 100vh;
}
</style>

<!-- HTML مكون اللودر الجديد -->
<div class="loader" id="loader">
    <div class="loader-container">
        <div class="logo-wrapper" id="logoWrapper">
            <img src="https://alfagolden.com/iconalfa.png" alt="Alfa Logo" id="logo">
        </div>
        <div class="shadow" id="shadow"></div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script>
// JavaScript مكون اللودر مع التصميم الجديد
(function() {
    'use strict';
    
    // التحقق من وجود المكون
    const loader = document.getElementById('loader');
    const logoWrapper = document.getElementById("logoWrapper");
    const shadow = document.getElementById("shadow");
    
    if (!loader || !logoWrapper || !shadow) return;
    
    // تشغيل تأثير GSAP للحركة
    function initLoaderAnimation() {
        gsap.to(logoWrapper, {
            y: -55,
            duration: 1.2,
            ease: "power1.inOut",
            yoyo: true,
            repeat: -1,
            onUpdate() {
                const y = gsap.getProperty(logoWrapper, "y");
                const percent = 1 - Math.abs(y / -55);
                const minW = 40, maxW = 100;
                const minH = 8,  maxH = 20;
                const minO = 0.2, maxO = 0.6;
                const minB = 3,   maxB = 8;
                shadow.style.width  = (minW + (maxW - minW) * percent) + "px";
                shadow.style.height = (minH + (maxH - minH) * percent) + "px";
                shadow.style.opacity = (minO + (maxO - minO) * percent);
                shadow.style.filter  = `blur(${minB + (maxB - minB) * percent}px)`;
            }
        });
    }
    
    // بدء تشغيل الحركة
    if (typeof gsap !== 'undefined') {
        initLoaderAnimation();
    } else {
        // في حالة عدم تحميل GSAP، انتظار قليلاً وحاول مرة أخرى
        setTimeout(() => {
            if (typeof gsap !== 'undefined') {
                initLoaderAnimation();
            }
        }, 100);
    }
    
    // دالة إخفاء اللودر
    function hideLoader() {
        if (loader) {
            loader.classList.add('hidden');
            setTimeout(() => {
                loader.style.display = 'none';
                // إيقاف تأثيرات GSAP عند الإخفاء
                if (typeof gsap !== 'undefined') {
                    gsap.killTweensOf(logoWrapper);
                }
            }, 500);
        }
    }
    
    // إخفاء اللودر بعد تحميل المحتوى
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(hideLoader, 1000);
        });
    } else {
        setTimeout(hideLoader, 500);
    }
    
    // إخفاء اللودر عند تحميل النافذة كاملة
    window.addEventListener('load', () => {
        setTimeout(hideLoader, 500);
    });
    
    // للعرض المستقل - إضافة class للـ body
    if (window.location.pathname.includes('loader.php')) {
        document.body.classList.add('loader-standalone');
        // في حالة العرض المستقل، إخفاء اللودر بعد 3 ثواني للعرض
        setTimeout(hideLoader, 3000);
    }
})();
</script>

<?php
// في حالة الوصول المباشر للملف
if (basename($_SERVER['PHP_SELF']) == 'loader.php') {
    echo '<!DOCTYPE html>
    <html lang="ar" dir="rtl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>مكون اللودر - ألفا الذهبية</title>
    </head>
    <body class="loader-standalone">
    ';
    
    // سيتم عرض المكون هنا تلقائياً
    
    echo '
    </body>
    </html>';
}
?>