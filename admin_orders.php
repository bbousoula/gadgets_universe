<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['username'] !== 'admin') {
    header("Location: login.php");
    exit();
}

try {
    $orders_stmt = $conn->prepare("SELECT * FROM orders ORDER BY created_at DESC");
    $orders_stmt->execute();
    $orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);

    $items_stmt = $conn->prepare("SELECT * FROM order_items ORDER BY id ASC");
    $items_stmt->execute();
    $all_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $items_by_order = [];
    foreach ($all_items as $item) {
        $items_by_order[$item['order_id']][] = $item;
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Gadgets Universe</title>
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

        .filter-bar { display: flex; gap: 15px; background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; flex-wrap: wrap; align-items: center; }
        .filter-group { display: flex; flex-direction: column; gap: 5px; flex: 1; min-width: 200px; }
        .filter-group label { font-size: 0.75rem; font-weight: 700; color: #7f8c8d; text-transform: uppercase; letter-spacing: 0.5px; }
        .filter-input { padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 0.9rem; outline: none; transition: border-color 0.2s; }
        .filter-input:focus { border-color: #3498db; }

        .table-responsive { overflow-x: auto; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .orders-table { width: 100%; background: #fff; border-collapse: collapse; min-width: 1000px; }
        .orders-table th { background: #2c3e50; color: #fff; padding: 12px 15px; text-align: left; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .orders-table td { padding: 12px 15px; border-bottom: 1px solid #eee; font-size: 0.9rem; color: #333; vertical-align: middle; }
        .orders-table tr:hover td { background: #f8f9fa; }
        
        .status-select { padding: 6px 10px; border-radius: 4px; border: 1px solid #ccc; font-size: 0.85rem; font-weight: 600; cursor: pointer; background-color: #fff; outline: none; width: 100%; max-width: 140px; }
        .status-select.status-Pending { border-color: #f39c12; color: #f39c12; }
        .status-select.status-Processing { border-color: #3498db; color: #3498db; }
        .status-select.status-Shipped { border-color: #9b59b6; color: #9b59b6; }
        .status-select.status-Delivered { border-color: #2ecc71; color: #2ecc71; }
        .status-select.status-Cancelled { border-color: #e74c3c; color: #e74c3c; }

        .payment-status-pending { border-color: #f39c12; color: #f39c12; }
        .payment-status-paid { border-color: #2ecc71; color: #2ecc71; }
        .payment-status-failed { border-color: #e74c3c; color: #e74c3c; }
        .payment-status-refunded { border-color: #95a5a6; color: #95a5a6; }
        
        .btn-sm { padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 0.8rem; font-weight: 600; border: none; cursor: pointer; display: inline-block; transition: background 0.15s; }
        .btn-view { background: #3498db; color: #fff; }
        .btn-view:hover { background: #2980b9; }
        .btn-pdf { background: #e74c3c; color: #fff; margin-left: 5px; }
        .btn-pdf:hover { background: #c0392b; }
        
        .details-row { display: none; }
        .details-row.active { display: table-row; }
        .details-row td { padding: 0; background: #f4f6f8; border-bottom: 2px solid #ddd; }
        .details-content { padding: 15px 25px; display: flex; gap: 40px; flex-wrap: wrap; }
        .details-col { flex: 1; min-width: 250px; }
        .details-col h4 { margin: 0 0 10px 0; font-size: 0.85rem; color: #7f8c8d; text-transform: uppercase; letter-spacing: 0.5px; }
        .details-col p { margin: 0 0 5px 0; font-size: 0.9rem; color: #2c3e50; }
        
        .items-list { width: 100%; border-collapse: collapse; margin-top: 5px; }
        .items-list th, .items-list td { padding: 8px 10px; border-bottom: 1px solid #ddd; font-size: 0.85rem; text-align: left; }
        .items-list th { color: #7f8c8d; font-weight: 600; }

        .no-results { text-align: center; padding: 30px; color: #7f8c8d; font-size: 1rem; display: none; }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h2>📦 Orders</h2>
            <a href="dashboard.php" class="btn-sm" style="background:#7f8c8d; color:#fff;">← Back to Dashboard</a>
        </div>

        <div class="filter-bar">
            <div class="filter-group">
                <label>Search (Date, ID, Customer)</label>
                <input type="text" id="searchInput" class="filter-input" placeholder="Search...">
            </div>
            <div class="filter-group">
                <label>Payment Status</label>
                <select id="filterPayment" class="filter-input">
                    <option value="">All</option>
                    <option value="pending">Pending</option>
                    <option value="paid">Paid</option>
                    <option value="failed">Failed</option>
                    <option value="refunded">Refunded</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Delivery Status</label>
                <select id="filterDelivery" class="filter-input">
                    <option value="">All</option>
                    <option value="Pending">Pending</option>
                    <option value="Processing">Processing</option>
                    <option value="Shipped">Shipped</option>
                    <option value="Delivered">Delivered</option>
                    <option value="Cancelled">Cancelled</option>
                </select>
            </div>
        </div>

        <?php if (empty($orders)): ?>
            <div style="background:#fff; padding:40px; text-align:center; border-radius:8px; color:#7f8c8d;">
                No orders have been placed yet.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="orders-table" id="ordersTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Total (Rs)</th>
                            <th>Payment</th>
                            <th>Delivery Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $index => $o): ?>
                            <tr class="order-row" data-payment="<?php echo htmlspecialchars($o['payment_status']); ?>" data-delivery="<?php echo htmlspecialchars($o['delivery_status']); ?>">
                                <td class="col-date"><?php echo date('M j, Y g:i A', strtotime($o['created_at'])); ?></td>
                                <td class="col-id" style="font-weight:bold; color:#2c3e50; font-family:monospace;"><?php echo htmlspecialchars($o['order_code']); ?></td>
                                <td class="col-customer">
                                    <?php echo htmlspecialchars($o['first_name'] . ' ' . $o['last_name']); ?>
                                    <br><span style="font-size:0.75rem; color:#7f8c8d;">@<?php echo htmlspecialchars($o['username']); ?></span>
                                </td>
                                <td style="font-weight:bold; color:#2ecc71;">Rs <?php echo number_format($o['total_amount'], 2); ?></td>
                                <td>
                                    <select class="status-select payment-status-<?php echo htmlspecialchars($o['payment_status']); ?>" onchange="updatePaymentStatus(<?php echo $o['id']; ?>, this)">
                                        <option value="pending" <?php echo $o['payment_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="paid" <?php echo $o['payment_status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                        <option value="failed" <?php echo $o['payment_status'] === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                        <option value="refunded" <?php echo $o['payment_status'] === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                                    </select>
                                    <div style="font-size:0.75rem; color:#7f8c8d; margin-top:4px;">
                                        <?php echo $o['payment_method'] === 'mcb_juice' ? 'MCB Juice' : 'COD'; ?>
                                    </div>
                                </td>
                                <td>
                                    <select class="status-select status-<?php echo htmlspecialchars($o['delivery_status']); ?>" onchange="updateStatus(<?php echo $o['id']; ?>, this)">
                                        <option value="Pending" <?php echo $o['delivery_status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="Processing" <?php echo $o['delivery_status'] === 'Processing' ? 'selected' : ''; ?>>Processing</option>
                                        <option value="Shipped" <?php echo $o['delivery_status'] === 'Shipped' ? 'selected' : ''; ?>>Shipped</option>
                                        <option value="Delivered" <?php echo $o['delivery_status'] === 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                                        <option value="Cancelled" <?php echo $o['delivery_status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </td>
                                <td>
                                    <button class="btn-sm btn-view" onclick="toggleDetails(<?php echo $index; ?>)">Details</button>
                                    <a href="receipt.php?order=<?php echo urlencode($o['order_code']); ?>" class="btn-sm btn-pdf" target="_blank">PDF</a>
                                </td>
                            </tr>

                            <tr class="details-row" id="details-<?php echo $index; ?>">
                                <td colspan="7">
                                    <div class="details-content">
                                        <div class="details-col" style="flex: 0 0 300px;">
                                            <h4>Delivery Info</h4>
                                            <p><strong>Name:</strong> <?php echo htmlspecialchars($o['first_name'] . ' ' . $o['last_name']); ?></p>
                                            <p><strong>Address:</strong> <?php echo nl2br(htmlspecialchars($o['delivery_address'])); ?></p>
                                            <hr style="border:none; border-top:1px solid #ddd; margin:10px 0;">
                                            <p><strong>Method:</strong> <?php echo $o['payment_method'] === 'mcb_juice' ? 'MCB Juice' : 'Cash on Delivery'; ?></p>
                                            <?php if ($o['payment_method'] === 'mcb_juice'): ?>
                                                <p><strong>Juice Ref:</strong> <span style="color:#e74c3c; font-family:monospace;"><?php echo htmlspecialchars($o['payment_reference']); ?></span></p>
                                                <p><strong>Phone:</strong> +230 <?php echo htmlspecialchars($o['payment_phone']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="details-col">
                                            <h4>Items Purchased</h4>
                                            <table class="items-list">
                                                <thead>
                                                    <tr>
                                                        <th>Product</th>
                                                        <th>Qty</th>
                                                        <th>Unit Price</th>
                                                        <th>Subtotal</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php 
                                                    $order_items = $items_by_order[$o['id']] ?? [];
                                                    foreach ($order_items as $item): 
                                                    ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                                            <td><?php echo $item['quantity']; ?></td>
                                                            <td>Rs <?php echo number_format($item['unit_price'], 2); ?></td>
                                                            <td>Rs <?php echo number_format($item['subtotal'], 2); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="no-results" id="noResults">No orders match your search criteria.</div>
        <?php endif; ?>
    </div>

    <script>
        function toggleDetails(index) {
            document.getElementById('details-' + index).classList.toggle('active');
        }

        const searchInput = document.getElementById('searchInput');
        const filterPayment = document.getElementById('filterPayment');
        const filterDelivery = document.getElementById('filterDelivery');
        const rows = document.querySelectorAll('.order-row');
        const noResults = document.getElementById('noResults');

        function applyFilters() {
            const searchTerm = searchInput.value.toLowerCase();
            const paymentVal = filterPayment.value;
            const deliveryVal = filterDelivery.value;
            let visibleCount = 0;

            rows.forEach(row => {
                const dateText = row.querySelector('.col-date').textContent.toLowerCase();
                const idText = row.querySelector('.col-id').textContent.toLowerCase();
                const customerText = row.querySelector('.col-customer').textContent.toLowerCase();
                const rowPayment = row.getAttribute('data-payment');
                const rowDelivery = row.getAttribute('data-delivery');

                const matchesSearch = dateText.includes(searchTerm) || idText.includes(searchTerm) || customerText.includes(searchTerm);
                const matchesPayment = paymentVal === '' || rowPayment === paymentVal;
                const matchesDelivery = deliveryVal === '' || rowDelivery === deliveryVal;

                if (matchesSearch && matchesPayment && matchesDelivery) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }

                const detailsRow = row.nextElementSibling;
                if (detailsRow && detailsRow.classList.contains('details-row')) {
                    if (row.style.display === 'none') {
                        detailsRow.style.display = 'none';
                        detailsRow.classList.remove('active');
                    } else {
                        detailsRow.style.display = detailsRow.classList.contains('active') ? 'table-row' : 'none';
                    }
                }
            });

            if (noResults) {
                noResults.style.display = (visibleCount === 0 && rows.length > 0) ? 'block' : 'none';
            }
        }

        if (searchInput) searchInput.addEventListener('input', applyFilters);
        if (filterPayment) filterPayment.addEventListener('change', applyFilters);
        if (filterDelivery) filterDelivery.addEventListener('change', applyFilters);

        function updateStatus(orderId, selectElement) {
            const newStatus = selectElement.value;
            selectElement.style.opacity = '0.5';
            selectElement.closest('.order-row').setAttribute('data-delivery', newStatus);
            const formData = new FormData();
            formData.append('order_id', orderId);
            formData.append('delivery_status', newStatus);

            fetch('update_status.php', { method: 'POST', body: formData })
            .then(response => response.text())
            .then(data => {
                selectElement.style.opacity = '1';
                if (data.trim() === 'success') {
                    selectElement.className = 'status-select status-' + newStatus;
                } else {
                    alert('Error updating status: ' + data); window.location.reload(); 
                }
            }).catch(() => { selectElement.style.opacity = '1'; alert('Network error.'); window.location.reload(); });
        }

        function updatePaymentStatus(orderId, selectElement) {
            const newStatus = selectElement.value;
            selectElement.style.opacity = '0.5';
            selectElement.closest('.order-row').setAttribute('data-payment', newStatus);
            const formData = new FormData();
            formData.append('order_id', orderId);
            formData.append('payment_status', newStatus);

            fetch('update_status.php', { method: 'POST', body: formData })
            .then(response => response.text())
            .then(data => {
                selectElement.style.opacity = '1';
                if (data.trim() === 'success') {
                    selectElement.className = 'status-select payment-status-' + newStatus;
                } else {
                    alert('Error updating payment status: ' + data); window.location.reload(); 
                }
            }).catch(() => { selectElement.style.opacity = '1'; alert('Network error.'); window.location.reload(); });
        }
    </script>
</body>
</html>