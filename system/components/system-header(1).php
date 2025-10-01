<?php
if (!defined('SYSTEM_NOCODB_TOKEN')) {
    define('SYSTEM_NOCODB_TOKEN', 's2N4_9bxo38V5R6gyZJ3qjRiks1fBiiS7RvW6WPT');
    define('SYSTEM_NOCODB_API_URL', 'https://ncdb.alfagolden.com/api/v2/tables/');
}

function fetchSystemNocoDB($tableId, $viewId = '') {
    $url = SYSTEM_NOCODB_API_URL . $tableId . '/records';
    if (!empty($viewId)) {
        $url .= '?viewId=' . $viewId;
    }
    
    $options = [
        'http' => [
            'header' => "xc-token: " . SYSTEM_NOCODB_TOKEN . "\r\n" .
                       "Content-Type: application/json\r\n",
            'method' => 'GET',
            'timeout' => 10
        ]
    ];
    
    $context = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);
    
    if ($result === FALSE) {
        return [];
    }
    
    $data = json_decode($result, true);
    return isset($data['list']) ? $data['list'] : [];
}

$systemMenuData = fetchSystemNocoDB('mzoog6zen0pozp6');

function sanitizeSystemData($data) {
    if (is_array($data)) {
        return array_map('sanitizeSystemData', $data);
    }
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}

$systemMenuData = sanitizeSystemData($systemMenuData);
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
:root {
    --gold: #977e2b;
    --gold-hover: #b89635;
    --dark-gray: #2c2c2c;
    --medium-gray: #666;
    --light-gray: #f8f9fa;
    --white: #ffffff;
    --sidebar-collapsed: 64px;
    --sidebar-expanded: 260px;
    --header-height: 70px;
    --transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    --z-sidebar: 1000;
}

* {
    box-sizing: border-box;
}

.system-header-component {
    font-family: 'Cairo', 'Tajawal', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.system-layout {
    display: flex;
    min-height: 100vh;
    background: var(--light-gray);
}

.system-sidebar {
    position: fixed;
    top: 0;
    right: 0;
    width: var(--sidebar-collapsed);
    height: 100vh;
    background: var(--white);
    box-shadow: var(--shadow);
    z-index: var(--z-sidebar);
    transition: var(--transition);
    direction: rtl;
    display: grid;
    grid-template-rows: auto 1fr auto;
}

.system-sidebar.expanded {
    width: var(--sidebar-expanded);
}

.system-main-content {
    flex: 1;
    margin-right: var(--sidebar-collapsed);
    transition: var(--transition);
    min-height: 100vh;
    padding: 1.5rem;
}

.system-sidebar.expanded + .system-main-content {
    margin-right: var(--sidebar-expanded);
}

.sidebar-header {
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-bottom: 1px solid rgba(151, 126, 43, 0.1);
    background: rgba(151, 126, 43, 0.03);
    position: relative;
}

.logo-container {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
}

.logo-icon {
    width: 32px;
    height: 32px;
    object-fit: contain;
    opacity: 1;
    transform: scale(1);
    transition: var(--transition);
    position: absolute;
}

.logo-full {
    height: 28px;
    object-fit: contain;
    opacity: 0;
    transform: scale(0.8);
    transition: var(--transition);
    position: absolute;
}

.system-sidebar.expanded .logo-icon {
    opacity: 0;
    transform: scale(0.8);
}

.system-sidebar.expanded .logo-full {
    opacity: 1;
    transform: scale(1);
}

.sidebar-content {
    display: flex;
    flex-direction: column;
    overflow: hidden;
    padding: 12px 0;
}

.user-section {
    padding: 0 8px 16px;
    text-align: center;
    opacity: 0;
    transform: translateY(10px);
    transition: var(--transition);
}

.system-sidebar.expanded .user-section {
    opacity: 1;
    transform: translateY(0);
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--gold), var(--gold-hover));
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 1rem;
    margin: 0 auto 6px;
}

.user-name {
    font-weight: 600;
    color: var(--dark-gray);
    font-size: 0.8rem;
    margin-bottom: 2px;
}

.user-role {
    font-size: 0.7rem;
    color: var(--medium-gray);
}

.nav-section {
    margin-bottom: 20px;
}

