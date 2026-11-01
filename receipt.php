<?php
session_start();
require_once 'config/db.php';
require_once 'fpdf.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (empty($_GET['order'])) {
    http_response_code(400);
    exit("No order specified.");
}

$order_code = trim($_GET['order']);

$stmt = $conn->prepare("SELECT * FROM orders WHERE order_code = :c LIMIT 1");
$stmt->execute([':c' => $order_code]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    http_response_code(404);
    exit("Order not found.");
}

$is_admin   = ($_SESSION['username'] === 'admin');
$owns_order = ((int)$order['user_id'] === (int)$_SESSION['user_id']);

if (!$owns_order && !$is_admin) {
    http_response_code(403);
    exit("You don't have permission to view this receipt.");
}

$items_stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = :oid ORDER BY id ASC");
$items_stmt->execute([':oid' => $order['id']]);
$items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

$scheme    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host      = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base_path = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$store_url = $scheme . '://' . $host . $base_path . '/store.php';

function pdf_text($str) {
    $str = (string)$str;
    if ($str === '') return '';
    if (function_exists('iconv')) {
        $converted = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $str);
        if ($converted !== false) return $converted;
    }
    if (function_exists('mb_convert_encoding')) {
        return @mb_convert_encoding($str, 'ISO-8859-1', 'UTF-8');
    }
    return preg_replace('/[^\x20-\x7E]/', '?', $str);
}

function fmt_juice($num) {
    $num = preg_replace('/\D/', '', (string)$num);
    return strlen($num) === 8 ? substr($num, 0, 4) . ' ' . substr($num, 4) : $num;
}

$pdf = new FPDF('P', 'mm', 'A4');
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 15);
$pdf->AddPage();

$logo_path = __DIR__ . '/images/logo_horizontal.png';
$header_y  = $pdf->GetY();

if (file_exists($logo_path)) {
    $logo_w   = 80;
    $img_info = @getimagesize($logo_path);
    $logo_h   = $img_info ? ($logo_w * $img_info[1] / $img_info[0]) : 20;
    $logo_x   = (210 - $logo_w) / 2;

    $pdf->Image($logo_path, $logo_x, $header_y, $logo_w, 0, '', $store_url);
    $pdf->SetY($header_y + $logo_h + 3);
} else {
    $pdf->SetFont('Arial', 'B', 22);
    $pdf->SetTextColor(235, 0, 69);
    $pdf->Cell(0, 12, 'GADGETS UNIVERSE', 0, 1, 'C', false, $store_url);
}

$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(127, 140, 141);
$pdf->Cell(0, 5, 'Port Louis, Mauritius  |  contact@gadgetsuniverse.mu', 0, 1, 'C');

$pdf->Ln(4);
$pdf->SetDrawColor(220, 220, 220);
$pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
$pdf->Ln(8);

$pdf->SetFont('Arial', 'B', 16);
$pdf->SetTextColor(44, 62, 80);
$pdf->Cell(0, 10, 'RECEIPT', 0, 1);
$pdf->Ln(2);

$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(85, 85, 85);
$date_formatted = date('F j, Y', strtotime($order['created_at']));

$pdf->Cell(45, 6, 'Order ID:', 0, 0);
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetTextColor(44, 62, 80);
$pdf->Cell(0, 6, $order['order_code'], 0, 1);

$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(85, 85, 85);
$pdf->Cell(45, 6, 'Date:', 0, 0);
$pdf->SetTextColor(44, 62, 80);
$pdf->Cell(0, 6, $date_formatted, 0, 1);

$pdf->SetTextColor(85, 85, 85);
$pdf->Cell(45, 6, 'Payment Status:', 0, 0);
if ($order['payment_status'] === 'paid') {
    $pdf->SetTextColor(39, 174, 96);
} else {
    $pdf->SetTextColor(243, 156, 18);
}
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 6, strtoupper($order['payment_status']), 0, 1);
$pdf->Ln(6);

$pdf->SetFont('Arial', 'B', 10);
$pdf->SetTextColor(44, 62, 80);
$pdf->Cell(0, 7, 'BILLED TO', 0, 1);

$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(85, 85, 85);
$customer_name = trim($order['first_name'] . ' ' . $order['last_name']);
if ($customer_name === '') $customer_name = $order['username'];
$pdf->Cell(0, 5.5, pdf_text($customer_name), 0, 1);

