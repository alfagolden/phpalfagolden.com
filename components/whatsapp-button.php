<?php
// مكون زر واتساب - مستقل بالكامل
?>

<style>
/* أنماط مكون زر واتساب */
:root {
    --gold: #977e2b;
    --gold-hover: #b89635;
    --whatsapp-green: #25D366;
    --whatsapp-green-hover: #20c157;
    --transition-normal: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* إعدادات أساسية للمكون */
.whatsapp-component * {
    box-sizing: border-box;
}

.whatsapp-component {
    font-family: 'Cairo', 'Tajawal', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* زر واتساب العائم */
.whatsapp-float {
    position: fixed;
    bottom: 20px;
    left: 20px;
    width: 75px;
    height: 75px;
    border-radius: 50%;
    background-color: var(--gold);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
    z-index: 1000;
    cursor: pointer;
    transition: var(--transition-normal);
    animation: glow 3s infinite;
    border: 3px solid rgba(255, 255, 255, 0.2);
    text-decoration: none;
    color: inherit;
}

.whatsapp-float:hover {
    transform: scale(1.1);
    background-color: var(--gold-hover);
    animation-play-state: paused;
}

.whatsapp-float:active {
    transform: scale(0.95);
}

.whatsapp-float img {
    width: 50px;
    height: 50px;
    object-fit: contain;
    filter: grayscale(1);
    transition: var(--transition-normal);
}

.whatsapp-float:hover img {
    transform: rotate(15deg) scale(1.1);
}

/* تأثير التوهج */
@keyframes glow {
    0%, 100% {
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3),
                    0 0 10px rgba(151, 126, 43, 0.7);
    }
    50% {
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3),
                    0 0 20px rgba(151, 126, 43, 1);
    }
}

/* نسخة واتساب خضراء */
.whatsapp-float.green-theme {
    background-color: var(--whatsapp-green);
    border-color: rgba(255, 255, 255, 0.3);
}

.whatsapp-float.green-theme:hover {
    background-color: var(--whatsapp-green-hover);
}

.whatsapp-float.green-theme img {
    filter: brightness(0) invert(1);
}

/* تأثير النبض */
.whatsapp-float.pulse {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
    100% {
        transform: scale(1);
    }
}

/* رسالة التحفيز */
.whatsapp-message {
    position: fixed;
    bottom: 110px;
    left: 20px;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    padding: 12px 16px;
    border-radius: 20px;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    color: #333;
    font-size: 14px;
    font-weight: 600;
    max-width: 200px;
    opacity: 0;
    transform: translateY(20px);
    transition: var(--transition-normal);
    z-index: 999;
    border: 1px solid rgba(151, 126, 43, 0.2);
}

.whatsapp-message.show {
    opacity: 1;
    transform: translateY(0);
}

.whatsapp-message::after {
    content: '';
    position: absolute;
    bottom: -8px;
    left: 20px;
    width: 0;
    height: 0;
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    border-top: 8px solid rgba(255, 255, 255, 0.95);
}

/* الاستجابة للشاشات المختلفة */
@media (max-width: 768px) {
    .whatsapp-float {
        width: 60px;
        height: 60px;
        bottom: 20px;
        left: 20px;
    }
    
    .whatsapp-float img {
        width: 35px;
        height: 35px;
    }
    
    .whatsapp-message {
        bottom: 90px;
        max-width: 160px;
        font-size: 12px;
    }
}

@media (max-width: 480px) {
    .whatsapp-float {
        width: 55px;
        height: 55px;
        bottom: 15px;
        left: 15px;
    }
    
    .whatsapp-float img {
        width: 30px;
        height: 30px;
    }
    
    .whatsapp-message {
        bottom: 80px;
        left: 15px;
        max-width: 140px;
        padding: 10px 12px;
    }
}

