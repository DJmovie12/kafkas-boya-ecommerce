<?php
$page_title = "Ürünler";
require_once 'includes/header.php';

// Filtreleme parametreleri
$brand_filter = isset($_GET['marka']) ? trim($_GET['marka']) : '';
$category_filter = isset($_GET['kategori']) ? trim($_GET['kategori']) : '';
$search_query = isset($_GET['ara']) ? trim($_GET['ara']) : '';
$sort = isset($_GET['sirala']) ? trim($_GET['sirala']) : 'default';

// SQL sorgusu oluştur - JOIN ifadelerini düzelt
$sql = "SELECT p.*, b.name as brand_name, c.name as category_name 
        FROM products p 
        LEFT JOIN brands b ON p.brand_id = b.id 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE 1=1";

$params = [];
$types = '';

// Marka filtresi
if (!empty($brand_filter)) {
    $sql .= " AND b.name = ?";
    $params[] = $brand_filter;
    $types .= 's';
}

// Kategori filtresi
if (!empty($category_filter)) {
    $sql .= " AND c.name = ?";
    $params[] = $category_filter;
    $types .= 's';
}

// Arama filtresi
if (!empty($search_query)) {
    $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $search_term = '%' . $search_query . '%';
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= 'ss';
}

// Sıralama
switch ($sort) {
    case 'price-low':
        $sql .= " ORDER BY p.price ASC";
        break;
    case 'price-high':
        $sql .= " ORDER BY p.price DESC";
        break;
    case 'name':
        $sql .= " ORDER BY p.name ASC";
        break;
    case 'newest':
        $sql .= " ORDER BY p.created_at DESC";
        break;
    default:
        $sql .= " ORDER BY p.created_at DESC";
}

// Sorguyu hazırla ve çalıştır
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Hata kontrolü
if (!$result) {
    die("Sorgu hatası: " . $conn->error);
}

$products = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Debug için ürünleri kontrol et (sadece geliştirme sırasında)
// echo "<pre>"; print_r($products); echo "</pre>";

// Tüm markaları ve kategorileri al (filtreleme için)
$brands_result = $conn->query("SELECT DISTINCT name FROM brands ORDER BY name");
$brands = $brands_result->fetch_all(MYSQLI_ASSOC);

