<?php
require_once 'includes/db_connect.php';
require_once 'includes/session.php';
require_once 'includes/security.php';

// Güvenlik header'larını ayarla
set_security_headers();

// Zaten giriş yapmışsa, ana sayfaya yönlendir
if (isUserLoggedIn()) {
    header("Location: /index.php");
    exit();
}

// Rate limiting initialization
if (!isset($_SESSION['rate_limits'])) {
    $_SESSION['rate_limits'] = [];
}

$error = '';
$success = '';
$field_errors = [];

// Form verilerini saklamak için
$form_data = [
    'email' => ''
];

// "Beni Hatırla" cookie kontrolü
if (isset($_COOKIE['remember_token']) && !isUserLoggedIn()) {
    $remember_token = secure_input($_COOKIE['remember_token']);
    $stmt = $conn->prepare("SELECT user_id FROM remember_tokens WHERE token = ? AND expires_at > NOW()");
    $stmt->bind_param("s", $remember_token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $token_data = $result->fetch_assoc();
        $user_id = $token_data['user_id'];
        
        // Kullanıcı bilgilerini al
        $user_stmt = $conn->prepare("SELECT id, username, email, role, address FROM users WHERE id = ?");
        $user_stmt->bind_param("i", $user_id);
        $user_stmt->execute();
        $user_result = $user_stmt->get_result();
        
        if ($user_result->num_rows === 1) {
            $user = $user_result->fetch_assoc();
            
            // Oturum başlat
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['address'] = $user['address'];
            $_SESSION['last_activity'] = time();
            
            // Session fixation koruması
            session_regenerate_id(true);
            
            header("Location: /index.php");
            exit();
        }
    }
    
    // Geçersiz token'ı temizle
    setcookie('remember_token', '', time() - 3600, '/');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Token kontrolü
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Güvenlik hatası. Lütfen formu tekrar doldurun.';
    } else {
        // Rate limiting kontrolü
        $rate_limit_key = 'login_' . get_client_ip();
        if (!check_rate_limit($rate_limit_key, 5, 900)) {
            $error = 'Çok fazla hatalı giriş denemesi. Lütfen 15 dakika sonra tekrar deneyin.';
        } else {
            // Inputları temizle ve validate et
            $email = secure_input($_POST['email'] ?? '', 'email');
            $password = secure_input($_POST['password'] ?? '', 'password');
            $remember_me = isset($_POST['remember_me']);
            
            // Validasyon
            if (!$email) {
                $field_errors['email'] = 'Geçerli bir e-posta adresi girin.';
            }
            
            if (empty($password)) {
                $field_errors['password'] = 'Şifre gereklidir.';
            }
            
            // Form verilerini sakla
            $form_data['email'] = htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8');
            
            if (empty($field_errors)) {
                // Veritabanında kullanıcıyı ara - Prepared statement ile
                $stmt = $conn->prepare("SELECT id, username, email, password, role, address, login_attempts, last_login_attempt FROM users WHERE email = ?");
                if (!$stmt) {
                    $error = 'Sistem hatası. Lütfen daha sonra tekrar deneyin.';
                } else {
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows === 1) {
                        $user = $result->fetch_assoc();
                        
                        // Hesap kilitleme kontrolü
                        $max_attempts = 5;
                        $lock_time = 900; // 15 dakika
                        
                        if ($user['login_attempts'] >= $max_attempts && 
                            (time() - strtotime($user['last_login_attempt'])) < $lock_time) {
                            $error = 'Hesabınız geçici olarak kilitlendi. Lütfen 15 dakika sonra tekrar deneyin.';
                        } else {
                            // Şifre kontrolü
                            if (password_verify($password, $user['password'])) {
                                // Başarılı giriş - attemptleri sıfırla
                                $reset_stmt = $conn->prepare("UPDATE users SET login_attempts = 0, last_login_attempt = NULL WHERE id = ?");
                                $reset_stmt->bind_param("i", $user['id']);
                                $reset_stmt->execute();
                                $reset_stmt->close();
                                
                                $_SESSION['rate_limits'][$rate_limit_key]['attempts'] = 0;
                                
                                // Oturum başlat
                                $_SESSION['user_id'] = $user['id'];
                                $_SESSION['username'] = $user['username'];
                                $_SESSION['email'] = $user['email'];
                                $_SESSION['role'] = $user['role'];
                                $_SESSION['address'] = $user['address'];
                                $_SESSION['last_activity'] = time();

                                // Session fixation koruması
                                session_regenerate_id(true);

                                // "Beni Hatırla" işlemi
                                if ($remember_me) {
                                    $token = bin2hex(random_bytes(32));
                                    $expires = time() + (30 * 24 * 60 * 60); // 30 gün
                                    
                                    // Önceki token'ları temizle
                                    $delete_stmt = $conn->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
                                    $delete_stmt->bind_param("i", $user['id']);
                                    $delete_stmt->execute();
                                    $delete_stmt->close();
                                    
                                    // Yeni token ekle
                                    $insert_stmt = $conn->prepare("INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, FROM_UNIXTIME(?))");
                                    $insert_stmt->bind_param("isi", $user['id'], $token, $expires);
                                    $insert_stmt->execute();
                                    $insert_stmt->close();
                                    
                                    setcookie('remember_token', $token, $expires, '/', '', true, true);
                                }

                                // Misafir sepetini kullanıcıya aktar
                                if (function_exists('transferGuestCartToUser')) {
                                    transferGuestCartToUser($user['id'], $conn);
                                    
                                    // Sepet transfer mesajlarını al ve session'a kaydet
                                    $transfer_messages = getCartTransferMessages();
                                    if (!empty($transfer_messages)) {
                                        $_SESSION['cart_transfer_info'] = $transfer_messages;
                                    }
                                }

                                // Geçici contact mesajı varsa, mesajı gönder ve contact sayfasına yönlendir
                                if (getTempContactMessage()) {
                                    header("Location: contact.php?action=send_temp_message");
                                    exit();
                                }

                                // Redirect after login kontrolü
                                $redirect_url = getRedirectAfterLogin();
                                if ($redirect_url) {
                                    clearRedirectAfterLogin();
                                    header("Location: $redirect_url");
                                    exit();
                                }

                                // ÖNEMLİ DEĞİŞİKLİK: Öncelikle checkout'a yönlendir
                                // Eğer sepette ürün varsa ve login cart sayfasından geldiyse checkout'a git
                                $cart_count = getCartItemCount($user['id'], $conn);
                                if ($cart_count > 0 && isset($_GET['redirect']) && $_GET['redirect'] === 'checkout.php') {
                                    header("Location: checkout.php");
                                    exit();
                                }

                                // Normal yönlendirme
                                if ($user['role'] === 'admin') {
                                    header("Location: /admin/dashboard.php");
                                } else {
                                    // Burada direkt index.php'ye yönlendir, çünkü özel durumlar zaten yukarıda kontrol edildi
                                    header("Location: index.php");
                                    exit();
                                }
                                exit();
                            } else {
                                // Hatalı şifre - attempt sayısını artır
                                $attempts = $user['login_attempts'] + 1;
                                $update_stmt = $conn->prepare("UPDATE users SET login_attempts = ?, last_login_attempt = NOW() WHERE id = ?");
                                $update_stmt->bind_param("ii", $attempts, $user['id']);
                                $update_stmt->execute();
                                $update_stmt->close();
                                
                                increment_rate_limit($rate_limit_key);
                                
                                $remaining_attempts = $max_attempts - $attempts;
                                if ($remaining_attempts > 0) {
                                    $error = "E-posta veya şifre hatalı. Kalan deneme hakkı: {$remaining_attempts}";
                                } else {
                                    $error = "Hesabınız geçici olarak kilitlendi. Lütfen 15 dakika sonra tekrar deneyin.";
                                }
                            }
                        }
                    } else {
                        // Kullanıcı bulunamadı
                        increment_rate_limit($rate_limit_key);
                        $error = 'E-posta veya şifre hatalı.';
                    }
                    $stmt->close();
                }
            } else {
                $error = 'Lütfen formdaki hataları düzeltin.';
            }
        }
    }
}

