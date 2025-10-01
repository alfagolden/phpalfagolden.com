<?php
$quote_id = isset($_GET['quote_id']) ? intval($_GET['quote_id']) : 1;

// معالجة طلب إنشاء PDF
if (isset($_POST['action']) && $_POST['action'] === 'generate_pdf') {
    header('Content-Type: application/json');
    
    $postData = [
        'action' => 'generate_pdf',
        'quote_id' => $quote_id
    ];
    
    $ch = curl_init('https://alfagolden.com/system/docs/template_processor.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    echo $response;
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عرض السعر #<?= $quote_id ?> - PDF</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Cairo', sans-serif;
            background: #f8f9fa;
            overflow: hidden;
        }
        
        #loader {
            position: fixed;
            inset: 0;
            background: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #e5e7eb;
            border-top-color: #977e2b;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        #loader p {
            margin-top: 20px;
            color: #666;
            font-size: 16px;
            font-weight: 600;
        }
        
        #pdfViewer {
            width: 100vw;
            height: 100vh;
            border: none;
            display: none;
        }
        
        #error {
            display: none;
            position: fixed;
            inset: 0;
            background: white;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #dc2626;
            padding: 20px;
            text-align: center;
        }
        
        #error p {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        #error small {
            font-size: 14px;
            color: #666;
        }
    </style>
</head>
<body>
    <div id="loader">
        <div class="spinner"></div>
        <p id="loaderText">جاري إنشاء ملف PDF...</p>
    </div>
    
    <div id="error">
        <p>حدث خطأ في إنشاء الملف</p>
        <small id="errorMessage"></small>
    </div>
    
    <iframe id="pdfViewer"></iframe>

    <script>
        const quoteId = <?= $quote_id ?>;
        
        const loader = document.getElementById('loader');
        const loaderText = document.getElementById('loaderText');
        const pdfViewer = document.getElementById('pdfViewer');
        const error = document.getElementById('error');
        const errorMessage = document.getElementById('errorMessage');

        async function generatePdf() {
            try {
                const formData = new FormData();
                formData.append('action', 'generate_pdf');
                
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success && result.file_url) {
                    showPdf(result.file_url);
                } else {
                    throw new Error(result.error || 'فشل إنشاء الملف');
                }
            } catch (err) {
                console.error('خطأ في إنشاء PDF:', err);
                showError(err.message);
            }
        }

        function showPdf(url) {
            pdfViewer.src = url;
            pdfViewer.style.display = 'block';
            loader.style.display = 'none';
        }

        function showError(message) {
            loader.style.display = 'none';
            error.style.display = 'flex';
            errorMessage.textContent = message;
        }

        window.addEventListener('load', generatePdf);
    </script>
</body>
</html>