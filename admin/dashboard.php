<?php
require_once '../includes/db_connect.php';
require_once '../includes/session.php';

// Admin kontrolü
requireAdmin();

// İstatistikleri al
$total_orders = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
$total_products = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
$total_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'")->fetch_assoc()['count'];
$total_revenue = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE status = 'delivered'")->fetch_assoc()['total'] ?? 0;

// Son siparişleri al
$recent_orders = $conn->query("SELECT o.*, u.username, u.email FROM orders o 
                               JOIN users u ON o.user_id = u.id 
                               ORDER BY o.created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// En çok satılan ürünleri al
$top_products = $conn->query("SELECT p.id, p.name, SUM(oi.quantity) as total_sold 
                              FROM order_items oi 
                              JOIN products p ON oi.product_id = p.id 
                              GROUP BY p.id 
                              ORDER BY total_sold DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Admin Paneli</title>
    
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
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
        }
        
        .stat-card .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
            <a href="dashboard.php" class="nav-link active"><i class="fas fa-chart-line me-2" style="width:20px"></i> Dashboard</a>
            <a href="products.php" class="nav-link"><i class="fas fa-boxes me-2" style="width:20px"></i> Ürünler</a>
            <a href="categories.php" class="nav-link"><i class="fas fa-list me-2" style="width:20px"></i> Kategoriler</a>
            <a href="brands.php" class="nav-link"><i class="fas fa-tag me-2" style="width:20px"></i> Markalar</a>
            <a href="orders.php" class="nav-link"><i class="fas fa-receipt me-2" style="width:20px"></i> Siparişler</a>
            <a href="users.php" class="nav-link"><i class="fas fa-users me-2" style="width:20px"></i> Kullanıcılar</a>
            <hr class="bg-white-50 mx-3">
            <a href="/logout.php" class="nav-link"><i class="fas fa-sign-out-alt me-2" style="width:20px"></i> Çıkış Yap</a>
        </nav>
    </div>

    <!-- Top Navbar -->
    <div class="top-navbar">
        <h4 class="mb-0 fw-bold">Dashboard</h4>
        <div class="d-flex align-items-center gap-3">
            <span class="text-muted"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                <i class="fas fa-user"></i>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Statistics Row -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $total_orders; ?></div>
                    <div class="stat-label">Toplam Sipariş</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $total_products; ?></div>
                    <div class="stat-label">Toplam Ürün</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $total_users; ?></div>
                    <div class="stat-label">Toplam Kullanıcı</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-value">₺<?php echo number_format($total_revenue, 0, ',', '.'); ?></div>
                    <div class="stat-label">Toplam Gelir</div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Recent Orders -->
            <div class="col-md-8">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0">Son Siparişler</h5>
                        <a href="/admin/orders.php" class="btn btn-sm btn-outline-primary">Tümünü Gör</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Sipariş No</th>
                                        <th>Müşteri</th>
                                        <th>Tutar</th>
                                        <th>Durum</th>
                                        <th>Tarih</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($recent_orders) > 0): ?>
                                        <?php foreach ($recent_orders as $order): ?>
                                            <tr>
                                                <td class="ps-4"><strong>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></strong></td>
                                                <td>
                                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($order['username']); ?></div>
                                                    <div class="text-muted small"><?php echo htmlspecialchars($order['email']); ?></div>
                                                </td>
                                                <td><span class="fw-bold text-primary">₺<?php echo number_format($order['total_amount'], 2, ',', '.'); ?></span></td>
                                                <td>
                                                    <?php 
                                                    $statusClass = match($order['status']) {
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
                                                    <span class="badge rounded-pill <?php echo $statusClass; ?>">
                                                        <?php echo $statusLabels[$order['status']] ?? $order['status']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="text-dark"><?php echo date('d.m.Y', strtotime($order['created_at'])); ?></div>
                                                    <div class="text-muted small"><?php echo date('H:i', strtotime($order['created_at'])); ?></div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="5" class="text-center py-5 text-muted">Henüz sipariş bulunmuyor.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Products -->
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="fw-bold mb-0">En Çok Satılan Ürünler</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Ürün Adı</th>
                                        <th class="text-end pe-4">Satış Miktarı</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($top_products) > 0): ?>
                                        <?php foreach ($top_products as $product): ?>
                                            <tr>
                                                <td class="ps-4"><strong><?php echo htmlspecialchars($product['name']); ?></strong></td>
                                                <td class="text-end pe-4"><span class="badge bg-primary"><?php echo $product['total_sold']; ?> adet</span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="2" class="text-center py-5 text-muted">Henüz satış verisi bulunmuyor.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>