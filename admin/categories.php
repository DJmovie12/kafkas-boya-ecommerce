<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/session.php';
requireAdmin();

$page_title = "Kategori Yönetimi";
$message = '';

// Kategori Ekle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['name']);
    $desc = trim($_POST['description']);
    
    if (!empty($name)) {
        try {
            $stmt = $conn->prepare("INSERT INTO categories (NAME, description) VALUES (?, ?)");
            $stmt->bind_param("ss", $name, $desc);
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success alert-dismissible fade show">Kategori başarıyla eklendi.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            }
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $errorMsg = (strpos($e->getMessage(), 'Duplicate entry') !== false) ? 'Bu kategori adı zaten mevcut.' : 'Hata oluştu.';
            $message = '<div class="alert alert-danger alert-dismissible fade show">' . $errorMsg . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        }
    } else {
        $message = '<div class="alert alert-warning alert-dismissible fade show">Kategori adı boş olamaz.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
}

// Kategori Sil
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if ($conn->query("DELETE FROM categories WHERE id = $id")) {
        header("Location: categories.php?msg=deleted");
        exit();
    }
}

if (isset($_GET['msg']) && $_GET['msg'] == 'deleted') {
    $message = '<div class="alert alert-success alert-dismissible fade show">Kategori silindi.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
}

$categories = $conn->query("SELECT * FROM categories ORDER BY id ASC");
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
        
        .main-content { margin-left: 250px; padding: 30px; }
        
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
        
        .card { border: none; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .card-header { background: white; border-bottom: 1px solid #e9ecef; padding: 20px; border-radius: 15px 15px 0 0; }
        
        .form-control:focus { border-color: #667eea; box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25); }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; }
        .btn-primary:hover { background: linear-gradient(135deg, #5a6fd6 0%, #6c4596 100%); transform: translateY(-2px); }
        
        .table-hover tbody tr:hover { background-color: #f8f9fa; }
    </style>
</head>
<body>

<?php include 'admin-assets/sidebar.php'; ?>

    <!-- Top Navbar -->
    <div class="top-navbar">
        <h4 class="mb-0 fw-bold">Kategori Yönetimi</h4>
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
                        <h5 class="fw-bold mb-0 text-primary">Yeni Kategori Ekle</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-medium">Kategori Adı</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-tag text-muted"></i></span>
                                    <input type="text" name="name" class="form-control" required placeholder="Örn: İç Cephe">
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-medium">Açıklama</label>
                                <textarea name="description" class="form-control" rows="4" placeholder="Kategori hakkında kısa bilgi..."></textarea>
                            </div>
                            <button type="submit" name="add_category" class="btn btn-primary w-100 py-2">
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
                        <h5 class="fw-bold mb-0">Mevcut Kategoriler</h5>
                        <span class="badge bg-primary rounded-pill"><?php echo $categories->num_rows; ?> Kayıt</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4" style="width: 60px;">ID</th>
                                        <th>Kategori Adı</th>
                                        <th>Açıklama</th>
                                        <th class="text-end pe-4" style="width: 100px;">İşlem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($categories->num_rows > 0): ?>
                                        <?php while($cat = $categories->fetch_assoc()): ?>
                                        <tr>
                                            <td class="ps-4 fw-bold text-muted">#<?php echo $cat['id']; ?></td>
                                            <td>
                                                <span class="fw-bold text-dark"><?php echo htmlspecialchars($cat['name']); ?></span>
                                            </td>
                                            <td class="text-muted small"><?php echo htmlspecialchars($cat['description']); ?></td>
                                            <td class="text-end pe-4">
                                                <a href="?delete=<?php echo $cat['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bu kategoriyi silmek istediğinize emin misiniz?')" title="Sil">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="4" class="text-center py-5 text-muted">Henüz eklenmiş bir kategori yok.</td></tr>
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