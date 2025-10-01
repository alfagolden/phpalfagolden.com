<?php
// مكون الفوتر مع نظام الترجمة الكامل - بدون تكرار
?>

<style>
/* أنماط مكون الفوتر */
:root {
    --gold: #977e2b;
    --gold-hover: #b89635;
    --gold-light: #d4b85a;
    --dark-gray: #2c2c2c;
    --white: #ffffff;
    --spacing-sm: 0.5rem;
    --spacing-md: 1rem;
    --spacing-lg: 1.5rem;
    --spacing-xl: 3rem;
    --spacing-2xl: 4rem;
    --font-size-xl: 1.25rem;
    --shadow-md: 0 4px 8px rgba(0, 0, 0, 0.12), 0 2px 4px rgba(0, 0, 0, 0.08);
    --shadow-lg: 0 15px 25px rgba(0, 0, 0, 0.15), 0 5px 10px rgba(0, 0, 0, 0.05);
    --transition-normal: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* إعدادات أساسية للمكون */
.footer-component * {
    box-sizing: border-box;
}

.footer-component {
    font-family: 'Cairo', 'Tajawal', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* أيقونات احتياطية في حالة عدم تحميل Font Awesome */
.icon-fallback {
    display: inline-block;
    width: 20px;
    height: 20px;
    text-align: center;
    background: var(--gold);
    color: white;
    border-radius: 3px;
    line-height: 20px;
    font-size: 12px;
    font-weight: bold;
}

/* الفوتر الرئيسي */
.footer-component footer {
    background: linear-gradient(135deg, var(--dark-gray) 0%, #1a1a1a 100%);
    color: var(--white);
    padding: calc(var(--spacing-2xl) + var(--spacing-lg)) 0 var(--spacing-xl);
    position: relative;
    overflow: hidden;
}

.footer-component footer::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, var(--gold), transparent);
}

.footer-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 var(--spacing-lg);
}

.footer-content {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: var(--spacing-2xl);
    margin-bottom: var(--spacing-2xl);
}

.footer-section h3 {
    color: var(--gold);
    margin-bottom: var(--spacing-lg);
    font-size: var(--font-size-xl);
    font-weight: 700;
    position: relative;
    padding-bottom: var(--spacing-sm);
}

.footer-section h3::after {
    content: '';
    position: absolute;
    bottom: 0;
    right: 0;
    width: 30px;
    height: 2px;
    background: var(--gold);
}

.footer-section ul {
    list-style: none;
    margin: 0;
    padding: 0;
}

.footer-section ul li {
    margin-bottom: var(--spacing-md);
}

.footer-section a {
    color: rgba(255, 255, 255, 0.8);
    transition: var(--transition-normal);
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-xs);
    text-decoration: none;
    cursor: pointer;
}

.footer-section a:hover {
    color: var(--gold);
    transform: translateX(-5px);
}

/* تأثير خاص لروابط المعرض */
.gallery-trigger-link {
    position: relative;
    overflow: hidden;
}

.gallery-trigger-link::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(151, 126, 43, 0.2), transparent);
    transition: left 0.5s;
}

.gallery-trigger-link:hover::before {
    left: 100%;
}

.contact-info p {
    margin-bottom: var(--spacing-sm);
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    color: rgba(255, 255, 255, 0.9);
}

.contact-info i, .contact-info .icon-fallback {
    color: var(--gold);
    width: 20px;
    flex-shrink: 0;
}

.contact-info a {
    color: rgba(255, 255, 255, 0.9);
    text-decoration: none;
    transition: var(--transition-normal);
}

.contact-info a:hover {
    color: var(--gold);
}

.social-links {
    display: flex;
    gap: var(--spacing-md);
    margin-top: var(--spacing-lg);
}

.social-links a {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, var(--gold), var(--gold-hover));
    color: var(--white);
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: var(--transition-normal);
    font-size: var(--font-size-xl);
    box-shadow: var(--shadow-md);
    position: relative;
    overflow: hidden;
    text-decoration: none;
}

.social-links a::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s;
}

