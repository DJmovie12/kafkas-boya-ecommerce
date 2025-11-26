<?php
require_once '../includes/db_connect.php';
require_once '../includes/session.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$order_id = $_GET['order_id'] ?? 0;

// Sipariş bilgilerini getir
$stmt = $conn->prepare("
    SELECT o.*, u.username, u.email, u.address 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();
$stmt->close();

if (!$order) {
    die("
        <div style='text-align: center; padding: 50px;'>
            <h2>Sipariş bulunamadı!</h2>
            <p>Geçersiz sipariş numarası veya sipariş silinmiş olabilir.</p>
            <button onclick='window.close()' style='padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;'>
                Pencereyi Kapat
            </button>
        </div>
    ");
}

// Sipariş öğelerini getir
$items_stmt = $conn->prepare("
    SELECT oi.*, p.name, p.price 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$order_items = $items_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fatura - Kafkas Boya</title>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { 
                margin: 0 !important; 
                padding: 0 !important;
                background: white !important;
                font-size: 12px !important;
            }
            .invoice-container { 
                box-shadow: none !important; 
                border: 1px solid #ddd !important;
                margin: 0 !important;
                padding: 15mm !important;
                max-width: none !important;
                width: 210mm !important;
                min-height: 297mm !important;
            }
            * {
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
        }
        
        @page {
            size: A4;
            margin: 0;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
        }
        
        .invoice-container {
            width: 210mm;
            min-height: 297mm;
            background: white;
            border: 1px solid #ccc;
            padding: 20mm;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            box-sizing: border-box;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .header h1 {
            font-size: 28px;
            margin: 10px 0 5px 0;
            color: #2c3e50;
        }
        
        .header p {
            font-size: 14px;
            color: #666;
            margin: 0;
        }
        
        .company-info {
            float: left;
            width: 45%;
            font-size: 11px;
            line-height: 1.3;
        }
        
        .invoice-info {
            float: right;
            width: 45%;
            text-align: right;
            font-size: 11px;
            line-height: 1.3;
        }
        
        .clear { 
            clear: both; 
        }
        
        .customer-info {
            margin: 15px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
            font-size: 11px;
            line-height: 1.3;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 10px;
        }
        
        th, td {
            border: 1px solid #ddd;
            padding: 6px 4px;
            text-align: left;
        }
        
        th {
            background: #f8f9fa;
            font-weight: bold;
            font-size: 9px;
        }
        
        .total-row {
            background: #e9ecef !important;
            font-weight: bold;
        }
        
        .footer {
            margin-top: 25px;
            text-align: center;
            font-size: 9px;
            color: #666;
            line-height: 1.3;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        
        h3 {
            font-size: 12px;
            margin: 0 0 6px 0;
            color: #2c3e50;
        }
        
        .text-center {
            text-align: center;
        }
        
        .no-print {
            margin-top: 20px;
        }
        
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
            margin: 0 3px;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        /* Sütun genişliklerini optimize et */
        table th:nth-child(1),
        table td:nth-child(1) {
            width: 50%; /* Ürün Adı */
        }
        
        table th:nth-child(2),
        table td:nth-child(2) {
            width: 12%; /* Adet */
            text-align: center;
        }
        
        table th:nth-child(3),
        table td:nth-child(3) {
            width: 18%; /* Birim Fiyat */
            text-align: right;
        }
        
        table th:nth-child(4),
        table td:nth-child(4) {
            width: 20%; /* Toplam */
            text-align: right;
        }
        
        /* Toplam satırları için */
        tfoot td {
            text-align: right !important;
            padding: 8px 4px;
        }
        
        /* Zebra desenli satırlar */
        tbody tr:nth-child(even) {
            background-color: #fafafa;
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- Başlık -->
        <div class="header">
            <img src="../assets/img/kafkasboya-logo.png" alt="" style="max-height: 60px;">
            <p>Boya ve Dekorasyon Malzemeleri</p>
        </div>

        <!-- Firma ve Fatura Bilgileri -->
        <div class="company-info">
            <h3>SATICI BİLGİLERİ</h3>
            <p><strong>Kafkas Boya</strong><br>
            Örnek Mah. Ana Cad. No:123<br>
            İstanbul / Türkiye<br>
            Tel: (0212) 123 45 67<br>
            Vergi No: 1234567890</p>
        </div>

        <div class="invoice-info">
            <h3>FATURA BİLGİLERİ</h3>
            <p><strong>Fatura No:</strong> <?php echo $order['id']; ?><br>
            <strong>Fatura Tarihi:</strong> <?php echo date('d/m/Y', strtotime($order['created_at'])); ?><br>
            <strong>Sipariş No:</strong> <?php echo $order['id']; ?><br>
            <strong>Durum:</strong> <?php echo ucfirst($order['status']); ?></p>
        </div>
        <div class="clear"></div>

        <!-- Müşteri Bilgileri -->
        <div class="customer-info">
            <h3>MÜŞTERİ BİLGİLERİ</h3>
            <p><strong>Ad Soyad:</strong> <?php echo htmlspecialchars($order['username']); ?><br>
            <strong>E-posta:</strong> <?php echo htmlspecialchars($order['email']); ?><br>
            <strong>Adres:</strong> <?php echo nl2br(htmlspecialchars($order['address'] ?? 'Adres bilgisi bulunamadı')); ?></p>
        </div>

        <!-- Sipariş Kalemleri -->
        <table>
            <thead>
                <tr>
                    <th>Ürün Adı</th>
                    <th>Adet</th>
                    <th>Birim Fiyat</th>
                    <th>Toplam</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $subtotal = 0;
                if ($order_items && $order_items->num_rows > 0):
                while ($item = $order_items->fetch_assoc()): 
                    $item_total = $item['quantity'] * $item['price'];
                    $subtotal += $item_total;
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                    <td><?php echo $item['quantity']; ?> adet</td>
                    <td><?php echo number_format($item['price'], 2); ?> TL</td>
                    <td><?php echo number_format($item_total, 2); ?> TL</td>
                </tr>
                <?php endwhile; 
                else: ?>
                <tr>
                    <td colspan="4" style="text-align: center;">Siparişte ürün bulunamadı</td>
                </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="3" style="text-align: right;"><strong>Ara Toplam:</strong></td>
                    <td><strong><?php echo number_format($subtotal, 2); ?> TL</strong></td>
                </tr>
                <tr class="total-row">
                    <td colspan="3" style="text-align: right;"><strong>KDV (%18):</strong></td>
                    <td><strong><?php echo number_format($subtotal * 0.18, 2); ?> TL</strong></td>
                </tr>
                <tr class="total-row">
                    <td colspan="3" style="text-align: right;"><strong>Genel Toplam:</strong></td>
                    <td><strong><?php echo number_format($subtotal * 1.18, 2); ?> TL</strong></td>
                </tr>
            </tfoot>
        </table>

        <!-- Açıklama -->
        <div class="footer">
            <p><strong>Ödeme Şekli:</strong> <?php echo htmlspecialchars($order['payment_method'] ?? 'Kredi Kartı'); ?></p>
            <p>Faturanızı saklayınız. İade ve değişimlerde gereklidir.</p>
            <p><strong>Kafkas Boya - Teşekkür ederiz!</strong></p>
            <p>İletişim: (0212) 123 45 67 | info@kafkasboya.com</p>
        </div>

        <!-- Yazdırma Butonu -->
        <div class="text-center no-print mt-4">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print me-2"></i>Yazdır
            </button>
            <button onclick="window.close()" class="btn btn-secondary">
                <i class="fas fa-times me-2"></i>Kapat
            </button>
        </div>
    </div>

    <!-- Font Awesome için -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</body>
</html>