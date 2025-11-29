<?php
$page_title = "Ürün Detayı";
require_once 'includes/header.php';

// CSRF Token oluştur
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Ürün ID'sini al ve güvenlik kontrolü
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($product_id === 0) {
    header("Location: /shop.php");
    exit();
}

// Ürünü veritabanından al
$stmt = $conn->prepare("SELECT p.*, b.name as brand_name, c.name as category_name 
                        FROM products p 
                        LEFT JOIN brands b ON p.brand_id = b.id 
                        LEFT JOIN categories c ON p.category_id = c.id 
                        WHERE p.id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: /shop.php");
    exit();
}

$product = $result->fetch_assoc();
$stmt->close();

// Yorumları al
$reviews_stmt = $conn->prepare("
    SELECT r.*, u.username 
    FROM reviews r 
    LEFT JOIN users u ON r.user_id = u.id 
    WHERE r.product_id = ? 
    ORDER BY r.created_at DESC
");
$reviews_stmt->bind_param("i", $product_id);
$reviews_stmt->execute();
$reviews_result = $reviews_stmt->get_result();
$reviews = $reviews_result->fetch_all(MYSQLI_ASSOC);
$reviews_stmt->close();

// Ortalama puanı hesapla
$avg_rating_stmt = $conn->prepare("
    SELECT AVG(rating) as avg_rating, COUNT(*) as review_count 
    FROM reviews 
    WHERE product_id = ?
");
$avg_rating_stmt->bind_param("i", $product_id);
$avg_rating_stmt->execute();
$avg_rating_result = $avg_rating_stmt->get_result();
$rating_data = $avg_rating_result->fetch_assoc();
$avg_rating_stmt->close();

$average_rating = $rating_data['avg_rating'] ? round($rating_data['avg_rating'], 1) : 0;
$review_count = $rating_data['review_count'] ? $rating_data['review_count'] : 0;

// Yorum ekleme işlemi - GÜVENLİK ÖNLEMLERİ EKLENDİ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_review'])) {
    // CSRF kontrolü
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error'] = 'Geçersiz işlem! Lütfen tekrar deneyin.';
    } else {
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['error'] = 'Yorum yapmak için giriş yapmalısınız.';
        } else {
            $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
            $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
            
            // Validasyon
            if ($rating < 1 || $rating > 5) {
                $_SESSION['error'] = 'Lütfen 1-5 arası puan verin.';
            } elseif (empty($comment)) {
                $_SESSION['error'] = 'Lütfen yorumunuzu yazın.';
            } elseif (strlen($comment) < 10) {
                $_SESSION['error'] = 'Yorumunuz en az 10 karakter olmalıdır.';
            } elseif (strlen($comment) > 500) {
                $_SESSION['error'] = 'Yorumunuz en fazla 500 karakter olabilir.';
            } else {
                // Aynı kullanıcı aynı ürüne daha önce yorum yapmış mı kontrol et
                $check_stmt = $conn->prepare("SELECT id FROM reviews WHERE product_id = ? AND user_id = ?");
                $check_stmt->bind_param("ii", $product_id, $_SESSION['user_id']);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    $_SESSION['error'] = 'Bu ürüne daha önce yorum yaptınız.';
                } else {
                    $insert_stmt = $conn->prepare("
                        INSERT INTO reviews (product_id, user_id, rating, comment) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $insert_stmt->bind_param("iiis", $product_id, $_SESSION['user_id'], $rating, $comment);
                    
                    if ($insert_stmt->execute()) {
                        $_SESSION['success'] = 'Yorumunuz başarıyla eklendi!';
                        // JavaScript ile yönlendirme yap
                        echo '<script>window.location.href = "shop-single.php?id=' . $product_id . '";</script>';
                        exit();
                    } else {
                        $_SESSION['error'] = 'Yorum eklenirken bir hata oluştu.';
                    }
                    $insert_stmt->close();
                }
                $check_stmt->close();
            }
        }
    }
}

