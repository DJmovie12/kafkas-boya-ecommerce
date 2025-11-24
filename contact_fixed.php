<?php
$page_title = "İletişim";
require_once 'includes/header.php';

$error = '';
$success = '';

// Form gönderimi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // Validasyon
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'Tüm alanlar gereklidir.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Geçerli bir e-posta adresi girin.';
    } else {
        // E-posta gönder (gerçek uygulamada mail() fonksiyonu kullanılabilir)
        // Şimdilik başarı mesajı göster
        $success = 'Mesajınız başarıyla gönderildi. En kısa zamanda sizinle iletişime geçeceğiz.';
        
        // Veritabanına kaydet (isteğe bağlı)
        // $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)");
        // $stmt->bind_param("sssss", $name, $email, $phone, $subject, $message);
        // $stmt->execute();
        // $stmt->close();
    }
}
?>

    <!-- Page Header -->
    <section class="page-header bg-light py-5" style="margin-top: 76px;">
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
                        </div>
                        <div class="card-body">
                            <?php if ($error): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>

                            <?php if ($success): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>

                            <form method="POST">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="name" class="form-label fw-medium">Ad Soyad *</label>
                                        <input type="text" class="form-control" id="name" name="name" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label fw-medium">E-posta *</label>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="phone" class="form-label fw-medium">Telefon</label>
                                        <input type="tel" class="form-control" id="phone" name="phone">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="subject" class="form-label fw-medium">Konu *</label>
                                        <input type="text" class="form-control" id="subject" name="subject" required>
                                    </div>
                                    <div class="col-12">
                                        <label for="message" class="form-label fw-medium">Mesaj *</label>
                                        <textarea class="form-control" id="message" name="message" rows="6" required></textarea>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-paper-plane me-2"></i>Mesaj Gönder
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

    <!-- Map Section (İsteğe bağlı) -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-4" data-aos="fade-up">
                <h2 class="display-5 fw-bold text-dark mb-3" style="font-family: 'Playfair Display', serif;">
                    Harita
                </h2>
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
            </div>

            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item border-0 shadow-sm mb-3 rounded-3">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
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
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
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
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
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
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
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

<?php require_once 'includes/footer.php'; ?>
