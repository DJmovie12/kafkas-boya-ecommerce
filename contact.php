<?php
ob_start();
$page_title = "İletişim";
require_once 'includes/header.php';
require_once 'includes/db_connect.php';
require_once 'includes/session.php';

$error = '';
$success = '';

// CSRF Token oluştur
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Güvenlik fonksiyonları
function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function validate_phone($phone) {
    // Telefon numarası validasyonu (Türkiye formatı)
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (empty($phone)) return true; // Telefon opsiyonel
    if (strlen($phone) < 10) return false;
    return preg_match('/^(\+90|0)?[5][0-9]{9}$/', $phone);
}

function validate_name($name) {
    // İsim validasyonu (sadece harf, boşluk ve Türkçe karakterler)
    return preg_match('/^[a-zA-ZğüşıöçĞÜŞİÖÇ\s]{2,50}$/u', $name);
}

function validate_subject($subject) {
    // Konu validasyonu
    return strlen($subject) >= 3 && strlen($subject) <= 100;
}

function validate_message($message) {
    // Mesaj validasyonu
    return strlen($message) >= 10 && strlen($message) <= 1000;
}

// Form gönderimi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF kontrolü
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Geçersiz işlem! Lütfen formu tekrar doldurun.';
    } else {
        $name = sanitize_input($_POST['name'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $phone = sanitize_input($_POST['phone'] ?? '');
        $subject = sanitize_input($_POST['subject'] ?? '');
        $message = sanitize_input($_POST['message'] ?? '');
        
        // Validasyon
        $errors = [];
        
        if (empty($name) || !validate_name($name)) {
            $errors[] = 'Geçerli bir ad soyad girin (2-50 karakter, sadece harf ve boşluk)';
        }
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Geçerli bir e-posta adresi girin';
        }
        
        if (!empty($phone) && !validate_phone($phone)) {
            $errors[] = 'Geçerli bir telefon numarası girin (örn: 05551234567)';
        }
        
        if (empty($subject) || !validate_subject($subject)) {
            $errors[] = 'Konu 3-100 karakter arasında olmalıdır';
        }
        
        if (empty($message) || !validate_message($message)) {
            $errors[] = 'Mesaj 10-1000 karakter arasında olmalıdır';
        }
        
        if (!empty($errors)) {
            $error = implode('<br>', $errors);
        } else {
            // Rate limiting kontrolü (1 dakikada maksimum 3 mesaj) - IP adresi olmadan email bazlı
            $current_time = time();
            $one_minute_ago = $current_time - 60;
            
            $stmt = $conn->prepare("SELECT COUNT(*) as message_count FROM contacts WHERE email = ? AND created_at > FROM_UNIXTIME(?)");
            $stmt->bind_param("si", $email, $one_minute_ago);
            $stmt->execute();
            $result = $stmt->get_result();
            $message_count = $result->fetch_assoc()['message_count'];
            $stmt->close();
            
            if ($message_count >= 3) {
                $error = 'Çok fazla mesaj gönderdiniz. Lütfen 1 dakika bekleyin.';
            } else {
                // Kullanıcı giriş yapmış mı kontrol et
                if (isUserLoggedIn()) {
                    // Giriş yapmışsa direkt kaydet
                    $user_id = $_SESSION['user_id'];
                    $stmt = $conn->prepare("INSERT INTO contacts (user_id, name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?, ?)");
                    
                    if ($stmt) {
                        $stmt->bind_param("isssss", $user_id, $name, $email, $phone, $subject, $message);
                        
                        if ($stmt->execute()) {
                            $_SESSION['contact_success'] = 'Mesajınız başarıyla gönderildi. En kısa zamanda sizinle iletişime geçeceğiz.';
                            ob_end_clean();
                            header('Location: contact.php');
                            exit();
                        } else {
                            $error = 'Mesaj gönderilirken bir hata oluştu. Lütfen tekrar deneyin.';
                        }
                        $stmt->close();
                    } else {
                        $error = 'Veritabanı hatası: ' . $conn->error;
                    }
                } else {
                    // Giriş yapmamışsa mesajı session'a kaydet ve giriş/üye ol sayfasına yönlendir
                    $tempMessage = [
                        'name' => $name,
                        'email' => $email,
                        'phone' => $phone,
                        'subject' => $subject,
                        'message' => $message
                    ];
                    
                    saveTempContactMessage($tempMessage);
                    $_SESSION['redirect_after_login'] = 'contact.php';
                    
                    // Kullanıcıyı giriş sayfasına yönlendir
                    ob_end_clean();
                    header('Location: login.php?action=complete_contact');
                    exit();
                }
            }
        }
    }
}