// Benzer ürünleri al (aynı marka)
$brand_id = isset($product['brand_id']) ? $product['brand_id'] : null;
$similar_products = [];

if ($brand_id) {
    $similar_stmt = $conn->prepare("SELECT * FROM products WHERE brand_id = ? AND id != ? AND stock > 0 LIMIT 4");
    $similar_stmt->bind_param("ii", $brand_id, $product_id);
    $similar_stmt->execute();
    $similar_result = $similar_stmt->get_result();
    $similar_products = $similar_result->fetch_all(MYSQLI_ASSOC);
    $similar_stmt->close();
}
?>

    <!-- Page Header -->
    <section class="page-header bg-light py-5" style="margin-top: 70px;">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-12">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-2">
                            <li class="breadcrumb-item"><a href="/index.php"
                                    class="text-decoration-none text-primary">Ana Sayfa</a></li>
                            <li class="breadcrumb-item"><a href="/shop.php"
                                    class="text-decoration-none text-primary">Ürünler</a></li>
                            <li class="breadcrumb-item active"><?php echo isset($product['name']) ? htmlspecialchars($product['name']) : 'Ürün'; ?></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <!-- Hata ve Başarı Mesajlarını Göster -->
    <?php if (isset($_SESSION['error'])): ?>
        <div class="container mt-4">
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="container mt-4">
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Product Detail Section -->
    <section class="py-5">
        <div class="container">
            <div class="row g-5">
                <!-- Product Images -->
                <div class="col-lg-6" data-aos="fade-right">
                    <div class="product-images-container text-center position-relative">
                        <div class="main-image-container d-flex justify-content-center align-items-center rounded-4 overflow-hidden mb-3 position-relative" 
                            style="height: 500px; background-color: #f8f9fa; border: 1px solid #e9ecef;">
                            <?php if (!empty($product['image'])): ?>
                                <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                                    alt="<?php echo isset($product['name']) ? htmlspecialchars($product['name']) : 'Ürün'; ?>"
                                    id="main-product-image"
                                    style="max-height: 100%; max-width: 100%; object-fit: contain;"
                                    class="img-fluid">
                            <?php else: ?>
                                <div class="w-100 h-100 d-flex align-items-center justify-content-center">
                                    <i class="fas fa-image text-muted" style="font-size: 4rem;"></i>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Ürün Durumu İkonu -->
                            <?php if (isset($product['stock']) && $product['stock'] > 0): ?>
                                <div class="position-absolute top-0 start-0 m-3">
                                    <span class="badge bg-success px-3 py-2">
                                        <i class="fas fa-check-circle me-1"></i>Stokta
                                    </span>
                                </div>
                            <?php else: ?>
                                <div class="position-absolute top-0 start-0 m-3">
                                    <span class="badge bg-danger px-3 py-2">
                                        <i class="fas fa-times-circle me-1"></i>Stok Yok
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Product Info -->
                <div class="col-lg-6" data-aos="fade-left">
                    <div class="product-info">
                        <!-- Breadcrumb Info -->
                        <div class="mb-3">
                            <?php if (isset($product['category_name']) && !empty($product['category_name'])): ?>
                                <span class="badge bg-light text-dark me-2">
                                    <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($product['category_name']); ?>
                                </span>
                            <?php endif; ?>
                            <?php if (isset($product['brand_name']) && !empty($product['brand_name'])): ?>
                                <span class="badge bg-primary">
                                    <i class="fas fa-copyright me-1"></i><?php echo htmlspecialchars($product['brand_name']); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Product Title -->
                        <h1 class="display-5 fw-bold text-dark mb-3" style="font-family: 'Playfair Display', serif;">
                            <?php echo isset($product['name']) ? htmlspecialchars($product['name']) : 'Ürün Adı Yok'; ?>
                        </h1>

                        <!-- Rating -->
                        <div class="rating-section mb-3">
                            <div class="d-flex align-items-center">
                                <div class="star-rating me-2">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?php echo $i <= round($average_rating) ? 'text-warning' : 'text-muted'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <span class="text-muted">
                                    <i class="fas fa-star-half-alt me-1"></i><?php echo $average_rating; ?> - <?php echo $review_count; ?> yorum
                                </span>
                            </div>
                        </div>

                        <!-- Price -->
                        <div class="price-section mb-4">
                            <h2 class="text-primary fw-bold mb-2">
                                <i class="fas fa-tag me-2"></i>₺<?php echo isset($product['price']) ? number_format($product['price'], 2, ',', '.') : '0,00'; ?>
                            </h2>
                            <small class="text-muted">
                                <i class="fas fa-receipt me-1"></i>KDV Dahil
                            </small>
                        </div>

                        <!-- Stock Status -->
                        <div class="stock-section mb-4">
                            <?php if (isset($product['stock']) && $product['stock'] > 0): ?>
                                <span class="badge bg-success px-3 py-2">
                                    <i class="fas fa-box me-1"></i>Stokta Var (<?php echo $product['stock']; ?> adet)
                                </span>
                            <?php else: ?>
                                <span class="badge bg-danger px-3 py-2">
                                    <i class="fas fa-box-open me-1"></i>Stok Yok
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Short Description -->
                        <div class="description-section mb-4">
                            <p class="text-muted">
                                <i class="fas fa-align-left me-2 text-primary"></i>
                                <?php 
                                if (isset($product['description']) && !empty($product['description'])) {
                                    echo nl2br(htmlspecialchars($product['description']));
                                } else {
                                    echo 'Bu ürün için açıklama bulunmamaktadır.';
                                }
                                ?>
                            </p>
                        </div>

                        <!-- Quantity & Add to Cart -->
                        <div class="action-section mb-4">
                            <div class="row g-3">
