<?php
// Gerekli dosyaları dahil et
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/session.php';

// Gerekli verileri al
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

// Girişleri doğrula
if ($product_id <= 0 || $quantity <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Geçersiz ürün bilgisi.']);
    exit;
}

// Veritabanı bağlantı kontrolü
if (!isset($conn) || $conn === null) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Veritabanı bağlantısı kurulamadı.']);
    exit;
}

try {
    // ÖNCE STOK KONTROLÜ YAP
    $stock_stmt = $conn->prepare("SELECT stock, name FROM products WHERE id = ?");
    $stock_stmt->bind_param("i", $product_id);
    $stock_stmt->execute();
    $stock_result = $stock_stmt->get_result();
    
    if ($stock_result->num_rows === 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Ürün bulunamadı.']);
        exit;
    }
    
    $product = $stock_result->fetch_assoc();
    $stock_stmt->close();
    
    // Stok yeterli mi kontrol et
    if ($product['stock'] < $quantity) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'message' => "Yeterli stok yok! Mevcut stok: {$product['stock']} adet"
        ]);
        exit;
    }

    // KULLANICI GİRİŞ YAPMIŞ MI KONTROL ET
    if (isUserLoggedIn()) {
        // ============================================
        // GİRİŞ YAPMIŞ KULLANICI - VERİTABANINA EKLE
        // ============================================
        $user_id = $_SESSION['user_id'];
        
        // 1. Ürünün sepette olup olmadığını kontrol et
        $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $user_id, $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $cart_item = $result->fetch_assoc();
        $stmt->close();

        if ($cart_item) {
            // Ürün sepette varsa: Toplam miktarı kontrol et
            $new_quantity = $cart_item['quantity'] + $quantity;
            
                // Stok kontrolü (sepetteki + yeni eklenen)
                if ($new_quantity > $product['stock']) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false, 
                        'message' => "❌ STOK YETERSİZ! Sepetinizde zaten {$cart_item['quantity']} adet var. Toplam {$product['stock']} adetten fazla ekleyemezsiniz!",
                        'error_type' => 'stock_limit'
                    ]);
                    exit;
                }
            // Stok yeterli, güncelle
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

        // Başarılı yanıt gönder (Veritabanından sepet sayısı)
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => $message,
            'cart_count' => getCartItemCount($user_id, $conn),
            'logged_in' => true
        ]);

    } else {
        // ============================================
        // GİRİŞ YAPMAYAN KULLANICI - SESSION'A EKLE
        // ============================================
        
        // Session sepet array'i yoksa oluştur
        if (!isset($_SESSION['guest_cart'])) {
            $_SESSION['guest_cart'] = [];
        }
        
        // Ürün zaten sepette var mı kontrol et
        $product_found = false;
        foreach ($_SESSION['guest_cart'] as &$item) {
            if ($item['product_id'] == $product_id) {
                $new_quantity = $item['quantity'] + $quantity;
                
                // Stok kontrolü (sepetteki + yeni eklenen)
                if ($new_quantity > $product['stock']) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false, 
                        'message' => "❌ STOK YETERSİZ! Sepetinizde zaten {$item['quantity']} adet var. Toplam {$product['stock']} adetten fazla ekleyemezsiniz!",
                        'error_type' => 'stock_limit'
                    ]);
                    exit;
                }
                
                $item['quantity'] = $new_quantity;
                $product_found = true;
                $message = "Ürün sepetinizdeki mevcut miktarına $quantity adet eklendi.";
                break;
            }
        }
        
        // Ürün sepette yoksa yeni ekle
        if (!$product_found) {
            $_SESSION['guest_cart'][] = [
                'product_id' => $product_id,
                'quantity' => $quantity,
                'added_at' => time()
            ];
            $message = "Ürün sepetinize başarıyla eklendi.";
        }
        
        // Session sepetteki toplam ürün sayısını hesapla
        $cart_count = 0;
        foreach ($_SESSION['guest_cart'] as $item) {
            $cart_count += $item['quantity'];
        }
        
        // Başarılı yanıt gönder (Session'dan sepet sayısı)
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => $message,
            'cart_count' => $cart_count,
            'logged_in' => false,
            'notice' => 'Ödeme yapmak için giriş yapmanız gerekecek.'
        ]);
    }

} catch (mysqli_sql_exception $e) {
    // Veritabanı işlemleri sırasında oluşan hataları yakala
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu. Lütfen tekrar deneyin.']);
} catch (Exception $e) {
    // Diğer hatalar
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Beklenmeyen bir hata oluştu.']);
}
?>