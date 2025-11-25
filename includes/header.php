<?php
// require_once __DIR__ . '/db_connect.php'; // Eğer header ana dizinde değilse, bu yolları ayarlayın.
// require_once __DIR__ . '/session.php';  // Eğer header ana dizinde değilse, bu yolları ayarlayın.
require_once __DIR__ . '/db_connect.php'; 
require_once __DIR__ . '/session.php';
// Sepet sayısını al
$cart_count = 0;
if (isUserLoggedIn() && isset($conn) && $conn) {
    $user_id = $_SESSION['user_id'];
    
    // GÜVENLİ SORGULAMA: getCartItemCount fonksiyonu session.php'ye taşındığı için onu çağırıyoruz
    // NOT: Eğer getCartItemCount, session.php'de değilse, yukarıdaki session.php kodunu kullandığınızdan emin olun.
    $cart_count = getCartItemCount($user_id, $conn);
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
                            <li><a class="dropdown-item" href="/shop.php?marka=filli">Filli Boya</a></li>
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
                    <a href="/cart.php" class="btn btn-outline-primary btn-sm me-2 position-relative" style="z-index: 1020;">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="position-absolute translate-middle badge rounded-pill bg-danger" 
                            style="z-index: 1021; font-size: 0.6rem; min-width: 18px; height: 18px; line-height: 1.2; top: 10px; right: -10px; position: absolute;"
                            id="cart-count"><?php echo $cart_count; ?></span>
                    </a>
                    <?php if (isUserLoggedIn()): ?>
                        <div class="dropdown">
                            <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['username']); ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <?php if (isAdmin()): ?>
                                    <li><a class="dropdown-item" href="/admin/dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Admin Paneli</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item" href="/profile.php"><i class="fas fa-user-circle me-2"></i>Profilim</a></li>
                                <li><a class="dropdown-item" href="/orders.php"><i class="fas fa-receipt me-2"></i>Siparişlerim</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Çıkış Yap</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="/login.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-user me-1"></i>Giriş
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>