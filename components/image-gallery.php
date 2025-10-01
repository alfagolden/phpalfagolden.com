<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>معرض الصور الديناميكي</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

<?php
if (!defined('NOCODB_TOKEN')) {
    define('NOCODB_TOKEN', 'fwVaKHr6zbDns5iW9u8annJUf5LCBJXjqPfujIpV');
    define('NOCODB_API_URL', 'https://app.nocodb.com/api/v2/tables/');
}

function fetchNocoDB_Gallery($tableId, $viewId = '', $limit = 100) {
    $allRecords = [];
    $offset = 0;
    $hasMoreData = true;
    
    while ($hasMoreData) {
        $url = NOCODB_API_URL . $tableId . '/records';
        $params = [
            'limit' => $limit,
            'offset' => $offset
        ];
        
        if (!empty($viewId)) {
            $params['viewId'] = $viewId;
        }
        
        $url .= '?' . http_build_query($params);
        
        $options = [
            'http' => [
                'header' => "xc-token: " . NOCODB_TOKEN . "\r\n" .
                           "Content-Type: application/json\r\n",
                'method' => 'GET',
                'timeout' => 30
            ]
        ];
        
        $context = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);
        
        if ($result === FALSE) {
            break;
        }
        
        $data = json_decode($result, true);
        
        if (!isset($data['list']) || !is_array($data['list'])) {
            break;
        }
        
        $allRecords = array_merge($allRecords, $data['list']);
        
        if (isset($data['pageInfo'])) {
            $pageInfo = $data['pageInfo'];
            
            if (isset($pageInfo['isLastPage']) && $pageInfo['isLastPage']) {
                $hasMoreData = false;
            } elseif (count($data['list']) < $limit) {
                $hasMoreData = false;
            } else {
                $offset += $limit;
            }
        } else {
            $hasMoreData = false;
        }
        
        if ($offset > 10000) {
            break;
        }
        
        usleep(100000);
    }
    
    return $allRecords;
}

function processStoriesData($data) {
    $processedData = [];
    $groupedByFile = [];
    
    foreach ($data as $item) {
        $imageField = $item['الصورة'] ?? $item['صورة'] ?? $item['الصوره'] ?? '';
        $fileIdField = $item['معرف الملف'] ?? $item['معرف_الملف'] ?? $item['رقم الملف'] ?? '';
        
        if (!empty($imageField) && !empty($fileIdField)) {
            $fileId = $fileIdField;
            $storyOrder = $item['الترتيب'] ?? $item['ترتيب'] ?? 1;
            $subOrder = $item['الترتيب الفرعي'] ?? $item['ترتيب فرعي'] ?? $item['الترتيب_الفرعي'] ?? 1;
            $subName = $item['الاسم الفرعي'] ?? $item['اسم فرعي'] ?? $item['الاسم_الفرعي'] ?? '';
            $mainName = $item['الاسم'] ?? $item['اسم'] ?? '';
            
            if (empty($subName)) {
                $subName = " ";
            }
            
            if (!isset($groupedByFile[$fileId])) {
                $groupedByFile[$fileId] = [
                    'الاسم' => $mainName,
                    'معرف_الملف' => $fileId,
                    'المجموعات' => []
                ];
            }
            
            if (!isset($groupedByFile[$fileId]['المجموعات'][$storyOrder])) {
                $groupedByFile[$fileId]['المجموعات'][$storyOrder] = [
                    'الاسم_الفرعي' => $subName,
                    'الترتيب' => $storyOrder,
                    'الصور' => []
                ];
            }
            
            $groupedByFile[$fileId]['المجموعات'][$storyOrder]['الصور'][] = [
                'رابط' => trim($imageField),
                'ترتيب_فرعي' => $subOrder
            ];
        }
    }
    
    foreach ($groupedByFile as $gallery) {
        if (!empty($gallery['المجموعات'])) {
            ksort($gallery['المجموعات']);
            
            $sortedGroups = [];
            foreach ($gallery['المجموعات'] as $group) {
                usort($group['الصور'], function($a, $b) {
                    return $a['ترتيب_فرعي'] <=> $b['ترتيب_فرعي'];
                });
                
                $imageUrls = array_map(function($img) {
                    return $img['رابط'];
                }, $group['الصور']);
                
                if (!empty($imageUrls)) {
                    $sortedGroups[] = [
                        'الاسم_الفرعي' => $group['الاسم_الفرعي'],
                        'الترتيب' => $group['الترتيب'],
                        'الصور' => $imageUrls
                    ];
                }
            }
            
            if (!empty($sortedGroups)) {
                $processedData[] = [
                    'الاسم' => $gallery['الاسم'],
                    'معرف_الملف' => $gallery['معرف_الملف'],
                    'المجموعات' => $sortedGroups
                ];
            }
        }
    }
    
    return $processedData;
}

