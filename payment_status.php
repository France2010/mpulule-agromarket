<?php
session_start();
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

$ref = mysqli_real_escape_string($conn, $_GET['ref'] ?? '');
if (!$ref) { echo json_encode(['status' => 'unknown']); exit; }

$order = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT id, status, payment_status, admin_note FROM orders WHERE order_ref='$ref'"));

if (!$order) { echo json_encode(['status' => 'not_found']); exit; }

if ($order['payment_status'] === 'paid' || $order['status'] === 'approved') {
    echo json_encode(['status' => 'paid']);
} elseif ($order['payment_status'] === 'failed' || $order['status'] === 'rejected') {
    echo json_encode(['status' => 'failed', 'message' => $order['admin_note'] ?: 'Payment was not completed']);
} else {
    echo json_encode(['status' => 'pending']);
}