<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/session.php';
requireAdmin();

$page_title = "Marka Yönetimi";
$message = '';

// Marka Ekle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_brand'])) {
    $name = trim($_POST['name']);
    $desc = trim($_POST['description']);
    $logo = trim($_POST['logo_url']);
    
    if (!empty($name)) {
        try {
            $stmt = $conn->prepare("INSERT INTO brands (NAME, description, logo_url) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $desc, $logo);
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success alert-dismissible fade show">Marka başarıyla eklendi.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            }
            $stmt->close();
        } catch (Exception $e) {
            $message = '<div class="alert alert-danger alert-dismissible fade show">Marka eklenirken bir hata oluştu.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        }
    }
}

// Marka Sil
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM brands WHERE id = $id");
    header("Location: brands.php?msg=deleted");
    exit();
}

if (isset($_GET['msg']) && $_GET['msg'] == 'deleted') {
    $message = '<div class="alert alert-success alert-dismissible fade show">Marka silindi.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
}

$brands = $conn->query("SELECT * FROM brands ORDER BY id ASC");
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?> - Admin Paneli</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; }
        
        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            position: fixed;
            left: 0; top: 0; width: 250px; z-index: 1000; padding-top: 20px;
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px; border-left: 3px solid transparent; transition: all 0.3s ease; margin-bottom: 5px;
        }
        
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: white; background-color: rgba(255,255,255,0.1); border-left-color: white;
        }
        
        .main-content { margin-left: 250px; padding: 30px; }
        
        .top-navbar {
            background: white; padding: 15px 30px; border-bottom: 1px solid #e9ecef;
            display: flex; justify-content: space-between; align-items: center;
            margin-left: 250px; position: sticky; top: 0; z-index: 999;
        }
        
        .card { border: none; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .card-header { background: white; border-bottom: 1px solid #e9ecef; padding: 20px; border-radius: 15px 15px 0 0; }
        
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; }
        .btn-primary:hover { background: linear-gradient(135deg, #5a6fd6 0%, #6c4596 100%); }
        
        .brand-logo-preview { 
            width: 50px; height: 50px; object-fit: contain; 
            background: white; border: 1px solid #e9ecef; padding: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
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
            <a href="products.php" class="nav-link"><i class="fas fa-boxes me-2" style="width:20px"></i> Ürünler</a>
            <a href="categories.php" class="nav-link"><i class="fas fa-list me-2" style="width:20px"></i> Kategoriler</a>
            <a href="brands.php" class="nav-link active"><i class="fas fa-tag me-2" style="width:20px"></i> Markalar</a>
            <a href="orders.php" class="nav-link"><i class="fas fa-receipt me-2" style="width:20px"></i> Siparişler</a>
            <a href="users.php" class="nav-link"><i class="fas fa-users me-2" style="width:20px"></i> Kullanıcılar</a>
            <hr class="bg-white-50 mx-3">
            <a href="/logout.php" class="nav-link"><i class="fas fa-sign-out-alt me-2" style="width:20px"></i> Çıkış Yap</a>
        </nav>
    </div>

    <!-- Top Navbar -->
    <div class="top-navbar">
        <h4 class="mb-0 fw-bold">Marka Yönetimi</h4>
        <div class="d-flex align-items-center gap-3">
            <span class="text-muted"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                <i class="fas fa-user"></i>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <?php echo $message; ?>

        <div class="row g-4">
            <!-- Ekleme Formu -->
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="fw-bold mb-0 text-primary">Yeni Marka Ekle</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-medium">Marka Adı</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-tag text-muted"></i></span>
                                    <input type="text" name="name" class="form-control" required placeholder="Örn: Marshall">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-medium">Logo URL</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-image text-muted"></i></span>
                                    <input type="text" name="logo_url" class="form-control" placeholder="assets/img/brand.png">
                                </div>
                                <div class="form-text">Resim dosya yolunu giriniz.</div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-medium">Açıklama</label>
                                <textarea name="description" class="form-control" rows="3" placeholder="Marka hakkında kısa bilgi..."></textarea>
                            </div>
                            <button type="submit" name="add_brand" class="btn btn-primary w-100 py-2">
                                <i class="fas fa-plus-circle me-2"></i> Kaydet
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Liste -->
            <div class="col-md-8">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0">Mevcut Markalar</h5>
                        <span class="badge bg-primary rounded-pill"><?php echo $brands->num_rows; ?> Kayıt</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4" style="width: 80px;">Logo</th>
                                        <th>Marka Adı</th>
                                        <th>Açıklama</th>
                                        <th class="text-end pe-4" style="width: 100px;">İşlem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($brands->num_rows > 0): ?>
                                        <?php while($brand = $brands->fetch_assoc()): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <?php if(!empty($brand['logo_url'])): ?>
                                                    <img src="/<?php echo htmlspecialchars($brand['logo_url']); ?>" class="brand-logo-preview rounded-circle" onerror="this.src='/assets/img/placeholder.jpg'">
                                                <?php else: ?>
                                                    <div class="brand-logo-preview rounded-circle d-flex align-items-center justify-content-center bg-light text-muted">
                                                        <i class="fas fa-image"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td><span class="fw-bold text-dark"><?php echo htmlspecialchars($brand['name']); ?></span></td>
                                            <td class="text-muted small"><?php echo htmlspecialchars($brand['description']); ?></td>
                                            <td class="text-end pe-4">
                                                <a href="?delete=<?php echo $brand['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bu markayı silmek istediğinize emin misiniz?')" title="Sil">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="4" class="text-center py-5 text-muted">Henüz eklenmiş bir marka yok.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>