$galleryRawData = fetchNocoDB_Gallery('ma95crsjyfik3ce', 'vwm7ve6soxdrrbea', 50);

function sanitizeData_Gallery($data) {
    if (is_array($data)) {
        return array_map('sanitizeData_Gallery', $data);
    }
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}

$galleryRawData = sanitizeData_Gallery($galleryRawData);
$galleryData = processStoriesData($galleryRawData);
?>

<style>
:root {
    --primary: #977e2b;
    --primary-hover: #b89635;
    --white: #ffffff;
    --overlay: rgba(0, 0, 0, 0.95);
    --glass-bg: rgba(255, 255, 255, 0.1);
    --glass-border: rgba(255, 255, 255, 0.2);
    --text-primary: #ffffff;
    --text-secondary: rgba(255, 255, 255, 0.8);
    --shadow-soft: 0 8px 32px rgba(0, 0, 0, 0.3);
    --shadow-strong: 0 20px 60px rgba(0, 0, 0, 0.5);
    --radius-sm: 8px;
    --radius-md: 12px;
    --radius-lg: 16px;
    --radius-xl: 24px;
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    --z-overlay: 9999;
}

* {
    box-sizing: border-box;
}

body {
    font-family: 'Cairo', sans-serif;
    margin: 0;
    padding: 0;
}

.gallery-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: var(--overlay);
    z-index: var(--z-overlay);
    opacity: 0;
    visibility: hidden;
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    user-select: none;
    transform: scale(1.1);
    cursor: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='40' height='40' viewBox='0 0 40 40'%3E%3Ccircle cx='20' cy='20' r='15' fill='none' stroke='white' stroke-width='2' opacity='0.8'/%3E%3Ccircle cx='20' cy='20' r='8' fill='white' opacity='0.6'/%3E%3Cline x1='29' y1='29' x2='35' y2='35' stroke='white' stroke-width='3' stroke-linecap='round' opacity='0.8'/%3E%3C/svg%3E") 20 20, zoom-in;
}

.gallery-overlay.active {
    opacity: 1;
    visibility: visible;
    transform: scale(1);
}

.gallery-container {
    position: relative;
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    transform: translateY(50px);
    transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

.gallery-overlay.active .gallery-container {
    transform: translateY(0);
}

.gallery-header {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    z-index: 20;
    padding: 25px 30px;
    background: linear-gradient(180deg, rgba(0, 0, 0, 0.8) 0%, transparent 100%);
    display: flex;
    justify-content: space-between;
    align-items: center;
    opacity: 0;
    transform: translateY(-20px);
    transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1) 0.2s;
}

.gallery-overlay.active .gallery-header {
    opacity: 1;
    transform: translateY(0);
}

.gallery-title {
    color: var(--text-primary);
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
}

.gallery-close {
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    color: var(--text-primary);
    width: 45px;
    height: 45px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: var(--transition);
    backdrop-filter: blur(20px);
}

.gallery-close:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: scale(1.1);
}

.progress-container {
    position: absolute;
    top: 80px;
    left: 30px;
    right: 30px;
    z-index: 20;
    display: flex;
    gap: 6px;
    height: 4px;
    direction: ltr;
    opacity: 0;
    transform: translateY(-10px);
    transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1) 0.3s;
}

