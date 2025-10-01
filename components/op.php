<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مشاريع ألفا الذهبية</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --gold: #977e2b;
            --gold-hover: #b89635;
            --gold-light: rgba(151, 126, 43, 0.1);
            --dark-gray: #2c2c2c;
            --medium-gray: #666;
            --light-gray: #f8f9fa;
            --white: #ffffff;
            --border-color: #e5e7eb;
            --success: #28a745;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body { font-family: 'Cairo', sans-serif; background: var(--light-gray); color: var(--dark-gray); line-height: 1.6; }

        /* Container */
        .container { max-width: 1200px; margin: 0 auto; padding: 40px 20px; }

        /* Filter Buttons */
        .filter-section { text-align: center; margin-bottom: 40px; }
        .filter-buttons { display: flex; justify-content: center; gap: 10px; flex-wrap: nowrap; align-items: center; }
        .filter-btn {
            background: var(--white); color: var(--medium-gray); border: 2px solid var(--border-color);
            padding: 10px 16px; border-radius: 25px; font-size: 14px; font-weight: 600; cursor: pointer;
            transition: var(--transition); font-family: 'Cairo', sans-serif; white-space: nowrap; min-width: fit-content; flex-shrink: 1;
        }
        .filter-btn:hover { border-color: var(--gold); color: var(--gold); }
        .filter-btn.active { background: var(--gold); color: var(--white); border-color: var(--gold); }

        /* Projects Grid */
        .projects-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 30px; margin-top: 30px; }

        /* Project Card */
        .project-card {
            background: var(--white); border-radius: 16px; overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1); cursor: pointer; transition: var(--transition); position: relative;
        }
        .project-card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.15); }

        .card-image-container { position: relative; height: 250px; overflow: hidden; }
        .card-image { width: 100%; height: 100%; object-fit: cover; transition: var(--transition); }
        .project-card:hover .card-image { transform: scale(1.05); }

        /* Dots */
        .image-dots {
            position: absolute; bottom: 15px; left: 50%; transform: translateX(-50%);
            display: flex; gap: 8px; z-index: 6; background: rgba(0,0,0,0.5);
            padding: 8px 12px; border-radius: 20px; backdrop-filter: blur(5px);
        }
        .image-dot { width: 8px; height: 8px; border-radius: 50%; background: rgba(255,255,255,0.5); cursor: pointer; transition: var(--transition); }
        .image-dot:hover { background: rgba(255,255,255,0.8); }
        .image-dot.active { background: var(--gold); transform: scale(1.3); }

        .status-badge {
            position: absolute; top: 15px; right: 15px; background: var(--gold); color: var(--white);
            padding: 6px 12px; border-radius: 15px; font-size: 12px; font-weight: 600; z-index: 5;
        }
        .status-badge.completed { background: var(--success); }

        .card-content { padding: 25px; }
        .card-title { font-size: 20px; font-weight: 700; color: var(--dark-gray); margin-bottom: 15px; line-height: 1.4; }
        .card-description { color: var(--medium-gray); font-size: 15px; line-height: 1.6; }

        /* Loading */
        .loading { text-align: center; padding: 60px 20px; color: var(--medium-gray); }
        .spinner { display: inline-block; width: 40px; height: 40px; border: 4px solid var(--border-color); border-top: 4px solid var(--gold); border-radius: 50%; animation: spin 1s linear infinite; margin-bottom: 20px; }
        @keyframes spin { 0%{transform: rotate(0deg);} 100%{transform: rotate(360deg);} }

        /* Gallery Modal */
        .gallery-modal { position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.95); display:none; z-index:9999; opacity:0; transition: opacity .3s ease; }
        .gallery-modal.active { display:block; opacity:1; }
        .gallery-wrapper { position: relative; width: 100%; height: 100%; display:flex; align-items:center; justify-content:center; }
        .gallery-counter {
            position:absolute; top:30px; left:30px; background: rgba(0,0,0,0.8); color:#fff; padding:10px 20px; border-radius:25px; font-size:16px; font-weight:600; z-index:20; border:2px solid rgba(255,255,255,0.2);
        }
        .gallery-close-btn {
            position:absolute; top:30px; right:30px; background: rgba(255,0,0,0.8); color:#fff; border:none; width:50px; height:50px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:20px; transition: var(--transition); z-index:20; border:2px solid rgba(255,255,255,0.2);
        }
        .gallery-close-btn:hover { background: rgba(255,0,0,1); transform: scale(1.1); }
        .gallery-main-image { max-width: 80%; max-height: 80%; object-fit: contain; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,0.5); z-index:1; }
        .gallery-arrow {
            position:absolute; top:50%; transform: translateY(-50%); background: rgba(0,0,0,0.8); color:#fff; border:none; width:70px; height:70px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:28px; transition: var(--transition); z-index:15; border:3px solid rgba(255,255,255,0.3);
        }
        .gallery-arrow:hover { background: var(--gold); border-color: var(--gold); transform: translateY(-50%) scale(1.1); }
        .gallery-arrow:active { transform: translateY(-50%) scale(0.95); }
        .gallery-arrow-prev { left:40px; }
        .gallery-arrow-next { right:40px; }
        [dir="rtl"] .gallery-arrow-prev { right:40px; left:auto; }
        [dir="rtl"] .gallery-arrow-next { left:40px; right:auto; }
        [dir="rtl"] .gallery-arrow-prev .fa-chevron-left { transform: scaleX(-1); }
        [dir="rtl"] .gallery-arrow-next .fa-chevron-right { transform: scaleX(-1); }
        .gallery-info-panel {
            position:absolute; bottom:30px; left:50%; transform: translateX(-50%); background: rgba(0,0,0,0.9); color:#fff; padding:20px 30px; border-radius:15px; text-align:center; max-width:80%; z-index:10; border:2px solid rgba(255,255,255,0.2); backdrop-filter: blur(10px);
        }
        .gallery-title-text { font-size:24px; font-weight:700; margin-bottom:10px; color: var(--gold); }
        .gallery-desc-text { font-size:16px; line-height:1.6; opacity:.9; }
        .gallery-touch-zone { position:absolute; top:0; bottom:0; width:25%; z-index:5; cursor:pointer; }
        .gallery-touch-left { left:0; }
        .gallery-touch-right { right:0; }

        /* Responsive */
        @media (max-width: 768px) {
            .container { padding: 20px 15px; }
            .projects-grid { grid-template-columns: 1fr; gap: 20px; }
            .filter-buttons { gap: 8px; }
            .filter-btn { padding: 8px 12px; font-size: 13px; }
            .gallery-counter { top:20px; left:20px; padding:8px 16px; font-size:14px; }
            .gallery-close-btn { top:20px; right:20px; width:45px; height:45px; font-size:18px; }
            .gallery-arrow { width:60px; height:60px; font-size:24px; }
            .gallery-arrow-prev { left:20px; }
            .gallery-arrow-next { right:20px; }
            [dir="rtl"] .gallery-arrow-prev { right:20px; left:auto; }
            [dir="rtl"] .gallery-arrow-next { left:20px; right:auto; }
            .gallery-main-image { max-width: 90%; max-height: 70%; }
            .gallery-info-panel { bottom:20px; padding:15px 20px; max-width:90%; min-width:90vw; }
            .gallery-title-text { font-size:20px; }
            .gallery-desc-text { font-size:14px; }
            .gallery-touch-zone { width:30%; }
        }
        @media (max-width: 480px) {
            .filter-btn { padding: 6px 10px; font-size: 12px; }
            .filter-buttons { gap: 6px; }
            .gallery-arrow { width:50px; height:50px; font-size:20px; }
            .gallery-arrow-prev { left:15px; }
            .gallery-arrow-next { right:15px; }
            [dir="rtl"] .gallery-arrow-prev { right:15px; left:auto; }
            [dir="rtl"] .gallery-arrow-next { left:15px; right:auto; }
            .gallery-touch-zone { width:35%; }
        }

        /* LTR tweaks */
        [dir="ltr"] .status-badge { left: 15px; right: auto; }
        [dir="ltr"] .gallery-counter { right: 30px; left: auto; }
        [dir="ltr"] .gallery-close-btn { left: 30px; right: auto; }
        @media (max-width: 768px) {
            [dir="ltr"] .gallery-counter { right: 20px; left: auto; }
            [dir="ltr"] .gallery-close-btn { left: 20px; right: auto; }
        }

        .hidden { display: none !important; }
    </style>
