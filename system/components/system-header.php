<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy();
    header('Location: https://alfagolden.com/system/login.php'); 
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'انتهت الجلسة']);
        exit;
    }
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'get_user_data') {
        $url = "https://base.alfagolden.com/api/database/rows/table/702/{$user_id}/";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Token h5qAt85gtiJDAzpH51WrXPywhmnhrPWy'
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'name' => $data['field_6912'] ?? '',
                'gender' => $data['field_6913'] ?? 'ذكر',
                'phone' => $data['field_6773'] ?? ''
            ]
        ]);
    }
    
    if ($action === 'update_user_data') {
        $name = trim($_POST['name'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        
        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'الاسم مطلوب']);
            exit;
        }
        
        $url = "https://base.alfagolden.com/api/database/rows/table/702/{$user_id}/";
        
        $updateData = [
            'field_6912' => $name,
            'field_6913' => $gender
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Token h5qAt85gtiJDAzpH51WrXPywhmnhrPWy',
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($updateData));
        $response = curl_exec($ch);
        curl_close($ch);
        
        echo json_encode(['success' => true, 'message' => 'تم تحديث البيانات بنجاح']);
    }
    
    exit;
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
:root { 
    --gold: #C4941A; 
    --gold-hover: #E6B52F; 
    --gold-light: #F4E8C1;
    --gold-darker: #8B6914;
    --dark-gray: #1a1a1a; 
    --medium-gray: #4a4a4a; 
    --light-gray: #f7f9fc; 
    --white: #ffffff; 
    --sidebar-collapsed: 68px;
    --sidebar-expanded: 250px;
    --header-height: 70px;
    --shadow-light: 0 4px 12px rgba(0, 0, 0, 0.08); 
    --shadow-medium: 0 8px 25px rgba(0, 0, 0, 0.15); 
    --shadow-strong: 0 12px 40px rgba(196, 148, 26, 0.25);
    --transition-smooth: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    --transition-bounce: all 0.45s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    --border-radius: 12px;
    --border-radius-small: 8px;
    --z-sidebar: 1000;
    --z-modal: 1050;
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: 'Cairo', 'Tajawal', 'Segoe UI', sans-serif;
    background: linear-gradient(135deg, var(--light-gray) 0%, #eef2f7 100%);
    transition: var(--transition-smooth);
    min-height: 100vh;
}

.system-header-component {
    direction: rtl;
}

/* Desktop styles */
@media (min-width: 769px) {
    body {
        padding-right: var(--sidebar-collapsed);
        transition: padding-right 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    body.sidebar-expanded {
        padding-right: var(--sidebar-expanded);
    }
}

/* Mobile styles */
@media (max-width: 768px) {
    body {
        padding-top: var(--header-height);
        padding-right: 0;
    }
}

/* Desktop Sidebar */
.system-sidebar {
    position: fixed;
    top: 0;
    right: 0;
    width: var(--sidebar-collapsed);
    height: 100vh;
    background: linear-gradient(180deg, var(--white) 0%, #fefefe 100%);
    box-shadow: var(--shadow-medium);
    z-index: var(--z-sidebar);
    transition: var(--transition-smooth);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border-left: 1px solid rgba(196, 148, 26, 0.1);
}

.system-sidebar.expanded {
    width: var(--sidebar-expanded);
    box-shadow: var(--shadow-strong);
}

@media (max-width: 768px) {
    .system-sidebar {
        display: none;
    }
}

.sidebar-header {
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-bottom: 2px solid rgba(196, 148, 26, 0.15);
    background: linear-gradient(135deg, rgba(196, 148, 26, 0.08) 0%, rgba(196, 148, 26, 0.04) 100%);
    cursor: pointer;
    position: relative;
    overflow: hidden;
    transition: var(--transition-smooth);
}

.sidebar-header:hover {
    background: linear-gradient(135deg, rgba(196, 148, 26, 0.12) 0%, rgba(196, 148, 26, 0.06) 100%);
    border-bottom-color: rgba(196, 148, 26, 0.25);
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
    width: 36px;
    height: 36px;
    object-fit: contain;
    opacity: 1;
    transform: scale(1);
    transition: var(--transition-bounce);
    filter: drop-shadow(0 3px 6px rgba(196, 148, 26, 0.4));
}

.logo-full {
    height: 55px;
    object-fit: contain;
    opacity: 0;
    transform: scale(0.8);
    transition: var(--transition-bounce);
    position: absolute;
    filter: drop-shadow(0 4px 12px rgba(196, 148, 26, 0.5));
}

.system-sidebar.expanded .logo-icon {
    opacity: 0;
    transform: scale(0.6) rotate(-10deg);
}

.system-sidebar.expanded .logo-full {
    opacity: 1;
    transform: scale(1);
}

.sidebar-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    padding: 20px 0;
    overflow: hidden;
}

.nav-section-title {
    font-size: 0.7rem;
    font-weight: 700;
    color: var(--gold-darker);
    text-transform: uppercase;
    letter-spacing: 0.8px;
    margin: 20px 12px 10px;
    opacity: 0;
    transform: translateX(25px);
    transition: var(--transition-smooth);
    position: relative;
}

.nav-section-title::after {
    content: '';
    position: absolute;
    bottom: -4px;
    right: 0;
    width: 30px;
    height: 2px;
    background: linear-gradient(90deg, var(--gold) 0%, transparent 100%);
    opacity: 0;
    transition: var(--transition-smooth);
}

.system-sidebar.expanded .nav-section-title {
    opacity: 1;
    transform: translateX(0);
}

.system-sidebar.expanded .nav-section-title::after {
    opacity: 1;
}

.nav-menu {
    list-style: none;
    margin: 0;
    padding: 0;
}

.nav-item {
    margin: 0 10px 6px;
}

.nav-link {
    width: 100%;
    height: 52px;
    display: flex;
    align-items: center;
    color: var(--dark-gray);
    text-decoration: none;
    border-radius: var(--border-radius);
    transition: var(--transition-smooth);
    font-weight: 500;
    font-size: 0.9rem;
    cursor: pointer;
    position: relative;
    overflow: hidden;
    padding: 0;
    background: transparent;
}

/* إصلاح محاذاة الأيقونات - الأيقونات في المنتصف تماماً */
.nav-icon {
    width: var(--sidebar-collapsed);
    height: 52px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
    transition: var(--transition-bounce);
    color: var(--medium-gray);
    position: relative;
    z-index: 2;
}

.nav-link::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(196, 148, 26, 0.15), transparent);
    transition: var(--transition-smooth);
    z-index: 1;
}

