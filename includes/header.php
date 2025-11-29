<?php
// includes/header.php - EN BAŞA output buffering ekle
if (!ob_get_level()) {
    ob_start();
}

require_once 'db_connect.php'; 
require_once 'session.php';

// Sepet sayısını al
$cart_count = 0;
if (isUserLoggedIn() && isset($conn) && $conn) {
    $user_id = $_SESSION['user_id'];
    $cart_count = getCartItemCount($user_id, $conn);
} else {
    // Misafir kullanıcı için session'dan sepet sayısını al
    $cart_count = getCartItemCount();
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - Kafkas Boya' : 'Kafkas Boya - Profesyonel Boya Çözümleri'; ?></title>

    <!-- Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        /* Global alerts için özel stil - SABİT SAĞ ÜST */
        .global-alerts {
            position: fixed;
            top: 90px;
            right: 20px;
            z-index: 1060;
            max-width: 400px;
            width: 100%;
        }

        .global-alert {
            border-radius: 12px;
            border: none;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            font-weight: 500;
            margin-bottom: 15px;
            animation: slideInRight 0.4s ease-out;
            border-left: 5px solid;
            padding: 15px 20px;
            min-height: 70px;
            display: flex;
            align-items: center;
            position: relative;
            backdrop-filter: blur(10px);
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }

        .global-alert.hiding {
            animation: slideOutRight 0.3s ease-in forwards;
        }

        .global-alert.alert-info {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            color: #1565c0;
            border-left-color: #2196f3;
        }

        .global-alert.alert-danger {
            background: linear-gradient(135deg, #ffebee, #ffcdd2);
            color: #c62828;
            border-left-color: #f44336;
        }

        .global-alert.alert-success {
            background: linear-gradient(135deg, #e8f5e8, #c8e6c9);
            color: #2e7d32;
            border-left-color: #4caf50;
        }

        .global-alert .alert-content {
            flex: 1;
            padding-right: 40px;
        }

        .global-alert .btn-close {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            padding: 8px;
            background: transparent;
            border: none;
            opacity: 0.8;
            transition: all 0.3s ease;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .global-alert .btn-close:hover {
            opacity: 1;
            background: rgba(0,0,0,0.1);
        }

        .global-alert .alert-icon {
            font-size: 20px;
            margin-right: 12px;
            min-width: 24px;
        }

        /* Progress bar for auto-close */
        .alert-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            background: currentColor;
            opacity: 0.3;
            border-radius: 0 0 0 8px;
            animation: progressBar 5s linear forwards;
        }

        @keyframes progressBar {
            from { width: 100%; }
            to { width: 0%; }
        }

        /* Mobile responsive */
        @media (max-width: 768px) {
            .global-alerts {
                right: 10px;
                left: 10px;
                max-width: none;
            }
            
            .global-alert {
                padding: 12px 15px;
                min-height: 60px;
            }
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="/index.php">
                <img src="/assets/img/kafkasboya-logo.png" alt="kafkas boya logo"
                    style="height: 60px; margin-right: 10px;">
            </a>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link fw-medium" href="/index.php">Ana Sayfa</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle fw-medium" href="#" role="button" data-bs-toggle="dropdown">
                            Ürünlerimiz
                        </a>
                        <ul class="dropdown-menu border-0 shadow-sm">
                            <li><a class="dropdown-item" href="/shop.php">Tüm Ürünler</a></li>
                            <li><a class="dropdown-item" href="/shop.php?marka=polisan">Polisan</a></li>
                            <li><a class="dropdown-item" href="/shop.php?marka=filli+boya">Filli Boya</a></li>
                            <li><a class="dropdown-item" href="/shop.php?marka=marshall">Marshall</a></li>
                            <li><a class="dropdown-item" href="/shop.php?marka=dyo">DYO</a></li>
                            <li><a class="dropdown-item" href="/shop.php?marka=permolit">Permolit</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-medium" href="/about.php">Hakkımızda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-medium" href="/contact.php">İletişim</a>
                    </li>
                </ul>

<div class="d-flex align-items-center ms-auto">
    <a href="/cart.php" class="btn btn-outline-primary btn-sm me-2 position-relative" style="z-index: 1020; width: 30%; min-width: auto; flex: 0 0 30%;">
        <i class="fas fa-shopping-cart"></i>
        <span class="position-absolute translate-middle badge rounded-pill bg-danger" 
            style="z-index: 1021; font-size: 0.6rem; min-width: 18px; height: 18px; max-width: 50px; line-height: 1.2; top: 10px; right: -10px; position: absolute;"
            id="cart-count"><?php echo $cart_count; ?></span>
    </a>
    <?php if (isUserLoggedIn()): ?>
        <div class="dropdown" style="width: 70%;">
            <button class="btn btn-primary btn-sm dropdown-toggle w-100" type="button" data-bs-toggle="dropdown">
                <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['username']); ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <?php if (isAdmin()): ?>
                    <li><a class="dropdown-item" href="/admin/dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Admin Paneli</a></li>
                    <li><hr class="dropdown-divider"></li>
                <?php endif; ?>
                <li><a class="dropdown-item" href="/profile.php"><i class="fas fa-user-circle me-2"></i>Profilim</a></li>
                <li><a class="dropdown-item" href="/profile.php?tab=orders"><i class="fas fa-receipt me-2"></i>Siparişlerim</a></li>
                <li><a class="dropdown-item" href="/profile.php?tab=messages"><i class="fas fa-receipt me-2"></i>Mesajlarım</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Çıkış Yap</a></li>
            </ul>
        </div>
    <?php else: ?>
    <div class="d-flex gap-2" style="width: 70%;">
        <a href="/login.php" class="btn btn-outline-primary btn-sm text-nowrap" 
        style="width: 50%; min-width: auto; flex: 0 0 50%; font-size: clamp(0.7rem, 2vw, 0.875rem);">
            <i class="fas fa-sign-in-alt me-1"></i>
            <span class="d-none d-sm-inline">Giriş Yap</span>
            <span class="d-inline d-sm-none">Giriş</span>
        </a>
        <a href="/register.php" class="btn btn-primary btn-sm text-nowrap" 
        style="width: 50%; min-width: auto; flex: 0 0 50%; font-size: clamp(0.7rem, 2vw, 0.875rem);">
            <i class="fas fa-user-plus me-1"></i>
            <span class="d-none d-sm-inline">Üye Ol</span>
            <span class="d-inline d-sm-none">Üye</span>
        </a>
    </div>
    <?php endif; ?>
</div>
            </div>
        </div>
    </nav>

    <!-- Global Alert Messages - SABİT SAĞ ÜST KÖŞEDE -->
    <div class="global-alerts">
        <?php if (isset($_SESSION['cart_transfer_info'])): ?>
            <?php foreach ($_SESSION['cart_transfer_info'] as $message): ?>
                <div class="global-alert alert-info" data-auto-close="5000">
                    <div class="alert-progress"></div>
                    <i class="fas fa-info-circle alert-icon"></i>
                    <div class="alert-content"><?php echo htmlspecialchars($message); ?></div>
                    <button type="button" class="btn-close" onclick="closeAlert(this)">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endforeach; ?>
            <?php unset($_SESSION['cart_transfer_info']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="global-alert alert-danger" data-auto-close="5000">
                <div class="alert-progress"></div>
                <i class="fas fa-exclamation-circle alert-icon"></i>
                <div class="alert-content"><?php echo $_SESSION['error']; ?></div>
                <button type="button" class="btn-close" onclick="closeAlert(this)">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="global-alert alert-success" data-auto-close="5000">
                <div class="alert-progress"></div>
                <i class="fas fa-check-circle alert-icon"></i>
                <div class="alert-content"><?php echo $_SESSION['success']; ?></div>
                <button type="button" class="btn-close" onclick="closeAlert(this)">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
    </div>

    <!-- Main Content -->
    <main>

<script>
// Alert otomatik kapanma ve yönetimi
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.global-alert[data-auto-close]');
    
    alerts.forEach(alert => {
        const autoCloseTime = parseInt(alert.getAttribute('data-auto-close'));
        
        // Otomatik kapanma
        const autoCloseTimer = setTimeout(() => {
            closeAlertElement(alert);
        }, autoCloseTime);
        
        // Fare üzerine gelince duraklat
        alert.addEventListener('mouseenter', () => {
            clearTimeout(autoCloseTimer);
            const progress = alert.querySelector('.alert-progress');
            if (progress) {
                progress.style.animationPlayState = 'paused';
            }
        });
        
        // Fare çıkınca devam et
        alert.addEventListener('mouseleave', () => {
            const newTimer = setTimeout(() => {
                closeAlertElement(alert);
            }, autoCloseTime);
            alert.setAttribute('data-timer', newTimer);
            
            const progress = alert.querySelector('.alert-progress');
            if (progress) {
                progress.style.animationPlayState = 'running';
            }
        });
    });
});

function closeAlert(button) {
    const alert = button.closest('.global-alert');
    closeAlertElement(alert);
}

function closeAlertElement(alert) {
    if (!alert) return;
    
    alert.classList.add('hiding');
    
    setTimeout(() => {
        if (alert.parentElement) {
            alert.remove();
        }
    }, 300);
}

// Sayfa yüklendiğinde alert'leri temizle (güvenlik için)
setTimeout(() => {
    const alerts = document.querySelectorAll('.global-alert');
    alerts.forEach(alert => {
        if (alert.parentElement) {
            alert.remove();
        }
    });
}, 10000); // 10 saniye sonra tüm alert'leri temizle
</script>