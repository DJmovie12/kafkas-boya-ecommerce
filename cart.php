<?php
session_start();
require_once 'includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Sepete ürün ekleme
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
        header('Location: cart.php');
        exit();
    }
    
    // Sepetten ürün silme
    if ($_POST['action'] === 'remove') {
        $product_id = $_POST['product_id'];
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $user_id, $product_id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['success'] = 'Ürün sepetten çıkarıldı!';
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
            $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("iii", $quantity, $user_id, $product_id);
            $stmt->execute();
            $stmt->close();
            $_SESSION['success'] = 'Miktar güncellendi!';
        }
        header('Location: cart.php');
        exit();
    }
}

// Sepetteki ürünleri al
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

$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}

include 'includes/header.php';
?>

<div class="container my-5">
    <h2>Sepetim</h2>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (empty($cart_items)): ?>
        <div class="alert alert-info">Sepetiniz boş!</div>
        <a href="shop.php" class="btn btn-primary">Alışverişe Devam Et</a>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Ürün</th>
                        <th>Fiyat</th>
                        <th>Miktar</th>
                        <th>Stok</th>
                        <th>Toplam</th>
                        <th>İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart_items as $item): ?>
                        <tr>
                            <td>
                                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="" style="width: 50px;">
                                <?php echo htmlspecialchars($item['name']); ?>
                            </td>
                            <td>₺<?php echo number_format($item['price'], 2); ?></td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                    <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                           min="1" max="<?php echo $item['stock']; ?>" 
                                           class="form-control form-control-sm" style="width: 80px; display: inline-block;">
                                    <button type="submit" class="btn btn-sm btn-primary">Güncelle</button>
                                </form>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $item['stock'] > 10 ? 'success' : 'warning'; ?>">
                                    <?php echo $item['stock']; ?> adet
                                </span>
                            </td>
                            <td>₺<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Sil</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="text-end"><strong>Toplam:</strong></td>
                        <td colspan="2"><strong>₺<?php echo number_format($total, 2); ?></strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <div class="text-end">
            <a href="shop.php" class="btn btn-secondary">Alışverişe Devam Et</a>
            <a href="checkout.php" class="btn btn-success">Siparişi Tamamla</a>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>