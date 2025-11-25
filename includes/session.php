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
 * Kullanıcının toplam sepet öğesi sayısını döndürür.
 * @param int $user_id
 * @param mysqli $conn
 * @return int
 */
function getCartItemCount($user_id, $conn) {
    if (!isset($conn) || !$conn) {
        return 0; // Bağlantı yoksa 0 döndür
    }
    $count = 0;
    // Prepared statement ile güvenli sorgu
    if ($stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?")) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $count = $row['total'] ?? 0;
        $stmt->close();
    }
    return $count;
}