<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Baserow Fields</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: #f5f5f5;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        input {
            width: 200px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-left: 10px;
        }
        
        button {
            background: #007cba;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        button:hover {
            background: #005a8b;
        }
        
        .result {
            margin-top: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .field-row {
            padding: 8px 12px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
        }
        
        .field-row:last-child {
            border-bottom: none;
        }
        
        .field-row:nth-child(even) {
            background: #f9f9f9;
        }
        
        .field-id {
            font-weight: bold;
            color: #666;
            min-width: 50px;
        }
        
        .field-name {
            color: #333;
        }
        
        .error {
            color: red;
            background: #fee;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
        }
        
        .loading {
            color: #666;
            font-style: italic;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>عرض حقول الجدول</h2>
        
        <div>
            <label>Table ID:</label>
            <input type="number" id="tableId" placeholder="711" />
            <button onclick="getFields()">عرض الحقول</button>
        </div>
        
        <div id="loading" class="loading" style="display: none;">جاري التحميل...</div>
        <div id="error" class="error" style="display: none;"></div>
        <div id="results"></div>
    </div>

    <script>
        const API_BASE = 'https://base.alfagolden.com';
        const API_TOKEN = 'h5qAt85gtiJDAzpH51WrXPywhmnhrPWy';

        async function getFields() {
            const tableId = document.getElementById('tableId').value;
            
            if (!tableId) {
                showError('أدخل Table ID');
                return;
            }

            showLoading(true);
            clearResults();

            try {
                const response = await fetch(`${API_BASE}/api/database/fields/table/${tableId}/`, {
                    headers: {
                        'Authorization': `Token ${API_TOKEN}`,
                        'Content-Type': 'application/json',
                    },
                });

                if (!response.ok) {
                    throw new Error(`خطأ ${response.status}`);
                }

                const fields = await response.json();
                displayFields(fields);
                
            } catch (error) {
                showError(`خطأ: ${error.message}`);
            } finally {
                showLoading(false);
            }
        }

        function displayFields(fields) {
            const results = document.getElementById('results');
            
            if (fields.length === 0) {
                results.innerHTML = '<p>لا توجد حقول</p>';
                return;
            }

            let html = '<div class="result">';
            fields.forEach(field => {
                html += `
                    <div class="field-row">
                        <span class="field-id">${field.id}</span>
                        <span class="field-name">${field.name}</span>
                    </div>
                `;
            });
            html += '</div>';
            
            results.innerHTML = html;
        }

        function showLoading(show) {
            document.getElementById('loading').style.display = show ? 'block' : 'none';
        }

        function showError(message) {
            const errorDiv = document.getElementById('error');
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
        }

        function clearResults() {
            document.getElementById('results').innerHTML = '';
            document.getElementById('error').style.display = 'none';
        }

        // Enter key support
        document.getElementById('tableId').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                getFields();
            }
        });
    </script>
</body>
</html>