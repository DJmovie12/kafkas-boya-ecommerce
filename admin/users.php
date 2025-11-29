<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/session.php';
requireAdmin();

$page_title = "Kullanıcı Yönetimi";
$message = '';

// Kullanıcı Silme
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if ($id != $_SESSION['user_id']) {
        if ($conn->query("DELETE FROM users WHERE id = $id")) {
            header("Location: users.php?msg=deleted");
            exit();
        }
    } else {
        $message = '<div class="alert alert-warning alert-dismissible fade show">Kendinizi silemezsiniz.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
}

if (isset($_GET['msg']) && $_GET['msg'] == 'deleted') {
    $message = '<div class="alert alert-success alert-dismissible fade show">Kullanıcı başarıyla silindi.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
}

$users = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?> - Admin Paneli</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; }
        
        .main-content { margin-left: 250px; padding: 30px; }
        
        .top-navbar {
            background: white; padding: 15px 30px; border-bottom: 1px solid #e9ecef;
            display: flex; justify-content: space-between; align-items: center;
            margin-left: 250px; position: sticky; top: 0; z-index: 999;
        }
        
        .card { border: none; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .card-header { background: white; border-bottom: 1px solid #e9ecef; padding: 20px; border-radius: 15px 15px 0 0; }
        
        .user-avatar { width: 40px; height: 40px; background-color: #e9ecef; color: #6c757d; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
    </style>
</head>
<body>

<?php include 'admin-assets/sidebar.php'; ?>

    <!-- Top Navbar -->
    <div class="top-navbar">
        <h4 class="mb-0 fw-bold">Kullanıcı Yönetimi</h4>
        <div class="d-flex align-items-center gap-3">
            <span class="text-muted"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                <i class="fas fa-user"></i>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <?php echo $message; ?>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">Kayıtlı Kullanıcılar</h5>
                <div class="d-flex gap-2">
                    <div class="input-group input-group-sm" style="width: 250px;">
                        <input type="text" class="form-control" placeholder="Kullanıcı ara...">
                        <button class="btn btn-outline-secondary"><i class="fas fa-search"></i></button>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4" style="width: 60px;">ID</th>
                            <th>Kullanıcı</th>
                            <th>E-posta</th>
                            <th>Adres</th> <!-- YENİ EKLENEN -->
                            <th>Rol</th>
                            <th>Kayıt Tarihi</th>
                            <th class="text-end pe-4">İşlem</th>
                        </tr>
                    </thead>
                        <tbody>
                            <?php if ($users->num_rows > 0): ?>
                                <?php while($user = $users->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4 text-muted">#<?php echo $user['id']; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="user-avatar me-3">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <span class="fw-bold text-dark"><?php echo htmlspecialchars($user['username']); ?></span>
                                        </div>
                                    </td>
                                    <td><span class="text-muted"><?php echo htmlspecialchars($user['email']); ?></span></td>
                                    <td>
                                        <span class="text-muted small" title="<?php echo htmlspecialchars($user['address'] ?? 'Adres yok'); ?>">
                                            <?php 
                                            $address = $user['address'] ?? 'Adres yok';
                                            echo strlen($address) > 30 ? substr($address, 0, 30) . '...' : $address;
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($user['role'] === 'admin'): ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-2 rounded-pill"><i class="fas fa-shield-alt me-1"></i> Yönetici</span>
                                        <?php else: ?>
                                            <span class="badge bg-info bg-opacity-10 text-info px-3 py-2 rounded-pill">Müşteri</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="text-muted small"><i class="far fa-calendar-alt me-1"></i><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <?php if($user['id'] != $_SESSION['user_id']): ?>
                                            <a href="?delete=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bu kullanıcıyı silmek istediğinize emin misiniz? Tüm siparişleri de silinecektir!')" title="Kullanıcıyı Sil">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-success bg-opacity-10 text-success">Aktif Oturum</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center py-5 text-muted">Kayıtlı kullanıcı bulunamadı.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>