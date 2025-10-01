<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>شركة ألفا الذهبية - الصفحة الرئيسية</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Cairo', sans-serif;
            background: #f8f9fa;
            direction: rtl;
        }
        
        .home-component {
            margin-right: 64px;
            padding: 40px;
            background: #f8f9fa;
            min-height: 100vh;
            font-family: 'Cairo', 'Tajawal', sans-serif;
        }
        
        .welcome-section {
            background: #ffffff;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            text-align: center;
            margin-bottom: 30px;
        }
        
        .welcome-title {
            font-size: 28px;
            font-weight: 700;
            color: #977e2b;
            margin-bottom: 16px;
        }
        
        .welcome-subtitle {
            font-size: 16px;
            color: #666;
            line-height: 1.6;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 30px;
            max-width: 1000px;
            margin-right: auto;
            margin-left: auto;
        }
        
        .action-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 35px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid transparent;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(151, 126, 43, 0.15);
            border-color: #977e2b;
            text-decoration: none;
            color: inherit;
        }
        
        .action-icon {
            width: 70px;
            height: 70px;
            background: rgba(151, 126, 43, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: #977e2b;
            font-size: 28px;
        }
        
        .action-title {
            font-size: 20px;
            font-weight: 600;
            color: #2c2c2c;
            margin-bottom: 12px;
        }
        
        .action-description {
            font-size: 14px;
            color: #666;
            line-height: 1.5;
        }
        
        @media (max-width: 768px) {
            .home-component {
                margin-right: 0;
                padding: 20px;
            }
            
            .welcome-section {
                padding: 30px 20px;
            }
            
            .welcome-title {
                font-size: 24px;
            }
            
            .quick-actions {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .action-card {
                padding: 30px 20px;
            }
            
            .action-icon {
                width: 60px;
                height: 60px;
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="home-component">
        <div class="welcome-section">
            <h1 class="welcome-title">مرحباً بك في شركة ألفا الذهبية</h1>
            <p class="welcome-subtitle">
                نحن سعداء لوجودك معنا. يمكنك الآن الوصول إلى جميع الخدمات والأدوات المتاحة لك.
                <br>
                ابدأ رحلتك معنا من خلال الخيارات السريعة أدناه.
            </p>
        </div>
        
        <div class="quick-actions">
            <a href="mm.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-globe"></i>
                </div>
                <h3 class="action-title">إدارة محتوى الموقع</h3>
                <p class="action-description">إدارة وتحديث محتوى الموقع الإلكتروني والصفحات والمقالات</p>
            </a>
            
            <a href="mq.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <h3 class="action-title">إدارة عروض الأسعار</h3>
                <p class="action-description">إنشاء ومراجعة وإدارة جميع عروض الأسعار للعملاء</p>
            </a>
            
            <a href="mu.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-users-cog"></i>
                </div>
                <h3 class="action-title">إدارة المستخدمين والصلاحيات</h3>
                <p class="action-description">إدارة العملاء والموظفين وصلاحياتهم في النظام</p>
            </a>
        </div>
    </div>
</body>
</html>