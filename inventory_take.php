<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['username'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$take_id = (int)($_GET['id'] ?? 0);
if ($take_id <= 0) { header("Location: inventory.php"); exit(); }

$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_take'])) {
    try {
        $conn->beginTransaction();
        foreach ($_POST['actual_stock'] as $item_id => $actual) {
            $actual = max(0, (int)$actual);
            $stmt = $conn->prepare("SELECT expected_stock FROM inventory_take_items WHERE id = :id");
            $stmt->execute([':id' => $item_id]);
            $expected = $stmt->fetchColumn();
            $variance = $actual - $expected;

            $upd = $conn->prepare("UPDATE inventory_take_items SET actual_stock = :a, variance = :v WHERE id = :id");
            $upd->execute([':a' => $actual, ':v' => $variance, ':id' => $item_id]);

            $g_stmt = $conn->prepare("UPDATE gadgets g JOIN inventory_take_items i ON g.id = i.gadget_id SET g.quantity = :a WHERE i.id = :id");
            $g_stmt->execute([':a' => $actual, ':id' => $item_id]);
        }
        $conn->commit();
        $message = "<div style='background:#d4f4de; color:#1e7e34; padding:15px; border-radius:4px; margin-bottom:20px; font-weight:bold;'>Stock take saved! Store inventory updated.</div>";
    } catch (Exception $e) {
        $conn->rollBack();
        $message = "<div style='background:#f8d7da; color:#721c24; padding:15px; border-radius:4px; margin-bottom:20px;'>Error: " . $e->getMessage() . "</div>";
    }
}

$take_stmt = $conn->prepare("SELECT * FROM inventory_takes WHERE id = :id");
$take_stmt->execute([':id' => $take_id]);
$take = $take_stmt->fetch(PDO::FETCH_ASSOC);
if (!$take) { header("Location: inventory.php"); exit(); }

$items_stmt = $conn->prepare("SELECT i.*, g.name, g.buying_price FROM inventory_take_items i JOIN gadgets g ON i.gadget_id = g.id WHERE i.take_id = :tid ORDER BY g.name ASC");
$items_stmt->execute([':tid' => $take_id]);
$items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Take: <?php echo htmlspecialchars($take['take_name']); ?></title>
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
        .admin-header h2 { color: #2c3e50 !important; text-shadow: none !important; }
        
        .panel { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { background: #2c3e50; color: #fff; padding: 12px; text-align: left; font-size: 0.85rem; }
        td { padding: 10px 12px; border-bottom: 1px solid #eee; font-size: 0.9rem; vertical-align: middle; }
        
        .text-center { text-align: center; }
        .input-actual { width: 80px; padding: 8px; font-size: 1rem; text-align: center; border: 2px solid #3498db; border-radius: 4px; font-weight: bold; }
        
        .variance-neg { color: #e74c3c; font-weight: bold; }
        .variance-pos { color: #2ecc71; font-weight: bold; }
        .variance-zero { color: #7f8c8d; }
        
        .btn-save { background: #2ecc71; color: #fff; border: none; padding: 12px 25px; border-radius: 4px; font-size: 1rem; font-weight: bold; cursor: pointer; width: 100%; margin-top: 20px; transition: 0.2s; }
        .btn-save:hover { background: #27ae60; }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <div>
                <h2 style="margin:0;">Stock Take: <?php echo htmlspecialchars($take['take_name']); ?></h2>
                <p style="margin:5px 0 0 0; color:#555;">Date: <?php echo date('F j, Y', strtotime($take['take_date'])); ?></p>
            </div>
            <a href="inventory.php" class="btn" style="background:#7f8c8d; color:#fff; width:auto; text-decoration:none;">← Back to Inventory</a>
        </div>

        <div class="panel">
            <?php echo $message; ?>
            
            <form method="POST">
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="text-center">Opening Stock</th>
                                <th class="text-center">Sold Since Last</th>
                                <th class="text-center" style="background:#2980b9;">Expected (System)</th>
                                <th class="text-center" style="background:#9b59b6;">Actual (Physical Count)</th>
                                <th class="text-center">Variance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                                    <td class="text-center" style="color:#7f8c8d;"><?php echo $item['opening_stock']; ?></td>
                                    <td class="text-center" style="color:#e74c3c;">-<?php echo $item['sold_since_last']; ?></td>
                                    <td class="text-center" style="font-weight:bold; font-size:1.1rem; background:#f0f8ff;"><?php echo $item['expected_stock']; ?></td>
                                    
                                    <td class="text-center" style="background:#faf0ff;">
                                        <input type="number" name="actual_stock[<?php echo $item['id']; ?>]" 
                                               class="input-actual" 
                                               value="<?php echo $item['actual_stock'] > 0 ? $item['actual_stock'] : $item['expected_stock']; ?>" 
                                               min="0" required>
                                    </td>
                                    
                                    <td class="text-center">
                                        <?php 
                                            if ($item['actual_stock'] > 0 || $item['variance'] != 0) {
                                                $v = $item['variance'];
                                                if ($v == 0) echo "<span class='variance-zero'>0 (OK)</span>";
                                                elseif ($v < 0) echo "<span class='variance-neg'>$v (Short)</span>";
                                                else echo "<span class='variance-pos'>+$v (Over)</span>";
                                            } else {
                                                echo "<span style='color:#ccc;'>—</span>";
                                            }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <button type="submit" name="save_take" class="btn-save">Save Count & Update Store Inventory</button>
            </form>
        </div>
    </div>
</body>
</html>