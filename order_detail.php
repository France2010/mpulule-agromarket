<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();
$page_title = 'Order Details';

$uid = (int)$_SESSION['user_id'];
$ref = mysqli_real_escape_string($conn, $_GET['ref'] ?? '');

$order = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM orders WHERE order_ref='$ref' AND user_id=$uid LIMIT 1"));

if (!$order) {
    header('Location: orders.php'); exit;
}

$items = [];
$r = mysqli_query($conn,
    "SELECT oi.*, p.name AS product_name_db, p.image, p.unit, c.name AS cat_name
     FROM order_items oi
     LEFT JOIN products p ON p.id = oi.product_id
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE oi.order_id = {$order['id']}");
while ($row = mysqli_fetch_assoc($r)) $items[] = $row;

require_once __DIR__ . '/includes/header.php';
?>

<style>
.detail-wrap { background: #0a0f1c; min-height: calc(100vh - 62px); padding: 30px 0 60px; }
.detail-wrap h1 { color: #fbbf24; font-size: 22px; font-weight: 800; margin: 0 0 6px; }
.detail-wrap .sub { color: #94a3b8; font-size: 13px; margin-bottom: 22px; }

.back-link {
    display: inline-flex; align-items: center; gap: 6px;
    color: #fbbf24; text-decoration: none; font-size: 13px; font-weight: 600;
    margin-bottom: 16px;
}
.back-link:hover { color: #f59e0b; }

.detail-card {
    background: linear-gradient(135deg, #111827 0%, #0f172a 100%);
    border: 1px solid rgba(245,158,11,0.15);
    border-radius: 16px;
    padding: 22px 24px;
    margin-bottom: 16px;
}
.detail-card h3 {
    color: #fbbf24; font-size: 14px; font-weight: 700;
    margin: 0 0 16px; display: flex; align-items: center; gap: 8px;
}

.detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px 20px; }
@media (max-width: 500px) { .detail-grid { grid-template-columns: 1fr; } }
.detail-field .lbl {
    color: #64748b; font-size: 10px; text-transform: uppercase;
    font-weight: 600; letter-spacing: 0.5px; display: block; margin-bottom: 4px;
}
.detail-field .val { color: #e5e7eb; font-size: 14px; font-weight: 600; word-break: break-word; }
.detail-field .val.gold { color: #fbbf24; font-size: 16px; font-weight: 800; }

/* Items */
.item-row {
    display: flex; align-items: center; gap: 14px;
    padding: 12px 0;
    border-bottom: 1px solid rgba(245,158,11,0.08);
}
.item-row:last-child { border-bottom: none; padding-bottom: 0; }
.item-row:first-child { padding-top: 0; }

.item-img {
    width: 56px; height: 56px; border-radius: 10px;
    background: #1e293b; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    overflow: hidden;
}
.item-img img { width: 100%; height: 100%; object-fit: cover; }
.item-img i { color: #f59e0b; font-size: 22px; }

.item-info { flex: 1; min-width: 0; }
.item-info .name { color: #e5e7eb; font-size: 14px; font-weight: 600; margin-bottom: 4px; }
.item-info .cat { color: #64748b; font-size: 11px; }

.item-price { text-align: right; flex-shrink: 0; }
.item-price .total { color: #fbbf24; font-size: 15px; font-weight: 800; }
.item-price .unit { color: #64748b; font-size: 11px; }

.total-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 16px 0 0; margin-top: 8px;
    border-top: 1px solid rgba(245,158,11,0.15);
}
.total-row .lbl { color: #94a3b8; font-size: 13px; font-weight: 600; text-transform: uppercase; }
.total-row .val { color: #fbbf24; font-size: 22px; font-weight: 800; }
</style>

<div class="detail-wrap">
    <div class="container" style="max-width:820px">
        <a href="orders.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to orders</a>

        <h1>Order <?php echo e($order['order_ref']); ?></h1>
        <p class="sub">
            Placed on <?php echo date('d M Y · H:i', strtotime($order['created_at'])); ?>
        </p>

        <!-- Items -->
        <div class="detail-card">
            <h3><i class="fas fa-box"></i> Order Items</h3>
            <?php foreach ($items as $it):
                $pname = $it['product_name_db'] ?: ($it['product_name'] ?? 'Product');
                $img_path = '';
                if (!empty($it['image'])) {
                    $fp = __DIR__ . '/uploads/products/' . $it['image'];
                    if (file_exists($fp)) $img_path = '/agro_project/uploads/products/' . $it['image'];
                }
            ?>
                <div class="item-row">
                    <div class="item-img">
                        <?php if ($img_path): ?>
                            <img src="<?php echo $img_path; ?>" alt="">
                        <?php else: ?>
                            <i class="fas fa-cube"></i>
                        <?php endif; ?>
                    </div>
                    <div class="item-info">
                        <div class="name"><?php echo e($pname); ?></div>
                        <div class="cat"><?php echo e($it['cat_name'] ?? ''); ?> · Qty: <?php echo (int)$it['qty']; ?></div>
                    </div>
                    <div class="item-price">
                        <div class="total"><?php echo fmt((float)$it['unit_price'] * (int)$it['qty']); ?></div>
                        <div class="unit"><?php echo fmt((float)$it['unit_price']); ?> each</div>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="total-row">
                <span class="lbl">Total</span>
                <span class="val"><?php echo fmt((float)$order['total_amount']); ?></span>
            </div>
        </div>

        <!-- Delivery -->
        <div class="detail-card">
            <h3><i class="fas fa-map-marker-alt"></i> Delivery Details</h3>
            <div class="detail-grid">
                <div class="detail-field">
                    <span class="lbl">Name</span>
                    <span class="val"><?php echo e($order['customer_name']); ?></span>
                </div>
                <div class="detail-field">
                    <span class="lbl">Phone</span>
                    <span class="val"><?php echo e($order['phone']); ?></span>
                </div>
                <div class="detail-field">
                    <span class="lbl">Email</span>
                    <span class="val"><?php echo e($order['email']); ?></span>
                </div>
                <div class="detail-field">
                    <span class="lbl">Region</span>
                    <span class="val"><?php echo e($order['region']); ?></span>
                </div>
                <?php if (!empty($order['delivery_notes'])): ?>
                <div class="detail-field" style="grid-column: 1 / -1;">
                    <span class="lbl">Notes</span>
                    <span class="val"><?php echo e($order['delivery_notes']); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Payment -->
        <div class="detail-card">
            <h3><i class="fas fa-credit-card"></i> Payment</h3>
            <div class="detail-grid">
                <div class="detail-field">
                    <span class="lbl">Method</span>
                    <span class="val"><?php echo strtoupper(e($order['payment_method'] ?? '—')); ?></span>
                </div>
                <div class="detail-field">
                    <span class="lbl">Payment Status</span>
                    <span class="val"><?php echo ucfirst(e($order['payment_status'] ?? 'unpaid')); ?></span>
                </div>
                <div class="detail-field">
                    <span class="lbl">Order Status</span>
                    <span class="val"><?php echo ucfirst(e($order['status'])); ?></span>
                </div>
                <?php if (!empty($order['payment_ref'])): ?>
                <div class="detail-field">
                    <span class="lbl">Reference</span>
                    <span class="val" style="font-family:monospace;"><?php echo e($order['payment_ref']); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Contact -->
        <div class="detail-card" style="text-align:center;">
            <h3 style="justify-content:center;"><i class="fas fa-headset"></i> Need help?</h3>
            <p style="color:#94a3b8;font-size:13px;margin:0 0 12px;">Questions about this order? Contact us.</p>
            <a href="https://wa.me/<?php echo e(get_setting('whatsapp_number', '+255746905955')); ?>?text=<?php echo urlencode('Hi, I have a question about order ' . $order['order_ref']); ?>"
               target="_blank"
               class="btn btn-gold" style="padding:10px 20px;border-radius:10px;text-decoration:none;display:inline-flex;align-items:center;gap:8px;">
                <i class="fab fa-whatsapp"></i> WhatsApp us
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>