</head>
<body>
<?php
    // Baserow API Configuration
    $token   = 'ZC7JRm4cpaONJ8nHw6jN9lq4CBRpbq2Z';
    $tableId = '716'; // مشاريعنا
    $baseUrl = 'https://base.alfagolden.com';

    function fetchProjectsData($token, $tableId, $baseUrl, $viewId = null) {
        $url = $baseUrl . '/api/database/rows/table/' . $tableId . '/?user_field_names=true&size=200';
        if (!empty($viewId)) {
            $url .= '&view_id=' . urlencode($viewId);
        }

        $context = stream_context_create([
            'http' => [
                'header' => "Authorization: Token " . $token . "\r\n" .
                            "Content-Type: application/json\r\n",
                'method' => 'GET',
                'timeout' => 30
            ]
        ]);

        $result = @file_get_contents($url, false, $context);

        if ($result === FALSE) {
            return ['error' => 'فشل في الاتصال بقاعدة البيانات'];
        }

        $data = json_decode($result, true);

        if (!isset($data['results'])) {
            return ['error' => 'لا توجد بيانات'];
        }

        return $data['results'];
    }

    function processProjectsData($data) {
        if (isset($data['error'])) {
            return $data;
        }

        $projects = [];

        foreach ($data as $item) {
            $orderRaw = isset($item['ترتيب']) ? trim($item['ترتيب']) : '';
            $order = is_numeric($orderRaw) ? floatval($orderRaw) : 999;

            // معالجة الصور
            $images = [];
            if (!empty($item['صور'])) {
                $imageUrls = preg_split('/\s*[,\x{060C}]\s*/u', $item['صور']);
                foreach ($imageUrls as $url) {
                    $url = trim($url);
                    if (!empty($url)) {
                        $images[] = $url;
                    }
                }
            }

            if (!empty($images)) {
                $status_ar = $item['الحالة'] ?? '';
                
                $projects[] = [
                    'order'          => $order,
                    'title_ar'       => $item['العنوان'] ?? '',
                    'title_en'       => $item['العنوان-en'] ?? '',
                    'description_ar' => $item['نص'] ?? '',
                    'description_en' => $item['نص-en'] ?? '',
                    'status_ar'      => $status_ar,
                    'images'         => $images
                ];
            }
        }

        // Sort by order ASC
        usort($projects, function($a, $b) {
            if ($a['order'] == $b['order']) return 0;
            return ($a['order'] < $b['order']) ? -1 : 1;
        });

        return $projects;
    }

    $rawData      = fetchProjectsData($token, $tableId, $baseUrl);
    $projectsData = processProjectsData($rawData);