<div class="col-md-6">
    <label class="form-label fw-medium">
        <i class="fas fa-sort-amount-up me-1"></i>Miktar
    </label>
    <div class="input-group input-group-responsive">
        <button class="btn btn-outline-secondary quantity-decrease" data-product="<?php echo $product_id; ?>">-</button>

        <input type="number" class="form-control text-center quantity-input"
               id="quantity_<?php echo $product_id; ?>"
               value="1"
               min="1"
               max="<?php echo isset($product['stock']) ? $product['stock'] : 0; ?>">

        <button class="btn btn-outline-secondary quantity-increase" data-product="<?php echo $product_id; ?>">+</button>
    </div>
</div>

                                <div class="col-md-6">
                                    <?php if (isset($product['stock']) && $product['stock'] > 0): ?>
                                        <label class="form-label fw-medium">&nbsp;</label>
                                        <button class="btn btn-primary w-100 add-to-cart" data-product="<?php echo $product_id; ?>">
                                            <i class="fas fa-shopping-cart me-2"></i>Sepete Ekle
                                        </button>
                                    <?php else: ?>
                                        <label class="form-label fw-medium">&nbsp;</label>
                                        <button class="btn btn-secondary w-100" disabled>
                                            <i class="fas fa-ban me-2"></i>Stok Yok
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Info -->
                        <div class="additional-info">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="info-box p-3 bg-light rounded-3">
                                        <i class="fas fa-truck text-primary me-2"></i>
                                        <strong>Hızlı Teslimat</strong>
                                        <p class="text-muted small mb-0">2-3 gün içinde teslimat</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-box p-3 bg-light rounded-3">
                                        <i class="fas fa-shield-alt text-success me-2"></i>
                                        <strong>Güvenli Ödeme</strong>
                                        <p class="text-muted small mb-0">SSL ile korumalı</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Details Tabs -->
            <div class="row mt-5">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-bottom-0">
                            <ul class="nav nav-tabs card-header-tabs" id="productTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab">
                                        <i class="fas fa-info-circle me-2"></i>Ürün Bilgisi
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="description-tab" data-bs-toggle="tab" data-bs-target="#description" type="button" role="tab">
                                        <i class="fas fa-file-alt me-2"></i>Açıklama
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="specs-tab" data-bs-toggle="tab" data-bs-target="#specs" type="button" role="tab">
                                        <i class="fas fa-list me-2"></i>Özellikler
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#reviews" type="button" role="tab">
                                        <i class="fas fa-star me-2"></i>Yorumlar (<?php echo $review_count; ?>)
                                    </button>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content" id="productTabsContent">
                                <!-- Ürün Bilgisi Tab -->
                                <div class="tab-pane fade show active" id="details" role="tabpanel">
                                    <?php if (isset($product['detailed_description']) && !empty($product['detailed_description'])): ?>
                                        <div class="row">
                                            <div class="col-md-8">
                                                <h5 class="fw-bold mb-3">
                                                    <i class="fas fa-align-left text-primary me-2"></i>Detaylı Ürün Açıklaması
                                                </h5>
                                                <p class="text-muted"><?php echo nl2br(htmlspecialchars($product['detailed_description'])); ?></p>
                                                
                                                <?php if (isset($product['features']) && !empty($product['features'])): ?>
                                                    <h6 class="fw-bold mt-4 mb-3">
                                                        <i class="fas fa-bolt text-warning me-2"></i>Öne Çıkan Özellikler
                                                    </h6>
                                                    <ul class="list-unstyled">
                                                        <?php 
                                                        $features = explode('\\n', $product['features']);
                                                        foreach ($features as $feature): 
                                                            if (!empty(trim($feature))):
                                                        ?>
                                                            <li class="mb-2">
                                                                <i class="fas fa-check text-success me-2"></i>
                                                                <?php echo htmlspecialchars(trim($feature)); ?>
                                                            </li>
                                                        <?php 
                                                            endif;
                                                        endforeach; 
                                                        ?>
                                                    </ul>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="bg-light p-4 rounded-3">
                                                    <h6 class="fw-bold mb-3">
                                                        <i class="fas fa-rocket text-info me-2"></i>Hızlı Bilgiler
                                                    </h6>
                                                    <div class="mb-3">
                                                        <strong><i class="fas fa-copyright me-1"></i>Marka:</strong><br>
                                                        <span class="text-muted"><?php echo htmlspecialchars($product['brand_name']); ?></span>
                                                    </div>
                                                    <div class="mb-3">
                                                        <strong><i class="fas fa-tag me-1"></i>Kategori:</strong><br>
                                                        <span class="text-muted"><?php echo htmlspecialchars($product['category_name']); ?></span>
                                                    </div>
                                                    <div class="mb-3">
                                                        <strong><i class="fas fa-box me-1"></i>Stok Durumu:</strong><br>
                                                        <span class="badge bg-<?php echo $product['stock'] > 0 ? 'success' : 'danger'; ?>">
                                                            <?php echo $product['stock'] > 0 ? 'Stokta Var' : 'Stok Yok'; ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted">
                                            <i class="fas fa-info-circle me-2"></i>Bu ürün için detaylı bilgi bulunmamaktadır.
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <!-- Açıklama Tab -->
                                <div class="tab-pane fade" id="description" role="tabpanel">
                                    <?php if (isset($product['usage_instructions']) && !empty($product['usage_instructions'])): ?>
                                        <h5 class="fw-bold mb-3">
                                            <i class="fas fa-book text-success me-2"></i>Kullanım Talimatları
                                        </h5>
                                        <div class="bg-light p-4 rounded-3">
                                            <ol class="mb-0">
                                                <?php 
                                                $instructions = explode('\\n', $product['usage_instructions']);
                                                foreach ($instructions as $instruction): 
                                                    if (!empty(trim($instruction))):
                                                ?>
                                                    <li class="mb-2"><?php echo htmlspecialchars(trim($instruction)); ?></li>
                                                <?php 
                                                    endif;
                                                endforeach; 
                                                ?>
                                            </ol>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted">
                                            <i class="fas fa-info-circle me-2"></i>Bu ürün için kullanım talimatları bulunmamaktadır.
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <!-- Özellikler Tab -->
                                <div class="tab-pane fade" id="specs" role="tabpanel">
                                    <?php if (isset($product['specifications']) && !empty($product['specifications'])): ?>
                                        <h5 class="fw-bold mb-3">
                                            <i class="fas fa-cogs text-primary me-2"></i>Teknik Özellikler
                                        </h5>
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <tbody>
                                                    <?php 
                                                    $specs = explode('\\n', $product['specifications']);
                                                    foreach ($specs as $spec): 
                                                        if (!empty(trim($spec))):
                                                            $parts = explode(':', $spec, 2);
                                                            if (count($parts) === 2):
                                                    ?>
                                                        <tr>
                                                            <td width="30%" class="fw-bold bg-light"><?php echo htmlspecialchars(trim($parts[0])); ?></td>
                                                            <td><?php echo htmlspecialchars(trim($parts[1])); ?></td>
                                                        </tr>
                                                    <?php 
                                                            endif;
                                                        endif;
                                                    endforeach; 
                                                    ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted">
                                            <i class="fas fa-info-circle me-2"></i>Bu ürün için teknik özellikler bulunmamaktadır.
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <!-- Yorumlar Tab -->
                                <div class="tab-pane fade" id="reviews" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <!-- Rating Summary -->
                                            <div class="rating-summary text-center mb-4">
                                                <h2 class="text-primary fw-bold"><?php echo $average_rating; ?></h2>
                                                <div class="star-rating mb-2">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <i class="fas fa-star <?php echo $i <= round($average_rating) ? 'text-warning' : 'text-muted'; ?>"></i>
                                                    <?php endfor; ?>
                                                </div>
                                                <p class="text-muted">
                                                    <i class="fas fa-comments me-1"></i><?php echo $review_count; ?> yorum
                                                </p>
                                            </div>

                                            <!-- Add Review Form -->
                                            <?php if (isset($_SESSION['user_id'])): ?>
                                                <div class="add-review-form">
                                                    <h6 class="fw-bold mb-3">
                                                        <i class="fas fa-edit text-primary me-2"></i>Yorum Yap
                                                    </h6>
                                                    <form method="POST" id="reviewForm">
                                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                        <div class="mb-3">
                                                            <label class="form-label">
                                                                <i class="fas fa-star me-1 text-warning"></i>Puanınız <span class="text-danger">*</span>
                                                            </label>
                                                            <div class="star-rating-input mb-2">
                                                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                                                    <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>">
                                                                    <label for="star<?php echo $i; ?>"><i class="fas fa-star"></i></label>
                                                                <?php endfor; ?>
                                                            </div>
                                                            <small class="text-muted d-block">Lütfen puanınızı seçin</small>
                                                            <div id="ratingError" class="text-danger small mt-1" style="display: none;">
                                                                <i class="fas fa-exclamation-triangle me-1"></i>Lütfen puan seçin
                                                            </div>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">
                                                                <i class="fas fa-comment me-1 text-info"></i>Yorumunuz <span class="text-danger">*</span>
                                                            </label>
                                                            <textarea name="comment" class="form-control" rows="4" placeholder="Ürün hakkındaki düşünceleriniz..." required></textarea>
                                                            <small class="text-muted">En az 10, en fazla 500 karakter</small>
                                                        </div>
                                                        <button type="submit" name="add_review" class="btn btn-primary w-100">
                                                            <i class="fas fa-paper-plane me-2"></i>Yorumu Gönder
                                                        </button>
                                                    </form>
                                                </div>
                                            <?php else: ?>
                                                <div class="alert alert-info">
                                                    <i class="fas fa-info-circle me-2"></i>
                                                    <p class="mb-0">Yorum yapmak için <a href="/login.php" class="alert-link">giriş yapın</a>.</p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-8">
                                            <!-- Reviews List -->
                                            <div class="reviews-list">
                                                <h6 class="fw-bold mb-3">
                                                    <i class="fas fa-users me-2 text-success"></i>Müşteri Yorumları
                                                </h6>
                                                
                                                <?php if (count($reviews) > 0): ?>
                                                    <?php foreach ($reviews as $review): ?>
                                                        <div class="review-item border-bottom pb-3 mb-3">
                                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                                <div>
                                                                    <strong>
                                                                        <i class="fas fa-user me-1 text-muted"></i><?php echo htmlspecialchars($review['username']); ?>
                                                                    </strong>
                                                                    <div class="star-rating small mt-1">
                                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                            <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                                                        <?php endfor; ?>
                                                                    </div>
                                                                </div>
                                                                <small class="text-muted">
                                                                    <i class="fas fa-clock me-1"></i><?php echo date('d.m.Y', strtotime($review['created_at'])); ?>
                                                                </small>
                                                            </div>
                                                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <p class="text-muted">
                                                        <i class="fas fa-comment-slash me-2"></i>Bu ürün için henüz yorum yapılmamış.
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Similar Products -->
            <?php if (!empty($similar_products)): ?>
                <div class="row mt-5 mb-5">
                    <div class="col-12 text-center mb-4" data-aos="fade-up">
                        <h3 class="fw-bold mb-2" style="font-family: 'Playfair Display', serif;">
                            <i class="fas fa-random me-2 text-primary"></i>Benzer Ürünler
                        </h3>
                        <p class="text-muted">Aynı markadan seçtiğimiz diğer ürünler</p>
                    </div>
                    
                    <div class="col-12">
                        <div class="similar-products-slider position-relative">
                            <div class="swiper similarProductsSwiper">
                                <div class="swiper-wrapper">
                                    <?php foreach ($similar_products as $similar): ?>
                                        <div class="swiper-slide">
                                            <div class="product-card h-100 bg-white rounded-4 shadow-sm overflow-hidden hover-lift">
                                                <div class="product-image position-relative overflow-hidden" style="height: 250px; background-color: #f8f9fa;">
                                                    <?php if (!empty($similar['image'])): ?>
                                                        <img src="<?php echo htmlspecialchars($similar['image']); ?>" 
                                                            alt="<?php echo isset($similar['name']) ? htmlspecialchars($similar['name']) : 'Ürün'; ?>"
                                                            class="w-100 h-100" style="object-fit: cover;">
                                                    <?php else: ?>
                                                        <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-light">
                                                            <i class="fas fa-image text-muted" style="font-size: 2rem;"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (isset($similar['stock']) && $similar['stock'] > 0): ?>
                                                        <div class="position-absolute top-0 end-0 m-2">
                                                            <span class="badge bg-success">
                                                                <i class="fas fa-check me-1"></i>Stokta
                                                            </span>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="position-absolute top-0 end-0 m-2">
                                                            <span class="badge bg-danger">
                                                                <i class="fas fa-times me-1"></i>Tükendi
                                                            </span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="p-3">
                                                    <h6 class="fw-semibold text-dark mb-2" style="min-height: 40px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                                        <?php echo isset($similar['name']) ? htmlspecialchars($similar['name']) : 'Ürün Adı Yok'; ?>
                                                    </h6>
                                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                                        <span class="text-primary fw-bold fs-5">
                                                            <i class="fas fa-tag me-1"></i>₺<?php echo isset($similar['price']) ? number_format($similar['price'], 2, ',', '.') : '0,00'; ?>
                                                        </span>
                                                    </div>
                                                    <div class="d-grid gap-2">
                                                        <a href="/shop-single.php?id=<?php echo $similar['id']; ?>" class="btn btn-outline-primary btn-sm">
                                                            <i class="fas fa-eye me-1"></i>Ürünü İncele
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <!-- Navigation buttons -->
                                <div class="swiper-button-next"></div>
                                <div class="swiper-button-prev"></div>
                                
                                <!-- Pagination -->
                                <div class="swiper-pagination"></div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Diğer Ürünleri Keşfet -->
            <div class="row mt-5 mb-5">
                <div class="col-12">
                    <div class="explore-more-section bg-gradient p-5 rounded-4 text-center position-relative overflow-hidden" 
                         style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);" data-aos="zoom-in">
                        <div class="position-absolute top-0 start-0 w-100 h-100" 
                             style="background: url('data:image/svg+xml,%3Csvg width=\"20\" height=\"20\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cpath d=\"M0 0h20v20H0z\" fill=\"none\"/%3E%3Cpath d=\"M10 0L0 10l10 10 10-10z\" fill=\"%23ffffff\" opacity=\".05\"/%3E%3C/svg%3E'); opacity: 0.3;"></div>
                        
                        <div class="position-relative">
                            <div class="mb-4">
                                <i class="fas fa-box-open text-dark mb-3" style="font-size: 3rem; opacity: 0.9;"></i>
                            </div>
                            <h2 class="text-dark fw-bold mb-3" style="font-family: 'Playfair Display', serif;">
                                Diğer Ürünlerimizi Keşfedin
                            </h2>
                            <p class="text-dark mb-4 opacity-90" style="max-width: 600px; margin: 0 auto;">
                                Geniş ürün yelpazemizde size özel birçok seçenek var. 
                                Kaliteli ve uygun fiyatlı ürünlerimize göz atın!
                            </p>
                            <div class="d-flex gap-3 justify-content-center flex-wrap">
                                <a href="/shop.php" class="btn btn-light btn-lg px-4">
                                    <i class="fas fa-shopping-bag me-2"></i>Tüm Ürünleri Gör
                                </a>
                                <a href="/shop.php?filter=new" class="btn btn-outline-dark btn-lg px-4">
                                    <i class="fas fa-star me-2"></i>Yeni Ürünler
                                </a>
                            </div>
                            
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <style>
    .star-rating {
        color: #ffc107;
    }
    
    .star-rating-input {
        display: flex;
        flex-direction: row-reverse;
        justify-content: flex-end;
    }
    
    .star-rating-input input {
        display: none;
    }
    
    .star-rating-input label {
        cursor: pointer;
        color: #ddd;
        font-size: 1.5rem;
        margin-right: 5px;
        transition: color 0.2s;
    }
    
    .star-rating-input label:hover,
    .star-rating-input label:hover ~ label,
    .star-rating-input input:checked ~ label {
        color: #ffc107;
    }
    
    .nav-tabs .nav-link {
        color: #6c757d;
        border: none;
        padding: 1rem 1.5rem;
    }
    
    .nav-tabs .nav-link.active {
        color: #667eea;
        border-bottom: 3px solid #667eea;
        background: transparent;
    }
    
    /* Swiper Slider Styles */
    .similarProductsSwiper {
        padding: 20px 50px 60px;
    }
    
    .similarProductsSwiper .swiper-button-next,
    .similarProductsSwiper .swiper-button-prev {
        width: 45px;
        height: 45px;
        background: white;
        border-radius: 50%;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        transition: all 0.3s ease;
    }
    
    .similarProductsSwiper .swiper-button-next:after,
    .similarProductsSwiper .swiper-button-prev:after {
        font-size: 18px;
        color: #667eea;
        font-weight: bold;
    }
    
    .similarProductsSwiper .swiper-button-next:hover,
    .similarProductsSwiper .swiper-button-prev:hover {
        background: #667eea;
        transform: scale(1.1);
    }
    
    .similarProductsSwiper .swiper-button-next:hover:after,
    .similarProductsSwiper .swiper-button-prev:hover:after {
        color: white;
    }
    
    .similarProductsSwiper .swiper-pagination {
        bottom: 20px;
    }
    
    .similarProductsSwiper .swiper-pagination-bullet {
        width: 10px;
        height: 10px;
        background: #667eea;
        opacity: 0.3;
        transition: all 0.3s ease;
    }
    
    .similarProductsSwiper .swiper-pagination-bullet-active {
        opacity: 1;
        width: 30px;
        border-radius: 5px;
    }
    
    .hover-lift {
        transition: all 0.3s ease;
    }
    
    .hover-lift:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 24px rgba(0,0,0,0.15) !important;
    }
    
    .category-quick-card {
        transition: all 0.3s ease;
        border: 1px solid rgba(255,255,255,0.2);
    }
    
    .category-quick-card:hover {
        background: rgba(255,255,255,0.25) !important;
        transform: translateY(-5px);
    }
    
    .backdrop-blur {
        backdrop-filter: blur(10px);
    }
    
    @media (max-width: 768px) {
        .similarProductsSwiper {
            padding: 20px 15px 60px;
        }
        
        .similarProductsSwiper .swiper-button-next,
        .similarProductsSwiper .swiper-button-prev {
            width: 35px;
            height: 35px;
        }
        
        .similarProductsSwiper .swiper-button-next:after,
        .similarProductsSwiper .swiper-button-prev:after {
            font-size: 14px;
        }
        
        .explore-more-section {
            padding: 3rem 1.5rem !important;
        }
        
        .explore-more-section h2 {
            font-size: 1.5rem;
        }
    }

