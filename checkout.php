<?php
$page_title = "Ödeme";
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
$stmt = $conn->prepare("SELECT c.*, p.name, p.price 
                        FROM cart c 
                        JOIN products p ON c.product_id = p.id 
                        WHERE c.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$cart_items = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Sepet boşsa yönlendir
if (empty($cart_items)) {
    header("Location: /cart.php");
    exit();
}

// Toplam hesapla
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$tax = $subtotal * 0.18;
$total = $subtotal + $tax;

// Sipariş oluştur
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');
    $payment_method = trim($_POST['payment_method'] ?? '');

    // Validasyon
    if (empty($full_name) || empty($email) || empty($phone) || empty($address) || empty($city) || empty($postal_code)) {
        $error = 'Tüm alanlar gereklidir.';
    } elseif (empty($payment_method)) {
        $error = 'Ödeme yöntemi seçiniz.';
    } else {
        // Sipariş oluştur
        $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, status) VALUES (?, ?, 'pending')");
        $stmt->bind_param("id", $user_id, $total);
        
        if ($stmt->execute()) {
            $order_id = $stmt->insert_id;
            $stmt->close();

            // Sipariş öğelerini ekle
            $insert_ok = true;
            foreach ($cart_items as $item) {
                $item_stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $item_stmt->bind_param("iiii", $order_id, $item['product_id'], $item['quantity'], $item['price']);
                
                if (!$item_stmt->execute()) {
                    $insert_ok = false;
                    break;
                }
                $item_stmt->close();
            }

            if ($insert_ok) {
                // Sepeti temizle
                $clear_stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
                $clear_stmt->bind_param("i", $user_id);
                $clear_stmt->execute();
                $clear_stmt->close();

                // Sipariş onay sayfasına yönlendir
                header("Location: /order-confirmation.php?order_id=" . $order_id);
                exit();
            } else {
                $error = 'Sipariş oluşturulurken bir hata oluştu.';
            }
        } else {
            $error = 'Sipariş oluşturulurken bir hata oluştu.';
        }
    }
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
                            <li class="breadcrumb-item"><a href="/cart.php"
                                    class="text-decoration-none text-primary">Sepetim</a></li>
                            <li class="breadcrumb-item active">Ödeme</li>
                        </ol>
                    </nav>
                    <h1 class="display-5 fw-bold text-dark mb-0" style="font-family: 'Playfair Display', serif;">
                        Ödeme
                    </h1>
                </div>
            </div>
        </div>
    </section>

    <!-- Checkout Section -->
    <section class="py-5">
        <div class="container">
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Checkout Form -->
                <div class="col-lg-8">
                    <form method="POST" class="checkout-form">
                        <!-- Kişisel Bilgiler -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white border-bottom">
                                <h5 class="fw-bold mb-0">
                                    <i class="fas fa-user me-2 text-primary"></i>Kişisel Bilgiler
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="full_name" class="form-label fw-medium">Ad Soyad *</label>
                                        <input type="text" class="form-control" id="full_name" name="full_name" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label fw-medium">E-posta *</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($_SESSION['email']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="phone" class="form-label fw-medium">Telefon *</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Teslimat Adresi -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white border-bottom">
                                <h5 class="fw-bold mb-0">
                                    <i class="fas fa-map-marker-alt me-2 text-primary"></i>Teslimat Adresi
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="address" class="form-label fw-medium">Adres *</label>
                                        <textarea class="form-control" id="address" name="address" rows="3" required></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="city" class="form-label fw-medium">Şehir *</label>
                                        <input type="text" class="form-control" id="city" name="city" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="postal_code" class="form-label fw-medium">Posta Kodu *</label>
                                        <input type="text" class="form-control" id="postal_code" name="postal_code" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Ödeme Yöntemi -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white border-bottom">
                                <h5 class="fw-bold mb-0">
                                    <i class="fas fa-credit-card me-2 text-primary"></i>Ödeme Yöntemi
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="radio" name="payment_method" id="credit_card" value="credit_card" required>
                                    <label class="form-check-label" for="credit_card">
                                        <i class="fas fa-credit-card me-2"></i>Kredi Kartı
                                    </label>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="radio" name="payment_method" id="debit_card" value="debit_card">
                                    <label class="form-check-label" for="debit_card">
                                        <i class="fas fa-credit-card me-2"></i>Banka Kartı
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="bank_transfer" value="bank_transfer">
                                    <label class="form-check-label" for="bank_transfer">
                                        <i class="fas fa-university me-2"></i>Banka Transferi
                                    </label>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="fas fa-check-circle me-2"></i>Siparişi Tamamla
                        </button>
                    </form>
                </div>

                <!-- Order Summary -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm sticky-top" style="top: 100px;">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="fw-bold mb-0">Sipariş Özeti</h5>
                        </div>
                        <div class="card-body">
                            <div class="summary-items mb-3" style="max-height: 300px; overflow-y: auto;">
                                <?php foreach ($cart_items as $item): ?>
                                    <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                                        <span class="text-muted"><?php echo htmlspecialchars($item['name']); ?> x<?php echo $item['quantity']; ?></span>
                                        <span class="fw-bold">₺<?php echo number_format($item['price'] * $item['quantity'], 2, ',', '.'); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <hr>

                            <div class="summary-row d-flex justify-content-between mb-2">
                                <span class="text-muted">Ara Toplam:</span>
                                <span class="fw-bold">₺<?php echo number_format($subtotal, 2, ',', '.'); ?></span>
                            </div>
                            <div class="summary-row d-flex justify-content-between mb-2">
                                <span class="text-muted">Kargo:</span>
                                <span class="fw-bold text-success">Ücretsiz</span>
                            </div>
                            <div class="summary-row d-flex justify-content-between mb-3">
                                <span class="text-muted">KDV (18%):</span>
                                <span class="fw-bold">₺<?php echo number_format($tax, 2, ',', '.'); ?></span>
                            </div>

                            <hr>

                            <div class="summary-row d-flex justify-content-between">
                                <span class="fw-bold">Toplam:</span>
                                <span class="fw-bold text-primary" style="font-size: 1.25rem;">₺<?php echo number_format($total, 2, ',', '.'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

<?php require_once 'includes/footer.php'; ?>