if (!empty($order['delivery_address'])) {
    $pdf->MultiCell(0, 5.5, pdf_text($order['delivery_address']));
}
$pdf->Ln(6);

$pdf->SetFont('Arial', 'B', 10);
$pdf->SetTextColor(44, 62, 80);
$pdf->Cell(0, 7, 'ITEMS', 0, 1);

$w_item  = 95;
$w_qty   = 20;
$w_price = 32.5;
$w_total = 32.5;

$pdf->SetFillColor(244, 244, 244);
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetTextColor(44, 62, 80);
$pdf->Cell($w_item,  8, ' Item',        1, 0, 'L', true);
$pdf->Cell($w_qty,   8, 'Qty',          1, 0, 'C', true);
$pdf->Cell($w_price, 8, 'Price (Rs) ',  1, 0, 'R', true);
$pdf->Cell($w_total, 8, 'Total (Rs) ',  1, 1, 'R', true);

$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(85, 85, 85);

foreach ($items as $item) {
    $name = pdf_text($item['product_name']);
    if (strlen($name) > 55) $name = substr($name, 0, 52) . '...';

    $pdf->Cell($w_item,  7, ' ' . $name,                                 1, 0, 'L');
    $pdf->Cell($w_qty,   7, (int)$item['quantity'],                      1, 0, 'C');
    $pdf->Cell($w_price, 7, number_format($item['unit_price'], 2) . ' ', 1, 0, 'R');
    $pdf->Cell($w_total, 7, number_format($item['subtotal'],   2) . ' ', 1, 1, 'R');
}

$pdf->SetFillColor(234, 237, 237);
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetTextColor(44, 62, 80);
$pdf->Cell($w_item + $w_qty + $w_price, 9, 'GRAND TOTAL ', 1, 0, 'R', true);
$pdf->Cell($w_total, 9, 'Rs ' . number_format($order['total_amount'], 2) . ' ', 1, 1, 'R', true);
$pdf->Ln(8);

$pdf->SetFont('Arial', 'B', 10);
$pdf->SetTextColor(44, 62, 80);
$pdf->Cell(0, 7, 'PAYMENT INFORMATION', 0, 1);

$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(85, 85, 85);
$method_label = ($order['payment_method'] === 'mcb_juice') ? 'MCB Juice' : 'Cash on Delivery';
$pdf->Cell(45, 6, 'Method:', 0, 0);
$pdf->SetTextColor(44, 62, 80);
$pdf->Cell(0, 6, $method_label, 0, 1);

if ($order['payment_method'] === 'mcb_juice' && !empty($order['payment_reference'])) {
    $pdf->SetTextColor(85, 85, 85);
    $pdf->Cell(45, 6, 'Juice Reference:', 0, 0);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetTextColor(235, 0, 69);
    $pdf->Cell(0, 6, $order['payment_reference'], 0, 1);
    $pdf->SetFont('Arial', '', 10);
}

if ($order['payment_method'] === 'mcb_juice' && !empty($order['payment_phone'])) {
    $pdf->SetTextColor(85, 85, 85);
    $pdf->Cell(45, 6, 'Sent to:', 0, 0);
    $pdf->SetTextColor(44, 62, 80);
    $pdf->Cell(0, 6, '+230 ' . fmt_juice($order['payment_phone']), 0, 1);
}
$pdf->Ln(10);

$pdf->SetDrawColor(220, 220, 220);
$pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
$pdf->Ln(6);

$pdf->SetFont('Arial', 'B', 10);
$pdf->SetTextColor(44, 62, 80);
$pdf->Cell(0, 6, 'Thank you for shopping with Gadgets Universe!', 0, 1, 'C');

$pdf->SetFont('Arial', 'I', 8);
$pdf->SetTextColor(127, 140, 141);
$pdf->Cell(0, 5, 'This is a computer-generated receipt. No signature required.', 0, 1, 'C');
$pdf->Cell(0, 5, 'For inquiries: contact@gadgetsuniverse.mu', 0, 1, 'C');

if (ob_get_length()) ob_end_clean();
$pdf->Output('D', 'Receipt-' . $order['order_code'] . '.pdf');
exit;