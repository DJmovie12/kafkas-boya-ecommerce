<?php
require_once '../includes/db_connect.php';
require_once '../includes/session.php';

// Admin kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
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

function validate_image($file) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    if (!in_array($file['type'], $allowed_types)) {
        return false;
    }
    
    if ($file['size'] > $max_size) {
        return false;
    }
    
    // Dosya uzantısı kontrolü
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($file_extension, $allowed_extensions)) {
        return false;
    }
    
    return true;
}

function generate_filename($original_name) {
    $extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
    $timestamp = time();
    $random_string = bin2hex(random_bytes(8));
    return "product_{$timestamp}_{$random_string}.{$extension}";
}

// CSRF Token oluştur
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// AJAX endpoint for product editing
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_product' && isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();
    
    if ($product) {
        // Features ve specifications'ı array'e çevir
        $product['features_array'] = $product['features'] ? explode('\\n', $product['features']) : [''];
        $product['specs_array'] = [];
        
        if ($product['specifications']) {
            $specs = explode('\\n', $product['specifications']);
            foreach ($specs as $spec) {
                $parts = explode(':', $spec, 2);
                if (count($parts) === 2) {
                    $product['specs_array'][] = [
                        'key' => trim($parts[0]),
                        'value' => trim($parts[1])
                    ];
                }
            }
        }
        
        if (empty($product['specs_array'])) {
            $product['specs_array'] = [['key' => '', 'value' => '']];
        }
        
        header('Content-Type: application/json');
        echo json_encode($product);
        exit();
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Ürün bulunamadı']);
        exit();
    }
}