.social-links a:hover::before {
    left: 100%;
}

.social-links a:hover {
    transform: translateY(-5px) rotate(360deg);
    box-shadow: var(--shadow-lg);
    background: linear-gradient(135deg, var(--gold-hover), var(--gold-light));
}

.footer-bottom {
    text-align: center;
    padding-top: var(--spacing-xl);
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.875rem;
}

.footer-logo {
    margin-bottom: var(--spacing-lg);
    display: inline-block;
    background: var(--white);
    padding: var(--spacing-md) var(--spacing-xl);
    border-radius: 1rem;
    box-shadow: var(--shadow-lg);
    transition: var(--transition-normal);
}

.footer-logo:hover {
    transform: scale(1.05);
}

.footer-logo img {
    height: 80px;
    object-fit: contain;
    display: block;
}

.footer-section p {
    color: rgba(255, 255, 255, 0.8);
    line-height: 1.6;
    margin-bottom: var(--spacing-md);
}

/* قسم الروابط السريعة المحدث */
.quick-links {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--spacing-lg);
}

.quick-links-column ul {
    list-style: none;
    margin: 0;
    padding: 0;
}

.quick-links-column h4 {
    color: var(--gold-light);
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: var(--spacing-md);
    border-bottom: 1px solid rgba(151, 126, 43, 0.3);
    padding-bottom: var(--spacing-sm);
}

/* الاستجابة للشاشات المختلفة */
@media (max-width: 768px) {
    .footer-content {
        grid-template-columns: 1fr;
        text-align: center;
        gap: var(--spacing-xl);
    }
    
    .footer-section {
        padding: 0 var(--spacing-lg);
    }
    
    .social-links {
        justify-content: center;
    }
    
    .contact-info p {
        justify-content: center;
    }
    
    .footer-logo img {
        height: 60px;
    }
    
    .quick-links {
        grid-template-columns: 1fr;
        text-align: right;
    }
}

@media (max-width: 480px) {
    .footer-container {
        padding: 0 var(--spacing-md);
    }
    
    .social-links a {
        width: 45px;
        height: 45px;
        font-size: 1rem;
    }
}

/* للعرض المستقل */
.footer-standalone {
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

/* تحسين عرض الأيقونات */
.fa, .fab, .fas, .far {
    font-family: "Font Awesome 6 Free", "Font Awesome 6 Brands" !important;
}

/* حالة تحميل الأيقونات */
.icon-loading {
    opacity: 0.5;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 0.5; }
    50% { opacity: 1; }
}
</style>

<!-- تحميل Font Awesome مع روابط متعددة للضمان -->
<link rel="preconnect" href="https://cdnjs.cloudflare.com">
<link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">

<!-- رابط Font Awesome الأساسي مع integrity -->
<link rel="stylesheet" 
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" 
      integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" 
      crossorigin="anonymous" 
      referrerpolicy="no-referrer">

<!-- HTML مكون الفوتر مع الترجمة الكاملة - بدون تكرار -->
<div class="footer-component">
    <footer role="contentinfo">
        <div class="footer-container">
            <div class="footer-content">
                <div class="footer-section" data-aos="fade-up">
                    <div class="footer-logo">
                        <img src="/logo.png" alt="شركة ألفا الذهبية" loading="lazy">
                    </div>
                    <p id="companyDescription">شركة ألفا الذهبية للمصاعد والمقاولات، رائدة في مجال المصاعد والمقاولات في المملكة العربية السعودية. نقدم خدمات متميزة وحلول مبتكرة.</p>
                </div>
                
                <div class="footer-section" data-aos="fade-up" data-aos-delay="100">
