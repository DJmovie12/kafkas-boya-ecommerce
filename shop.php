<?php
$page_title = "Ürünler";
require_once 'includes/header.php';

// Veritabanı bağlantısını kontrol et
// db_connect.php dosyasından gelen $conn değişkenini kullanıyoruz.
if (!isset($conn) || $conn === null) {
    echo '<div class="container my-5"><div class="alert alert-danger text-center">Veritabanı bağlantısı kurulamadı. Lütfen teknik ekiple iletişime geçin.</div></div>';
    require_once 'includes/footer.php';
    exit;
}

// --- 1. Filtreleme ve Sıralama Parametreleri ---
$brand_filter = isset($_GET['marka']) ? trim($_GET['marka']) : '';
$category_filter = isset($_GET['kategori']) ? trim($_GET['kategori']) : '';
$search_query = isset($_GET['ara']) ? trim($_GET['ara']) : '';
$sort = isset($_GET['sirala']) ? trim($_GET['sirala']) : 'default';

// --- 2. Yan Menü İçin Marka ve Kategori Listelerini Getir ---
$brands = [];
$stmt = $conn->prepare("SELECT id, name FROM brands ORDER BY name ASC");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $brands[] = $row;
}
$stmt->close();

$categories = [];
$stmt = $conn->prepare("SELECT id, name FROM categories ORDER BY name ASC");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}
$stmt->close();


// --- 3. Ana SQL Sorgusu Oluşturma ---

// Başlangıç sorgusu
// COALESCE(AVG(r.rating), 0) ile puanı olmayanlar 0 kabul edilir.
$sql = "SELECT p.*, b.name as brand_name, c.name as category_name, 
               COALESCE(AVG(r.rating), 0) as avg_rating, 
               COUNT(r.id) as review_count 
        FROM products p 
        LEFT JOIN brands b ON p.brand_id = b.id 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN reviews r ON p.id = r.product_id";
        
$where_conditions = [];
$params = [];
$types = '';

// Marka filtresi
if (!empty($brand_filter)) {
    $where_conditions[] = "b.name = ?";
    $params[] = $brand_filter;
    $types .= 's';
}

// Kategori filtresi
if (!empty($category_filter)) {
    $where_conditions[] = "c.name = ?";
    $params[] = $category_filter;
    $types .= 's';
}

// Arama sorgusu
if (!empty($search_query)) {
    $search_param = "%" . $search_query . "%";
    // Ürün adı veya açıklamasında arama yap
    $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

// WHERE clause'u GROUP BY'dan önce eklenmeli.
if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(" AND ", $where_conditions);
}

// GROUP BY: Hata veren satırın düzgün hali. Tüm non-aggregated sütunlar eklenmeli.
$sql .= " GROUP BY p.id, p.name, p.description, p.price, p.stock, p.brand_id, p.category_id, p.image, p.created_at, p.updated_at, b.name, c.name";


// Sıralama
switch ($sort) {
    case 'price_asc':
        $sql .= " ORDER BY p.price ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY p.price DESC";
        break;
    case 'rating_desc':
        // Popülerlik/Puanlama, yüksek puana ve çok yoruma göre sırala
        $sql .= " ORDER BY avg_rating DESC, review_count DESC"; 
        break;
    default:
        // Varsayılan: En yeni ürünler
        $sql .= " ORDER BY p.created_at DESC";
        break;
}

// --- 4. Sorguyu Çalıştır ve Ürünleri Al ---
$products = [];
if ($stmt = $conn->prepare($sql)) {
    if (!empty($types)) {
        // Parametreleri bind etmek için call_user_func_array kullanılır
        // Bu, farklı sayıda parametre olduğunda çalışmasını sağlar
        $stmt->bind_param($types, ...$params);
    }
    
    // Hata kontrolü
    if (!$stmt->execute()) {
        error_log("SQL Execute Error: " . $stmt->error);
        // Kullanıcıya dostça bir hata mesajı göster
        $product_error = true;
    } else {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        $product_error = false;
    }
    $stmt->close();
} else {
    error_log("SQL Prepare Error: " . $conn->error);
    $product_error = true;
}

