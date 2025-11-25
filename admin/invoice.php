<?php
require_once '../includes/db_connect.php';
require_once '../includes/session.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Sipariş bilgilerini çek
$stmt = $pdo->prepare("
    SELECT o.*, u.username, u.email 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    die('Sipariş bulunamadı!');
}

// Sipariş kalemlerini çek
$stmt = $pdo->prepare("
    SELECT oi.*, p.name, b.name as brand_name 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    LEFT JOIN brands b ON p.brand_id = b.id 
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll();

// KDV hesaplama
$subtotal = $order['total_amount'] / 1.20; // %20 KDV varsayımı
$tax = $order['total_amount'] - $subtotal;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fatura #<?php echo $order['id']; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #333;
        }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            border-bottom: 3px solid #007bff;
            padding-bottom: 20px;
        }
        .company-info {
            flex: 1;
        }
        .company-info h1 {
            color: #007bff;
            margin: 0 0 10px 0;
        }
        .invoice-info {
            text-align: right;
        }
        .invoice-info h2 {
            color: #007bff;
            margin: 0 0 10px 0;
        }
        .customer-info, .order-info {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .customer-info h3, .order-info h3 {
            margin: 0 0 10px 0;
            color: #007bff;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th {
            background: #007bff;
            color: white;
            padding: 12px;
            text-align: left;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .totals {
            margin-top: 20px;
            text-align: right;
        }
        .totals table {
            margin-left: auto;
            width: 300px;
        }
        .totals td {
            border: none;
            padding: 8px;
        }
        .grand-total {
            font-size: 1.3em;
            font-weight: bold;
            color: #007bff;
            border-top: 2px solid #007bff !important;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 0.9em;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        @media print {
            .no-print {
                display: none;
            }
        }
        .print-button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 5px;
            font-size: 16px;
            margin-bottom: 20px;
        }
        .print-button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <button onclick="window.print()" class="print-button no-print">🖨️ Yazdır</button>
    
    <div class="invoice-header">
        <div class="company-info">
            <h1>KAFKAS BOYA</h1>
            <p>Boya ve Vernik Ticaret<br>
            İstanbul, Türkiye<br>
            Tel: +90 XXX XXX XX XX<br>
            info@kafkasboya.com</p>
        </div>
        <div class="invoice-info">
            <h2>FATURA</h2>
            <p><strong>Fatura No:</strong> #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?><br>
            <strong>Tarih:</strong> <?php echo date('d.m.Y', strtotime($order['created_at'])); ?><br>
            <strong>Durum:</strong> <?php 
                echo match($order['status']) {
                    'pending' => 'Beklemede',
                    'processing' => 'İşleniyor',
                    'shipped' => 'Kargoda',
                    'delivered' => 'Teslim Edildi',
                    'cancelled' => 'İptal',
                    default => 'Bilinmiyor'
                };
            ?></p>
        </div>
    </div>
    
    <div class="customer-info">
        <h3>Müşteri Bilgileri</h3>
        <p><strong>Ad:</strong> <?php echo htmlspecialchars($order['username']); ?><br>
        <strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Ürün Adı</th>
                <th>Marka</th>
                <th>Birim Fiyat</th>
                <th>Miktar</th>
                <th>Toplam</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($order_items as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                    <td><?php echo htmlspecialchars($item['brand_name']); ?></td>
                    <td>₺<?php echo number_format($item['price'], 2); ?></td>
                    <td><?php echo $item['quantity']; ?> adet</td>
                    <td>₺<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div class="totals">
        <table>
            <tr>
                <td>Ara Toplam:</td>
                <td>₺<?php echo number_format($subtotal, 2); ?></td>
            </tr>
            <tr>
                <td>KDV (%20):</td>
                <td>₺<?php echo number_format($tax, 2); ?></td>
            </tr>
            <tr class="grand-total">
                <td>GENEL TOPLAM:</td>
                <td>₺<?php echo number_format($order['total_amount'], 2); ?></td>
            </tr>
        </table>
    </div>
    
    <div class="footer">
        <p>Bu fatura elektronik ortamda oluşturulmuştur.<br>
        Kafkas Boya - Kaliteli Boya ve Vernik Çözümleri<br>
        www.kafkasboya.com</p>
    </div>
</body>
</html>