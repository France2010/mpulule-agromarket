<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();
$page_title = 'My Orders';

$uid = (int)$_SESSION['user_id'];

// Filter
$filter = $_GET['status'] ?? 'all';
$where = "user_id=$uid";
if ($filter === 'pending')    $where .= " AND status='pending'";
if ($filter === 'processing') $where .= " AND status IN ('approved','processing')";
if ($filter === 'shipped')    $where .= " AND status='shipped'";
if ($filter === 'delivered')  $where .= " AND status='delivered'";
if ($filter === 'rejected')   $where .= " AND status='rejected'";

$orders = [];
$r = mysqli_query($conn,
    "SELECT id, order_ref, total_amount, status, payment_status, payment_method, created_at
     FROM orders WHERE $where ORDER BY created_at DESC");
while ($row = mysqli_fetch_assoc($r)) $orders[] = $row;

// Counters
$counts = [
    'all'        => (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM orders WHERE user_id=$uid"))['c'],
    'pending'    => (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM orders WHERE user_id=$uid AND status='pending'"))['c'],
    'processing' => (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM orders WHERE user_id=$uid AND status IN ('approved','processing')"))['c'],
    'shipped'    => (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM orders WHERE user_id=$uid AND status='shipped'"))['c'],
    'delivered'  => (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM orders WHERE user_id=$uid AND status='delivered'"))['c'],
];

require_once __DIR__ . '/includes/header.php';
?>

<style>
.orders-wrap { background: #0a0f1c; min-height: calc(100vh - 62px); padding: 30px 0 60px; }

.orders-head {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 22px; flex-wrap: wrap; gap: 12px;
}
.orders-head h1 {
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    -webkit-background-clip: text; background-clip: text; color: transparent;
    font-size: 26px; font-weight: 800; margin: 0;
}
.orders-head p { color: #94a3b8; font-size: 13px; margin: 4px 0 0; }

/* Tabs */
.order-tabs {
    display: flex; gap: 8px; margin-bottom: 22px; flex-wrap: wrap;
    border-bottom: 1px solid rgba(245,158,11,0.1); padding-bottom: 10px;
}
.order-tab {
    padding: 8px 16px; border-radius: 20px;
    color: #94a3b8; font-size: 12px; font-weight: 600;
    text-decoration: none; transition: all 0.2s;
    display: inline-flex; align-items: center; gap: 6px;
}
.order-tab .count {
    background: rgba(100,116,139,0.2);
    padding: 1px 8px; border-radius: 20px; font-size: 10px;
}
.order-tab:hover { background: rgba(245,158,11,0.08); color: #fbbf24; }
.order-tab.active {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: #fff;
}
.order-tab.active .count { background: rgba(255,255,255,0.25); color: #fff; }

/* Order cards */
.order-list { display: flex; flex-direction: column; gap: 14px; }

.order-card {
    background: linear-gradient(135deg, #111827 0%, #0f172a 100%);
    border: 1px solid rgba(245,158,11,0.15);
    border-radius: 16px; padding: 18px 20px;
    transition: all 0.25s ease;
    text-decoration: none; display: block;
}
.order-card:hover { border-color: rgba(245,158,11,0.4); transform: translateY(-2px); }

.order-card-top {
    display: flex; align-items: flex-start; justify-content: space-between;
    gap: 12px; margin-bottom: 14px; flex-wrap: wrap;
}
.order-ref {
    color: #fbbf24; font-weight: 800; font-size: 14px;
    font-family: monospace; letter-spacing: 0.5px;
    margin-bottom: 4px;
}
.order-date { color: #64748b; font-size: 11px; }

.order-card-body {
    display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px;
    padding-top: 14px;
    border-top: 1px solid rgba(245,158,11,0.08);
}
@media (max-width: 640px) {
    .order-card-body { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 400px) {
    .order-card-body { grid-template-columns: 1fr; }
}
.order-field .lbl {
    color: #64748b; font-size: 10px; text-transform: uppercase;
    letter-spacing: 0.5px; font-weight: 600; display: block; margin-bottom: 4px;
}
.order-field .val {
    color: #e5e7eb; font-size: 14px; font-weight: 600;
}
.order-field .val.gold { color: #fbbf24; font-size: 16px; font-weight: 800; }

.empty-orders {
    text-align: center; padding: 70px 20px;
    background: linear-gradient(135deg, #111827 0%, #0f172a 100%);
    border: 1px solid rgba(245,158,11,0.15); border-radius: 16px;
}
.empty-orders i { font-size: 56px; color: rgba(245,158,11,0.2); display: block; margin-bottom: 16px; }
.empty-orders h3 { color: #e5e7eb; font-size: 18px; margin: 0 0 8px; }
.empty-orders p { color: #94a3b8; font-size: 13px; margin-bottom: 20px; }
</style>

<div class="orders-wrap">
    <div class="container">
        <div class="orders-head">
            <div>
                <h1><i class="fas fa-shopping-bag"></i> My Orders</h1>
                <p>Track and manage your purchases</p>
            </div>
            <a href="products.php" class="btn btn-gold" style="padding:10px 20px;border-radius:10px;text-decoration:none;">
                <i class="fas fa-plus"></i> New Order
            </a>
        </div>

        <!-- Tabs -->
        <div class="order-tabs">
            <a href="orders.php" class="order-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">
                <i class="fas fa-list"></i> All <span class="count"><?php echo $counts['all']; ?></span>
            </a>
            <a href="orders.php?status=pending" class="order-tab <?php echo $filter === 'pending' ? 'active' : ''; ?>">
                <i class="fas fa-clock"></i> Pending <span class="count"><?php echo $counts['pending']; ?></span>
            </a>
            <a href="orders.php?status=processing" class="order-tab <?php echo $filter === 'processing' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i> Processing <span class="count"><?php echo $counts['processing']; ?></span>
            </a>
            <a href="orders.php?status=shipped" class="order-tab <?php echo $filter === 'shipped' ? 'active' : ''; ?>">
                <i class="fas fa-truck"></i> Shipped <span class="count"><?php echo $counts['shipped']; ?></span>
            </a>
            <a href="orders.php?status=delivered" class="order-tab <?php echo $filter === 'delivered' ? 'active' : ''; ?>">
                <i class="fas fa-check-circle"></i> Delivered <span class="count"><?php echo $counts['delivered']; ?></span>
            </a>
        </div>

        <!-- List -->
        <?php if (empty($orders)): ?>
            <div class="empty-orders">
                <i class="fas fa-shopping-bag"></i>
                <h3>No orders yet</h3>
                <p>Browse our marketplace and place your first order</p>
                <a href="products.php" class="btn btn-gold" style="padding:12px 28px;border-radius:10px;text-decoration:none;">
                    <i class="fas fa-store"></i> Browse Products
                </a>
            </div>
        <?php else: ?>
            <div class="order-list">
                <?php foreach ($orders as $o):
                    $stMap = [
                        'pending'    => ['amber', 'Pending'],
                        'approved'   => ['blue',  'Approved'],
                        'processing' => ['blue',  'Processing'],
                        'shipped'    => ['blue',  'Shipped'],
                        'delivered'  => ['green', 'Delivered'],
                        'rejected'   => ['red',   'Rejected'],
                    ];
                    [$stColor, $stLabel] = $stMap[$o['status']] ?? ['gray', ucfirst($o['status'])];
                    if ($o['payment_status'] === 'paid') { $stColor = 'green'; $stLabel = 'Paid'; }
                ?>
                <a href="order_detail.php?ref=<?php echo urlencode($o['order_ref']); ?>" class="order-card">
                    <div class="order-card-top">
                        <div>
                            <div class="order-ref"><?php echo e($o['order_ref']); ?></div>
                            <div class="order-date">
                                <i class="fas fa-calendar"></i>
                                <?php echo date('d M Y · H:i', strtotime($o['created_at'])); ?>
                            </div>
                        </div>
                        <span class="ds-badge <?php echo $stColor; ?>"><?php echo $stLabel; ?></span>
                    </div>
                    <div class="order-card-body">
                        <div class="order-field">
                            <span class="lbl">Total Amount</span>
                            <span class="val gold"><?php echo fmt((float)$o['total_amount']); ?></span>
                        </div>
                        <div class="order-field">
                            <span class="lbl">Payment</span>
                            <span class="val"><?php echo strtoupper(e($o['payment_method'] ?? '—')); ?></span>
                        </div>
                        <div class="order-field">
                            <span class="lbl">Status</span>
                            <span class="val"><?php echo $stLabel; ?></span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>