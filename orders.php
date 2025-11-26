<?php
// 1. Mantık ve Kontroller
require_once 'includes/db_connect.php';
require_once 'includes/session.php';

$page_title = "Siparişlerim";

// Giriş yapmamışsa yönlendir
if (!isUserLoggedIn()) {
    header("Location: /login.php?redirect=orders.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Kullanıcının siparişlerini çek
// Her sipariş için toplam ürün sayısını da alt sorgu ile alıyoruz
$sql = "SELECT o.*, 
        (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count 
        FROM orders o 
        WHERE o.user_id = ? 
        ORDER BY o.created_at DESC";

$orders = [];
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    $stmt->close();
}

// 2. Header Dahil Et
require_once 'includes/header.php';
?>

    <!-- Page Header -->
    <section class="page-header bg-light py-5" style="margin-top: 70px;">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-2">
                            <li class="breadcrumb-item"><a href="/index.php" class="text-decoration-none text-primary">Ana Sayfa</a></li>
                            <li class="breadcrumb-item"><a href="/profile.php" class="text-decoration-none text-primary">Hesabım</a></li>
                            <li class="breadcrumb-item active">Siparişlerim</li>
                        </ol>
                    </nav>
                    <h1 class="display-5 fw-bold text-dark mb-0" style="font-family: 'Playfair Display', serif;">
                        Sipariş Geçmişi
                    </h1>
                </div>
            </div>
        </div>
    </section>

    <!-- Orders Section -->
    <section class="py-5">
        <div class="container" style="height: %100; margin-bottom: 48px;">
            <div class="row">
                <!-- Sidebar Menu (Opsiyonel, Profil sayfalarında genelde olur) -->
                <div class="col-lg-3 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <a href="/profile.php" class="list-group-item list-group-item-action py-3">
                                    <i class="fas fa-user me-2 text-muted"></i> Profil Bilgilerim
                                </a>
                                <a href="/orders.php" class="list-group-item list-group-item-action py-3 active bg-primary text-white">
                                    <i class="fas fa-shopping-bag me-2"></i> Siparişlerim
                                </a>
                                <a href="/logout.php" class="list-group-item list-group-item-action py-3 text-danger">
                                    <i class="fas fa-sign-out-alt me-2"></i> Çıkış Yap
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Orders List -->
                <div class="col-lg-9">
                    <?php if (empty($orders)): ?>
                        <div class="text-center py-5 bg-light rounded-3">
                            <div class="mb-4">
                                <i class="fas fa-shopping-basket fa-4x text-muted opacity-50"></i>
                            </div>
                            <h3 class="fw-bold text-dark">Henüz Siparişiniz Yok</h3>
                            <p class="text-muted mb-4">Hemen alışverişe başlayıp ilk siparişinizi oluşturun.</p>
                            <a href="/shop.php" class="btn btn-primary btn-lg px-5">Alışverişe Başla</a>
                        </div>
                    <?php else: ?>
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0 fw-bold">Toplam <?php echo count($orders); ?> Sipariş</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="ps-4 py-3">Sipariş No</th>
                                                <th class="py-3">Tarih</th>
                                                <th class="py-3">Tutar</th>
                                                <th class="py-3">Durum</th>
                                                <th class="py-3 text-end pe-4">İşlem</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($orders as $order): ?>
                                                <tr>
                                                    <td class="ps-4 fw-bold">#<?php echo $order['id']; ?></td>
                                                    <td class="text-muted">
                                                        <i class="far fa-calendar-alt me-1"></i>
                                                        <?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?>
                                                    </td>
                                                    <td class="fw-bold text-primary">
                                                        ₺<?php echo number_format($order['total_amount'], 2, ',', '.'); ?>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        $statusColors = [
                                                            'pending' => 'bg-warning text-dark',
                                                            'processing' => 'bg-info text-white',
                                                            'shipped' => 'bg-primary',
                                                            'delivered' => 'bg-success',
                                                            'cancelled' => 'bg-danger'
                                                        ];
                                                        $statusTexts = [
                                                            'pending' => 'Bekliyor',
                                                            'processing' => 'Hazırlanıyor',
                                                            'shipped' => 'Kargolandı',
                                                            'delivered' => 'Teslim Edildi',
                                                            'cancelled' => 'İptal Edildi'
                                                        ];
                                                        $status = $order['status'] ?? 'pending';
                                                        ?>
                                                        <span class="badge rounded-pill <?php echo $statusColors[$status] ?? 'bg-secondary'; ?>">
                                                            <?php echo $statusTexts[$status] ?? ucfirst($status); ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-end pe-4">
                                                        <a href="#" class="btn btn-sm btn-outline-primary view-order-details" data-bs-toggle="modal" data-bs-target="#orderModal<?php echo $order['id']; ?>">
                                                            Detaylar <i class="fas fa-chevron-right ms-1"></i>
                                                        </a>
                                                    </td>
                                                </tr>

                                                <!-- Order Detail Modal -->
                                                <div class="modal fade" id="orderModal<?php echo $order['id']; ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog modal-lg modal-dialog-centered">
                                                        <div class="modal-content">
                                                            <div class="modal-header border-bottom-0">
                                                                <h5 class="modal-title fw-bold">Sipariş Detayı #<?php echo $order['id']; ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <!-- Sipariş Ürünlerini Çek -->
                                                                <?php
                                                                $item_sql = "SELECT oi.*, p.name, p.image 
                                                                             FROM order_items oi 
                                                                             JOIN products p ON oi.product_id = p.id 
                                                                             WHERE oi.order_id = ?";
                                                                $item_stmt = $conn->prepare($item_sql);
                                                                $item_stmt->bind_param("i", $order['id']);
                                                                $item_stmt->execute();
                                                                $items_result = $item_stmt->get_result();
                                                                ?>
                                                                
                                                                <div class="list-group list-group-flush mb-3">
                                                                    <?php while($item = $items_result->fetch_assoc()): ?>
                                                                        <div class="list-group-item d-flex align-items-center py-3 px-0 border-bottom">
                                                                            <div class="flex-shrink-0">
                                                                                <img src="<?php echo htmlspecialchars($item['image'] ?? '/assets/img/placeholder.jpg'); ?>" 
                                                                                     alt="Ürün" class="rounded" style="width: 60px; height: 60px; object-fit: cover;">
                                                                            </div>
                                                                            <div class="flex-grow-1 ms-3">
                                                                                <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($item['name']); ?></h6>
                                                                                <small class="text-muted">Adet: <?php echo $item['quantity']; ?></small>
                                                                            </div>
                                                                            <div class="text-end">
                                                                                <span class="fw-bold text-primary">₺<?php echo number_format($item['price'] * $item['quantity'], 2, ',', '.'); ?></span>
                                                                            </div>
                                                                        </div>
                                                                    <?php endwhile; ?>
                                                                    <?php $item_stmt->close(); ?>
                                                                </div>

                                                                <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded">
                                                                    <span class="fw-bold text-muted">Toplam Tutar</span>
                                                                    <span class="h5 mb-0 fw-bold text-primary">₺<?php echo number_format($order['total_amount'], 2, ',', '.'); ?></span>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer border-top-0">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

<?php require_once 'includes/footer.php'; ?>