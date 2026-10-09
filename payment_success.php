<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/payment_gateways.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$action     = $input['action'] ?? '';
$product_id = (int)($input['product_id'] ?? 0);
$quantity   = max(1, (int)($input['quantity'] ?? 1));
$method     = mysqli_real_escape_string($conn, $input['method'] ?? '');
$phone      = mysqli_real_escape_string($conn, $input['phone'] ?? '');
$region     = mysqli_real_escape_string($conn, $input['region'] ?? '');
$notes      = mysqli_real_escape_string($conn, $input['notes'] ?? '');

if ($action !== 'mobile_payment' || !$product_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment request']);
    exit;
}

$prod = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT id, name, price, stock_qty FROM products WHERE id=$product_id AND active=1"
));

if (!$prod) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit;
}
if ((int)$prod['stock_qty'] < $quantity) {
    echo json_encode(['success' => false, 'message' => 'Insufficient stock. Only ' . $prod['stock_qty'] . ' left.']);
    exit;
}

$total = (float)$prod['price'] * $quantity;

$user_id    = $_SESSION['user_id'] ?? null;
$user_name  = 'Guest';
$user_email = '';
$user_phone = $phone;

if ($user_id) {
    $u = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT firstname, lastname, email, phone FROM users WHERE id=" . (int)$user_id
    ));
    if ($u) {
        $user_name  = mysqli_real_escape_string($conn, $u['firstname'] . ' ' . $u['lastname']);
        $user_email = mysqli_real_escape_string($conn, $u['email']);
        if (!$user_phone) $user_phone = mysqli_real_escape_string($conn, $u['phone'] ?? '');
    }
}

$order_ref = 'ORD-' . strtoupper(bin2hex(random_bytes(4)));

$user_sql = $user_id ? (int)$user_id : 'NULL';
$insert = mysqli_query($conn, "
    INSERT INTO orders
        (order_ref, user_id, customer_name, email, phone, region, delivery_notes,
         total_amount, status, payment_method, payment_status, created_at)
    VALUES
        ('$order_ref', $user_sql, '$user_name', '$user_email', '$user_phone', '$region', '$notes',
         $total, 'pending', '$method', 'pending', NOW())
");

if (!$insert) {
    echo json_encode(['success' => false, 'message' => 'DB error: ' . mysqli_error($conn)]);
    exit;
}
$order_id = mysqli_insert_id($conn);

$prod_name_esc = mysqli_real_escape_string($conn, $prod['name']);
mysqli_query($conn, "
    INSERT INTO order_items (order_id, product_id, product_name, qty, unit_price)
    VALUES ($order_id, $product_id, '$prod_name_esc', $quantity, {$prod['price']})
");

mysqli_query($conn, "UPDATE products SET stock_qty = stock_qty - $quantity WHERE id = $product_id");

try {
    $gateway = new PaymentGateway(PAYMENT_GATEWAY);
    $result  = $gateway->initiatePayment([
        'order_ref'    => $order_ref,
        'amount'       => $total,
        'currency'     => 'TZS',
        'method'       => $method,
        'phone'        => $user_phone,
        'customer'     => [
            'name'  => $user_name,
            'email' => $user_email,
            'phone' => $user_phone,
        ],
        'description'  => 'Payment for ' . $prod['name'] . ' x' . $quantity,
        'callback_url' => PAYMENT_CALLBACK_URL,
        'redirect_url' => PAYMENT_REDIRECT_URL . '?ref=' . $order_ref,
    ]);

    $gateway_ref = mysqli_real_escape_string($conn, $result['gateway_ref'] ?? '');
    mysqli_query($conn, "UPDATE orders SET payment_ref='$gateway_ref' WHERE id=$order_id");

    echo json_encode([
        'success'          => true,
        'order_id'         => $order_id,
        'order_ref'        => $order_ref,
        'gateway_ref'      => $gateway_ref,
        'awaiting_payment' => $result['awaiting_payment'] ?? true,
        'message'          => $result['message'] ?? 'Payment request sent'
    ]);

} catch (Exception $e) {
    mysqli_query($conn, "UPDATE products SET stock_qty = stock_qty + $quantity WHERE id = $product_id");
    mysqli_query($conn, "
        UPDATE orders
        SET status='rejected',
            payment_status='failed',
            admin_note='" . mysqli_real_escape_string($conn, $e->getMessage()) . "'
        WHERE id=$order_id
    ");

    echo json_encode(['success' => false, 'message' => 'Payment error: ' . $e->getMessage()]);
}