?>

<!-- Page Header -->
<section class="page-header bg-light py-5" style="margin-top: 70px;">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2">
                        <li class="breadcrumb-item"><a href="/index.php" class="text-decoration-none text-primary">Ana Sayfa</a></li>
                        <li class="breadcrumb-item active">Ürünler</li>
                    </ol>
                </nav>
                <h1 class="display-5 fw-bold text-dark mb-0" style="font-family: 'Playfair Display', serif;">
                    Ürünlerimiz
                </h1>
                <p class="text-muted mt-2 mb-0">Profesyonel boya ve vernik ürünleri</p>
            </div>
        </div>
    </div>
</section>

<!-- Filtre ve Ürünler Bölümü -->
<div class="container my-5 pt-3">
    <div class="row" style="margin-top: 0;">
        <!-- Mobil Sabit (Sticky) Butonlar - Mobilde aktif, masaüstünde gizli -->
        <div class="d-lg-none fixed-bottom bg-white shadow-lg p-3 d-flex justify-content-around z-index-1030">
            <button class="btn btn-primary w-50 me-2" onclick="toggleMobileFilters()">
                <i class="fas fa-filter me-2"></i> Filtrele
            </button>
            <button class="btn btn-outline-primary w-50 ms-2" onclick="toggleMobileSort()">
                <i class="fas fa-sort-amount-down-alt me-2"></i> Sırala
            </button>
        </div>

        <!-- Filtre Yan Menüsü (Masaüstü) -->
        <div class="col-lg-3">
            <div class="card shadow-sm mb-4 sticky-top" style="top: 100px;">
                <div class="card-header bg-primary text-white fw-bold">
                    <i class="fas fa-filter me-2"></i>Filtrele
                </div>
                <div class="card-body">
                    <!-- Arama Formu -->
                    <form method="GET" class="mb-4">
                        <div class="input-group">
                            <input type="text" name="ara" class="form-control" placeholder="Ürün Ara..." value="<?= htmlspecialchars($search_query) ?>">
                            <button class="btn btn-outline-primary" type="submit"><i class="fas fa-search"></i></button>
                        </div>
                        <?php if (!empty($brand_filter)): ?><input type="hidden" name="marka" value="<?= htmlspecialchars($brand_filter) ?>"><?php endif; ?>
                        <?php if (!empty($category_filter)): ?><input type="hidden" name="kategori" value="<?= htmlspecialchars($category_filter) ?>"><?php endif; ?>
                        <?php if ($sort !== 'default'): ?><input type="hidden" name="sirala" value="<?= htmlspecialchars($sort) ?>"><?php endif; ?>
                    </form>

                    <!-- Markaya Göre Filtre -->
                    <h6 class="fw-bold text-secondary mb-2">Markalar</h6>
                    <ul class="list-group list-group-flush mb-4">
                        <?php foreach ($brands as $brand): ?>
                            <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                <a href="javascript:void(0)" onclick="filterByBrand('<?= htmlspecialchars($brand['name']) ?>')" class="text-decoration-none <?= $brand_filter === $brand['name'] ? 'fw-bold text-primary' : 'text-dark' ?>">
                                    <?= htmlspecialchars($brand['name']) ?>
                                </a>
                                <?php if ($brand_filter === $brand['name']): ?>
                                    <i class="fas fa-check-circle text-primary"></i>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                        <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                            <?php 
                                $current_params = array_filter(['kategori' => $category_filter, 'ara' => $search_query, 'sirala' => $sort]);
                                $all_brands_url = '/shop.php?' . http_build_query($current_params);
                            ?>
                            <a href="<?= $all_brands_url ?>" class="text-decoration-none <?= empty($brand_filter) ? 'fw-bold text-primary' : 'text-dark' ?>">
                                Tüm Markalar
                            </a>
                            <?php if (empty($brand_filter)): ?>
                                <i class="fas fa-check-circle text-primary"></i>
                            <?php endif; ?>
                        </li>
                    </ul>

                    <!-- Kategoriye Göre Filtre -->
                    <h6 class="fw-bold text-secondary mb-2">Kategoriler</h6>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($categories as $category): ?>
                            <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                <a href="javascript:void(0)" onclick="filterByCategory('<?= htmlspecialchars($category['name']) ?>')" class="text-decoration-none <?= $category_filter === $category['name'] ? 'fw-bold text-primary' : 'text-dark' ?>">
                                    <?= htmlspecialchars($category['name']) ?>
                                </a>
                                <?php if ($category_filter === $category['name']): ?>
                                    <i class="fas fa-check-circle text-primary"></i>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                        <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                            <?php 
                                $current_params = array_filter(['marka' => $brand_filter, 'ara' => $search_query, 'sirala' => $sort]);
                                $all_categories_url = '/shop.php?' . http_build_query($current_params);
                            ?>
                            <a href="<?= $all_categories_url ?>" class="text-decoration-none <?= empty($category_filter) ? 'fw-bold text-primary' : 'text-dark' ?>">
                                Tüm Kategoriler
                            </a>
                            <?php if (empty($category_filter)): ?>
                                <i class="fas fa-check-circle text-primary"></i>
                            <?php endif; ?>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Ürünler ve Sıralama (Sağ Bölüm) -->
        <div class="col-12 col-lg-9">
            <!-- Sıralama Seçeneği (Sadece Masaüstü) -->
            <div class="d-none d-lg-flex justify-content-end mb-4">
                <form method="GET" id="sort-form-desktop">
                    <?php if (!empty($brand_filter)): ?><input type="hidden" name="marka" value="<?= htmlspecialchars($brand_filter) ?>"><?php endif; ?>
                    <?php if (!empty($category_filter)): ?><input type="hidden" name="kategori" value="<?= htmlspecialchars($category_filter) ?>"><?php endif; ?>
                    <?php if (!empty($search_query)): ?><input type="hidden" name="ara" value="<?= htmlspecialchars($search_query) ?>"><?php endif; ?>
                    
                    <select name="sirala" id="sort-select-desktop" class="form-select" style="max-width: 250px;" onchange="document.getElementById('sort-form-desktop').submit()">
                        <option value="default" <?= $sort == 'default' ? 'selected' : '' ?>>Varsayılan Sıralama (En Yeni)</option>
                        <option value="price_asc" <?= $sort == 'price_asc' ? 'selected' : '' ?>>Fiyata Göre Artan</option>
                        <option value="price_desc" <?= $sort == 'price_desc' ? 'selected' : '' ?>>Fiyata Göre Azalan</option>
                        <option value="rating_desc" <?= $sort == 'rating_desc' ? 'selected' : '' ?>>Popülerlik (Yüksek Puan)</option>
                    </select>
                </form>
            </div>

            <!-- Ürün Listesi -->
            <?php if (isset($product_error) && $product_error): ?>
                <div class="alert alert-danger text-center py-5">
                    <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                    <h4>Ürünler yüklenirken bir hata oluştu. Lütfen daha sonra tekrar deneyin.</h4>
                </div>
            <?php elseif (empty($products)): ?>
                <div class="alert alert-info text-center py-5">
                    <i class="fas fa-box-open fa-3x mb-3"></i>
                    <h4>Aradığınız kriterlere uygun ürün bulunamadı.</h4>
                    <a href="/shop.php" class="btn btn-primary mt-3"><i class="fas fa-undo me-2"></i>Tüm Ürünlere Geri Dön</a>
                </div>
            <?php else: ?>
                <!-- Mobilde hep 2'li (row-cols-2), diğerlerinde 2 veya 3'lü (md:2, lg:3) -->
                <div class="row row-cols-2 row-cols-md-3 g-4">
                    <?php foreach ($products as $product): ?>
                        <div class="col">
                            <!-- Ürün Kartı -->
                            <div class="card h-100 shadow-sm border-0 product-card text-center"> 
                                <a href="/shop-single.php?id=<?php echo $product['id']; ?>" class="text-decoration-none">
                                    <img src="<?= htmlspecialchars($product['image'] ?? '/assets/img/default-product.png') ?>" 
                                         class="card-img-top p-3 rounded-t-lg mx-auto" 
                                         alt="<?= htmlspecialchars($product['name']) ?>" 
                                         style="height: 150px; object-fit: contain; width: 100%;">
                                </a>
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title fw-bold text-truncate text-primary mb-1">
                                        <a href="/shop-single.php?id=<?php echo $product['id']; ?>" class="text-decoration-none text-primary">
                                            <?= htmlspecialchars($product['name']) ?>
                                        </a>
                                    </h5>
                                    <p class="card-text text-muted small mb-1">Marka: <?= htmlspecialchars($product['brand_name'] ?? 'N/A') ?></p>
                                    
                                    <!-- Değerlendirme Puanı (Yıldız Sistemi) -->
                                    <div class="mb-2">
                                        <?php 
                                            $avg_rating = $product['avg_rating'];
                                            $full_stars = floor($avg_rating);
                                            $half_star = ($avg_rating - $full_stars) >= 0.5;
                                            $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);
                                            
                                            // Dolu Yıldızlar
                                            for ($i = 0; $i < $full_stars; $i++):
                                        ?>
                                            <i class="fas fa-star text-warning" style="font-size: 0.8rem;"></i>
                                        <?php endfor; ?>
                                        
                                        <!-- Yarım Yıldız -->
                                        <?php if ($half_star): ?>
                                            <i class="fas fa-star-half-alt text-warning" style="font-size: 0.8rem;"></i>
                                        <?php endif; ?>

                                        <!-- Boş Yıldızlar -->
                                        <?php for ($i = 0; $i < $empty_stars; $i++): ?>
                                            <i class="far fa-star text-secondary opacity-50" style="font-size: 0.8rem;"></i>
                                        <?php endfor; ?>

                                        <span class="small text-muted ms-1">(<?= number_format($avg_rating, 1, ',', '.') ?>)</span>
                                    </div>

                                    <!-- Fiyat -->
                                    <div class="mt-auto pt-2">
                                        <p class="fs-4 fw-bolder text-danger mb-2"><?= number_format($product['price'], 2, ',', '.') ?> ₺</p>
                                    </div>
                                    
                                    <!-- Stok Durumu -->
                                    <?php if ($product['stock'] > 0): ?>
                                        <span class="badge bg-success mb-3"><i class="fas fa-check-circle me-1"></i> Stokta Var</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger mb-3"><i class="fas fa-times-circle me-1"></i> Stokta Yok</span>
                                    <?php endif; ?>

                                    <!-- Butonlar -->
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
                
                <!-- Alt kısımda Mobil Sticky Butonlar için boşluk bırakılır -->
                <div class="d-lg-none" style="height: 70px;"></div>

            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Filtre Overlay (Mobil) -->
