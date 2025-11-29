<?php
/**
 * Kafkas Boya E-Ticaret Sitesi
 * Oturum Yönetimi ve Utility Fonksiyonları
 */

session_start();

// Oturum Güvenliği
if (!isset($_SESSION['session_id'])) {
    $_SESSION['session_id'] = session_id();
}

// Kullanıcı Giriş Kontrolü
function isUserLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Kullanıcı Bilgilerini Al
function getCurrentUser() {
    if (isUserLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'] ?? '',
            'email' => $_SESSION['email'] ?? '',
            'role' => $_SESSION['role'] ?? 'user'
        ];
    }
    return null;
}

// Admin Kontrolü
function isAdmin() {
    return isUserLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Admin Sayfasına Erişim Kontrolü
function requireAdmin() {
    if (!isAdmin()) {
        header("Location: /admin/login.php");
        exit();
    }
}

// Kullanıcı Sayfasına Erişim Kontrolü
function requireLogin() {
    if (!isUserLoggedIn()) {
        header("Location: /login.php");
        exit();
    }
}

// Oturum Kapat
function logout() {
    session_destroy();
    header("Location: /index.php");
    exit();
}

// CSRF Token Oluştur
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Sepet öğe sayısını getir (hem girişli hem misafir için)
 * @param int|null $user_id
 * @param mysqli|null $conn
 * @return int
 */
function getCartItemCount($user_id = null, $conn = null) {
    $count = 0;
    
    if (isUserLoggedIn() && $conn && $user_id) {
        // Giriş yapmış kullanıcı - veritabanından say
        $stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $count = $data['total'] ?? 0;
        $stmt->close();
    } else {
        // Misafir kullanıcı - session'dan say
        if (isset($_SESSION['guest_cart'])) {
            foreach ($_SESSION['guest_cart'] as $item) {
                $count += $item['quantity'];
            }
        }
    }
    
    return $count;
}

/**
 * Misafir sepetini kullanıcıya aktar (STOK KONTROLLÜ)
 * @param int $user_id
 * @param mysqli $conn
 */
function transferGuestCartToUser($user_id, $conn) {
    if (!isset($_SESSION['guest_cart']) || empty($_SESSION['guest_cart'])) {
        return;
    }
    
    foreach ($_SESSION['guest_cart'] as $guest_item) {
        // Önce ürünün stok bilgisini al
        $stmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
        $stmt->bind_param("i", $guest_item['product_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        $stmt->close();
        
        if (!$product) {
            continue; // Ürün bulunamadıysa atla
        }
        
        $max_quantity = $product['stock'];
        
        // Ürünün zaten sepette olup olmadığını kontrol et
        $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $user_id, $guest_item['product_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $cart_item = $result->fetch_assoc();
        $stmt->close();
        
        if ($cart_item) {
            // Ürün sepette varsa: Toplam miktarı kontrol et
            $new_quantity = $cart_item['quantity'] + $guest_item['quantity'];
            
            // Stok kontrolü (sepetteki + misafir sepetindeki)
            if ($new_quantity > $max_quantity) {
                // Stok yetersizse, maksimum stok kadar ekle
                $new_quantity = $max_quantity;
                
                // Kullanıcıya bilgi mesajı göster (session'a kaydet)
                if (!isset($_SESSION['cart_transfer_messages'])) {
                    $_SESSION['cart_transfer_messages'] = [];
                }
                $_SESSION['cart_transfer_messages'][] = 
                    "Ürün stoğu yetersiz olduğu için sepetinizdeki miktar maksimum {$max_quantity} adet ile sınırlandırıldı.";
            }
            
            // Miktarı güncelle
            $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
            $stmt->bind_param("ii", $new_quantity, $cart_item['id']);
            $stmt->execute();
            $stmt->close();
        } else {
            // Ürün sepette yoksa: Yeni ekle, ama stok kontrolü yap
            $final_quantity = min($guest_item['quantity'], $max_quantity);
            
            if ($final_quantity > 0) {
                $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
                $stmt->bind_param("iii", $user_id, $guest_item['product_id'], $final_quantity);
                $stmt->execute();
                $stmt->close();
                
                // Eğer miktar kısıtlandıysa bilgi mesajı göster
                if ($final_quantity < $guest_item['quantity']) {
                    if (!isset($_SESSION['cart_transfer_messages'])) {
                        $_SESSION['cart_transfer_messages'] = [];
                    }
                    $_SESSION['cart_transfer_messages'][] = 
                        "Ürün stoğu yetersiz olduğu için sepetinize {$final_quantity} adet eklenebildi.";
                }
            }
        }
    }
    
    // Session sepetini temizle
    unset($_SESSION['guest_cart']);
}

// Sepet transfer mesajlarını al
function getCartTransferMessages() {
    $messages = $_SESSION['cart_transfer_messages'] ?? [];
    unset($_SESSION['cart_transfer_messages']);
    return $messages;
}

// Geçici contact mesajını session'a kaydet
function saveTempContactMessage($messageData) {
    $_SESSION['temp_contact_message'] = $messageData;
}

// Geçici contact mesajını al
function getTempContactMessage() {
    return $_SESSION['temp_contact_message'] ?? null;
}

// Geçici contact mesajını temizle
function clearTempContactMessage() {
    unset($_SESSION['temp_contact_message']);
}

// Redirect after login ayarla
function setRedirectAfterLogin($url) {
    $_SESSION['redirect_after_login'] = $url;
}

// Redirect after login al
function getRedirectAfterLogin() {
    return $_SESSION['redirect_after_login'] ?? null;
}

// Redirect after login temizle
function clearRedirectAfterLogin() {
    unset($_SESSION['redirect_after_login']);
}