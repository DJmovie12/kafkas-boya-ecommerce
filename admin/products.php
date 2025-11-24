<?php
require_once '../includes/db_connect.php';
require_once '../includes/session.php';

// Admin kontrolü
requireAdmin();

$error = '';
$success = '';
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Ürün ekleme/düzenleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $brand_id = intval($_POST['brand_id'] ?? 0);
    $category_id = intval($_POST['category_id'] ?? 0);
    $image = trim($_POST['image'] ?? '');

    if (empty($name) || $price <= 0 || $brand_id === 0 || $category_id === 0) {
        $error = 'Tüm alanlar gereklidir ve fiyat 0\'dan büyük olmalıdır.';
    } else {
        if ($action === 'add') {
            // Yeni ürün ekle
            $stmt = $conn->prepare("INSERT INTO products (name, description, price, stock, brand_id, category_id, image) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssdiis", $name, $description, $price, $stock, $brand_id, $category_id, $image);
            
            if ($stmt->execute()) {
                $success = 'Ürün başarıyla eklendi.';
                $action = 'list';
            } else {
                $error = 'Ürün eklenirken hata oluştu.';
            }
            $stmt->close();
        } elseif ($action === 'edit' && $product_id > 0) {
            // Ürünü güncelle
            $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock = ?, brand_id = ?, category_id = ?, image = ? 
                                   WHERE id = ?");
            $stmt->bind_param("ssdiissi", $name, $description, $price, $stock, $brand_id, $category_id, $image, $product_id);
            
            if ($stmt->execute()) {
                $success = 'Ürün başarıyla güncellendi.';
                $action = 'list';
            } else {
                $error = 'Ürün güncellenirken hata oluştu.';
            }
            $stmt->close();
        }
    }
}

// Ürün silme işlemi
if (isset($_GET['delete']) && intval($_GET['delete']) > 0) {
    $delete_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        $success = 'Ürün başarıyla silindi.';
    } else {
        $error = 'Ürün silinirken hata oluştu.';
    }
    $stmt->close();
    $action = 'list';
}

// Tüm ürünleri al
$products = [];
if ($action === 'list') {
    $result = $conn->query("SELECT p.*, b.name as brand_name, c.name as category_name 
                           FROM products p 
                           LEFT JOIN brands b ON p.brand_id = b.id 
                           LEFT JOIN categories c ON p.category_id = c.id 
                           ORDER BY p.created_at DESC");
    $products = $result->fetch_all(MYSQLI_ASSOC);
}

// Ürünü düzenlemek için al
$product = null;
if ($action === 'edit' && $product_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();
}

// Markaları ve kategorileri al
$brands = $conn->query("SELECT * FROM brands ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ürün Yönetimi - Admin Paneli</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background-color: #f8f9fa;
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
            padding: 15px 20px;
            border-left: 3px solid transparent;
            transition: all 0.3s ease;
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
        
        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid #e9ecef;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #5568d3 0%, #6a3f91 100%);
        }
        
        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
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
            <a href="/admin/dashboard.php" class="nav-link">
                <i class="fas fa-chart-line me-2"></i>Dashboard
            </a>
            <a href="/admin/products.php" class="nav-link active">
                <i class="fas fa-boxes me-2"></i>Ürünler
            </a>
            <a href="/admin/categories.php" class="nav-link">
                <i class="fas fa-list me-2"></i>Kategoriler
            </a>
            <a href="/admin/brands.php" class="nav-link">
                <i class="fas fa-tag me-2"></i>Markalar
            </a>
            <a href="/admin/orders.php" class="nav-link">
                <i class="fas fa-receipt me-2"></i>Siparişler
            </a>
            <a href="/admin/users.php" class="nav-link">
                <i class="fas fa-users me-2"></i>Kullanıcılar
            </a>
            <hr class="bg-white-50">
            <a href="/logout.php" class="nav-link">
                <i class="fas fa-sign-out-alt me-2"></i>Çıkış Yap
            </a>
        </nav>
    </div>

    <!-- Top Navbar -->
    <div class="top-navbar">
        <h4 class="mb-0 fw-bold">Ürün Yönetimi</h4>
        <div class="d-flex align-items-center gap-3">
            <span class="text-muted"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
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

        <!-- Add/Edit Product Form -->
        <?php if ($action === 'add' || $action === 'edit'): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="fw-bold mb-0">
                        <?php echo $action === 'add' ? 'Yeni Ürün Ekle' : 'Ürünü Düzenle'; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label fw-medium">Ürün Adı *</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                    value="<?php echo htmlspecialchars($product['name'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="price" class="form-label fw-medium">Fiyat (₺) *</label>
                                <input type="number" step="0.01" class="form-control" id="price" name="price" 
                                    value="<?php echo htmlspecialchars($product['price'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="stock" class="form-label fw-medium">Stok *</label>
                                <input type="number" class="form-control" id="stock" name="stock" 
                                    value="<?php echo htmlspecialchars($product['stock'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="brand_id" class="form-label fw-medium">Marka *</label>
                                <select class="form-select" id="brand_id" name="brand_id" required>
                                    <option value="">Seçiniz</option>
                                    <?php foreach ($brands as $brand): ?>
                                        <option value="<?php echo $brand['id']; ?>" 
                                            <?php echo (isset($product['brand_id']) && $product['brand_id'] == $brand['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($brand['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="category_id" class="form-label fw-medium">Kategori *</label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">Seçiniz</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" 
                                            <?php echo (isset($product['category_id']) && $product['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="image" class="form-label fw-medium">Resim URL</label>
                                <input type="url" class="form-control" id="image" name="image" 
                                    value="<?php echo htmlspecialchars($product['image'] ?? ''); ?>">
                            </div>
                            <div class="col-12">
                                <label for="description" class="form-label fw-medium">Açıklama</label>
                                <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i><?php echo $action === 'add' ? 'Ürün Ekle' : 'Güncelle'; ?>
                            </button>
                            <a href="/admin/products.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>İptal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- Products List -->
        <?php if ($action === 'list'): ?>
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Ürün Listesi (<?php echo count($products); ?>)</h5>
                    <a href="/admin/products.php?action=add" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus me-2"></i>Yeni Ürün Ekle
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Ürün Adı</th>
                                    <th>Marka</th>
                                    <th>Kategori</th>
                                    <th>Fiyat</th>
                                    <th>Stok</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $prod): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($prod['name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($prod['brand_name'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($prod['category_name'] ?? '-'); ?></td>
                                        <td>₺<?php echo number_format($prod['price'], 2, ',', '.'); ?></td>
                                        <td>
                                            <?php if ($prod['stock'] > 0): ?>
                                                <span class="badge bg-success"><?php echo $prod['stock']; ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Stok Yok</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="/admin/products.php?action=edit&id=<?php echo $prod['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="/admin/products.php?delete=<?php echo $prod['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Emin misiniz?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
