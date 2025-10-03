<?php
// Define color constants for the elegant gold, white, and light gray theme
define('COLOR_GOLD_LIGHT', '#f9e79f');
define('COLOR_GOLD', '#d4a017');
define('COLOR_GOLD_DARK', '#b7950b');
define('COLOR_WHITE', '#ffffff');
define('COLOR_LIGHT_BG', '#f5f5f5');
define('COLOR_LIGHT_CARD', '#e0e0e0');
define('COLOR_TEXT_DARK', '#333333');
define('COLOR_TEXT_GRAY', '#666666');

// Sample data for dynamic home.php
$site_title = "GoldCMS";
$user_name = "";
$user_role = "";

// تحديد الصفحة الحالية من GET parameter
$current_page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// قائمة الروابط مع روابط الـ href الجديدة
$nav_items = [
    ['icon' => 'fa-home', 'text' => 'Dashboard', 'page' => 'dashboard', 'active' => ($current_page == 'dashboard')],
    ['icon' => 'fa-file-alt', 'text' => 'home.php', 'page' => 'home.php', 'active' => ($current_page == 'home.php')],
    ['icon' => 'fa-images', 'text' => 'Media Library', 'page' => 'media', 'active' => ($current_page == 'media')],
    ['icon' => 'fa-users', 'text' => 'Users', 'page' => 'users', 'active' => ($current_page == 'users')],
    ['icon' => 'fa-cog', 'text' => 'Settings', 'page' => 'settings', 'active' => ($current_page == 'settings')],
    ['icon' => 'fa-chart-line', 'text' => 'Analytics', 'page' => 'analytics', 'active' => ($current_page == 'analytics')],
    ['icon' => 'fa-bell', 'text' => 'Notifications', 'page' => 'notifications', 'badge' => '3', 'active' => ($current_page == 'notifications')],
    ['icon' => 'fa-comments', 'text' => 'Comments', 'page' => 'comments', 'active' => ($current_page == 'comments')],
    ['icon' => 'fa-plug', 'text' => 'Plugins', 'page' => 'plugins', 'active' => ($current_page == 'plugins')],
    ['icon' => 'fa-life-ring', 'text' => 'Support', 'page' => 'support', 'active' => ($current_page == 'support')]
];

