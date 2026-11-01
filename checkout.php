<?php
session_start();
require_once 'config/db.php';

if (empty($_SESSION['cart'])) {
    header("Location: store.php");
    exit();
}

/* ==================== Config ==================== */

const MCB_JUICE_NUMBER = '57930098';

/* ==================== Helpers ==================== */

function format_order_code(int $id): string {
    return 'GU' . str_pad((string)$id, 7, '0', STR_PAD_LEFT);
}

function generate_payment_ref(): string {
    return 'JUICE-' . strtoupper(bin2hex(random_bytes(4)));
}

function format_juice_number(string $num): string {
    $num = preg_replace('/\D/', '', $num);
    return strlen($num) === 8 ? substr($num, 0, 4) . ' ' . substr($num, 4) : $num;
}

/* ==================== Load user profile ==================== */

$user_profile = [
    'first_name' => '',
    'last_name'  => '',
    'address'    => '',
];

$prof_stmt = $conn->prepare("SELECT first_name, last_name, address FROM users WHERE id = :id LIMIT 1");
$prof_stmt->execute([':id' => $_SESSION['user_id']]);
$row = $prof_stmt->fetch(PDO::FETCH_ASSOC);
if ($row) {
    $user_profile['first_name'] = $row['first_name'] ?? '';
    $user_profile['last_name']  = $row['last_name']  ?? '';
    $user_profile['address']    = $row['address']    ?? '';
}

/* ==================== Cart preview ==================== */

$cart_items_preview = [];
$cart_total_preview = 0;

$ids = array_keys($_SESSION['cart']);
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = $conn->prepare("SELECT * FROM gadgets WHERE id IN ($placeholders)");
$stmt->execute($ids);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($products as $p) {
    $qty = $_SESSION['cart'][$p['id']];
    // FIX: Use selling_price
    $sub = $p['selling_price'] * $qty;
    $cart_total_preview += $sub;
    $cart_items_preview[] = [
        'name'  => $p['name'],
        'qty'   => $qty,
        'price' => $p['selling_price'],
        'sub'   => $sub,
    ];
}

/* ==================== Handle POST ==================== */

