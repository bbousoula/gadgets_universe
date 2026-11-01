<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['username'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if (isset($_POST['start_take'])) {
    $take_name = trim($_POST['take_name']);
    $take_date = $_POST['take_date'];

    try {
        $conn->beginTransaction();
        $stmt = $conn->prepare("INSERT INTO inventory_takes (take_date, take_name) VALUES (:d, :n)");
        $stmt->execute([':d' => $take_date, ':n' => $take_name]);
        $take_id = $conn->lastInsertId();

        $gadgets = $conn->query("SELECT id FROM gadgets")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($gadgets as $g) {
            $gid = $g['id'];
            $live_stmt = $conn->prepare("SELECT quantity FROM gadgets WHERE id = :gid");
            $live_stmt->execute([':gid' => $gid]);
            $opening_stock = $live_stmt->fetchColumn();
            $expected_stock = $opening_stock;
            $sold_since_last = 0;

            $ins_stmt = $conn->prepare("INSERT INTO inventory_take_items (take_id, gadget_id, opening_stock, sold_since_last, expected_stock) VALUES (:tid, :gid, :open, :sold, :exp)");
            $ins_stmt->execute([':tid' => $take_id, ':gid' => $gid, ':open' => $opening_stock, ':sold' => $sold_since_last, ':exp' => $expected_stock]);
        }

        $conn->commit();
        header("Location: inventory_take.php?id=" . $take_id);
        exit();
    } catch (Exception $e) {
        $conn->rollBack();
        $error = "Error starting stock take: " . $e->getMessage();
    }
}

$live_inventory = $conn->query("SELECT * FROM gadgets ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$past_takes = $conn->query("SELECT * FROM inventory_takes ORDER BY take_date DESC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - Gadgets Universe</title>
    <link rel="stylesheet" href="css/style.css">

    <style>
    /* Force the background */
    body {
        background-color: #dae0ea;
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

        .panel { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 25px; }
        .panel h3 { margin-top: 0; color: #2c3e50; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 15px; }

        .form-row { display: flex; gap: 15px; align-items: flex-end; }
        .form-group { display: flex; flex-direction: column; gap: 5px; }
        .form-group label { font-size: 0.8rem; font-weight: 700; color: #7f8c8d; text-transform: uppercase; }
        .form-group input { padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 0.9rem; }
        
        .btn-start { background: #9b59b6; color: #fff; border: none; padding: 10px 20px; border-radius: 4px; font-weight: bold; cursor: pointer; transition: 0.2s; height: 40px; }
        .btn-start:hover { background: #8e44ad; }

        table { width: 100%; border-collapse: collapse; }
        th { background: #2c3e50; color: #fff; padding: 12px 15px; text-align: left; font-size: 0.85rem; text-transform: uppercase; }
        td { padding: 12px 15px; border-bottom: 1px solid #eee; font-size: 0.9rem; color: #333; vertical-align: middle; }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .stock-ok { color: #2ecc71; font-weight: bold; }
        .stock-low { color: #f39c12; font-weight: bold; }
        .stock-out { color: #e74c3c; font-weight: bold; }
        
        .btn-view { background: #3498db; color: #fff; text-decoration: none; padding: 6px 12px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; display: inline-block; }
        .btn-view:hover { background: #2980b9; }

        .error-msg { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h2>📋 Inventory Management</h2>
            <a href="dashboard.php" class="btn" style="background:#7f8c8d; color:#fff; width:auto; text-decoration:none;">← Back to Dashboard</a>
        </div>

        <?php if(isset($error)) echo "<div class='error-msg'>$error</div>"; ?>

        <div class="panel" style="border-left: 5px solid #9b59b6;">
            <h3>Start New Inventory Take</h3>
            <form method="POST" class="form-row">
                <div class="form-group" style="flex: 1;">
                    <label>Stock Take Name</label>
                    <input type="text" name="take_name" placeholder="e.g. End of Month Count - October" required>
                </div>
                <div class="form-group" style="flex: 0 0 200px;">
                    <label>Date</label>
                    <input type="date" name="take_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <button type="submit" name="start_take" class="btn-start">Start Stock Take</button>
            </form>
        </div>

        <div style="display: flex; gap: 25px; flex-wrap: wrap;">
            <div class="panel" style="flex: 2; min-width: 500px;">
                <h3>Live Inventory (System Stock)</h3>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="text-center">Current Stock</th>
                                <th class="text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($live_inventory as $item): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                                    <td class="text-center" style="font-size: 1.1rem;"><?php echo $item['quantity']; ?></td>
                                    <td class="text-right">
                                        <?php if ($item['quantity'] > 10): ?>
                                            <span class="stock-ok">Healthy</span>
                                        <?php elseif ($item['quantity'] > 0): ?>
                                            <span class="stock-low">Low Stock</span>
                                        <?php else: ?>
                                            <span class="stock-out">Out of Stock</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel" style="flex: 1; min-width: 300px;">
                <h3>Past Stock Takes</h3>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Name</th>
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($past_takes)): ?>
                                <tr><td colspan="3" class="text-center" style="color:#7f8c8d;">No takes recorded.</td></tr>
                            <?php else: ?>
                                <?php foreach ($past_takes as $t): ?>
                                    <tr>
                                        <td><?php echo date('M j, Y', strtotime($t['take_date'])); ?></td>
                                        <td style="font-size: 0.85rem;"><?php echo htmlspecialchars($t['take_name']); ?></td>
                                        <td class="text-right">
                                            <a href="inventory_take.php?id=<?php echo $t['id']; ?>" class="btn-view">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>