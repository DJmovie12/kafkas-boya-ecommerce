<?php
require_once 'includes/db_connect.php';
require_once 'includes/session.php';

// Zaten giriş yapmışsa, ana sayfaya yönlendir
if (isUserLoggedIn()) {
    header("Location: /index.php");
    exit();
}

// transferGuestCartToUser fonksiyonunun var olduğundan emin ol
if (!function_exists('transferGuestCartToUser')) {
    require_once 'includes/session.php';
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $password_confirm = trim($_POST['password_confirm'] ?? '');
    $address = trim($_POST['address'] ?? '');

    // Sıkı validasyon
    if (empty($username) || empty($email) || empty($password) || empty($password_confirm) || empty($address)) {
        $error = 'Tüm alanlar gereklidir.';
    } elseif (strlen($username) < 3 || strlen($username) > 20) {
        $error = 'Kullanıcı adı 3-20 karakter arasında olmalıdır.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = 'Kullanıcı adı sadece harf, rakam ve alt çizgi içerebilir.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Geçerli bir e-posta adresi girin.';
    } elseif (strlen($password) < 8) {
        $error = 'Şifre en az 8 karakter olmalıdır.';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error = 'Şifre en az bir büyük harf içermelidir.';
    } elseif (!preg_match('/[a-z]/', $password)) {
        $error = 'Şifre en az bir küçük harf içermelidir.';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = 'Şifre en az bir rakam içermelidir.';
    } elseif ($password !== $password_confirm) {
        $error = 'Şifreler eşleşmiyor.';
    } elseif (strlen($address) < 10) {
        $error = 'Adres en az 10 karakter olmalıdır.';
    } else {
        // E-posta veya kullanıcı adının zaten var olup olmadığını kontrol et
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->bind_param("ss", $email, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = 'Bu e-posta adresi veya kullanıcı adı zaten kayıtlı.';
        } else {
            // Şifreyi hashle
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            // Yeni kullanıcıyı ekle
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, address, role) VALUES (?, ?, ?, ?, 'user')");
            $stmt->bind_param("ssss", $username, $email, $hashed_password, $address);

if ($stmt->execute()) {
    // Yeni kullanıcının ID'sini al
    $new_user_id = $stmt->insert_id;
    

    $_SESSION['user_id'] = $new_user_id;
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;
    $_SESSION['role'] = 'user';
    $_SESSION['address'] = $address;
    
    // Misafir sepetini yeni kullanıcıya aktar
    if (function_exists('transferGuestCartToUser')) {
        transferGuestCartToUser($new_user_id, $conn);
    }
    
    $success = 'Kayıt başarılı! Yönlendiriliyorsunuz...';
    header("refresh:2;url=/index.php");
            } else {
                $error = 'Kayıt sırasında bir hata oluştu. Lütfen tekrar deneyin.';
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Üye Ol - Kafkas Boya</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #4A90E2;
            --primary-dark: #2C6EBB;
            --primary-light: #7FB3F0;
            --secondary: #D4AF37;
            --secondary-dark: #B8941F;
            --accent: #8B4513;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #4A90E2 0%, #2C6EBB 50%, #7FB3F0 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        /* Animated Background Particles */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
            pointer-events: none;
        }

        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 50%;
            animation: float 15s infinite;
        }

        .particle:nth-child(1) { width: 80px; height: 80px; left: 10%; animation-delay: 0s; }
        .particle:nth-child(2) { width: 60px; height: 60px; left: 30%; animation-delay: 2s; }
        .particle:nth-child(3) { width: 100px; height: 100px; left: 50%; animation-delay: 4s; }
        .particle:nth-child(4) { width: 70px; height: 70px; left: 70%; animation-delay: 6s; }
        .particle:nth-child(5) { width: 90px; height: 90px; left: 85%; animation-delay: 8s; }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); opacity: 0.3; }
            50% { transform: translateY(-100px) rotate(180deg); opacity: 0.6; }
        }

        /* Main Container */
        .register-wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 1000px;
        }

        .register-container {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            border-radius: 30px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            overflow: hidden;
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 650px;
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Left Side - Form */
        .form-side {
            padding: 20px 15px;
            background: white;
        }

        .brand {
            text-align: center;
            margin-bottom: 15px;
        }

        .brand-logo {
            width: 150px;
            height: auto;
            margin-bottom: 5px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .brand h1 {
            font-size: 28px;
            font-weight: 700;
            font-family: 'Playfair Display', serif;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 5px;
        }

        .brand p {
            color: #64748b;
            font-size: 14px;
        }

        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 15px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .alert-danger {
            background: #fee;
            color: #E74C3C;
            border: 1px solid #fcc;
        }

        .alert-success {
            background: #d1fae5;
            color: #10b981;
            border: 1px solid #a7f3d0;
        }

        .alert i {
            font-size: 18px;
        }

        /* Form Groups */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #1e293b;
            font-weight: 500;
            font-size: 14px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 16px;
            transition: all 0.3s;
        }

        .form-control {
            width: 100%;
            padding: 14px 18px 14px 50px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s;
            background: #f8fafc;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 4px rgba(74, 144, 226, 0.1);
        }

        .form-control:focus + .input-icon {
            color: var(--primary);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
            padding-top: 14px;
        }

        /* Password Toggle */
        .password-toggle {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
        }

        .password-toggle:hover {
            color: var(--primary);
        }

        /* Password Strength */
        .password-strength {
            height: 4px;
            background: #e2e8f0;
            border-radius: 2px;
            margin-top: 8px;
            overflow: hidden;
        }

        .strength-bar {
            height: 100%;
            width: 0;
            transition: all 0.3s;
            border-radius: 2px;
        }

        .strength-text {
            font-size: 12px;
            margin-top: 5px;
            font-weight: 500;
        }

        /* Submit Button */
        .btn-submit {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .btn-submit::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }

        .btn-submit:hover::before {
            left: 100%;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(74, 144, 226, 0.4);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        /* Links */
        .form-footer {
            text-align: center;
            margin-top: 25px;
        }

        .form-footer p {
            color: #64748b;
            font-size: 14px;
        }

        .form-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .form-footer a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            color: #64748b;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            margin-top: 15px;
        }

        .back-link:hover {
            border-color: var(--primary);
            color: var(--primary);
            transform: translateX(-5px);
        }

        /* Right Side - Info */
        .info-side {
            background: linear-gradient(135deg, rgba(74, 144, 226, 0.95), rgba(44, 110, 187, 0.95));
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .info-side::before {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: rgba(212, 175, 55, 0.2);
            border-radius: 50%;
            top: -100px;
            right: -100px;
        }

        .info-side::after {
            content: '';
            position: absolute;
            width: 200px;
            height: 200px;
            background: rgba(212, 175, 55, 0.15);
            border-radius: 50%;
            bottom: -50px;
            left: -50px;
        }

        .info-content {
            position: relative;
            z-index: 2;
            text-align: center;
        }

        .info-icon {
            font-size: 80px;
            margin-bottom: 30px;
            opacity: 0.9;
            color: var(--secondary);
            animation: bounce 3s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        .info-content h2 {
            font-size: 32px;
            font-weight: 700;
            font-family: 'Playfair Display', serif;
            margin-bottom: 15px;
        }

        .info-content p {
            font-size: 16px;
            opacity: 0.9;
            margin-bottom: 40px;
        }

        .features {
            width: 100%;
            max-width: 350px;
        }

        .feature {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 15px;
            margin-bottom: 15px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s;
        }

        .feature:hover {
            background: rgba(212, 175, 55, 0.2);
            transform: translateX(10px);
            border-color: var(--secondary);
        }

        .feature-icon {
            width: 45px;
            height: 45px;
            background: rgba(212, 175, 55, 0.3);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: var(--secondary);
        }

        .feature-text {
            text-align: left;
        }

        .feature-text strong {
            display: block;
            font-size: 15px;
            margin-bottom: 3px;
        }

        .feature-text small {
            font-size: 13px;
            opacity: 0.8;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .register-container {
                grid-template-columns: 1fr;
            }

            .info-side {
                display: none;
            }

            .form-side {
                padding: 30px 20px;
            }

            .brand h1 {
                font-size: 24px;
            }

            .brand-logo {
                width: 120px;
            }
        }

        /* Small text under inputs */
        .form-text {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <div class="register-wrapper">
        <div class="register-container">
            <!-- Form Side -->
            <div class="form-side">
                <div class="brand">
                    <img src="assets/img/kafkasboya-logo.png" alt="Kafkas Boya Logo" class="brand-logo">
                    <p>Yeni bir hesap oluşturun</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo htmlspecialchars($success); ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" id="registerForm">
                    <div class="form-group">
                        <label for="username">Kullanıcı Adı</label>
                        <div class="input-wrapper">
                            <input type="text" id="username" name="username" class="form-control" 
                                   placeholder="Kullanıcı adınız" required>
                            <i class="fas fa-user input-icon"></i>
                        </div>
                        <div class="form-text">3-20 karakter arası, sadece harf, rakam ve alt çizgi</div>
                    </div>

                    <div class="form-group">
                        <label for="email">E-posta Adresi</label>
                        <div class="input-wrapper">
                            <input type="email" id="email" name="email" class="form-control" 
                                   placeholder="ornek@example.com" required>
                            <i class="fas fa-envelope input-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="address">Adres</label>
                        <div class="input-wrapper">
                            <textarea id="address" name="address" class="form-control" 
                                      placeholder="Tam adresinizi girin" required></textarea>
                            <i class="fas fa-map-marker-alt input-icon"></i>
                        </div>
                        <div class="form-text">Teslimat için en az 10 karakter</div>
                    </div>

                    <div class="form-group">
                        <label for="password">Şifre</label>
                        <div class="input-wrapper">
                            <input type="password" id="password" name="password" class="form-control" 
                                   placeholder="••••••••" required>
                            <i class="fas fa-lock input-icon"></i>
                            <button type="button" class="password-toggle" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength">
                            <div class="strength-bar" id="strengthBar"></div>
                        </div>
                        <div class="form-text strength-text" id="strengthText">En az 8 karakter, büyük harf, küçük harf ve rakam</div>
                    </div>

                    <div class="form-group">
                        <label for="password_confirm">Şifre Tekrar</label>
                        <div class="input-wrapper">
                            <input type="password" id="password_confirm" name="password_confirm" class="form-control" 
                                   placeholder="••••••••" required>
                            <i class="fas fa-lock input-icon"></i>
                            <button type="button" class="password-toggle" id="togglePasswordConfirm">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="fas fa-user-plus"></i> Üye Ol
                    </button>
                </form>

                <div class="form-footer">
                    <p>Zaten hesabınız var mı? <a href="login.php">Giriş Yap</a></p>
                    <a href="index.php" class="back-link">
                        <i class="fas fa-arrow-left"></i> Anasayfaya Dön
                    </a>
                </div>
            </div>

            <!-- Info Side -->
            <div class="info-side">
                <div class="info-content">
                    <div class="info-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <h2>Hoş Geldiniz!</h2>
                    <p>Kafkas Boya ailesine katılın ve özel avantajlardan yararlanın</p>

                    <div class="features">
                        <div class="feature">
                            <div class="feature-icon">
                                <i class="fas fa-shipping-fast"></i>
                            </div>
                            <div class="feature-text">
                                <strong>Hızlı Teslimat</strong>
                                <small>Siparişleriniz anında işleme alınır</small>
                            </div>
                        </div>

                        <div class="feature">
                            <div class="feature-icon">
                                <i class="fas fa-history"></i>
                            </div>
                            <div class="feature-text">
                                <strong>Sipariş Geçmişi</strong>
                                <small>Tüm siparişlerinizi takip edin</small>
                            </div>
                        </div>

                        <div class="feature">
                            <div class="feature-icon">
                                <i class="fas fa-tags"></i>
                            </div>
                            <div class="feature-text">
                                <strong>Özel İndirimler</strong>
                                <small>Üyelere özel kampanyalar</small>
                            </div>
                        </div>

                        <div class="feature">
                            <div class="feature-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div class="feature-text">
                                <strong>Güvenli Ödeme</strong>
                                <small>256-bit SSL şifreleme</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Password Toggle
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });

        document.getElementById('togglePasswordConfirm').addEventListener('click', function() {
            const password = document.getElementById('password_confirm');
            const icon = this.querySelector('i');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });

        // Password Strength Meter
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('strengthText');
            let strength = 0;
            let feedback = [];

            if (password.length >= 8) {
                strength += 25;
            } else {
                feedback.push('En az 8 karakter');
            }

            if (/[A-Z]/.test(password)) {
                strength += 25;
            } else {
                feedback.push('Büyük harf');
            }

            if (/[a-z]/.test(password)) {
                strength += 25;
            } else {
                feedback.push('Küçük harf');
            }

            if (/[0-9]/.test(password)) {
                strength += 25;
            } else {
                feedback.push('Rakam');
            }

            strengthBar.style.width = strength + '%';

            if (strength === 0) {
                strengthBar.style.background = '#e2e8f0';
                strengthText.textContent = 'En az 8 karakter, büyük harf, küçük harf ve rakam';
                strengthText.style.color = '#94a3b8';
            } else if (strength < 50) {
                strengthBar.style.background = '#E74C3C';
                strengthText.textContent = 'Zayıf şifre - Eksik: ' + feedback.join(', ');
                strengthText.style.color = '#E74C3C';
            } else if (strength < 100) {
                strengthBar.style.background = '#F39C12';
                strengthText.textContent = 'Orta şifre - Eksik: ' + feedback.join(', ');
                strengthText.style.color = '#F39C12';
            } else {
                strengthBar.style.background = '#5DADE2';
                strengthText.textContent = 'Güçlü şifre ✓';
                strengthText.style.color = '#5DADE2';
            }
        });

        // Form validation feedback
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const passwordConfirm = document.getElementById('password_confirm').value;

            if (password !== passwordConfirm) {
                e.preventDefault();
                alert('Şifreler eşleşmiyor!');
            }
        });
    </script>
</body>
</html>