<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['username'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once 'config/db.php';

try {
    $stmt = $conn->prepare("SELECT * FROM gadgets ORDER BY created_at DESC");
    $stmt->execute();
    $gadgets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Database extraction failure: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Gadgets Universe</title>
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
</head>
<body>
    <!-- Using a standard container that inherits the global transparent rule -->
    <div class="crud-container" style="padding: 20px; max-width: 1400px; margin: 0 auto;">
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>! 👋</h2>
            <a href="logout.php" style="color: #e74c3c; font-weight: bold; text-decoration: none; background: rgba(255,255,255,0.8); padding: 6px 12px; border-radius: 4px;">Log Out</a>
        </div>
        
        <h3>Gadgets Universe Inventory</h3>
        
        <p style="margin: 15px 0; display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="create.php" class="btn" style="width: auto; text-decoration: none; display: inline-block; background: #2ecc71;">+ Add New Gadget</a>
            <a href="admin_orders.php" class="btn" style="width: auto; text-decoration: none; display: inline-block; background: #3498db;">📦 Manage Orders</a>
            <a href="accounting.php" class="btn" style="width: auto; text-decoration: none; display: inline-block; background: #9b59b6;">📊 Accounting</a>
            <a href="inventory.php" class="btn" style="width: auto; text-decoration: none; display: inline-block; background: #e67e22;">📋 Inventory</a>
        </p>

        <div style="overflow-x: auto;">
            <table style="background-color: #ffffff; width: 100%;">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Brand</th>
                        <th>Buy Price (Rs)</th>
                        <th>Sell Price (Rs)</th>
                        <th>Profit/Unit</th>
                        <th>Stock Qty</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($gadgets) > 0): ?>
                        <?php foreach ($gadgets as $gadget): ?>
                            <?php $profit = $gadget['selling_price'] - $gadget['buying_price']; ?>
                            <tr>
                                <td data-label="Gadget Name"><?php echo htmlspecialchars($gadget['name']); ?></td>
                                <td data-label="Brand"><?php echo htmlspecialchars($gadget['brand']); ?></td>
                                <td data-label="Buy Price">Rs <?php echo number_format($gadget['buying_price'], 2); ?></td>
                                <td data-label="Sell Price" style="font-weight: bold; color: #2ecc71;">
                                    Rs <?php echo number_format($gadget['selling_price'], 2); ?>
                                </td>
                                <td data-label="Profit" style="font-weight: bold; color: <?php echo $profit > 0 ? '#3498db' : '#e74c3c'; ?>;">
                                    Rs <?php echo number_format($profit, 2); ?>
                                </td>
                                <td data-label="Stock Qty" style="font-weight: bold; color: <?php echo $gadget['quantity'] > 0 ? '#2c3e50' : '#e74c3c'; ?>;">
                                    <?php echo htmlspecialchars($gadget['quantity']); ?>
                                </td>
                                <td data-label="Actions">
                                    <a href="update.php?id=<?php echo $gadget['id']; ?>" style="color: #3498db; margin-right: 12px; text-decoration: none; font-weight: bold;">Edit</a>
                                    <a href="delete.php?id=<?php echo $gadget['id']; ?>" onclick="return confirm('Are you sure you want to delete this item?');" style="color: #e74c3c; text-decoration: none; font-weight: bold;">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: #7f8c8d;">No catalog listings found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        
</body>
</html>