<div class="filter-overlay" onclick="closeAllMobile()"></div>

<!-- Mobil Filtre Kenar Çubuğu -->
<div class="filter-sidebar-mobile bg-white shadow-lg p-4" id="mobile-filter-sidebar">
    <h5 class="fw-bold mb-3 text-primary d-flex justify-content-between align-items-center">
        Filtrele
        <button class="btn-close" aria-label="Kapat" onclick="closeAllMobile()"></button>
    </h5>
    
    <!-- Arama Formu (Mobil) -->
    <form method="GET" class="mb-4">
        <div class="input-group">
            <input type="text" name="ara" class="form-control" placeholder="Ürün Ara..." value="<?= htmlspecialchars($search_query) ?>">
            <button class="btn btn-outline-primary" type="submit"><i class="fas fa-search"></i></button>
        </div>
        <?php if (!empty($brand_filter)): ?><input type="hidden" name="marka" value="<?= htmlspecialchars($brand_filter) ?>"><?php endif; ?>
        <?php if (!empty($category_filter)): ?><input type="hidden" name="kategori" value="<?= htmlspecialchars($category_filter) ?>"><?php endif; ?>
        <?php if ($sort !== 'default'): ?><input type="hidden" name="sirala" value="<?= htmlspecialchars($sort) ?>"><?php endif; ?>
    </form>

    <h6 class="fw-bold text-secondary mb-2">Markalar</h6>
    <div class="list-group mb-4">
        <?php foreach ($brands as $brand): ?>
            <a href="javascript:void(0)" onclick="filterByBrand('<?= htmlspecialchars($brand['name']) ?>')" class="list-group-item list-group-item-action <?= $brand_filter === $brand['name'] ? 'active bg-primary border-primary' : '' ?>">
                <?= htmlspecialchars($brand['name']) ?>
            </a>
        <?php endforeach; ?>
        <a href="/shop.php?<?= http_build_query(array_filter(['kategori' => $category_filter, 'ara' => $search_query, 'sirala' => $sort])) ?>" class="list-group-item list-group-item-action <?= empty($brand_filter) ? 'active bg-primary border-primary' : '' ?>">
            Tüm Markalar
        </a>
    </div>

    <h6 class="fw-bold text-secondary mb-2">Kategoriler</h6>
    <div class="list-group">
        <?php foreach ($categories as $category): ?>
            <a href="javascript:void(0)" onclick="filterByCategory('<?= htmlspecialchars($category['name']) ?>')" class="list-group-item list-group-item-action <?= $category_filter === $category['name'] ? 'active bg-primary border-primary' : '' ?>">
                <?= htmlspecialchars($category['name']) ?>
            </a>
        <?php endforeach; ?>
        <a href="/shop.php?<?= http_build_query(array_filter(['marka' => $brand_filter, 'ara' => $search_query, 'sirala' => $sort])) ?>" class="list-group-item list-group-item-action <?= empty($category_filter) ? 'active bg-primary border-primary' : '' ?>">
            Tüm Kategoriler
        </a>
    </div>
