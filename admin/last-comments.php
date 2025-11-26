<?php
require_once '../includes/db_connect.php';
require_once '../includes/session.php';

// Admin kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Yorumları al
$reviews_result = $conn->query("
    SELECT r.*, u.username, p.name as product_name, p.image as product_image
    FROM reviews r 
    LEFT JOIN users u ON r.user_id = u.id 
    LEFT JOIN products p ON r.product_id = p.id 
    ORDER BY r.created_at DESC
");
$reviews = $reviews_result->fetch_all(MYSQLI_ASSOC);
$reviews_result->free();

// Yorum silme işlemi
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    $stmt = $conn->prepare("DELETE FROM reviews WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    
    $_SESSION['success'] = 'Yorum başarıyla silindi!';
    header('Location: last-comments.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Son Yorumlar - Admin Paneli</title>
    
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
        
        .review-item {
            border-bottom: 1px solid #e9ecef;
            padding: 20px 0;
        }
        
        .review-item:last-child {
            border-bottom: none;
        }
        
        .star-rating {
            color: #ffc107;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #5a6fd6 0%, #6c4596 100%);
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
            <a href="brands.php" class="nav-link"><i class="fas fa-tag me-2" style="width:20px"></i> Markalar</a>
            <a href="orders.php" class="nav-link"><i class="fas fa-receipt me-2" style="width:20px"></i> Siparişler</a>
            <a href="users.php" class="nav-link"><i class="fas fa-users me-2" style="width:20px"></i> Kullanıcılar</a>
            <a href="last-comments.php" class="nav-link active"><i class="fas fa-comments me-2" style="width:20px"></i> Son Yorumlar</a>
            <hr class="bg-white-50 mx-3">
            <a href="/logout.php" class="nav-link"><i class="fas fa-sign-out-alt me-2" style="width:20px"></i> Çıkış Yap</a>
        </nav>
    </div>

    <!-- Top Navbar -->
    <div class="top-navbar">
        <h4 class="mb-0 fw-bold">Son Yorumlar</h4>
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
            <h1 class="h2">Müşteri Yorumları</h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <span class="badge bg-primary fs-6">Toplam <?php echo count($reviews); ?> yorum</span>
            </div>
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
                <h5 class="mb-0">Tüm Yorumlar</h5>
            </div>
            <div class="card-body">
                <?php if (count($reviews) > 0): ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-item">
                            <div class="row">
                                <div class="col-md-1">
                                    <?php if ($review['product_image']): ?>
                                        <img src="../<?php echo htmlspecialchars($review['product_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($review['product_name']); ?>" 
                                             class="img-fluid rounded" style="width: 60px; height: 60px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                            <i class="fas fa-box text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-8">
                                    <div class="d-flex align-items-center mb-2">
                                        <strong class="me-3"><?php echo htmlspecialchars($review['username']); ?></strong>
                                        <div class="star-rating me-3">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <small class="text-muted"><?php echo date('d.m.Y H:i', strtotime($review['created_at'])); ?></small>
                                    </div>
                                    <h6 class="text-primary mb-2"><?php echo htmlspecialchars($review['product_name']); ?></h6>
                                    <p class="mb-2"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                </div>
                                <div class="col-md-3 text-end">
                                    <a href="?action=delete&id=<?php echo $review['id']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Bu yorumu silmek istediğinizden emin misiniz?')">
                                        <i class="fas fa-trash me-1"></i>Sil
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                        <h4>Henüz Yorum Yok</h4>
                        <p class="text-muted">Müşteriler henüz hiç yorum yapmamış.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>