.gallery-overlay.active .progress-container {
    opacity: 1;
    transform: translateY(0);
}

.progress-bar {
    flex: 1;
    background: rgba(255, 255, 255, 0.3);
    border-radius: 2px;
    overflow: hidden;
    position: relative;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--white), rgba(255, 255, 255, 0.9));
    border-radius: 2px;
    width: 0%;
    transition: width 0.1s linear;
    transform-origin: left center;
}

.image-counter {
    position: absolute;
    top: 95px;
    right: 30px;
    z-index: 20;
    background: var(--glass-bg);
    color: var(--text-primary);
    padding: 8px 16px;
    border-radius: var(--radius-lg);
    font-size: 0.9rem;
    font-weight: 600;
    backdrop-filter: blur(20px);
    border: 1px solid var(--glass-border);
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
    opacity: 0;
    transform: translateY(-10px);
    transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1) 0.4s;
}

.gallery-overlay.active .image-counter {
    opacity: 1;
    transform: translateY(0);
}

.gallery-content {
    flex: 1;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}

.image-container {
    position: relative;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 120px 60px 150px;
}

.main-image {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-strong);
    opacity: 0;
    transform: scale(0.8);
    transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='40' height='40' viewBox='0 0 40 40'%3E%3Ccircle cx='20' cy='20' r='15' fill='none' stroke='white' stroke-width='2' opacity='0.8'/%3E%3Ccircle cx='20' cy='20' r='8' fill='white' opacity='0.6'/%3E%3Cline x1='29' y1='29' x2='35' y2='35' stroke='white' stroke-width='3' stroke-linecap='round' opacity='0.8'/%3E%3C/svg%3E") 20 20, zoom-in;
    z-index: 5;
}

.main-image.loaded {
    opacity: 1;
    transform: scale(1);
}

.tap-area {
    position: absolute;
    top: 0;
    bottom: 0;
    width: 50%;
    z-index: 10;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.tap-area.left {
    left: 0;
}

.tap-area.right {
    right: 0;
}

.tap-area:hover {
    opacity: 1;
}

.tap-area::after {
    content: '';
    position: absolute;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: var(--glass-bg);
    border: 2px solid var(--glass-border);
    opacity: 0;
    transition: all 0.3s ease;
    backdrop-filter: blur(20px);
}

.tap-area.left::after {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='white' viewBox='0 0 24 24'%3E%3Cpath d='M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: center;
    background-size: 28px;
}

.tap-area.right::after {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='white' viewBox='0 0 24 24'%3E%3Cpath d='M8.59 16.59L10 18l6-6-6-6-1.41 1.41L13.17 12z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: center;
    background-size: 28px;
}

.tap-area:hover::after {
    opacity: 1;
    transform: scale(1.1);
}

.subtitle-container {
    position: absolute;
    bottom: 120px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 20;
    background: linear-gradient(135deg, rgba(0, 0, 0, 0.8), rgba(0, 0, 0, 0.6));
    color: var(--text-primary);
    padding: 20px 30px;
    border-radius: var(--radius-xl);
    font-size: 1.4rem;
    font-weight: 700;
    text-align: center;
    backdrop-filter: blur(30px);
    border: 1px solid var(--glass-border);
    box-shadow: var(--shadow-soft);
    max-width: calc(100% - 4rem);
    line-height: 1.3;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.7);
    opacity: 0;
    transform: translate(-50%, 20px);
    transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1) 0.5s;
}

.subtitle-container.hidden {
    display: none;
}

.gallery-overlay.active .subtitle-container {
    opacity: 1;
    transform: translateX(-50%);
}

.navigation-controls {
    position: absolute;
    bottom: 30px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 20;
    display: flex;
    gap: 15px;
    align-items: center;
    opacity: 0;
    transform: translate(-50%, 20px);
    transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1) 0.6s;
    flex-direction: row-reverse;
}

.gallery-overlay.active .navigation-controls {
    opacity: 1;
    transform: translateX(-50%);
}

.nav-button {
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    color: var(--text-primary);
    width: 55px;
    height: 55px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: var(--transition);
    backdrop-filter: blur(30px);
    box-shadow: var(--shadow-soft);
}

