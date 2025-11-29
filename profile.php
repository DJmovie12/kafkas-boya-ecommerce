<?php
// 1. Mantık ve Kontroller
require_once 'includes/db_connect.php';
require_once 'includes/session.php';

$page_title = "Profilim";

// Giriş yapmamışsa yönlendir
if (!isUserLoggedIn()) {
    header("Location: /login.php?redirect=profile.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Profil Güncelleme İşlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    
    if (empty($username) || empty($email)) {
        $error = 'Kullanıcı adı ve e-posta boş bırakılamaz.';
    } else {
        // Kullanıcı adı veya e-posta başka bir kullanıcıda var mı kontrol et
        $check_sql = "SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?";
        if ($stmt = $conn->prepare($check_sql)) {
            $stmt->bind_param("ssi", $username, $email, $user_id);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows > 0) {
                $error = 'Bu kullanıcı adı veya e-posta zaten kullanımda.';
            } else {
                // Güncelleme yap
                $update_sql = "UPDATE users SET username = ?, email = ? WHERE id = ?";
                if ($update_stmt = $conn->prepare($update_sql)) {
                    $update_stmt->bind_param("ssi", $username, $email, $user_id);
                    if ($update_stmt->execute()) {
                        $_SESSION['username'] = $username; // Session'ı da güncelle
                        $_SESSION['email'] = $email;
                        $message = 'Profil bilgileriniz başarıyla güncellendi.';
                    } else {
                        $error = 'Güncelleme sırasında bir hata oluştu.';
                    }
                    $update_stmt->close();
                }
            }
            $stmt->close();
        }
    }
}

// Şifre Değiştirme İşlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'Tüm şifre alanlarını doldurunuz.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Yeni şifreler eşleşmiyor.';
    } elseif (strlen($new_password) < 6) {
        $error = 'Yeni şifre en az 6 karakter olmalıdır.';
    } else {
        // Mevcut şifreyi kontrol et
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        if (password_verify($current_password, $user['password'])) {
            // Şifreyi güncelle
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($update_stmt->execute()) {
                $message = 'Şifreniz başarıyla değiştirildi.';
            } else {
                $error = 'Şifre değiştirilirken bir hata oluştu.';
            }
            $update_stmt->close();
        } else {
            $error = 'Mevcut şifreniz hatalı.';
        }
    }
}

