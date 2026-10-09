<?php
session_start();
require_once __DIR__ . '/config.php';
$page_title = 'Order Confirmed';

$ref = mysqli_real_escape_string($conn, $_GET['ref'] ?? '');
$order = $ref ? mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM orders WHERE order_ref='$ref'")) : null;

require_once __DIR__ . '/includes/header.php';
?>
<div class="page-header" style="background:linear-gradient(135deg,#0f172a 0%,#0a0f1c 100%);padding:60px 0;text-align:center">
    <div class="container">
        <i class="fas fa-check-circle" style="font-size:4rem;color:#10b981;margin-bottom:16px"></i>
        <h1 style="color:#fbbf24;font-size:2rem">Payment Successful!</h1>
        <p style="color:#9ca3af;margin-top:8px">Thank you for your order</p>
    </div>
</div>

<section class="section">
    <div class="container" style="max-width:600px">
        <?php if($order): ?>
        <div style="background:linear-gradient(135deg,#111827 0%,#0f172a 100%);border:1px solid rgba(245,158,11,0.2);border-radius:16px;padding:24px">
            <div style="text-align:center;padding-bottom:16px;border-bottom:1px solid rgba(245,158,11,0.1);margin-bottom:16px">
                <p style="color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:1px">Order Reference</p>
                <p style="color:#fbbf24;font-size:22px;font-weight:800;margin-top:4px"><?php echo e($order['order_ref']); ?></p>
            </div>
            <table style="width:100%;font-size:14px">
                <tr><td style="padding:8px 0;color:#94a3b8">Customer</td><td style="padding:8px 0;color:#e5e7eb;text-align:right"><?php echo e($order['customer_name']); ?></td></tr>
                <tr><td style="padding:8px 0;color:#94a3b8">Phone</td><td style="padding:8px 0;color:#e5e7eb;text-align:right"><?php echo e($order['phone']); ?></td></tr>
                <tr><td style="padding:8px 0;color:#94a3b8">Region</td><td style="padding:8px 0;color:#e5e7eb;text-align:right"><?php echo e($order['region']); ?></td></tr>
                <tr><td style="padding:8px 0;color:#94a3b8">Payment</td><td style="padding:8px 0;color:#10b981;text-align:right;font-weight:700"><?php echo strtoupper(e($order['payment_method'] ?? 'PAID')); ?></td></tr>
                <tr style="border-top:1px solid rgba(245,158,11,0.15)"><td style="padding:12px 0;color:#94a3b8;font-weight:600">Total Paid</td><td style="padding:12px 0;color:#fbbf24;text-align:right;font-size:18px;font-weight:800"><?php echo fmt((float)$order['total_amount']); ?></td></tr>
            </table>
        </div>
        <div style="text-align:center;margin-top:24px">
            <a href="/agro_project/products.php" class="btn btn-gold">Continue Shopping</a>
        </div>
        <?php else: ?>
        <p style="text-align:center;color:#9ca3af">Order not found.</p>
        <?php endif; ?>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>