$categories_result = $conn->query("SELECT DISTINCT name FROM categories ORDER BY name");
$categories = $categories_result->fetch_all(MYSQLI_ASSOC);
?>
    <!-- Page Header -->
    <section class="page-header bg-light py-5" style="margin-top: 76px;">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-2">
                            <li class="breadcrumb-item"><a href="/index.php"
                                    class="text-decoration-none text-primary">Ana Sayfa</a></li>
                            <li class="breadcrumb-item active">Ürünler</li>
                        </ol>
                    </nav>
                    <h1 class="display-5 fw-bold text-dark mb-0" style="font-family: 'Playfair Display', serif;">
                        Ürünlerimiz
                    </h1>
                    <p class="text-muted mt-2 mb-0">Profesyonel boya ve vernik ürünleri</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <div class="d-flex align-items-center justify-content-lg-end gap-2">
                        <span class="text-muted small">Sırala:</span>
                        <form method="GET" class="d-inline">
                            <input type="hidden" name="marka" value="<?php echo htmlspecialchars($brand_filter); ?>">
                            <input type="hidden" name="kategori" value="<?php echo htmlspecialchars($category_filter); ?>">
                            <input type="hidden" name="ara" value="<?php echo htmlspecialchars($search_query); ?>">
                            <select class="form-select form-select-sm" name="sirala" style="width: auto;" onchange="this.form.submit()">
                                <option value="default" <?php echo $sort === 'default' ? 'selected' : ''; ?>>Varsayılan</option>
                                <option value="price-low" <?php echo $sort === 'price-low' ? 'selected' : ''; ?>>Fiyat: Düşükten Yükseğe</option>
                                <option value="price-high" <?php echo $sort === 'price-high' ? 'selected' : ''; ?>>Fiyat: Yüksekten Düşüğe</option>
                                <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>İsme Göre</option>
                                <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>En Yeni</option>
                            </select>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <!-- Filters Sidebar -->
                <div class="col-lg-3">
                    <div class="filters-sidebar sticky-top" style="top: 100px;">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white border-0 pb-0">
                                <h5 class="fw-semibold mb-0">
                                    <i class="fas fa-filter me-2 text-primary"></i>Filtreler
                                </h5>
                            </div>
                            <div class="card-body">
                                <!-- Search Filter -->
                                <div class="mb-4">
                                    <label class="form-label fw-medium">Ürün Ara</label>
                                    <form method="GET" class="input-group">
                                        <input type="text" class="form-control" name="ara" placeholder="Ürün adı..." value="<?php echo htmlspecialchars($search_query); ?>">
                                        <button class="btn btn-primary" type="submit">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </form>
                                </div>

                                <!-- Brand Filter -->
                                <div class="mb-4">
                                    <label class="form-label fw-medium">Marka</label>
                                    <?php foreach ($brands as $brand): ?>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="brand_<?php echo htmlspecialchars($brand['name']); ?>" 
                                                <?php echo $brand_filter === $brand['name'] ? 'checked' : ''; ?>
                                                onchange="filterByBrand('<?php echo htmlspecialchars($brand['name']); ?>')">
                                            <label class="form-check-label" for="brand_<?php echo htmlspecialchars($brand['name']); ?>">
                                                <?php echo htmlspecialchars($brand['name']); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Category Filter -->
                                <div class="mb-4">
                                    <label class="form-label fw-medium">Kategori</label>
                                    <?php foreach ($categories as $category): ?>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="cat_<?php echo htmlspecialchars($category['name']); ?>"
                                                <?php echo $category_filter === $category['name'] ? 'checked' : ''; ?>
                                                onchange="filterByCategory('<?php echo htmlspecialchars($category['name']); ?>')">
                                            <label class="form-check-label" for="cat_<?php echo htmlspecialchars($category['name']); ?>">
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Clear Filters -->
                                <?php if (!empty($brand_filter) || !empty($category_filter) || !empty($search_query)): ?>
                                    <div class="d-grid">
                                        <a href="/shop.php" class="btn btn-outline-secondary btn-sm">
                                            <i class="fas fa-times me-1"></i>Filtreleri Temizle
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Products Grid -->
<!-- Products Grid -->
<div class="col-lg-9">
    <?php if (empty($products)): ?>
        <div class="text-center py-5">
            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
            <h4>Ürün Bulunamadı</h4>
            <p class="text-muted">Arama kriterlerinize uygun ürün bulunmamaktadır.</p>
            <a href="/shop.php" class="btn btn-primary mt-3">Tüm Ürünleri Gör</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($products as $product): ?>
                <div class="col-md-6 col-lg-4" data-aos="fade-up">
                    <div class="product-card h-100 bg-white rounded-4 shadow-sm overflow-hidden hover-lift">
                        <!-- Product Image -->
                        <div class="product-image position-relative overflow-hidden" style="height: 250px; background-color: #f8f9fa;">
                            <?php if (!empty($product['image'])): ?>
                                <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                                        alt="<?php echo isset($product['name']) ? htmlspecialchars($product['name']) : 'Ürün Resmi'; ?>"
                                    class="w-100 h-100 object-fit-cover">
                            <?php else: ?>
                                <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-light">
                                    <i class="fas fa-image text-muted" style="font-size: 3rem;"></i>
                                </div>
                            <?php endif; ?>
                            <?php if (isset($product['stock']) && $product['stock'] <= 0): ?>
                                <div class="position-absolute top-0 end-0 m-2">
                                    <span class="badge bg-danger">Stok Yok</span>
                                </div>
                            <?php else: ?>
                                <div class="position-absolute top-0 end-0 m-2">
                                    <span class="badge bg-success">Stokta</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Product Info -->
                        <div class="p-3">
                            <h6 class="fw-semibold text-dark mb-2">
                                <?php echo isset($product['name']) ? htmlspecialchars($product['name']) : 'Ürün Adı Yok'; ?>
                            </h6>
                            
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-primary fw-bold">
                                    ₺<?php echo isset($product['price']) ? number_format($product['price'], 2, ',', '.') : '0,00'; ?>
                                </span>
                                <?php if (isset($product['brand_name']) && !empty($product['brand_name'])): ?>
                                    <small class="text-muted"><?php echo htmlspecialchars($product['brand_name']); ?></small>
                                <?php endif; ?>
                            </div>

                            <div class="d-grid gap-2">
                                <a href="/shop-single.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-eye me-1"></i>Detaylar
                                </a>
                                <?php if (isset($product['stock']) && $product['stock'] > 0): ?>
                                    <button class="btn btn-primary btn-sm add-to-cart" data-product="<?php echo $product['id']; ?>">
                                        <i class="fas fa-shopping-cart me-1"></i>Sepete Ekle
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-secondary btn-sm" disabled>
                                        <i class="fas fa-ban me-1"></i>Stok Yok
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
            </div>
        </div>
    </section>

    <script>
        function filterByBrand(brand) {
            const url = new URL(window.location);
            if (url.searchParams.get('marka') === brand) {
                url.searchParams.delete('marka');
            } else {
                url.searchParams.set('marka', brand);
            }
            window.location = url.toString();
        }

        function filterByCategory(category) {
            const url = new URL(window.location);
            if (url.searchParams.get('kategori') === category) {
                url.searchParams.delete('kategori');
            } else {
                url.searchParams.set('kategori', category);
            }
            window.location = url.toString();
        }
    </script>

<?php require_once 'includes/footer.php'; ?>
