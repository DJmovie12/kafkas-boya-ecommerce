<?php
require_once '../includes/db_connect.php';
require_once '../includes/session.php';

// Giriş kontrolü
if (!isUserLoggedIn()) {
    header("Location: /login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$action = isset($_POST['action']) ? trim($_POST['action']) : '';

if ($product_id === 0 || empty($action)) {
    header("Location: /cart.php");
    exit();
}

// Sepetteki ürünü al
$stmt = $conn->prepare("SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?");
$stmt->bind_param("ii", $user_id, $product_id);
$stmt->execute();
$result = $stmt->get_result();
$cart_item = $result->fetch_assoc();
$stmt->close();

if (!$cart_item) {
    header("Location: /cart.php");
    exit();
}

$current_quantity = $cart_item['quantity'];

if ($action === 'increase') {
    $new_quantity = $current_quantity + 1;
} elseif ($action === 'decrease') {
    $new_quantity = $current_quantity - 1;
    if ($new_quantity < 1) {
        $new_quantity = 1;
    }
} else {
    header("Location: /cart.php");
    exit();
}

// Sepeti güncelle
$update_stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
$update_stmt->bind_param("iii", $new_quantity, $user_id, $product_id);
$update_stmt->execute();
$update_stmt->close();

// Sepete geri dön
header("Location: /cart.php");
exit();
?>