// Ürün silme işlemi (Pasif hale getirme)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // CSRF kontrolü
    if (!isset($_GET['token']) || !hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
        $_SESSION['error'] = 'Geçersiz işlem!';
        header('Location: products.php');
        exit();
    }
    
    try {
        // Ürünün sipariş geçmişini kontrol et
        $stmt = $conn->prepare("SELECT COUNT(*) as order_count FROM order_items WHERE product_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $order_data = $result->fetch_assoc();
        $stmt->close();
        
        if ($order_data['order_count'] > 0) {
            // Sipariş varsa ürünü pasif hale getir
            $stmt = $conn->prepare("UPDATE products SET stock = 0 WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            
            $_SESSION['success'] = 'Ürün sipariş geçmişi olduğu için stok 0 yapıldı. Artık satışta görünmeyecek.';
        } else {
            // Sipariş yoksa tamamen sil
            $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
            $stmt->close();
            
            $conn->begin_transaction();
            
            // Sepetten sil
            $stmt = $conn->prepare("DELETE FROM cart WHERE product_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            
            // Yorumlardan sil
            $stmt = $conn->prepare("DELETE FROM reviews WHERE product_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            
            // Ürünü sil
            $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            
            $conn->commit();
            
            // Resmi sil
            if ($product['image'] && file_exists('../' . $product['image'])) {
                unlink('../' . $product['image']);
            }
            
            $_SESSION['success'] = 'Ürün başarıyla silindi!';
        }
        
        header('Location: products.php');
        exit();
        
    } catch (Exception $e) {
        if (isset($conn) && $conn) {
            $conn->rollback();
        }
        error_log("Ürün silme hatası: " . $e->getMessage());
        $_SESSION['error'] = 'İşlem sırasında hata oluştu: ' . $e->getMessage();
        header('Location: products.php');
        exit();
    }
}

// Ürünü aktif/pasif yapma işlemi
if (isset($_GET['action']) && $_GET['action'] === 'toggle_status' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // CSRF kontrolü
    if (!isset($_GET['token']) || !hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
        $_SESSION['error'] = 'Geçersiz işlem!';
        header('Location: products.php');
        exit();
    }
    
    try {
        // Mevcut durumu al
        $stmt = $conn->prepare("SELECT stock, previous_stock FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        $stmt->close();
        
        // Stok durumunu değiştir
        if ($product['stock'] == 0) {
            // Eski stoğu geri yükle
            $new_stock = $product['previous_stock'] > 0 ? $product['previous_stock'] : 10;
            $previous_stock = $product['previous_stock']; // Aynı kalır
        } else {
            // Mevcut stoğu previous_stock'a kaydet ve 0 yap
            $new_stock = 0;
            $previous_stock = $product['stock'];
        }
        
        $stmt = $conn->prepare("UPDATE products SET stock = ?, previous_stock = ? WHERE id = ?");
        $stmt->bind_param("iii", $new_stock, $previous_stock, $id);
        $stmt->execute();
        $stmt->close();
        
        $status = $new_stock == 0 ? 'pasif' : 'aktif';
        $_SESSION['success'] = "Ürün başarıyla {$status} hale getirildi!";
        header('Location: products.php');
        exit();
        
    } catch (Exception $e) {
        error_log("Ürün durum değiştirme hatası: " . $e->getMessage());
        $_SESSION['error'] = 'Durum değiştirilirken hata oluştu: ' . $e->getMessage();
        header('Location: products.php');
        exit();
    }
}

// Helper function - array to string for database
function formatFeaturesForDB($features_array) {
    return implode('\\n', array_filter($features_array));
}

function formatSpecificationsForDB($specs_array) {
    $result = [];
    foreach ($specs_array as $spec) {
        if (!empty(trim($spec['key'])) && !empty(trim($spec['value']))) {
            $result[] = trim($spec['key']) . ': ' . trim($spec['value']);
        }
    }
    return implode('\\n', $result);
}

// Ürün ekleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    // CSRF kontrolü
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error'] = 'Geçersiz işlem!';
        header('Location: products.php');
        exit();
    }
    
    $name = sanitize_input($_POST['name']);
    $description = sanitize_input($_POST['description']);
    $detailed_description = sanitize_input($_POST['detailed_description']);
    
    // Features array'i oluştur
    $features_array = [];
    if (isset($_POST['features']) && is_array($_POST['features'])) {
        $features_array = array_filter(array_map('sanitize_input', $_POST['features']));
    }
    $features = formatFeaturesForDB($features_array);
    
    $usage_instructions = sanitize_input($_POST['usage_instructions']);
    
    // Specifications array'i oluştur
    $specs_array = [];
    if (isset($_POST['spec_keys']) && isset($_POST['spec_values'])) {
        $keys = array_map('sanitize_input', $_POST['spec_keys']);
        $values = array_map('sanitize_input', $_POST['spec_values']);
        for ($i = 0; $i < count($keys); $i++) {
            if (!empty(trim($keys[$i])) && !empty(trim($values[$i]))) {
                $specs_array[] = [
                    'key' => $keys[$i],
                    'value' => $values[$i]
                ];
            }
        }
    }
    $specifications = formatSpecificationsForDB($specs_array);
    
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $brand_id = intval($_POST['brand_id']);
    $category_id = intval($_POST['category_id']);
    
    // Fotoğraf yükleme işlemi
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        if (validate_image($_FILES['image'])) {
            $upload_dir = '../assets/img/products/';
            
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $new_filename = generate_filename($_FILES['image']['name']);
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $image_path = 'assets/img/products/' . $new_filename;
            } else {
                $_SESSION['error'] = 'Dosya yüklenirken hata oluştu!';
            }
        } else {
            $_SESSION['error'] = 'Geçersiz dosya türü! Sadece JPG, PNG, WEBP veya GIF yükleyebilirsiniz. (Max: 5MB)';
        }
    }
    
    if (!isset($_SESSION['error'])) {
        try {
            // Ürün adı kontrolü (aynı isimde ürün var mı?)
            $check_stmt = $conn->prepare("SELECT id FROM products WHERE name = ?");
            $check_stmt->bind_param("s", $name);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $_SESSION['error'] = 'Bu isimde bir ürün zaten mevcut!';
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO products (name, description, detailed_description, features, usage_instructions, specifications, price, stock, brand_id, category_id, image) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("ssssssdiiss", $name, $description, $detailed_description, $features, $usage_instructions, $specifications, $price, $stock, $brand_id, $category_id, $image_path);
                $stmt->execute();
                $stmt->close();
                
                $_SESSION['success'] = 'Ürün başarıyla eklendi!';
            }
            $check_stmt->close();
        } catch (Exception $e) {
            error_log("Ürün ekleme hatası: " . $e->getMessage());
            $_SESSION['error'] = 'Ürün eklenirken hata oluştu: ' . $e->getMessage();
        }
    }
    header('Location: products.php');
    exit();
}

