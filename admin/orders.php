<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/session.php';
requireAdmin();

$page_title = "Sipariş Yönetimi";
$message = '';

// Durum Güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE orders SET STATUS = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $order_id);
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success alert-dismissible fade show">Sipariş durumu güncellendi.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    } else {
        $message = '<div class="alert alert-danger alert-dismissible fade show">Hata oluştu.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
    $stmt->close();
}

// Siparişleri Çek
$sql = "SELECT o.*, u.username, u.email, 
        (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        ORDER BY o.created_at DESC";
$orders = $conn->query($sql);
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
            padding: 12px 20px;
            border-left: 3px solid transparent;
            transition: all 0.3s ease;
            margin-bottom: 5px;
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
        
        .status-badge { font-size: 0.85rem; padding: 0.5em 0.8em; }
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
            <a href="orders.php" class="nav-link active"><i class="fas fa-receipt me-2" style="width:20px"></i> Siparişler</a>
            <a href="users.php" class="nav-link"><i class="fas fa-users me-2" style="width:20px"></i> Kullanıcılar</a>
            <hr class="bg-white-50 mx-3">
            <a href="/logout.php" class="nav-link"><i class="fas fa-sign-out-alt me-2" style="width:20px"></i> Çıkış Yap</a>
        </nav>
    </div>

    <!-- Top Navbar -->
    <div class="top-navbar">
        <h4 class="mb-0 fw-bold">Sipariş Yönetimi</h4>
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

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">Tüm Siparişler</h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-secondary"><i class="fas fa-filter me-1"></i> Filtrele</button>
                    <button class="btn btn-sm btn-outline-secondary"><i class="fas fa-download me-1"></i> Dışa Aktar</button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">ID</th>
                                <th>Müşteri</th>
                                <th>Tutar</th>
                                <th>Ürün</th>
                                <th>Tarih</th>
                                <th>Durum</th>
                                <th class="text-end pe-4">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($orders->num_rows > 0): ?>
                                <?php while($order = $orders->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4"><strong>#<?php echo $order['id']; ?></strong></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($order['username']); ?></div>
                                        <div class="text-muted small"><?php echo htmlspecialchars($order['email']); ?></div>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-primary">₺<?php echo number_format($order['total_amount'], 2, ',', '.'); ?></span>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?php echo $order['item_count']; ?> Adet</span></td>
                                    <td>
                                        <div class="text-dark"><?php echo date('d.m.Y', strtotime($order['created_at'])); ?></div>
                                        <div class="text-muted small"><?php echo date('H:i', strtotime($order['created_at'])); ?></div>
                                    </td>
                                    <td>
                                        <?php 
                                        $statusClass = match($order['STATUS']) {
                                            'pending' => 'bg-warning text-dark',
                                            'processing' => 'bg-info text-white',
                                            'shipped' => 'bg-primary',
                                            'delivered' => 'bg-success',
                                            'cancelled' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                        
                                        $statusLabels = [
                                            'pending' => 'Bekliyor',
                                            'processing' => 'Hazırlanıyor',
                                            'shipped' => 'Kargolandı',
                                            'delivered' => 'Teslim Edildi',
                                            'cancelled' => 'İptal Edildi'
                                        ];
                                        ?>
                                        <span class="badge rounded-pill status-badge <?php echo $statusClass; ?>">
                                            <?php echo $statusLabels[$order['STATUS']] ?? $order['STATUS']; ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <form method="POST" class="d-flex justify-content-end align-items-center gap-2">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <select name="status" class="form-select form-select-sm" style="width: 130px; border-radius: 20px;">
                                                <?php foreach ($statusLabels as $key => $label): ?>
                                                    <option value="<?php echo $key; ?>" <?php echo $order['STATUS'] == $key ? 'selected' : ''; ?>>
                                                        <?php echo $label; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" name="update_status" class="btn btn-sm btn-primary rounded-circle" title="Kaydet" style="width: 32px; height: 32px; padding: 0;">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="7" class="text-center py-5 text-muted">Henüz sipariş bulunmuyor.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>