.nav-link::after {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 4px;
    height: 100%;
    background: linear-gradient(180deg, var(--gold) 0%, var(--gold-hover) 100%);
    transform: scaleY(0);
    transition: var(--transition-bounce);
    border-radius: 0 var(--border-radius-small) var(--border-radius-small) 0;
}

.nav-link:hover::before {
    left: 100%;
}

.nav-link:hover::after {
    transform: scaleY(1);
}

.nav-link:hover {
    background: linear-gradient(135deg, rgba(196, 148, 26, 0.12) 0%, rgba(196, 148, 26, 0.08) 100%);
    color: var(--gold-darker);
    transform: translateY(-2px) translateX(-3px);
    box-shadow: 0 6px 20px rgba(196, 148, 26, 0.25);
}

.nav-link:hover .nav-icon {
    transform: scale(1.15) rotate(8deg);
    color: var(--gold);
}

.nav-text {
    opacity: 0;
    transform: translateX(20px);
    transition: var(--transition-smooth);
    white-space: nowrap;
    margin-right: 12px;
    font-weight: 500;
    color: inherit;
    z-index: 2;
    position: relative;
}

.system-sidebar.expanded .nav-text {
    opacity: 1;
    transform: translateX(0);
}

/* Mobile Header - محسن */
.mobile-header {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: var(--header-height);
    background: linear-gradient(135deg, var(--white) 0%, #fefefe 100%);
    border-bottom: 2px solid rgba(196, 148, 26, 0.15);
    z-index: var(--z-sidebar);
    box-shadow: var(--shadow-light);
    direction: rtl;
}

@media (max-width: 768px) {
    .mobile-header {
        display: flex;
    }
}

.mobile-header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 100%;
    padding: 0 1.2rem;
    width: 100%;
    flex-direction: row-reverse;
}

