<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['username'] !== 'admin') {
    header("Location: login.php");
    exit();
}

try {
    $ledger_sql = "
        SELECT 
            o.order_code,
            o.created_at,
            o.first_name,
            o.last_name,
            i.product_name,
            i.quantity,
            i.unit_price AS selling_price,
            IFNULL(g.buying_price, 0) AS buying_price,
            (i.unit_price - IFNULL(g.buying_price, 0)) AS unit_profit,
            ((i.unit_price - IFNULL(g.buying_price, 0)) * i.quantity) AS total_profit,
            i.subtotal AS total_revenue
        FROM order_items i
        JOIN orders o ON i.order_id = o.id
        LEFT JOIN gadgets g ON i.gadget_id = g.id
        ORDER BY o.created_at DESC, i.id ASC
    ";
    
    $stmt = $conn->prepare($ledger_sql);
    $stmt->execute();
    $ledger = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_revenue = 0;
    $total_cogs = 0;
    $total_profit = 0;
    $total_items_sold = 0;

    foreach ($ledger as $row) {
        $total_revenue += $row['total_revenue'];
        $total_cogs += ($row['buying_price'] * $row['quantity']);
        $total_profit += $row['total_profit'];
        $total_items_sold += $row['quantity'];
    }

    $profit_margin = ($total_revenue > 0) ? ($total_profit / $total_revenue) * 100 : 0;

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accounting & Financials - Gadgets Universe</title>
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
        .admin-container { max-width: 1400px; margin: 0 auto; padding: 20px; background: transparent !important; }
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .admin-header h2 { margin: 0; color: #2c3e50 !important; text-shadow: none !important; }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .stat-card { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); display: flex; align-items: center; gap: 15px; border-left: 5px solid #3498db; }
        .stat-card.green { border-left-color: #2ecc71; }
        .stat-card.red { border-left-color: #e74c3c; }
        .stat-card.orange { border-left-color: #f39c12; }
        
        .stat-icon { font-size: 2rem; background: #f4f6f8; width: 55px; height: 55px; display: flex; align-items: center; justify-content: center; border-radius: 50%; }
        .stat-info h3 { margin: 0 0 5px 0; font-size: 0.8rem; color: #7f8c8d; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-info .value { font-size: 1.6rem; font-weight: 800; color: #2c3e50; line-height: 1; }
        .stat-info .sub-value { font-size: 0.75rem; color: #7f8c8d; margin-top: 4px; }

        .table-responsive { overflow-x: auto; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); background: #fff; }
        .ledger-table { width: 100%; border-collapse: collapse; min-width: 1100px; }
        .ledger-table th { background: #2c3e50; color: #fff; padding: 12px 15px; text-align: left; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .ledger-table td { padding: 12px 15px; border-bottom: 1px solid #eee; font-size: 0.85rem; color: #333; vertical-align: middle; }
        .ledger-table tr:hover td { background: #f8f9fa; }
        
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        
        .profit-positive { color: #27ae60; font-weight: bold; }
        .profit-negative { color: #e74c3c; font-weight: bold; }
        
        .badge { display: inline-block; padding: 3px 8px; border-radius: 12px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; background: #e8f4fd; color: #2980b9; }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h2>📊 Accounting & Financials</h2>
            <a href="dashboard.php" class="btn" style="background:#7f8c8d; color:#fff; width:auto; text-decoration:none;">← Back to Dashboard</a>
        </div>

        <div class="stats-grid">
            <div class="stat-card green">
                <div class="stat-icon">💰</div>
                <div class="stat-info">
                    <h3>Gross Revenue</h3>
                    <div class="value">Rs <?php echo number_format($total_revenue, 2); ?></div>
                    <div class="sub-value">Total sales collected</div>
                </div>
            </div>
            
            <div class="stat-card red">
                <div class="stat-icon">📦</div>
                <div class="stat-info">
                    <h3>Cost of Goods (COGS)</h3>
                    <div class="value">Rs <?php echo number_format($total_cogs, 2); ?></div>
                    <div class="sub-value">Capital spent on sold items</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">📈</div>
                <div class="stat-info">
                    <h3>Net Profit</h3>
                    <div class="value">Rs <?php echo number_format($total_profit, 2); ?></div>
                    <div class="sub-value">Revenue minus COGS</div>
                </div>
            </div>

            <div class="stat-card orange">
                <div class="stat-icon">⚖️</div>
                <div class="stat-info">
                    <h3>Profit Margin</h3>
                    <div class="value"><?php echo number_format($profit_margin, 1); ?>%</div>
                    <div class="sub-value"><?php echo number_format($total_items_sold); ?> total items sold</div>
                </div>
            </div>
        </div>

        <h3 style="color: #2c3e50 !important; margin-bottom: 15px;">Sales Ledger</h3>
        
        <?php if (empty($ledger)): ?>
            <div style="background:#fff; padding:40px; text-align:center; border-radius:8px; color:#7f8c8d;">
                No sales data available yet.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="ledger-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Product</th>
                            <th class="text-center">Qty</th>
                            <th class="text-right">Buy Price</th>
                            <th class="text-right">Sell Price</th>
                            <th class="text-right">Total Revenue</th>
                            <th class="text-right">Total Profit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ledger as $row): ?>
                            <tr>
                                <td style="color: #7f8c8d;"><?php echo date('M j, Y g:i A', strtotime($row['created_at'])); ?></td>
                                <td><span class="badge"><?php echo htmlspecialchars($row['order_code']); ?></span></td>
                                <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                <td style="font-weight: 600; color: #2c3e50;"><?php echo htmlspecialchars($row['product_name']); ?></td>
                                <td class="text-center" style="font-weight: bold;"><?php echo $row['quantity']; ?></td>
                                <td class="text-right" style="color: #e74c3c;">Rs <?php echo number_format($row['buying_price'], 2); ?></td>
                                <td class="text-right" style="color: #2ecc71;">Rs <?php echo number_format($row['selling_price'], 2); ?></td>
                                <td class="text-right" style="font-weight: bold;">Rs <?php echo number_format($row['total_revenue'], 2); ?></td>
                                <td class="text-right <?php echo $row['total_profit'] >= 0 ? 'profit-positive' : 'profit-negative'; ?>">Rs <?php echo number_format($row['total_profit'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot style="background: #f4f6f8; font-weight: bold;">
                        <tr>
                            <td colspan="4" class="text-right" style="padding: 15px;">Grand Totals:</td>
                            <td class="text-center" style="padding: 15px;"><?php echo $total_items_sold; ?></td>
                            <td></td>
                            <td></td>
                            <td class="text-right" style="padding: 15px; color: #2c3e50;">Rs <?php echo number_format($total_revenue, 2); ?></td>
                            <td class="text-right <?php echo $total_profit >= 0 ? 'profit-positive' : 'profit-negative'; ?>" style="padding: 15px;">Rs <?php echo number_format($total_profit, 2); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>