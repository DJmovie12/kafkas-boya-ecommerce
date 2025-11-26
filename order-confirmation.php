<?php
ob_start(); // OUTPUT BUFFERING - Header hatasını çözer

$page_title = "Sipariş Onayı";
require_once 'includes/db_connect.php';
require_once 'includes/session.php';

// Giriş kontrolü
if (!isUserLoggedIn()) {
    header("Location: /login.php");
    exit();
}

// Veritabanı bağlantı kontrolü
if (!isset($conn) || $conn === null) {
    die("Veritabanı bağlantısı kurulamadı.");
}

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id === 0) {
    header("Location: /index.php");
    exit();
}

// Siparişi al
$stmt = $conn->prepare("SELECT o.*, u.username, u.email, u.address 
                       FROM orders o 
                       JOIN users u ON o.user_id = u.id 
                       WHERE o.id = ? AND o.user_id = ?");
$stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: /orders.php");
    exit();
}

$order = $result->fetch_assoc();
$stmt->close();

// Sipariş öğelerini al
$items_stmt = $conn->prepare("SELECT oi.*, p.name, p.image 
                             FROM order_items oi 
                             JOIN products p ON oi.product_id = p.id 
                             WHERE oi.order_id = ?");
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();
$order_items = $items_result->fetch_all(MYSQLI_ASSOC);
$items_stmt->close();

// Şimdi header'ı include et
require_once 'includes/header.php';
ob_end_flush(); // Buffer'ı temizle
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sipariş Onayı - Kafkas Boya</title>
    <!-- Page Header -->
    <section class="page-header bg-light py-5" style="margin-top: 70px;">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-12">
                    <h1 class="display-5 fw-bold text-dark mb-0" style="font-family: 'Playfair Display', serif;">
                        Sipariş Onayı
                    </h1>
                </div>
            </div>
        </div>
    </section>

    <!-- Confirmation Section -->
    <section class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <!-- Success Message -->
                    <div class="text-center mb-5">
                        <div class="success-icon mb-4">
                            <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                        </div>
                        <h2 class="fw-bold text-dark mb-2">Siparişiniz Başarıyla Alındı!</h2>
                        <p class="text-muted lead">Siparişiniz işleme alınmıştır. Aşağıda sipariş detaylarını görebilirsiniz.</p>
                    </div>

                    <!-- Order Details Card -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="fw-bold mb-0">
                                <i class="fas fa-receipt me-2"></i>Sipariş Detayları
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <p class="text-muted mb-1">Sipariş Numarası:</p>
                                    <h6 class="fw-bold">#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></h6>
                                </div>
                                <div class="col-md-6">
                                    <p class="text-muted mb-1">Sipariş Tarihi:</p>
                                    <h6 class="fw-bold"><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></h6>
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <p class="text-muted mb-1">Durum:</p>
                                    <h6 class="fw-bold">
                                        <?php
                                        $status_badges = [
                                            'pending' => ['bg-warning', 'Beklemede'],
                                            'processing' => ['bg-info', 'İşleniyor'],
                                            'shipped' => ['bg-primary', 'Kargoda'],
                                            'delivered' => ['bg-success', 'Teslim Edildi'],
                                            'cancelled' => ['bg-danger', 'İptal Edildi']
                                        ];
                                        $status = $order['status'] ?? 'pending';
                                        $badge = $status_badges[$status] ?? $status_badges['pending'];
                                        ?>
                                        <span class="badge <?php echo $badge[0]; ?>"><?php echo $badge[1]; ?></span>
                                    </h6>
                                </div>
                                <div class="col-md-6">
                                    <p class="text-muted mb-1">Toplam Tutar:</p>
                                    <h6 class="fw-bold text-primary">₺<?php echo number_format($order['total_amount'], 2, ',', '.'); ?></h6>
                                </div>
                            </div>

                            <!-- Teslimat Adresi -->
                            <div class="row">
                                <div class="col-12">
                                    <p class="text-muted mb-1">Teslimat Adresi:</p>
                                    <h6 class="fw-bold"><?php echo htmlspecialchars($order['address'] ?? 'Belirtilmemiş'); ?></h6>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="fw-bold mb-0">
                                <i class="fas fa-boxes me-2"></i>Sipariş Öğeleri
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <?php foreach ($order_items as $item): ?>
                                <div class="d-flex align-items-center p-4 border-bottom">
                                    <div class="flex-shrink-0 me-3">
                                        <?php if (!empty($item['image'])): ?>
                                            <img src="<?php echo htmlspecialchars($item['image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                                 class="rounded" style="width: 60px; height: 60px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-light rounded d-flex align-items-center justify-content-center" 
                                                 style="width: 60px; height: 60px;">
                                                <i class="fas fa-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                        <p class="text-muted mb-0">Miktar: <?php echo $item['quantity']; ?> adet</p>
                                    </div>
                                    <div class="text-end">
                                        <p class="text-muted mb-1">Birim Fiyat: ₺<?php echo number_format($item['price'], 2, ',', '.'); ?></p>
                                        <h6 class="fw-bold">₺<?php echo number_format($item['price'] * $item['quantity'], 2, ',', '.'); ?></h6>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Next Steps -->
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Sonraki Adımlar:</strong> Siparişiniz 2-3 gün içinde kargoya verilecektir. Sipariş takibini yapmak için profilinizden "Siparişlerim" bölümünü ziyaret edebilirsiniz.
                    </div>

                    <!-- Action Buttons -->
                    <div class="row g-3">
                        <div class="col-md-6">
                            <a href="/orders.php" class="btn btn-primary w-100">
                                <i class="fas fa-list me-2"></i>Siparişlerim
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="/shop.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-shopping-bag me-2"></i>Alışverişe Devam Et
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

<?php require_once 'includes/footer.php'; ?>