/* تكبير الشعار في الهيدر العلوي */
.mobile-logo {
    order: 1;
    transition: var(--transition-smooth);
}

.mobile-logo img {
    height: 48px; /* زيادة من 38px إلى 48px */
    object-fit: contain;
    filter: drop-shadow(0 3px 8px rgba(196, 148, 26, 0.4));
    transition: var(--transition-smooth);
}

.mobile-logo:hover img {
    transform: scale(1.05);
    filter: drop-shadow(0 4px 12px rgba(196, 148, 26, 0.5));
}

/* تحسين زر البرجر */
.mobile-burger {
    order: 2;
    background: linear-gradient(135deg, rgba(196, 148, 26, 0.1) 0%, rgba(196, 148, 26, 0.05) 100%);
    border: 2px solid rgba(196, 148, 26, 0.2);
    border-radius: var(--border-radius-small);
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: var(--transition-bounce);
    color: var(--gold);
    font-size: 18px;
    position: relative;
    overflow: hidden;
}

.mobile-burger::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    background: radial-gradient(circle, rgba(196, 148, 26, 0.2) 0%, transparent 70%);
    transform: translate(-50%, -50%);
    transition: var(--transition-smooth);
    border-radius: 50%;
}

.mobile-burger:hover::before {
    width: 100px;
    height: 100px;
}

.mobile-burger:hover {
    background: linear-gradient(135deg, rgba(196, 148, 26, 0.2) 0%, rgba(196, 148, 26, 0.1) 100%);
    border-color: var(--gold);
    transform: scale(1.08);
    box-shadow: 0 4px 15px rgba(196, 148, 26, 0.3);
}

.mobile-burger:active {
    transform: scale(0.95);
}

.mobile-burger i {
    transition: var(--transition-bounce);
    position: relative;
    z-index: 2;
}

.mobile-burger:hover i {
    transform: rotate(180deg);
}

/* تحسين القائمة المنسدلة */
.mobile-menu {
    position: fixed;
    top: var(--header-height);
    left: 0;
    right: 0;
    background: linear-gradient(180deg, var(--white) 0%, #fefefe 100%);
    box-shadow: var(--shadow-medium);
    transform: translateY(-100%);
    opacity: 0;
    visibility: hidden;
    transition: var(--transition-smooth);
    z-index: 999;
    max-height: calc(100vh - var(--header-height));
    overflow-y: auto;
    direction: rtl;
    border-bottom: 3px solid rgba(196, 148, 26, 0.2);
}

.mobile-menu.active {
    transform: translateY(0);
    opacity: 1;
    visibility: visible;
}

.mobile-nav {
    padding: 1.5rem;
}

.mobile-nav-item {
    margin-bottom: 8px;
}

.mobile-nav-link {
    height: 56px;
    display: flex;
    align-items: center;
    padding: 0 1.2rem;
    color: var(--dark-gray);
    text-decoration: none;
    border-radius: var(--border-radius);
    font-weight: 500;
    font-size: 0.95rem;
    transition: var(--transition-smooth);
    position: relative;
    overflow: hidden;
    background: transparent;
}

.mobile-nav-link::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(196, 148, 26, 0.15), transparent);
    transition: var(--transition-smooth);
}

.mobile-nav-link::after {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 4px;
    height: 100%;
    background: linear-gradient(180deg, var(--gold) 0%, var(--gold-hover) 100%);
    transform: scaleY(0);
    transition: var(--transition-bounce);
    border-radius: 0 var(--border-radius-small) var(--border-radius-small) 0;
}

.mobile-nav-link:hover::before {
    left: 100%;
}

.mobile-nav-link:hover::after {
    transform: scaleY(1);
}

.mobile-nav-link:hover {
    background: linear-gradient(135deg, rgba(196, 148, 26, 0.12) 0%, rgba(196, 148, 26, 0.08) 100%);
    color: var(--gold-darker);
    transform: translateX(-4px);
    box-shadow: 0 4px 15px rgba(196, 148, 26, 0.2);
}

.mobile-nav-icon {
    width: 24px;
    height: 24px;
    margin-left: 1.2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    transition: var(--transition-bounce);
    color: var(--medium-gray);
}

.mobile-nav-link:hover .mobile-nav-icon {
    transform: scale(1.15) rotate(5deg);
    color: var(--gold);
}

