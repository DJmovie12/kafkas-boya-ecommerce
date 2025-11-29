<?php

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/session.php';

// Admin kontrolü
if (!isAdmin()) {
    header('Location: /index.php');
    exit;
}

// Mesajı okundu olarak işaretle
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $id = intval($_GET['mark_read']);
    $stmt = $conn->prepare("UPDATE contacts SET status = 'read' WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header('Location: user-contacts.php');
    exit;
}

// Mesajı sil
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM contacts WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header('Location: user-contacts.php');
    exit;
}

// Tüm mesajları getir (yanıt bilgisiyle birlikte)
$sql = "SELECT c.*, 
               (SELECT COUNT(*) FROM contact_replies WHERE contact_id = c.id) as reply_count 
        FROM contacts c 
        ORDER BY c.created_at DESC";
$result = $conn->query($sql);
$contacts = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// İstatistikler
$total_contacts = count($contacts);
$new_contacts = count(array_filter($contacts, fn($c) => $c['status'] === 'new'));
$read_contacts = count(array_filter($contacts, fn($c) => $c['status'] === 'read'));

// Yanıt Gönderme İşlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reply'])) {
    $contact_id = intval($_POST['contact_id']);
    $reply_message = trim($_POST['reply_message']);
    $admin_id = $_SESSION['user_id'];
    
    // Bu mesaja daha önce yanıt verilmiş mi kontrol et
    $check_stmt = $conn->prepare("SELECT COUNT(*) as reply_count FROM contact_replies WHERE contact_id = ?");
    $check_stmt->bind_param("i", $contact_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $reply_count = $check_result->fetch_assoc()['reply_count'];
    $check_stmt->close();
    
    if ($reply_count > 0) {
        // Zaten yanıt verilmişse hata mesajı göster
        header('Location: user-contacts.php?error=already_replied');
        exit;
    }
    
    if (!empty($reply_message)) {
        // Veritabanına yanıt ekle
        $stmt = $conn->prepare("INSERT INTO contact_replies (contact_id, admin_id, reply_message) VALUES (?, ?, ?)");
        
        if ($stmt) {
            $stmt->bind_param("iis", $contact_id, $admin_id, $reply_message);
            
            if ($stmt->execute()) {
                // Contact statusunu 'replied' yap
                $update_stmt = $conn->prepare("UPDATE contacts SET status = 'replied' WHERE id = ?");
                $update_stmt->bind_param("i", $contact_id);
                $update_stmt->execute();
                $update_stmt->close();
                
                header('Location: user-contacts.php?success=1');
                exit;
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İletişim Mesajları - Admin Paneli</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { 
            background-color: #f8f9fa; 
            font-family: 'Inter', sans-serif; 
        }

        .main-content { 
            margin-left: 250px; 
            padding: 30px; 
        }
        
        .top-navbar {
            background: white;
            padding: 15px 30px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-left: 250px;
            position: sticky;
            top: 0;
            z-index: 999;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid #667eea;
            transition: all 0.3s ease;
            margin-bottom: 30px;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
        }
        
        .stat-card .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .card-header {
            background: white;
            border-bottom: 1px solid #e9ecef;
            padding: 20px;
            border-radius: 15px 15px 0 0;
        }
        
        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #5a6fd6 0%, #6c4596 100%);
        }

        .new-message {
            background-color: #f0f4ff !important;
            font-weight: 500;
        }
        
        .replied-message {
            background-color: #f0fff4 !important;
        }
        
        .alert-success {
            border-left: 4px solid #28a745;
        }
        
        .alert-danger {
            border-left: 4px solid #dc3545;
        }
    </style>
</head>
<body>
<?php include 'admin-assets/sidebar.php'; ?>

    <!-- Top Navbar -->
    <div class="top-navbar">
        <h4 class="mb-0 fw-bold">
            <i class="fas fa-envelope me-2"></i>İletişim Mesajları
        </h4>
        <div class="d-flex align-items-center gap-3">
            <span class="text-muted"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                <i class="fas fa-user"></i>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Başarı/Hata Mesajları -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <div>Yanıt başarıyla gönderildi!</div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error']) && $_GET['error'] === 'already_replied'): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <div>Bu mesaja zaten yanıt verilmiş. Her mesaja sadece 1 kez yanıt verebilirsiniz.</div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Row -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $total_contacts; ?></div>
                    <div class="stat-label">Toplam Mesaj</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="border-left-color: #dc3545;">
                    <div class="stat-value" style="color: #dc3545;"><?php echo $new_contacts; ?></div>
                    <div class="stat-label">Yeni Mesaj</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="border-left-color: #ffc107;">
                    <div class="stat-value" style="color: #ffc107;"><?php echo $read_contacts; ?></div>
                    <div class="stat-label">Okunmuş</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="border-left-color: #28a745;">
                    <div class="stat-value" style="color: #28a745;">
                        <?php echo count(array_filter($contacts, fn($c) => $c['status'] === 'replied')); ?>
                    </div>
                    <div class="stat-label">Yanıt Verilen</div>
                </div>
            </div>
        </div>

        <!-- Messages Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">
                    <i class="fas fa-envelope me-2"></i>Müşteri Mesajları
                </h5>
                <span class="badge bg-primary"><?php echo $total_contacts; ?> toplam</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($contacts)): ?>
                    <div class="alert alert-info m-4 mb-0">
                        <i class="fas fa-info-circle me-2"></i>Henüz mesaj yok.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Durum</th>
                                    <th>Ad Soyad</th>
                                    <th>E-posta</th>
                                    <th>Konu</th>
                                    <th>Yanıt</th>
                                    <th>Tarih</th>
                                    <th class="text-end pe-4">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($contacts as $contact): 
                                    $has_reply = $contact['reply_count'] > 0;
                                ?>
                                <tr class="<?php 
                                    echo $contact['status'] === 'new' ? 'new-message' : ''; 
                                    echo $has_reply ? ' replied-message' : '';
                                ?>">
                                    <td class="ps-4">
                                        <?php if ($contact['status'] === 'new'): ?>
                                            <span class="badge bg-danger">
                                                <i class="fas fa-exclamation-circle me-1"></i>YENİ
                                            </span>
                                        <?php elseif ($contact['status'] === 'read'): ?>
                                            <span class="badge bg-warning">
                                                <i class="fas fa-check me-1"></i>OKUNDU
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-reply me-1"></i>YANIT VERİLDİ
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong class="text-dark"><?php echo htmlspecialchars($contact['name']); ?></strong>
                                    </td>
                                    <td>
                                        <a href="mailto:<?php echo htmlspecialchars($contact['email']); ?>" 
                                           class="text-decoration-none">
                                            <?php echo htmlspecialchars($contact['email']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <div class="text-dark" style="max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                            <?php echo htmlspecialchars($contact['subject']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($has_reply): ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i>Yanıtlandı
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-clock me-1"></i>Bekliyor
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="text-dark"><?php echo date('d.m.Y', strtotime($contact['created_at'])); ?></div>
                                        <div class="text-muted small"><?php echo date('H:i', strtotime($contact['created_at'])); ?></div>
                                    </td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" 
                                                data-bs-target="#messageModal<?php echo $contact['id']; ?>"
                                                title="Mesajı İncele">
                                            <i class="fas fa-eye me-1"></i>İncele
                                        </button>
                                        <?php if ($contact['status'] === 'new'): ?>
                                            <a href="?mark_read=<?php echo $contact['id']; ?>" 
                                               class="btn btn-sm btn-warning"
                                               title="Okundu İşaretle">
                                                <i class="fas fa-check me-1"></i>Okundu
                                            </a>
                                        <?php endif; ?>
                                        <a href="?delete=<?php echo $contact['id']; ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Bu mesajı silmek istediğinize emin misiniz?');"
                                           title="Mesajı Sil">
                                            <i class="fas fa-trash me-1"></i>Sil
                                        </a>
                                    </td>
                                </tr>

                                <!-- Modal -->
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
                                                        <p class="text-muted small mb-1">Ad Soyad</p>
                                                        <p class="fw-bold text-dark"><?php echo htmlspecialchars($contact['name']); ?></p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <p class="text-muted small mb-1">E-posta</p>
                                                        <p class="fw-bold text-dark">
                                                            <a href="mailto:<?php echo htmlspecialchars($contact['email']); ?>" 
                                                               class="text-decoration-none">
                                                                <?php echo htmlspecialchars($contact['email']); ?>
                                                            </a>
                                                        </p>
                                                    </div>
                                                </div>

                                                <div class="row mb-4">
                                                    <div class="col-md-6">
                                                        <p class="text-muted small mb-1">Telefon</p>
                                                        <p class="fw-bold text-dark">
                                                            <?php 
                                                            if (!empty($contact['phone'])) {
                                                                echo htmlspecialchars($contact['phone']);
                                                            } else {
                                                                echo '<span class="text-muted">Belirtilmemiş</span>';
                                                            }
                                                            ?>
                                                        </p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <p class="text-muted small mb-1">Gönderim Tarihi</p>
                                                        <p class="fw-bold text-dark"><?php echo date('d.m.Y H:i', strtotime($contact['created_at'])); ?></p>
                                                    </div>
                                                </div>

                                                <hr>

                                                <p class="text-muted small mb-2">Mesaj İçeriği</p>
                                                <div class="bg-light p-3 rounded" style="min-height: 150px;">
                                                    <p class="text-dark mb-0"><?php echo nl2br(htmlspecialchars($contact['message'])); ?></p>
                                                </div>

                                                <!-- Önceki Yanıtlar -->
                                                <?php if ($has_reply): ?>
                                                    <hr>
                                                    <p class="text-muted small mb-2">Admin Yanıtı</p>
                                                    <div class="bg-success bg-opacity-10 p-3 rounded border-start border-success border-3">
                                                        <?php 
                                                        // Yanıtları getir
                                                        $reply_stmt = $conn->prepare("SELECT cr.*, u.username 
                                                                                     FROM contact_replies cr 
                                                                                     JOIN users u ON cr.admin_id = u.id 
                                                                                     WHERE cr.contact_id = ? 
                                                                                     ORDER BY cr.created_at DESC 
                                                                                     LIMIT 1");
                                                        $reply_stmt->bind_param("i", $contact['id']);
                                                        $reply_stmt->execute();
                                                        $reply_result = $reply_stmt->get_result();
                                                        $reply = $reply_result->fetch_assoc();
                                                        $reply_stmt->close();
                                                        ?>
                                                        <p class="text-dark mb-2"><?php echo nl2br(htmlspecialchars($reply['reply_message'])); ?></p>
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <small class="text-muted">
                                                                <i class="fas fa-user-shield me-1"></i>
                                                                <?php echo htmlspecialchars($reply['username']); ?>
                                                            </small>
                                                            <small class="text-muted">
                                                                <?php echo date('d.m.Y H:i', strtotime($reply['created_at'])); ?>
                                                            </small>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="modal-footer border-top flex-column align-items-start">
                                                <!-- Yanıt Formu -->
                                                <?php if (!$has_reply): ?>
                                                    <form class="w-100" method="POST" id="replyForm<?php echo $contact['id']; ?>">
                                                        <input type="hidden" name="contact_id" value="<?php echo $contact['id']; ?>">
                                                        <div class="mb-3 w-100">
                                                            <label class="form-label text-muted small">Yanıt Mesajı</label>
                                                            <textarea class="form-control" name="reply_message" rows="3" placeholder="Müşteriye yanıtınızı yazın..." required></textarea>
                                                            <div class="form-text">
                                                                <i class="fas fa-info-circle me-1"></i>
                                                                Her mesaja sadece 1 kez yanıt verebilirsiniz.
                                                            </div>
                                                        </div>
                                                        <div class="d-flex gap-2 w-100">
                                                            <button type="submit" name="send_reply" class="btn btn-primary flex-grow-1">
                                                                <i class="fas fa-paper-plane me-2"></i>Yanıt Gönder
                                                            </button>
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                                                        </div>
                                                    </form>
                                                <?php else: ?>
                                                    <div class="w-100">
                                                        <div class="alert alert-info w-100">
                                                            <i class="fas fa-info-circle me-2"></i>
                                                            Bu mesaja zaten yanıt verilmiş. Her mesaja sadece 1 kez yanıt verebilirsiniz.
                                                        </div>
                                                        <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Kapat</button>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>