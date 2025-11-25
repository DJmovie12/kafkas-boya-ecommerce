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

// Son 5 Siparişi Çek
$order_sql = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
$stmt = $conn->prepare($order_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_orders = $stmt->get_result();
$stmt->close();

// 2. Header Dahil Et
require_once 'includes/header.php';
?>

    <!-- Page Header -->
    <section class="page-header bg-light py-5" style="margin-top: 76px;">
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
                <!-- Sidebar Menu -->
                <div class="col-lg-3">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body text-center p-4">
                            <div class="mb-3">
                                <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle" style="width: 80px; height: 80px;">
                                    <i class="fas fa-user fa-2x"></i>
                                </div>
                            </div>
                            <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($user_data['username']); ?></h5>
                            <p class="text-muted small mb-0"><?php echo htmlspecialchars($user_data['email']); ?></p>
                            <p class="text-muted small mt-2">Üyelik Tarihi: <?php echo date('d.m.Y', strtotime($user_data['created_at'])); ?></p>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm">
                        <div class="list-group list-group-flush">
                            <a href="/profile.php" class="list-group-item list-group-item-action py-3 active bg-primary text-white border-0">
                                <i class="fas fa-user me-2"></i> Profil Bilgilerim
                            </a>
                            <a href="/orders.php" class="list-group-item list-group-item-action py-3 border-0">
                                <i class="fas fa-shopping-bag me-2 text-muted"></i> Siparişlerim
                            </a>
                            <a href="/logout.php" class="list-group-item list-group-item-action py-3 text-danger border-0">
                                <i class="fas fa-sign-out-alt me-2"></i> Çıkış Yap
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="col-lg-9">
                    <!-- Recent Orders Preview -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold">Son Siparişlerim</h5>
                            <a href="/orders.php" class="text-decoration-none small fw-bold">Tümünü Gör <i class="fas fa-arrow-right ms-1"></i></a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="ps-4">Sipariş No</th>
                                            <th>Tarih</th>
                                            <th>Tutar</th>
                                            <th>Durum</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($recent_orders->num_rows > 0): ?>
                                            <?php while($order = $recent_orders->fetch_assoc()): ?>
                                                <tr>
                                                    <td class="ps-4 fw-bold">#<?php echo $order['id']; ?></td>
                                                    <td class="text-muted small"><?php echo date('d.m.Y', strtotime($order['created_at'])); ?></td>
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
                                                        $status = $order['STATUS'] ?? 'pending';
                                                        ?>
                                                        <span class="badge rounded-pill <?php echo $statusColors[$status] ?? 'bg-secondary'; ?>">
                                                            <?php echo $statusTexts[$status] ?? ucfirst($status); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
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
            </div>
        </div>
    </section>

<?php require_once 'includes/footer.php'; ?>