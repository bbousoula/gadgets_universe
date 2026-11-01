<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/db.php';

// Handle clearing the whole cart
if (isset($_GET['action']) && $_GET['action'] == 'clear') {
    $_SESSION['cart'] = [];
    header("Location: store.php");
    exit();
}

// Handle removing a single item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_item'])) {
    $remove_id = (int)$_POST['remove_item'];
    if (isset($_SESSION['cart'][$remove_id])) {
        unset($_SESSION['cart'][$remove_id]);
    }
    header("Location: cart.php");
    exit();
}

$cart_items = [];
$grand_total = 0;

if (!empty($_SESSION['cart'])) {
    $ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $conn->prepare("SELECT * FROM gadgets WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($products as $product) {
        $quantity = $_SESSION['cart'][$product['id']];
        $subtotal = $product['selling_price'] * $quantity;
        $grand_total += $subtotal;

        $product['quantity_ordered'] = $quantity;
        $product['subtotal'] = $subtotal;
        $cart_items[] = $product;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Shopping Cart</title>
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
        .btn-remove { background: #e74c3c; color: #fff; border: none; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; font-size: 14px; font-weight: bold; display: inline-flex; align-items: center; justify-content: center; transition: background 0.15s; }
        .btn-remove:hover { background: #c0392b; }
        @media (max-width: 768px) { .btn-remove { width: 40px; height: 40px; font-size: 18px; } }
    </style>
</head>
<body>
    <div class="crud-container" style="padding: 20px; max-width: 1200px; margin: 0 auto;">
        <h2 style="margin-bottom: 20px;">
            <?php echo htmlspecialchars($_SESSION['username'] ?? 'Your'); ?>'s Shopping Cart 🛒
        </h2>

        <table style="background-color: #fff;">
            <thead>
                <tr>
                    <th>Item Description</th>
                    <th>Unit Price (Rs)</th>
                    <th>Quantity</th>
                    <th>Subtotal (Rs)</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($cart_items)): ?>
                    <?php foreach ($cart_items as $item): ?>
                        <tr>
                            <td data-label="Item Description"><?php echo htmlspecialchars($item['name']); ?></td>
                            <td data-label="Unit Price">Rs <?php echo number_format($item['selling_price'], 2); ?></td>
                            <td data-label="Quantity"><?php echo $item['quantity_ordered']; ?></td>
                            <td data-label="Subtotal">Rs <?php echo number_format($item['subtotal'], 2); ?></td>
                            <td data-label="Actions">
                                <form method="POST" action="cart.php" style="margin:0;" onsubmit="return confirm('Remove this item from your cart?');">
                                    <input type="hidden" name="remove_item" value="<?php echo (int)$item['id']; ?>">
                                    <button type="submit" class="btn-remove" title="Remove item" aria-label="Remove item">&times;</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr style="font-weight: bold; background: #eaeded;">
                        <td colspan="4" style="text-align: right;">Grand Total (Rs):</td>
                        <td>Rs <?php echo number_format($grand_total, 2); ?></td>
                    </tr>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: #7f8c8d;">Your active shopping cart session is currently empty.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div style="display: flex; gap: 15px; margin-top: 25px; flex-wrap: wrap;">
            <a href="store.php" class="btn" style="background:#7f8c8d; text-decoration:none; text-align:center; line-height:24px;">Continue Shopping</a>
            <?php if (!empty($cart_items)): ?>
                <a href="checkout.php" class="btn" style="background:#2ecc71; text-decoration:none; text-align:center; line-height:24px;">Proceed to Checkout</a>
                <a href="cart.php?action=clear" class="btn" style="background:#e74c3c; text-decoration:none; text-align:center; line-height:24px;">Clear Cart</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>