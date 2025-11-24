<?php
/**
 * Kafkas Boya E-Ticaret Sitesi
 * Veritabanı Bağlantı Dosyası
 */

// Veritabanı Ayarları
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'kafkas_boya_db');

// MySQLi Bağlantısı Oluştur
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Bağlantı Kontrolü
if ($conn->connect_error) {
    die("Veritabanı bağlantı hatası: " . $conn->connect_error);
}

// UTF-8 Karakter Seti Ayarla
$conn->set_charset("utf8mb4");

// Hata Raporlamayı Etkinleştir (Geliştirme Aşamasında)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

?>
