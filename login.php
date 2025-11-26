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

// Rate limiting initialization
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt_time'] = time();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Çok fazla deneme kontrolü - POST'tan önce kontrol et
    if ($_SESSION['login_attempts'] >= 5 && (time() - $_SESSION['last_attempt_time']) < 900) {
        $error = 'Çok fazla hatalı giriş denemesi. Lütfen 15 dakika sonra tekrar deneyin.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        // Validasyon
        if (empty($email) || empty($password)) {
            $error = 'E-posta ve şifre gereklidir.';
            $_SESSION['login_attempts']++;
            $_SESSION['last_attempt_time'] = time();
        } else {
            // Veritabanında kullanıcıyı ara
            $stmt = $conn->prepare("SELECT id, username, email, password, role, address FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Şifre kontrolü
                if (password_verify($password, $user['password'])) {
                    // Başarılı giriş - attemptleri sıfırla
                    $_SESSION['login_attempts'] = 0;
                    
                    // Oturum başlat
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['address'] = $user['address'];

                    // Misafir sepetini kullanıcıya aktar
                    if (function_exists('transferGuestCartToUser')) {
                        transferGuestCartToUser($user['id'], $conn);
                    }

                    // Başarılı giriş
                    if ($user['role'] === 'admin') {
                        header("Location: /admin/dashboard.php");
                    } else {
                        // Yönlendirme parametresini kontrol et
                        $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';
                        header("Location: $redirect");
                    }
                    exit();
                } else {
                    $error = 'E-posta veya şifre hatalı.';
                    $_SESSION['login_attempts']++;
                    $_SESSION['last_attempt_time'] = time();
                }
            } else {
                $error = 'E-posta veya şifre hatalı.';
                $_SESSION['login_attempts']++;
                $_SESSION['last_attempt_time'] = time();
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap - Kafkas Boya</title>
    
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
        .login-wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 1000px;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            border-radius: 30px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            overflow: hidden;
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 550px;
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Left Side - Info */
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
            left: -100px;
        }

        .info-side::after {
            content: '';
            position: absolute;
            width: 200px;
            height: 200px;
            background: rgba(212, 175, 55, 0.15);
            border-radius: 50%;
            bottom: -50px;
            right: -50px;
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

        /* Right Side - Form */
        .form-side {
            padding: 50px 40px;
            background: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .brand {
            text-align: center;
            margin-bottom: 30px;
        }

        .brand-logo {
            width: 150px;
            height: auto;
            margin-bottom: 15px;
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

        /* Remember Me */
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .checkbox-wrapper input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: var(--primary);
        }

        .checkbox-wrapper label {
            font-size: 14px;
            color: #64748b;
            cursor: pointer;
            margin: 0;
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

        /* Responsive */
        @media (max-width: 768px) {
            .login-container {
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
    </style>
</head>
<body>
    <div class="particles">
    </div>

    <div class="login-wrapper">
        <div class="login-container">
            <!-- Info Side -->
            <div class="info-side">
                <div class="info-content">
                    <div class="info-icon">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <h2>Tekrar Hoşgeldiniz!</h2>
                    <p>Hesabınıza giriş yapın ve alışverişe başlayın</p>

                    <div class="features">
                        <div class="feature">
                            <div class="feature-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div class="feature-text">
                                <strong>Güvenli Alışveriş</strong>
                                <small>256-bit SSL şifreleme</small>
                            </div>
                        </div>

                        <div class="feature">
                            <div class="feature-icon">
                                <i class="fas fa-truck"></i>
                            </div>
                            <div class="feature-text">
                                <strong>Sipariş Takibi</strong>
                                <small>Gerçek zamanlı güncelleme</small>
                            </div>
                        </div>

                        <div class="feature">
                            <div class="feature-icon">
                                <i class="fas fa-bolt"></i>
                            </div>
                            <div class="feature-text">
                                <strong>Hızlı Checkout</strong>
                                <small>Kayıtlı adresleriniz</small>
                            </div>
                        </div>

                        <div class="feature">
                            <div class="feature-icon">
                                <i class="fas fa-percent"></i>
                            </div>
                            <div class="feature-text">
                                <strong>Özel İndirimler</strong>
                                <small>Sadece üyelere özel</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Side -->
            <div class="form-side">
                <div class="brand">
                    <img src="assets/img/kafkasboya-logo.png" alt="Kafkas Boya Logo" class="brand-logo">
                    <p>Hesabınıza giriş yapın</p>
                </div>
                
                <?php if ($error && strpos($error, 'Çok fazla hatalı giriş denemesi') !== false): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-clock me-2"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                        <br>
                        <small>Lütfen 15 dakika sonra tekrar deneyin.</small>
                    </div>
                <?php elseif ($error): ?>
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

                <form method="POST">
                    <div class="form-group">
                        <label for="email">E-posta Adresi</label>
                        <div class="input-wrapper">
                            <input type="email" id="email" name="email" class="form-control" 
                                   placeholder="ornek@example.com" required>
                            <i class="fas fa-envelope input-icon"></i>
                        </div>
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
                    </div>

                    <div class="form-options">
                        <div class="checkbox-wrapper">
                            <input type="checkbox" id="remember_me" name="remember_me">
                            <label for="remember_me">Beni Hatırla</label>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="fas fa-sign-in-alt"></i> Giriş Yap
                    </button>
                </form>

                <div class="form-footer">
                    <p>Hesabınız yok mu? <a href="register.php">Üye Ol</a></p>
                    <a href="index.php" class="back-link">
                        <i class="fas fa-arrow-left"></i> Anasayfaya Dön
                    </a>
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
    </script>
</body>
</html>