/* Ana input group */
.input-group-responsive {
    display: flex;
    align-items: center;
    width: 100%;
    max-width: 200px;
    gap: 0;
}

/* Orta ekran – varsayılan */
.quantity-input {
    text-align: center;
    min-width: 60px;
    width: 60px;
}

/* Butonların sabit küçülmeyen yapısı */
.input-group-responsive .btn {
    flex: 0 0 40px;
    padding: 6px 0;
}

/* 767px ve altı – daha kompakt */
@media (max-width: 767px) {
    .input-group-responsive {
        max-width: 170px;
    }

    .input-group-responsive .btn {
        flex: 0 0 35px;
        padding: 5px 0;
        font-size: 14px;
    }

    .quantity-input {
        width: 50px;
        min-width: 50px;
        font-size: 14px;
    }
}

/* 576px ve altı – en kompakt mod */
@media (max-width: 576px) {
    .input-group-responsive {
        max-width: 150px;
    }

    .input-group-responsive .btn {
        flex: 0 0 30px;
        padding: 4px 0;
        font-size: 13px;
    }

    .quantity-input {
        width: 45px;
        min-width: 45px;
        font-size: 13px;
    }
}

    </style>

    <!-- Swiper JS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    
    <script>
    // Similar Products Slider
    document.addEventListener('DOMContentLoaded', function() {
        const similarProductsSwiper = new Swiper('.similarProductsSwiper', {
            slidesPerView: 1,
            spaceBetween: 20,
            loop: <?php echo count($similar_products) > 4 ? 'true' : 'false'; ?>,
            autoplay: {
                delay: 3500,
                disableOnInteraction: false,
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            breakpoints: {
                576: {
                    slidesPerView: 2,
                    spaceBetween: 20,
                },
                992: {
                    slidesPerView: 3,
                    spaceBetween: 25,
                },
                1200: {
                    slidesPerView: 4,
                    spaceBetween: 30,
                }
            }
        });

        // Yıldız seçimi hatası düzeltmesi
        const reviewForm = document.getElementById('reviewForm');
        const ratingInputs = document.querySelectorAll('input[name="rating"]');
        const ratingError = document.getElementById('ratingError');

        if (reviewForm) {
            reviewForm.addEventListener('submit', function(e) {
                let ratingSelected = false;
                
                // Yıldız seçilmiş mi kontrol et
                ratingInputs.forEach(input => {
                    if (input.checked) {
                        ratingSelected = true;
                    }
                });

                if (!ratingSelected) {
                    e.preventDefault();
                    ratingError.style.display = 'block';
                    
                    // Hata mesajını 3 saniye sonra gizle
                    setTimeout(() => {
                        ratingError.style.display = 'none';
                    }, 3000);
                } else {
                    ratingError.style.display = 'none';
                }
            });
        }

        // Yıldız seçildiğinde hatayı gizle
        ratingInputs.forEach(input => {
            input.addEventListener('change', function() {
                ratingError.style.display = 'none';
            });
        });
    });
    </script>

<?php require_once 'includes/footer.php'; ?>