// Ürün düzenleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    // CSRF kontrolü
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error'] = 'Geçersiz işlem!';
        header('Location: products.php');
        exit();
    }
    
    $id = intval($_POST['id']);
    $name = sanitize_input($_POST['name']);
    $description = sanitize_input($_POST['description']);
    $detailed_description = sanitize_input($_POST['detailed_description']);
    
    // Features array'i oluştur
    $features_array = [];
    if (isset($_POST['features']) && is_array($_POST['features'])) {
        $features_array = array_filter(array_map('sanitize_input', $_POST['features']));
    }
    $features = formatFeaturesForDB($features_array);
    
    $usage_instructions = sanitize_input($_POST['usage_instructions']);
    
    // Specifications array'i oluştur
    $specs_array = [];
    if (isset($_POST['spec_keys']) && isset($_POST['spec_values'])) {
        $keys = array_map('sanitize_input', $_POST['spec_keys']);
        $values = array_map('sanitize_input', $_POST['spec_values']);
        for ($i = 0; $i < count($keys); $i++) {
            if (!empty(trim($keys[$i])) && !empty(trim($values[$i]))) {
                $specs_array[] = [
                    'key' => $keys[$i],
                    'value' => $values[$i]
                ];
            }
        }
    }
    $specifications = formatSpecificationsForDB($specs_array);
    
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $brand_id = intval($_POST['brand_id']);
    $category_id = intval($_POST['category_id']);
    
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
        if (validate_image($_FILES['image'])) {
            $upload_dir = '../assets/img/products/';
            
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $new_filename = generate_filename($_FILES['image']['name']);
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                // Eski resmi sil
                if ($image_path && file_exists('../' . $image_path)) {
                    unlink('../' . $image_path);
                }
                $image_path = 'assets/img/products/' . $new_filename;
            }
        } else {
            $_SESSION['error'] = 'Geçersiz dosya türü! Sadece JPG, PNG, WEBP veya GIF yükleyebilirsiniz. (Max: 5MB)';
        }
    }
    
    try {
        // Ürün adı kontrolü (başka bir üründe aynı isim var mı?)
        $check_stmt = $conn->prepare("SELECT id FROM products WHERE name = ? AND id != ?");
        $check_stmt->bind_param("si", $name, $id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $_SESSION['error'] = 'Bu isimde başka bir ürün zaten mevcut!';
        } else {
            $stmt = $conn->prepare("
                UPDATE products 
                SET name = ?, description = ?, detailed_description = ?, features = ?, usage_instructions = ?, specifications = ?, price = ?, stock = ?, brand_id = ?, category_id = ?, image = ? 
                WHERE id = ?
            ");
            $stmt->bind_param("ssssssdiissi", $name, $description, $detailed_description, $features, $usage_instructions, $specifications, $price, $stock, $brand_id, $category_id, $image_path, $id);
            $stmt->execute();
            $stmt->close();
            
            $_SESSION['success'] = 'Ürün başarıyla güncellendi!';
        }
        $check_stmt->close();
    } catch (Exception $e) {
        error_log("Ürün güncelleme hatası: " . $e->getMessage());
        $_SESSION['error'] = 'Ürün güncellenirken hata oluştu: ' . $e->getMessage();
    }
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
        
        .form-textarea {
            min-height: 100px;
        }
        
        .feature-item, .spec-row {
            margin-bottom: 10px;
            padding: 10px;
            border: 1px solid #e9ecef;
            border-radius: 5px;
            background: #f8f9fa;
        }
        
        .add-feature-btn, .add-spec-btn {
            margin-top: 10px;
        }
        
        .remove-btn {
            margin-left: 10px;
        }
        
        .spec-table {
            width: 100%;
        }
        
        .spec-table input {
            width: 100%;
        }
        
        .image-upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            background: #f8f9fa;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .image-upload-area:hover {
            border-color: #667eea;
            background: #f0f4ff;
        }
        
        .image-upload-area.dragover {
            border-color: #667eea;
            background: #e8f4ff;
        }
        
        .image-preview-container {
            text-align: center;
            margin-bottom: 15px;
        }
        
        .image-preview {
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
            border: 2px solid #e9ecef;
        }
        
        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .modal-xl {
            max-width: 1200px;
        }
        
        .nav-tabs .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
        }
    </style>
