<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$stmt = $conn->prepare("SELECT id, order_code, created_at, total_amount, payment_method, payment_status FROM orders WHERE user_id = :uid ORDER BY created_at DESC");
$stmt->execute([':uid' => $_SESSION['user_id']]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$first_name = $_SESSION['first_name'] ?? $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Gadgets Universe</title>
    <link rel="stylesheet" href="css/style.css">

    <style>
    /* Force the background */
    body {
        background-image: url('images/background-texture.jpg') !important;
        background-size: cover !important;
        background-position: center !important;
        background-repeat: no-repeat !important;
        background-attachment: fixed !important;
    }
    
    /* Make wrappers transparent */
    .crud-container, 
    .admin-container, 
    .orders-container, 
    .auth-container {
        background: transparent !important;
    }

    /* Fix text colors for light background */
    h2, h3, p {
        color: #2c3e50 !important;
        text-shadow: none !important;
    }
</style>

    <style>
        .orders-container { max-width: 900px; margin: 0 auto; padding: 20px; background: transparent !important; }
        .orders-container h2 { color: #2c3e50 !important; text-shadow: none !important; margin-bottom: 20px; }
        .orders-table {
            width: 100%;
            background: #fff;
            border-collapse: collapse;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .orders-table th {
            background: #f4f4f4;
            padding: 12px 14px;
            text-align: left;
            font-size: 0.82rem;
            color: #2c3e50;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            border-bottom: 2px solid #ddd;
        }
        .orders-table td {
            padding: 12px 14px;
            border-bottom: 1px solid #eee;
            font-size: 0.88rem;
            color: #444;
        }
        .orders-table tr:last-child td { border-bottom: none; }
        .orders-table tr:hover td { background: #f8f9fa; }
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }
        .badge-paid    { background: #d4f4de; color: #1e7e34; }
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-failed  { background: #f8d7da; color: #721c24; }
        .order-code {
            font-family: 'Courier New', monospace;
            font-weight: 700;
            color: #2c3e50;
        }
        .btn-receipt {
            display: inline-block;
            background: #3498db;
            color: #fff;
            padding: 5px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.78rem;
            font-weight: 600;
        }
        .btn-receipt:hover { background: #2980b9; }
        .empty-state {
            background: #fff;
            padding: 50px 20px;
            text-align: center;
            border-radius: 8px;
            color: #7f8c8d;
            font-size: 0.95rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .empty-state .icon { font-size: 3rem; margin-bottom: 10px; }
        .back-store {
            display: inline-block;
            margin-top: 20px;
            color: #3498db;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
        }
        @media (max-width: 640px) {
            .orders-table thead { display: none; }
            .orders-table, .orders-table tbody, .orders-table tr, .orders-table td {
                display: block;
                width: 100%;
            }
            .orders-table td { padding: 10px 14px; border-bottom: 1px solid #eee; }
            .orders-table td::before {
                content: attr(data-label);
                display: block;
                font-size: 0.7rem;
                color: #7f8c8d;
                font-weight: 700;
                letter-spacing: 0.5px;
                text-transform: uppercase;
                margin-bottom: 3px;
            }
        }
    </style>
</head>
<body>
    <div class="orders-container">
        <h2>My Orders 📦</h2>

        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <div class="icon">📭</div>
                <p>You haven't placed any orders yet, <strong><?php echo htmlspecialchars($first_name); ?></strong>.</p>
                <p style="margin-top:15px;">
                    <a href="store.php" class="btn-receipt" style="padding:9px 20px;">Start Shopping</a>
                </p>
            </div>
        <?php else: ?>
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Order ID</th>
                        <th>Total (Rs)</th>
                        <th>Payment</th>
                        <th>Receipt</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td data-label="Date">
                                <?php echo date('M j, Y', strtotime($o['created_at'])); ?>
                            </td>
                            <td data-label="Order ID">
                                <span class="order-code"><?php echo htmlspecialchars($o['order_code']); ?></span>
                            </td>
                            <td data-label="Total (Rs)">
                                Rs <?php echo number_format($o['total_amount'], 2); ?>
                            </td>
                            <td data-label="Payment">
                                <span class="badge badge-<?php echo htmlspecialchars($o['payment_status']); ?>">
                                    <?php echo htmlspecialchars($o['payment_status']); ?>
                                </span>
                                <br>
                                <span style="font-size:0.72rem; color:#7f8c8d;">
                                    <?php echo $o['payment_method'] === 'mcb_juice' ? 'MCB Juice' : 'Cash on Delivery'; ?>
                                </span>
                            </td>
                            <td data-label="Receipt">
                                <a href="receipt.php?order=<?php echo urlencode($o['order_code']); ?>"
                                   class="btn-receipt">📄 PDF</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <a href="store.php" class="back-store" style="color: #3498db;">← Back to Store</a>
    </div>
</body>
</html>