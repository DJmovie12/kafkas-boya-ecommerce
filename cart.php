<?php
$page_title = "Sepetim";
require_once 'includes/header.php';
require_once 'includes/session.php';

// Giriş kontrolü
if (!isUserLoggedIn()) {
    header("Location: /login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Sepeti al
$stmt = $conn->prepare("SELECT c.*, p.name, p.price, p.image, p.stock 
                        FROM cart c 
                        JOIN products p ON c.product_id = p.id 
                        WHERE c.user_id = ?
                        ORDER BY c.added_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$cart_items = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Sepet toplamını hesapla
$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>

    <!-- Page Header -->
    <section class="page-header bg-light py-5" style="margin-top: 76px;">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-2">
                            <li class="breadcrumb-item"><a href="/index.php"
                                    class="text-decoration-none text-primary">Ana Sayfa</a></li>
                            <li class="breadcrumb-item active">Sepetim</li>
                        </ol>
                    </nav>
                    <h1 class="display-5 fw-bold text-dark mb-0" style="font-family: 'Playfair Display', serif;">
                        Sepetim
                    </h1>
                </div>
            </div>
        </div>
    </section>

    <!-- Cart Section -->
    <section class="py-5">
        <div class="container">
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (empty($cart_items)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                    <h4>Sepetiniz Boş</h4>
                    <p class="text-muted">Alışverişe devam etmek için ürünleri inceleyin</p>
                    <a href="/shop.php" class="btn btn-primary mt-3">Alışverişe Başla</a>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <!-- Cart Items -->
                    <div class="col-lg-8">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white border-bottom">
                                <h5 class="fw-bold mb-0">Sepet Öğeleri (<?php echo count($cart_items); ?>)</h5>
                            </div>
                            <div class="card-body p-0">
                                <?php foreach ($cart_items as $item): ?>
                                    <div class="cart-item d-flex align-items-center p-4 border-bottom">
                                        <img src="<?php echo htmlspecialchars($item['image'] ?? 'assets/img/placeholder.png'); ?>" 
                                            alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                            class="img-fluid rounded me-3" style="width: 100px; height: 100px; object-fit: cover;">
                                        
                                        <div class="flex-grow-1">
                                            <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                            <p class="text-muted mb-0">₺<?php echo number_format($item['price'], 2, ',', '.'); ?></p>
                                        </div>

                                        <div class="quantity-control me-3">
                                            <div class="input-group input-group-sm">
                                                <form method="POST" action="/api/cart-update.php" class="d-inline">
                                                    <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                                    <input type="hidden" name="action" value="decrease">
                                                    <button type="submit" class="btn btn-outline-secondary">-</button>
                                                </form>
                                                <input type="number" class="form-control text-center" value="<?php echo $item['quantity']; ?>" readonly style="width: 50px;">
                                                <form method="POST" action="/api/cart-update.php" class="d-inline">
                                                    <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                                    <input type="hidden" name="action" value="increase">
                                                    <button type="submit" class="btn btn-outline-secondary">+</button>
                                                </form>
                                            </div>
                                        </div>

                                        <div class="text-end me-3">
                                            <h6 class="fw-bold">₺<?php echo number_format($item['price'] * $item['quantity'], 2, ',', '.'); ?></h6>
                                        </div>

                                        <form method="POST" action="/api/cart-remove.php" class="d-inline">
                                            <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Cart Summary -->
                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm sticky-top" style="top: 100px;">
                            <div class="card-header bg-white border-bottom">
                                <h5 class="fw-bold mb-0">Sipariş Özeti</h5>
                            </div>
                            <div class="card-body">
                                <div class="summary-row d-flex justify-content-between mb-3">
                                    <span class="text-muted">Ara Toplam:</span>
                                    <span class="fw-bold">₺<?php echo number_format($total, 2, ',', '.'); ?></span>
                                </div>
                                <div class="summary-row d-flex justify-content-between mb-3">
                                    <span class="text-muted">Kargo:</span>
                                    <span class="fw-bold text-success">Ücretsiz</span>
                                </div>
                                <div class="summary-row d-flex justify-content-between mb-3">
                                    <span class="text-muted">KDV (18%):</span>
                                    <span class="fw-bold">₺<?php echo number_format($total * 0.18, 2, ',', '.'); ?></span>
                                </div>
                                <hr>
                                <div class="summary-row d-flex justify-content-between mb-4">
                                    <span class="fw-bold">Toplam:</span>
                                    <span class="fw-bold text-primary" style="font-size: 1.25rem;">₺<?php echo number_format($total * 1.18, 2, ',', '.'); ?></span>
                                </div>
                                <a href="/checkout.php" class="btn btn-primary w-100 mb-2">
                                    <i class="fas fa-credit-card me-2"></i>Ödemeye Geç
                                </a>
                                <a href="/shop.php" class="btn btn-outline-secondary w-100">
                                    <i class="fas fa-shopping-bag me-2"></i>Alışverişe Devam Et
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

<?php require_once 'includes/footer.php'; ?>
