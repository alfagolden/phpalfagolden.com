<?php
$quote_id = isset($_GET['quote_id']) ? intval($_GET['quote_id']) : 1;

function getFullPageContent($page_file, $quote_id) {
    $url = "https://alfagolden.com/system/q/p/{$page_file}?quote_id={$quote_id}";
    $content = file_get_contents($url);
    
    $content = preg_replace('/<html[^>]*>/i', '<div class="page-wrapper">', $content);
    $content = str_replace('</html>', '</div>', $content);
    $content = preg_replace('/<body[^>]*>/i', '<div class="page-body">', $content);
    $content = str_replace('</body>', '</div>', $content);
    
    return $content;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عرض سعر كامل - شركة ألفا الذهبية للمصاعد</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Cairo', sans-serif;
        }
        
        .no-print {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            margin-bottom: 20px;
            border-radius: 8px;
        }
        
        .print-button {
            background: #9c7d2d;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            font-family: 'Cairo', sans-serif;
        }
        
        .print-button:hover {
            background: #7a632a;
        }

        .page-wrapper {
            page-break-after: always;
            margin-bottom: 20px;
        }
        
        .page-wrapper:last-child {
            page-break-after: auto;
        }

        @media print {
            @page {
                size: A4;
                margin: 15mm;
            }
            
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .no-print {
                display: none !important;
            }
            
            .page-wrapper {
                margin-bottom: 0;
            }
        }
    </style>
</head>
<body>

<div class="no-print">
    <button class="print-button" onclick="window.print()">طباعة العرض الكامل</button>
</div>

<?php 
$pages = ['p1.php', 'p2.php', 'p3.php', 'p4.php', 'p5.php', 'p6.php', 'p7.php'];

foreach($pages as $page) {
    echo getFullPageContent($page, $quote_id);
}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const elements = document.querySelectorAll('.spec-value');
    elements.forEach(function(element) {
        if (!element.textContent.trim()) {
            const container = element.closest('tr, .spec-item');
            if (container) {
                container.style.display = 'none';
            }
        }
    });
});
</script>

</body>
</html>