$error_message       = "";
$success_message     = "";
$order_code_display  = "";
$order_items_display = [];
$order_total_display = 0;
$payment_display     = [];
$delivery_display    = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $payment_method  = $_POST['payment_method'] ?? '';
    $payment_ref_in  = trim($_POST['payment_ref'] ?? '');
    $juice_confirmed = !empty($_POST['juice_confirmed']);

    if (!in_array($payment_method, ['mcb_juice', 'cod'], true)) {
        $error_message = "Please select a payment method.";
    } elseif ($payment_method === 'mcb_juice' && !$juice_confirmed) {
        $error_message = "Please confirm you have sent the payment via MCB Juice before continuing.";
    } else {
        try {
            $conn->beginTransaction();

            $cart_valid = true;
            $order_items_data = [];
            $grand_total = 0;

            foreach ($_SESSION['cart'] as $product_id => $qty_ordered) {
                // FIX: Select selling_price instead of price
                $s = $conn->prepare("SELECT name, selling_price, quantity FROM gadgets WHERE id = :id FOR UPDATE");
                $s->execute([':id' => $product_id]);
                $row = $s->fetch(PDO::FETCH_ASSOC);

                if (!$row || $row['quantity'] < $qty_ordered) {
                    $cart_valid = false;
                    $error_message = "Sorry, " . htmlspecialchars($row['name'] ?? 'an item') . " does not have enough remaining stock.";
                    break;
                }

                // FIX: Use selling_price
                $subtotal = $row['selling_price'] * $qty_ordered;
                $grand_total += $subtotal;

                $order_items_data[] = [
                    'gadget_id' => (int)$product_id,
                    'name'      => $row['name'],
                    'price'     => $row['selling_price'],
                    'qty'       => $qty_ordered,
                    'subtotal'  => $subtotal,
                ];
            }

            if ($cart_valid) {
                if ($payment_method === 'mcb_juice') {
                    $payment_status = 'paid';
                    $payment_reference = preg_match('/^JUICE-[A-F0-9]{8}$/', $payment_ref_in)
                        ? $payment_ref_in
                        : generate_payment_ref();
                    $payment_phone_db = MCB_JUICE_NUMBER;
                } else {
                    $payment_status    = 'pending';
                    $payment_reference = null;
                    $payment_phone_db  = null;
                }

                // --- INSERT order with delivery snapshot ---
                $order_stmt = $conn->prepare(
                    "INSERT INTO orders
                        (order_code, user_id, username,
                         first_name, last_name, delivery_address,
                         total_amount,
                         payment_method, payment_status, payment_reference, payment_phone)
                     VALUES
                        (NULL, :uid, :uname,
                         :fname, :lname, :addr,
                         :total,
                         :pmethod, :pstatus, :pref, :pphone)"
                );
                $order_stmt->execute([
                    ':uid'     => $_SESSION['user_id'],
                    ':uname'   => $_SESSION['username'],
                    ':fname'   => $user_profile['first_name'],
                    ':lname'   => $user_profile['last_name'],
                    ':addr'    => $user_profile['address'],
                    ':total'   => $grand_total,
                    ':pmethod' => $payment_method,
                    ':pstatus' => $payment_status,
                    ':pref'    => $payment_reference,
                    ':pphone'  => $payment_phone_db,
                ]);
                $order_id   = (int)$conn->lastInsertId();
                $order_code = format_order_code($order_id);

                $conn->prepare("UPDATE orders SET order_code = :c WHERE id = :id")
                     ->execute([':c' => $order_code, ':id' => $order_id]);

                $item_stmt  = $conn->prepare(
                    "INSERT INTO order_items (order_id, gadget_id, product_name, unit_price, quantity, subtotal)
                     VALUES (:oid, :gid, :pname, :price, :qty, :sub)"
                );
                $stock_stmt = $conn->prepare(
                    "UPDATE gadgets SET quantity = quantity - :qty WHERE id = :id"
                );
                foreach ($order_items_data as $it) {
                    $item_stmt->execute([
                        ':oid'   => $order_id,
                        ':gid'   => $it['gadget_id'],
                        ':pname' => $it['name'],
                        ':price' => $it['price'],
                        ':qty'   => $it['qty'],
                        ':sub'   => $it['subtotal'],
                    ]);
                    $stock_stmt->execute([':qty' => $it['qty'], ':id' => $it['gadget_id']]);
                }

                $conn->commit();

                $order_code_display  = $order_code;
                $order_items_display = $order_items_data;
                $order_total_display = $grand_total;
                $payment_display = [
                    'method'    => $payment_method,
                    'status'    => $payment_status,
                    'reference' => $payment_reference,
                    'phone'     => $payment_phone_db,
                ];
                $delivery_display = [
                    'name'    => trim($user_profile['first_name'] . ' ' . $user_profile['last_name']),
                    'address' => $user_profile['address'],
                ];
                $_SESSION['cart'] = [];

                if ($payment_method === 'mcb_juice') {
                    $success_message = "<h3>🎉 Payment Recorded!</h3>"
                        . "<p>Your MCB Juice payment has been registered.</p>"
                        . "<p style='font-size:1.15rem;margin-top:10px;'>"
                        . "Order ID: <strong style='color:#27ae60;letter-spacing:1px;'>{$order_code}</strong>"
                        . "</p>"
                        . "<p style='font-size:1.05rem;margin-top:4px;'>"
                        . "Juice Reference: <strong style='color:#eb0045;'>" . htmlspecialchars($payment_reference) . "</strong>"
                        . "</p>"
                        . "<p style='font-size:0.9rem;color:#7f8c8d;margin-top:6px;'>"
                        . "Payment sent to: +230 " . htmlspecialchars(format_juice_number($payment_phone_db))
                        . "</p>";
                } else {
                    $success_message = "<h3>🎉 Order Placed!</h3>"
                        . "<p>Payment will be collected on delivery.</p>"
                        . "<p style='font-size:1.15rem;margin-top:10px;'>"
                        . "Order ID: <strong style='color:#27ae60;letter-spacing:1px;'>{$order_code}</strong>"
                        . "</p>";
                }
            } else {
                $conn->rollBack();
            }
        } catch (Exception $e) {
            $conn->rollBack();
            $error_message = "An error occurred during checkout: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Gadgets Universe</title>
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
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0 20px 0;
            font-size: 0.9rem;
        }
        .summary-table th,
        .summary-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }
        .summary-table th { background: #f4f4f4; font-weight: 600; color: #2c3e50; }
        .summary-table tfoot td { font-weight: bold; background: #eaeded; }
        .summary-table td:last-child,
        .summary-table th:last-child { text-align: right; }

        /* Delivery address card */
        .delivery-card {
            background: #f8f9fa;
            border-left: 4px solid #3498db;
            padding: 12px 14px;
            border-radius: 4px;
            margin: 10px 0 20px 0;
            font-size: 0.88rem;
            line-height: 1.5;
        }
        .delivery-card .label {
            font-size: 0.72rem;
            color: #7f8c8d;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 3px;
        }
        .delivery-card .name {
            font-weight: 700;
            color: #2c3e50;
            font-size: 0.95rem;
        }
        .delivery-card .addr { color: #555; margin-top: 2px; }
        .delivery-card .empty {
            color: #e74c3c;
            font-style: italic;
            font-size: 0.82rem;
        }

        .pay-methods { display: flex; flex-direction: column; gap: 10px; margin: 15px 0 20px 0; }
        .pay-option {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            border: 2px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            transition: border-color 0.15s, background 0.15s;
            background: #fff;
        }
        .pay-option:hover { border-color: #3498db; background: #f8fbff; }
        .pay-option.selected { border-color: #eb0045; background: #fff5f7; }
        .pay-option input[type="radio"] { transform: scale(1.3); cursor: pointer; }
        .pay-option .pay-info { flex: 1; }
        .pay-option .pay-title { font-weight: 700; color: #2c3e50; font-size: 1rem; }
        .pay-option .pay-desc  { font-size: 0.82rem; color: #7f8c8d; margin-top: 2px; }
        .pay-logo {
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 0.5px;
            padding: 6px 10px;
            border-radius: 4px;
            color: #fff;
        }
        .pay-logo.juice { background: #eb0045; }
        .pay-logo.cod   { background: #7f8c8d; }

        #juice-fields {
            display: none;
            margin-top: 8px;
            padding: 16px;
            background: #fff5f7;
            border: 1px dashed #eb0045;
            border-radius: 8px;
        }
        #juice-fields.active { display: block; }

        .juice-amount {
            text-align: center;
            font-size: 1.4rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 6px;
        }
        .juice-number-box {
            background: #fff;
            border: 2px solid #eb0045;
            border-radius: 10px;
            padding: 16px;
            text-align: center;
            margin: 12px 0;
            box-shadow: 0 2px 8px rgba(235, 0, 69, 0.12);
        }
        .juice-number-box .label {
            font-size: 0.78rem;
            color: #7f8c8d;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .juice-number-box .number {
            font-size: 1.8rem;
            font-weight: 800;
            color: #eb0045;
            letter-spacing: 2px;
            font-family: 'Courier New', monospace;
            user-select: all;
            -webkit-user-select: all;
        }
        .juice-number-box .prefix {
            font-size: 0.85rem;
            color: #7f8c8d;
            margin-top: 2px;
        }
        .juice-ref-box {
            background: #fff;
            border: 1px dashed #eb0045;
            border-radius: 6px;
            padding: 10px 14px;
            text-align: center;
            margin: 10px 0;
        }
        .juice-ref-box .label {
            font-size: 0.72rem;
            color: #7f8c8d;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .juice-ref-box .code {
            font-size: 1.05rem;
            font-weight: 700;
            color: #2c3e50;
            letter-spacing: 1px;
            font-family: 'Courier New', monospace;
            user-select: all;
            -webkit-user-select: all;
            margin-top: 2px;
        }
        .juice-steps {
            font-size: 0.85rem;
            color: #555;
            padding-left: 22px;
            margin-top: 8px;
            line-height: 1.7;
            text-align: left;
        }
        .juice-steps strong { color: #2c3e50; }
        .juice-confirm-label {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-top: 14px;
            padding: 12px;
            background: #fff;
            border: 1px solid #eb0045;
            border-radius: 6px;
            cursor: pointer;
        }
        .juice-confirm-label input { margin-top: 3px; transform: scale(1.3); cursor: pointer; }
        .juice-confirm-label span  { font-size: 0.88rem; color: #2c3e50; font-weight: 600; line-height: 1.3; }

        .btn-pay-juice { background: #eb0045 !important; }
        .btn-pay-cod   { background: #2ecc71 !important; }
    </style>
</head>
<body>
    <div class="auth-container" style="max-width: 640px;">
        <h2>Checkout</h2>

        <?php if (!empty($error_message)): ?>
            <div style="background:#f2dede; color:#a94442; padding:15px; border-radius:4px; margin-bottom:20px; font-weight:bold;">
                <?php echo $error_message; ?>
            </div>
            <p style="text-align:center;"><a href="cart.php" class="btn">Return to Cart</a></p>

        <?php elseif (!empty($success_message)): ?>
            <div style="background:#dff0d8; color:#3c763d; padding:15px; border-radius:4px; margin-bottom:20px;">
                <?php echo $success_message; ?>
            </div>

            <!-- Delivery info -->
            <div class="delivery-card">
                <div class="label">Delivering to</div>
                <div class="name">Full Name: <?php echo htmlspecialchars($delivery_display['name']); ?></div>
                <div class="addr">Address: <?php echo nl2br(htmlspecialchars($delivery_display['address'])); ?></div>
            </div>

            <h3 style="color:#2c3e50; font-size:1rem; margin-bottom:5px;">Order Summary</h3>
            <table class="summary-table">
                <thead>
                    <tr><th>Item</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($order_items_display as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo (int)$row['qty']; ?></td>
                            <td>Rs <?php echo number_format($row['price'], 2); ?></td>
                            <td>Rs <?php echo number_format($row['subtotal'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3">Grand Total</td>
                        <td>Rs <?php echo number_format($order_total_display, 2); ?></td>
                    </tr>
                </tfoot>
            </table>

            <div style="background:#f8f9fa; padding:12px 14px; border-radius:6px; font-size:0.9rem; margin-bottom:15px;">
                <strong>Payment method:</strong>
                <?php if ($payment_display['method'] === 'mcb_juice'): ?>
                    MCB Juice <span style="color:#eb0045; font-weight:700;">· Paid</span><br>
                    <span style="font-size:0.82rem; color:#7f8c8d;">Reference: <?php echo htmlspecialchars($payment_display['reference']); ?></span><br>
                    <span style="font-size:0.82rem; color:#7f8c8d;">Sent to: +230 <?php echo htmlspecialchars(format_juice_number($payment_display['phone'])); ?></span>
                <?php else: ?>
                    Cash on Delivery <span style="color:#f39c12; font-weight:700;">· Pending</span>
                <?php endif; ?>
            </div>

            <p style="text-align:center; margin-top:15px;">
                <a href="receipt.php?order=<?php echo urlencode($order_code_display); ?>"
                   class="btn" style="background:#3498db; margin-right:10px;">
                    📄 Download Receipt (PDF)
                </a>
                <a href="index.php" class="btn">Continue Browsing Store</a>
            </p>

        <?php else: ?>
            <p style="margin-bottom:15px; color:#7f8c8d; text-align:center;">
                Review your order and choose a payment method.
            </p>

            <!-- Delivery info -->
            <div class="delivery-card">
                <div class="label">Delivering to</div>
                <?php if (!empty($user_profile['first_name']) || !empty($user_profile['address'])): ?>
                    <div class="name">Full Name: 
                        <?php echo htmlspecialchars(trim($user_profile['first_name'] . ' ' . $user_profile['last_name'])); ?>
                    </div>
                    <div class="addr">Address: <?php echo nl2br(htmlspecialchars($user_profile['address'])); ?></div>
                <?php else: ?>
                    <div class="empty">
                        No address on file. Please update your profile or contact support before ordering.
                    </div>
                <?php endif; ?>
            </div>

            <h3 style="color:#2c3e50; font-size:1rem; margin-bottom:5px;">Your Order</h3>
            <table class="summary-table">
                <thead>
                    <tr><th>Item</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($cart_items_preview as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo (int)$row['qty']; ?></td>
                            <td>Rs <?php echo number_format($row['price'], 2); ?></td>
                            <td>Rs <?php echo number_format($row['sub'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3">Grand Total</td>
                        <td>Rs <?php echo number_format($cart_total_preview, 2); ?></td>
                    </tr>
                </tfoot>
            </table>

            <h3 style="color:#2c3e50; font-size:1rem; margin-bottom:5px;">Payment Method</h3>
            <form action="checkout.php" method="POST" id="checkout-form">
                <div class="pay-methods">
                    <label class="pay-option" id="opt-juice">
                        <input type="radio" name="payment_method" value="mcb_juice" required
                               onchange="selectMethod('mcb_juice')">
                        <div class="pay-logo juice">MCB</div>
                        <div class="pay-info">
                            <div class="pay-title">MCB Juice</div>
                            <div class="pay-desc">Send payment to our Juice number</div>
                        </div>
                    </label>

                    <label class="pay-option" id="opt-cod">
                        <input type="radio" name="payment_method" value="cod"
                               onchange="selectMethod('cod')">
                        <div class="pay-logo cod">COD</div>
                        <div class="pay-info">
                            <div class="pay-title">Cash on Delivery</div>
                            <div class="pay-desc">Pay in cash when your order arrives</div>
                        </div>
                    </label>
                </div>

                <div id="juice-fields">
                    <p style="font-weight:700; color:#eb0045; text-align:center; margin-bottom:6px; font-size:1.05rem;">
                        Pay via MCB Juice App
                    </p>

                    <div class="juice-amount">
                        Rs <?php echo number_format($cart_total_preview, 2); ?>
                    </div>

                    <div class="juice-number-box">
                        <div class="label">Send payment to</div>
                        <div class="number"><?php echo htmlspecialchars(format_juice_number(MCB_JUICE_NUMBER)); ?></div>
                        <div class="prefix">+230 · MCB Juice merchant</div>
                    </div>

                    <div class="juice-ref-box">
                        <div class="label">Payment reference (use in Juice description)</div>
                        <div class="code" id="juice-ref-code"></div>
                    </div>

                    <ol class="juice-steps">
                        <li>Open your <strong>MCB Juice</strong> app</li>
                        <li>Choose <strong>Send Money</strong> → enter the number above</li>
                        <li>Enter amount <strong>Rs <?php echo number_format($cart_total_preview, 2); ?></strong></li>
                        <li>In the description, paste the <strong>payment reference</strong> shown above</li>
                        <li>Complete the transfer, then tick the box below</li>
                    </ol>

                    <label class="juice-confirm-label">
                        <input type="checkbox" name="juice_confirmed" id="juice_confirmed">
                        <span>
                            I have sent Rs <?php echo number_format($cart_total_preview, 2); ?> to <strong>+230 <?php echo htmlspecialchars(format_juice_number(MCB_JUICE_NUMBER)); ?></strong> via MCB Juice
                        </span>
                    </label>

                    <input type="hidden" name="payment_ref" id="payment_ref_input" value="">
                </div>

                <button type="submit" class="btn btn-pay-cod" id="submit-btn" style="margin-top:15px;">
                    Confirm and Complete Purchase
                </button>
            </form>

            <p style="text-align: center; margin-top: 15px;">
                <a href="cart.php" style="text-decoration: none; color: #e74c3c;">← Back to Cart</a>
            </p>
        <?php endif; ?>
    </div>

    <script>
        let refRendered = false;

        function generateJuiceRef() {
            const bytes = new Uint8Array(4);
            if (window.crypto && crypto.getRandomValues) {
                crypto.getRandomValues(bytes);
            } else {
                for (let i = 0; i < bytes.length; i++) bytes[i] = Math.floor(Math.random() * 256);
            }
            return 'JUICE-' + Array.from(bytes).map(b => b.toString(16).padStart(2, '0')).join('').toUpperCase();
        }

        function selectMethod(method) {
            const juiceFields  = document.getElementById('juice-fields');
            const btn          = document.getElementById('submit-btn');
            const optJuice     = document.getElementById('opt-juice');
            const optCod       = document.getElementById('opt-cod');
            const confirmCheck = document.getElementById('juice_confirmed');

            if (method === 'mcb_juice') {
                juiceFields.classList.add('active');
                btn.textContent = 'Complete Order (Rs)';
                btn.classList.remove('btn-pay-cod');
                btn.classList.add('btn-pay-juice');
                optJuice.classList.add('selected');
                optCod.classList.remove('selected');

                if (!refRendered) {
                    const ref = generateJuiceRef();
                    document.getElementById('payment_ref_input').value = ref;
                    document.getElementById('juice-ref-code').textContent = ref;
                    refRendered = true;
                }
            } else {
                juiceFields.classList.remove('active');
                btn.textContent = 'Confirm Order — Pay on Delivery (Rs)';
                btn.classList.remove('btn-pay-juice');
                btn.classList.add('btn-pay-cod');
                optJuice.classList.remove('selected');
                optCod.classList.add('selected');
                if (confirmCheck) confirmCheck.checked = false;
            }
        }

        document.getElementById('checkout-form').addEventListener('submit', function (e) {
            const method = document.querySelector('input[name="payment_method"]:checked');
            if (method && method.value === 'mcb_juice') {
                if (!document.getElementById('juice_confirmed').checked) {
                    e.preventDefault();
                    alert('Please send the payment via MCB Juice and tick the confirmation box before continuing.');
                    return false;
                }
            }
        });
    </script>
</body>
</html>