<h3 id="uniqueLinksTitle">أعرفنا أكثر</h3>                    <ul>
                        <li><a href="#" class="gallery-trigger-link" data-gallery-id="10" data-text-key="contractingProfile">الملف التعريفي للمقاولات</a></li>
                        <li><a href="#" class="gallery-trigger-link" data-gallery-id="7" data-text-key="ourValues">قيمنا ومبادئنا</a></li>
                        <li><a href="#" class="gallery-trigger-link" data-gallery-id="8" data-text-key="visionMission">رؤيتنا ورسالتنا</a></li>
                        <li><a href="#" class="gallery-trigger-link" data-gallery-id="9" data-text-key="aboutUs">من نحن</a></li>
                    </ul>
                </div>
                
                <div class="footer-section" data-aos="fade-up" data-aos-delay="200">
                    <h3 id="contactTitle">تواصل معنا</h3>
                    <div class="contact-info">
                        <p><strong>
                            <i class="fas fa-phone icon-loading" aria-hidden="true"></i>
                            <span class="icon-fallback" style="display: none;">📞</span>
                            <span id="phonesLabel">الأرقام:</span>
                        </strong></p>
                        <p><a href="tel:+966148400009">0148400009</a></p>
                        <p><a href="tel:+966506086333">0506086333</a></p>
                        <p><a href="tel:+966112522227">0112522227</a></p>
                        <p><a href="tel:+966506023111">0506023111</a></p>
                        <br>
                        <p><strong>
                            <i class="fas fa-map-marker-alt icon-loading" aria-hidden="true"></i>
                            <span class="icon-fallback" style="display: none;">📍</span>
                            <span id="addressesLabel">العناوين:</span>
                        </strong></p>
                        <p id="medinaAddress">المدينة المنورة - القصواء - شارع الأمير سلطان</p>
<p id="riyadhAddress">الرياض - القيروان - شارع الملك سلمان</p>                    </div>
                </div>
                
                <div class="footer-section" data-aos="fade-up" data-aos-delay="300">
                    <h3 id="followUsTitle">تابعنا على</h3>
                    <div class="social-links" role="list">
                        <a href="https://twitter.com/alfagolden0" 
                           target="_blank" 
                           rel="noopener" 
                           data-aos="zoom-in" 
                           data-aos-delay="400"
                           aria-label="تابعنا على تويتر">
                            <i class="fab fa-twitter icon-loading" aria-hidden="true"></i>
                            <span class="icon-fallback" style="display: none;">T</span>
                        </a>
                        <a href="https://www.facebook.com/alfagolden2" 
                           target="_blank" 
                           rel="noopener" 
                           data-aos="zoom-in" 
                           data-aos-delay="450"
                           aria-label="تابعنا على فيسبوك">
                            <i class="fab fa-facebook-f icon-loading" aria-hidden="true"></i>
                            <span class="icon-fallback" style="display: none;">F</span>
                        </a>
                        <a href="https://www.youtube.com/" 
                           target="_blank" 
                           rel="noopener" 
                           data-aos="zoom-in" 
                           data-aos-delay="500"
                           aria-label="تابعنا على يوتيوب">
                            <i class="fab fa-youtube icon-loading" aria-hidden="true"></i>
                            <span class="icon-fallback" style="display: none;">Y</span>
                        </a>
                        <a href="https://www.instagram.com/alfagolden2" 
                           target="_blank" 
                           rel="noopener" 
                           data-aos="zoom-in" 
                           data-aos-delay="550"
                           aria-label="تابعنا على إنستجرام">
                            <i class="fab fa-instagram icon-loading" aria-hidden="true"></i>
                            <span class="icon-fallback" style="display: none;">I</span>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p id="copyrightText">جميع الحقوق محفوظة © <?php echo date('Y'); ?> شركة ألفا الذهبية للمصاعد والمقاولات</p>
            </div>
        </div>
    </footer>
</div>

