<?php
require_once __DIR__ . '/config.php';

$raw = file_get_contents('php://input');
file_put_contents(__DIR__ . '/anypay_callback.log',
    date('Y-m-d H:i:s') . ' ' . $raw . PHP_EOL, FILE_APPEND);

$payload = json_decode($raw, true);
if (!$payload) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid payload']);
    exit;
}

$order_ref = $payload['order_id'] ?? $payload['order_reference'] ?? $payload['reference'] ?? '';
$status    = strtolower($payload['status'] ?? $payload['payment_status'] ?? '');

if (!$order_ref) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing order_id']);
    exit;
}

$ref_esc = mysqli_real_escape_string($conn, $order_ref);
$raw_esc = mysqli_real_escape_string($conn, $raw);

$order = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM orders WHERE order_ref='$ref_esc' LIMIT 1"));

if (!$order) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Order not found']);
    exit;
}
if ($order['payment_status'] === 'paid') {
    echo json_encode(['status' => 'ok', 'message' => 'Already processed']);
    exit;
}

$success = in_array($status, ['success', 'completed', 'paid', 'successful', 'successfull']);
$failed  = in_array($status, ['failed', 'cancelled', 'canceled', 'rejected', 'error']);

if ($success) {
    mysqli_query($conn, "
        UPDATE orders
        SET status='approved', payment_status='paid', paid_at=NOW(), gateway_response='$raw_esc'
        WHERE id={$order['id']}
    ");

    if ($order['user_id']) {
        $msg = mysqli_real_escape_string($conn,
            "Order $order_ref — Payment received. Total: " . number_format($order['total_amount']) . " TSh");
        mysqli_query($conn, "
            INSERT INTO notifications
                (user_id, email, phone, type, ref_id, ref_code, old_status, new_status,
                 message, channel, created_at)
            VALUES
                ({$order['user_id']}, '{$order['email']}', '{$order['phone']}',
                 'order', {$order['id']}, '$order_ref',
                 'pending', 'approved', '$msg', 'both', NOW())
        ");
    }
    echo json_encode(['status' => 'ok']);

} elseif ($failed) {
    $items = mysqli_query($conn, "SELECT product_id, qty FROM order_items WHERE order_id={$order['id']}");
    while ($item = mysqli_fetch_assoc($items)) {
        mysqli_query($conn, "UPDATE products SET stock_qty = stock_qty + {$item['qty']} WHERE id={$item['product_id']}");
    }
    mysqli_query($conn, "
        UPDATE orders SET status='rejected', payment_status='failed', gateway_response='$raw_esc'
        WHERE id={$order['id']}
    ");
    echo json_encode(['status' => 'ok']);

} else {
    mysqli_query($conn, "UPDATE orders SET gateway_response='$raw_esc' WHERE id={$order['id']}");
    echo json_encode(['status' => 'ok', 'message' => 'Unhandled status: ' . $status]);
}