// تحديد مسار الصفحة للـ include
$page_file = "pages/{$current_page}.php";
if (!file_exists($page_file)) {
    $page_file = "pages/dashboard.php"; // fallback to dashboard if page not found
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elegant Gold Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Georgia', 'Times New Roman', serif;
        }
        :root {
            --gold-light: <?php echo COLOR_GOLD_LIGHT; ?>;
            --gold: <?php echo COLOR_GOLD; ?>;
            --gold-dark: <?php echo COLOR_GOLD_DARK; ?>;
            --white: <?php echo COLOR_WHITE; ?>;
            --light-bg: <?php echo COLOR_LIGHT_BG; ?>;
            --light-card: <?php echo COLOR_LIGHT_CARD; ?>;
            --text-dark: <?php echo COLOR_TEXT_DARK; ?>;
            --text-gray: <?php echo COLOR_TEXT_GRAY; ?>;
            --sidebar-width: 300px;
            --transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        body {
            background: linear-gradient(145deg, var(--light-bg), #e8ecef, #ffffff);
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            overflow-x: hidden;
        }
        .sidebar {
            width: var(--sidebar-width);
            background: var(--white);
            height: 100vh;
            position: fixed;
            box-shadow: 0 0 40px rgba(0, 0, 0, 0.1);
            z-index: 100;
            transition: var(--transition);
            border-right: 2px solid rgba(<?php echo COLOR_GOLD; ?>, 0.3);
        }
        .sidebar-header {
            padding: 30px 25px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid rgba(<?php echo COLOR_GOLD; ?>, 0.1);
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 18px;
        }
        .logo-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--gold), var(--gold-dark));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-home.php: center;
            box-shadow: 0 6px 12px rgba(<?php echo COLOR_GOLD; ?>, 0.4);
            transition: var(--transition);
        }
        .logo-icon:hover {
            transform: scale(1.05);
        }
        .logo-icon i {
            font-size: 24px;
            color: var(--white);
        }
        .logo-text {
            font-size: 24px;
            font-weight: 700;
            background: linear-gradient(to right, var(--gold-light), var(--gold));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .nav-links {
            padding: 25px 0;
            overflow-y: auto;
            height: calc(100vh - 160px);
        }
        .nav-item {
            list-style: none;
            margin-bottom: 10px;
        }
        .nav-link {
            display: flex;
            align-items: center;
            padding: 16px 30px;
            text-decoration: none;
            color: var(--text-dark);
            font-size: 17px;
            font-weight: 500;
            transition: var(--transition);
            border-left: 4px solid transparent;
            position: relative;
            border-radius: 0 10px 10px 0;
            margin: 0 15px;
        }
        .nav-link:hover {
            background: rgba(<?php echo COLOR_GOLD; ?>, 0.1);
            border-left: 4px solid var(--gold);
            transform: translateX(5px);
        }
        .nav-link.active {
            background: rgba(<?php echo COLOR_GOLD; ?>, 0.2);
            border-left: 4px solid var(--gold);
            box-shadow: 0 6px 15px rgba(<?php echo COLOR_GOLD; ?>, 0.3);
        }
        .nav-link i {
            width: 30px;
            font-size: 20px;
            margin-right: 18px;
            color: var(--gold);
            transition: var(--transition);
        }
        .nav-link:hover i, .nav-link.active i {
            color: var(--gold-dark);
            transform: scale(1.15);
        }
        .nav-link span {
            flex: 1;
        }
        .badge {
            background: linear-gradient(135deg, var(--gold), var(--gold-dark));
            color: var(--white);
            padding: 4px 12px;
            border-radius: 25px;
            font-size: 13px;
            font-weight: 600;
            margin-left: 12px;
            animation: pulse 2s infinite;
        }
        .user-info {
            padding: 25px;
            display: flex;
            align-items: center;
            gap: 18px;
            border-top: 1px solid rgba(<?php echo COLOR_GOLD; ?>, 0.1);
            position: absolute;
            bottom: 0;
            width: calc(var(--sidebar-width) - 50px);
        }
        .user-avatar {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--gold), var(--gold-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 22px;
            color: var(--white);
            box-shadow: 0 4px 10px rgba(<?php echo COLOR_GOLD; ?>, 0.3);
        }
        .user-details h4 {
            font-size: 17px;
            margin-bottom: 5px;
            color: var(--text-dark);
        }
        .user-details p {
            font-size: 14px;
            color: var(--text-gray);
        }
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 40px;
            transition: var(--transition);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }
        .page-title {
            font-size: 32px;
            font-weight: 700;
            background: linear-gradient(to right, var(--gold-light), var(--gold));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: 1px;
        }
        .page-content {
            background: var(--light-card);
            border-radius: 18px;
            padding: 30px;
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(<?php echo COLOR_GOLD; ?>, 0.1);
        }
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
            .menu-toggle {
                display: block;
            }
        }
        .menu-toggle {
            display: none;
            background: var(--light-card);
            border: none;
            color: var(--gold);
            width: 50px;
            height: 50px;
            border-radius: 10px;
            font-size: 22px;
            cursor: pointer;
            margin-right: 18px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(<?php echo COLOR_GOLD; ?>, 0.8); }
            70% { box-shadow: 0 0 0 12px rgba(<?php echo COLOR_GOLD; ?>, 0); }
            100% { box-shadow: 0 0 0 0 rgba(<?php echo COLOR_GOLD; ?>, 0); }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-crown"></i>
                </div>
                <div class="logo-text"><?php echo htmlspecialchars($site_title); ?></div>
            </div>
        </div>
        <ul class="nav-links">
            <?php foreach ($nav_items as $item): ?>
                <li class="nav-item">
                    <a href="?page=<?php echo htmlspecialchars($item['page']); ?>" class="nav-link<?php echo $item['active'] ? ' active' : ''; ?>">
                        <i class="fas <?php echo htmlspecialchars($item['icon']); ?>"></i>
                        <span><?php echo htmlspecialchars($item['text']); ?></span>
                        <?php if (isset($item['badge'])): ?>
                            <span class="badge"><?php echo htmlspecialchars($item['badge']); ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="user-info">
            <div class="user-avatar"><?php echo strtoupper(substr($user_name, 0, 2)); ?></div>
            <div class="user-details">
                <h4><?php echo htmlspecialchars($user_name); ?></h4>
                <p><?php echo htmlspecialchars($user_role); ?></p>
            </div>
        </div>
    </div>
    <div class="main-content">
        <div class="header">
            <button class="menu-toggle">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="page-title"><?php echo ucfirst(str_replace('-', ' ', $current_page)); ?> Page</h1>
        </div>
        <div class="page-content">
            <?php include $page_file; ?>
        </div>
    </div>
    <script>
        document.querySelector('.menu-toggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
    </script>
</body>
</html>