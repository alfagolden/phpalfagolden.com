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
<title>شركة ألفا الذهبية للمقاولات</title>  <!-- خط Cairo -->
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
  <!-- Font Awesome للأيقونات -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    /* CSS Variables للحجم الديناميكي */
    :root {
      --lang-btn-scale: 1;
      --lang-btn-width: calc(85px * var(--lang-btn-scale));
      --lang-btn-height: calc(38px * var(--lang-btn-scale));
      --lang-btn-font: calc(12px * var(--lang-btn-scale));
      --lang-btn-icon: calc(14px * var(--lang-btn-scale));
      --lang-btn-gap: calc(6px * var(--lang-btn-scale));
      --lang-btn-padding: calc(8px * var(--lang-btn-scale));
      --lang-btn-radius: calc(19px * var(--lang-btn-scale));
      --lang-btn-top: calc(20px * var(--lang-btn-scale));
      --lang-btn-right: calc(20px * var(--lang-btn-scale));
    }

    /* تعديل المتغيرات حسب حجم الشاشة */
    @media screen and (max-width: 1440px) {
      :root { --lang-btn-scale: 0.95; }
    }
    
    @media screen and (max-width: 1200px) {
      :root { --lang-btn-scale: 0.9; }
    }
    
    @media screen and (max-width: 992px) {
      :root { --lang-btn-scale: 0.85; }
    }
    
    @media screen and (max-width: 768px) {
      :root { --lang-btn-scale: 0.8; }
    }
    
    @media screen and (max-width: 576px) {
      :root { --lang-btn-scale: 0.75; }
    }
    
    @media screen and (max-width: 480px) {
      :root { --lang-btn-scale: 0.7; }
    }
    
    @media screen and (max-width: 360px) {
      :root { --lang-btn-scale: 0.65; }
    }

    /* إعادة ضبط الأنماط */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    html, body {
      width: 100%;
      height: 100%;
      font-family: 'Cairo', sans-serif;
      background: url('/b.svg') no-repeat center center/cover;
      overflow: hidden;
      position: relative;
    }
    
    /* طبقة بيضاء شفافة لتفتيح الخلفية */
    .white-layer {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(255, 255, 255, 0.5);
      z-index: 1;
    }
    
    /* إخفاء خلفية WebGL */
    canvas#bg-canvas,
    #overlay {
      display: none;
    }
    
    /* === زر اللغة الاحترافي === */
    .language-switcher {
      position: fixed !important;
      top: var(--lang-btn-top) !important;
      right: var(--lang-btn-right) !important;
      width: var(--lang-btn-width) !important;
      height: var(--lang-btn-height) !important;
      z-index: 99 !important;
      
      /* التصميم */
      background: rgba(255, 255, 255, 0.95) !important;
      border: 2px solid rgba(151, 126, 43, 0.25) !important;
      border-radius: var(--lang-btn-radius) !important;
      backdrop-filter: blur(12px) !important;
      -webkit-backdrop-filter: blur(12px) !important;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08), 0 1px 4px rgba(0, 0, 0, 0.04) !important;
      
      /* المحاذاة */
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      gap: var(--lang-btn-gap) !important;
      
      /* التفاعل */
      cursor: pointer !important;
      user-select: none !important;
      touch-action: manipulation !important;
      
      /* الانتقالات */
      transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
      
      /* ضمان الظهور */
      visibility: visible !important;
      opacity: 1 !important;
      pointer-events: auto !important;
      
      /* الخط */
      font-family: 'Cairo', sans-serif !important;
      font-size: var(--lang-btn-font) !important;
      font-weight: 600 !important;
      color: #2c2c2c !important;
    }

    /* حالات التفاعل */
    .language-switcher:hover {
      background: rgba(151, 126, 43, 0.08) !important;
      border-color: rgba(151, 126, 43, 0.4) !important;
      transform: translateY(-1px) !important;
      box-shadow: 0 6px 25px rgba(151, 126, 43, 0.15), 0 2px 8px rgba(0, 0, 0, 0.06) !important;
      color: #977e2b !important;
    }

    .language-switcher:active {
      transform: translateY(0) scale(0.98) !important;
      transition: all 0.1s ease !important;
    }

    /* أيقونة العالم */
    .lang-icon {
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      color: #977e2b !important;
      font-size: var(--lang-btn-icon) !important;
      width: var(--lang-btn-icon) !important;
      height: var(--lang-btn-icon) !important;
      transition: transform 0.2s ease !important;
    }

    .language-switcher:hover .lang-icon {
      transform: rotate(15deg) !important;
    }

    /* نص اللغة */
    .lang-text {
      font-size: var(--lang-btn-font) !important;
      font-weight: 600 !important;
      letter-spacing: 0.3px !important;
      white-space: nowrap !important;
      margin: 0 !important;
      padding: 0 !important;
    }

    /* تأثير النقر */
    .language-switcher.clicked {
      animation: btnPulse 0.3s ease !important;
    }

    @keyframes btnPulse {
      0% { transform: scale(1); }
      50% { transform: scale(0.95); }
      100% { transform: scale(1); }
    }

    /* === باقي الأنماط === */
    #loader {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 50000;
    }
    
    .spinner {
      border: 8px solid rgba(0,0,0,0.3);
      border-top: 8px solid #908400;
      border-radius: 50%;
      width: 60px;
      height: 60px;
      animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    
    .glass-container {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      width: 90%;
      max-width: 600px;
      background: rgba(255, 255, 255, 0.0);
      border-radius: 130px 130px 50px 50px;
      backdrop-filter: blur(6px);
      border: 2px solid rgb(224, 224, 224);
      padding: 80px 20px 40px;
      text-align: center;
      z-index: 2;
    }
    
    .logo-container {
      position: absolute;
      top: -80px;
      left: 50%;
      transform: translateX(-50%);
      width: 220px;
      padding: 10px;
      border-radius: 15px;
      background: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    }
    
    .logo-container img {
      width: 100%;
      height: auto;
      object-fit: contain;
    }
    
    .glass-container h2 {
      color: #000;
      margin-bottom: 20px;
      font-size: 1.5rem;
    }
    
    .services-grid {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 20px;
      margin-top: 20px;
    }
    
    .service-item {
      display: block;
      width: 80%;
      margin: 0 auto;
      padding: 10px 20px;
      text-align: center;
      font-size: 1.1rem;
      font-weight: bold;
      border-radius: 30px;
      border: 1px solid transparent;
      cursor: pointer;
      transition: transform 0.3s ease, background 0.3s ease;
      text-decoration: none;
    }
    
    .gold-btn {
      background: #977e2b;
      color: #fff;
      border-color: #977e2b;
    }
    
    .gold-btn:hover {
      background: #c49b30;
    }
    
    .white-btn {
      background: #fff;
      color: #977e2b;
      border-color: #977e2b;
    }
    
    .white-btn:hover {
      background: #f0f0f0;
    }
    
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
    
    @media (max-width: 768px) {
      .glass-container {
        transform: translate(-50%, -50%) scale(0.8);
      }
      .logo-container {
        transform: translateX(-50%) scale(0.8);
      }
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
  <!-- زر تغيير اللغة الاحترافي -->
  <button class="language-switcher" id="languageSwitcher" type="button" aria-label="تغيير اللغة">
    <i class="fas fa-globe lang-icon" id="langIcon" aria-hidden="true"></i>
    <span class="lang-text" id="langText">English</span>
  </button>

  <!-- لودر الصفحة -->
  <div id="loader">
    <div class="spinner"></div>
  </div>
  
  <!-- خلفية WebGL الأصلية (ستظل موجودة بالكود لكنها مخفية) -->
  <canvas id="bg-canvas"></canvas>
  <div id="overlay"></div>
  
  <!-- الطبقة البيضاء الشفافة لتفتيح الخلفية -->
  <div class="white-layer"></div>
  
  <!-- الحاوية الزجاجية (في وسط الصفحة) -->
  <div class="glass-container">
    <!-- حاوية الشعار -->
    <div class="logo-container">
      <img src="logo.png" alt="الشعار">
    </div>
    <!-- عنوان الصفحة -->
    <h2 id="pageTitle">شركة ألفا الذهبية للمقاولات</h2>
    <!-- قائمة الأزرار: كل زر في صف منفصل -->
    <div class="services-grid">
      <a href="#" class="service-item gold-btn gallery-trigger-link" data-gallery-id="3" id="newsBtn">
        آخر الأخبار
      </a>
      <a href="#" class="service-item white-btn gallery-trigger-link" data-gallery-id="10" id="profileBtn">
        بروفايل
      </a>
      <a href="#" class="service-item gold-btn gallery-trigger-link" data-gallery-id="4" id="certificatesBtn">
        الشهادات
      </a>
      <a href="https://alfagolden.com/op.php" class="service-item white-btn" id="projectsBtn">
        مشاريعنا
      </a>
      <a href="#" class="service-item gold-btn gallery-trigger-link" data-gallery-id="1" id="participationBtn">
        مشاركاتنا
      </a>
    </div>
  </div>
  
  <!-- إضافة كومبوننت الواتس اب -->
  <?php include 'components/whatsapp-button.php'; ?>
  
  <!-- إضافة معرض الصور -->
  <?php include 'components/image-gallery.php'; ?>
  
  <!-- مكتبة GSAP -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.5/gsap.min.js"></script>
  
  <!-- كود WebGL (يعمل في الخلفية لكنه غير مرئي) -->
  <script>
    const canvas = document.getElementById("bg-canvas");
    const gl = canvas.getContext('webgl2');
    const dpr = window.devicePixelRatio || 1;
    
    const vertexSource = `#version 300 es
      precision mediump float;
      in vec2 position;
      void main(void){
        gl_Position = vec4(position, 0.0, 1.0);
      }`;
    
    const fragmentSource = `#version 300 es
      precision mediump float;
      out vec4 fragColor;
      uniform vec2 resolution;
      uniform float time;
      
      float hash21(vec2 p) {
        p = fract(p * vec2(324.967, 509.314));
        p += dot(p, p + 75.09);
        return fract(p.x * p.y);
      }
      float flip(vec2 p) {
        float rand = hash21(p);
        return rand > 0.5 ? 1.0 : -1.0;
      }
      mat2 rot(float a) {
        float s = sin(a), c = cos(a);
        return mat2(c, -s, s, c);
      }
      float cLength(vec2 p, float k) {
        p = abs(p);
        return pow(pow(p.x, k) + pow(p.y, k), 1.0/k);
      }
      float circle(vec2 p, vec2 c, float r, float w, float b, float s, float k) {
        float d = cLength(p - c*(p.x > -p.y ? s : -s), k);
        return smoothstep(b, -b, abs(d - r) - w);
      }
      
      void main() {
        float t = time * 0.125;
        float f = 0.5 + 0.5 * sin(t);
        float zoom = 5.0;
        float mn = min(resolution.x, resolution.y);
        float rhm = mn - mn * 0.9 * f;
        float px = zoom / rhm;
        
        vec2 uv = (gl_FragCoord.xy - 0.5 * resolution.xy) / rhm;
        uv *= zoom;
        uv *= rot(time * 0.1);
        
        vec2 gv = fract(uv) - 0.5;
        vec2 id = floor(uv);
        
        vec2 oc = vec2(0.5);
        gv.x *= flip(id);
        
        float sdf = circle(gv, oc, 0.5, 0.125, px + f * px * zoom, 1.0,
                           12.0 - 11.0 * smoothstep(0.0, 1.0, 0.5 + 0.5 * cos(t)));
                           
        float edge = smoothstep(-0.02, 0.02, sdf);
        
        vec3 darkGold = vec3(0.72, 0.525, 0.043);
        vec3 darkGray = vec3(0.15, 0.15, 0.15);
        
        vec3 color = mix(darkGray, darkGold, edge);
        
        float vignette = smoothstep(1.0, 0.3, length(uv));
        color = mix(color, vec3(0.0), vignette);
        
        fragColor = vec4(color, 1.0);
      }`;
    
    let timeUniform, resolutionUniform, program, buffer;
    const vertices = new Float32Array([
      -1.0, -1.0,
       1.0, -1.0,
      -1.0,  1.0,
      -1.0,  1.0,
       1.0, -1.0,
       1.0,  1.0
    ]);
    
    function resize() {
      const w = window.innerWidth;
      const h = window.innerHeight;
      canvas.width = w * dpr;
      canvas.height = h * dpr;
      gl.viewport(0, 0, canvas.width, canvas.height);
    }
    window.addEventListener('resize', resize);
    
    function compileShader(shader, source) {
      gl.shaderSource(shader, source);
      gl.compileShader(shader);
      if (!gl.getShaderParameter(shader, gl.COMPILE_STATUS)) {
        console.error(gl.getShaderInfoLog(shader));
      }
    }
    
    function initWebGL() {
      if (!gl) return;
      
      const vs = gl.createShader(gl.VERTEX_SHADER);
      const fs = gl.createShader(gl.FRAGMENT_SHADER);
      program = gl.createProgram();
      
      compileShader(vs, vertexSource);
      compileShader(fs, fragmentSource);
      
      gl.attachShader(program, vs);
      gl.attachShader(program, fs);
      gl.linkProgram(program);
      if (!gl.getProgramParameter(program, gl.LINK_STATUS)) {
        console.error(gl.getProgramInfoLog(program));
      }
      
      buffer = gl.createBuffer();
      gl.bindBuffer(gl.ARRAY_BUFFER, buffer);
      gl.bufferData(gl.ARRAY_BUFFER, vertices, gl.STATIC_DRAW);
      
      gl.useProgram(program);
      const posLocation = gl.getAttribLocation(program, 'position');
      gl.enableVertexAttribArray(posLocation);
      gl.vertexAttribPointer(posLocation, 2, gl.FLOAT, false, 0, 0);
      
      timeUniform = gl.getUniformLocation(program, 'time');
      resolutionUniform = gl.getUniformLocation(program, 'resolution');
      
      resize();
      requestAnimationFrame(render);
    }
    
    function render(t) {
      gl.clearColor(0, 0, 0, 1);
      gl.clear(gl.COLOR_BUFFER_BIT);
      gl.useProgram(program);
      gl.uniform1f(timeUniform, t * 0.001);
      gl.uniform2f(resolutionUniform, canvas.width, canvas.height);
      gl.drawArrays(gl.TRIANGLES, 0, 6);
      requestAnimationFrame(render);
    }
  </script>
  
  <!-- نظام الترجمة الاحترافي -->
  <script>
    /**
     * نظام إدارة اللغة المتطور
     * Professional Language Management System
     */
    class LanguageManager {
      constructor() {
        this.currentLanguage = 'ar';
        this.isInitialized = false;
        this.isProcessing = false;
        
        // ترجمات المحتوى
        this.translations = {
          ar: {
            pageTitle: 'شركة ألفا الذهبية للمقاولات',
            newsBtn: 'آخر الأخبار',
            profileBtn: 'بروفايل',
            certificatesBtn: 'الشهادات',
            projectsBtn: 'مشاريعنا',
            participationBtn: 'مشاركاتنا',
            languageTooltip: 'Switch to English'
          },
          en: {
            pageTitle: 'Alpha Golden Contracting Company',
            newsBtn: 'Latest News',
            profileBtn: 'Profile',
            certificatesBtn: 'Certificates',
            projectsBtn: 'Our Projects',
            participationBtn: 'Our Participations',
            languageTooltip: 'تغيير إلى العربية'
          }
        };
        
        // عناصر DOM
        this.elements = {
          switcher: null,
          langText: null,
          pageTitle: null,
          newsBtn: null,
          profileBtn: null,
          certificatesBtn: null,
          projectsBtn: null,
          participationBtn: null
        };
        
        this.initializeElements();
        this.loadSavedLanguage();
      }
      
      /**
       * تهيئة عناصر DOM
       */
      initializeElements() {
        this.elements = {
          switcher: document.getElementById('languageSwitcher'),
          langText: document.getElementById('langText'),
          pageTitle: document.getElementById('pageTitle'),
          newsBtn: document.getElementById('newsBtn'),
          profileBtn: document.getElementById('profileBtn'),
          certificatesBtn: document.getElementById('certificatesBtn'),
          projectsBtn: document.getElementById('projectsBtn'),
          participationBtn: document.getElementById('participationBtn')
        };
        
        // التحقق من وجود العناصر الأساسية
        if (!this.elements.switcher || !this.elements.langText) {
          console.error('LanguageManager: العناصر الأساسية غير موجودة');
          return false;
        }
        
        console.log('✅ LanguageManager: تم العثور على جميع العناصر');
        return true;
      }
      
      /**
       * تحميل اللغة المحفوظة
       */
      loadSavedLanguage() {
        try {
          const saved = localStorage.getItem('siteLanguage');
          if (saved && (saved === 'ar' || saved === 'en')) {
            this.currentLanguage = saved;
            console.log(`📁 تم تحميل اللغة المحفوظة: ${saved}`);
          }
        } catch (error) {
          console.warn('⚠️ خطأ في تحميل اللغة:', error);
        }
      }
      
      /**
       * حفظ اللغة الحالية
       */
      saveCurrentLanguage() {
        try {
          localStorage.setItem('siteLanguage', this.currentLanguage);
          console.log(`💾 تم حفظ اللغة: ${this.currentLanguage}`);
        } catch (error) {
          console.warn('⚠️ خطأ في حفظ اللغة:', error);
        }
      }
      
      /**
       * تحديث جميع النصوص
       */
      updateTexts() {
        if (!this.translations[this.currentLanguage]) {
          console.error(`❌ ترجمات غير موجودة للغة: ${this.currentLanguage}`);
          return;
        }
        
        const texts = this.translations[this.currentLanguage];
        
        // تحديث النصوص
        Object.keys(texts).forEach(key => {
          if (key !== 'languageTooltip' && this.elements[key]) {
            this.elements[key].textContent = texts[key];
          }
        });
        
        // تحديث نص زر اللغة
        if (this.elements.langText) {
          this.elements.langText.textContent = this.currentLanguage === 'ar' ? 'English' : 'العربية';
        }
        
        // تحديث tooltip
        if (this.elements.switcher) {
          this.elements.switcher.setAttribute('aria-label', texts.languageTooltip);
          this.elements.switcher.title = texts.languageTooltip;
        }
        
        // تحديث اتجاه الصفحة
        this.updatePageDirection();
        
        console.log(`🔄 تم تحديث النصوص للغة: ${this.currentLanguage}`);
      }
      
      /**
       * تحديث اتجاه الصفحة
       */
      updatePageDirection() {
        const html = document.documentElement;
        
        if (this.currentLanguage === 'ar') {
          html.setAttribute('dir', 'rtl');
          html.setAttribute('lang', 'ar');
        } else {
          html.setAttribute('dir', 'ltr');
          html.setAttribute('lang', 'en');
        }
      }
      
      /**
       * تبديل اللغة
       */
      toggleLanguage() {
        if (this.isProcessing) {
          console.log('⏳ عملية التبديل قيد التنفيذ...');
          return;
        }
        
        this.isProcessing = true;
        
        const newLanguage = this.currentLanguage === 'ar' ? 'en' : 'ar';
        
        console.log(`🔄 تبديل اللغة من ${this.currentLanguage} إلى ${newLanguage}`);
        
        // تأثير بصري للنقر
        if (this.elements.switcher) {
          this.elements.switcher.classList.add('clicked');
          setTimeout(() => {
            this.elements.switcher.classList.remove('clicked');
          }, 300);
        }
        
        // تغيير اللغة
        this.currentLanguage = newLanguage;
        this.updateTexts();
        this.saveCurrentLanguage();
        
        // إرسال حدث التغيير
        this.broadcastLanguageChange();
        
        // إنهاء المعالجة
        setTimeout(() => {
          this.isProcessing = false;
          console.log(`✅ تم التبديل بنجاح إلى: ${this.currentLanguage}`);
        }, 100);
      }
      
      /**
       * إرسال حدث تغيير اللغة
       */
      broadcastLanguageChange() {
        const event = new CustomEvent('siteLanguageChanged', {
          detail: { 
            language: this.currentLanguage,
            isRTL: this.currentLanguage === 'ar',
            translations: this.translations[this.currentLanguage]
          }
        });
        
        document.dispatchEvent(event);
        console.log(`📡 تم إرسال حدث تغيير اللغة: ${this.currentLanguage}`);
      }
      
      /**
       * ربط الأحداث
       */
      bindEvents() {
        if (!this.elements.switcher) {
          console.error('❌ لا يمكن ربط الأحداث - زر اللغة غير موجود');
          return;
        }
        
        // إزالة المستمعين السابقين لتجنب التداخل
        this.elements.switcher.removeEventListener('click', this.handleClick.bind(this));
        
        // ربط حدث النقر الجديد
        this.elements.switcher.addEventListener('click', this.handleClick.bind(this));
        
        // ربط أحداث إضافية للتأكد
        this.elements.switcher.addEventListener('touchend', this.handleTouch.bind(this));
        
        console.log('🔗 تم ربط أحداث زر اللغة');
      }
      
      /**
       * معالج النقر
       */
      handleClick(event) {
        event.preventDefault();
        event.stopPropagation();
        
        console.log('👆 تم النقر على زر اللغة');
        this.toggleLanguage();
      }
      
      /**
       * معالج اللمس
       */
      handleTouch(event) {
        event.preventDefault();
        event.stopPropagation();
        
        console.log('👆 تم لمس زر اللغة');
        this.toggleLanguage();
      }
      
      /**
       * التهيئة الكاملة
       */
      initialize() {
        if (this.isInitialized) {
          console.log('⚠️ النظام مهيأ مسبقاً');
          return;
        }
        
        console.log('🚀 بدء تهيئة نظام اللغة...');
        
        // التحقق من العناصر
        if (!this.initializeElements()) {
          console.error('❌ فشل في العثور على العناصر المطلوبة');
          return;
        }
        
        // تحديث النصوص الأولي
        this.updateTexts();
        
        // ربط الأحداث
        this.bindEvents();
        
        // إرسال الحالة الأولية
        setTimeout(() => {
          this.broadcastLanguageChange();
        }, 100);
        
        this.isInitialized = true;
        console.log('✅ تم تهيئة نظام اللغة بنجاح');
      }
      
      /**
       * الحصول على اللغة الحالية
       */
      getCurrentLanguage() {
        return this.currentLanguage;
      }
      
      /**
       * فحص ما إذا كانت اللغة RTL
       */
      isRTL() {
        return this.currentLanguage === 'ar';
      }
    }
    
    // إنشاء مثيل النظام
    let languageManager;
    
    // تهيئة فورية عند تحميل DOM
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', function() {
        languageManager = new LanguageManager();
        languageManager.initialize();
      });
    } else {
      // DOM جاهز بالفعل
      languageManager = new LanguageManager();
      languageManager.initialize();
    }
    
    // تصدير للوصول العام
    window.ContractsLanguageManager = {
      getCurrentLanguage: () => languageManager ? languageManager.getCurrentLanguage() : 'ar',
      changeLanguage: (lang) => {
        if (languageManager && (lang === 'ar' || lang === 'en')) {
          languageManager.currentLanguage = lang;
          languageManager.updateTexts();
          languageManager.saveCurrentLanguage();
          languageManager.broadcastLanguageChange();
        }
      },
      toggleLanguage: () => languageManager ? languageManager.toggleLanguage() : null,
      isRTL: () => languageManager ? languageManager.isRTL() : true
    };
  </script>
  
  <!-- سكريبت الصفحة -->
  <script>
    window.onload = () => {
      // تهيئة WebGL
      initWebGL();
      
      // إزالة اللودر
      gsap.to("#loader", {
        duration: 0.5,
        opacity: 0,
        onComplete: () => {
          document.getElementById("loader").style.display = "none";
          document.body.style.overflow = "auto";
        }
      });
      
      // حركة دخول الحاوية ببطء
      gsap.from(".glass-container", { 
        duration: 1.5, 
        opacity: 0, 
        y: -50, 
        ease: "power2.out"
      });
      
      // عرض الأزرار زر زر (stagger)
      gsap.from(".service-item", { 
        duration: 1, 
        opacity: 0, 
        stagger: 0.5, 
        ease: "power2.out", 
        delay: 1.5 
      });
      
      // معالجة النقر على روابط المعرض فقط (ليس زر مشاريعنا)
      document.addEventListener('click', function(e) {
        const galleryLink = e.target.closest('.gallery-trigger-link');
        if (galleryLink) {
          e.preventDefault();
          e.stopPropagation();
          
          const galleryId = galleryLink.getAttribute('data-gallery-id');
          if (galleryId) {
            if (typeof window.openImageGallery === 'function') {
              console.log('فتح معرض رقم:', galleryId);
              window.openImageGallery(galleryId);
            } else {
              console.error('دالة فتح المعرض غير متوفرة - تأكد من تحميل مكون المعرض');
              alert('مكون المعرض غير متوفر حالياً');
            }
          }
        }
      });
    };
    
    console.log('📄 تم تحميل صفحة المقاولات مع نظام اللغة المتطور');
  </script>
  
  
  

  
  
  
</body>
</html>