/* Modal styles - محسن */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.65);
    z-index: var(--z-modal);
    opacity: 0;
    visibility: hidden;
    transition: var(--transition-smooth);
    backdrop-filter: blur(8px);
}

.modal-overlay.active {
    opacity: 1;
    visibility: visible;
}

.settings-modal {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) scale(0.9);
    background: linear-gradient(180deg, var(--white) 0%, #fefefe 100%);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-strong);
    width: 90%;
    max-width: 500px;
    z-index: calc(var(--z-modal) + 1);
    direction: rtl;
    border: 1px solid rgba(196, 148, 26, 0.15);
    transition: var(--transition-bounce);
}

.modal-overlay.active .settings-modal {
    transform: translate(-50%, -50%) scale(1);
}

.modal-header {
    padding: 2rem 2.5rem 1.5rem;
    border-bottom: 2px solid rgba(196, 148, 26, 0.15);
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, rgba(196, 148, 26, 0.05) 0%, transparent 100%);
    border-radius: var(--border-radius) var(--border-radius) 0 0;
}

.modal-title {
    font-size: 1.4rem;
    font-weight: 700;
    color: var(--dark-gray);
    margin: 0;
    position: relative;
}

.modal-title::after {
    content: '';
    position: absolute;
    bottom: -8px;
    right: 0;
    width: 40px;
    height: 3px;
    background: linear-gradient(90deg, var(--gold) 0%, var(--gold-hover) 100%);
    border-radius: 2px;
}

.modal-close-btn {
    background: rgba(196, 148, 26, 0.1);
    border: 1px solid rgba(196, 148, 26, 0.2);
    font-size: 1.4rem;
    color: var(--gold);
    cursor: pointer;
    padding: 0.6rem;
    border-radius: var(--border-radius-small);
    transition: var(--transition-bounce);
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-close-btn:hover {
    background: rgba(196, 148, 26, 0.2);
    color: var(--gold-hover);
    transform: scale(1.1) rotate(90deg);
    box-shadow: 0 4px 12px rgba(196, 148, 26, 0.3);
}

.modal-body {
    padding: 2.5rem;
}

.modal-form-group {
    margin-bottom: 2rem;
}

.modal-form-label {
    display: block;
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--dark-gray);
    margin-bottom: 0.8rem;
    position: relative;
}

.modal-form-label::after {
    content: '';
    position: absolute;
    bottom: -4px;
    right: 0;
    width: 20px;
    height: 2px;
    background: var(--gold);
    border-radius: 1px;
}

.modal-form-control {
    width: 100%;
    padding: 1rem 1.2rem;
    border: 2px solid #e5e7eb;
    border-radius: var(--border-radius-small);
    font-size: 1rem;
    transition: var(--transition-smooth);
    background: var(--white);
    color: var(--dark-gray);
    font-family: inherit;
    font-weight: 500;
}

.modal-form-control:focus {
    outline: none;
    border-color: var(--gold);
    box-shadow: 0 0 0 4px rgba(196, 148, 26, 0.15);
    transform: translateY(-2px);
}

.modal-form-control:disabled {
    background: #f9fafb;
    color: var(--medium-gray);
    border-color: #e5e7eb;
}

.modal-form-select {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23C4941A' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
    background-position: left 12px center;
    background-repeat: no-repeat;
    background-size: 16px 12px;
    padding-left: 45px;
    appearance: none;
}

.modal-footer {
    padding: 1.5rem 2.5rem 2rem;
    border-top: 2px solid rgba(196, 148, 26, 0.15);
    display: flex;
    justify-content: flex-end;
    gap: 1.2rem;
    background: linear-gradient(135deg, rgba(196, 148, 26, 0.03) 0%, transparent 100%);
    border-radius: 0 0 var(--border-radius) var(--border-radius);
}

.modal-btn {
    padding: 0.9rem 2rem;
    border-radius: var(--border-radius-small);
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition-bounce);
    border: 2px solid transparent;
    position: relative;
    overflow: hidden;
}

.modal-btn::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.3) 0%, transparent 70%);
    transform: translate(-50%, -50%);
    transition: var(--transition-smooth);
    border-radius: 50%;
}

.modal-btn:hover::before {
    width: 120px;
    height: 120px;
}