// Giriş yaptıktan sonra mesaj gönderme işlemi - DÜZELTİLDİ
if (isset($_GET['action']) && $_GET['action'] === 'send_temp_message' && isUserLoggedIn()) {
    $tempMessage = getTempContactMessage();
    if ($tempMessage) {
        $user_id = $_SESSION['user_id'];
        $stmt = $conn->prepare("INSERT INTO contacts (user_id, name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?, ?)");
        
        if ($stmt) {
            $stmt->bind_param("isssss", $user_id, $tempMessage['name'], $tempMessage['email'], $tempMessage['phone'], $tempMessage['subject'], $tempMessage['message']);
            
            if ($stmt->execute()) {
                clearTempContactMessage();
                $_SESSION['contact_success'] = 'Mesajınız başarıyla gönderildi. En kısa zamanda sizinle iletişime geçeceğiz.';
            } else {
                $error = 'Mesaj gönderilirken bir hata oluştu.';
            }
            $stmt->close();
        }
        
        ob_end_clean();
        header('Location: contact.php');
        exit();
    }
}

// Session'dan success mesajını al
if (isset($_SESSION['contact_success'])) {
    $success = $_SESSION['contact_success'];
    unset($_SESSION['contact_success']);
}

// Giriş yapmış kullanıcı için formu otomatik doldur
$current_user = null;
if (isUserLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $current_user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Session'da kayıtlı geçici mesaj varsa formu doldur
$tempMessage = getTempContactMessage();
if ($tempMessage && !isUserLoggedIn()) {
    $name = $tempMessage['name'];
    $email = $tempMessage['email'];
    $phone = $tempMessage['phone'];
    $subject = $tempMessage['subject'];
    $message_content = $tempMessage['message'];
} else {
    $name = $current_user ? $current_user['username'] : '';
    $email = $current_user ? $current_user['email'] : '';
    $phone = '';
    $subject = '';
    $message_content = '';
}
?>

    <!-- Page Header -->
    <section class="page-header bg-light py-5" style="margin-top: 70px;">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-2">
                            <li class="breadcrumb-item"><a href="index.php"
                                    class="text-decoration-none text-primary">Ana Sayfa</a></li>
                            <li class="breadcrumb-item active">İletişim</li>
                        </ol>
                    </nav>
                    <h1 class="display-5 fw-bold text-dark mb-0" style="font-family: 'Playfair Display', serif;">
                        İletişim
                    </h1>
                    <p class="text-muted mt-2 mb-0">Bize ulaşmak için aşağıdaki formu kullanın</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="py-5">
        <div class="container">
            <div class="row g-5">
                <!-- Contact Form -->
                <div class="col-lg-8" data-aos="fade-right">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="fw-bold mb-0">Bize Mesaj Gönderin</h5>
                            <p class="text-muted mb-0 small">* İşaretli alanlar zorunludur</p>
                        </div>
                        <div class="card-body">
                            <?php if ($error): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    <div><?php echo $error; ?></div>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>

                            <?php if ($success): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>

                            <form method="POST" id="contactForm">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="name" class="form-label fw-medium">Ad Soyad <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="name" name="name" 
                                               value="<?php echo htmlspecialchars($name); ?>" 
                                               required
                                               minlength="2"
                                               maxlength="50"
                                               pattern="[a-zA-ZğüşıöçĞÜŞİÖÇ\s]+"
                                               title="Sadece harf ve boşluk kullanabilirsiniz">
                                        <div class="form-text">En az 2 karakter</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label fw-medium">E-posta <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($email); ?>" 
                                               required
                                               maxlength="100">
                                        <div class="form-text">Geçerli bir e-posta adresi girin</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="phone" class="form-label fw-medium">Telefon</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" 
                                               value="<?php echo htmlspecialchars($phone); ?>"
                                               pattern="[\+]?[0-9\s\-\(\)]+"
                                               title="Geçerli bir telefon numarası girin">
                                        <div class="form-text">Örn: 0555 123 4567</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="subject" class="form-label fw-medium">Konu <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="subject" name="subject" 
                                               value="<?php echo htmlspecialchars($subject); ?>" 
                                               required
                                               minlength="3"
                                               maxlength="100">
                                        <div class="form-text">3-100 karakter arasında</div>
                                    </div>
                                    <div class="col-12">
                                        <label for="message" class="form-label fw-medium">Mesaj <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="message" name="message" rows="6" 
                                                  required
                                                  minlength="10"
                                                  maxlength="1000"
                                                  placeholder="Mesajınızı detaylı bir şekilde yazın..."><?php echo htmlspecialchars($message_content); ?></textarea>
                                        <div class="form-text d-flex justify-content-between">
                                            <span>10-1000 karakter arasında</span>
                                            <span id="messageCounter">0/1000</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary btn-lg px-4" id="submitBtn">
                                        <i class="fas fa-paper-plane me-2"></i>
                                        <?php echo isUserLoggedIn() ? 'Mesaj Gönder' : 'Giriş Yap ve Mesaj Gönder'; ?>
                                    </button>
                                    <button type="reset" class="btn btn-outline-secondary ms-2">
                                        <i class="fas fa-undo me-2"></i>Temizle
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Contact Info -->
                <div class="col-lg-4" data-aos="fade-left">
                    <!-- Address -->
                    <div class="contact-info-card card border-0 shadow-sm mb-4">
                        <div class="card-body">
                            <div class="d-flex align-items-start">
                                <div class="contact-icon me-3">
                                    <i class="fas fa-map-marker-alt fa-2x text-primary"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-2">Adres</h5>
                                    <p class="text-muted mb-0">
                                        Kafkas Boya<br>
                                        İstanbul, Türkiye<br>
                                        Merkez Ofis
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Phone -->
                    <div class="contact-info-card card border-0 shadow-sm mb-4">
                        <div class="card-body">
                            <div class="d-flex align-items-start">
                                <div class="contact-icon me-3">
                                    <i class="fas fa-phone fa-2x text-success"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-2">Telefon</h5>
                                    <p class="text-muted mb-0">
                                        <a href="tel:+905551234567" class="text-decoration-none text-muted">
                                            +90 555 123 4567
                                        </a><br>
                                        <a href="tel:+905559876543" class="text-decoration-none text-muted">
                                            +90 555 987 6543
                                        </a>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="contact-info-card card border-0 shadow-sm mb-4">
                        <div class="card-body">
                            <div class="d-flex align-items-start">
                                <div class="contact-icon me-3">
                                    <i class="fas fa-envelope fa-2x text-warning"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-2">E-posta</h5>
                                    <p class="text-muted mb-0">
                                        <a href="mailto:info@kafkasboya.com" class="text-decoration-none text-muted">
                                            info@kafkasboya.com
                                        </a><br>
                                        <a href="mailto:satış@kafkasboya.com" class="text-decoration-none text-muted">
                                            satış@kafkasboya.com
                                        </a>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Hours -->
                    <div class="contact-info-card card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-start">
                                <div class="contact-icon me-3">
                                    <i class="fas fa-clock fa-2x text-info"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-2">Çalışma Saatleri</h5>
                                    <p class="text-muted mb-0">
                                        Pazartesi - Cuma: 09:00 - 18:00<br>
                                        Cumartesi: 10:00 - 16:00<br>
                                        Pazar: Kapalı
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Map Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-4" data-aos="fade-up">
                <h2 class="display-5 fw-bold text-dark mb-3" style="font-family: 'Playfair Display', serif;">
                    Harita
                </h2>
                <p class="text-muted">Ofisimizin konumunu haritada görebilirsiniz</p>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="map-container rounded-4 overflow-hidden shadow-sm" style="height: 400px;">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3011.5789649834507!2d29.00000000000001!3d41.00000000000001!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x14caa7b6b6b6b6b7%3A0x1234567890abcdef!2sIstanbul%2C%20Turkey!5e0!3m2!1sen!2sus!4v1234567890" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5" data-aos="fade-up">
                <h2 class="display-5 fw-bold text-dark mb-3" style="font-family: 'Playfair Display', serif;">
                    Sık Sorulan <span class="text-primary">Sorular</span>
                </h2>
                <p class="text-muted">Müşterilerimizin en çok sorduğu sorular ve cevapları</p>
            </div>

            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item border-0 shadow-sm mb-3 rounded-3">
                            <h2 class="accordion-header">
                                <button class="accordion-button bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    <i class="fas fa-shipping-fast text-primary me-3"></i>
                                    Kargo ücreti ne kadar?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Tüm siparişlerde kargo ücreti ücretsizdir. 50 TL ve üzeri siparişlerde hızlı kargo hizmeti sunuyoruz.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item border-0 shadow-sm mb-3 rounded-3">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    <i class="fas fa-truck text-success me-3"></i>
                                    Teslimat süresi ne kadar?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Siparişleriniz 2-3 iş günü içinde kargoya verilir. Kargo ile beraber 3-5 iş günü içinde teslim edilir.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item border-0 shadow-sm mb-3 rounded-3">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                    <i class="fas fa-undo text-warning me-3"></i>
                                    İade politikanız nedir?
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Ürünler açılmamış ve hasarsız durumda ise 14 gün içinde iade kabul edilir. İade kargo ücreti müşteri tarafından ödenir.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item border-0 shadow-sm mb-3 rounded-3">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                    <i class="fas fa-credit-card text-info me-3"></i>
                                    Hangi ödeme yöntemlerini kabul ediyorsunuz?
                                </button>
                            </h2>
                            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Kredi kartı, banka kartı, banka transferi ve kapıda ödeme seçenekleri sunuyoruz.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
    // Mesaj karakter sayacı
    document.addEventListener('DOMContentLoaded', function() {
        const messageTextarea = document.getElementById('message');
        const messageCounter = document.getElementById('messageCounter');
        
        if (messageTextarea && messageCounter) {
            // Başlangıç değeri
            messageCounter.textContent = messageTextarea.value.length + '/1000';
            
            // Değişiklikleri dinle
            messageTextarea.addEventListener('input', function() {
                const length = this.value.length;
                messageCounter.textContent = length + '/1000';
                
                if (length > 1000) {
                    messageCounter.classList.add('text-danger');
                } else {
                    messageCounter.classList.remove('text-danger');
                }
            });
        }
        
        // Form gönderimini kontrol et
        const contactForm = document.getElementById('contactForm');
        const submitBtn = document.getElementById('submitBtn');
        
        if (contactForm) {
            contactForm.addEventListener('submit', function(e) {
                // Çift gönderimi önle
                if (submitBtn.disabled) {
                    e.preventDefault();
                    return;
                }
                
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Gönderiliyor...';
            });
        }
    });
    </script>

<?php require_once 'includes/footer.php'; ?>