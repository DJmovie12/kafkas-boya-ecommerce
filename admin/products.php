<?php
require_once '../includes/db_connect.php';
require_once '../includes/session.php';

// Admin kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Ürün ekleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'add') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $brand_id = $_POST['brand_id'];
    $category_id = $_POST['category_id'];
    
    // Fotoğraf yükleme işlemi
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../assets/img/products/';
        
        // Klasör yoksa oluştur
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            // Benzersiz dosya adı oluştur
            $new_filename = 'product_' . time() . '_' . uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $image_path = 'assets/img/products/' . $new_filename;
            } else {
                $_SESSION['error'] = 'Dosya yüklenirken hata oluştu!';
            }
        } else {
            $_SESSION['error'] = 'Geçersiz dosya türü! Sadece JPG, PNG, WEBP veya GIF yükleyebilirsiniz.';
        }
    }
    
    if (!isset($_SESSION['error'])) {
        try {
            $stmt = $conn->prepare("
                INSERT INTO products (name, description, price, stock, brand_id, category_id, image) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("ssdiiss", $name, $description, $price, $stock, $brand_id, $category_id, $image_path);
            $stmt->execute();
            $stmt->close();
            
            $_SESSION['success'] = 'Ürün başarıyla eklendi!';
            header('Location: products.php');
            exit();
        } catch (Exception $e) {
            $_SESSION['error'] = 'Ürün eklenirken hata oluştu: ' . $e->getMessage();
        }
    }
}

// Ürün düzenleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'edit') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $brand_id = $_POST['brand_id'];
    $category_id = $_POST['category_id'];
    
    // Mevcut resmi al
    $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $current_product = $result->fetch_assoc();
    $stmt->close();
    
    $image_path = $current_product['image'];
    
    // Yeni fotoğraf yüklendiyse
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../assets/img/products/';
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $new_filename = 'product_' . time() . '_' . uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                // Eski dosyayı sil
                if ($image_path && file_exists('../' . $image_path)) {
                    unlink('../' . $image_path);
                }
                $image_path = 'assets/img/products/' . $new_filename;
            }
        }
    }
    
    try {
        $stmt = $conn->prepare("
            UPDATE products 
            SET name = ?, description = ?, price = ?, stock = ?, brand_id = ?, category_id = ?, image = ? 
            WHERE id = ?
        ");
        $stmt->bind_param("ssdiissi", $name, $description, $price, $stock, $brand_id, $category_id, $image_path, $id);
        $stmt->execute();
        $stmt->close();
        
        $_SESSION['success'] = 'Ürün başarıyla güncellendi!';
        header('Location: products.php');
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = 'Ürün güncellenirken hata oluştu: ' . $e->getMessage();
    }
}

// Ürün silme işlemi
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Ürün resmini al
    $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();
    
    // Ürünü sil
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    
    // Resmi sil
    if ($product && $product['image'] && file_exists('../' . $product['image'])) {
        unlink('../' . $product['image']);
    }
    
    $_SESSION['success'] = 'Ürün başarıyla silindi!';
    header('Location: products.php');
    exit();
}

// Markaları ve kategorileri çek
$brands_result = $conn->query("SELECT * FROM brands ORDER BY name");
$brands = $brands_result->fetch_all(MYSQLI_ASSOC);
$brands_result->free();

$categories_result = $conn->query("SELECT * FROM categories ORDER BY name");
$categories = $categories_result->fetch_all(MYSQLI_ASSOC);
$categories_result->free();

