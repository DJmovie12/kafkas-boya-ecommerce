<?php
$page_title = "Kafkas Boya - Profesyonel Boya Çözümleri";
require_once 'includes/header.php';
?>
<style>/* Product Cards */
.product-card {
    transition: all 0.3s ease;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15) !important;
}

.product-image {
    position: relative;
    overflow: hidden;
}

.product-actions {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(255, 255, 255, 0.95);
    transform: translateY(100%);
    transition: transform 0.3s ease;
    z-index: 10;
}

.product-card:hover .product-actions {
    transform: translateY(0);
}

.product-badge .badge {
    z-index: 5;
}</style>
    <!-- HERO SECTION -->
    <section class="hero-section position-relative overflow-hidden" style="margin-top: 70px; height: 100vh;">
        <div class="hero-background" style="background-image: url('assets/img/slide-1.webp');"></div>
        <div class="hero-gradient-overlay"></div>

        <div class="container h-100 position-relative z-index-1">
            <div class="row h-100 align-items-center">
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
                <div class="col-lg-6" data-aos="fade-left"></div>
            </div>
        </div>
    </section>

    <!-- FAVORI ÜRÜNLER SECTION -->
    <section class="py-5 bg-white">
        <div class="container">
            <div class="text-center mb-5" data-aos="fade-up">
                <h2 class="display-5 fw-bold text-dark mb-3" style="font-family: 'Playfair Display', serif;">
                    <span class="text-primary">Popüler</span> Ürünler
                </h2>
                <p class="lead text-muted">Müşterilerimizin en çok tercih ettiği boya ürünleri</p>
            </div>

            <div class="row g-4">
                <?php
                // Popüler ürünleri veritabanından çek (ilk 4 ürün)
                $featured_sql = "SELECT p.*, b.name as brand_name FROM products p 
                                LEFT JOIN brands b ON p.brand_id = b.id 
                                ORDER BY p.created_at DESC LIMIT 4";
                
                $featured_result = $conn->query($featured_sql);
                
                if ($featured_result && $featured_result->num_rows > 0) {
                    $featured_products = $featured_result->fetch_all(MYSQLI_ASSOC);
                    
                    foreach ($featured_products as $index => $product) {
                        $badge_types = ['Popüler', 'En Çok Satan', 'Önerilen', 'Ekonomik'];
                        $badge = $badge_types[$index % 4];
                        $rating = 4.5 + ($index * 0.1);
                    ?>
                    <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="<?php echo ($index * 100); ?>">
                        <div class="product-card rounded-4 overflow-hidden shadow-sm hover-shadow h-100 d-flex flex-column">
                            <div class="product-image position-relative overflow-hidden" style="height: 220px; background-color: #f8f9fa;">
                                <?php if (!empty($product['image'])): ?>
                                    <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                         class="w-100 h-100" style="object-fit: cover;">
                                <?php else: ?>
                                    <div class="w-100 h-100 d-flex align-items-center justify-content-center">
                                        <i class="fas fa-image text-muted" style="font-size: 2rem;"></i>
                                    </div>
                                <?php endif; ?>
                                <span class="badge bg-primary position-absolute top-0 start-0 m-3"><?php echo $badge; ?></span>
                                <div class="product-actions d-flex flex-column gap-2 p-3">
                                    <a href="shop-single.php?id=<?php echo $product['id']; ?>" class="btn btn-primary btn-sm w-100">
                                        <i class="fas fa-eye me-1"></i>Detaylı İncele
                                    </a>
                                    <button class="btn btn-warning btn-sm w-100 add-to-cart" data-product="<?php echo $product['id']; ?>">
                                        <i class="fas fa-shopping-cart me-1"></i>Sepete Ekle
                                    </button>
                                </div>
                            </div>
                            <div class="product-info p-3 flex-grow-1 d-flex flex-column">
                                <h6 class="fw-semibold text-dark mb-2"><?php echo htmlspecialchars($product['name']); ?></h6>
                                <div class="mb-2">
                                    <i class="fas fa-star text-warning"></i>
                                    <small class="text-muted"><?php echo number_format($rating, 1); ?>/5</small>
                                </div>
                                <p class="text-muted small mb-3">
                                    <?php 
                                    if (!empty($product['brand_name'])) {
                                        echo htmlspecialchars($product['brand_name']) . ' marka profesyonel boya';
                                    } else {
                                        echo 'Yüksek kaliteli profesyonel boya çözümü';
                                    }
                                    ?>
                                </p>
                                <h5 class="text-primary fw-bold mt-auto">₺<?php echo number_format($product['price'], 2, ',', '.'); ?></h5>
                            </div>
                        </div>
                    </div>
                    <?php 
                    }
                } else {
                    echo '<div class="col-12 text-center py-5"><p class="text-muted">Ürün bulunamadı</p></div>';
                }
                ?>
            </div>

            <div class="text-center mt-5">
                <a href="shop.php" class="btn btn-primary btn-lg px-5 rounded-pill">
                    <i class="fas fa-eye me-2"></i>Tüm Ürünleri Gör
                </a>
            </div>
        </div>
    </section>

    <!-- HAKKIMIZDA SECTION -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row align-items-center g-4">
                <div class="col-lg-6" data-aos="fade-right">
                    <div class="about-visual rounded-4 overflow-hidden h-100 shadow-lg" style="background-image: url('assets/img/banner_img_01.webp'); background-size: cover; background-position: center; min-height: 400px; position: relative;">
                        <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.4);"></div>
                        <div class="d-flex flex-column justify-content-center align-items-center h-100 p-4 text-white position-relative z-1">
                        </div>
                    </div>
                </div>

                <div class="col-lg-6" data-aos="fade-left">
                    <div class="about-content">
                        <h2 class="display-5 fw-bold text-dark mb-4" style="font-family: 'Playfair Display', serif;">
                            <span class="text-primary">Kafkas Boya</span> Hakkında
                        </h2>
                        
                        <p class="lead text-muted mb-4">
                            1995'ten bu yana Kafkas Boya, Türkiye'nin en güvenilir boya tedarikçilerinden biri olarak hizmet vermektedir.
                        </p>

                        <div class="mb-4">
                            <h5 class="text-dark fw-bold mb-3">
                                <i class="fas fa-check-circle text-success me-2"></i>Neden Biz?
                            </h5>
                            <ul class="list-unstyled">
                                <li class="mb-3">
                                    <i class="fas fa-star text-warning me-2"></i>
                                    <strong>20+ Yıl Deneyim:</strong> Sektörde köklü bir geçmişe sahibiz
                                </li>
                                <li class="mb-3">
                                    <i class="fas fa-star text-warning me-2"></i>
                                    <strong>Kaliteli Ürünler:</strong> Sadece sertifikalı ve test edilmiş ürünler
                                </li>
                                <li class="mb-3">
                                    <i class="fas fa-star text-warning me-2"></i>
                                    <strong>Uzman Ekip:</strong> Müşteri memnuniyeti için her zaman hazır
                                </li>
                                <li class="mb-3">
                                    <i class="fas fa-star text-warning me-2"></i>
                                    <strong>Hızlı Teslimat:</strong> Siparişleriniz en kısa sürede ulaşır
                                </li>
                            </ul>
                        </div>

                        <div class="d-flex gap-3 flex-wrap">
                            <a href="#contact" class="btn btn-primary btn-lg rounded-pill">
                                <i class="fas fa-envelope me-2"></i>İletişime Geç
                            </a>
                            <a href="shop.php" class="btn btn-outline-primary btn-lg rounded-pill">
                                <i class="fas fa-shopping-bag me-2"></i>Mağazayı Ziyaret Et
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- MARKALAR SECTION -->
    <section id="favori-markalar" class="py-5 bg-white">
        <div class="container">
            <div class="text-center mb-5" data-aos="fade-up">
                <h2 class="display-5 fw-bold text-dark mb-3" style="font-family: 'Playfair Display', serif;">
                    Anlaştığımız <span class="text-primary">Boya Markaları</span>
                </h2>
                <p class="lead text-muted">Sektörün lider markalarıyla iş ortaklığımız</p>
            </div>

            <div class="row g-4 justify-content-center">
                <?php
                $brands = ['polisan', 'filli-boya', 'marshall', 'dyo', 'permolit'];
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
                            <div class="brand-icon bg-<?php echo $brand_color; ?> bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                <img src="assets/img/<?php echo $brand; ?>.webp" alt="<?php echo $brand_name; ?> logo" style="width: 70px; height: auto; border-radius: 100%;">
                            </div>
                        </div>
                        <h5 class="fw-semibold text-dark mb-2"><?php echo $brand_name; ?></h5>
                        <p class="text-muted small mb-3"><?php echo $brand_desc; ?></p>
                        <a href="shop.php?marka=<?php echo $brand; ?>" class="btn btn-outline-primary btn-sm">İncele</a>
                    </div>
                </div>
                <?php } ?>

                <div class="col-lg-2 col-md-4 col-6" data-aos="zoom-in" data-aos-delay="<?php echo (count($brands) * 100) + 100; ?>">
                    <div class="brand-card h-100 p-4 bg-primary bg-opacity-75 rounded-4 shadow-lg text-center hover-lift">
                        <div class="brand-logo mb-3">
                            <div class="brand-icon bg-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                <i class="fas fa-store text-primary" style="font-size: 32px;"></i>
                            </div>
                        </div>
                        <h5 class="fw-semibold text-white mb-2">Tüm Markalarımız</h5>
                        <p class="text-white small mb-3">Çok daha fazlasını keşfet</p>
                        <a href="shop.php" class="btn btn-light btn-sm">Mağazaya Git</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- NEDEN BÄ°Z SECTION -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5" data-aos="fade-up">
                <h2 class="display-5 fw-bold text-dark mb-3">
                    Neden <span class="text-primary">Kafkas Boya?</span>
                </h2>
                <p class="lead text-muted">Müşterilerimizin tercih etme nedenleri ve sunduğumuz avantajlar</p>
            </div>

            <div class="row g-4">
                <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="100">
                    <div class="feature-card-modern p-4 rounded-4 text-center h-100 bg-white shadow-sm">
                        <div class="feature-icon-wrapper mb-4">
                            <div class="icon-circle bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center">
                                <i class="fas fa-award fa-2x text-primary"></i>
                            </div>
                        </div>
                        <h5 class="fw-bold mb-2 text-dark">20+ Yıl Deneyim</h5>
                        <p class="text-muted small">Boya sektöründe 20 yılı aşkın tecrübemiz ve güvenilirliğimiz</p>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="200">
                    <div class="feature-card-modern p-4 rounded-4 text-center h-100 bg-white shadow-sm">
                        <div class="feature-icon-wrapper mb-4">
                            <div class="icon-circle bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center">
                                <i class="fas fa-check-circle fa-2x text-success"></i>
                            </div>
                        </div>
                        <h5 class="fw-bold mb-2 text-dark">Kaliteli Ürünler</h5>
                        <p class="text-muted small">Sektörün en iyi ve lider markalarıyla iş ortaklığı</p>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="300">
                    <div class="feature-card-modern p-4 rounded-4 text-center h-100 bg-white shadow-sm">
                        <div class="feature-icon-wrapper mb-4">
                            <div class="icon-circle bg-warning bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center">
                                <i class="fas fa-headset fa-2x text-warning"></i>
                            </div>
                        </div>
                        <h5 class="fw-bold mb-2 text-dark">Uzman Destek</h5>
                        <p class="text-muted small">Uzman ekibimizle her zaman sorularınıza çözüm sunmaya hazırız</p>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="400">
                    <div class="feature-card-modern p-4 rounded-4 text-center h-100 bg-white shadow-sm">
                        <div class="feature-icon-wrapper mb-4">
                            <div class="icon-circle bg-info bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center">
                                <i class="fas fa-truck fa-2x text-info"></i>
                            </div>
                        </div>
                        <h5 class="fw-bold mb-2 text-dark">Hızlı Teslimat</h5>
                        <p class="text-muted small">Siparişleriniz hızlı ve güvenli bir şekilde adresinize teslim edilir</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- İSTATİSTİKLER SECTION -->
    <section class="py-5 bg-primary text-white">
        <div class="container">
            <div class="row text-center g-4">
                <div class="col-md-3 col-6" data-aos="fade-up">
                    <h2 class="fw-bold mb-2" style="font-size: 3.5rem; font-family: 'Georgia', serif; font-weight: 700;">20+</h2>
                    <p class="fw-semibold" style="font-size: 1.1rem;">Yıl Deneyim</p>
                </div>
                <div class="col-md-3 col-6" data-aos="fade-up" data-aos-delay="100">
                    <h2 class="fw-bold mb-2" style="font-size: 3.5rem; font-family: 'Georgia', serif; font-weight: 700;">50K+</h2>
                    <p class="fw-semibold" style="font-size: 1.1rem;">Mutlu Müşteri</p>
                </div>
                <div class="col-md-3 col-6" data-aos="fade-up" data-aos-delay="200">
                    <h2 class="fw-bold mb-2" style="font-size: 3.5rem; font-family: 'Georgia', serif; font-weight: 700;">5</h2>
                    <p class="fw-semibold" style="font-size: 1.1rem;">Lider Marka</p>
                </div>
                <div class="col-md-3 col-6" data-aos="fade-up" data-aos-delay="300">
                    <h2 class="fw-bold mb-2" style="font-size: 3.5rem; font-family: 'Georgia', serif; font-weight: 700;">100%</h2>
                    <p class="fw-semibold" style="font-size: 1.1rem;">Memnuniyet</p>
                </div>
            </div>
        </div>
    </section>

    <!-- KAMPANYA BANNER -->
    <section class="py-5 bg-white">
        <div class="container">
            <div class="row align-items-center g-4">
                <div class="col-lg-8" data-aos="fade-right">
                    <h3 class="display-5 fw-bold text-dark mb-3">
                        <i class="fas fa-gift text-primary me-2"></i>Özel Teklifler
                    </h3>
                    <p class="lead text-muted mb-3">
                        Bu ay yapılan sipariş miktarına göre %10 - %20 arasında indirim fırsatı!
                    </p>
                    <p class="text-muted">
                        <i class="fas fa-clock text-warning me-2"></i>Sınırlı zaman için geçerli
                    </p>
                </div>
                <div class="col-lg-4 text-lg-end" data-aos="fade-left">
                    <a href="shop.php" class="btn btn-primary btn-lg px-5 rounded-pill">
                        <i class="fas fa-bolt me-2"></i>Fırsatı Kaçırma!
                    </a>
                </div>
            </div>
        </div>
    </section>


    <!-- CTA SECTION -->
    <section class="py-5 bg-primary text-white position-relative overflow-hidden">
        <div class="container position-relative z-index-1">
            <div class="row align-items-center">
                <div class="col-lg-8" data-aos="fade-right">
                    <h2 class="display-4 fw-bold mb-3">Profesyonel Boya Çözümleri İçin Bize Ulaşın</h2>
                    <p class="lead mb-0 text-light">Uzman ekibimiz size en uygun ürünü seçmekte yardımcı olacak</p>
                </div>
                <div class="col-lg-4 text-lg-end" data-aos="fade-left">
                    <a href="#contact" class="btn btn-warning btn-lg px-5 rounded-pill fw-bold">
                        <i class="fas fa-phone me-2"></i>Hemen İletişime Geç
                    </a>
                </div>
            </div>
        </div>
    </section>

<?php require_once 'includes/footer.php'; ?>