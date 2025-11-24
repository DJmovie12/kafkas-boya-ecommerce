<?php
$page_title = "Ürün Detayı";
require_once 'includes/header.php';

// Ürün ID'sini al
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

// Debug için ürün verilerini kontrol et
// echo "<pre>"; print_r($product); echo "</pre>";

// Benzer ürünleri al (aynı marka)
$brand_id = isset($product['brand_id']) ? $product['brand_id'] : null;
$similar_products = [];

if ($brand_id) {
    $similar_stmt = $conn->prepare("SELECT * FROM products WHERE brand_id = ? AND id != ? LIMIT 4");
    $similar_stmt->bind_param("ii", $brand_id, $product_id);
    $similar_stmt->execute();
    $similar_result = $similar_stmt->get_result();
    $similar_products = $similar_result->fetch_all(MYSQLI_ASSOC);
    $similar_stmt->close();
}
?>

    <!-- Page Header -->
    <section class="page-header bg-light py-5" style="margin-top: 76px;">
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
                                    style="max-height: 100%; max-width: 100%; object-fit: contain;">
                            <?php else: ?>
                                <div class="w-100 h-100 d-flex align-items-center justify-content-center">
                                    <i class="fas fa-image text-muted" style="font-size: 4rem;"></i>
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
                                <span class="badge bg-light text-dark me-2"><?php echo htmlspecialchars($product['category_name']); ?></span>
                            <?php endif; ?>
                            <?php if (isset($product['brand_name']) && !empty($product['brand_name'])): ?>
                                <span class="badge bg-primary"><?php echo htmlspecialchars($product['brand_name']); ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Product Title -->
                        <h1 class="display-5 fw-bold text-dark mb-3" style="font-family: 'Playfair Display', serif;">
                            <?php echo isset($product['name']) ? htmlspecialchars($product['name']) : 'Ürün Adı Yok'; ?>
                        </h1>

                        <!-- Price -->
                        <div class="price-section mb-4">
                            <h2 class="text-primary fw-bold mb-2">
                                ₺<?php echo isset($product['price']) ? number_format($product['price'], 2, ',', '.') : '0,00'; ?>
                            </h2>
                            <small class="text-muted">KDV Dahil</small>
                        </div>

                        <!-- Stock Status -->
                        <div class="stock-section mb-4">
                            <?php if (isset($product['stock']) && $product['stock'] > 0): ?>
                                <span class="badge bg-success">Stokta Var (<?php echo $product['stock']; ?> adet)</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Stok Yok</span>
                            <?php endif; ?>
                        </div>

                        <!-- Description -->
                        <div class="description-section mb-4">
                            <h5 class="fw-bold mb-3">Ürün Açıklaması</h5>
                            <p class="text-muted">
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
                                    <label class="form-label fw-medium">Miktar</label>
                                    <div class="input-group">
                                        <button class="btn btn-outline-secondary quantity-decrease" data-product="<?php echo $product_id; ?>">-</button>
                                        <input type="number" class="form-control text-center" 
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

            <!-- Similar Products -->
            <?php if (!empty($similar_products)): ?>
                <div class="row mt-5">
                    <div class="col-12">
                        <h3 class="fw-bold mb-4">Benzer Ürünler</h3>
                    </div>
                    <?php foreach ($similar_products as $similar): ?>
                        <div class="col-md-6 col-lg-3" data-aos="fade-up">
                            <div class="product-card h-100 bg-white rounded-4 shadow-sm overflow-hidden hover-lift">
                                <div class="product-image position-relative overflow-hidden" style="height: 200px; background-color: #f8f9fa;">
                                    <?php if (!empty($similar['image'])): ?>
                                        <img src="<?php echo htmlspecialchars($similar['image']); ?>" 
                                            alt="<?php echo isset($similar['name']) ? htmlspecialchars($similar['name']) : 'Ürün'; ?>"
                                            class="w-100 h-100" style="object-fit: cover;">
                                    <?php else: ?>
                                        <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-light">
                                            <i class="fas fa-image text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="p-3">
                                    <h6 class="fw-semibold text-dark mb-2">
                                        <?php echo isset($similar['name']) ? htmlspecialchars($similar['name']) : 'Ürün Adı Yok'; ?>
                                    </h6>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="text-primary fw-bold">
                                            ₺<?php echo isset($similar['price']) ? number_format($similar['price'], 2, ',', '.') : '0,00'; ?>
                                        </span>
                                    </div>
                                    <div class="d-grid gap-2">
                                        <a href="/shop-single.php?id=<?php echo $similar['id']; ?>" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-eye me-1"></i>Detaylar
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <script>
    // Miktar artırma/azaltma fonksiyonları
    document.querySelectorAll('.quantity-decrease').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-product');
            const quantityInput = document.getElementById('quantity_' + productId);
            let currentValue = parseInt(quantityInput.value);
            if (currentValue > 1) {
                quantityInput.value = currentValue - 1;
            }
        });
    });

    document.querySelectorAll('.quantity-increase').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-product');
            const quantityInput = document.getElementById('quantity_' + productId);
            let currentValue = parseInt(quantityInput.value);
            const maxStock = parseInt(quantityInput.getAttribute('max'));
            if (currentValue < maxStock) {
                quantityInput.value = currentValue + 1;
            }
        });
    });

    // Sepete ekleme fonksiyonu
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-product');
            const quantity = document.getElementById('quantity_' + productId).value;
            
            // AJAX ile sepete ekleme işlemi burada yapılacak
            console.log('Ürün ID:', productId, 'Miktar:', quantity);
            alert('Ürün sepete eklendi!');
        });
    });
    </script>

<?php require_once 'includes/footer.php'; ?>