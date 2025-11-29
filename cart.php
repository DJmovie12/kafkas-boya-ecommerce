<?php
require_once 'includes/db_connect.php';
require_once 'includes/session.php';

// Giriş yapmış kullanıcı için user_id, yoksa guest
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$is_guest = !isset($_SESSION['user_id']);

// Sepete ürün ekleme (hem girişli hem misafir için)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $product_id = $_POST['product_id'];
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
        
        // Önce ürünün stok bilgisini kontrol et
        $stmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        $stmt->close();
        
        if (!$product) {
            $_SESSION['error'] = 'Ürün bulunamadı!';
        } elseif ($quantity > $product['stock']) {
            $_SESSION['error'] = "Maksimum {$product['stock']} adet ekleyebilirsiniz!";
        } else {
            if ($is_guest) {
                // Misafir kullanıcı - session'a ekle
                if (!isset($_SESSION['guest_cart'])) {
                    $_SESSION['guest_cart'] = [];
                }
                
                $product_found = false;
                foreach ($_SESSION['guest_cart'] as &$item) {
                    if ($item['product_id'] == $product_id) {
                        $new_quantity = $item['quantity'] + $quantity;
                        if ($new_quantity > $product['stock']) {
                            $_SESSION['error'] = "Toplam miktar stok miktarını aşamaz! Mevcut stok: {$product['stock']}";
                            $product_found = true;
                            break;
                        }
                        $item['quantity'] = $new_quantity;
                        $product_found = true;
                        $_SESSION['success'] = 'Ürün miktarı güncellendi!';
                        break;
                    }
                }
                
                if (!$product_found) {
                    $_SESSION['guest_cart'][] = [
                        'product_id' => $product_id,
                        'quantity' => $quantity,
                        'added_at' => time()
                    ];
                    $_SESSION['success'] = 'Ürün sepete eklendi!';
                }
            } else {
                // Giriş yapmış kullanıcı - veritabanına ekle
                // Sepette zaten var mı kontrol et
                $stmt = $conn->prepare("SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?");
                $stmt->bind_param("ii", $user_id, $product_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $cart_item = $result->fetch_assoc();
                $stmt->close();
                
                if ($cart_item) {
                    $new_quantity = $cart_item['quantity'] + $quantity;
                    if ($new_quantity > $product['stock']) {
                        $_SESSION['error'] = "Toplam miktar stok miktarını aşamaz! Mevcut stok: {$product['stock']}";
                    } else {
                        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
                        $stmt->bind_param("iii", $new_quantity, $user_id, $product_id);
                        $stmt->execute();
                        $stmt->close();
                        $_SESSION['success'] = 'Ürün miktarı güncellendi!';
                    }
                } else {
                    $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
                    $stmt->bind_param("iii", $user_id, $product_id, $quantity);
                    $stmt->execute();
                    $stmt->close();
                    $_SESSION['success'] = 'Ürün sepete eklendi!';
                }
            }
        }
        header('Location: cart.php');
        exit();
    }
    
    // Sepetten ürün silme
    if ($_POST['action'] === 'remove') {
        $product_id = $_POST['product_id'];
        
        if ($is_guest) {
            // Misafir kullanıcı - session'dan sil
            if (isset($_SESSION['guest_cart'])) {
                foreach ($_SESSION['guest_cart'] as $key => $item) {
                    if ($item['product_id'] == $product_id) {
                        unset($_SESSION['guest_cart'][$key]);
                        $_SESSION['guest_cart'] = array_values($_SESSION['guest_cart']); // Reindex
                        break;
                    }
                }
            }
            $_SESSION['success'] = 'Ürün sepetten çıkarıldı!';
        } else {
            // Giriş yapmış kullanıcı - veritabanından sil
            $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("ii", $user_id, $product_id);
            $stmt->execute();
            $stmt->close();
            $_SESSION['success'] = 'Ürün sepetten çıkarıldı!';
        }
        header('Location: cart.php');
        exit();
    }
    
    // Miktar güncelleme
    if ($_POST['action'] === 'update') {
        $product_id = $_POST['product_id'];
        $quantity = (int)$_POST['quantity'];
        
        // Stok kontrolü
        $stmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        $stmt->close();
        
        if ($quantity > $product['stock']) {
            $_SESSION['error'] = "Maksimum {$product['stock']} adet ekleyebilirsiniz!";
        } elseif ($quantity < 1) {
            $_SESSION['error'] = 'Miktar en az 1 olmalıdır!';
        } else {
            if ($is_guest) {
                // Misafir kullanıcı - session'da güncelle
                if (isset($_SESSION['guest_cart'])) {
                    foreach ($_SESSION['guest_cart'] as &$item) {
                        if ($item['product_id'] == $product_id) {
                            $item['quantity'] = $quantity;
                            break;
                        }
                    }
                }
                $_SESSION['success'] = 'Miktar güncellendi!';
            } else {
                // Giriş yapmış kullanıcı - veritabanında güncelle
                $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
                $stmt->bind_param("iii", $quantity, $user_id, $product_id);
                $stmt->execute();
                $stmt->close();
                $_SESSION['success'] = 'Miktar güncellendi!';
            }
        }
        header('Location: cart.php');
        exit();
    }
}

