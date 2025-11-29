<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/session.php';
requireAdmin();

$page_title = "Marka Yönetimi";
$message = '';

// Güvenlik fonksiyonları
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function validate_image($file) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 2 * 1024 * 1024; // 2MB
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    if (!in_array($file['type'], $allowed_types)) {
        return false;
    }
    
    if ($file['size'] > $max_size) {
        return false;
    }
    
    // Dosya uzantısı kontrolü
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($file_extension, $allowed_extensions)) {
        return false;
    }
    
    return true;
}

function generate_filename($original_name) {
    $extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
    $timestamp = time();
    $random_string = bin2hex(random_bytes(8));
    return "brand_{$timestamp}_{$random_string}.{$extension}";
}

// Marka Ekle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_brand'])) {
    $name = sanitize_input($_POST['name']);
    $desc = sanitize_input($_POST['description']);
    $logo_url = '';
    
    // Dosya yükleme işlemi
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        if (validate_image($_FILES['logo'])) {
            $upload_dir = '../assets/img/brands/';
            
            // Klasör yoksa oluştur
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $new_filename = generate_filename($_FILES['logo']['name']);
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_path)) {
                $logo_url = 'assets/img/brands/' . $new_filename;
            } else {
                $message = '<div class="alert alert-danger alert-dismissible fade show">Logo yüklenirken hata oluştu!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            }
        } else {
            $message = '<div class="alert alert-danger alert-dismissible fade show">Geçersiz dosya türü! Sadece JPG, PNG, WEBP veya GIF yükleyebilirsiniz. (Max: 2MB)<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        }
    }
    
    if (!empty($name) && empty($message)) {
        try {
            // Marka adı kontrolü (aynı isimde marka var mı?)
            $check_stmt = $conn->prepare("SELECT id FROM brands WHERE name = ?");
            $check_stmt->bind_param("s", $name);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $message = '<div class="alert alert-warning alert-dismissible fade show">Bu isimde bir marka zaten mevcut!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            } else {
                $stmt = $conn->prepare("INSERT INTO brands (name, description, logo_url) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $name, $desc, $logo_url);
                if ($stmt->execute()) {
                    $message = '<div class="alert alert-success alert-dismissible fade show">Marka başarıyla eklendi.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                }
                $stmt->close();
            }
            $check_stmt->close();
        } catch (Exception $e) {
            error_log("Marka ekleme hatası: " . $e->getMessage());
            $message = '<div class="alert alert-danger alert-dismissible fade show">Marka eklenirken bir hata oluştu.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        }
    } elseif (empty($name)) {
        $message = '<div class="alert alert-warning alert-dismissible fade show">Marka adı gereklidir!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
}

// Marka Sil
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // CSRF token kontrolü
    if (!isset($_GET['token']) || !hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
        $message = '<div class="alert alert-danger alert-dismissible fade show">Geçersiz işlem!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    } else {
        try {
            // Silinecek markanın logo URL'sini al
            $stmt = $conn->prepare("SELECT logo_url FROM brands WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $brand = $result->fetch_assoc();
            $stmt->close();
            
            // Markayı sil
            $stmt = $conn->prepare("DELETE FROM brands WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                // Logo dosyasını da sil
                if (!empty($brand['logo_url']) && file_exists('../' . $brand['logo_url'])) {
                    unlink('../' . $brand['logo_url']);
                }
                $message = '<div class="alert alert-success alert-dismissible fade show">Marka başarıyla silindi.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            }
            $stmt->close();
        } catch (Exception $e) {
            error_log("Marka silme hatası: " . $e->getMessage());
            $message = '<div class="alert alert-danger alert-dismissible fade show">Marka silinirken bir hata oluştu. Bu markaya ait ürünler olabilir.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        }
    }
}

// CSRF Token oluştur
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$brands = $conn->query("SELECT * FROM brands ORDER BY id ASC");
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
        
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; }
        .btn-primary:hover { background: linear-gradient(135deg, #5a6fd6 0%, #6c4596 100%); }
        
        .brand-logo-preview { 
            width: 60px; height: 60px; object-fit: cover; 
            background: white; border: 2px solid #e9ecef; padding: 3px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); border-radius: 8px;
        }
        
        .logo-upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            background: #f8f9fa;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .logo-upload-area:hover {
            border-color: #667eea;
            background: #f0f4ff;
        }
        
        .logo-upload-area.dragover {
            border-color: #667eea;
            background: #e8f4ff;
        }
        
        .logo-preview-container {
            text-align: center;
            margin-bottom: 15px;
        }
        
        .logo-preview {
            max-width: 150px;
            max-height: 150px;
            border-radius: 8px;
            border: 2px solid #e9ecef;
        }
    </style>
</head>
<body>

<?php include 'admin-assets/sidebar.php'; ?>

    <!-- Top Navbar -->
    <div class="top-navbar">
        <h4 class="mb-0 fw-bold">Marka Yönetimi</h4>
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

        <div class="row g-4">
            <!-- Ekleme Formu -->
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="fw-bold mb-0 text-primary">Yeni Marka Ekle</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data" id="brandForm">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium">Marka Adı *</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-tag text-muted"></i></span>
                                    <input type="text" name="name" class="form-control" required placeholder="Örn: Marshall" maxlength="100">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium">Marka Logosu</label>
                                
                                <div class="logo-upload-area" id="logoUploadArea">
                                    <div id="logoPreviewContainer" class="logo-preview-container" style="display: none;">
                                        <img id="logoPreview" class="logo-preview" src="" alt="Logo önizleme">
                                        <button type="button" class="btn btn-sm btn-outline-danger mt-2" onclick="removeLogo()">
                                            <i class="fas fa-times me-1"></i>Logoyu Kaldır
                                        </button>
                                    </div>
                                    
                                    <div id="uploadPlaceholder">
                                        <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                                        <p class="text-muted mb-1">Logo yüklemek için tıklayın veya sürükleyin</p>
                                        <small class="text-muted">JPG, PNG, WEBP, GIF (Max: 2MB)</small>
                                    </div>
                                    
                                    <input type="file" name="logo" id="logoInput" accept="image/*" style="display: none;">
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label fw-medium">Açıklama</label>
                                <textarea name="description" class="form-control" rows="3" placeholder="Marka hakkında kısa bilgi..." maxlength="500"></textarea>
                            </div>
                            
                            <button type="submit" name="add_brand" class="btn btn-primary w-100 py-2">
                                <i class="fas fa-plus-circle me-2"></i> Kaydet
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Liste -->
            <div class="col-md-8">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0">Mevcut Markalar</h5>
                        <span class="badge bg-primary rounded-pill"><?php echo $brands->num_rows; ?> Kayıt</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4" style="width: 80px;">Logo</th>
                                        <th>Marka Adı</th>
                                        <th>Açıklama</th>
                                        <th class="text-end pe-4" style="width: 100px;">İşlem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($brands->num_rows > 0): ?>
                                        <?php while($brand = $brands->fetch_assoc()): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <?php if(!empty($brand['logo_url'])): ?>
                                                    <img src="../<?php echo htmlspecialchars($brand['logo_url']); ?>" 
                                                         class="brand-logo-preview" 
                                                         alt="<?php echo htmlspecialchars($brand['name']); ?> logo"
                                                         onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjYwIiBoZWlnaHQ9IjYwIiByeD0iOCIgZmlsbD0iI0Y4RjlGQSIvPgo8cGF0aCBkPSJNMzAgMjBDMzMuMzEzNyAyMCAzNiAxNy4zMTM3IDM2IDE0QzM2IDEwLjY4NjMgMzMuMzEzNyA4IDMwIDhDMjYuNjg2MyA4IDI0IDEwLjY4NjMgMjQgMTRDMjQgMTcuMzEzNyAyNi42ODYzIDIwIDMwIDIwWiIgZmlsbD0iIzk5QTFBQiIvPgo8cGF0aCBkPSJNMTggNDhWNDJDMTggMzguNjg2MyAyMC42ODYzIDM2IDI0IDM2SDM2QzM5LjMxMzcgMzYgNDIgMzguNjg2MyA0MiA0MlY0OCIgc3Ryb2tlPSIjOTlBMUFCIiBzdHJva2Utd2lkdGg9IjIiLz4KPC9zdmc+Cg=='">
                                                <?php else: ?>
                                                    <div class="brand-logo-preview d-flex align-items-center justify-content-center bg-light text-muted">
                                                        <i class="fas fa-image fa-lg"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="fw-bold text-dark"><?php echo htmlspecialchars($brand['name']); ?></span>
                                            </td>
                                            <td class="text-muted small">
                                                <?php 
                                                if (!empty($brand['description'])) {
                                                    echo htmlspecialchars(mb_strlen($brand['description']) > 100 ? mb_substr($brand['description'], 0, 100) . '...' : $brand['description']);
                                                } else {
                                                    echo '<span class="text-muted fst-italic">Açıklama yok</span>';
                                                }
                                                ?>
                                            </td>
                                            <td class="text-end pe-4">
                                                <a href="?delete=<?php echo $brand['id']; ?>&token=<?php echo $_SESSION['csrf_token']; ?>" 
                                                   class="btn btn-sm btn-outline-danger" 
                                                   onclick="return confirm('Bu markayı silmek istediğinize emin misiniz? Bu işlem geri alınamaz!')" 
                                                   title="Sil">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-5 text-muted">
                                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                                <p>Henüz eklenmiş bir marka yok.</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Logo yükleme işlevselliği
document.addEventListener('DOMContentLoaded', function() {
    const logoInput = document.getElementById('logoInput');
    const logoUploadArea = document.getElementById('logoUploadArea');
    const logoPreview = document.getElementById('logoPreview');
    const logoPreviewContainer = document.getElementById('logoPreviewContainer');
    const uploadPlaceholder = document.getElementById('uploadPlaceholder');
    
    // Tıklama ile dosya seçme
    logoUploadArea.addEventListener('click', function() {
        logoInput.click();
    });
    
    // Dosya seçildiğinde
    logoInput.addEventListener('change', function(e) {
        if (this.files && this.files[0]) {
            const file = this.files[0];
            
            // Dosya türü kontrolü
            if (!file.type.match('image.*')) {
                alert('Sadece resim dosyaları yükleyebilirsiniz!');
                return;
            }
            
            // Dosya boyutu kontrolü (2MB)
            if (file.size > 2 * 1024 * 1024) {
                alert('Dosya boyutu 2MB\'dan küçük olmalıdır!');
                return;
            }
            
            const reader = new FileReader();
            
            reader.onload = function(e) {
                logoPreview.src = e.target.result;
                uploadPlaceholder.style.display = 'none';
                logoPreviewContainer.style.display = 'block';
            }
            
            reader.readAsDataURL(file);
        }
    });
    
    // Drag and drop desteği
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        logoUploadArea.addEventListener(eventName, preventDefaults, false);
    });
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    ['dragenter', 'dragover'].forEach(eventName => {
        logoUploadArea.addEventListener(eventName, highlight, false);
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        logoUploadArea.addEventListener(eventName, unhighlight, false);
    });
    
    function highlight() {
        logoUploadArea.classList.add('dragover');
    }
    
    function unhighlight() {
        logoUploadArea.classList.remove('dragover');
    }
    
    logoUploadArea.addEventListener('drop', handleDrop, false);
    
    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        
        if (files.length) {
            logoInput.files = files;
            const event = new Event('change');
            logoInput.dispatchEvent(event);
        }
    }
});

function removeLogo() {
    const logoInput = document.getElementById('logoInput');
    const logoPreviewContainer = document.getElementById('logoPreviewContainer');
    const uploadPlaceholder = document.getElementById('uploadPlaceholder');
    
    logoInput.value = '';
    logoPreviewContainer.style.display = 'none';
    uploadPlaceholder.style.display = 'block';
}

// Form gönderiminden önce kontrol
document.getElementById('brandForm').addEventListener('submit', function(e) {
    const nameInput = this.querySelector('input[name="name"]');
    
    if (nameInput.value.trim() === '') {
        e.preventDefault();
        alert('Marka adı gereklidir!');
        nameInput.focus();
        return false;
    }
    
    return true;
});
</script>
</body>
</html>