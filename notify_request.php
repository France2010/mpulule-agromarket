<?php
session_start();
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

$product_id = (int)($_POST['product_id'] ?? 0);
if (!$product_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid product']);
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;
$email = $phone = null;

if ($user_id) {
    $u = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT email, phone FROM users WHERE id=" . (int)$user_id));
    if ($u) { $email = $u['email']; $phone = $u['phone']; }
}

$user_sql  = $user_id ? (int)$user_id : 'NULL';
$email_esc = $email ? "'" . mysqli_real_escape_string($conn, $email) . "'" : 'NULL';
$phone_esc = $phone ? "'" . mysqli_real_escape_string($conn, $phone) . "'" : 'NULL';

$existing = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT id FROM stock_notifications WHERE product_id=$product_id AND user_id=$user_sql"));

if ($existing) {
    echo json_encode(['success' => true, 'message' => 'You are already on the waiting list']);
    exit;
}

mysqli_query($conn, "
    INSERT INTO stock_notifications (product_id, user_id, email, phone, created_at)
    VALUES ($product_id, $user_sql, $email_esc, $phone_esc, NOW())
");

echo json_encode(['success' => true, 'message' => 'You will be notified when this product is back in stock']);