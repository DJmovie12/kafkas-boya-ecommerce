<?php
// admin-assets/sidebar.php

// Aktif sayfayı belirle
$current_page = basename($_SERVER['PHP_SELF']);
?>
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    </head>
<style>
.sidebar {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    position: fixed;
    left: 0; top: 0; width: 250px; z-index: 1000; padding-top: 20px;
}

.sidebar .nav-link {
    color: rgba(255,255,255,0.8);
    padding: 12px 20px; border-left: 3px solid transparent; transition: all 0.3s ease; margin-bottom: 5px;
}

.sidebar .nav-link:hover, .sidebar .nav-link.active {
    color: white; background-color: rgba(255,255,255,0.1); border-left-color: white;
}
</style>

<!-- Sidebar -->
<div class="sidebar">
    <div class="text-center mb-4 px-3">
        <h5 class="text-white fw-bold mb-1">Kafkas Boya</h5>
        <small class="text-white-50">Admin Paneli</small>
    </div>
    
    <nav class="nav flex-column">
        <a href="dashboard.php" class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-line me-2" style="width:20px"></i> Dashboard
        </a>
        <a href="products.php" class="nav-link <?php echo $current_page == 'products.php' ? 'active' : ''; ?>">
            <i class="fas fa-boxes me-2" style="width:20px"></i> Ürünler
        </a>
        <a href="categories.php" class="nav-link <?php echo $current_page == 'categories.php' ? 'active' : ''; ?>">
            <i class="fas fa-list me-2" style="width:20px"></i> Kategoriler
        </a>
        <a href="brands.php" class="nav-link <?php echo $current_page == 'brands.php' ? 'active' : ''; ?>">
            <i class="fas fa-tag me-2" style="width:20px"></i> Markalar
        </a>
        <a href="orders.php" class="nav-link <?php echo $current_page == 'orders.php' ? 'active' : ''; ?>">
            <i class="fas fa-receipt me-2" style="width:20px"></i> Siparişler
        </a>
        <a href="users.php" class="nav-link <?php echo $current_page == 'users.php' ? 'active' : ''; ?>">
            <i class="fas fa-users me-2" style="width:20px"></i> Kullanıcılar
        </a>
        <a href="user-contacts.php" class="nav-link <?php echo $current_page == 'user-contacts.php' ? 'active' : ''; ?>">
            <i class="fas fa-envelope me-2" style="width:20px"></i> İletişim Mesajları
        </a>
        <a href="last-comments.php" class="nav-link <?php echo $current_page == 'last-comments.php' ? 'active' : ''; ?>">
            <i class="fas fa-comments me-2" style="width:20px"></i> Son Yorumlar
        </a>
        <hr class="bg-white-50 mx-3">
        <a href="/logout.php" class="nav-link">
            <i class="fas fa-sign-out-alt me-2" style="width:20px"></i> Çıkış Yap
        </a>
    </nav>
</div>