?>

    <div class="container">
        <!-- Filter Section -->
        <div class="filter-section">
            <div class="filter-buttons">
                <button class="filter-btn active" data-filter="all" id="filterAll">الكل</button>
                <button class="filter-btn" data-filter="تحت التنفيذ" id="filterInProgress">تحت التنفيذ</button>
                <button class="filter-btn" data-filter="تم التنفيذ" id="filterCompleted">تم التنفيذ</button>
            </div>
        </div>

        <!-- Projects Grid -->
        <div class="projects-grid" id="projectsGrid">
            <?php if (isset($projectsData['error'])): ?>
                <div class="loading">
                    <div class="spinner"></div>
                    <p><?php echo htmlspecialchars($projectsData['error']); ?></p>
                </div>
            <?php else: ?>
                <?php foreach ($projectsData as $index => $project): ?>
                    <?php
                        $isCompleted = false;
                        if (isset($project['status_ar']) && trim($project['status_ar']) === 'تم التنفيذ') {
                            $isCompleted = true;
                        }
                    ?>
                    <div class="project-card"
                         data-status="<?php echo htmlspecialchars($project['status_ar']); ?>"
                         data-index="<?php echo $index; ?>"
                         onclick="openGallery(<?php echo $index; ?>)">

                        <div class="card-image-container">
                            <img class="card-image"
                                 src="<?php echo htmlspecialchars($project['images'][0]); ?>"
                                 alt="<?php echo htmlspecialchars($project['title_ar'] ?: $project['title_en']); ?>"
                                 loading="lazy"
                                 onerror="handleImageError(this)">

                            <?php if (count($project['images']) > 1): ?>
                                <div class="image-dots">
                                    <?php for ($i = 0; $i < count($project['images']); $i++): ?>
                                        <span class="image-dot <?php echo $i === 0 ? 'active' : ''; ?>"
                                              onclick="event.stopPropagation(); goToCardImage(<?php echo $index; ?>, <?php echo $i; ?>)"></span>
                                    <?php endfor; ?>
                                </div>
                            <?php endif; ?>

                            <div class="status-badge <?php echo $isCompleted ? 'completed' : ''; ?>">
                                <span class="status-text" data-status-ar="<?php echo htmlspecialchars($project['status_ar']); ?>">
                                    <?php echo htmlspecialchars($project['status_ar']); ?>
                                </span>
                            </div>
                        </div>

                        <div class="card-content">
                            <h3 class="card-title">
                                <span class="title-ar"><?php echo htmlspecialchars($project['title_ar']); ?></span>
                                <span class="title-en" style="display: none;"><?php echo htmlspecialchars($project['title_en']); ?></span>
                            </h3>
                            <p class="card-description">
                                <span class="desc-ar">
                                    <?php
                                        $desc_ar = $project['description_ar'] ?? '';
                                        echo htmlspecialchars(mb_substr($desc_ar, 0, 150)) . (mb_strlen($desc_ar) > 150 ? '...' : '');
                                    ?>
                                </span>
                                <span class="desc-en" style="display: none;">
                                    <?php
                                        $desc_en = $project['description_en'] ?? '';
                                        echo htmlspecialchars(mb_substr($desc_en, 0, 150)) . (mb_strlen($desc_en) > 150 ? '...' : '');
                                    ?>
                                </span>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Gallery Modal -->
    <div class="gallery-modal" id="galleryModal">
        <div class="gallery-wrapper">
            <div class="gallery-counter" id="galleryCounter">1/1</div>

            <button class="gallery-close-btn" onclick="closeGallery()">
                <i class="fas fa-times"></i>
            </button>

            <div class="gallery-touch-zone gallery-touch-left" onclick="navigateGallery('prev')"></div>
            <div class="gallery-touch-zone gallery-touch-right" onclick="navigateGallery('next')"></div>

            <img class="gallery-main-image" id="galleryMainImage" src="" alt="">

            <button class="gallery-arrow gallery-arrow-prev" id="galleryPrevArrow" onclick="navigateGallery('prev')">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="gallery-arrow gallery-arrow-next" id="galleryNextArrow" onclick="navigateGallery('next')">
                <i class="fas fa-chevron-right"></i>
            </button>

            <div class="gallery-info-panel">
                <h3 class="gallery-title-text" id="galleryTitleText"></h3>
                <p class="gallery-desc-text" id="galleryDescText"></p>
            </div>
        </div>
    </div>

    <script>
        // Projects data from PHP
        const projectsData = <?php echo json_encode($projectsData, JSON_UNESCAPED_UNICODE); ?>;

        // Current states
        let currentLanguage = 'ar';
        let currentFilter = 'all';
        let currentGalleryProject = 0;
        let currentGalleryImage = 0;
        let cardImageIndices = {};
        let cardAutoSlideIntervals = {};

        // Language translations for filter buttons and status
        const translations = {
            ar: { 
                all: 'الكل', 
                inProgress: 'تحت التنفيذ', 
                completed: 'تم التنفيذ',
                statusTranslations: {
                    'تحت التنفيذ': 'تحت التنفيذ',
                    'تم التنفيذ': 'تم التنفيذ'
                }
            },
            en: { 
                all: 'All',  
                inProgress: 'Under Implementation', 
                completed: 'Completed',
                statusTranslations: {
                    'تحت التنفيذ': 'Under Implementation',
                    'تم التنفيذ': 'Completed'
                }
            }
        };

        function handleImageError(img) {
            img.style.display = 'none';
            const card = img.closest('.project-card');
            if (card) {
                const imageContainer = card.querySelector('.card-image-container');
                if (imageContainer) {
                    imageContainer.style.backgroundColor = '#f0f0f0';
                    imageContainer.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#666;font-size:16px;">لا توجد صورة متاحة</div>';
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            initializeCardImageIndices();
            startAutoSlideForCards();
            bindEvents();
            
            // الحصول على اللغة الحالية من الهيدر إذا كان متوفراً
            if (window.TranslationManager && typeof window.TranslationManager.getCurrentLanguage === 'function') {
                currentLanguage = window.TranslationManager.getCurrentLanguage();
            }
            
            updateLanguage();
            
            // الاستماع لتغييرات اللغة من الهيدر
            document.addEventListener('siteLanguageChanged', function(event) {
                console.log('تم استقبال تغيير اللغة من الهيدر:', event.detail.language);
                currentLanguage = event.detail.language;
                updateLanguage();
            });
            
            // استماع إضافي للتوافق مع النظام القديم
            document.addEventListener('languageChanged', function(event) {
                console.log('تم استقبال تغيير اللغة (النظام القديم):', event.detail.language);
                currentLanguage = event.detail.language;
                updateLanguage();
            });
        });

        function initializeCardImageIndices() { 
            projectsData.forEach((_, i) => cardImageIndices[i] = 0); 
        }

        function startAutoSlideForCards() {
            projectsData.forEach((project, index) => {
                if (project.images.length > 1) {
                    cardAutoSlideIntervals[index] = setInterval(() => { nextCardImage(index); }, 2000);
                }
            });
        }

        function stopAutoSlideForCard(cardIndex) {
            if (cardAutoSlideIntervals[cardIndex]) {
                clearInterval(cardAutoSlideIntervals[cardIndex]);
                delete cardAutoSlideIntervals[cardIndex];
            }
        }

        function restartAutoSlideForCard(cardIndex) {
            const project = projectsData[cardIndex];
            if (project.images.length > 1) {
                stopAutoSlideForCard(cardIndex);
                cardAutoSlideIntervals[cardIndex] = setInterval(() => { nextCardImage(cardIndex); }, 2000);
            }
        }

        function bindEvents() {
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.addEventListener('click', function() { filterProjects(this.dataset.filter); });
            });

            document.querySelectorAll('.project-card').forEach((card, index) => {
                card.addEventListener('mouseenter', () => stopAutoSlideForCard(index));
                card.addEventListener('mouseleave', () => restartAutoSlideForCard(index));
            });

            document.addEventListener('keydown', function(e) {
                const modal = document.getElementById('galleryModal');
                if (modal.classList.contains('active')) {
                    switch(e.key) {
                        case 'Escape': closeGallery(); break;
                        case 'ArrowLeft': navigateGallery('prev'); break;
                        case 'ArrowRight': navigateGallery('next'); break;
                        case ' ': e.preventDefault(); navigateGallery('next'); break;
                    }
                }
            });

            document.getElementById('galleryModal').addEventListener('click', function(e) {
                if (e.target === this) closeGallery();
            });
        }

        function updateLanguage() {
            const html = document.documentElement;

            if (currentLanguage === 'ar') {
                html.setAttribute('lang', 'ar'); 
                html.setAttribute('dir', 'rtl');
                
                document.getElementById('filterAll').textContent = translations.ar.all;
                document.getElementById('filterInProgress').textContent = translations.ar.inProgress;
                document.getElementById('filterCompleted').textContent = translations.ar.completed;

                document.querySelectorAll('.title-ar, .desc-ar').forEach(el => el.style.display = '');
                document.querySelectorAll('.title-en, .desc-en').forEach(el => el.style.display = 'none');

                // تحديث شارات الحالة للعربية
                document.querySelectorAll('.status-text').forEach(statusEl => {
                    const statusAr = statusEl.getAttribute('data-status-ar') || '';
                    statusEl.textContent = statusAr;
                });
            } else {
                html.setAttribute('lang', 'en'); 
                html.setAttribute('dir', 'ltr');
                
                document.getElementById('filterAll').textContent = translations.en.all;
                document.getElementById('filterInProgress').textContent = translations.en.inProgress;
                document.getElementById('filterCompleted').textContent = translations.en.completed;

                document.querySelectorAll('.title-en, .desc-en').forEach(el => el.style.display = '');
                document.querySelectorAll('.title-ar, .desc-ar').forEach(el => el.style.display = 'none');

                // ترجمة شارات الحالة للإنجليزية بناءً على القيمة العربية
                document.querySelectorAll('.status-text').forEach(statusEl => {
                    const statusAr = statusEl.getAttribute('data-status-ar') || '';
                    const statusEn = translations.en.statusTranslations[statusAr] || statusAr;
                    statusEl.textContent = statusEn;
                });
            }

            // تحديث المعرض إذا كان مفتوحاً
            if (document.getElementById('galleryModal').classList.contains('active')) {
                updateGalleryContent();
            }
        }

        function filterProjects(filter) {
            currentFilter = filter;
            document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelector(`[data-filter="${filter}"]`).classList.add('active');

            document.querySelectorAll('.project-card').forEach(card => {
                const status = card.dataset.status;
                if (filter === 'all' || status === filter) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        function nextCardImage(cardIndex) {
            const project = projectsData[cardIndex];
            if (project.images.length > 1) {
                cardImageIndices[cardIndex] = (cardImageIndices[cardIndex] + 1) % project.images.length;
                updateCardImage(cardIndex);
                updateCardDots(cardIndex);
            }
        }

        function goToCardImage(cardIndex, imageIndex) {
            cardImageIndices[cardIndex] = imageIndex;
            updateCardImage(cardIndex);
            updateCardDots(cardIndex);
            restartAutoSlideForCard(cardIndex);
        }

        function updateCardImage(cardIndex) {
            const project = projectsData[cardIndex];
            const img = document.querySelector(`[data-index="${cardIndex}"] .card-image`);
            if (img && project.images && project.images[cardImageIndices[cardIndex]]) {
                img.src = project.images[cardImageIndices[cardIndex]];
            }
        }

        function updateCardDots(cardIndex) {
            const card = document.querySelector(`[data-index="${cardIndex}"]`);
            if (card) {
                const dots = card.querySelectorAll('.image-dot');
                dots.forEach((dot, index) => {
                    if (index === cardImageIndices[cardIndex]) dot.classList.add('active');
                    else dot.classList.remove('active');
                });
            }
        }

        // Gallery
        function openGallery(projectIndex) {
            const project = projectsData[projectIndex];
            if (!project || !project.images || project.images.length === 0) {
                console.error('لا توجد صور للعرض');
                return;
            }
            currentGalleryProject = projectIndex;
            currentGalleryImage = 0;

            const modal = document.getElementById('galleryModal');
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';

            updateGalleryContent();
            updateGalleryNavigation();
        }

        function closeGallery() {
            const modal = document.getElementById('galleryModal');
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }

        function navigateGallery(direction) {
            const project = projectsData[currentGalleryProject];
            if (!project || !project.images || project.images.length <= 1) return;

            if (direction === 'next') {
                currentGalleryImage = (currentGalleryImage + 1) % project.images.length;
            } else if (direction === 'prev') {
                currentGalleryImage = currentGalleryImage === 0 ? project.images.length - 1 : currentGalleryImage - 1;
            }

            updateGalleryContent();
        }

        function updateGalleryContent() {
            const project = projectsData[currentGalleryProject];
            if (!project || !project.images) return;

            const galleryImage  = document.getElementById('galleryMainImage');
            const galleryTitle  = document.getElementById('galleryTitleText');
            const galleryDesc   = document.getElementById('galleryDescText');
            const galleryCounter= document.getElementById('galleryCounter');

            if (project.images[currentGalleryImage]) {
                galleryImage.src = project.images[currentGalleryImage];
            }

            if (currentLanguage === 'ar') {
                galleryTitle.textContent = project.title_ar || 'بدون عنوان';
                galleryDesc.textContent  = project.description_ar || 'بدون وصف';
            } else {
                galleryTitle.textContent = project.title_en || 'No Title';
                galleryDesc.textContent  = project.description_en || 'No Description';
            }

            galleryCounter.textContent = `${currentGalleryImage + 1}/${project.images.length}`;
        }

        function updateGalleryNavigation() {
            const project = projectsData[currentGalleryProject];
            const prevArrow = document.getElementById('galleryPrevArrow');
            const nextArrow = document.getElementById('galleryNextArrow');

            if (!project || !project.images || project.images.length <= 1) {
                prevArrow.classList.add('hidden');
                nextArrow.classList.add('hidden');
            } else {
                prevArrow.classList.remove('hidden');
                nextArrow.classList.remove('hidden');
            }
        }
    </script>
</body>
</html>