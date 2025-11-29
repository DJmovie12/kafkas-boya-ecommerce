<?php
require_once '../includes/db_connect.php';
require_once '../includes/session.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Sipariş bilgilerini çek
$stmt = $conn->prepare("
    SELECT o.*, u.username, u.email 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();
$stmt->close();

if (!$order) {
    $_SESSION['error'] = 'Sipariş bulunamadı!';
    header('Location: orders.php');
    exit();
}

// Sipariş kalemlerini çek
$stmt = $conn->prepare("
    SELECT oi.*, p.name, p.image, b.name as brand_name 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    LEFT JOIN brands b ON p.brand_id = b.id 
    WHERE oi.order_id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order_items = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sipariş Detayları - Admin Paneli</title>
    
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
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
    </style>
</head>
<body>
<?php include 'admin-assets/sidebar.php'; ?>

    <!-- Top Navbar -->
    <div class="top-navbar">
        <h4 class="mb-0 fw-bold">Sipariş Detayları</h4>
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
            <h1 class="h2">Sipariş Detayları - #<?php echo $order['id']; ?></h1>
            <div>
                <a href="orders.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Geri Dön
                </a>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-user me-2"></i>Müşteri Bilgileri</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Kullanıcı Adı:</strong> <?php echo htmlspecialchars($order['username']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
                        <p><strong>Sipariş Tarihi:</strong> <?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-receipt me-2"></i>Sipariş Bilgileri</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Sipariş No:</strong> #<?php echo $order['id']; ?></p>
                        <p><strong>Durum:</strong> 
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
                                'pending' => 'Beklemede',
                                'processing' => 'İşleniyor',
                                'shipped' => 'Kargoda',
                                'delivered' => 'Teslim Edildi',
                                'cancelled' => 'İptal'
                            ];
                            ?>
                            <span class="badge rounded-pill <?php echo $statusClass; ?>">
                                <?php echo $statusLabels[$order['status']] ?? $order['STATUS']; ?>
                            </span>
                        </p>
                        <p><strong>Toplam Tutar:</strong> <span class="text-success fs-4">₺<?php echo number_format($order['total_amount'], 2); ?></span></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-boxes me-2"></i>Sipariş Kalemleri</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Ürün</th>
                                <th>Marka</th>
                                <th>Birim Fiyat</th>
                                <th>Miktar</th>
                                <th>Toplam</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order_items as $item): ?>
                                <tr>
                                    <td>
                                        <?php if ($item['image']): ?>
                                            <img src="../<?php echo htmlspecialchars($item['image']); ?>" 
                                                 alt="" style="width: 50px; height: 50px; object-fit: cover;" class="rounded me-2">
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($item['name']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['brand_name']); ?></td>
                                    <td>₺<?php echo number_format($item['price'], 2); ?></td>
                                    <td><?php echo $item['quantity']; ?> adet</td>
                                    <td>₺<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-end"><strong>Genel Toplam:</strong></td>
                                <td><strong class="text-success">₺<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        <div class="d-flex gap-2 mt-4">
    <a href="orders.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-2"></i>Siparişlere Dön
    </a>
    <a href="generate-invoice.php?order_id=<?php echo $order_id; ?>" class="btn btn-success" target="_blank">
        <i class="fas fa-print me-2"></i>Fatura Yazdır
    </a>
</div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>