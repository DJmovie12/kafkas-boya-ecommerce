<?php
// checkout.php
require_once 'includes/db_connect.php';
require_once 'includes/session.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Kullanıcının adres bilgisini veritabanından al
$user_stmt = $conn->prepare("SELECT address FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();
$user_stmt->close();

$user_address = $user_data['address'] ?? '';

// Sepetteki ürünleri al
$stmt = $conn->prepare("
    SELECT c.*, p.price, p.stock, p.name, p.image
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$cart_items = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Sepet boşsa cart.php'ye yönlendir
if (empty($cart_items)) {
    $_SESSION['error'] = 'Sepetiniz boş!';
    header('Location: cart.php');
    exit();
}

// Toplam hesapla
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$tax = $subtotal * 0.18; // %18 KDV
$total = $subtotal + $tax;

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $city = $_POST['city'] ?? '';
    $district = $_POST['district'] ?? '';
    $address = $_POST['address'] ?? '';
    $postal_code = $_POST['postal_code'] ?? '';
    $payment_method = $_POST['payment_method'] ?? '';
    
    // Basit validasyon
    if (empty($full_name) || empty($email) || empty($phone) || empty($city) || empty($district) || empty($address) || empty($postal_code) || empty($payment_method)) {
        $error = 'Lütfen tüm zorunlu alanları doldurun!';
    } else {
        try {
            // Transaction başlat
            $conn->begin_transaction();
            
            // Stok kontrolü yap
            $stock_errors = [];
            foreach ($cart_items as $item) {
                if ($item['quantity'] > $item['stock']) {
                    $stock_errors[] = "{$item['name']} için yeterli stok yok! Mevcut stok: {$item['stock']}";
                }
            }
            
            // Stok hatası varsa işlemi iptal et
            if (!empty($stock_errors)) {
                $conn->rollback();
                $error = implode('<br>', $stock_errors);
            } else {
                // Siparişi oluştur
                $stmt = $conn->prepare("
                    INSERT INTO orders (user_id, total_amount, status) 
                    VALUES (?, ?, 'pending')
                ");
                $stmt->bind_param("id", $user_id, $total);
                $stmt->execute();
                $order_id = $stmt->insert_id;
                $stmt->close();
                
                // Sipariş detaylarını ekle ve stokları güncelle
                foreach ($cart_items as $item) {
                    // Sipariş kalemi ekle
                    $stmt = $conn->prepare("
                        INSERT INTO order_items (order_id, product_id, quantity, price) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['price']);
                    $stmt->execute();
                    $stmt->close();
                    
                    // Stoku düş
                    $stmt = $conn->prepare("
                        UPDATE products 
                        SET stock = stock - ? 
                        WHERE id = ? AND stock >= ?
                    ");
                    $stmt->bind_param("iii", $item['quantity'], $item['product_id'], $item['quantity']);
                    $stmt->execute();
                    
                    if ($stmt->affected_rows === 0) {
                        throw new Exception("Stok güncellenirken hata oluştu: {$item['name']}");
                    }
                    $stmt->close();
                }
                
                // Sepeti temizle
                $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $stmt->close();
                
                // Transaction'ı tamamla
                $conn->commit();
                
                header('Location: /order-confirmation.php?order_id=' . $order_id);
                exit();
                
            }
        } catch (Exception $e) {
            $conn->rollback();
            $error = 'Sipariş oluşturulurken hata: ' . $e->getMessage();
        }
    }
}

require_once 'includes/header.php'; 
?>

    <!-- Page Header -->
    <section class="page-header bg-light py-5" style="margin-top: 70px;">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-2">
                            <li class="breadcrumb-item"><a href="/index.php"
                                    class="text-decoration-none text-primary">Ana Sayfa</a></li>
                            <li class="breadcrumb-item"><a href="/cart.php"
                                    class="text-decoration-none text-primary">Sepetim</a></li>
                            <li class="breadcrumb-item active">Ödeme</li>
                        </ol>
                    </nav>
                    <h1 class="display-5 fw-bold text-dark mb-0" style="font-family: 'Playfair Display', serif;">
                        Ödeme
                    </h1>
                </div>
            </div>
        </div>
    </section>

    <!-- Checkout Section -->
    <section class="py-5">
        <div class="container">
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Checkout Form -->
                <div class="col-lg-8">
                    <form method="POST" class="checkout-form">
                        <!-- Kişisel Bilgiler -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white border-bottom">
                                <h5 class="fw-bold mb-0">
                                    <i class="fas fa-user me-2 text-primary"></i>Kişisel Bilgiler
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="full_name" class="form-label fw-medium">Ad Soyad *</label>
                                        <input type="text" class="form-control" id="full_name" name="full_name" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label fw-medium">E-posta *</label>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="phone" class="form-label fw-medium">Telefon *</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Teslimat Adresi -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white border-bottom">
                                <h5 class="fw-bold mb-0">
                                    <i class="fas fa-map-marker-alt me-2 text-primary"></i>Teslimat Adresi
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="city" class="form-label fw-medium">İl *</label>
                                        <select class="form-select" id="city" name="city" required>
                                            <option value="">İl Seçin</option>
                                            <!-- İller API'den gelecek -->
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="district" class="form-label fw-medium">İlçe *</label>
                                        <select class="form-select" id="district" name="district" required disabled>
                                            <option value="">Önce il seçin</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label for="address" class="form-label fw-medium">Açık Adres *</label>
                                        <textarea class="form-control" id="address" name="address" rows="3" placeholder="Mahalle, sokak, apartman, daire no vb. detaylı adres bilgisi" required><?php echo htmlspecialchars($user_address); ?></textarea>
                                        <small class="text-muted">Teslimatın yapılacağı tam adresi yazın</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="postal_code" class="form-label fw-medium">Posta Kodu *</label>
                                        <input type="text" class="form-control" id="postal_code" name="postal_code" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Ödeme Yöntemi -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white border-bottom">
                                <h5 class="fw-bold mb-0">
                                    <i class="fas fa-credit-card me-2 text-primary"></i>Ödeme Yöntemi
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="radio" name="payment_method" id="credit_card" value="credit_card" required>
                                    <label class="form-check-label" for="credit_card">
                                        <i class="fas fa-credit-card me-2"></i>Kredi Kartı
                                    </label>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="radio" name="payment_method" id="debit_card" value="debit_card">
                                    <label class="form-check-label" for="debit_card">
                                        <i class="fas fa-credit-card me-2"></i>Banka Kartı
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="bank_transfer" value="bank_transfer">
                                    <label class="form-check-label" for="bank_transfer">
                                        <i class="fas fa-university me-2"></i>Banka Transferi
                                    </label>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="fas fa-check-circle me-2"></i>Siparişi Tamamla - ₺<?php echo number_format($total, 2, ',', '.'); ?>
                        </button>
                    </form>
                </div>

                <!-- Order Summary -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm sticky-top" style="top: 100px;">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="fw-bold mb-0">Sipariş Özeti</h5>
                        </div>
                        <div class="card-body">
                            <div class="summary-items mb-3" style="max-height: 300px; overflow-y: auto;">
                                <?php foreach ($cart_items as $item): ?>
                                    <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                                        <img src="<?php echo htmlspecialchars($item['image']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                             class="rounded me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                            <div class="d-flex justify-content-between">
                                                <small class="text-muted"><?php echo $item['quantity']; ?> adet × ₺<?php echo number_format($item['price'], 2); ?></small>
                                                <strong>₺<?php echo number_format($item['price'] * $item['quantity'], 2); ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <hr>

                            <div class="summary-row d-flex justify-content-between mb-2">
                                <span class="text-muted">Ara Toplam:</span>
                                <span class="fw-bold">₺<?php echo number_format($subtotal, 2, ',', '.'); ?></span>
                            </div>
                            <div class="summary-row d-flex justify-content-between mb-2">
                                <span class="text-muted">Kargo:</span>
                                <span class="fw-bold text-success">Ücretsiz</span>
                            </div>
                            <div class="summary-row d-flex justify-content-between mb-3">
                                <span class="text-muted">KDV (18%):</span>
                                <span class="fw-bold">₺<?php echo number_format($tax, 2, ',', '.'); ?></span>
                            </div>

                            <hr>

                            <div class="summary-row d-flex justify-content-between">
                                <span class="fw-bold">Toplam:</span>
                                <span class="fw-bold text-primary" style="font-size: 1.25rem;">₺<?php echo number_format($total, 2, ',', '.'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- JavaScript for City-District API -->
    <script>
        // İlleri API'den çek
        async function loadCities() {
            try {
                const response = await fetch('https://turkiyeapi.dev/api/v1/provinces');
                const data = await response.json();
                
                const citySelect = document.getElementById('city');
                
                // İlleri alfabetik sırala ve select'e ekle
                data.data.sort((a, b) => a.name.localeCompare(b.name)).forEach(city => {
                    const option = document.createElement('option');
                    option.value = city.name;
                    option.textContent = city.name;
                    citySelect.appendChild(option);
                });
            } catch (error) {
                console.error('İller yüklenirken hata:', error);
                // Fallback: Manuel il listesi
                loadFallbackCities();
            }
        }

        // İlçeleri API'den çek
        async function loadDistricts(cityName) {
            try {
                const response = await fetch(`https://turkiyeapi.dev/api/v1/provinces?name=${encodeURIComponent(cityName)}`);
                const data = await response.json();
                
                const districtSelect = document.getElementById('district');
                
                // Önceki ilçeleri temizle
                districtSelect.innerHTML = '<option value="">İlçe Seçin</option>';
                districtSelect.disabled = false;
                
                if (data.data && data.data.length > 0) {
                    const city = data.data[0];
                    // İlçeleri alfabetik sırala ve select'e ekle
                    city.districts.sort((a, b) => a.name.localeCompare(b.name)).forEach(district => {
                        const option = document.createElement('option');
                        option.value = district.name;
                        option.textContent = district.name;
                        districtSelect.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('İlçeler yüklenirken hata:', error);
                // Fallback: Manuel ilçe listesi
                loadFallbackDistricts(cityName);
            }
        }

        // Fallback: Manuel il listesi (API çalışmazsa)
        function loadFallbackCities() {
            const cities = [
                'Adana', 'Adıyaman', 'Afyonkarahisar', 'Ağrı', 'Amasya', 'Ankara', 'Antalya', 'Artvin',
                'Aydın', 'Balıkesir', 'Bilecik', 'Bingöl', 'Bitlis', 'Bolu', 'Burdur', 'Bursa', 'Çanakkale',
                'Çankırı', 'Çorum', 'Denizli', 'Diyarbakır', 'Edirne', 'Elazığ', 'Erzincan', 'Erzurum',
                'Eskişehir', 'Gaziantep', 'Giresun', 'Gümüşhane', 'Hakkari', 'Hatay', 'Isparta', 'Mersin',
                'İstanbul', 'İzmir', 'Kars', 'Kastamonu', 'Kayseri', 'Kırklareli', 'Kırşehir', 'Kocaeli',
                'Konya', 'Kütahya', 'Malatya', 'Manisa', 'Kahramanmaraş', 'Mardin', 'Muğla', 'Muş', 'Nevşehir',
                'Niğde', 'Ordu', 'Rize', 'Sakarya', 'Samsun', 'Siirt', 'Sinop', 'Sivas', 'Tekirdağ', 'Tokat',
                'Trabzon', 'Tunceli', 'Şanlıurfa', 'Uşak', 'Van', 'Yozgat', 'Zonguldak', 'Aksaray', 'Bayburt',
                'Karaman', 'Kırıkkale', 'Batman', 'Şırnak', 'Bartın', 'Ardahan', 'Iğdır', 'Yalova', 'Karabük',
                'Kilis', 'Osmaniye', 'Düzce'
            ];
            
            const citySelect = document.getElementById('city');
            cities.sort().forEach(city => {
                const option = document.createElement('option');
                option.value = city;
                option.textContent = city;
                citySelect.appendChild(option);
            });
        }

        // Fallback: Manuel ilçe listesi (API çalışmazsa)
        function loadFallbackDistricts(cityName) {
            // Basit bir fallback - sadece büyük şehirler için
            const fallbackDistricts = {
                'İstanbul': ['Adalar', 'Arnavutköy', 'Ataşehir', 'Avcılar', 'Bağcılar', 'Bahçelievler', 'Bakırköy', 'Başakşehir'],
                'Ankara': ['Altındağ', 'Çankaya', 'Keçiören', 'Mamak', 'Sincan', 'Yenimahalle'],
                'İzmir': ['Bornova', 'Buca', 'Karşıyaka', 'Konak', 'Bayraklı', 'Çiğli'],
                'Bursa': ['Osmangazi', 'Yıldırım', 'Nilüfer', 'Gemlik', 'Gürsu'],
                'Antalya': ['Kepez', 'Muratpaşa', 'Konyaaltı', 'Aksu']
            };
            
            const districtSelect = document.getElementById('district');
            districtSelect.innerHTML = '<option value="">İlçe Seçin</option>';
            districtSelect.disabled = false;
            
            if (fallbackDistricts[cityName]) {
                fallbackDistricts[cityName].forEach(district => {
                    const option = document.createElement('option');
                    option.value = district;
                    option.textContent = district;
                    districtSelect.appendChild(option);
                });
            } else {
                const option = document.createElement('option');
                option.value = 'Merkez';
                option.textContent = 'Merkez';
                districtSelect.appendChild(option);
            }
        }

        // Sayfa yüklendiğinde illeri çek
        document.addEventListener('DOMContentLoaded', function() {
            loadCities();
            
            // İl seçildiğinde ilçeleri çek
            document.getElementById('city').addEventListener('change', function() {
                const city = this.value;
                if (city) {
                    loadDistricts(city);
                } else {
                    document.getElementById('district').innerHTML = '<option value="">Önce il seçin</option>';
                    document.getElementById('district').disabled = true;
                }
            });
        });
    </script>

<?php require_once 'includes/footer.php'; ?>