</div>

<!-- Mobil Sıralama Kenar Çubuğu -->
<div class="filter-sidebar-mobile bg-white shadow-lg p-4" id="mobile-sort-sidebar">
    <h5 class="fw-bold mb-3 text-primary d-flex justify-content-between align-items-center">
        Sıralama Seçenekleri
        <button class="btn-close" aria-label="Kapat" onclick="closeAllMobile()"></button>
    </h5>
    
    <form method="GET" id="sort-form-mobile">
        <?php if (!empty($brand_filter)): ?><input type="hidden" name="marka" value="<?= htmlspecialchars($brand_filter) ?>"><?php endif; ?>
        <?php if (!empty($category_filter)): ?><input type="hidden" name="kategori" value="<?= htmlspecialchars($category_filter) ?>"><?php endif; ?>
        <?php if (!empty($search_query)): ?><input type="hidden" name="ara" value="<?= htmlspecialchars($search_query) ?>"><?php endif; ?>

        <div class="list-group">
            <a href="javascript:void(0)" onclick="selectSortOption('default')" class="list-group-item list-group-item-action <?= $sort == 'default' ? 'active bg-primary border-primary' : '' ?>">
                Varsayılan Sıralama (En Yeni)
            </a>
            <a href="javascript:void(0)" onclick="selectSortOption('price_asc')" class="list-group-item list-group-item-action <?= $sort == 'price_asc' ? 'active bg-primary border-primary' : '' ?>">
                Fiyata Göre Artan
            </a>
            <a href="javascript:void(0)" onclick="selectSortOption('price_desc')" class="list-group-item list-group-item-action <?= $sort == 'price_desc' ? 'active bg-primary border-primary' : '' ?>">
                Fiyata Göre Azalan
            </a>
            <a href="javascript:void(0)" onclick="selectSortOption('rating_desc')" class="list-group-item list-group-item-action <?= $sort == 'rating_desc' ? 'active bg-primary border-primary' : '' ?>">
                Popülerlik (Yüksek Puan)
            </a>
        </div>
        
        <!-- Sıralama değerini göndermek için gizli input -->
        <input type="hidden" name="sirala" id="mobile-sort-input" value="<?= htmlspecialchars($sort) ?>">
    </form>