.nav-section-title {
    font-size: 0.7rem;
    font-weight: 700;
    color: var(--gold);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin: 0 12px 8px;
    opacity: 0;
    transition: var(--transition);
}

.system-sidebar.expanded .nav-section-title {
    opacity: 1;
}

.nav-menu {
    list-style: none;
    margin: 0;
    padding: 0;
}

.nav-item {
    margin: 0 6px 2px;
}

.nav-link {
    width: calc(100% - 0px);
    height: 44px;
    display: grid;
    grid-template-columns: 44px 1fr;
    align-items: center;
    color: var(--dark-gray);
    text-decoration: none;
    border-radius: 6px;
    transition: var(--transition);
    font-weight: 500;
    font-size: 0.85rem;
    position: relative;
    cursor: pointer;
    overflow: hidden;
}

.system-sidebar:not(.expanded) .nav-link {
    grid-template-columns: 1fr;
    justify-items: center;
}

.nav-link:hover {
    background: rgba(151, 126, 43, 0.08);
    color: var(--gold);
}

.nav-link.active {
    background: var(--gold);
    color: white;
    box-shadow: 0 2px 8px rgba(151, 126, 43, 0.3);
}

.nav-icon {
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

.nav-text {
    opacity: 0;
    transition: var(--transition);
    white-space: nowrap;
    overflow: hidden;
    padding-right: 8px;
}

.system-sidebar.expanded .nav-text {
    opacity: 1;
}

.nav-tooltip {
    position: absolute;
    right: calc(100% + 8px);
    top: 50%;
    transform: translateY(-50%);
    background: rgba(0, 0, 0, 0.9);
    color: white;
    padding: 6px 10px;
    border-radius: 6px;
    font-size: 0.75rem;
    white-space: nowrap;
    opacity: 0;
    visibility: hidden;
    transition: var(--transition);
    z-index: 1001;
    pointer-events: none;
}

.nav-tooltip::after {
    content: '';
    position: absolute;
    left: -4px;
    top: 50%;
    transform: translateY(-50%);
    border: 4px solid transparent;
    border-right-color: rgba(0, 0, 0, 0.9);
}

.system-sidebar:not(.expanded) .nav-link:hover .nav-tooltip {
    opacity: 1;
    visibility: visible;
}

.language-section {
    padding: 0 6px 12px;
}

.language-btn {
    width: calc(100% - 0px);
    height: 44px;
    background: rgba(151, 126, 43, 0.06);
    border: 1px solid rgba(151, 126, 43, 0.15);
    border-radius: 6px;
    cursor: pointer;
    transition: var(--transition);
    display: grid;
    grid-template-columns: 44px 1fr;
    align-items: center;
    font-size: 0.85rem;
    font-weight: 500;
    color: var(--gold);
}

.system-sidebar:not(.expanded) .language-btn {
    grid-template-columns: 1fr;
    justify-items: center;
}

.language-btn:hover {
    background: rgba(151, 126, 43, 0.12);
    border-color: var(--gold);
}

.language-icon {
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
}

.language-text {
    opacity: 0;
    transition: var(--transition);
    padding-right: 8px;
}

.system-sidebar.expanded .language-text {
    opacity: 1;
}

.system-mobile-header {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: var(--header-height);
    background: var(--white);
    border-bottom: 1px solid rgba(151, 126, 43, 0.1);
    z-index: var(--z-sidebar);
    box-shadow: var(--shadow);
    direction: rtl;
}

.mobile-header-container {
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 100%;
    padding: 0 1rem;
    max-width: 1400px;
    margin: 0 auto;
}

.mobile-logo img {
    height: 36px;
}

.mobile-controls {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.mobile-language-btn {
    background: rgba(151, 126, 43, 0.06);
    border: 1px solid rgba(151, 126, 43, 0.15);
    border-radius: 6px;
    padding: 8px 12px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    color: var(--gold);
    font-weight: 500;
}

.mobile-menu-toggle {
    background: rgba(151, 126, 43, 0.06);
    border: 1px solid rgba(151, 126, 43, 0.15);
    font-size: 1rem;
    color: var(--gold);
    cursor: pointer;
    padding: 8px;
    border-radius: 6px;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.system-mobile-menu {
    position: fixed;
    top: var(--header-height);
    left: 0;
    right: 0;
    background: var(--white);
    box-shadow: var(--shadow);
    transform: translateY(-100%);
    opacity: 0;
    visibility: hidden;
    transition: var(--transition);
    z-index: 999;
    max-height: calc(100vh - var(--header-height));
    overflow-y: auto;
    direction: rtl;
}

.system-mobile-menu.active {
    transform: translateY(0);
    opacity: 1;
    visibility: visible;
}

.mobile-nav {
    padding: 1rem;
}

.mobile-user-section {
    padding: 1rem;
    text-align: center;
    border-bottom: 1px solid rgba(151, 126, 43, 0.1);
    margin-bottom: 1rem;
    background: rgba(151, 126, 43, 0.03);
    border-radius: 8px;
}

.mobile-user-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--gold), var(--gold-hover));
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 1.2rem;
    margin: 0 auto 8px;
}

