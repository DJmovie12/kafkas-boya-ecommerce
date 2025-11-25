<?php
require_once '../includes/db_connect.php';
require_once '../includes/session.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Sipariş bilgilerini çek
$stmt = $pdo->prepare("
    SELECT o.*, u.username, u.email 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    $_SESSION['error'] = 'Sipariş bulunamadı!';
    header('Location: orders.php');
    exit();
}

// Sipariş kalemlerini çek
$stmt = $pdo->prepare("
    SELECT oi.*, p.name, p.image, b.name as brand_name 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    LEFT JOIN brands b ON p.brand_id = b.id 
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll();

include 'includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Sipariş Detayları - #<?php echo $order['id']; ?></h1>
                <div>
                    <a href="orders.php" class="btn btn-secondary">Geri Dön</a>
                    <a href="invoice.php?id=<?php echo $order['id']; ?>" target="_blank" class="btn btn-success">Fatura Yazdır</a>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>Müşteri Bilgileri</h5>
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
                            <h5>Sipariş Bilgileri</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>Sipariş No:</strong> #<?php echo $order['id']; ?></p>
                            <p><strong>Durum:</strong> 
                                <span class="badge bg-<?php 
                                    echo match($order['status']) {
                                        'pending' => 'warning',
                                        'processing' => 'info',
                                        'shipped' => 'primary',
                                        'delivered' => 'success',
                                        'cancelled' => 'danger',
                                        default => 'secondary'
                                    };
                                ?>">
                                    <?php 
                                        echo match($order['status']) {
                                            'pending' => 'Beklemede',
                                            'processing' => 'İşleniyor',
                                            'shipped' => 'Kargoda',
                                            'delivered' => 'Teslim Edildi',
                                            'cancelled' => 'İptal',
                                            default => 'Bilinmiyor'
                                        };
                                    ?>
                                </span>
                            </p>
                            <p><strong>Toplam Tutar:</strong> <span class="text-success fs-4">₺<?php echo number_format($order['total_amount'], 2); ?></span></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5>Sipariş Kalemleri</h5>
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
                                            <img src="../<?php echo htmlspecialchars($item['image']); ?>" 
                                                 alt="" style="width: 50px; height: 50px; object-fit: cover;" class="me-2">
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
                                    <td><strong>₺<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>