</div>

<style>
/* --- Görsel İyileştirme CSS Kodları --- */
/* Mobile First Yaklaşımı */
.product-card {
    transition: transform 0.2s, box-shadow 0.2s; /* Hover efekti için */
    border-radius: 12px;
}

.product-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
}

.z-index-1030 {
    z-index: 1030 !important;
}

/* Mobil Filtre Stil Tüyoları (MANDATORY) */
.filter-sidebar-mobile {
    position: fixed;
    top: 0;
    right: -300px; /* Başlangıçta gizli */
    width: 300px;
    max-width: 90vw; /* Küçük ekranlarda taşmayı engelle */
    height: 100%;
    z-index: 1050;
    transition: right 0.3s ease-in-out;
    overflow-y: auto;
}
.filter-sidebar-mobile.show {
    right: 0;
}
.filter-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1040;
    display: none;
}
.filter-overlay.show {
    display: block;
}

/* 768px (md) altında 2 kart düzeni zaten row-cols-2 ile sağlandı,
   ama eğer kartların boyutu mobile çok büyük gelirse max-width ekleyebiliriz */
@media (max-width: 576px) {
    /* En küçük telefonlarda kartları biraz daha daraltmak için */
    .row.row-cols-2 .col {
        padding-left: 0.5rem !important;
        padding-right: 0.5rem !important;
    }
}