.mobile-nav-item {
    margin-bottom: 4px;
}

.mobile-nav-link {
    height: 50px;
    display: flex;
    align-items: center;
    padding: 0 1rem;
    color: var(--dark-gray);
    text-decoration: none;
    border-radius: 8px;
    font-weight: 500;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.mobile-nav-link:hover {
    background: rgba(151, 126, 43, 0.08);
    color: var(--gold);
}

.mobile-nav-icon {
    width: 20px;
    height: 20px;
    margin-left: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

.mobile-language-section {
    padding: 1rem;
    border-top: 1px solid rgba(151, 126, 43, 0.1);
    margin-top: 1rem;
    display: flex;
    justify-content: center;
}

.mobile-language-btn2 {
    background: rgba(151, 126, 43, 0.06);
    border: 1px solid rgba(151, 126, 43, 0.15);
    border-radius: 8px;
    padding: 12px 20px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--gold);
    min-width: 120px;
    justify-content: center;
}

@media (max-width: 768px) {
    .system-sidebar {
        display: none;
    }
    
    .system-mobile-header {
        display: block;
    }
    
    .system-main-content {
        margin-right: 0;
        margin-top: var(--header-height);
        padding: 1rem;
    }
}

@media (max-width: 480px) {
    .mobile-header-container {
        padding: 0 0.75rem;
    }
    
    .mobile-logo img {
        height: 32px;
    }
    
    .system-main-content {
        padding: 0.75rem;
    }
}
</style>

<div class="system-header-component">
    <aside class="system-sidebar" id="systemSidebar">
        <div class="sidebar-header">
            <div class="logo-container">
                <img src="/iconalfa.png" alt="ألفا" class="logo-icon">
                <img src="/logo.png" alt="شركة ألفا الذهبية" class="logo-full">
            </div>
        </div>
        
        <div class="sidebar-content">
            <div class="user-section">
                <div class="user-avatar">ج</div>
                <div class="user-name">جستن مثال</div>
                <div class="user-role">مدير النظام</div>
            </div>
            
            <nav>
                <div class="nav-section">
                    <h3 class="nav-section-title">القائمة الرئيسية</h3>
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="system.php" class="nav-link active" data-translate="dashboard">
                                <div class="nav-icon"><i class="fas fa-tachometer-alt"></i></div>
                                <span class="nav-text">لوحة التحكم</span>
                                <div class="nav-tooltip">لوحة التحكم</div>
                            </a>
                        </li>
                        <?php if (!empty($systemMenuData)): ?>
                            <?php 
                            $maxItems = 6;
                            $menuItems = array_slice($systemMenuData, 0, $maxItems);
                            foreach ($menuItems as $menuItem): 
                            ?>
                                <li class="nav-item">
                                    <a href="<?php echo !empty($menuItem['الرابط']) ? $menuItem['الرابط'] : '#'; ?>" 
                                       class="nav-link"
                                       data-page-ar="<?php echo sanitizeSystemData($menuItem['الصفحة'] ?? ''); ?>"
                                       data-page-en="<?php echo sanitizeSystemData($menuItem['page'] ?? $menuItem['الصفحة'] ?? ''); ?>">
                                        <div class="nav-icon"><i class="fas fa-folder"></i></div>
                                        <span class="nav-text"><?php echo $menuItem['الصفحة'] ?? ''; ?></span>
                                        <div class="nav-tooltip"><?php echo $menuItem['الصفحة'] ?? ''; ?></div>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="nav-section">
                    <h3 class="nav-section-title">الإعدادات</h3>
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="#" class="nav-link" data-translate="profile">
                                <div class="nav-icon"><i class="fas fa-user"></i></div>
                                <span class="nav-text">الملف الشخصي</span>
                                <div class="nav-tooltip">الملف الشخصي</div>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link" data-translate="settings">
                                <div class="nav-icon"><i class="fas fa-cog"></i></div>
                                <span class="nav-text">الإعدادات</span>
                                <div class="nav-tooltip">الإعدادات</div>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link" data-translate="logout">
                                <div class="nav-icon"><i class="fas fa-sign-out-alt"></i></div>
                                <span class="nav-text">تسجيل الخروج</span>
                                <div class="nav-tooltip">تسجيل الخروج</div>
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
        </div>
        
        <div class="language-section">
            <button class="language-btn" id="languageBtn">
                <div class="language-icon"><i class="fas fa-globe"></i></div>
                <span class="language-text" id="languageText">English</span>
            </button>
        </div>
    </aside>
    
    <header class="system-mobile-header">
        <div class="mobile-header-container">
            <div class="mobile-logo">
                <img src="/logo.png" alt="شركة ألفا الذهبية">
            </div>
            
            <div class="mobile-controls">
                <button class="mobile-language-btn" id="mobileLanguageBtn">
                    <i class="fas fa-globe"></i>
                    <span id="mobileLanguageText">English</span>
                </button>
                
                <button class="mobile-menu-toggle" id="mobileMenuToggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </header>
    
    <nav class="system-mobile-menu" id="mobileMenu">
        <div class="mobile-nav">
            <div class="mobile-user-section">
                <div class="mobile-user-avatar">ج</div>
                <div style="font-weight: 600; color: #2c2c2c; margin-bottom: 4px;">جستن مثال</div>
                <div style="font-size: 0.8rem; color: #666;">مدير النظام</div>
            </div>
            
            <ul class="nav-menu">
                <li class="mobile-nav-item">
                    <a href="system.php" class="mobile-nav-link" data-translate="dashboard">
                        <div class="mobile-nav-icon"><i class="fas fa-tachometer-alt"></i></div>
                        <span>لوحة التحكم</span>
                    </a>
                </li>
                <?php if (!empty($systemMenuData)): ?>
                    <?php foreach (array_slice($systemMenuData, 0, 8) as $menuItem): ?>
                        <li class="mobile-nav-item">
                            <a href="<?php echo !empty($menuItem['الرابط']) ? $menuItem['الرابط'] : '#'; ?>" 
                               class="mobile-nav-link"
                               data-page-ar="<?php echo sanitizeSystemData($menuItem['الصفحة'] ?? ''); ?>"
                               data-page-en="<?php echo sanitizeSystemData($menuItem['page'] ?? $menuItem['الصفحة'] ?? ''); ?>">
                                <div class="mobile-nav-icon"><i class="fas fa-folder"></i></div>
                                <span><?php echo $menuItem['الصفحة'] ?? ''; ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
                <li class="mobile-nav-item">
                    <a href="#" class="mobile-nav-link" data-translate="profile">
                        <div class="mobile-nav-icon"><i class="fas fa-user"></i></div>
                        <span>الملف الشخصي</span>
                    </a>
                </li>
                <li class="mobile-nav-item">
                    <a href="#" class="mobile-nav-link" data-translate="settings">
                        <div class="mobile-nav-icon"><i class="fas fa-cog"></i></div>
                        <span>الإعدادات</span>
                    </a>
                </li>
                <li class="mobile-nav-item">
                    <a href="#" class="mobile-nav-link" data-translate="logout">
                        <div class="mobile-nav-icon"><i class="fas fa-sign-out-alt"></i></div>
                        <span>تسجيل الخروج</span>
                    </a>
                </li>
            </ul>
            
            <div class="mobile-language-section">
                <button class="mobile-language-btn2" id="mobileLanguageBtn2">
                    <i class="fas fa-globe"></i>
                    <span id="mobileLanguageText2">English</span>
                </button>
            </div>
        </div>
    </nav>
</div>

<script>
window.SystemTranslationManager = (function() {
    const translations = {
        ar: { 'dashboard': 'لوحة التحكم', 'profile': 'الملف الشخصي', 'settings': 'الإعدادات', 'logout': 'تسجيل الخروج' },
        en: { 'dashboard': 'Dashboard', 'profile': 'Profile', 'settings': 'Settings', 'logout': 'Logout' }
    };
    
    let currentLanguage = localStorage.getItem('systemLanguage') || 'ar';
    
    function updateTexts() {
        document.querySelectorAll('[data-translate]').forEach(el => {
            const key = el.getAttribute('data-translate');
            if (translations[currentLanguage][key]) {
                const textEl = el.querySelector('.nav-text, span') || el;
                textEl.textContent = translations[currentLanguage][key];
            }
        });
        
        document.querySelectorAll('[data-page-ar][data-page-en]').forEach(link => {
            const nameAr = link.getAttribute('data-page-ar');
            const nameEn = link.getAttribute('data-page-en');
            const textEl = link.querySelector('.nav-text, span');
            const tooltipEl = link.querySelector('.nav-tooltip');
            
            if (textEl) textEl.textContent = currentLanguage === 'ar' ? nameAr : nameEn;
            if (tooltipEl) tooltipEl.textContent = currentLanguage === 'ar' ? nameAr : nameEn;
        });
        
        ['languageText', 'mobileLanguageText', 'mobileLanguageText2'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.textContent = currentLanguage === 'ar' ? 'English' : 'العربية';
        });
    }
    
    function toggleLanguage() {
        currentLanguage = currentLanguage === 'ar' ? 'en' : 'ar';
        localStorage.setItem('systemLanguage', currentLanguage);
        updateTexts();
        
        document.dispatchEvent(new CustomEvent('systemLanguageChanged', {
            detail: { language: currentLanguage, isRTL: currentLanguage === 'ar' }
        }));
    }
    
    return { init: updateTexts, toggleLanguage };
})();

