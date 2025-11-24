-- Kafkas Boya E-Ticaret Sitesi - Veritabanı Kurulum Betiği
-- Bu betiği phpMyAdmin'de çalıştırarak tüm tabloları ve örnek verileri oluşturabilirsiniz.

-- Veritabanını oluştur (eğer yoksa)
CREATE DATABASE IF NOT EXISTS kafkas_boya_db;
USE kafkas_boya_db;

-- ========================================
-- MARKALAR TABLOSU
-- ========================================
CREATE TABLE IF NOT EXISTS brands (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    logo_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- KATEGORİLER TABLOSU
-- ========================================
CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- ÜRÜNLER TABLOSU
-- ========================================
CREATE TABLE IF NOT EXISTS products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    brand_id INT,
    category_id INT,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE SET NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_brand (brand_id),
    INDEX idx_category (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- KULLANICILAR TABLOSU
-- ========================================
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- SEPET TABLOSU
-- ========================================
CREATE TABLE IF NOT EXISTS cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart (user_id, product_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- SİPARİŞLER TABLOSU
-- ========================================
CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- SİPARİŞ ÖĞELERİ TABLOSU
-- ========================================
CREATE TABLE IF NOT EXISTS order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- ÖRNEK VERİLER
-- ========================================

-- Markalar
INSERT INTO brands (name, description) VALUES
('Polisan', 'Premium kaliteli boya ve vernik ürünleri'),
('Filli Boya', 'Geniş renk paletine sahip profesyonel boya'),
('Marshall', 'Dayanıklı ve uzun ömürlü boya çözümleri'),
('DYO', 'Ekonomik ve kaliteli boya seçenekleri'),
('Permolit', 'Profesyonel ve endüstriyel boya ürünleri');

-- Kategoriler
INSERT INTO categories (name, description) VALUES
('İç Cephe', 'İç mekan boyama ürünleri'),
('Dış Cephe', 'Dış mekan boyama ürünleri'),
('Tavan', 'Tavan boyama ürünleri'),
('Ahşap & Metal', 'Ahşap ve metal yüzeyler için boya'),
('Astar', 'Astar ve hazırlık ürünleri');

-- Ürünler (örnek ürünler)
INSERT INTO products (name, description, price, stock, brand_id, category_id, image) VALUES
('Polisan Premium İç Cephe Boyası 1L', 'Yüksek kaliteli, mat bitişli iç cephe boyası', 150.00, 50, 1, 1, 'assets/img/products/shop_01.webp'),
('Polisan Premium İç Cephe Boyası 2.5L', 'Yüksek kaliteli, mat bitişli iç cephe boyası', 350.00, 40, 1, 1, 'assets/img/products/shop_02.webp'),
('Polisan Premium Dış Cephe Boyası 5L', 'Dayanıklı dış cephe boyası, UV korumalı', 800.00, 30, 1, 2, 'assets/img/products/shop_03.webp'),
('Filli Boya İç Cephe 1L', 'Geniş renk seçeneği ile iç cephe boyası', 120.00, 60, 2, 1, 'assets/img/products/shop_04.webp'),
('Filli Boya İç Cephe 2.5L', 'Geniş renk seçeneği ile iç cephe boyası', 280.00, 50, 2, 1, 'assets/img/products/shop_05.webp'),
('Marshall Dış Cephe 5L', 'Uzun ömürlü dış cephe koruma boyası', 750.00, 25, 3, 2, 'assets/img/products/shop_06.webp'),
('Marshall Tavan Boyası 1L', 'Tavan boyama için özel formülasyon', 130.00, 45, 3, 3, 'assets/img/products/shop_07.webp'),
('DYO İç Cephe Ekonomik 1L', 'Ekonomik fiyatla kaliteli iç cephe boyası', 90.00, 80, 4, 1, 'assets/img/products/shop_08.webp'),
('DYO İç Cephe Ekonomik 2.5L', 'Ekonomik fiyatla kaliteli iç cephe boyası', 210.00, 70, 4, 1, 'assets/img/products/shop_09.webp'),
('Permolit Ahşap Boyası 1L', 'Ahşap yüzeyler için koruyucu boya', 180.00, 35, 5, 4, 'assets/img/products/shop_10.webp'),
('Permolit Metal Boyası 1L', 'Metal yüzeyler için paslanmaz boya', 200.00, 30, 5, 4, 'assets/img/products/shop_11.webp'),
('Polisan Astar 1L', 'Yüzey hazırlığı için astar boyası', 110.00, 55, 1, 5, 'assets/img/products/polisan-astar-1l.jpg');

-- Admin Kullanıcısı (Şifre: admin123)
INSERT INTO users (username, email, password, role) VALUES
('admin', 'admin@kafkasboya.com', '$2y$10$YIjlrDflS5Z.PLQVVc8p6OPST9/PgBkqquzi.Ss7KIUgO2nQcRHSm', 'admin');

-- Test Kullanıcısı (Şifre: test123)
INSERT INTO users (username, email, password, role) VALUES
('testuser', 'test@example.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36P4/KFm', 'user');

-- ========================================
-- VERİTABANI KURULUMU TAMAMLANDI
-- ========================================
-- Admin Giriş Bilgileri:
-- Kullanıcı Adı: admin
-- E-posta: admin@kafkasboya.com
-- Şifre: admin123
-- 
-- Test Kullanıcı Giriş Bilgileri:
-- Kullanıcı Adı: testuser
-- E-posta: test@example.com
-- Şifre: test123