// Ürünleri listele
$products_result = $conn->query("
    SELECT p.*, b.name as brand_name, c.name as category_name 
    FROM products p 
    LEFT JOIN brands b ON p.brand_id = b.id 
    LEFT JOIN categories c ON p.category_id = c.id 
    ORDER BY p.created_at DESC
");
$products = $products_result->fetch_all(MYSQLI_ASSOC);
$products_result->free();

// Düzenlenecek ürün varsa bilgilerini al
$edit_product = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_product = $result->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ürün Yönetimi - Admin Paneli</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { 
            background-color: #f8f9fa; 
            font-family: 'Inter', sans-serif; 
        }
        
        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            z-index: 1000;
            padding-top: 20px;
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            border-left: 3px solid transparent;
            transition: all 0.3s ease;
            margin-bottom: 5px;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.1);
            border-left-color: white;
        }
        
        .main-content { 
            margin-left: 250px; 
            padding: 30px; 
        }
        
        .top-navbar {
            background: white;
            padding: 15px 30px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-left: 250px;
            position: sticky;
            top: 0;
            z-index: 999;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid #667eea;
            transition: all 0.3s ease;
            margin-bottom: 30px;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .card-header {
            background: white;
            border-bottom: 1px solid #e9ecef;
            padding: 20px;
            border-radius: 15px 15px 0 0;
        }
        
        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #5a6fd6 0%, #6c4596 100%);
        }
        
        .star-rating {
            color: #ffc107;
        }
        
        .star-rating.small {
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="text-center mb-4 px-3">
            <h5 class="text-white fw-bold mb-1">Kafkas Boya</h5>
            <small class="text-white-50">Admin Paneli</small>
        </div>
        
        <nav class="nav flex-column">
            <a href="dashboard.php" class="nav-link"><i class="fas fa-chart-line me-2" style="width:20px"></i> Dashboard</a>
            <a href="products.php" class="nav-link active"><i class="fas fa-boxes me-2" style="width:20px"></i> Ürünler</a>
            <a href="categories.php" class="nav-link"><i class="fas fa-list me-2" style="width:20px"></i> Kategoriler</a>
            <a href="brands.php" class="nav-link"><i class="fas fa-tag me-2" style="width:20px"></i> Markalar</a>
            <a href="orders.php" class="nav-link"><i class="fas fa-receipt me-2" style="width:20px"></i> Siparişler</a>
            <a href="users.php" class="nav-link"><i class="fas fa-users me-2" style="width:20px"></i> Kullanıcılar</a>
            <a href="last-comments.php" class="nav-link"><i class="fas fa-comments me-2" style="width:20px"></i> Son Yorumlar</a>
            <hr class="bg-white-50 mx-3">
            <a href="/logout.php" class="nav-link"><i class="fas fa-sign-out-alt me-2" style="width:20px"></i> Çıkış Yap</a>
        </nav>
    </div>

    <!-- Top Navbar -->
    <div class="top-navbar">
        <h4 class="mb-0 fw-bold">Ürün Yönetimi</h4>
        <div class="d-flex align-items-center gap-3">
            <span class="text-muted"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                <i class="fas fa-user"></i>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3">
            <h1 class="h2">Ürün Yönetimi</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                <i class="fas fa-plus me-2"></i>Yeni Ürün Ekle
            </button>
        </div>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Tüm Ürünler</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Resim</th>
                                <th>Ürün Adı</th>
                                <th>Marka</th>
                                <th>Kategori</th>
                                <th>Fiyat</th>
                                <th>Stok</th>
                                <th>Puan</th>
                                <th>Yorum</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><?php echo $product['id']; ?></td>
                                    <td>
                                        <?php if ($product['image']): ?>
                                            <img src="../<?php echo htmlspecialchars($product['image']); ?>" 
                                                 alt="" style="width: 50px; height: 50px; object-fit: cover;" class="rounded">
                                        <?php else: ?>
                                            <span class="text-muted">Resim yok</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['brand_name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                    <td>₺<?php echo number_format($product['price'], 2); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $product['stock'] > 10 ? 'success' : ($product['stock'] > 0 ? 'warning' : 'danger'); ?>">
                                            <?php echo $product['stock']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        // Bu ürünün ortalama puanını al
                                        $rating_stmt = $conn->prepare("SELECT AVG(rating) as avg_rating FROM reviews WHERE product_id = ?");
                                        $rating_stmt->bind_param("i", $product['id']);
                                        $rating_stmt->execute();
                                        $rating_result = $rating_stmt->get_result();
                                        $rating_data = $rating_result->fetch_assoc();
                                        $avg_rating = $rating_data['avg_rating'] ? round($rating_data['avg_rating'], 1) : 0;
                                        $rating_stmt->close();
                                        ?>
                                        <div class="star-rating small">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?php echo $i <= round($avg_rating) ? 'text-warning' : 'text-muted'; ?>" style="font-size: 0.8rem;"></i>
                                            <?php endfor; ?>
                                            <br>
                                            <small class="text-muted">(<?php echo $avg_rating; ?>)</small>
                                        </div>
                                    </td>
                                    <td>
                                        <?php 
                                        $count_stmt = $conn->prepare("SELECT COUNT(*) as review_count FROM reviews WHERE product_id = ?");
                                        $count_stmt->bind_param("i", $product['id']);
                                        $count_stmt->execute();
                                        $count_result = $count_stmt->get_result();
                                        $count_data = $count_result->fetch_assoc();
                                        $count_stmt->close();
                                        ?>
                                        <span class="badge bg-info"><?php echo $count_data['review_count']; ?></span>
                                    </td>
                                    <td>
                                        <a href="?action=edit&id=<?php echo $product['id']; ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit me-1"></i>Düzenle
                                        </a>
                                        <a href="?action=delete&id=<?php echo $product['id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Bu ürünü silmek istediğinizden emin misiniz?')">
                                            <i class="fas fa-trash me-1"></i>Sil
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Ürün Ekleme Modal -->
        <div class="modal fade" id="addProductModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form method="POST" action="?action=add" enctype="multipart/form-data">
                        <div class="modal-header">
                            <h5 class="modal-title">Yeni Ürün Ekle</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Ürün Adı</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Açıklama</label>
                                <textarea name="description" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Fiyat (₺)</label>
                                    <input type="number" name="price" class="form-control" step="0.01" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Stok</label>
                                    <input type="number" name="stock" class="form-control" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Marka</label>
                                    <select name="brand_id" class="form-select" required>
                                        <option value="">Seçiniz</option>
                                        <?php foreach ($brands as $brand): ?>
                                            <option value="<?php echo $brand['id']; ?>"><?php echo htmlspecialchars($brand['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Kategori</label>
                                <select name="category_id" class="form-select" required>
                                    <option value="">Seçiniz</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Ürün Resmi</label>
                                <input type="file" name="image" class="form-control" accept="image/*">
                                <small class="text-muted">JPG, PNG, WEBP veya GIF formatında resim yükleyebilirsiniz.</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                            <button type="submit" class="btn btn-primary">Kaydet</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Ürün Düzenleme Formu -->
        <?php if ($edit_product): ?>
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">Ürün Düzenle</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="?action=edit" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?php echo $edit_product['id']; ?>">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ürün Adı</label>
                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($edit_product['name']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fiyat (₺)</label>
                            <input type="number" name="price" class="form-control" step="0.01" value="<?php echo $edit_product['price']; ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($edit_product['description']); ?></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Stok</label>
                            <input type="number" name="stock" class="form-control" value="<?php echo $edit_product['stock']; ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Marka</label>
                            <select name="brand_id" class="form-select" required>
                                <?php foreach ($brands as $brand): ?>
                                    <option value="<?php echo $brand['id']; ?>" <?php echo $brand['id'] == $edit_product['brand_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($brand['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Kategori</label>
                            <select name="category_id" class="form-select" required>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo $category['id'] == $edit_product['category_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ürün Resmi</label>
                        <?php if ($edit_product['image']): ?>
                            <div class="mb-2">
                                <img src="../<?php echo htmlspecialchars($edit_product['image']); ?>" alt="" style="max-width: 200px;" class="rounded">
                                <p class="text-muted">Mevcut resim</p>
                            </div>
                        <?php endif; ?>
                        <input type="file" name="image" class="form-control" accept="image/*">
                        <small class="text-muted">Yeni resim yüklemek için seçin. Boş bırakılırsa mevcut resim korunur.</small>
                    </div>
                    <button type="submit" class="btn btn-primary">Güncelle</button>
                    <a href="products.php" class="btn btn-secondary">İptal</a>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>