document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('systemSidebar');
    const mobileMenu = document.getElementById('mobileMenu');
    const mobileToggle = document.getElementById('mobileMenuToggle');
    let hoverTimeout = null;
    let isMobileOpen = false;
    
    if (sidebar) {
        sidebar.addEventListener('mouseenter', () => {
            clearTimeout(hoverTimeout);
            sidebar.classList.add('expanded');
        });
        
        sidebar.addEventListener('mouseleave', () => {
            hoverTimeout = setTimeout(() => {
                sidebar.classList.remove('expanded');
            }, 2000);
        });
    }
    
    if (mobileToggle) {
        mobileToggle.addEventListener('click', () => {
            isMobileOpen = !isMobileOpen;
            mobileMenu.classList.toggle('active', isMobileOpen);
            mobileToggle.innerHTML = isMobileOpen ? '<i class="fas fa-times"></i>' : '<i class="fas fa-bars"></i>';
            document.body.style.overflow = isMobileOpen ? 'hidden' : '';
        });
    }
    
    document.addEventListener('click', (e) => {
        if (isMobileOpen && !mobileMenu.contains(e.target) && !mobileToggle.contains(e.target)) {
            isMobileOpen = false;
            mobileMenu.classList.remove('active');
            mobileToggle.innerHTML = '<i class="fas fa-bars"></i>';
            document.body.style.overflow = '';
        }
    });
    
    ['languageBtn', 'mobileLanguageBtn', 'mobileLanguageBtn2'].forEach(id => {
        const btn = document.getElementById(id);
        if (btn) {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                SystemTranslationManager.toggleLanguage();
                if (id.includes('mobile') && isMobileOpen) {
                    isMobileOpen = false;
                    mobileMenu.classList.remove('active');
                    mobileToggle.innerHTML = '<i class="fas fa-bars"></i>';
                    document.body.style.overflow = '';
                }
            });
        }
    });
    
    const currentPage = window.location.pathname.split('/').pop() || 'system.php';
    document.querySelectorAll(`[href="${currentPage}"]`).forEach(link => {
        link.classList.add('active');
    });
    
    SystemTranslationManager.init();
    
    window.SystemHeaderManager = {
        getCurrentLanguage: () => localStorage.getItem('systemLanguage') || 'ar',
        isRTL: () => (localStorage.getItem('systemLanguage') || 'ar') === 'ar'
    };
});
</script>