<script>
// JavaScript مكون الفوتر مع نظام الترجمة الكامل - بدون تكرار
(function() {
    'use strict';
    
    let currentLanguage = 'ar'; // اللغة الحالية
    let isInitialized = false; // تجنب التهيئة المتكررة
    
    // ترجمات شاملة لجميع النصوص
    const translations = {
        ar: {
            // وصف الشركة
            companyDescription: 'شركة ألفا الذهبية للمصاعد والمقاولات، رائدة في مجال المصاعد والمقاولات في المملكة العربية السعودية. نقدم خدمات متميزة وحلول مبتكرة.',
            
            // العناوين
uniqueLinksTitle: 'أعرفنا أكثر',

            // روابط المعارض والصفحات (الفريدة فقط)
            contractingProfile: 'الملف التعريفي للمقاولات',
            ourValues: 'قيمنا ومبادئنا',
            visionMission: 'رؤيتنا ورسالتنا',
            aboutUs: 'من نحن',
            
            // معلومات الاتصال
            contactTitle: 'تواصل معنا',
            phonesLabel: 'الأرقام:',
            addressesLabel: 'العناوين:',
            medinaAddress: 'المدينة المنورة - القصواء - شارع الأمير سلطان',
            riyadhAddress: 'الرياض - الملقا - شارع الخير',
            followUsTitle: 'تابعنا على',
            
            // حقوق النشر
            copyrightText: `جميع الحقوق محفوظة © ${new Date().getFullYear()} شركة ألفا الذهبية للمصاعد والمقاولات`
        },
        en: {
            // وصف الشركة
            companyDescription: 'Alfa Golden Elevators and Contracting Company, a leader in the field of elevators and contracting in the Kingdom of Saudi Arabia. We provide distinguished services and innovative solutions.',
            
            // العناوين
            uniqueLinksTitle: 'Learn More About Us',
            
            // روابط المعارض والصفحات (الفريدة فقط)
            contractingProfile: 'Contracting Profile',
            ourValues: 'Our Values & Principles',
            visionMission: 'Our Vision & Mission',
            aboutUs: 'About Us',
            
            // معلومات الاتصال
            contactTitle: 'Contact Us',
            phonesLabel: 'Phone Numbers:',
            addressesLabel: 'Addresses:',
            medinaAddress: 'Medina - Al-Qaswa - Prince Sultan Street',
            riyadhAddress: 'Riyadh - Al-Malqa - Al-Khair Street',
            followUsTitle: 'Follow Us',
            
            // حقوق النشر
            copyrightText: `All Rights Reserved © ${new Date().getFullYear()} Alfa Golden Elevators and Contracting Company`
        }
    };
    
    // تحديث النصوص حسب اللغة
    function updateFooterTexts(language) {
        if (!translations[language]) return;
        
        const textMappings = [
            // وصف الشركة
            { id: 'companyDescription', key: 'companyDescription' },
            
            // عناوين الأقسام
            { id: 'uniqueLinksTitle', key: 'uniqueLinksTitle' },
            { id: 'contactTitle', key: 'contactTitle' },
            { id: 'followUsTitle', key: 'followUsTitle' },
            
            // تسميات معلومات الاتصال
            { id: 'phonesLabel', key: 'phonesLabel' },
            { id: 'addressesLabel', key: 'addressesLabel' },
            { id: 'medinaAddress', key: 'medinaAddress' },
            { id: 'riyadhAddress', key: 'riyadhAddress' },
            
            // حقوق النشر
            { id: 'copyrightText', key: 'copyrightText' }
        ];
        
        // تحديث النصوص بالـ ID
        textMappings.forEach(mapping => {
            const element = document.getElementById(mapping.id);
            if (element && translations[language][mapping.key]) {
                element.textContent = translations[language][mapping.key];
            }
        });
        
        // تحديث النصوص بـ data-text-key
        const elementsWithKeys = document.querySelectorAll('[data-text-key]');
        elementsWithKeys.forEach(element => {
            const textKey = element.getAttribute('data-text-key');
            if (textKey && translations[language][textKey]) {
                element.textContent = translations[language][textKey];
            }
        });
    }
    
    // معالجة تغيير اللغة
    function handleLanguageChange(event) {
        const newLanguage = event.detail.language;
        console.log('الفوتر: تم استلام تغيير اللغة:', newLanguage);
        
        currentLanguage = newLanguage;
        
        // تحديث جميع النصوص
        updateFooterTexts(newLanguage);
        
        console.log('تم تحديث مكون الفوتر للغة:', newLanguage);
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
        updateFooterTexts(currentLanguage);
        
        // تهيئة التأثيرات والتفاعل
        initializeInteractions();
        
        isInitialized = true;
        console.log('تم تهيئة مكون الفوتر - اللغة الحالية:', currentLanguage);
    }
    
    // تهيئة التأثيرات والتفاعل
    function initializeInteractions() {
        // فحص تحميل Font Awesome وإظهار البدائل إذا لزم الأمر
        checkFontAwesome();
        
        // معالجة النقر على روابط المعرض - نفس المنطق المستخدم في الهيدر
        document.addEventListener('click', function(e) {
            const galleryLink = e.target.closest('.gallery-trigger-link');
            if (galleryLink) {
                e.preventDefault();
                e.stopPropagation();
                
                const galleryId = galleryLink.getAttribute('data-gallery-id');
                if (galleryId) {
                    // التأكد من وجود دالة فتح المعرض
                    if (typeof window.openImageGallery === 'function') {
                        console.log('فتح معرض رقم من الفوتر:', galleryId);
                        window.openImageGallery(galleryId);
                    } else {
                        console.error('دالة فتح المعرض غير متوفرة - تأكد من تحميل مكون المعرض');
                        alert('مكون المعرض غير متوفر حالياً');
                    }
                }
            }
        });
        
        // تأثيرات تفاعلية للروابط الاجتماعية
        const socialLinks = document.querySelectorAll('.social-links a');
        
        socialLinks.forEach(link => {
            link.addEventListener('mouseenter', function() {
                this.style.willChange = 'transform';
            });
            
            link.addEventListener('mouseleave', function() {
                this.style.willChange = 'auto';
            });
            
            link.addEventListener('click', function() {
                this.style.transform = 'scale(0.9)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 100);
            });
        });
        
        // تأثيرات تفاعلية للشعار
        const footerLogo = document.querySelector('.footer-logo');
        if (footerLogo) {
            footerLogo.addEventListener('mouseenter', function() {
                this.style.willChange = 'transform';
            });
            
            footerLogo.addEventListener('mouseleave', function() {
                this.style.willChange = 'auto';
            });
        }
        
        // تأثير تمرير سلس للروابط العادية
        const footerLinks = document.querySelectorAll('.footer-section a:not(.gallery-trigger-link)');
        footerLinks.forEach(link => {
            link.addEventListener('mouseenter', function() {
                this.style.willChange = 'transform';
            });
            
            link.addEventListener('mouseleave', function() {
                this.style.willChange = 'auto';
            });
        });
        
        // تأثير خاص لروابط المعرض
        const galleryLinks = document.querySelectorAll('.gallery-trigger-link');
        galleryLinks.forEach(link => {
            link.addEventListener('mouseenter', function() {
                this.style.willChange = 'transform';
                this.style.backgroundColor = 'rgba(151, 126, 43, 0.1)';
                this.style.borderRadius = '4px';
                this.style.padding = '2px 6px';
            });
            
            link.addEventListener('mouseleave', function() {
                this.style.willChange = 'auto';
                this.style.backgroundColor = '';
                this.style.borderRadius = '';
                this.style.padding = '';
            });
            
            // تأثير النقر
            link.addEventListener('mousedown', function() {
                this.style.transform = 'scale(0.95)';
            });
            
            link.addEventListener('mouseup', function() {
                this.style.transform = '';
            });
        });
        
        // تأثير الظهور التدريجي
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, {
                threshold: 0.1
            });
            
            const footerSections = document.querySelectorAll('.footer-section');
            footerSections.forEach((section, index) => {
                section.style.opacity = '0';
                section.style.transform = 'translateY(20px)';
                section.style.transition = `all 0.6s ease-out ${index * 0.1}s`;
                observer.observe(section);
            });
        }
        
        // تأثير متطور للروابط الاجتماعية
        socialLinks.forEach((link, index) => {
            link.addEventListener('mouseenter', function() {
                socialLinks.forEach((otherLink, otherIndex) => {
                    if (otherIndex !== index) {
                        otherLink.style.transform = 'scale(0.9)';
                        otherLink.style.opacity = '0.7';
                    }
                });
            });
            
            link.addEventListener('mouseleave', function() {
                socialLinks.forEach(otherLink => {
                    otherLink.style.transform = '';
                    otherLink.style.opacity = '';
                });
            });
        });
        
        // معالجة أخطاء الصور
        document.addEventListener('error', (e) => {
            if (e.target.tagName === 'IMG' && e.target.closest('.footer-component')) {
                e.target.src = 'https://via.placeholder.com/200x80/977e2b/ffffff?text=ألفا+الذهبية';
                e.target.alt = 'شعار ألفا الذهبية';
            }
        }, true);
        
        // معالجة أخطاء تحميل CSS الخارجي
        const fontAwesomeLinks = document.querySelectorAll('link[href*="font-awesome"]');
        fontAwesomeLinks.forEach(link => {
            link.addEventListener('error', function() {
                console.warn('فشل في تحميل Font Awesome من:', this.href);
                showIconFallbacks();
            });
            
            link.addEventListener('load', function() {
                console.log('تم تحميل Font Awesome بنجاح من:', this.href);
                removeLoadingClass();
            });
        });
    }
    
    // فحص تحميل Font Awesome
    function checkFontAwesome() {
        const testElement = document.createElement('i');
        testElement.className = 'fas fa-home';
        testElement.style.position = 'absolute';
        testElement.style.left = '-9999px';
        document.body.appendChild(testElement);
        
        setTimeout(() => {
            const computedStyle = window.getComputedStyle(testElement, '::before');
            const content = computedStyle.getPropertyValue('content');
            
            if (content === 'none' || content === '') {
                showIconFallbacks();
            } else {
                removeLoadingClass();
            }
            
            document.body.removeChild(testElement);
        }, 2000);
    }
    
    function showIconFallbacks() {
        const icons = document.querySelectorAll('.footer-component i');
        icons.forEach(icon => {
            const fallback = icon.nextElementSibling;
            if (fallback && fallback.classList.contains('icon-fallback')) {
                icon.style.display = 'none';
                fallback.style.display = 'inline-block';
            }
        });
    }
    
    function removeLoadingClass() {
        const loadingIcons = document.querySelectorAll('.icon-loading');
        loadingIcons.forEach(icon => {
            icon.classList.remove('icon-loading');
        });
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
    
    // إتاحة الوصول للمكون من النطاق العام للتشخيص
    window.FooterManager = {
        updateLanguage: handleLanguageChange,
        getCurrentLanguage: () => currentLanguage,
        isInitialized: () => isInitialized,
        reinit: initializeComponent
    };
    
    console.log('تم تحميل مكون الفوتر مع نظام الترجمة الكامل - بدون تكرار');
})();
</script>