.modal-btn-primary {
    background: linear-gradient(135deg, var(--gold) 0%, var(--gold-hover) 100%);
    color: var(--white);
    box-shadow: 0 4px 15px rgba(196, 148, 26, 0.3);
}

.modal-btn-primary:hover {
    background: linear-gradient(135deg, var(--gold-hover) 0%, #F4C842 100%);
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(196, 148, 26, 0.4);
}

.modal-btn-secondary {
    background: var(--white);
    color: var(--dark-gray);
    border-color: #d1d5db;
}

.modal-btn-secondary:hover {
    background: var(--light-gray);
    border-color: var(--gold);
    color: var(--gold-darker);
    transform: translateY(-2px);
}

.modal-message {
    padding: 1.2rem;
    border-radius: var(--border-radius-small);
    margin-bottom: 2rem;
    font-size: 0.95rem;
    display: none;
    font-weight: 500;
}

.modal-message.success {
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    color: #166534;
    border: 2px solid #bbf7d0;
}

.modal-message.error {
    background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
    color: #dc2626;
    border: 2px solid #fecaca;
}

.loader {
    text-align: center;
    padding: 3.5rem 0;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #e5e7eb;
    border-top: 4px solid var(--gold);
    border-radius: 50%;
    animation: spin 1s linear infinite;
    display: inline-block;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* تحسينات للشاشات الصغيرة */
@media (max-width: 480px) {
    :root {
        --header-height: 65px;
    }
    
    .mobile-header-content {
        padding: 0 1rem;
    }
    
    .mobile-logo img {
        height: 42px; /* تقليل قليل للشاشات الصغيرة جداً */
    }
    
    .mobile-burger {
        width: 44px;
        height: 44px;
        font-size: 16px;
    }
    
    .settings-modal {
        width: 95%;
        margin: 0 auto;
    }
    
    .modal-header, .modal-body, .modal-footer {
        padding: 1.5rem 2rem;
    }
    
    .modal-title {
        font-size: 1.2rem;
    }
}

/* تحسينات إضافية للتفاعلات */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.mobile-menu.active .mobile-nav-item {
    animation: fadeInUp 0.4s ease-out forwards;
}

.mobile-menu.active .mobile-nav-item:nth-child(1) { animation-delay: 0.1s; }
.mobile-menu.active .mobile-nav-item:nth-child(2) { animation-delay: 0.2s; }
.mobile-menu.active .mobile-nav-item:nth-child(3) { animation-delay: 0.3s; }
</style>

<div class="system-header-component">
    <aside class="system-sidebar" id="systemSidebar">
        <div class="sidebar-header" onclick="window.location.href='https://alfagolden.com/system/hs.php'">
            <div class="logo-container">
                <img src="/iconalfa.png" alt="ألفا" class="logo-icon">
                <img src="/logo.png" alt="شركة ألفا الذهبية" class="logo-full">
            </div>
        </div>
        
        <div class="sidebar-content">
            <nav>
                <div class="nav-section">
                    <h3 class="nav-section-title">إدارة الحساب</h3>
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="#" class="nav-link" id="settingsBtn">
                                <div class="nav-icon"><i class="fas fa-cog"></i></div>
                                <span class="nav-text">الإعدادات</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="?logout=true" class="nav-link">
                                <div class="nav-icon"><i class="fas fa-sign-out-alt"></i></div>
                                <span class="nav-text">تسجيل الخروج</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
        </div>
    </aside>

    <header class="mobile-header">
        <div class="mobile-header-content">
            <div class="mobile-logo">
                <img src="/logo.png" alt="شركة ألفا الذهبية">
            </div>
            
            <button class="mobile-burger" id="mobileBurger">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </header>

    <nav class="mobile-menu" id="mobileMenu">
        <div class="mobile-nav">
            <ul class="nav-menu">
                <li class="mobile-nav-item">
                    <a href="#" class="mobile-nav-link" id="mobileSettingsBtn">
                        <div class="mobile-nav-icon"><i class="fas fa-cog"></i></div>
                        <span>الإعدادات</span>
                    </a>
                </li>
                <li class="mobile-nav-item">
                    <a href="?logout=true" class="mobile-nav-link">
                        <div class="mobile-nav-icon"><i class="fas fa-sign-out-alt"></i></div>
                        <span>تسجيل الخروج</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>
</div>

<div class="modal-overlay" id="modalOverlay">
    <div class="settings-modal">
        <div class="modal-header">
            <h3 class="modal-title">إعدادات الحساب</h3>
            <button class="modal-close-btn" id="closeBtn">×</button>
        </div>
        
        <div id="message" class="modal-message"></div>
        
        <div class="modal-body" id="modalBody">
            <div class="loader">
                <div class="spinner"></div>
            </div>
        </div>
        
        <div class="modal-footer">
            <button class="modal-btn modal-btn-secondary" id="cancelBtn">إلغاء</button>
            <button class="modal-btn modal-btn-primary" id="saveBtn">حفظ</button>
        </div>
    </div>
</div>

<script>
class MobileMenuController {
    constructor() {
        this.isOpen = false;
        this.isAnimating = false;
        this.init();
    }
    
    init() {
        this.burger = document.getElementById('mobileBurger');
        this.menu = document.getElementById('mobileMenu');
        this.bindEvents();
    }
    
    bindEvents() {
        if (this.burger) {
            this.burger.addEventListener('click', (e) => this.handleBurgerClick(e));
        }
        
        document.addEventListener('click', (e) => this.handleOutsideClick(e));
        document.addEventListener('keydown', (e) => this.handleKeyPress(e));
    }
    
    handleBurgerClick(e) {
        e.preventDefault();
        e.stopPropagation();
        
        if (this.isAnimating) return;
        
        this.toggle();
    }
    
    handleOutsideClick(e) {
        if (this.isOpen && 
            !this.menu?.contains(e.target) && 
            !this.burger?.contains(e.target)) {
            this.close();
        }
    }
    
    handleKeyPress(e) {
        if (e.key === 'Escape' && this.isOpen) {
            this.close();
        }
    }
    
    toggle() {
        this.isOpen ? this.close() : this.open();
    }
    
    open() {
        if (this.isOpen || this.isAnimating) return;
        
        this.isAnimating = true;
        this.isOpen = true;
        
        if (this.menu) this.menu.classList.add('active');
        if (this.burger) this.burger.innerHTML = '<i class="fas fa-times"></i>';
        
        document.body.style.overflow = 'hidden';
        
        setTimeout(() => {
            this.isAnimating = false;
        }, 350);
    }
    
    close() {
        if (!this.isOpen || this.isAnimating) return;
        
        this.isAnimating = true;
        this.isOpen = false;
        
        if (this.menu) this.menu.classList.remove('active');
        if (this.burger) this.burger.innerHTML = '<i class="fas fa-bars"></i>';
        
        document.body.style.overflow = '';
        
        setTimeout(() => {
            this.isAnimating = false;
        }, 350);
    }
}

class SettingsModal {
    constructor() {
        this.init();
    }
    
    init() {
        this.modalOverlay = document.getElementById('modalOverlay');
        this.modalBody = document.getElementById('modalBody');
        this.closeBtn = document.getElementById('closeBtn');
        this.cancelBtn = document.getElementById('cancelBtn');
        this.saveBtn = document.getElementById('saveBtn');
        this.message = document.getElementById('message');
        
        this.bindEvents();
    }
    
    bindEvents() {
        const settingsBtn = document.getElementById('settingsBtn');
        const mobileSettingsBtn = document.getElementById('mobileSettingsBtn');
        
        if (settingsBtn) {
            settingsBtn.addEventListener('click', (e) => this.open(e));
        }
        
        if (mobileSettingsBtn) {
            mobileSettingsBtn.addEventListener('click', (e) => this.open(e));
        }
        
        if (this.closeBtn) {
            this.closeBtn.addEventListener('click', () => this.close());
        }
        
        if (this.cancelBtn) {
            this.cancelBtn.addEventListener('click', () => this.close());
        }
        
        if (this.saveBtn) {
            this.saveBtn.addEventListener('click', () => this.save());
        }
        
        if (this.modalOverlay) {
            this.modalOverlay.addEventListener('click', (e) => {
                if (e.target === this.modalOverlay) this.close();
            });
        }
        
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.modalOverlay?.classList.contains('active')) {
                this.close();
            }
        });
    }
    
    async open(e) {
        e.preventDefault();
        
        // إغلاق قائمة الجوال إذا كانت مفتوحة
        if (window.mobileMenu) {
            window.mobileMenu.close();
        }
        
        if (this.modalOverlay) {
            this.modalOverlay.classList.add('active');
        }
        
        if (this.modalBody) {
            this.modalBody.innerHTML = '<div class="loader"><div class="spinner"></div></div>';
        }
        
        try {
            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append('action', 'get_user_data');
            
            const response = await fetch('/system/components/system-header.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success && this.modalBody) {
                this.modalBody.innerHTML = `
                    <div class="modal-form-group">
                        <label class="modal-form-label">رقم الجوال</label>
                        <input type="text" class="modal-form-control" value="${result.data.phone || ''}" disabled>
                    </div>
                    <div class="modal-form-group">
                        <label class="modal-form-label">الاسم</label>
                        <input type="text" id="userName" class="modal-form-control" value="${result.data.name || ''}" placeholder="أدخل اسمك">
                    </div>
                    <div class="modal-form-group">
                        <label class="modal-form-label">الجنس</label>
                        <select id="userGender" class="modal-form-control modal-form-select">
                            <option value="ذكر" ${result.data.gender === 'ذكر' ? 'selected' : ''}>ذكر</option>
                            <option value="أنثى" ${result.data.gender === 'أنثى' ? 'selected' : ''}>أنثى</option>
                            <option value="مؤسسة" ${result.data.gender === 'مؤسسة' ? 'selected' : ''}>مؤسسة</option>
                        </select>
                    </div>
                `;
            }
        } catch (error) {
            if (this.modalBody) {
                this.modalBody.innerHTML = '<div style="color: #dc2626; text-align: center; padding: 2rem;">خطأ في تحميل البيانات</div>';
            }
        }
    }
    
    close() {
        if (this.modalOverlay) {
            this.modalOverlay.classList.remove('active');
        }
    }
    
    async save() {
        const nameInput = document.getElementById('userName');
        const genderSelect = document.getElementById('userGender');
        
        if (!nameInput || !genderSelect) return;
        
        const name = nameInput.value.trim();
        const gender = genderSelect.value;
        
        if (!name) {
            this.showMessage('الاسم مطلوب', 'error');
            return;
        }
        
        if (this.saveBtn) {
            this.saveBtn.disabled = true;
            this.saveBtn.textContent = 'جاري الحفظ...';
        }
        
        try {
            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append('action', 'update_user_data');
            formData.append('name', name);
            formData.append('gender', gender);
            
            const response = await fetch('/system/components/system-header.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showMessage('تم تحديث البيانات بنجاح', 'success');
                setTimeout(() => this.close(), 1500);
            } else {
                this.showMessage(result.message, 'error');
            }
        } catch (error) {
            this.showMessage('خطأ في حفظ البيانات', 'error');
        } finally {
            if (this.saveBtn) {
                this.saveBtn.disabled = false;
                this.saveBtn.textContent = 'حفظ';
            }
        }
    }
    
    showMessage(text, type) {
        if (this.message) {
            this.message.textContent = text;
            this.message.className = `modal-message ${type}`;
            this.message.style.display = 'block';
            setTimeout(() => {
                if (this.message) this.message.style.display = 'none';
            }, 4000);
        }
    }
}

class SidebarController {
    constructor() {
        this.init();
    }
    
    init() {
        this.sidebar = document.getElementById('systemSidebar');
        this.bindEvents();
    }
    
    bindEvents() {
        if (this.sidebar) {
            this.sidebar.addEventListener('mouseenter', () => this.expand());
            this.sidebar.addEventListener('mouseleave', () => this.collapse());
        }
    }
    
    expand() {
        if (this.sidebar) {
            this.sidebar.classList.add('expanded');
            document.body.classList.add('sidebar-expanded');
        }
    }
    
    collapse() {
        if (this.sidebar) {
            this.sidebar.classList.remove('expanded');
            document.body.classList.remove('sidebar-expanded');
        }
    }
}

// تهيئة الكلاسات عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    window.mobileMenu = new MobileMenuController();
    window.settingsModal = new SettingsModal();
    window.sidebarController = new SidebarController();
});
</script>