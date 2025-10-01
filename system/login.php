<?php
session_start();

// --- [ بداية الكود المضاف: التحقق من الجلسة ] ---

// التحقق مما إذا كان المستخدم مسجل دخوله بالفعل
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true && isset($_SESSION['login_time'])) {
    // الجلسة صالحة، قم بتحديث وقت آخر نشاط
    $_SESSION['login_time'] = time(); 
    
    // إعادة توجيه المستخدم إلى لوحة التحكم لأنه مسجل دخوله بالفعل
    header('Location: https://alfagolden.com/system/hh.php');
    exit;
}

// --- [ نهاية الكود المضاف ] ---

// --- [ نهاية الكود المضاف ] ---


// هذا الجزء سيعمل فقط إذا لم يكن المستخدم مسجل دخوله
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'send_phone') {
        $phone = $_POST['phone'] ?? '';
        
        if (!preg_match('/^5[0-9]{8}$/', $phone)) {
            echo json_encode(['success' => false, 'message' => 'رقم الجوال غير صحيح']);
            exit;
        }
        
        $webhook_url = 'https://n8n.alfagolden.com/webhook/db2b4fc4-c8f0-45e2-a31c-f5cc9cc76c40';
        $data = [
            'action' => 'verify_phone',
            'phone' => '+966' . $phone,
            'timestamp' => time()
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $webhook_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            $webhook_response = json_decode($response, true);
            
            if ($webhook_response && is_array($webhook_response) && count($webhook_response) > 0) {
                $first_item = $webhook_response[0];
                $user_id = $first_item['id'] ?? $first_item['Id'] ?? null;
                
                if ($user_id) {
                    $_SESSION['phone_verification'] = '+966' . $phone;
                    $_SESSION['verification_time'] = time();
                    $_SESSION['user_id'] = $user_id;
                    
                    echo json_encode(['success' => true, 'message' => 'تم إرسال رمز التحقق']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'خطأ في استقبال بيانات المستخدم']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'استجابة غير صحيحة من الخادم']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'حدث خطأ في إرسال رمز التحقق']);
        }
        exit;
    }
    
    if ($_POST['action'] === 'verify_code') {
        $code = $_POST['code'] ?? '';
        $phone = $_SESSION['phone_verification'] ?? '';
        $user_id = $_SESSION['user_id'] ?? null;
        
        if (!$phone) {
            echo json_encode(['success' => false, 'message' => 'جلسة منتهية، يرجى المحاولة مرة أخرى']);
            exit;
        }
        
        if (time() - ($_SESSION['verification_time'] ?? 0) > 300) {
            unset($_SESSION['phone_verification'], $_SESSION['verification_time'], $_SESSION['user_id']);
            echo json_encode(['success' => false, 'message' => 'انتهت صلاحية رمز التحقق']);
            exit;
        }
        
        $verification_webhook_url = 'https://n8n.alfagolden.com/webhook/5aced04e-62a2-43c3-9718-58b321bfd63e';
        $data = [
            'action' => 'verify_code',
            'phone' => $phone,
            'code' => $code,
            'id' => $user_id,
            'timestamp' => time()
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $verification_webhook_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            $result = json_decode($response, true);
            if ($result && isset($result['success']) && $result['success']) {
                // عند نجاح تسجيل الدخول، يتم إنشاء جلسة المستخدم
                $_SESSION['user_logged_in'] = true;
                $_SESSION['user_phone'] = $phone;
                $_SESSION['user_id'] = $user_id;
                $_SESSION['login_time'] = time(); // الأهم: تسجيل وقت الدخول
                
                unset($_SESSION['phone_verification'], $_SESSION['verification_time']);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'تم تسجيل الدخول بنجاح', 
                    'redirect' => 'https://alfagolden.com/system/hh.php'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'رمز التحقق غير صحيح']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'حدث خطأ في التحقق من الكود']);
        }
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - شركة ألفا الذهبية</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html, body {
            width: 100%;
            height: 100%;
            font-family: 'Cairo', sans-serif;
            overflow: hidden;
            position: relative;
            background: url('/b.svg') no-repeat center center/cover;
        }
        
        .white-layer {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.5);
            z-index: 1;
        }
        
        .login-container {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 90%;
            max-width: 500px;
            background: rgba(255, 255, 255, 0.0);
            border-radius: 130px 130px 50px 50px;
            backdrop-filter: blur(6px);
            border: 2px solid rgb(224, 224, 224);
            padding: 80px 30px 40px;
            text-align: center;
            z-index: 2;
        }

        .logo-container {
            position: absolute;
            top: -40px;
            left: 50%;
            transform: translateX(-50%);
            background: #fff;
            padding: 10px;
            border-radius: 15px;
            width: 220px;
            height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .logo-container img {
            width: 100%;
            height: auto;
            object-fit: contain;
        }

        .login-title {
            font-size: 1.8rem;
            font-weight: 800;
            color: #977e2b;
            margin: 20px 0 30px 0;
        }

        .phone-input-container {
            margin-bottom: 20px;
            position: relative;
        }

        .phone-wrapper {
            display: flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid rgba(151, 126, 43, 0.3);
            border-radius: 25px;
            padding: 15px 20px;
            font-size: 1.1rem;
            direction: ltr;
        }

        .phone-wrapper:focus-within {
            border-color: #977e2b;
            box-shadow: 0 0 10px rgba(151, 126, 43, 0.3);
        }

        .country-code {
            color: #999;
            font-weight: 600;
            margin-left: 10px;
            user-select: none;
        }

        .phone-input {
            flex: 1;
            border: none;
            outline: none;
            background: transparent;
            font-size: 1.1rem;
            font-family: 'Cairo', sans-serif;
            color: #333;
            text-align: left;
            direction: ltr;
            margin-left: 10px;
        }

        .phone-input::placeholder {
            color: #bbb;
            font-weight: 400;
        }

        .verification-container {
            display: none;
            margin-bottom: 20px;
        }

        .verification-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 15px;
        }

        .otp-container {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
            direction: ltr;
        }

        .otp-input {
            width: 50px;
            height: 50px;
            border: 2px solid rgba(151, 126, 43, 0.3);
            border-radius: 10px;
            text-align: center;
            font-size: 1.4rem;
            font-weight: 700;
            font-family: 'Cairo', sans-serif;
            background: rgba(255, 255, 255, 0.9);
            color: #333;
            outline: none;
            transition: all 0.3s ease;
        }

        .otp-input:focus {
            border-color: #977e2b;
            box-shadow: 0 0 10px rgba(151, 126, 43, 0.3);
        }

        .otp-input.filled {
            background: rgba(151, 126, 43, 0.1);
            border-color: #977e2b;
        }

        .btn {
            background: #977e2b;
            color: #fff;
            border: none;
            border-radius: 25px;
            padding: 15px 30px;
            font-size: 1.1rem;
            font-weight: 700;
            font-family: 'Cairo', sans-serif;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 15px;
        }

        .btn:hover {
            background: #887127;
            transform: translateY(-2px);
        }

        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        .btn.loading {
            position: relative;
            color: transparent;
        }

        .btn.loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid #fff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }

        .message {
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            font-weight: 600;
            text-align: center;
        }

        .message.success {
            background: rgba(76, 175, 80, 0.1);
            color: #4CAF50;
            border: 1px solid rgba(76, 175, 80, 0.3);
        }

        .message.error {
            background: rgba(244, 67, 54, 0.1);
            color: #f44336;
            border: 1px solid rgba(244, 67, 54, 0.3);
        }

        .resend-link {
            color: #977e2b;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
        }

        .resend-link:hover {
            text-decoration: underline;
        }

        .timer {
            color: #666;
            font-size: 0.9rem;
            margin-top: 10px;
        }

        @media (max-width: 768px) {
            .login-container {
                width: 95%;
                padding: 70px 20px 30px;
            }
            
            .logo-container {
                width: 180px;
                height: 100px;
            }
            
            .login-title {
                font-size: 1.5rem;
            }
            
            .otp-container {
                gap: 8px;
            }
            
            .otp-input {
                width: 45px;
                height: 45px;
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
    <div class="white-layer"></div>
    
    <div class="login-container">
        <div class="logo-container">
            <img src="../logo.png" alt="الشعار">
        </div>
        
        <h1 class="login-title">تسجيل الدخول</h1>
        
        <div id="messageContainer"></div>
        
        <div id="phoneContainer" class="phone-input-container">
            <div class="phone-wrapper">
                <span class="country-code">+966</span>
                <input 
                    type="tel" 
                    id="phoneInput" 
                    class="phone-input"
                    placeholder="5xxxxxxxx"
                    maxlength="9"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    autocomplete="tel"
                >
            </div>
            <button type="button" id="sendCodeBtn" class="btn">إرسال رمز التحقق</button>
        </div>
        
        <div id="verificationContainer" class="verification-container">
            <h3 class="verification-title">أدخل رمز التحقق</h3>
            
            <!-- Input مخفي للتعبئة التلقائية -->
            <input 
                type="text" 
                id="otpAutoFill" 
                autocomplete="one-time-code" 
                style="position: absolute; left: -9999px; opacity: 0; pointer-events: none;"
                tabindex="-1"
            >
            
            <div class="otp-container">
                <input type="tel" class="otp-input" maxlength="1" inputmode="numeric">
                <input type="tel" class="otp-input" maxlength="1" inputmode="numeric">
                <input type="tel" class="otp-input" maxlength="1" inputmode="numeric">
                <input type="tel" class="otp-input" maxlength="1" inputmode="numeric">
            </div>
            
            <div class="timer" id="timer"></div>
            <a href="#" class="resend-link" id="resendLink" style="display: none;">إعادة إرسال الرمز</a>
        </div>
    </div>

    <script>
        class OTPLogin {
            constructor() {
                this.phoneInput = document.getElementById('phoneInput');
                this.sendCodeBtn = document.getElementById('sendCodeBtn');
                this.phoneContainer = document.getElementById('phoneContainer');
                this.verificationContainer = document.getElementById('verificationContainer');
                this.otpInputs = document.querySelectorAll('.otp-input');
                this.otpAutoFill = document.getElementById('otpAutoFill');
                this.messageContainer = document.getElementById('messageContainer');
                this.timer = document.getElementById('timer');
                this.resendLink = document.getElementById('resendLink');
                
                this.timeLeft = 0;
                this.timerInterval = null;
                this.isProcessing = false;
                this.autoFillInterval = null;
                
                this.init();
            }
            
            init() {
                this.bindEvents();
                this.setupAutoFill();
            }
            
            bindEvents() {
                // Phone input events
                this.phoneInput.addEventListener('input', this.handlePhoneInput.bind(this));
                this.phoneInput.addEventListener('keypress', this.handleNumericInput.bind(this));
                
                // Send button
                this.sendCodeBtn.addEventListener('click', this.sendCode.bind(this));
                
                // OTP inputs
                this.otpInputs.forEach((input, index) => {
                    input.addEventListener('input', (e) => this.handleOtpInput(e, index));
                    input.addEventListener('keydown', (e) => this.handleOtpKeydown(e, index));
                    input.addEventListener('paste', this.handleOtpPaste.bind(this));
                });
                
                // Resend link
                this.resendLink.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.sendCode();
                });
            }
            
            setupAutoFill() {
                // AutoFill input listener
                this.otpAutoFill.addEventListener('input', (e) => {
                    const value = e.target.value.replace(/[^0-9]/g, '');
                    if (value.length >= 4) {
                        this.fillOtpCode(value.substring(0, 4));
                    }
                });

                // WebOTP API للأندرويد
                if ('OTPCredential' in window) {
                    navigator.credentials.get({
                        otp: { transport: ['sms'] }
                    }).then(otp => {
                        const code = otp.code.replace(/[^0-9]/g, '');
                        if (code.length >= 4) {
                            this.fillOtpCode(code.substring(0, 4));
                        }
                    }).catch(() => {
                        // التعبئة التلقائية غير متاحة
                    });
                }

                // Polling للتحقق من التعبئة التلقائية (fallback)
                this.startAutoFillPolling();
            }
            
            startAutoFillPolling() {
                if (this.autoFillInterval) {
                    clearInterval(this.autoFillInterval);
                }
                
                this.autoFillInterval = setInterval(() => {
                    const value = this.otpAutoFill.value.replace(/[^0-9]/g, '');
                    if (value.length >= 4) {
                        this.fillOtpCode(value.substring(0, 4));
                        clearInterval(this.autoFillInterval);
                    }
                }, 500);
            }
            
            stopAutoFillPolling() {
                if (this.autoFillInterval) {
                    clearInterval(this.autoFillInterval);
                    this.autoFillInterval = null;
                }
            }
            
            fillOtpCode(code) {
                for (let i = 0; i < 4; i++) {
                    if (this.otpInputs[i] && code[i]) {
                        this.otpInputs[i].value = code[i];
                        this.otpInputs[i].classList.add('filled');
                    }
                }
                this.checkOtpComplete();
                this.stopAutoFillPolling();
            }
            
            handlePhoneInput(e) {
                let value = e.target.value.replace(/[^0-9]/g, '');
                
                if (value.startsWith('0')) {
                    value = value.substring(1);
                }
                
                if (value.length > 0 && value[0] !== '5') {
                    this.showMessage('رقم الجوال يجب أن يبدأ بالرقم 5', 'error');
                    value = '';
                }
                
                if (value.length > 9) {
                    value = value.substring(0, 9);
                }
                
                e.target.value = value;
                this.clearMessage();
            }
            
            handleNumericInput(e) {
                if (!/[0-9]/.test(e.key) && !['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab'].includes(e.key)) {
                    e.preventDefault();
                }
            }
            
            handleOtpInput(e, index) {
                const value = e.target.value.replace(/[^0-9]/g, '');
                e.target.value = value;
                
                if (value) {
                    e.target.classList.add('filled');
                    if (index < 3) {
                        this.otpInputs[index + 1].focus();
                    }
                } else {
                    e.target.classList.remove('filled');
                }
                
                this.checkOtpComplete();
            }
            
            handleOtpKeydown(e, index) {
                if (e.key === 'Backspace' && !e.target.value && index > 0) {
                    this.otpInputs[index - 1].focus();
                }
            }
            
            handleOtpPaste(e) {
                e.preventDefault();
                const paste = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '');
                
                if (paste.length >= 4) {
                    this.fillOtpCode(paste.substring(0, 4));
                }
            }
            
            checkOtpComplete() {
                const code = Array.from(this.otpInputs).map(input => input.value).join('');
                if (code.length === 4 && !this.isProcessing) {
                    this.verifyCode(code);
                }
            }
            
            async sendCode() {
                const phone = this.phoneInput.value.trim();
                
                if (!phone) {
                    this.showMessage('يرجى إدخال رقم الجوال', 'error');
                    return;
                }
                
                if (!/^5[0-9]{8}$/.test(phone)) {
                    this.showMessage('رقم الجوال غير صحيح', 'error');
                    return;
                }
                
                this.setLoading(true);
                this.clearMessage();
                
                try {
                    const response = await this.makeRequest('send_phone', { phone });
                    
                    if (response.success) {
                        this.showMessage(response.message, 'success');
                        this.showVerificationSection();
                        this.startTimer(60); 
                    } else {
                        this.showMessage(response.message, 'error');
                    }
                } catch (error) {
                    this.showMessage('حدث خطأ في الاتصال', 'error');
                } finally {
                    this.setLoading(false);
                }
            }
            
            async verifyCode(code) {
                if (this.isProcessing) return;
                
                this.isProcessing = true;
                this.clearMessage();
                
                try {
                    const response = await this.makeRequest('verify_code', { code });
                    
                    if (response.success) {
                        this.showMessage(response.message, 'success');
                        setTimeout(() => {
                            if (response.redirect) {
                                window.location.href = response.redirect;
                            }
                        }, 1000);
                    } else {
                        this.showMessage(response.message, 'error');
                        this.clearOtpInputs();
                    }
                } catch (error) {
                    this.showMessage('حدث خطأ في التحقق', 'error');
                    this.clearOtpInputs();
                } finally {
                    this.isProcessing = false;
                }
            }
            
            async makeRequest(action, data) {
                const formData = new FormData();
                formData.append('ajax', '1');
                formData.append('action', action);
                
                Object.keys(data).forEach(key => {
                    formData.append(key, data[key]);
                });
                
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                return await response.json();
            }
            
            showVerificationSection() {
                this.phoneContainer.style.display = 'none';
                this.verificationContainer.style.display = 'block';
                
                // تنظيف وإعداد التعبئة التلقائية
                this.otpAutoFill.value = '';
                this.startAutoFillPolling();
                
                setTimeout(() => {
                    this.otpInputs[0].focus();
                }, 100);
            }
            
            clearOtpInputs() {
                this.otpInputs.forEach(input => {
                    input.value = '';
                    input.classList.remove('filled');
                });
                this.otpAutoFill.value = '';
                this.otpInputs[0].focus();
            }
            
            showMessage(message, type) {
                this.messageContainer.innerHTML = `<div class="message ${type}">${message}</div>`;
            }
            
            clearMessage() {
                this.messageContainer.innerHTML = '';
            }
            
            setLoading(loading) {
                if (loading) {
                    this.sendCodeBtn.classList.add('loading');
                    this.sendCodeBtn.disabled = true;
                } else {
                    this.sendCodeBtn.classList.remove('loading');
                    this.sendCodeBtn.disabled = false;
                }
            }
            
            startTimer(seconds) {
                if (this.timerInterval) {
                     clearInterval(this.timerInterval);
                }
                this.timeLeft = seconds;
                this.timer.style.display = 'block';
                this.resendLink.style.display = 'none';
                
                this.timerInterval = setInterval(() => {
                    this.timeLeft--;
                    this.updateTimer();
                    
                    if (this.timeLeft <= 0) {
                        clearInterval(this.timerInterval);
                        this.timer.style.display = 'none';
                        this.resendLink.style.display = 'inline-block';
                    }
                }, 1000);
                this.updateTimer();
            }
            
            updateTimer() {
                const minutes = Math.floor(this.timeLeft / 60);
                let seconds = this.timeLeft % 60;
                seconds = seconds < 10 ? '0' + seconds : seconds;
                this.timer.textContent = `إعادة الإرسال متاحة بعد ${minutes}:${seconds}`;
            }
            
            // تنظيف الـ intervals عند إغلاق الصفحة
            cleanup() {
                this.stopAutoFillPolling();
                if (this.timerInterval) {
                    clearInterval(this.timerInterval);
                }
            }
        }
        
        let otpLogin;
        
        document.addEventListener('DOMContentLoaded', () => {
            otpLogin = new OTPLogin();
        });
        
        window.addEventListener('beforeunload', () => {
            if (otpLogin) {
                otpLogin.cleanup();
            }
        });
    </script>
</body>
</html>