<?php
// في حالة الوصول المباشر للملف
if (basename($_SERVER['PHP_SELF']) == 'footer.php') {
    echo '<!DOCTYPE html>
    <html lang="ar" dir="rtl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>مكون الفوتر بدون تكرار - ألفا الذهبية</title>
        <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200;300;400;500;600;700;800;900&family=Tajawal:wght@200;300;400;500;700;800;900&display=swap" rel="stylesheet">
    </head>
    <body class="footer-standalone">
        <div class="demo-info">
            <h1>مكون الفوتر بدون تكرار</h1>
            <p>تم إزالة جميع العناصر المكررة مع الهيدر والتكرار الداخلي</p>
            <p><strong>العناصر المتبقية (الفريدة فقط):</strong></p>
            <ul style="text-align: right; max-width: 600px; margin: 0 auto;">
                <li>الملف التعريفي للمقاولات</li>
                <li>قيمنا ومبادئنا</li>
                <li>رؤيتنا ورسالتنا</li>
                <li>من نحن</li>
                <li>معلومات الاتصال (الأرقام والعناوين)</li>
                <li>وسائل التواصل الاجتماعي</li>
                <li>حقوق النشر</li>
            </ul>
        </div>
    ';
    
    echo '
    </body>
    </html>';
}
?>