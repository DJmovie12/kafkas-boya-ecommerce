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
    'username' => '',
    'email' => '',
    'address' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Token kontrolü
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Güvenlik hatası. Lütfen formu tekrar doldurun.';
    } else {
        // Rate limiting kontrolü
        $rate_limit_key = 'register_' . get_client_ip();
        if (!check_rate_limit($rate_limit_key, 3, 3600)) { // 1 saatte 3 deneme
            $error = 'Çok fazla kayıt denemesi. Lütfen 1 saat sonra tekrar deneyin.';
        } else {
            // Inputları temizle ve validate et
            $username = secure_input($_POST['username'] ?? '', 'username');
            $email = secure_input($_POST['email'] ?? '', 'email');
            $password = secure_input($_POST['password'] ?? '', 'password');
            $password_confirm = secure_input($_POST['password_confirm'] ?? '', 'password');
            $address = secure_input($_POST['address'] ?? '');
            
            // Form verilerini sakla (hata durumunda kaybolmasın)
            $form_data['username'] = htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8');
            $form_data['email'] = htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8');
            $form_data['address'] = htmlspecialchars($_POST['address'] ?? '', ENT_QUOTES, 'UTF-8');
            
            // Validasyon
            if (!$username) {
                $field_errors['username'] = 'Kullanıcı adı 3-20 karakter arasında olmalı ve sadece harf, rakam, alt çizgi içermelidir.';
            }
            
            if (!$email) {
                $field_errors['email'] = 'Geçerli bir e-posta adresi girin.';
            }
            
            if (empty($password)) {
                $field_errors['password'] = 'Şifre gereklidir.';
            } else {
                $password_errors = validate_password_strength($password);
                if (!empty($password_errors)) {
                    $field_errors['password'] = 'Şifre: ' . implode(', ', $password_errors);
                }
            }
            
            if (empty($password_confirm)) {
                $field_errors['password_confirm'] = 'Şifre tekrarı gereklidir.';
            } elseif ($password !== $password_confirm) {
                $field_errors['password_confirm'] = 'Şifreler eşleşmiyor.';
            }
            
            if (empty($address)) {
                $field_errors['address'] = 'Adres gereklidir.';
            } elseif (strlen($address) < 10) {
                $field_errors['address'] = 'Adres en az 10 karakter olmalıdır.';
            }
            
            if (empty($field_errors)) {
                // E-posta veya kullanıcı adının zaten var olup olmadığını kontrol et
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
                if (!$stmt) {
                    $error = 'Sistem hatası. Lütfen daha sonra tekrar deneyin.';
                } else {
                    $stmt->bind_param("ss", $email, $username);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        $error = 'Bu e-posta adresi veya kullanıcı adı zaten kayıtlı.';
                    } else {
                        // Şifreyi hashle
                        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

                        // Yeni kullanıcıyı ekle
                        $stmt = $conn->prepare("INSERT INTO users (username, email, password, address, role, created_at) VALUES (?, ?, ?, ?, 'user', NOW())");
                        if (!$stmt) {
                            $error = 'Sistem hatası. Lütfen daha sonra tekrar deneyin.';
                        } else {
                            $stmt->bind_param("ssss", $username, $email, $hashed_password, $address);

                            if ($stmt->execute()) {
                                // Başarılı kayıt - rate limiting sıfırla
                                $_SESSION['rate_limits'][$rate_limit_key]['attempts'] = 0;
                                
                                // Yeni kullanıcının ID'sini al
                                $new_user_id = $stmt->insert_id;
                                
                                $_SESSION['user_id'] = $new_user_id;
                                $_SESSION['username'] = $username;
                                $_SESSION['email'] = $email;
                                $_SESSION['role'] = 'user';
                                $_SESSION['address'] = $address;
                                $_SESSION['last_activity'] = time();
                                
                                // Session fixation koruması
                                session_regenerate_id(true);
                                
                                // Misafir sepetini yeni kullanıcıya aktar
                                if (function_exists('transferGuestCartToUser')) {
                                    transferGuestCartToUser($new_user_id, $conn);
                                }

                                // Geçici contact mesajı varsa, mesajı gönder ve contact sayfasına yönlendir
                                if (getTempContactMessage()) {
                                    header("Location: contact.php?action=send_temp_message");
                                    exit();
                                }

                                // ÖNEMLİ DEĞİŞİKLİK: Redirect after login kontrolü
                                $redirect_url = getRedirectAfterLogin();
                                if ($redirect_url) {
                                    clearRedirectAfterLogin();
                                    header("Location: $redirect_url");
                                    exit();
                                }

                                // Sepetten geldiyse checkout'a, değilse index.php'ye yönlendir
                                if (isset($_SESSION['redirect_after_login']) && $_SESSION['redirect_after_login'] === 'checkout.php') {
                                    clearRedirectAfterLogin();
                                    header("Location: checkout.php");
                                    exit();
                                } else {
                                    // Normal kayıt - ana sayfaya yönlendir
                                    header("Location: index.php");
                                    exit();
                                }
                                
                            } else {
                                $error = 'Kayıt sırasında bir hata oluştu. Lütfen tekrar deneyin.';
                            }
                        }
                    }
                    $stmt->close();
                }
            } else {
                $error = 'Lütfen formdaki hataları düzeltin.';
                increment_rate_limit($rate_limit_key);
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
    <title>Üye Ol - Kafkas Boya</title>
    
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
        <div class="auth-container register-container">
            <!-- Form Side -->
            <div class="form-side register-form-side">
                <div class="brand">
                    <img src="assets/img/kafkasboya-logo.png" alt="Kafkas Boya Logo" class="brand-logo">
                    <p>Yeni bir hesap oluşturun</p>
                </div>

                <?php if (getTempContactMessage()): ?>
                    <div class="contact-message-info">
                        <h6><i class="fas fa-envelope me-2"></i>Mesajınız Bekliyor</h6>
                        <p>Üye olduktan sonra iletişim mesajınız otomatik olarak gönderilecektir.</p>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" id="registerForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="form-group">
                        <label for="username">Kullanıcı Adı</label>
                        <div class="input-wrapper <?php echo isset($field_errors['username']) ? 'error' : ''; ?>">
                            <input type="text" id="username" name="username" class="form-control <?php echo isset($field_errors['username']) ? 'input-error' : ''; ?>" 
                                   placeholder="Kullanıcı adınız" required 
                                   value="<?php echo htmlspecialchars($form_data['username']); ?>">
                            <i class="fas fa-user input-icon"></i>
                        </div>
                        <div class="form-text">3-20 karakter arası, sadece harf, rakam ve alt çizgi</div>
                        <?php if (isset($field_errors['username'])): ?>
                            <span class="error-message"><?php echo htmlspecialchars($field_errors['username']); ?></span>
                        <?php endif; ?>
                    </div>

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
                        <label for="address">Adres</label>
                        <div class="input-wrapper <?php echo isset($field_errors['address']) ? 'error' : ''; ?>">
                            <textarea id="address" name="address" class="form-control <?php echo isset($field_errors['address']) ? 'input-error' : ''; ?>" 
                                      placeholder="Tam adresinizi girin" required><?php echo htmlspecialchars($form_data['address']); ?></textarea>
                            <i class="fas fa-map-marker-alt input-icon"></i>
                        </div>
                        <div class="form-text">Teslimat için en az 10 karakter</div>
                        <?php if (isset($field_errors['address'])): ?>
                            <span class="error-message"><?php echo htmlspecialchars($field_errors['address']); ?></span>
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
                        <div class="password-strength" id="passwordStrength">
                            <div class="strength-bar" id="strengthBar"></div>
                        </div>
                        <div class="form-text strength-text" id="strengthText">En az 8 karakter, büyük harf, küçük harf ve rakam</div>
                        <?php if (isset($field_errors['password'])): ?>
                            <span class="error-message"><?php echo htmlspecialchars($field_errors['password']); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="password_confirm">Şifre Tekrar</label>
                        <div class="input-wrapper <?php echo isset($field_errors['password_confirm']) ? 'error' : ''; ?>">
                            <input type="password" id="password_confirm" name="password_confirm" class="form-control <?php echo isset($field_errors['password_confirm']) ? 'input-error' : ''; ?>" 
                                   placeholder="••••••••" required>
                            <i class="fas fa-lock input-icon"></i>
                            <button type="button" class="password-toggle" id="togglePasswordConfirm">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <?php if (isset($field_errors['password_confirm'])): ?>
                            <span class="error-message"><?php echo htmlspecialchars($field_errors['password_confirm']); ?></span>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="fas fa-user-plus"></i> 
                        <?php echo getTempContactMessage() ? 'Üye Ol ve Mesajı Gönder' : 'Üye Ol'; ?>
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
            const passwordStrength = document.getElementById('passwordStrength');
            let strength = 0;
            let feedback = [];

            // Uzunluk kontrolü
            if (password.length >= 8) {
                strength += 25;
            } else {
                feedback.push('En az 8 karakter');
            }

            // Büyük harf kontrolü
            if (/[A-Z]/.test(password)) {
                strength += 25;
            } else {
                feedback.push('Büyük harf');
            }

            // Küçük harf kontrolü
            if (/[a-z]/.test(password)) {
                strength += 25;
            } else {
                feedback.push('Küçük harf');
            }

            // Rakam kontrolü
            if (/[0-9]/.test(password)) {
                strength += 15;
            } else {
                feedback.push('Rakam');
            }

            // Özel karakter kontrolü
            if (/[^a-zA-Z0-9]/.test(password)) {
                strength += 10;
            } else {
                feedback.push('Özel karakter');
            }

            strengthBar.style.width = strength + '%';

            // Strength class'larını ayarla
            passwordStrength.className = 'password-strength';
            
            if (strength === 0) {
                strengthText.textContent = 'En az 8 karakter, büyük harf, küçük harf ve rakam';
                strengthText.style.color = '#94a3b8';
            } else if (strength < 50) {
                passwordStrength.classList.add('weak');
                strengthText.textContent = 'Zayıf şifre - Eksik: ' + feedback.join(', ');
                strengthText.style.color = '#E74C3C';
            } else if (strength < 80) {
                passwordStrength.classList.add('medium');
                strengthText.textContent = 'Orta şifre - Eksik: ' + feedback.join(', ');
                strengthText.style.color = '#F39C12';
            } else if (strength < 100) {
                passwordStrength.classList.add('strong');
                strengthText.textContent = 'Güçlü şifre ✓';
                strengthText.style.color = '#5DADE2';
            } else {
                passwordStrength.classList.add('very-strong');
                strengthText.textContent = 'Çok güçlü şifre ✓';
                strengthText.style.color = '#27AE60';
            }
        });

        // Real-time validation
        document.getElementById('registerForm').addEventListener('input', function(e) {
            const target = e.target;
            
            if (target.name === 'username') {
                validateUsername(target);
            } else if (target.name === 'email') {
                validateEmail(target);
            } else if (target.name === 'address') {
                validateAddress(target);
            } else if (target.name === 'password_confirm') {
                validatePasswordConfirm();
            }
        });

        function validateUsername(input) {
            const username = input.value.trim();
            const usernameRegex = /^[a-zA-Z0-9_]{3,20}$/;
            
            if (username && !usernameRegex.test(username)) {
                input.classList.add('input-error');
            } else {
                input.classList.remove('input-error');
            }
        }

        function validateEmail(input) {
            const email = input.value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (email && !emailRegex.test(email)) {
                input.classList.add('input-error');
            } else {
                input.classList.remove('input-error');
            }
        }

        function validateAddress(input) {
            const address = input.value.trim();
            
            if (address && address.length < 10) {
                input.classList.add('input-error');
            } else {
                input.classList.remove('input-error');
            }
        }

        function validatePasswordConfirm() {
            const password = document.getElementById('password').value;
            const passwordConfirm = document.getElementById('password_confirm');
            
            if (passwordConfirm.value && password !== passwordConfirm.value) {
                passwordConfirm.classList.add('input-error');
            } else {
                passwordConfirm.classList.remove('input-error');
            }
        }

        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            let hasErrors = false;
            
            // Client-side validation
            const password = document.getElementById('password').value;
            const passwordConfirm = document.getElementById('password_confirm').value;

            if (password !== passwordConfirm) {
                e.preventDefault();
                alert('Şifreler eşleşmiyor!');
                hasErrors = true;
            }
            
            // Diğer validasyonları buraya ekleyebilirsin
        });
    </script>
</body>
</html>