// Kullanıcı Bilgilerini Çek
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Tüm Siparişleri Çek
$orders_sql = "SELECT o.*, 
               (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count 
               FROM orders o 
               WHERE o.user_id = ? 
               ORDER BY o.created_at DESC";
$orders_stmt = $conn->prepare($orders_sql);
$orders_stmt->bind_param("i", $user_id);
$orders_stmt->execute();
$orders_result = $orders_stmt->get_result();
$all_orders = [];
while ($row = $orders_result->fetch_assoc()) {
    $all_orders[] = $row;
}
$orders_stmt->close();

// Kullanıcının İletişim Mesajlarını ve Yanıtları Çek
$contacts_sql = "SELECT c.*, cr.reply_message, cr.created_at as reply_date, u.username as admin_name 
                 FROM contacts c 
                 LEFT JOIN contact_replies cr ON c.id = cr.contact_id 
                 LEFT JOIN users u ON cr.admin_id = u.id 
                 WHERE c.user_id = ? 
                 ORDER BY c.created_at DESC";
$stmt = $conn->prepare($contacts_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_contacts = $stmt->get_result();
$stmt->close();

$active_tab = $_GET['tab'] ?? 'profile';

// 2. Header Dahil Et
require_once 'includes/header.php';
?>
<style>
    /* Profile Page Mobile Responsive Styles */
@media (max-width: 991px) {
    .profile-mobile-header {
        background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        color: white;
        padding: 1rem 0;
        margin-bottom: 1rem;
    }
    
    .profile-mobile-tabs .btn {
        font-size: 0.8rem;
        padding: 0.5rem 0.25rem;
    }
    
    .profile-mobile-tabs .btn i {
        font-size: 0.9rem;
    }
    
    /* Tab içerikleri için mobil uyumluluk */
    .tab-content .card {
        margin-bottom: 1rem;
    }
    
    .tab-content .table th,
    .tab-content .table td {
        padding: 0.75rem 0.5rem;
        font-size: 0.875rem;
    }
    
    .tab-content .btn {
        font-size: 0.875rem;
        padding: 0.5rem 1rem;
    }
}

@media (max-width: 576px) {
    .profile-mobile-tabs .btn {
        font-size: 0.75rem;
        padding: 0.4rem 0.2rem;
    }
    
    .profile-mobile-tabs .btn span {
        font-size: 0.7rem;
    }
    
    .tab-content .table {
        font-size: 0.8rem;
    }
    
    .tab-content .badge {
        font-size: 0.7rem;
    }
    
    /* Form elemanları için mobil uyumluluk */
    .card-body .form-control {
        font-size: 0.875rem;
    }
    
    .card-body .btn {
        font-size: 0.875rem;
    }
}

/* Genel mobil iyileştirmeler */
@media (max-width: 991px) {
    .container {
        padding-left: 10px;
        padding-right: 10px;
    }
    
    .row.g-4 {
        margin-left: -5px;
        margin-right: -5px;
    }
    
    .col-12 {
        padding-left: 5px;
        padding-right: 5px;
    }
}
</style>
    <!-- Page Header -->
    <section class="page-header bg-light py-5" style="margin-top: 70px;">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-2">
                            <li class="breadcrumb-item"><a href="/index.php" class="text-decoration-none text-primary">Ana Sayfa</a></li>
                            <li class="breadcrumb-item active">Hesabım</li>
                        </ol>
                    </nav>
                    <h1 class="display-5 fw-bold text-dark mb-0" style="font-family: 'Playfair Display', serif;">
                        Merhaba, <?php echo htmlspecialchars($user_data['username']); ?>
                    </h1>
                </div>
            </div>
        </div>
    </section>

    <!-- Profile Section -->
    <section class="py-5">
        <div class="container">
            
            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Mobile Profile Header - 991px ve altında görünecek -->
                <div class="col-12 d-lg-none mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center p-3">
                            <div class="mb-2">
                                <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle" style="width: 60px; height: 60px;">
                                    <i class="fas fa-user fa-lg text-light"></i>
                                </div>
                            </div>
                            <h6 class="fw-bold mb-1 small"><?php echo htmlspecialchars($user_data['username']); ?></h6>
                            <p class="text-muted small mb-0"><?php echo htmlspecialchars($user_data['email']); ?></p>
                            <p class="text-muted small mt-1">Üyelik: <?php echo date('d.m.Y', strtotime($user_data['created_at'])); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Mobile Navigation Tabs - 991px ve altında görünecek -->
                <div class="col-12 d-lg-none mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-2">
                            <div class="row g-1 text-center">
                                <div class="col-4">
                                    <a href="?tab=profile" class="btn btn-sm w-100 <?php echo $active_tab === 'profile' ? 'btn-primary text-white' : 'btn-outline-primary'; ?>">
                                        <i class="fas fa-user me-1 d-none d-sm-inline"></i>
                                        <span class="small">Profil</span>
                                    </a>
                                </div>
                                <div class="col-4">
                                    <a href="?tab=orders" class="btn btn-sm w-100 <?php echo $active_tab === 'orders' ? 'btn-primary text-white' : 'btn-outline-primary'; ?>">
                                        <i class="fas fa-shopping-bag me-1 d-none d-sm-inline"></i>
                                        <span class="small">Siparişler</span>
                                    </a>
                                </div>
                                <div class="col-4">
                                    <a href="?tab=messages" class="btn btn-sm w-100 <?php echo $active_tab === 'messages' ? 'btn-primary text-white' : 'btn-outline-primary'; ?>">
                                        <i class="fas fa-envelope me-1 d-none d-sm-inline"></i>
                                        <span class="small">Mesajlar</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Desktop Sidebar Menu - 992px ve üzerinde görünecek -->
                <div class="col-lg-3 d-none d-lg-block">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body text-center p-4">
                            <div class="mb-3">
                                <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle" style="width: 80px; height: 80px;">
                                    <i class="fas fa-user fa-2x text-light"></i>
                                </div>
                            </div>
                            <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($user_data['username']); ?></h5>
                            <p class="text-muted small mb-0"><?php echo htmlspecialchars($user_data['email']); ?></p>
                            <p class="text-muted small mt-2">Üyelik Tarihi: <?php echo date('d.m.Y', strtotime($user_data['created_at'])); ?></p>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm">
                        <div class="list-group list-group-flush">
                            <a href="?tab=profile" class="list-group-item list-group-item-action py-3 <?php echo $active_tab === 'profile' ? 'active bg-primary text-white' : 'border-0'; ?>">
                                <i class="fas fa-user me-2"></i> Profil Bilgilerim
                            </a>
                            <a href="?tab=orders" class="list-group-item list-group-item-action py-3 <?php echo $active_tab === 'orders' ? 'active bg-primary text-white' : 'border-0'; ?>">
                                <i class="fas fa-shopping-bag me-2"></i> Siparişlerim
                            </a>
                            <a href="?tab=messages" class="list-group-item list-group-item-action py-3 <?php echo $active_tab === 'messages' ? 'active bg-primary text-white' : 'border-0'; ?>">
                                <i class="fas fa-envelope me-2"></i> İletişim Mesajlarım
                            </a>
                            <a href="/logout.php" class="list-group-item list-group-item-action py-3 text-danger border-0">
                                <i class="fas fa-sign-out-alt me-2"></i> Çıkış Yap
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="col-lg-9 col-12">
                    <!-- Profile Info Tab -->
                    <div id="profileTab" class="tab-content <?php echo $active_tab === 'profile' ? '' : 'd-none'; ?>">
                        <!-- Recent Orders Preview -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                                <h5 class="mb-0 fw-bold">Son Siparişlerim</h5>
                                <a href="?tab=orders" class="text-decoration-none small fw-bold">Tümünü Gör <i class="fas fa-arrow-right ms-1"></i></a>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="ps-4">Sipariş No</th>
                                                <th class="d-none d-md-table-cell">Tarih</th>
                                                <th>Tutar</th>
                                                <th>Durum</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($all_orders) > 0): ?>
                                                <?php $recent_orders = array_slice($all_orders, 0, 5); ?>
                                                <?php foreach($recent_orders as $order): ?>
                                                    <tr>
                                                        <td class="ps-4 fw-bold">#<?php echo $order['id']; ?></td>
                                                        <td class="text-muted small d-none d-md-table-cell"><?php echo date('d.m.Y', strtotime($order['created_at'])); ?></td>
                                                        <td class="fw-bold text-primary">₺<?php echo number_format($order['total_amount'], 2, ',', '.'); ?></td>
                                                        <td>
                                                            <?php 
                                                            $statusColors = [
                                                                'pending' => 'bg-warning text-dark',
                                                                'processing' => 'bg-info text-white',
                                                                'shipped' => 'bg-primary',
                                                                'delivered' => 'bg-success',
                                                                'cancelled' => 'bg-danger'
                                                            ];
                                                            $statusTexts = [
                                                                'pending' => 'Bekliyor',
                                                                'processing' => 'Hazırlanıyor',
                                                                'shipped' => 'Kargolandı',
                                                                'delivered' => 'Teslim Edildi',
                                                                'cancelled' => 'İptal Edildi'
                                                            ];
                                                            $status = $order['status'] ?? 'pending';
                                                            ?>
                                                            <span class="badge rounded-pill <?php echo $statusColors[$status] ?? 'bg-secondary'; ?>">
                                                                <?php echo $statusTexts[$status] ?? ucfirst($status); ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr><td colspan="4" class="text-center py-4 text-muted">Henüz siparişiniz bulunmuyor.</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="row g-4">
                            <!-- Update Profile Info -->
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-header bg-white py-3">
                                        <h5 class="mb-0 fw-bold">Bilgilerimi Güncelle</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST">
                                            <div class="mb-3">
                                                <label class="form-label text-muted small">Kullanıcı Adı</label>
                                                <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($user_data['username']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label text-muted small">E-posta Adresi</label>
                                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                                            </div>
                                            <button type="submit" name="update_profile" class="btn btn-primary w-100">
                                                <i class="fas fa-save me-2"></i>Değişiklikleri Kaydet
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Change Password -->
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-header bg-white py-3">
                                        <h5 class="mb-0 fw-bold">Şifre Değiştir</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST">
                                            <div class="mb-3">
                                                <label class="form-label text-muted small">Mevcut Şifre</label>
                                                <input type="password" name="current_password" class="form-control" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label text-muted small">Yeni Şifre</label>
                                                <input type="password" name="new_password" class="form-control" required minlength="6">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label text-muted small">Yeni Şifre (Tekrar)</label>
                                                <input type="password" name="confirm_password" class="form-control" required minlength="6">
                                            </div>
                                            <button type="submit" name="change_password" class="btn btn-outline-primary w-100">
                                                <i class="fas fa-key me-2"></i>Şifreyi Güncelle
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Orders Tab -->
                    <div id="ordersTab" class="tab-content <?php echo $active_tab === 'orders' ? '' : 'd-none'; ?>">
                        <?php if (empty($all_orders)): ?>
                            <div class="text-center py-5 bg-light rounded-3">
                                <div class="mb-4">
                                    <i class="fas fa-shopping-basket fa-4x text-muted opacity-50"></i>
                                </div>
                                <h3 class="fw-bold text-dark">Henüz Siparişiniz Yok</h3>
                                <p class="text-muted mb-4">Hemen alışverişe başlayıp ilk siparişinizi oluşturun.</p>
                                <a href="/shop.php" class="btn btn-primary btn-lg px-5">Alışverişe Başla</a>
                            </div>
                        <?php else: ?>
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-white py-3">
                                    <h5 class="mb-0 fw-bold">Toplam <?php echo count($all_orders); ?> Sipariş</h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th class="ps-4 py-3">Sipariş No</th>
                                                    <th class="py-3 d-none d-md-table-cell">Tarih</th>
                                                    <th class="py-3">Tutar</th>
                                                    <th class="py-3">Durum</th>
                                                    <th class="py-3 text-end pe-4">İşlem</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($all_orders as $order): ?>
                                                    <tr>
                                                        <td class="ps-4 fw-bold">#<?php echo $order['id']; ?></td>
                                                        <td class="text-muted d-none d-md-table-cell">
                                                            <i class="far fa-calendar-alt me-1"></i>
                                                            <?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?>
                                                        </td>
                                                        <td class="fw-bold text-primary">
                                                            ₺<?php echo number_format($order['total_amount'], 2, ',', '.'); ?>
                                                        </td>
                                                        <td>
                                                            <?php 
                                                            $status = $order['status'] ?? 'pending';
                                                            ?>
                                                            <span class="badge rounded-pill <?php echo $statusColors[$status] ?? 'bg-secondary'; ?>">
                                                                <?php echo $statusTexts[$status] ?? ucfirst($status); ?>
                                                            </span>
                                                        </td>
                                                        <td class="text-end pe-4">
                                                            <a href="#" class="btn btn-sm btn-outline-primary view-order-details" data-bs-toggle="modal" data-bs-target="#orderModal<?php echo $order['id']; ?>">
                                                                <span class="d-none d-sm-inline">Detaylar</span>
                                                                <i class="fas fa-chevron-right ms-1"></i>
                                                            </a>
                                                        </td>
                                                    </tr>

                                                    <!-- Order Detail Modal -->
                                                    <div class="modal fade" id="orderModal<?php echo $order['id']; ?>" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog modal-lg modal-dialog-centered">
                                                            <div class="modal-content">
                                                                <div class="modal-header border-bottom-0">
                                                                    <h5 class="modal-title fw-bold">Sipariş Detayı #<?php echo $order['id']; ?></h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <!-- Mobile Order Info -->
                                                                    <div class="d-lg-none mb-3 p-3 bg-light rounded">
                                                                        <div class="row text-center">
                                                                            <div class="col-6">
                                                                                <small class="text-muted d-block">Tarih</small>
                                                                                <strong><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></strong>
                                                                            </div>
                                                                            <div class="col-6">
                                                                                <small class="text-muted d-block">Durum</small>
                                                                                <span class="badge rounded-pill <?php echo $statusColors[$status] ?? 'bg-secondary'; ?>">
                                                                                    <?php echo $statusTexts[$status] ?? ucfirst($status); ?>
                                                                                </span>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    <!-- Sipariş Ürünlerini Çek -->
                                                                    <?php
                                                                    $item_sql = "SELECT oi.*, p.name, p.image 
                                                                                 FROM order_items oi 
                                                                                 JOIN products p ON oi.product_id = p.id 
                                                                                 WHERE oi.order_id = ?";
                                                                    $item_stmt = $conn->prepare($item_sql);
                                                                    $item_stmt->bind_param("i", $order['id']);
                                                                    $item_stmt->execute();
                                                                    $items_result = $item_stmt->get_result();
                                                                    ?>
                                                                    
                                                                    <div class="list-group list-group-flush mb-3">
                                                                        <?php while($item = $items_result->fetch_assoc()): ?>
                                                                            <div class="list-group-item d-flex align-items-center py-3 px-0 border-bottom">
                                                                                <div class="flex-shrink-0">
                                                                                    <img src="<?php echo htmlspecialchars($item['image'] ?? '/assets/img/placeholder.jpg'); ?>" 
                                                                                         alt="Ürün" class="rounded" style="width: 60px; height: 60px; object-fit: cover;">
                                                                                </div>
                                                                                <div class="flex-grow-1 ms-3">
                                                                                    <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($item['name']); ?></h6>
                                                                                    <small class="text-muted">Adet: <?php echo $item['quantity']; ?></small>
                                                                                </div>
                                                                                <div class="text-end">
                                                                                    <span class="fw-bold text-primary">₺<?php echo number_format($item['price'] * $item['quantity'], 2, ',', '.'); ?></span>
                                                                                </div>
                                                                            </div>
                                                                        <?php endwhile; ?>
                                                                        <?php $item_stmt->close(); ?>
                                                                    </div>

                                                                    <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded">
                                                                        <span class="fw-bold text-muted">Toplam Tutar</span>
                                                                        <span class="h5 mb-0 fw-bold text-primary">₺<?php echo number_format($order['total_amount'], 2, ',', '.'); ?></span>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer border-top-0">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Messages Tab -->
                    <div id="messagesTab" class="tab-content <?php echo $active_tab === 'messages' ? '' : 'd-none'; ?>">
                        <!-- Contact Messages Section -->
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                                <h5 class="mb-0 fw-bold">
                                    <i class="fas fa-envelope me-2"></i>İletişim Mesajlarım
                                </h5>
                                <a href="/contact.php" class="text-decoration-none small fw-bold">Yeni Mesaj Gönder <i class="fas fa-plus ms-1"></i></a>
                            </div>
                            <div class="card-body p-0">
                                <?php if ($user_contacts->num_rows > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th class="ps-4">Konu</th>
                                                    <th class="d-none d-md-table-cell">Gönderim Tarihi</th>
                                                    <th>Durum</th>
                                                    <th class="text-end pe-4">İşlemler</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while($contact = $user_contacts->fetch_assoc()): ?>
                                                    <tr>
                                                        <td class="ps-4">
                                                            <strong class="text-dark"><?php echo htmlspecialchars($contact['subject']); ?></strong>
                                                        </td>
                                                        <td class="text-muted small d-none d-md-table-cell">
                                                            <?php echo date('d.m.Y H:i', strtotime($contact['created_at'])); ?>
                                                        </td>
                                                        <td>
                                                            <?php if (!empty($contact['reply_message'])): ?>
                                                                <span class="badge bg-success">
                                                                    <i class="fas fa-reply me-1"></i>Cevaplandı
                                                                </span>
                                                            <?php elseif ($contact['status'] === 'read'): ?>
                                                                <span class="badge bg-warning">
                                                                    <i class="fas fa-check me-1"></i>Okundu
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="badge bg-danger">
                                                                    <i class="fas fa-clock me-1"></i>Bekliyor
                                                                </span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="text-end pe-4">
                                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" 
                                                                    data-bs-target="#messageModal<?php echo $contact['id']; ?>">
                                                                <i class="fas fa-eye me-1"></i>Detaylar
                                                            </button>
                                                        </td>
                                                    </tr>

                                                    <!-- Message Detail Modal -->
                                                    <div class="modal fade" id="messageModal<?php echo $contact['id']; ?>" tabindex="-1">
                                                        <div class="modal-dialog modal-lg">
                                                            <div class="modal-content border-0 shadow-lg">
                                                                <div class="modal-header bg-primary text-white border-0">
                                                                    <h5 class="modal-title fw-bold">
                                                                        <i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($contact['subject']); ?>
                                                                    </h5>
                                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <div class="row mb-4">
                                                                        <div class="col-md-6">
                                                                            <p class="text-muted small mb-1">Gönderim Tarihi</p>
                                                                            <p class="fw-bold text-dark"><?php echo date('d.m.Y H:i', strtotime($contact['created_at'])); ?></p>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <p class="text-muted small mb-1">Durum</p>
                                                                            <p class="fw-bold text-dark">
                                                                                <?php if (!empty($contact['reply_message'])): ?>
                                                                                    <span class="badge bg-success">Cevaplandı</span>
                                                                                <?php elseif ($contact['status'] === 'read'): ?>
                                                                                    <span class="badge bg-warning">Okundu</span>
                                                                                <?php else: ?>
                                                                                    <span class="badge bg-danger">Bekliyor</span>
                                                                                <?php endif; ?>
                                                                            </p>
                                                                        </div>
                                                                    </div>

                                                                    <hr>

                                                                    <p class="text-muted small mb-2">Mesajınız</p>
                                                                    <div class="bg-light p-3 rounded mb-4">
                                                                        <p class="text-dark mb-0"><?php echo nl2br(htmlspecialchars($contact['message'])); ?></p>
                                                                    </div>

                                                                    <?php if (!empty($contact['reply_message'])): ?>
                                                                        <p class="text-muted small mb-2">Admin Yanıtı</p>
                                                                        <div class="bg-success bg-opacity-10 p-3 rounded border-start border-success border-3">
                                                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                                                <p class="fw-bold text-success mb-0">
                                                                                    <i class="fas fa-user-shield me-1"></i>
                                                                                    <?php echo htmlspecialchars($contact['admin_name']); ?>
                                                                                </p>
                                                                                <small class="text-muted"><?php echo date('d.m.Y H:i', strtotime($contact['reply_date'])); ?></small>
                                                                            </div>
                                                                            <p class="text-dark mb-0"><?php echo nl2br(htmlspecialchars($contact['reply_message'])); ?></p>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="modal-footer border-top">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-envelope fa-3x text-muted mb-3"></i>
                                        <h5 class="text-muted">Henüz mesajınız bulunmuyor</h5>
                                        <p class="text-muted mb-4">Bizimle iletişime geçmek için yeni mesaj gönderebilirsiniz.</p>
                                        <a href="/contact.php" class="btn btn-primary">
                                            <i class="fas fa-plus me-2"></i>Yeni Mesaj Gönder
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

<?php
// 3. Footer Dahil Et
require_once 'includes/footer.php';
?>