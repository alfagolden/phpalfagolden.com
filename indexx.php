<?php
// التحقق إذا كان تم الوصول إلى الصفحة بسبب خطأ
if (isset($_SERVER['REDIRECT_STATUS']) && ($_SERVER['REDIRECT_STATUS'] == 404 || $_SERVER['REDIRECT_STATUS'] == 500)) {
    echo "<div style='padding: 10px; background: #ffdddd; color: #990000; text-align:center; font-weight:bold;'>حدث خطأ في الصفحة التي طلبتها. سيتم تحويلك للرئيسية بعد 3 ثوانٍ...</div>";
    // إعادة التوجيه للرئيسية بعد 3 ثوانٍ
    header("refresh:3;url=/");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta property="og:title" content="شركة ألفا الذهبية للمصاعد والمقاولات" />
    <meta property="og:image" content="/link.jpg" />
    <meta property="og:url" content="https://alfagolden.com" />
    <meta property="og:type" content="website" />
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>شركة ألفا الذهبية للمصاعد والمقاولات</title>
    <!-- استخدام خط "Cairo" بإصدارات 400, 700 و800 (اكستر بولد) لإطلالة فخمة -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;800&display=swap" rel="stylesheet">
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

        /* إعادة تعيين الأنماط الأساسية */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        html, body {
            width: 100%;
            height: 100%;
            font-family: 'Cairo', sans-serif;
            overflow: hidden;
            position: relative;
            /* تغيير الخلفية إلى الصورة المطلوبة (ملاحظة: الصورة بيضاء) */
            background: url('/b.svg') no-repeat center center/cover;
        }
        
        p#description {
            font-weight: 900;
        }
        /* طبقة بيضاء شفافة لتفتيح الخلفية */
        .white-layer {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.5); /* 50% شفافية */
            z-index: 1;
        }
        /* إخفاء الخلفية المتحركة (canvas والـ overlay) */
        canvas#canvas,
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
            z-index: 999999 !important;
            
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
        
        /* لودر الصفحة */
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
        /* المربع الزجاجي الرئيسي */
        .glass-container {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 90%;
            max-width: 600px;
            /* تغيير تأثير الزجاج إلى تأثير أبيض خفيف */
            background: rgba(255, 255, 255, 0.0);
            border-radius: 130px 130px 50px 50px;
            backdrop-filter: blur(6px);
            border: 2px solid rgb(224, 224, 224);
            padding: 80px 20px 40px;
            text-align: center;
            z-index: 2;
        }
        /* تنسيقات الشعار */
        .logo-container {
            position: absolute;
            top: -40px;
            left: 50%;
            transform: translateX(-50%);
            background: #fff;
            padding: 10px;
            border-radius: 15px;
            width: 220px;
            height: 120px;
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
        /* تنسيقات النص الوصفي */
        .text-container {
            margin-top: 20px;
            padding: 10px;
            /* تغيير لون النص إلى الأسود */
            color: #000;
            font-size: 1rem;
            text-align: justify;
            min-height: 100px;
        }
        /* تنسيقات الأزرار */
        .buttons {
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translate(-50%, 50%);
            display: flex;
            gap: 20px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 30px;
            font-weight: 800; /* تحويل الخط إلى اكستر بولد */
            text-decoration: none;
            transition: all 0.3s ease;
            /* تغيير لون الحدود لتناسب الثيم الأبيض */
            border: 1px solid rgba(0,0,0,0.3);
            backdrop-filter: blur(5px);
        }
        /* زر المقاولات (يظل ذهبي كما هو) */
        .btn.btn-contracts {
            background: #977e2b;
            color: #fff;
            border-color: #977e2b;
        }
        .btn.btn-contracts:hover {
            background: #887127;
        }
        /* زر المصاعد */
        .btn.btn-elevators {
            background: #fff;
            color: #977e2b;
            border-color: #977e2b;
        }
        .btn.btn-elevators:hover {
            background: #f0f0f0;
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
    <canvas id="canvas"></canvas>
    <div id="overlay"></div>
    
    <!-- الطبقة البيضاء الشفافة لتفتيح الخلفية -->
    <div class="white-layer"></div>
    
    <!-- المربع الزجاجي الرئيسي -->
    <div class="glass-container">
        <!-- حاوية الشعار -->
        <div class="logo-container">
            <img src="logo.png" alt="الشعار">
        </div>
        <!-- النص الوصفي -->
        <div class="text-container">
            <p id="description" data-text-ar="شركة ألفا الذهبية للمقاولات والمصاعد هي احدى أبرز الكيانات السعودية المتخصصة في قطاع المقاولات العامة والمصاعد. تقدم الشركة خدمات متعددة في قطاع المصاعد من تركيب وصيانة المصاعد الأوربية بأنواعها المختلفة ووكلاء لابرز شركة المصاعد العالمية ، إلى جانب تنفيذ مشاريع متنوعة في مجالات المقاولات والعقارات والصناعة. يتميز نشاط الشركة بتقديم حلول شاملة مع الالتزام بأعلى معايير الجودة والكفاءة." data-text-en="Alpha Golden Contracting and Elevators Company is one of the most prominent Saudi entities specialized in the general contracting and elevators sector. The company provides multiple services in the elevator sector from installation and maintenance of European elevators of various types and agents for the most prominent international elevator companies, in addition to implementing diverse projects in the fields of contracting, real estate and industry. The company's activity is distinguished by providing comprehensive solutions with commitment to the highest standards of quality and efficiency."></p>
        </div>
        <!-- الأزرار -->
        <div class="buttons">
            <a href="/c" class="btn btn-contracts" id="contractsBtn">قطاع المقاولات</a>
            <a href="/h" class="btn btn-elevators" id="elevatorsBtn">قطاع المصاعد</a>
        </div>
    </div>
    
    <!-- زر واتساب من الكومبوننت -->
    <?php include 'components/whatsapp-button.php'; ?>
    
    <!-- مكتبة GSAP لتحريك العناصر -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.5/gsap.min.js"></script>
    
    <!-- كود خلفية WebGL الأصلية (سيظل موجوداً ولكنه غير مرئي) -->
    <script>
        // الحصول على عنصر canvas وسياق WebGL2
        const canvas = document.getElementById("canvas");
        const gl = canvas.getContext('webgl2');
        const dpr = window.devicePixelRatio || 1;
        
        // شفرة الـ vertex shader
        const vertexSource = `#version 300 es
        #ifdef GL_FRAGMENT_PRECISION_HIGH
          precision highp float;
        #else
          precision mediump float;
        #endif

        in vec2 position;
        void main(void) {
          gl_Position = vec4(position, 0.0, 1.0);
        }
        `;
        
        // شفرة الـ fragment shader (تم تعديلها لاستخدام ألوان: أسود، رمادي غامق، وذهبي داكن جديد)
        const fragmentSource = `#version 300 es
        #ifdef GL_FRAGMENT_PRECISION_HIGH
          precision highp float;
        #else
          precision mediump float;
        #endif

        uniform vec2 resolution;
        uniform float time;
        out vec4 fragColor;

        // دوال مساعدة
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
          float d = cLength(p - c * (p.x > -p.y ? s : -s), k);
          return smoothstep(b, -b, abs(d - r) - w);
        }

        void main(void) {
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
          
          // حساب الشكل الدائري باستخدام دالة signed distance
          float sdf = circle(gv, oc, 0.5, 0.125, px + f * px * zoom, 1.0, (12.0 - 11.0 * smoothstep(0.0, 1.0, 0.5 + 0.5 * cos(t))));
          
          // إعادة رسم (remap) القيمة لإيجاد حواف الشكل
          float edge = smoothstep(-0.02, 0.02, sdf);
          
          // تعريف الألوان: الذهب الداكن الجديد والرمادي الغامق؛ مع استخدام الأسود كخلفية
          vec3 darkGold = vec3(0.592, 0.494, 0.169);
          vec3 darkGray = vec3(0.15, 0.15, 0.15);
          
          // خلط اللونين بناءً على الحافة
          vec3 color = mix(darkGray, darkGold, edge);
          
          // إضافة تأثير فينيت (vignette) لتدرج اللون إلى الأسود عند الأطراف
          float vignette = smoothstep(1.0, 0.3, length(uv));
          color = mix(color, vec3(0.0), vignette);
          
          fragColor = vec4(color, 1.0);
        }
        `;
        
        let timeUniform, resolutionUniform, buffer, program;
        let vertices = [
          -1.0, -1.0,
           1.0, -1.0,
          -1.0,  1.0,
          -1.0,  1.0,
           1.0, -1.0,
           1.0,  1.0
        ];
        
        function resize() {
          const width = window.innerWidth;
          const height = window.innerHeight;
          canvas.width = width * dpr;
          canvas.height = height * dpr;
          gl.viewport(0, 0, canvas.width, canvas.height);
        }
        
        function compile(shader, source) {
          gl.shaderSource(shader, source);
          gl.compileShader(shader);
          if (!gl.getShaderParameter(shader, gl.COMPILE_STATUS)) {
            console.error(gl.getShaderInfoLog(shader));
          }
        }
        
        function setup() {
          const vs = gl.createShader(gl.VERTEX_SHADER);
          const fs = gl.createShader(gl.FRAGMENT_SHADER);
          program = gl.createProgram();
          compile(vs, vertexSource);
          compile(fs, fragmentSource);
          gl.attachShader(program, vs);
          gl.attachShader(program, fs);
          gl.linkProgram(program);
          if (!gl.getProgramParameter(program, gl.LINK_STATUS)) {
            console.error(gl.getProgramInfoLog(program));
          }
          buffer = gl.createBuffer();
          gl.bindBuffer(gl.ARRAY_BUFFER, buffer);
          gl.bufferData(gl.ARRAY_BUFFER, new Float32Array(vertices), gl.STATIC_DRAW);
          const positionAttrib = gl.getAttribLocation(program, "position");
          gl.enableVertexAttribArray(positionAttrib);
          gl.vertexAttribPointer(positionAttrib, 2, gl.FLOAT, false, 0, 0);
          timeUniform = gl.getUniformLocation(program, "time");
          resolutionUniform = gl.getUniformLocation(program, "resolution");
        }
        
        function draw(now) {
          gl.clearColor(0, 0, 0, 1);
          gl.clear(gl.COLOR_BUFFER_BIT);
          gl.useProgram(program);
          gl.bindBuffer(gl.ARRAY_BUFFER, buffer);
          gl.uniform1f(timeUniform, now * 0.001);
          gl.uniform2f(resolutionUniform, canvas.width, canvas.height);
          gl.drawArrays(gl.TRIANGLES, 0, vertices.length / 2);
        }
        
        function loop(now) {
          draw(now);
          requestAnimationFrame(loop);
        }
        
        function initWebGL() {
          setup();
          resize();
          loop(0);
        }
        
        window.addEventListener("resize", resize);
    </script>
    
    <!-- نظام الترجمة الاحترافي المحسن -->
    <script>
        /**
         * نظام إدارة اللغة المتطور للصفحة الرئيسية - الإصدار المحسن
         * Professional Language Management System for Homepage - Enhanced Version
         */
        class HomepageLanguageManager {
            constructor() {
                this.currentLanguage = 'ar';
                this.isInitialized = false;
                this.isProcessing = false;
                this.isTyping = false;
                this.typewriterTimer = null; // لحفظ مؤقت الكتابة
                this.currentTypingIndex = 0; // فهرس الحرف الحالي
                
                // ترجمات المحتوى
                this.translations = {
                    ar: {
                        contractsBtn: 'قطاع المقاولات',
                        elevatorsBtn: 'قطاع المصاعد',
                        languageTooltip: 'Switch to English'
                    },
                    en: {
                        contractsBtn: 'Contracting Sector',
                        elevatorsBtn: 'Elevators Sector',
                        languageTooltip: 'تغيير إلى العربية'
                    }
                };
                
                // عناصر DOM
                this.elements = {
                    switcher: null,
                    langText: null,
                    description: null,
                    contractsBtn: null,
                    elevatorsBtn: null
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
                    description: document.getElementById('description'),
                    contractsBtn: document.getElementById('contractsBtn'),
                    elevatorsBtn: document.getElementById('elevatorsBtn')
                };
                
                // التحقق من وجود العناصر الأساسية
                if (!this.elements.switcher || !this.elements.langText) {
                    console.error('HomepageLanguageManager: العناصر الأساسية غير موجودة');
                    return false;
                }
                
                console.log('✅ HomepageLanguageManager: تم العثور على جميع العناصر');
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
             * إيقاف عملية الكتابة الحالية
             */
            stopTypewriting() {
                if (this.typewriterTimer) {
                    clearTimeout(this.typewriterTimer);
                    this.typewriterTimer = null;
                }
                this.isTyping = false;
                this.currentTypingIndex = 0;
                console.log('⏹️ تم إيقاف عملية الكتابة');
            }
            
            /**
             * تأثير كتابة النص حرفاً حرفاً - نسخة محسنة قابلة للمقاطعة
             */
            typeWriterEffect(element, text, callback) {
                // إيقاف أي عملية كتابة سابقة
                this.stopTypewriting();
                
                this.isTyping = true;
                element.innerHTML = "";
                this.currentTypingIndex = 0;
                
                const typeChar = () => {
                    // التحقق من عدم إيقاف العملية
                    if (!this.isTyping || this.currentTypingIndex >= text.length) {
                        if (this.currentTypingIndex >= text.length) {
                            this.isTyping = false;
                            if (callback) callback();
                        }
                        return;
                    }
                    
                    element.innerHTML += text.charAt(this.currentTypingIndex);
                    this.currentTypingIndex++;
                    
                    // استخدام setTimeout بدلاً من الاستدعاء المباشر لجعل العملية قابلة للمقاطعة
                    this.typewriterTimer = setTimeout(typeChar, 20);
                };
                
                typeChar();
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
                
                // تحديث نصوص الأزرار
                if (this.elements.contractsBtn) this.elements.contractsBtn.textContent = texts.contractsBtn;
                if (this.elements.elevatorsBtn) this.elements.elevatorsBtn.textContent = texts.elevatorsBtn;
                
                // تحديث النص الوصفي مع تأثير الكتابة
                if (this.elements.description) {
                    const descText = this.currentLanguage === 'ar' ? 
                        this.elements.description.getAttribute('data-text-ar') : 
                        this.elements.description.getAttribute('data-text-en');
                    
                    if (descText) {
                        this.typeWriterEffect(this.elements.description, descText);
                    }
                }
                
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
             * تبديل اللغة - نسخة محسنة تعمل دائماً
             */
            toggleLanguage() {
                // إزالة شرط isTyping للسماح بالتبديل في أي وقت
                if (this.isProcessing) {
                    console.log('⏳ عملية التبديل قيد التنفيذ...');
                    return;
                }
                
                this.isProcessing = true;
                
                // إيقاف عملية الكتابة فوراً إذا كانت جارية
                if (this.isTyping) {
                    this.stopTypewriting();
                    console.log('⏹️ تم إيقاف الكتابة لتبديل اللغة');
                }
                
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
                
                console.log('🚀 بدء تهيئة نظام اللغة للصفحة الرئيسية...');
                
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
                console.log('✅ تم تهيئة نظام اللغة للصفحة الرئيسية بنجاح');
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
        let homepageLanguageManager;
        
        // تهيئة فورية عند تحميل DOM
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                homepageLanguageManager = new HomepageLanguageManager();
                homepageLanguageManager.initialize();
            });
        } else {
            // DOM جاهز بالفعل
            homepageLanguageManager = new HomepageLanguageManager();
            homepageLanguageManager.initialize();
        }
        
        // تصدير للوصول العام
        window.HomepageLanguageManager = {
            getCurrentLanguage: () => homepageLanguageManager ? homepageLanguageManager.getCurrentLanguage() : 'ar',
            changeLanguage: (lang) => {
                if (homepageLanguageManager && (lang === 'ar' || lang === 'en')) {
                    homepageLanguageManager.currentLanguage = lang;
                    homepageLanguageManager.updateTexts();
                    homepageLanguageManager.saveCurrentLanguage();
                    homepageLanguageManager.broadcastLanguageChange();
                }
            },
            toggleLanguage: () => homepageLanguageManager ? homepageLanguageManager.toggleLanguage() : null,
            isRTL: () => homepageLanguageManager ? homepageLanguageManager.isRTL() : true
        };
    </script>
    
    <!-- دمج تحميل الصفحة: تشغيل GSAP والمؤثرات وخلفية WebGL (غير المرئية) -->
    <script>
        window.onload = function() {
            // إزالة اللودر بعد تحميل الصفحة
            gsap.to("#loader", {
                duration: 0.5,
                opacity: 0,
                onComplete: function() {
                    document.getElementById("loader").style.display = "none";
                    document.body.style.overflow = "auto";
                }
            });
            
            // تحريك ظهور مكونات الصفحة
            gsap.from(".glass-container", { duration: 1, opacity: 0, y: 30, ease: "power2.out" });
            gsap.from(".logo-container", { duration: 1, opacity: 0, y: -30, delay: 0.5, ease: "power2.out" });
            gsap.from(".text-container", { duration: 1, opacity: 0, y: 20, delay: 0.7, ease: "power2.out" });
            gsap.from(".buttons", { duration: 1, opacity: 0, y: 20, delay: 0.9, ease: "power2.out" });
            
            // تأثير كتابة النص حرفاً حرفاً (سيتم تشغيله من خلال updateTexts)
            setTimeout(() => {
                if (homepageLanguageManager && !homepageLanguageManager.isTyping && homepageLanguageManager.elements.description) {
                    const textKey = homepageLanguageManager.currentLanguage === 'ar' ? 'data-text-ar' : 'data-text-en';
                    const fullText = homepageLanguageManager.elements.description.getAttribute(textKey);
                    if (fullText) {
                        homepageLanguageManager.typeWriterEffect(homepageLanguageManager.elements.description, fullText);
                    }
                }
            }, 1500); // تأخير لانتظار انتهاء الحركات
            
            // تهيئة خلفية WebGL (ستعمل لكن غير مرئية)
            initWebGL();
        };

        console.log('📄 تم تحميل الصفحة الرئيسية مع نظام اللغة المحسن');
    </script>
    
    

    
    
</body>
</html>