<?php
// Gerekli dosyaları dahil et
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/session.php'; // getCartItemCount artık burada

// GÜVENLİK KONTROLÜ: Kullanıcı giriş yapmış mı?
if (!isUserLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Sepete ürün eklemek için lütfen giriş yapın.']);
    exit;
}

// Gerekli verileri al
$user_id = $_SESSION['user_id'];
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

// Girişleri doğrula
if ($product_id <= 0 || $quantity <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Geçersiz ürün bilgisi.']);
    exit;
}

// SQL Güvenliği (Prepared Statements) için bağlantı nesnesini kontrol et
// Eğer db_connect.php'de bağlantı başarısız olduysa $conn null olacaktır.
if (!isset($conn) || $conn === null) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Veritabanı bağlantısı kurulamadı.']);
    exit;
}

try {
    // 1. Ürünün sepette olup olmadığını kontrol et
    $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cart_item = $result->fetch_assoc();
    $stmt->close();

    if ($cart_item) {
        // Ürün sepette varsa: Miktarı güncelle (Yeni miktarı topla)
        $new_quantity = $cart_item['quantity'] + $quantity;
        
        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_quantity, $cart_item['id']);
        $stmt->execute();
        $stmt->close();
        
        $message = "Ürün sepetinizdeki mevcut miktarına $quantity adet eklendi.";
    } else {
        // Ürün sepette yoksa: Yeni kayıt oluştur
        $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $user_id, $product_id, $quantity);
        $stmt->execute();
        $stmt->close();
        
        $message = "Ürün sepetinize başarıyla eklendi.";
    }

    // Başarılı yanıt gönder
    header('Content-Type: application/json');
    // Fonksiyon artık session.php'den çağrılıyor
    echo json_encode([
        'success' => true, 
        'message' => $message,
        'cart_count' => getCartItemCount($user_id, $conn) 
    ]);

} catch (mysqli_sql_exception $e) {
    // Veritabanı işlemleri sırasında oluşan hataları yakala
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Veritabanı işlemi sırasında hata oluştu: ' . $e->getMessage()]);
}

// getCartItemCount fonksiyonu session.php'ye taşındı.