/* Desktop'ta (lg) filtre çubuğunu sabitlemek için ek style.top */
.sticky-top {
    top: 100px; /* Navbar yüksekliği kadar boşluk */
}
</style>

<script>
    // URL parametrelerini koruyarak filtreleme yapan fonksiyon
    function updateUrlParameter(key, value) {
        const url = new URL(window.location);
        
        // Değer zaten seçiliyse (toggle işlevi) kaldır
        if (url.searchParams.get(key) === value) {
            url.searchParams.delete(key);
        } else {
            // Yeni değeri ayarla
            url.searchParams.set(key, value);
        }
        
        // Sayfayı yeni URL ile yeniden yükle
        window.location = url.toString();
    }
    
    function filterByBrand(brand) {
        // Mobil menüyü de kapat
        closeAllMobile();
        updateUrlParameter('marka', brand);
    }

    function filterByCategory(category) {
        // Mobil menüyü de kapat
        closeAllMobile();
        updateUrlParameter('kategori', category);
    }

    // Mobil Filtre Kenar Çubuğunu aç
    function toggleMobileFilters() {
        const sidebar = document.getElementById('mobile-filter-sidebar');
        const overlay = document.querySelector('.filter-overlay');
        
        // Diğer menüyü kapat
        document.getElementById('mobile-sort-sidebar').classList.remove('show');
        
        sidebar.classList.toggle('show');
        overlay.classList.toggle('show');
    }
    
    // Mobil Sıralama Kenar Çubuğunu aç
    function toggleMobileSort() {
        const sidebar = document.getElementById('mobile-sort-sidebar');
        const overlay = document.querySelector('.filter-overlay');

        // Diğer menüyü kapat
        document.getElementById('mobile-filter-sidebar').classList.remove('show');

        sidebar.classList.toggle('show');
        overlay.classList.toggle('show');
    }
    
    // Tüm mobil menüleri kapat
    function closeAllMobile() {
        document.getElementById('mobile-filter-sidebar').classList.remove('show');
        document.getElementById('mobile-sort-sidebar').classList.remove('show');
        document.querySelector('.filter-overlay').classList.remove('show');
    }
    
    // Sıralama seçeneğini seç ve formu gönder
    function selectSortOption(sortValue) {
        document.getElementById('mobile-sort-input').value = sortValue;
        document.getElementById('sort-form-mobile').submit();
    }

    // Overlay'a tıklanınca filtreleri kapat
    document.querySelector('.filter-overlay').addEventListener('click', function() {
        closeAllMobile();
    });
</script>

<?php require_once 'includes/footer.php'; ?>