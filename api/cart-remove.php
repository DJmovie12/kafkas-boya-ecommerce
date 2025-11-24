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

if ($product_id === 0) {
    header("Location: /cart.php");
    exit();
}

// Sepetten ürünü sil
$stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
$stmt->bind_param("ii", $user_id, $product_id);
$stmt->execute();
$stmt->close();

// Sepete geri dön
header("Location: /cart.php");
exit();
?>
