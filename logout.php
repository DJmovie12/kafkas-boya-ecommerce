<?php
require_once 'includes/db_connect.php';
require_once 'includes/session.php';
require_once 'includes/security.php';

// Güvenlik header'larını ayarla
set_security_headers();

// "Beni Hatırla" token'ını temizle
if (isset($_COOKIE['remember_token'])) {
    $token = secure_input($_COOKIE['remember_token']);
    
    $stmt = $conn->prepare("DELETE FROM remember_tokens WHERE token = ?");
    if ($stmt) {
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $stmt->close();
    }
    
    setcookie('remember_token', '', time() - 3600, '/', '', true, true);
}

// Tüm session'ı temizle ve yeni başlat
session_destroy();

// Yeni session başlat
session_start();

// Başarı mesajını göster
$_SESSION['logout_success'] = "Başarıyla çıkış yaptınız. Tekrar görüşmek üzere!";

// Ana sayfaya yönlendir
header("Location: index.php");
exit();
?>