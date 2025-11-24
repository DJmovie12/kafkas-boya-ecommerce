<?php
$page_title = "Kafkas Boya - Profesyonel Boya Çözümleri";
require_once 'includes/header.php';
?>

    <!-- Yeni Hero Section -->
    <section class="hero-section position-relative overflow-hidden" style="margin-top: 76px; height: 100vh;">
        <!-- Arka plan resmi - Tüm alanı kaplasın -->
        <div class="hero-background" style="background-image: url('assets/img/slide-1.webp');"></div>

        <!-- Gradient overlay - yumuşak geçiş için -->
        <div class="hero-gradient-overlay"></div>

        <div class="container h-100 position-relative z-index-1">
            <div class="row h-100 align-items-center">

                <!-- Sol Taraf: Metin ve Butonlar -->
                <div class="col-lg-6 d-flex flex-column justify-content-center" data-aos="fade-right">
                    <h1 class="display-3 fw-bold text-white mb-4" style="font-family: 'Playfair Display', serif;">
                        Evinize <span class="text-warning">Renk</span> Katın
                    </h1>
                    <p class="lead text-light mb-4">
                        Kafkas Boya olarak 20 yıllık deneyimimizle, profesyonel boya çözümleri sunuyoruz.
                        En kaliteli markalar, geniş ürün yelpazesi ve uzman desteğiyle yanınızdayız.
                    </p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="shop.php" class="btn btn-warning btn-lg px-4 py-3 rounded-pill fw-bold">
                            <i class="fas fa-shopping-cart me-2"></i>Alışverişe Başla
                        </a>
                        <a href="#favori-markalar" class="btn btn-outline-light btn-lg px-4 py-3 rounded-pill fw-bold">
                            <i class="fas fa-star me-2"></i>Markalarımız
                        </a>
                    </div>
                </div>

                <!-- Sağ Taraf: Boş alan (arka plan resmi görünecek) -->
                <div class="col-lg-6" data-aos="fade-left">
                    <!-- Bu alan boş, arka plan resmi görünecek -->
                </div>
            </div>
        </div>
    </section>


    <!-- Favori Markalar Section -->
    <section id="favori-markalar" class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5" data-aos="fade-up">
                <h2 class="display-5 fw-bold text-dark mb-3" style="font-family: 'Playfair Display', serif;">
                    Anlaştığımız <span class="text-primary">Boya Markaları</span>
                </h2>
                <p class="lead text-muted">Sektörün lider markalarıyla iş ortaklığımız</p>
            </div>

            <div class="row g-4">
                <?php
                // Markalardan ürün sayısını al
                $brands = ['polisan', 'filli', 'marshall', 'dyo', 'permolit'];
                $brand_names = ['Polisan', 'Filli Boya', 'Marshall', 'DYO', 'Permolit'];
                $brand_colors = ['primary', 'success', 'warning', 'danger', 'info'];
                $brand_descriptions = ['Premium kalite', 'Geniş renk paleti', 'Dayanıklı çözümler', 'Ekonomik çözümler', 'Profesyonel seçim'];

                foreach ($brands as $index => $brand) {
                    $brand_name = $brand_names[$index];
                    $brand_color = $brand_colors[$index];
                    $brand_desc = $brand_descriptions[$index];
                    ?>
                    <div class="col-lg-2 col-md-4 col-6" data-aos="zoom-in" data-aos-delay="<?php echo ($index * 100) + 100; ?>">
                        <div class="brand-card h-100 p-4 bg-white rounded-4 shadow-sm text-center hover-lift">
                            <div class="brand-logo mb-3">
                                <div class="brand-icon bg-<?php echo $brand_color; ?> bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center"
                                    style="width: 80px; height: 80px;">
                                    <img src="assets/img/<?php echo $brand; ?>.webp" alt="<?php echo $brand_name; ?> logo"
                                        style="width: 70px; height: auto; border-radius: 100%;">
                                </div>
                            </div>
                            <h5 class="fw-semibold text-dark mb-2"><?php echo $brand_name; ?></h5>
                            <p class="text-muted small"><?php echo $brand_desc; ?></p>
                            <a href="shop.php?marka=<?php echo $brand; ?>" class="btn btn-outline-primary btn-sm">İncele</a>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
    </section>

    <!-- Neden Biz Section -->
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5" data-aos="fade-up">
                <h2 class="display-5 fw-bold text-dark mb-3" style="font-family: 'Playfair Display', serif;">
                    Neden <span class="text-primary">Kafkas Boya?</span>
                </h2>
                <p class="lead text-muted">Müşterilerimizin tercih etme nedenleri</p>
            </div>

            <div class="row g-4">
                <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="100">
                    <div class="feature-card p-4 bg-light rounded-4 text-center h-100">
                        <div class="feature-icon mb-3">
                            <i class="fas fa-award fa-3x text-primary"></i>
                        </div>
                        <h5 class="fw-bold mb-2">20+ Yıl Deneyim</h5>
                        <p class="text-muted">Boya sektöründe 20 yılı aşkın tecrübemiz ve güvenilirliğimiz</p>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="200">
                    <div class="feature-card p-4 bg-light rounded-4 text-center h-100">
                        <div class="feature-icon mb-3">
                            <i class="fas fa-check-circle fa-3x text-success"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Kaliteli Ürünler</h5>
                        <p class="text-muted">Sektörün en iyi markalarıyla çalışıyoruz</p>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="300">
                    <div class="feature-card p-4 bg-light rounded-4 text-center h-100">
                        <div class="feature-icon mb-3">
                            <i class="fas fa-headset fa-3x text-warning"></i>
                        </div>
                        <h5 class="fw-bold mb-2">7/24 Destek</h5>
                        <p class="text-muted">Her zaman yardımcı olmak için hazırız</p>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="400">
                    <div class="feature-card p-4 bg-light rounded-4 text-center h-100">
                        <div class="feature-icon mb-3">
                            <i class="fas fa-truck fa-3x text-info"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Hızlı Teslimat</h5>
                        <p class="text-muted">Siparişleriniz hızlı bir şekilde teslim edilir</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

<?php require_once 'includes/footer.php'; ?>