</head>
<body>
<?php include 'admin-assets/sidebar.php'; ?>

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
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Tüm Ürünler</h5>
                <span class="badge bg-primary"><?php echo count($products); ?> ürün</span>
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
                                <th>Durum</th>
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
                                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                 class="product-image">
                                        <?php else: ?>
                                            <div class="product-image bg-light d-flex align-items-center justify-content-center text-muted">
                                                <i class="fas fa-image"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($product['name']); ?></div>
                                        <small class="text-muted"><?php echo mb_strimwidth(htmlspecialchars($product['description']), 0, 50, '...'); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['brand_name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                    <td><span class="fw-bold text-success">₺<?php echo number_format($product['price'], 2); ?></span></td>
                                    <td>
                                        <span class="badge bg-<?php echo $product['stock'] > 10 ? 'success' : ($product['stock'] > 0 ? 'warning' : 'danger'); ?>">
                                            <?php echo $product['stock']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $product['stock'] > 0 ? 'success' : 'secondary'; ?>">
                                            <i class="fas <?php echo $product['stock'] > 0 ? 'fa-eye' : 'fa-eye-slash'; ?> me-1"></i>
                                            <?php echo $product['stock'] > 0 ? 'Aktif' : 'Pasif'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
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
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-warning" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editProductModal"
                                                    onclick="loadEditData(<?php echo $product['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="?action=delete&id=<?php echo $product['id']; ?>&token=<?php echo $_SESSION['csrf_token']; ?>" 
                                               class="btn btn-danger" 
                                               onclick="return confirm('Bu ürünü silmek istediğinizden emin misiniz?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                            <a href="?action=toggle_status&id=<?php echo $product['id']; ?>&token=<?php echo $_SESSION['csrf_token']; ?>" 
                                               class="btn <?php echo $product['stock'] > 0 ? 'btn-success' : 'btn-secondary'; ?>"
                                               onclick="return confirm('Ürün durumunu değiştirmek istediğinizden emin misiniz?')">
                                                <i class="fas <?php echo $product['stock'] > 0 ? 'fa-eye' : 'fa-eye-slash'; ?>"></i>
                                            </a>
                                        </div>
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
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <form method="POST" action="products.php" enctype="multipart/form-data" id="addProductForm">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-plus-circle me-2"></i>Yeni Ürün Ekle
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <ul class="nav nav-tabs mb-4" id="addProductTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="basic-tab" data-bs-toggle="tab" data-bs-target="#basic" type="button" role="tab">
                                        <i class="fas fa-info-circle me-2"></i>Temel Bilgiler
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab">
                                        <i class="fas fa-align-left me-2"></i>Detaylar
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="media-tab" data-bs-toggle="tab" data-bs-target="#media" type="button" role="tab">
                                        <i class="fas fa-image me-2"></i>Görsel
                                    </button>
                                </li>
                            </ul>
                            
                            <div class="tab-content" id="addProductTabContent">
                                <!-- Temel Bilgiler Tab -->
                                <div class="tab-pane fade show active" id="basic" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Ürün Adı *</label>
                                                <input type="text" name="name" class="form-control" required maxlength="255">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Fiyat (₺) *</label>
                                                <input type="number" name="price" class="form-control" step="0.01" min="0" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Stok *</label>
                                                <input type="number" name="stock" class="form-control" min="0" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Marka *</label>
                                                <select name="brand_id" class="form-select" required>
                                                    <option value="">Seçiniz</option>
                                                    <?php foreach ($brands as $brand): ?>
                                                        <option value="<?php echo $brand['id']; ?>"><?php echo htmlspecialchars($brand['name']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Kategori *</label>
                                                <select name="category_id" class="form-select" required>
                                                    <option value="">Seçiniz</option>
                                                    <?php foreach ($categories as $category): ?>
                                                        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Kısa Açıklama *</label>
                                        <textarea name="description" class="form-control form-textarea" rows="3" placeholder="Ürünün kısa tanımı..." required maxlength="500"></textarea>
                                    </div>
                                </div>
                                
                                <!-- Detaylar Tab -->
                                <div class="tab-pane fade" id="details" role="tabpanel">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Detaylı Ürün Açıklaması</label>
                                        <textarea name="detailed_description" class="form-control form-textarea" rows="4" placeholder="Ürünün detaylı açıklaması..." maxlength="2000"></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Öne Çıkan Özellikler</label>
                                        <div id="features-container">
                                            <div class="feature-item">
                                                <div class="input-group">
                                                    <input type="text" name="features[]" class="form-control" placeholder="Özellik girin (örn: Su bazlı formül)" maxlength="255">
                                                    <button type="button" class="btn btn-outline-danger remove-feature" title="Sil">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-outline-primary btn-sm add-feature-btn">
                                            <i class="fas fa-plus me-1"></i>Yeni Özellik Ekle
                                        </button>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Kullanım Talimatları</label>
                                        <textarea name="usage_instructions" class="form-control form-textarea" rows="4" placeholder="Her adımı yeni satıra yazın..." maxlength="1000"></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Teknik Özellikler</label>
                                        <div class="table-responsive">
                                            <table class="table table-bordered spec-table">
                                                <thead>
                                                    <tr>
                                                        <th width="40%">Özellik</th>
                                                        <th width="40%">Değer</th>
                                                        <th width="20%">İşlem</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="specs-container">
                                                    <tr class="spec-row">
                                                        <td>
                                                            <input type="text" name="spec_keys[]" class="form-control" placeholder="Özellik adı (örn: Renk)" maxlength="100">
                                                        </td>
                                                        <td>
                                                            <input type="text" name="spec_values[]" class="form-control" placeholder="Değer (örn: Beyaz)" maxlength="100">
                                                        </td>
                                                        <td>
                                                            <button type="button" class="btn btn-outline-danger btn-sm remove-spec" title="Sil">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        <button type="button" class="btn btn-outline-primary btn-sm add-spec-btn">
                                            <i class="fas fa-plus me-1"></i>Yeni Özellik Ekle
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Görsel Tab -->
                                <div class="tab-pane fade" id="media" role="tabpanel">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Ürün Resmi</label>
                                        
                                        <div class="image-upload-area" id="imageUploadArea">
                                            <div id="imagePreviewContainer" class="image-preview-container" style="display: none;">
                                                <img id="imagePreview" class="image-preview" src="" alt="Resim önizleme">
                                                <button type="button" class="btn btn-sm btn-outline-danger mt-2" onclick="removeImage()">
                                                    <i class="fas fa-times me-1"></i>Resmi Kaldır
                                                </button>
                                            </div>
                                            
                                            <div id="uploadPlaceholder">
                                                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                                <p class="text-muted mb-1">Ürün resmi yüklemek için tıklayın veya sürükleyin</p>
                                                <small class="text-muted">JPG, PNG, WEBP, GIF (Max: 5MB)</small>
                                            </div>
                                            
                                            <input type="file" name="image" id="imageInput" accept="image/*" style="display: none;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Kaydet
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Ürün Düzenleme Modal -->
        <div class="modal fade" id="editProductModal" tabindex="-1">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <form method="POST" action="products.php" enctype="multipart/form-data" id="editProductForm">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="id" id="editProductId">
                        
                        <div class="modal-header bg-warning text-dark">
                            <h5 class="modal-title">
                                <i class="fas fa-edit me-2"></i>Ürün Düzenle
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div id="editProductContent">
                                <!-- Bu içerik JavaScript ile doldurulacak -->
                                <div class="text-center py-5">
                                    <div class="spinner-border text-warning" role="status">
                                        <span class="visually-hidden">Yükleniyor...</span>
                                    </div>
                                    <p class="mt-2 text-muted">Ürün bilgileri yükleniyor...</p>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save me-2"></i>Güncelle
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Global değişkenler
    let currentEditProduct = null;

    // Özellik ekleme fonksiyonu
    document.addEventListener('DOMContentLoaded', function() {
        initializeImageUpload();
        initializeFormHandlers();
    });

    function initializeImageUpload() {
        const imageInput = document.getElementById('imageInput');
        const imageUploadArea = document.getElementById('imageUploadArea');
        const imagePreview = document.getElementById('imagePreview');
        const imagePreviewContainer = document.getElementById('imagePreviewContainer');
        const uploadPlaceholder = document.getElementById('uploadPlaceholder');
        
        // Tıklama ile dosya seçme
        if (imageUploadArea) {
            imageUploadArea.addEventListener('click', function() {
                imageInput.click();
            });
        }
        
        // Dosya seçildiğinde
        if (imageInput) {
            imageInput.addEventListener('change', function(e) {
                handleImageSelection(this.files[0], imagePreview, imagePreviewContainer, uploadPlaceholder);
            });
        }
        
        // Drag and drop desteği
        if (imageUploadArea) {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                imageUploadArea.addEventListener(eventName, preventDefaults, false);
            });
            
            ['dragenter', 'dragover'].forEach(eventName => {
                imageUploadArea.addEventListener(eventName, highlight, false);
            });
            
            ['dragleave', 'drop'].forEach(eventName => {
                imageUploadArea.addEventListener(eventName, unhighlight, false);
            });
            
            imageUploadArea.addEventListener('drop', handleDrop, false);
        }
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        function highlight() {
            imageUploadArea.classList.add('dragover');
        }
        
        function unhighlight() {
            imageUploadArea.classList.remove('dragover');
        }
        
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            if (files.length) {
                handleImageSelection(files[0], imagePreview, imagePreviewContainer, uploadPlaceholder);
            }
        }
    }

    function handleImageSelection(file, previewElement, previewContainer, placeholder) {
        if (!file) return;
        
        // Dosya türü kontrolü
        if (!file.type.match('image.*')) {
            alert('Sadece resim dosyaları yükleyebilirsiniz!');
            return;
        }
        
        // Dosya boyutu kontrolü (5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert('Dosya boyutu 5MB\'dan küçük olmalıdır!');
            return;
        }
        
        const reader = new FileReader();
        
        reader.onload = function(e) {
            previewElement.src = e.target.result;
            placeholder.style.display = 'none';
            previewContainer.style.display = 'block';
        }
        
        reader.readAsDataURL(file);
    }

    function removeImage() {
        const imageInput = document.getElementById('imageInput');
        const imagePreviewContainer = document.getElementById('imagePreviewContainer');
        const uploadPlaceholder = document.getElementById('uploadPlaceholder');
        
        if (imageInput) imageInput.value = '';
        if (imagePreviewContainer) imagePreviewContainer.style.display = 'none';
        if (uploadPlaceholder) uploadPlaceholder.style.display = 'block';
    }

    function initializeFormHandlers() {
        // Özellik ekleme
        function addFeature(containerId) {
            const container = document.getElementById(containerId);
            const newFeature = document.createElement('div');
            newFeature.className = 'feature-item';
            newFeature.innerHTML = `
                <div class="input-group">
                    <input type="text" name="features[]" class="form-control" placeholder="Özellik girin" maxlength="255">
                    <button type="button" class="btn btn-outline-danger remove-feature" title="Sil">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            container.appendChild(newFeature);
            
            // Silme butonuna event listener ekle
            newFeature.querySelector('.remove-feature').addEventListener('click', function() {
                newFeature.remove();
            });
        }
        
        // Teknik özellik ekleme
        function addSpec(containerId) {
            const container = document.getElementById(containerId);
            const newRow = document.createElement('tr');
            newRow.className = 'spec-row';
            newRow.innerHTML = `
                <td>
                    <input type="text" name="spec_keys[]" class="form-control" placeholder="Özellik adı" maxlength="100">
                </td>
                <td>
                    <input type="text" name="spec_values[]" class="form-control" placeholder="Değer" maxlength="100">
                </td>
                <td>
                    <button type="button" class="btn btn-outline-danger btn-sm remove-spec" title="Sil">
                        <i class="fas fa-times"></i>
                    </button>
                </td>
            `;
            container.appendChild(newRow);
            
            // Silme butonuna event listener ekle
            newRow.querySelector('.remove-spec').addEventListener('click', function() {
                newRow.remove();
            });
        }
        
        // Ekleme butonlarına event listener ekle
        document.querySelectorAll('.add-feature-btn').forEach(button => {
            button.addEventListener('click', function() {
                const containerId = this.previousElementSibling.id;
                addFeature(containerId);
            });
        });
        
        document.querySelectorAll('.add-spec-btn').forEach(button => {
            button.addEventListener('click', function() {
                const containerId = this.previousElementSibling.querySelector('tbody').id;
                addSpec(containerId);
            });
        });
        
        // Mevcut silme butonlarına event listener ekle
        document.querySelectorAll('.remove-feature').forEach(button => {
            button.addEventListener('click', function() {
                this.closest('.feature-item').remove();
            });
        });
        
        document.querySelectorAll('.remove-spec').forEach(button => {
            button.addEventListener('click', function() {
                this.closest('.spec-row').remove();
            });
        });
    }

    // Ürün düzenleme modalını yükle - DÜZELTİLMİŞ VERSİYON
    function loadEditData(productId) {
        document.getElementById('editProductId').value = productId;
        
        // AJAX ile ürün verilerini al
        fetch(`products.php?ajax=get_product&id=${productId}`)
            .then(response => response.json())
            .then(product => {
                if (product.error) {
                    document.getElementById('editProductContent').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            ${product.error}
                        </div>
                    `;
                    return;
                }
                
                // Modal içeriğini oluştur
                const editContent = createEditFormContent(product);
                document.getElementById('editProductContent').innerHTML = editContent;
                
                // Edit modal için event listener'ları tekrar başlat
                initializeFormHandlers();
                
                // Mevcut resmi göster
                if (product.image) {
                    const editImagePreview = document.getElementById('editImagePreview');
                    const editImagePreviewContainer = document.getElementById('editImagePreviewContainer');
                    const editUploadPlaceholder = document.getElementById('editUploadPlaceholder');
                    
                    if (editImagePreview && editImagePreviewContainer && editUploadPlaceholder) {
                        editImagePreview.src = '../' + product.image;
                        editUploadPlaceholder.style.display = 'none';
                        editImagePreviewContainer.style.display = 'block';
                    }
                }
            })
            .catch(error => {
                console.error('Error loading product data:', error);
                document.getElementById('editProductContent').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Ürün bilgileri yüklenirken hata oluştu.
                    </div>
                `;
            });
    }

    function createEditFormContent(product) {
        // Marka seçenekleri
        let brandOptions = '';
        <?php foreach ($brands as $brand): ?>
            brandOptions += `<option value="<?php echo $brand['id']; ?>" ${product.brand_id == <?php echo $brand['id']; ?> ? 'selected' : ''}><?php echo htmlspecialchars($brand['name']); ?></option>`;
        <?php endforeach; ?>
        
        // Kategori seçenekleri
        let categoryOptions = '';
        <?php foreach ($categories as $category): ?>
            categoryOptions += `<option value="<?php echo $category['id']; ?>" ${product.category_id == <?php echo $category['id']; ?> ? 'selected' : ''}><?php echo htmlspecialchars($category['name']); ?></option>`;
        <?php endforeach; ?>
        
        // Features alanı
        let featuresHtml = '';
        if (product.features_array && product.features_array.length > 0) {
            product.features_array.forEach(feature => {
                featuresHtml += `
                    <div class="feature-item">
                        <div class="input-group">
                            <input type="text" name="features[]" class="form-control" value="${feature}" placeholder="Özellik girin" maxlength="255">
                            <button type="button" class="btn btn-outline-danger remove-feature" title="Sil">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                `;
            });
        } else {
            featuresHtml = `
                <div class="feature-item">
                    <div class="input-group">
                        <input type="text" name="features[]" class="form-control" placeholder="Özellik girin" maxlength="255">
                        <button type="button" class="btn btn-outline-danger remove-feature" title="Sil">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `;
        }
        
        // Specifications alanı
        let specsHtml = '';
        if (product.specs_array && product.specs_array.length > 0) {
            product.specs_array.forEach(spec => {
                specsHtml += `
                    <tr class="spec-row">
                        <td>
                            <input type="text" name="spec_keys[]" class="form-control" value="${spec.key}" placeholder="Özellik adı" maxlength="100">
                        </td>
                        <td>
                            <input type="text" name="spec_values[]" class="form-control" value="${spec.value}" placeholder="Değer" maxlength="100">
                        </td>
                        <td>
                            <button type="button" class="btn btn-outline-danger btn-sm remove-spec" title="Sil">
                                <i class="fas fa-times"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
        } else {
            specsHtml = `
                <tr class="spec-row">
                    <td>
                        <input type="text" name="spec_keys[]" class="form-control" placeholder="Özellik adı" maxlength="100">
                    </td>
                    <td>
                        <input type="text" name="spec_values[]" class="form-control" placeholder="Değer" maxlength="100">
                    </td>
                    <td>
                        <button type="button" class="btn btn-outline-danger btn-sm remove-spec" title="Sil">
                            <i class="fas fa-times"></i>
                        </button>
                    </td>
                </tr>
            `;
        }
        
        return `
            <ul class="nav nav-tabs mb-4" id="editProductTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="edit-basic-tab" data-bs-toggle="tab" data-bs-target="#edit-basic" type="button" role="tab">
                        <i class="fas fa-info-circle me-2"></i>Temel Bilgiler
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="edit-details-tab" data-bs-toggle="tab" data-bs-target="#edit-details" type="button" role="tab">
                        <i class="fas fa-align-left me-2"></i>Detaylar
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="edit-media-tab" data-bs-toggle="tab" data-bs-target="#edit-media" type="button" role="tab">
                        <i class="fas fa-image me-2"></i>Görsel
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="editProductTabContent">
                <!-- Temel Bilgiler Tab -->
                <div class="tab-pane fade show active" id="edit-basic" role="tabpanel">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Ürün Adı *</label>
                                <input type="text" name="name" class="form-control" value="${product.name}" required maxlength="255">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Fiyat (₺) *</label>
                                <input type="number" name="price" class="form-control" value="${product.price}" step="0.01" min="0" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Stok *</label>
                                <input type="number" name="stock" class="form-control" value="${product.stock}" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Marka *</label>
                                <select name="brand_id" class="form-select" required>
                                    <option value="">Seçiniz</option>
                                    ${brandOptions}
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Kategori *</label>
                                <select name="category_id" class="form-select" required>
                                    <option value="">Seçiniz</option>
                                    ${categoryOptions}
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Kısa Açıklama *</label>
                        <textarea name="description" class="form-control form-textarea" rows="3" placeholder="Ürünün kısa tanımı..." required maxlength="500">${product.description || ''}</textarea>
                    </div>
                </div>
                
                <!-- Detaylar Tab -->
                <div class="tab-pane fade" id="edit-details" role="tabpanel">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Detaylı Ürün Açıklaması</label>
                        <textarea name="detailed_description" class="form-control form-textarea" rows="4" placeholder="Ürünün detaylı açıklaması..." maxlength="2000">${product.detailed_description || ''}</textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Öne Çıkan Özellikler</label>
                        <div id="edit-features-container">
                            ${featuresHtml}
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm add-feature-btn">
                            <i class="fas fa-plus me-1"></i>Yeni Özellik Ekle
                        </button>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Kullanım Talimatları</label>
                        <textarea name="usage_instructions" class="form-control form-textarea" rows="4" placeholder="Her adımı yeni satıra yazın..." maxlength="1000">${product.usage_instructions || ''}</textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Teknik Özellikler</label>
                        <div class="table-responsive">
                            <table class="table table-bordered spec-table">
                                <thead>
                                    <tr>
                                        <th width="40%">Özellik</th>
                                        <th width="40%">Değer</th>
                                        <th width="20%">İşlem</th>
                                    </tr>
                                </thead>
                                <tbody id="edit-specs-container">
                                    ${specsHtml}
                                </tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm add-spec-btn">
                            <i class="fas fa-plus me-1"></i>Yeni Özellik Ekle
                        </button>
                    </div>
                </div>
                
                <!-- Görsel Tab -->
                <div class="tab-pane fade" id="edit-media" role="tabpanel">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Ürün Resmi</label>
                        
                        <div class="image-upload-area" id="editImageUploadArea">
                            <div id="editImagePreviewContainer" class="image-preview-container" style="display: none;">
                                <img id="editImagePreview" class="image-preview" src="" alt="Resim önizleme">
                                <button type="button" class="btn btn-sm btn-outline-danger mt-2" onclick="removeEditImage()">
                                    <i class="fas fa-times me-1"></i>Resmi Kaldır
                                </button>
                            </div>
                            
                            <div id="editUploadPlaceholder">
                                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                <p class="text-muted mb-1">Ürün resmi yüklemek için tıklayın veya sürükleyin</p>
                                <small class="text-muted">JPG, PNG, WEBP, GIF (Max: 5MB)</small>
                            </div>
                            
                            <input type="file" name="image" id="editImageInput" accept="image/*" style="display: none;">
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function removeEditImage() {
        const imageInput = document.getElementById('editImageInput');
        const imagePreviewContainer = document.getElementById('editImagePreviewContainer');
        const uploadPlaceholder = document.getElementById('editUploadPlaceholder');
        
        if (imageInput) imageInput.value = '';
        if (imagePreviewContainer) imagePreviewContainer.style.display = 'none';
        if (uploadPlaceholder) uploadPlaceholder.style.display = 'block';
    }

    // Modal açıldığında temizle
    const addModal = document.getElementById('addProductModal');
    if (addModal) {
        addModal.addEventListener('show.bs.modal', function() {
            // Formu temizle
            const form = document.getElementById('addProductForm');
            if (form) form.reset();
            
            // Özellikleri ve spec'leri sıfırla
            const featuresContainer = document.getElementById('features-container');
            const specsContainer = document.getElementById('specs-container');
            
            if (featuresContainer && featuresContainer.children.length > 1) {
                featuresContainer.innerHTML = featuresContainer.children[0].outerHTML;
            }
            
            if (specsContainer && specsContainer.children.length > 1) {
                specsContainer.innerHTML = specsContainer.children[0].outerHTML;
            }
            
            // Resmi temizle
            removeImage();
            
            // İlk tab'a dön
            const firstTab = document.querySelector('#addProductTabs .nav-link');
            if (firstTab) firstTab.click();
        });
    }

    // Form gönderiminden önce kontrol
    document.addEventListener('submit', function(e) {
        if (e.target.id === 'addProductForm' || e.target.id === 'editProductForm') {
            const nameInput = e.target.querySelector('input[name="name"]');
            
            if (nameInput && nameInput.value.trim() === '') {
                e.preventDefault();
                alert('Ürün adı gereklidir!');
                nameInput.focus();
                return false;
            }
        }
        return true;
    });
    </script>
</body>
</html>