.nav-button:hover:not(:disabled) {
    background: rgba(255, 255, 255, 0.2);
    transform: scale(1.1);
}

.nav-button:disabled {
    opacity: 0.4;
    cursor: not-allowed;
    transform: none;
}

.nav-button.double {
    display: flex;
}

.single-group .nav-button.double {
    display: none;
}

.nav-left {
    order: 1;
}

.nav-right {
    order: 2;
}

.loading-indicator {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: var(--text-primary);
    z-index: 30;
}

.spinner {
    width: 50px;
    height: 50px;
    border: 4px solid rgba(255, 255, 255, 0.3);
    border-top: 4px solid var(--primary);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

@media (max-width: 768px) {
    .gallery-header {
        padding: 20px;
    }
    
    .gallery-title {
        font-size: 1.2rem;
    }
    
    .gallery-close {
        width: 40px;
        height: 40px;
        font-size: 18px;
    }
    
    .progress-container {
        top: 70px;
        left: 20px;
        right: 20px;
    }
    
    .image-counter {
        top: 85px;
        right: 20px;
        font-size: 0.8rem;
        padding: 6px 12px;
    }
    
    .image-container {
        padding: 100px 30px 130px;
    }
    
    .subtitle-container {
        bottom: 100px;
        font-size: 1.2rem;
        padding: 15px 20px;
        max-width: calc(100% - 2rem);
    }
    
    .navigation-controls {
        bottom: 20px;
        gap: 10px;
    }
    
    .nav-button {
        width: 48px;
        height: 48px;
        font-size: 18px;
    }
}

@media (max-width: 480px) {
    .image-container {
        padding: 90px 20px 120px;
    }
    
    .subtitle-container {
        bottom: 90px;
        font-size: 1.1rem;
        padding: 12px 16px;
    }
    
    .nav-button {
        width: 44px;
        height: 44px;
        font-size: 16px;
    }
}
</style>

<div class="gallery-overlay" id="galleryOverlay">
    <div class="gallery-container">
        <div class="gallery-header">
            <h3 class="gallery-title" id="galleryTitle">معرض الصور</h3>
            <button class="gallery-close" id="galleryClose">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="progress-container" id="progressContainer"></div>
        
        <div class="image-counter" id="imageCounter">1/1</div>

        <div class="loading-indicator" id="loadingIndicator">
            <div class="spinner"></div>
        </div>

        <div class="gallery-content">
            <div class="image-container">
                <img class="main-image" id="mainImage" alt="صورة المعرض">
                <div class="tap-area left" id="tapLeft"></div>
                <div class="tap-area right" id="tapRight"></div>
            </div>
        </div>

        <div class="subtitle-container" id="subtitleContainer">العنوان الفرعي</div>

        <div class="navigation-controls" id="navigationControls">
            <button class="nav-button double nav-left" id="prevGroupBtn" title="المجموعة السابقة">
                <i class="fas fa-angle-double-left"></i>
            </button>
            <button class="nav-button nav-left" id="prevImageBtn" title="الصورة السابقة">
                <i class="fas fa-angle-left"></i>
            </button>
            <button class="nav-button nav-right" id="nextImageBtn" title="الصورة التالية">
                <i class="fas fa-angle-right"></i>
            </button>
            <button class="nav-button double nav-right" id="nextGroupBtn" title="المجموعة التالية">
                <i class="fas fa-angle-double-right"></i>
            </button>
        </div>
    </div>
</div>

<script id="galleryData" type="application/json">
<?php echo json_encode($galleryData, JSON_UNESCAPED_UNICODE); ?>
</script>

<script>
(function() {
    'use strict';
    
    let galleryData = [];
    let currentGallery = null;
    let currentGroupIndex = 0;
    let currentImageIndex = 0;
    let isGalleryOpen = false;
    
    const overlay = document.getElementById('galleryOverlay');
    const galleryTitle = document.getElementById('galleryTitle');
    const galleryClose = document.getElementById('galleryClose');
    const loadingIndicator = document.getElementById('loadingIndicator');
    const progressContainer = document.getElementById('progressContainer');
    const mainImage = document.getElementById('mainImage');
    const imageCounter = document.getElementById('imageCounter');
    const subtitleContainer = document.getElementById('subtitleContainer');
    const tapLeft = document.getElementById('tapLeft');
    const tapRight = document.getElementById('tapRight');
    const navigationControls = document.getElementById('navigationControls');
    const prevGroupBtn = document.getElementById('prevGroupBtn');
    const nextGroupBtn = document.getElementById('nextGroupBtn');
    const prevImageBtn = document.getElementById('prevImageBtn');
    const nextImageBtn = document.getElementById('nextImageBtn');
    
    function loadGalleryData() {
        const dataScript = document.getElementById('galleryData');
        if (dataScript && dataScript.textContent) {
            try {
                galleryData = JSON.parse(dataScript.textContent);
            } catch (e) {
                galleryData = [];
            }
        }
    }
    
    window.openImageGallery = function(galleryId) {
        const gallery = galleryData.find(g => g.معرف_الملف == galleryId);
        if (!gallery || !gallery.المجموعات || gallery.المجموعات.length === 0) {
            return;
        }
        
        isGalleryOpen = true;
        currentGallery = gallery;
        currentGroupIndex = 0;
        currentImageIndex = 0;
        
        galleryTitle.textContent = gallery.الاسم;
        
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        loadingIndicator.style.display = 'block';
        
        updateNavigationStyle();
        createProgressBars();
        
        setTimeout(() => {
            loadCurrentContent();
        }, 200);
    };
    
    function updateNavigationStyle() {
        if (currentGallery.المجموعات.length === 1) {
            navigationControls.classList.add('single-group');
        } else {
            navigationControls.classList.remove('single-group');
        }
    }
    
    function createProgressBars() {
        progressContainer.innerHTML = '';
        
        currentGallery.المجموعات.forEach((group, groupIndex) => {
            group.الصور.forEach((image, imageIndex) => {
                const progressBar = document.createElement('div');
                progressBar.className = 'progress-bar';
                progressBar.dataset.groupIndex = groupIndex;
                progressBar.dataset.imageIndex = imageIndex;
                
                const progressFill = document.createElement('div');
                progressFill.className = 'progress-fill';
                
                progressBar.appendChild(progressFill);
                progressContainer.appendChild(progressBar);
            });
        });
    }
    
    function updateProgressBars() {
        const progressBars = progressContainer.querySelectorAll('.progress-bar');
        let totalImageIndex = 0;
        
        for (let i = 0; i < currentGroupIndex; i++) {
            totalImageIndex += currentGallery.المجموعات[i].الصور.length;
        }
        totalImageIndex += currentImageIndex;
        
        progressBars.forEach((bar, index) => {
            const fill = bar.querySelector('.progress-fill');
            if (index < totalImageIndex) {
                fill.style.width = '100%';
                fill.style.transition = 'none';
            } else if (index === totalImageIndex) {
                fill.style.width = '100%';
                fill.style.transition = 'none';
            } else {
                fill.style.width = '0%';
                fill.style.transition = 'none';
            }
        });
    }
    
    function loadCurrentContent() {
        if (!currentGallery || !currentGallery.المجموعات[currentGroupIndex]) {
            return;
        }
        
        const currentGroup = currentGallery.المجموعات[currentGroupIndex];
        const currentImageUrl = currentGroup.الصور[currentImageIndex];
        
        if (!currentImageUrl) {
            return;
        }
        
        // التحقق من وجود العنوان الفرعي وإخفاؤه إذا كان فارغًا
        const subtitleText = currentGroup.الاسم_الفرعي ? currentGroup.الاسم_الفرعي.trim() : '';
        if (subtitleText && subtitleText !== '') {
            subtitleContainer.textContent = subtitleText;
            subtitleContainer.classList.remove('hidden');
        } else {
            subtitleContainer.classList.add('hidden');
        }
        
        const totalImages = currentGroup.الصور.length;
        imageCounter.textContent = `${currentImageIndex + 1}/${totalImages}`;
        
        updateNavigationButtons();
        loadImage(currentImageUrl);
        updateProgressBars();
    }
    
    function loadImage(imageUrl) {
        loadingIndicator.style.display = 'block';
        mainImage.classList.remove('loaded');
        
        const img = new Image();
        img.onload = function() {
            mainImage.src = imageUrl;
            mainImage.classList.add('loaded');
            loadingIndicator.style.display = 'none';
        };
        
        img.onerror = function() {
            mainImage.src = 'https://via.placeholder.com/800x600/977e2b/ffffff?text=صورة+غير+متوفرة';
            mainImage.classList.add('loaded');
            loadingIndicator.style.display = 'none';
        };
        
        img.src = imageUrl;
    }
    
    function nextImage() {
        const currentGroup = currentGallery.المجموعات[currentGroupIndex];
        
        if (currentImageIndex < currentGroup.الصور.length - 1) {
            currentImageIndex++;
            loadCurrentContent();
        } else {
            nextGroup();
        }
    }
    
    function prevImage() {
        if (currentImageIndex > 0) {
            currentImageIndex--;
            loadCurrentContent();
        } else {
            prevGroup();
        }
    }
    
    function nextGroup() {
        if (currentGroupIndex < currentGallery.المجموعات.length - 1) {
            currentGroupIndex++;
            currentImageIndex = 0;
            loadCurrentContent();
        } else {
            closeGallery();
        }
    }
    
    function prevGroup() {
        if (currentGroupIndex > 0) {
            currentGroupIndex--;
            currentImageIndex = currentGallery.المجموعات[currentGroupIndex].الصور.length - 1;
            loadCurrentContent();
        }
    }
    
    function updateNavigationButtons() {
        const isFirstImage = (currentGroupIndex === 0 && currentImageIndex === 0);
        const isLastImage = (currentGroupIndex === currentGallery.المجموعات.length - 1 && 
                           currentImageIndex === currentGallery.المجموعات[currentGroupIndex].الصور.length - 1);
        
        prevImageBtn.disabled = isFirstImage;
        nextImageBtn.disabled = isLastImage;
        prevGroupBtn.disabled = (currentGroupIndex === 0);
        nextGroupBtn.disabled = (currentGroupIndex === currentGallery.المجموعات.length - 1);
    }
    
    function closeGallery() {
        if (!isGalleryOpen) return;
        
        isGalleryOpen = false;
        
        overlay.classList.remove('active');
        
        setTimeout(() => {
            document.body.style.overflow = '';
            currentGallery = null;
            currentGroupIndex = 0;
            currentImageIndex = 0;
            progressContainer.innerHTML = '';
            mainImage.src = '';
            mainImage.classList.remove('loaded');
            subtitleContainer.classList.remove('hidden');
        }, 500);
    }
    
    function bindEvents() {
        galleryClose.addEventListener('click', closeGallery);
        
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) {
                closeGallery();
            }
        });
        
        document.addEventListener('keydown', function(e) {
            if (!isGalleryOpen) return;
            
            switch(e.key) {
                case 'Escape':
                    closeGallery();
                    break;
                case 'ArrowLeft':
                    e.preventDefault();
                    nextImage();
                    break;
                case 'ArrowRight':
                    e.preventDefault();
                    prevImage();
                    break;
                case ' ':
                    e.preventDefault();
                    nextImage();
                    break;
            }
        });
        
        tapLeft.addEventListener('click', function(e) {
            e.stopPropagation();
            prevImage();
        });
        
        tapRight.addEventListener('click', function(e) {
            e.stopPropagation();
            nextImage();
        });
        
        prevImageBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            prevImage();
        });
        
        nextImageBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            nextImage();
        });
        
        prevGroupBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            prevGroup();
        });
        
        nextGroupBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            nextGroup();
        });
        
        document.addEventListener('dragstart', function(e) {
            if (e.target.matches('.main-image')) {
                e.preventDefault();
            }
        });
    }
    
    function init() {
        loadGalleryData();
        bindEvents();
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>

</body>
</html>