// Sepetteki ürünleri al
$cart_items = [];
$total = 0;

if ($is_guest) {
    // Misafir kullanıcı - session'dan sepeti al
    if (isset($_SESSION['guest_cart']) && !empty($_SESSION['guest_cart'])) {
        $product_ids = array_column($_SESSION['guest_cart'], 'product_id');
        if (!empty($product_ids)) {
            $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
            $stmt = $conn->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
            $stmt->bind_param(str_repeat('i', count($product_ids)), ...$product_ids);
            $stmt->execute();
            $result = $stmt->get_result();
            $products = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            // Session'daki miktarlarla birleştir
            foreach ($_SESSION['guest_cart'] as $cart_item) {
                foreach ($products as $product) {
                    if ($product['id'] == $cart_item['product_id']) {
                        $product['quantity'] = $cart_item['quantity'];
                        $cart_items[] = $product;
                        $total += $product['price'] * $cart_item['quantity'];
                        break;
                    }
                }
            }
        }
    }
} else {
    // Giriş yapmış kullanıcı - veritabanından sepeti al
    $stmt = $conn->prepare("
        SELECT c.*, p.name, p.price, p.image, p.stock 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cart_items = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    foreach ($cart_items as $item) {
        $total += $item['price'] * $item['quantity'];
    }
}

$page_title = "Sepetim - Kafkas Boya";
include 'includes/header.php';
?>

<style>
    .cart-page {
        background: #f8f9fa;
        min-height: calc(100vh - 200px);
        padding: 70px 0;
    }

    .cart-header {
        background: linear-gradient(135deg, #4A90E2, #2C6EBB);
        color: white;
        padding: 30px 0;
        margin-bottom: 40px;
        border-radius: 0 0 30px 30px;
        box-shadow: 0 10px 30px rgba(74, 144, 226, 0.2);
    }

    .cart-header h1 {
        font-family: 'Playfair Display', serif;
        font-weight: 700;
        margin: 0;
    }

    .cart-header .cart-count {
        background: #D4AF37;
        color: #2C2C2C;
        padding: 5px 15px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 14px;
        margin-left: 15px;
    }

    /* Empty Cart State */
    .empty-cart {
        text-align: center;
        padding: 80px 20px;
        background: white;
        border-radius: 20px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    }

    .empty-cart-icon {
        font-size: 100px;
        color: #e2e8f0;
        margin-bottom: 30px;
        animation: bounce 2s infinite;
    }

    @keyframes bounce {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-20px); }
    }

    .empty-cart h3 {
        color: #2C2C2C;
        font-family: 'Playfair Display', serif;
        margin-bottom: 15px;
    }

    .empty-cart p {
        color: #64748b;
        margin-bottom: 30px;
    }

    /* Cart Item Card */
    .cart-item {
        background: white;
        border-radius: 20px;
        padding: 25px;
        margin-bottom: 20px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
        animation: slideIn 0.5s ease-out;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .cart-item:hover {
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        transform: translateY(-5px);
    }

    .cart-item.removing {
        animation: slideOut 0.5s ease-out forwards;
    }

    @keyframes slideOut {
        to {
            opacity: 0;
            transform: translateX(-100%);
        }
    }

    .product-image-wrapper {
        width: 120px;
        height: 120px;
        border-radius: 15px;
        overflow: hidden;
        background: #f8f9fa;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .product-image-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .cart-item:hover .product-image-wrapper img {
        transform: scale(1.1);
    }

    .product-info h5 {
        color: #2C2C2C;
        font-weight: 600;
        margin-bottom: 10px;
    }

    .product-price {
        color: #4A90E2;
        font-size: 24px;
        font-weight: 700;
        font-family: 'Playfair Display', serif;
    }

    .stock-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
    }

    .stock-badge.high {
        background: #d1fae5;
        color: #059669;
    }

    .stock-badge.medium {
        background: #fef3c7;
        color: #d97706;
    }

    .stock-badge.low {
        background: #fee2e2;
        color: #dc2626;
    }

    /* Quantity Controls */
    .quantity-control {
        display: flex;
        align-items: center;
        gap: 10px;
        background: #f8f9fa;
        padding: 8px;
        border-radius: 12px;
        width: fit-content;
    }

    .quantity-btn {
        width: 36px;
        height: 36px;
        border: none;
        background: white;
        border-radius: 8px;
        color: #4A90E2;
        font-size: 18px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .quantity-btn:hover {
        background: #4A90E2;
        color: white;
        transform: scale(1.1);
    }

    .quantity-input {
        width: 60px;
        height: 36px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        text-align: center;
        font-weight: 600;
        color: #2C2C2C;
        font-size: 16px;
    }

    .quantity-input:focus {
        outline: none;
        border-color: #4A90E2;
    }

    /* Remove Button */
    .remove-btn {
        width: 40px;
        height: 40px;
        border: 2px solid #fee2e2;
        background: white;
        color: #dc2626;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }

    .remove-btn:hover {
        background: #dc2626;
        color: white;
        transform: rotate(90deg);
    }

    /* Cart Summary */
    .cart-summary {
        background: white;
        border-radius: 20px;
        padding: 30px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        position: sticky;
        top: 100px;
    }

    .cart-summary h4 {
        font-family: 'Playfair Display', serif;
        color: #2C2C2C;
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f8f9fa;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 15px;
        color: #64748b;
    }

    .summary-row.total {
        font-size: 24px;
        font-weight: 700;
        color: #2C2C2C;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 2px solid #f8f9fa;
    }

    .summary-row.total .amount {
        color: #4A90E2;
        font-family: 'Playfair Display', serif;
    }

    .checkout-btn {
        width: 100%;
        padding: 16px;
        background: linear-gradient(135deg, #D4AF37, #B8941F);
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 18px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-top: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .checkout-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 30px rgba(212, 175, 55, 0.4);
    }

    .continue-shopping {
        width: 100%;
        padding: 14px;
        background: white;
        color: #4A90E2;
        border: 2px solid #4A90E2;
        border-radius: 12px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-top: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        text-decoration: none;
    }

    .continue-shopping:hover {
        background: #4A90E2;
        color: white;
    }

    /* Alerts */
    .custom-alert {
        padding: 15px 20px;
        border-radius: 12px;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 12px;
        animation: slideDown 0.5s ease-out;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .custom-alert.success {
        background: #d1fae5;
        color: #059669;
        border: 1px solid #a7f3d0;
    }

    .custom-alert.error {
        background: #fee2e2;
        color: #dc2626;
        border: 1px solid #fecaca;
    }

    .custom-alert i {
        font-size: 20px;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .cart-item {
            padding: 20px;
        }

        .product-image-wrapper {
            width: 80px;
            height: 80px;
        }

        .product-price {
            font-size: 20px;
        }

        .cart-summary {
            position: static;
            margin-top: 30px;
        }

        .quantity-control {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<div class="cart-page">
    <div class="cart-header">
        <div class="container">
            <div class="d-flex align-items-center">
                <h1><i class="fas fa-shopping-cart me-3"></i>Sepetim</h1>
                <?php if (!empty($cart_items)): ?>
                    <span class="cart-count"><?php echo count($cart_items); ?> Ürün</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="custom-alert error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="custom-alert success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if (empty($cart_items)): ?>
            <div class="empty-cart">
                <div class="empty-cart-icon">
                    <i class="fas fa-shopping-basket"></i>
                </div>
                <h3>Sepetiniz Boş</h3>
                <p>Henüz sepetinize ürün eklemediniz. Hemen alışverişe başlayın!</p>
                <a href="shop.php" class="btn btn-primary btn-lg px-5 rounded-pill">
                    <i class="fas fa-store me-2"></i>Alışverişe Başla
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-lg-8">
                    <?php foreach ($cart_items as $index => $item): 
                        $stock_level = $item['stock'] > 10 ? 'high' : ($item['stock'] > 5 ? 'medium' : 'low');
                        // product_id'yi doğru şekilde al
                        $product_id = $is_guest ? $item['id'] : ($item['product_id'] ?? $item['id']);
                    ?>
                        <div class="cart-item" style="animation-delay: <?php echo $index * 0.1; ?>s">
                            <div class="row align-items-center">
                                <div class="col-md-2 col-4 mb-3 mb-md-0">
                                    <div class="product-image-wrapper">
                                        <?php if (!empty($item['image'])): ?>
                                            <img src="<?php echo htmlspecialchars($item['image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($item['name']); ?>">
                                        <?php else: ?>
                                            <i class="fas fa-image text-muted" style="font-size: 40px;"></i>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="col-md-4 col-8 mb-3 mb-md-0">
                                    <div class="product-info">
                                        <h5><?php echo htmlspecialchars($item['name']); ?></h5>
                                        <div class="product-price">₺<?php echo number_format($item['price'], 2, ',', '.'); ?></div>
                                        <span class="stock-badge <?php echo $stock_level; ?>">
                                            <i class="fas fa-box"></i>
                                            <?php echo $item['stock']; ?> adet stok
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="col-md-3 col-8 mb-3 mb-md-0">
                                    <form method="POST" class="quantity-form">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                        <div class="quantity-control">
                                            <button type="button" class="quantity-btn minus-btn" 
                                                    data-product-id="<?php echo $product_id; ?>">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <input type="number" name="quantity" class="quantity-input" 
                                                   value="<?php echo $item['quantity']; ?>" 
                                                   min="1" max="<?php echo $item['stock']; ?>"
                                                   data-product-id="<?php echo $product_id; ?>">
                                            <button type="button" class="quantity-btn plus-btn"
                                                    data-product-id="<?php echo $product_id; ?>"
                                                    data-max="<?php echo $item['stock']; ?>">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                                
                                <div class="col-md-2 col-4 mb-3 mb-md-0">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="product-total">
                                            <strong style="color: #2C2C2C; font-size: 20px;">
                                                ₺<?php echo number_format($item['price'] * $item['quantity'], 2, ',', '.'); ?>
                                            </strong>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-1 col-12 text-end">
                                    <form method="POST" class="remove-form">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                        <button type="submit" class="remove-btn" 
                                                onclick="return confirm('Bu ürünü sepetten çıkarmak istediğinize emin misiniz?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
<div class="col-lg-4">
    <div class="cart-summary">
        <h4><i class="fas fa-file-invoice me-2"></i>Sipariş Özeti</h4>
        
        <div class="summary-row">
            <span>Ara Toplam</span>
            <span class="amount">₺<?php echo number_format($total, 2, ',', '.'); ?></span>
        </div>
        
        <div class="summary-row">
            <span>KDV (%20)</span>
            <span class="amount">₺<?php echo number_format($total * 0.20, 2, ',', '.'); ?></span>
        </div>
        
        <div class="summary-row">
            <span>Kargo</span>
            <span class="amount text-success">
                <?php if ($total > 500): ?>
                    Ücretsiz
                <?php else: ?>
                    ₺50,00
                <?php endif; ?>
            </span>
        </div>
        
        <div class="summary-row total">
            <span>Toplam</span>
            <span class="amount">₺<?php 
                $final_total = $total * 1.20;
                if ($total <= 500) {
                    $final_total += 50;
                }
                echo number_format($final_total, 2, ',', '.'); 
            ?></span>
        </div>

        <?php if ($total < 500): ?>
            <div class="alert alert-info mt-3 p-2 text-center" style="border-radius: 10px; font-size: 13px;">
                <i class="fas fa-truck me-1"></i>
                ₺<?php echo number_format(500 - $total, 2, ',', '.'); ?> daha alışveriş yapın, 
                <strong>kargo bedava!</strong>
            </div>
        <?php endif; ?>
        
<!-- Checkout butonunu güncelleyelim -->
<?php if ($is_guest): ?>
    <?php 
    // Misafir kullanıcı için redirect after login ayarla
    setRedirectAfterLogin('checkout.php');
    ?>
    <a href="login.php" class="checkout-btn">
        <i class="fas fa-sign-in-alt"></i>
        Ödeme İçin Giriş Yap
    </a>
<?php else: ?>
    <a href="checkout.php" class="checkout-btn">
        <i class="fas fa-lock"></i>
        Güvenli Ödeme
    </a>
<?php endif; ?>
        
        <a href="shop.php" class="continue-shopping">
            <i class="fas fa-arrow-left"></i>
            Alışverişe Devam Et
        </a>

        <div class="mt-4 text-center">
            <small class="text-muted">
                <i class="fas fa-shield-alt me-1"></i>
                256-bit SSL ile güvenli alışveriş
            </small>
        </div>
    </div>
</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Plus/Minus buttons
    document.querySelectorAll('.plus-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const productId = this.dataset.productId;
            const max = parseInt(this.dataset.max);
            const input = document.querySelector(`input[data-product-id="${productId}"]`);
            const currentValue = parseInt(input.value);
            
            if (currentValue < max) {
                input.value = currentValue + 1;
                input.closest('form').submit();
            } else {
                alert(`Maksimum ${max} adet ekleyebilirsiniz!`);
            }
        });
    });
    
    document.querySelectorAll('.minus-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const productId = this.dataset.productId;
            const input = document.querySelector(`input[data-product-id="${productId}"]`);
            const currentValue = parseInt(input.value);
            
            if (currentValue > 1) {
                input.value = currentValue - 1;
                input.closest('form').submit();
            }
        });
    });

    // Auto-submit on input change
    document.querySelectorAll('.quantity-input').forEach(input => {
        let timeout;
        input.addEventListener('change', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                this.closest('form').submit();
            }, 500);
        });
    });

    // Remove animation
    document.querySelectorAll('.remove-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const cartItem = this.closest('.cart-item');
            cartItem.classList.add('removing');
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>