/* للعرض المستقل */
.whatsapp-standalone {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    min-height: 100vh;
    padding: 2rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
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

.demo-controls {
    background: white;
    padding: 2rem;
    border-radius: 1rem;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    margin-bottom: 2rem;
}

.demo-controls h3 {
    color: #333;
    margin-bottom: 1rem;
    text-align: center;
}

.demo-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.demo-button {
    background: var(--gold);
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 0.5rem;
    cursor: pointer;
    font-size: 0.9rem;
    font-weight: 600;
    transition: var(--transition-normal);
}

.demo-button:hover {
    background: var(--gold-hover);
    transform: translateY(-2px);
}

.demo-button.green {
    background: var(--whatsapp-green);
}

.demo-button.green:hover {
    background: var(--whatsapp-green-hover);
}
</style>

<!-- HTML مكون زر واتساب -->
<div class="whatsapp-component">
    <!-- رسالة التحفيز -->
    <div class="whatsapp-message" id="whatsappMessage">
        تحدث معنا الآن!
    </div>
    
    <!-- زر واتساب العائم -->
    <a href="https://wa.me/966506086333" 
       target="_blank" 
       rel="noopener"
       class="whatsapp-float will-change-transform"
       id="whatsappButton"
       title="تواصل واتساب"
       aria-label="تواصل معنا عبر واتساب">
<img src="https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg" alt="واتساب">

    </a>
</div>

<script>
// JavaScript مكون زر واتساب
(function() {
    'use strict';
    
    // المتغيرات
    const whatsappButton = document.getElementById('whatsappButton');
    const whatsappMessage = document.getElementById('whatsappMessage');
    let messageTimeout;
    let isMessageVisible = false;
    
    if (!whatsappButton) return;
    
    // إظهار الرسالة التحفيزية
    function showMessage() {
        if (whatsappMessage && !isMessageVisible) {
            whatsappMessage.classList.add('show');
            isMessageVisible = true;
            
            // إخفاء الرسالة بعد 5 ثواني
            messageTimeout = setTimeout(hideMessage, 5000);
        }
    }
    
    // إخفاء الرسالة التحفيزية
    function hideMessage() {
        if (whatsappMessage && isMessageVisible) {
            whatsappMessage.classList.remove('show');
            isMessageVisible = false;
        }
    }
    
    // تأثيرات التفاعل
    whatsappButton.addEventListener('mouseenter', function() {
        this.style.willChange = 'transform';
        clearTimeout(messageTimeout);
        showMessage();
    });
    
    whatsappButton.addEventListener('mouseleave', function() {
        this.style.willChange = 'auto';
        messageTimeout = setTimeout(hideMessage, 1000);
    });
    
    // تأثير النقر
    whatsappButton.addEventListener('click', function() {
        // تأثير النقر السريع
        this.style.animation = 'none';
        this.style.transform = 'scale(0.9)';
        
        setTimeout(() => {
            this.style.transform = '';
            this.style.animation = '';
        }, 150);
        
        // إحصائيات (اختياري)
        if (typeof gtag !== 'undefined') {
            gtag('event', 'click', {
                event_category: 'WhatsApp',
                event_label: 'Contact Button',
                value: 1
            });
        }
    });
    
    // إظهار الرسالة التلقائي بعد 10 ثواني
    setTimeout(showMessage, 10000);
    
    // إظهار الرسالة كل 30 ثانية
    setInterval(() => {
        if (!isMessageVisible) {
            showMessage();
        }
    }, 30000);
    
    // إخفاء الرسالة عند النقر عليها
    if (whatsappMessage) {
        whatsappMessage.addEventListener('click', hideMessage);
    }
    
    // معالجة خطأ تحميل الصورة
    const whatsappImg = whatsappButton.querySelector('img');
    if (whatsappImg) {
        whatsappImg.addEventListener('error', function() {
            // استبدال بأيقونة فونت أوسوم إذا فشل تحميل الصورة
            whatsappButton.innerHTML = '<i class="fab fa-whatsapp" style="font-size: 35px; color: white;"></i>';
        });
    }
    
    // دوال التحكم للعرض المستقل
    window.toggleWhatsAppTheme = function() {
        whatsappButton.classList.toggle('green-theme');
    };
    
    window.toggleWhatsAppPulse = function() {
        whatsappButton.classList.toggle('pulse');
    };
    
    window.showWhatsAppMessage = function() {
        showMessage();
    };
    
    window.hideWhatsAppMessage = function() {
        hideMessage();
    };
    
    // تحسين الأداء
    let ticking = false;
    function updateOnScroll() {
        if (!ticking) {
            requestAnimationFrame(() => {
                // يمكن إضافة تأثيرات التمرير هنا
                ticking = false;
            });
            ticking = true;
        }
    }
    
    window.addEventListener('scroll', updateOnScroll, { passive: true });
})();
</script>

<?php
// في حالة الوصول المباشر للملف
if (basename($_SERVER['PHP_SELF']) == 'whatsapp-button.php') {
    echo '<!DOCTYPE html>
    <html lang="ar" dir="rtl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>مكون زر واتساب - ألفا الذهبية</title>
        <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200;300;400;500;600;700;800;900&family=Tajawal:wght@200;300;400;500;700;800;900&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    </head>
    <body class="whatsapp-standalone">
        <div class="demo-info">
            <h1>مكون زر واتساب</h1>
            <p>هذا هو مكون زر واتساب العائم مع تأثيرات تفاعلية متطورة</p>
        </div>
        
        <div class="demo-controls">
            <h3>تحكم في المظهر</h3>
            <div class="demo-buttons">
                <button class="demo-button green" onclick="window.toggleWhatsAppTheme()">تبديل اللون</button>
                <button class="demo-button" onclick="window.toggleWhatsAppPulse()">تبديل النبض</button>
                <button class="demo-button" onclick="window.showWhatsAppMessage()">إظهار الرسالة</button>
                <button class="demo-button" onclick="window.hideWhatsAppMessage()">إخفاء الرسالة</button>
            </div>
        </div>
    ';
    
    // سيتم عرض المكون هنا تلقائياً
    
    echo '
    </body>
    </html>';
}
?>