// CSRF Token oluştur
$csrf_token = generate_csrf_token();
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
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
    <div class="particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <div class="auth-wrapper">
        <div class="auth-container login-container">
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
                
                <?php if (getTempContactMessage()): ?>
                    <div class="contact-message-info">
                        <h6><i class="fas fa-envelope me-2"></i>Mesajınız Bekliyor</h6>
                        <p>Giriş yaptıktan sonra iletişim mesajınız otomatik olarak gönderilecektir.</p>
                    </div>
                <?php endif; ?>

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

                <form method="POST" id="loginForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="form-group">
                        <label for="email">E-posta Adresi</label>
                        <div class="input-wrapper <?php echo isset($field_errors['email']) ? 'error' : ''; ?>">
                            <input type="email" id="email" name="email" class="form-control <?php echo isset($field_errors['email']) ? 'input-error' : ''; ?>" 
                                   placeholder="ornek@example.com" required 
                                   value="<?php echo htmlspecialchars($form_data['email']); ?>">
                            <i class="fas fa-envelope input-icon"></i>
                        </div>
                        <?php if (isset($field_errors['email'])): ?>
                            <span class="error-message"><?php echo htmlspecialchars($field_errors['email']); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="password">Şifre</label>
                        <div class="input-wrapper <?php echo isset($field_errors['password']) ? 'error' : ''; ?>">
                            <input type="password" id="password" name="password" class="form-control <?php echo isset($field_errors['password']) ? 'input-error' : ''; ?>" 
                                   placeholder="••••••••" required>
                            <i class="fas fa-lock input-icon"></i>
                            <button type="button" class="password-toggle" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <?php if (isset($field_errors['password'])): ?>
                            <span class="error-message"><?php echo htmlspecialchars($field_errors['password']); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-options">
                        <div class="checkbox-wrapper">
                            <input type="checkbox" id="remember_me" name="remember_me" value="1">
                            <label for="remember_me">Beni Hatırla</label>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="fas fa-sign-in-alt"></i> 
                        <?php echo getTempContactMessage() ? 'Giriş Yap ve Mesajı Gönder' : 'Giriş Yap'; ?>
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

        // Real-time validation
        document.getElementById('loginForm').addEventListener('input', function(e) {
            const target = e.target;
            if (target.name === 'email') {
                validateEmail(target);
            }
        });

        function validateEmail(input) {
            const email = input.value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (email && !emailRegex.test(email)) {
                input.classList.add('input-error');
            } else {
                input.classList.remove('input-error');
            }
        }
    </script>
</body>
</html>