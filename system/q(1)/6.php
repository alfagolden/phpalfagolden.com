<?php
// احصل على معرف العرض من الرابط
$quote_id = isset($_GET['quote_id']) ? intval($_GET['quote_id']) : 1;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تحويل عرض السعر إلى PDF</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    
    <style>
        :root {
            --gold: #977e2b;
            --gold-hover: #b89635;
            --success: #28a745;
            --error: #dc3545;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-hover) 100%);
            overflow: hidden;
        }

        /* Loading Screen */
        .loader-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-hover) 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            z-index: 10000;
        }

        .loader-logo {
            width: 120px;
            height: auto;
            margin-bottom: 30px;
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .loader {
            width: 80px;
            height: 80px;
            border: 6px solid rgba(255, 255, 255, 0.3);
            border-top: 6px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 25px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loader-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 10px;
            text-align: center;
        }

        .progress-bar {
            width: 300px;
            height: 6px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 15px;
        }

        .progress-fill {
            height: 100%;
            background: white;
            border-radius: 3px;
            width: 0%;
            transition: width 0.3s ease;
        }

        .progress-text {
            font-size: 14px;
            opacity: 0.8;
            text-align: center;
        }

        .success-message {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: var(--success);
            color: white;
            padding: 25px 40px;
            border-radius: 15px;
            font-size: 18px;
            font-weight: 600;
            text-align: center;
            box-shadow: 0 10px 30px rgba(40, 167, 69, 0.4);
            z-index: 10001;
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .success-message i {
            font-size: 24px;
            margin-bottom: 10px;
            display: block;
        }

        /* Hidden iframe */
        .content-loader {
            position: absolute;
            top: -9999px;
            left: -9999px;
            width: 1200px;
            height: 800px;
            border: none;
            background: white;
        }

        /* مساحة العمل المخفية */
        .pdf-workspace {
            position: absolute;
            top: -9999px;
            left: -9999px;
            background: white;
            font-family: 'Cairo', sans-serif;
            direction: rtl;
        }

        /* صفحات PDF - A4 الحقيقي */
        .pdf-page {
            width: 210mm;
            height: 297mm;
            background: white;
            position: relative;
            page-break-after: always;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        /* صفحة الغلاف - نسخة من الأصل */
        .pdf-page.cover-page {
            padding: 0;
        }

        /* صفحة المحتوى */
        .pdf-page.content-page {
            padding: 15mm 10mm 10mm 10mm;
        }

        /* هيدر الصفحة */
        .page-header {
            position: absolute;
            top: 5mm;
            left: 10mm;
            right: 10mm;
            height: 10mm;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 2mm;
        }

        .page-header img {
            height: 8mm;
            width: auto;
        }

        /* محتوى الصفحة */
        .page-content {
            margin-top: 5mm;
            flex: 1;
            overflow: hidden;
        }

        /* منع قطع العناصر */
        .card, .hero-card, .final-cover {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }
    </style>
</head>
<body>
    <!-- شاشة التحميل -->
    <div class="loader-screen" id="loaderScreen">
        <img src="https://alfagolden.com/images/logo.png" alt="الشعار" class="loader-logo">
        <div class="loader"></div>
        <div class="loader-title">جارٍ إنشاء ملف PDF</div>
        <div class="progress-bar">
            <div class="progress-fill" id="progressFill"></div>
        </div>
        <div class="progress-text" id="progressText">جارٍ تحميل المحتوى...</div>
    </div>

    <!-- رسالة النجاح -->
    <div class="success-message" id="successMessage">
        <i class="fas fa-check-circle"></i>
        تم إنشاء ملف PDF بنجاح!<br>
        <small>سيبدأ التحميل خلال ثوانٍ قليلة...</small>
    </div>

    <!-- إطار مخفي لتحميل المحتوى الأصلي -->
    <iframe class="content-loader" id="contentLoader" 
            src="5.php?quote_id=<?= htmlspecialchars($quote_id, ENT_QUOTES, 'UTF-8') ?>"></iframe>

    <!-- مساحة عمل مخفية -->
    <div class="pdf-workspace" id="pdfWorkspace"></div>

    <!-- مكتبات JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>

    <script>
        class PDFGenerator {
            constructor() {
                this.iframe = document.getElementById('contentLoader');
                this.workspace = document.getElementById('pdfWorkspace');
                this.progressFill = document.getElementById('progressFill');
                this.progressText = document.getElementById('progressText');
                this.pages = [];
                this.init();
            }

            init() {
                console.log('🚀 بدء إنشاء PDF...');
                this.updateProgress(0, 'جارٍ التحضير...');

                // انتظار تحميل المكتبات
                this.waitForLibraries(() => {
                    this.updateProgress(20, 'تم تحميل المكتبات...');

                    const timeout = setTimeout(() => {
                        this.showError('انتهت مهلة تحميل المحتوى');
                    }, 15000);

                    this.iframe.onload = () => {
                        clearTimeout(timeout);
                        console.log('✅ تم تحميل المحتوى');
                        setTimeout(() => this.processContent(), 1500);
                    };

                    this.iframe.onerror = () => {
                        clearTimeout(timeout);
                        this.showError('فشل في تحميل المحتوى');
                    };
                });
            }

            waitForLibraries(callback) {
                const checkLibs = () => {
                    if (typeof html2canvas !== 'undefined' && typeof window.jspdf !== 'undefined') {
                        callback();
                    } else {
                        setTimeout(checkLibs, 100);
                    }
                };
                checkLibs();
            }

            updateProgress(percentage, text) {
                this.progressFill.style.width = percentage + '%';
                this.progressText.textContent = text;
                console.log(`📊 ${percentage}% - ${text}`);
            }

            async processContent() {
                try {
                    this.updateProgress(40, 'جارٍ استخراج المحتوى...');
                    
                    const iframeDoc = this.iframe.contentDocument;
                    if (!iframeDoc) throw new Error('لا يمكن الوصول للمحتوى');

                    // نسخ جميع الستايلات من الملف الأصلي
                    await this.copyStyles(iframeDoc);
                    
                    // استخراج العناصر الأساسية
                    const originalElements = this.extractElements(iframeDoc);
                    
                    this.updateProgress(60, 'جارٍ تنظيم الصفحات...');
                    await this.createPDFPages(originalElements);
                    
                    this.updateProgress(80, 'جارٍ إنشاء PDF...');
                    await this.generatePDF();
                    
                } catch (error) {
                    console.error('❌ خطأ:', error);
                    this.showError(error.message);
                }
            }

            async copyStyles(iframeDoc) {
                // نسخ جميع ستايلات CSS من الملف الأصلي
                const styles = iframeDoc.querySelectorAll('style, link[rel="stylesheet"]');
                styles.forEach(style => {
                    const newStyle = document.createElement('style');
                    if (style.tagName === 'STYLE') {
                        newStyle.textContent = style.textContent;
                    } else if (style.href) {
                        // للـ external stylesheets، نحاول نسخ المحتوى
                        newStyle.textContent = `@import url("${style.href}");`;
                    }
                    document.head.appendChild(newStyle);
                });
                console.log('✅ تم نسخ الستايلات');
            }

            extractElements(iframeDoc) {
                const elements = {
                    heroCard: null,
                    contentCards: [],
                    finalCover: null
                };

                // 1. غلاف البداية
                const heroCard = iframeDoc.querySelector('.hero-card');
                if (heroCard) {
                    elements.heroCard = heroCard.cloneNode(true);
                }

                // 2. البطاقات الوسطى (المحتوى)
                const contentCards = iframeDoc.querySelectorAll('.card:not(.hero-card):not(.final-cover)');
                contentCards.forEach(card => {
                    const cardBody = card.querySelector('.card-body');
                    if (cardBody && cardBody.textContent.trim().length > 10) {
                        elements.contentCards.push(card.cloneNode(true));
                    }
                });

                // 3. غلاف النهاية  
                const finalCover = iframeDoc.querySelector('.final-cover');
                if (finalCover) {
                    elements.finalCover = finalCover.cloneNode(true);
                }

                console.log(`📦 تم استخراج: غلاف البداية + ${elements.contentCards.length} بطاقة + غلاف النهاية`);
                return elements;
            }

            async createPDFPages(elements) {
                this.pages = [];

                // 1. صفحة الغلاف الأولى
                if (elements.heroCard) {
                    const coverPage = this.createPage('cover');
                    coverPage.appendChild(elements.heroCard);
                    this.pages.push(coverPage);
                }

                // 2. صفحات المحتوى الوسطى
                if (elements.contentCards.length > 0) {
                    await this.distributeCardsToPages(elements.contentCards);
                }

                // 3. صفحة الغلاف النهائية
                if (elements.finalCover) {
                    const finalPage = this.createPage('cover');
                    finalPage.appendChild(elements.finalCover);
                    this.pages.push(finalPage);
                }

                console.log(`✅ تم إنشاء ${this.pages.length} صفحة`);
            }

            async distributeCardsToPages(cards) {
                // توزيع البطاقات على الصفحات
                // نبدأ بصفحة فارغة
                let currentPage = this.createPage('content');
                let currentPageHeight = 0;
                const maxPageHeight = 250; // ارتفاع تقريبي بالمم للمحتوى

                for (let i = 0; i < cards.length; i++) {
                    const card = cards[i];
                    
                    // تقدير ارتفاع البطاقة
                    const estimatedHeight = await this.estimateCardHeight(card);
                    
                    // إذا كانت البطاقة لا تتسع في الصفحة الحالية
                    if (currentPageHeight > 0 && (currentPageHeight + estimatedHeight) > maxPageHeight) {
                        // احفظ الصفحة الحالية وابدأ صفحة جديدة
                        this.pages.push(currentPage);
                        currentPage = this.createPage('content');
                        currentPageHeight = 0;
                        console.log(`📄 صفحة جديدة - البطاقة ${i + 1} كبيرة`);
                    }

                    // أضف البطاقة للصفحة الحالية
                    const contentDiv = currentPage.querySelector('.page-content');
                    contentDiv.appendChild(card);
                    currentPageHeight += estimatedHeight;
                }

                // أضف الصفحة الأخيرة إذا كانت تحتوي على محتوى
                if (currentPageHeight > 0) {
                    this.pages.push(currentPage);
                }
            }

            async estimateCardHeight(card) {
                // تقدير سريع لارتفاع البطاقة
                // نضع البطاقة مؤقتاً في مساحة عمل مخفية لقياس ارتفاعها
                const tempContainer = document.createElement('div');
                tempContainer.style.position = 'absolute';
                tempContainer.style.top = '-9999px';
                tempContainer.style.width = '180mm';
                tempContainer.style.visibility = 'hidden';
                
                const cardClone = card.cloneNode(true);
                tempContainer.appendChild(cardClone);
                document.body.appendChild(tempContainer);
                
                const height = cardClone.offsetHeight;
                document.body.removeChild(tempContainer);
                
                // تحويل من pixels إلى mm تقريبي
                return Math.ceil(height * 0.264583); // 1px ≈ 0.264583mm
            }

            createPage(type) {
                const page = document.createElement('div');
                page.className = `pdf-page ${type}-page`;
                
                if (type === 'content') {
                    // أضف هيدر للصفحات الوسطى فقط
                    page.innerHTML = `
                        <div class="page-header">
                            <img src="https://alfagolden.com/images/logo.png" alt="شعار ألفا الذهبية">
                        </div>
                        <div class="page-content"></div>
                    `;
                }
                
                return page;
            }

            async generatePDF() {
                try {
                    const { jsPDF } = window.jspdf;
                    const pdf = new jsPDF({
                        orientation: 'portrait',
                        unit: 'mm',
                        format: 'a4',
                        compress: true
                    });

                    for (let i = 0; i < this.pages.length; i++) {
                        const page = this.pages[i];
                        
                        this.updateProgress(80 + (i / this.pages.length) * 15, 
                            `معالجة الصفحة ${i + 1}/${this.pages.length}...`);
                        
                        // أضف الصفحة لمساحة العمل
                        this.workspace.appendChild(page);
                        
                        try {
                            // التقاط الصفحة
                            const canvas = await html2canvas(page, {
                                scale: 2,
                                useCORS: true,
                                allowTaint: true,
                                backgroundColor: '#ffffff',
                                logging: false,
                                width: page.offsetWidth,
                                height: page.offsetHeight
                            });
                            
                            // إضافة صفحة للـ PDF
                            if (i > 0) pdf.addPage();
                            
                            const imgData = canvas.toDataURL('image/jpeg', 0.95);
                            pdf.addImage(imgData, 'JPEG', 0, 0, 210, 297);

                        } catch (error) {
                            console.error(`❌ خطأ في الصفحة ${i + 1}:`, error);
                        } finally {
                            // إزالة الصفحة من مساحة العمل
                            this.workspace.removeChild(page);
                        }
                        
                        // انتظار قصير
                        await new Promise(resolve => setTimeout(resolve, 100));
                    }

                    // حفظ الملف
                    this.updateProgress(95, 'جارٍ حفظ الملف...');
                    const fileName = `عرض_سعر_${Date.now()}.pdf`;
                    pdf.save(fileName);
                    
                    this.updateProgress(100, 'تم الانتهاء!');
                    this.showSuccess();
                    
                } catch (error) {
                    console.error('❌ خطأ في PDF:', error);
                    this.showError('فشل في إنشاء PDF: ' + error.message);
                }
            }

            showSuccess() {
                const successMsg = document.getElementById('successMessage');
                const loaderScreen = document.getElementById('loaderScreen');

                successMsg.style.display = 'block';
                setTimeout(() => successMsg.style.opacity = '1', 50);
                
                setTimeout(() => {
                    loaderScreen.style.opacity = '0';
                    setTimeout(() => loaderScreen.style.display = 'none', 500);
                }, 2000);
            }

            showError(message) {
                this.progressText.textContent = 'خطأ: ' + message;
                this.progressText.style.color = '#ff6b6b';
                this.progressFill.style.background = '#dc3545';
                
                setTimeout(() => {
                    window.location.href = `5.php?quote_id=<?= htmlspecialchars($quote_id, ENT_QUOTES, 'UTF-8') ?>`;
                }, 5000);
            }
        }

        // بدء العملية
        document.addEventListener('DOMContentLoaded', () => {
            document.fonts.ready.then(() => {
                new PDFGenerator();
            });
        });
    </script>
</body>
</html>