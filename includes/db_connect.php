<?php
/**
 * Kafkas Boya E-Ticaret Sitesi
 * Veritabanı Bağlantı Dosyası
 */

// Veritabanı Ayarları
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
// Veritabanı adınızı teyit edin. Eğer kafkas_boya_db değilse, doğru olanı yazın.
define('DB_NAME', 'kafkas_boya_db'); 


// MySQLi Bağlantısı Oluştur
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Bağlantı Kontrolü (DÜZELTME: die() komutu kaldırıldı)
if ($conn->connect_error) {
    // Bağlantı hatası durumunda, die() kullanmak yerine hatayı logla
    // ve $conn değişkenini null yap. Bu, hiçbir HTML çıktısı vermez.
    error_log("Veritabanı bağlantı hatası: " . $conn->connect_error);
    $conn = null; 
    
} else {
    // UTF-8 Karakter Seti Ayarla
    $conn->set_charset("utf8mb4");

    // Hata Raporlamayı Etkinleştir (Geliştirme Aşamasında)
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
}