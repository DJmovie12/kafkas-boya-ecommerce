# Kafkas Boya E-Ticaret Sitesi - Kurulum Talimatları

## 📋 Gereksinimler

- PHP 7.4 veya üzeri
- MySQL 5.7 veya üzeri
- phpMyAdmin (veritabanı yönetimi için)
- Web sunucusu (Apache, Nginx vb.)

## 🚀 Kurulum Adımları

### 1. Dosyaları Sunucuya Yükleyin

Tüm dosyaları web sunucunuzun kök dizinine (örneğin `/var/www/html/` veya `C:\xampp\htdocs\`) yükleyin.

```
kafkas_boya/
├── index.php
├── shop.php
├── shop-single.php
├── login.php
├── register.php
├── cart.php
├── checkout.php
├── order-confirmation.php
├── logout.php
├── includes/
│   ├── db_connect.php
│   ├── session.php
│   ├── header.php
│   └── footer.php
├── admin/
│   ├── dashboard.php
│   └── products.php
├── api/
│   ├── cart-update.php
│   └── cart-remove.php
├── assets/
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   ├── main.js
│   │   └── shop.js
│   └── img/
│       └── (resim dosyaları)
└── database_setup.sql
```

### 2. Veritabanını Oluşturun

#### Seçenek A: phpMyAdmin Kullanarak

1. phpMyAdmin'i açın (`http://localhost/phpmyadmin`)
2. Sol taraftan "Yeni" veya "+" butonuna tıklayın
3. Veritabanı adı olarak `kafkas_boya_db` yazın
4. "Oluştur" butonuna tıklayın
5. Oluşturulan veritabanını seçin
6. Üst menüden "SQL" sekmesine tıklayın
7. `database_setup.sql` dosyasının içeriğini kopyalayıp yapıştırın
8. "Çalıştır" butonuna tıklayın

#### Seçenek B: Komut Satırı Kullanarak

```bash
mysql -u root -p < database_setup.sql
```

### 3. Veritabanı Bağlantısını Yapılandırın

`includes/db_connect.php` dosyasını açıp aşağıdaki bilgileri kendi sunucunuza göre düzenleyin:

```php
define('DB_HOST', 'localhost');      // Veritabanı sunucusu
define('DB_USER', 'root');           // Veritabanı kullanıcısı
define('DB_PASS', '');               // Veritabanı şifresi
define('DB_NAME', 'kafkas_boya_db'); // Veritabanı adı
```

### 4. Dosya İzinlerini Ayarlayın

Sunucunuz Linux/Unix tabanlıysa, aşağıdaki komutları çalıştırın:

```bash
chmod 755 /var/www/html/kafkas_boya
chmod 755 /var/www/html/kafkas_boya/includes
chmod 755 /var/www/html/kafkas_boya/admin
chmod 755 /var/www/html/kafkas_boya/api
```

### 5. Siteyi Test Edin

Tarayıcıda aşağıdaki adresleri ziyaret edin:

- **Ana Sayfa**: `http://localhost/kafkas_boya/index.php`
- **Ürünler**: `http://localhost/kafkas_boya/shop.php`
- **Kullanıcı Girişi**: `http://localhost/kafkas_boya/login.php`
- **Admin Paneli**: `http://localhost/kafkas_boya/admin/dashboard.php`

## 👤 Varsayılan Giriş Bilgileri

### Admin Hesabı
- **Kullanıcı Adı**: admin
- **E-posta**: admin@kafkasboya.com
- **Şifre**: admin123

### Test Kullanıcısı
- **Kullanıcı Adı**: testuser
- **E-posta**: test@example.com
- **Şifre**: test123

## 📁 Dosya Yapısı Açıklaması

### Temel Dosyalar

| Dosya | Açıklama |
|-------|----------|
| `index.php` | Ana sayfa |
| `shop.php` | Ürün listeleme sayfası |
| `shop-single.php` | Ürün detay sayfası |
| `login.php` | Kullanıcı girişi |
| `register.php` | Üye kaydı |
| `cart.php` | Sepet sayfası |
| `checkout.php` | Ödeme sayfası |
| `order-confirmation.php` | Sipariş onay sayfası |
| `logout.php` | Çıkış yapma |

### Include Dosyaları

| Dosya | Açıklama |
|-------|----------|
| `includes/db_connect.php` | Veritabanı bağlantısı |
| `includes/session.php` | Oturum yönetimi |
| `includes/header.php` | Sayfa başlığı (navigasyon) |
| `includes/footer.php` | Sayfa altı (footer) |

### Admin Dosyaları

| Dosya | Açıklama |
|-------|----------|
| `admin/dashboard.php` | Admin paneli ana sayfası |
| `admin/products.php` | Ürün yönetimi (CRUD) |

### API Dosyaları

| Dosya | Açıklama |
|-------|----------|
| `api/cart-update.php` | Sepet güncelleme |
| `api/cart-remove.php` | Sepetten ürün silme |

## 🔐 Güvenlik Önerileri

1. **Şifreleri Değiştirin**: İlk kurulumdan sonra admin ve test kullanıcılarının şifrelerini değiştirin.

2. **HTTPS Kullanın**: Üretim ortamında her zaman HTTPS protokolünü kullanın.

3. **Veritabanı Yedeklemesi**: Düzenli olarak veritabanı yedeklemesi yapın.

4. **Dosya İzinleri**: Hassas dosyaların (db_connect.php) okuma izinlerini sınırlayın.

5. **SQL Injection Koruması**: Tüm SQL sorgularında prepared statements kullanılmıştır.

6. **XSS Koruması**: Tüm çıktılar `htmlspecialchars()` ile temizlenmiştir.

## 🛠️ Özelleştirme

### Logo Değiştirme

1. `assets/img/` klasörüne yeni logo dosyanızı yükleyin
2. `includes/header.php` dosyasında logo yolunu güncelleyin

### Renk Şeması Değiştirme

1. `assets/css/style.css` dosyasını açın
2. Renk değerlerini kendi tercihlerinize göre değiştirin

### Markaları ve Kategorileri Yönetme

1. Admin paneline giriş yapın
2. İlgili yönetim sayfalarından ekle/düzenle/sil işlemleri yapın

## 📧 İletişim ve Destek

Herhangi bir sorun veya soru için lütfen iletişime geçiniz.

## 📝 Lisans

Bu proje Kafkas Boya için özel olarak geliştirilmiştir.

---

**Kurulum Tamamlandı!** Siteyi ziyaret etmek için tarayıcınızda `http://localhost/kafkas_boya/` adresine gidin.
