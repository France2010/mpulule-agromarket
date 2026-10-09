<?php
session_start();
$root = '../';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();
$page_title = 'Dashboard';
require_once __DIR__ . '/includes/admin_header.php';

// Stats
$stats = [
    'orders'   => mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM orders"))['c'],
    'pending'  => mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM orders WHERE status='pending'"))['c'],
    'approved' => mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM orders WHERE status='approved'"))['c'],
    'revenue'  => mysqli_fetch_assoc(mysqli_query($conn,"SELECT COALESCE(SUM(total_amount),0) c FROM orders WHERE status NOT IN ('rejected')"))['c'],
    'users'    => mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM users WHERE user_role='user'"))['c'],
    'products' => mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM products WHERE active=1"))['c'],
    'loans'    => mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM loan_applications"))['c'],
    'messages' => mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM contact_messages WHERE is_read=0"))['c'],
];

// Recent orders
$recent_orders = mysqli_query($conn,"SELECT o.*,GROUP_CONCAT(p.name SEPARATOR ', ') prods FROM orders o LEFT JOIN order_items oi ON oi.order_id=o.id LEFT JOIN products p ON p.id=oi.product_id GROUP BY o.id ORDER BY o.created_at DESC LIMIT 8");

// Low stock products
$low_stock = mysqli_query($conn,"SELECT p.*,c.name cat_name FROM products p JOIN categories c ON c.id=p.category_id WHERE p.stock_qty<=20 AND p.active=1 ORDER BY p.stock_qty ASC LIMIT 6");
?>

<!-- STAT CARDS -->
<div class="grid-4" style="margin-bottom:28px">
    <div class="stat-card">
        <div class="icon"><i class="fas fa-box"></i></div>
        <div class="num"><?php echo number_format($stats['orders']); ?></div>
        <div class="lbl">Total orders</div>
    </div>
    <div class="stat-card" style="border-color:var(--gold)">
        <div class="icon"><i class="fas fa-hourglass-half"></i></div>
        <div class="num" style="color:var(--warn)"><?php echo number_format($stats['pending']); ?></div>
        <div class="lbl">Pending approval</div>
    </div>
    <div class="stat-card">
        <div class="icon"><i class="fas fa-check-circle"></i></div>
        <div class="num" style="color:var(--success)"><?php echo number_format($stats['approved']); ?></div>
        <div class="lbl">Approved orders</div>
    </div>
    <div class="stat-card">
        <div class="icon"><i class="fas fa-coins"></i></div>
        <div class="num" style="font-size:1.3rem"><?php echo fmt((float)$stats['revenue']); ?></div>
        <div class="lbl">Total revenue</div>
    </div>
</div>

<div class="grid-4" style="margin-bottom:36px">
    <div class="stat-card"><div class="icon"><i class="fas fa-users"></i></div><div class="num"><?php echo $stats['users']; ?></div><div class="lbl">Registered users</div></div>
    <div class="stat-card"><div class="icon"><i class="fas fa-seedling"></i></div><div class="num"><?php echo $stats['products']; ?></div><div class="lbl">Active products</div></div>
    <div class="stat-card"><div class="icon"><i class="fas fa-hand-holding-dollar"></i></div><div class="num"><?php echo $stats['loans']; ?></div><div class="lbl">Loan applications</div></div>
    <div class="stat-card"><div class="icon"><i class="fas fa-envelope"></i></div><div class="num" style="color:var(--warn)"><?php echo $stats['messages']; ?></div><div class="lbl">Unread messages</div></div>
</div>

<div style="display:grid;grid-template-columns:1fr 340px;gap:24px">
    <!-- RECENT ORDERS -->
    <div>
        <div class="flex-between" style="margin-bottom:14px">
            <h3>Recent orders</h3>
            <a href="orders.php" class="btn btn-outline btn-sm">View all</a>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Ref</th><th>Customer</th><th>Products</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                <?php while($o=mysqli_fetch_assoc($recent_orders)): ?>
                <tr>
                    <td><strong><?php echo e($o['order_ref']); ?></strong></td>
                    <td><?php echo e($o['customer_name']); ?><br><span style="font-size:11px;color:var(--text-muted)"><?php echo e($o['region']); ?></span></td>
                    <td style="font-size:12px;max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?php echo e($o['prods']??'—'); ?></td>
                    <td><?php echo fmt((float)$o['total_amount']); ?></td>
                    <td><span class="badge <?php
                        $sc=['pending'=>'badge-warn','approved'=>'badge-success','rejected'=>'badge-danger','processing'=>'badge-info','shipped'=>'badge-info','delivered'=>'badge-success'];
                        echo $sc[$o['status']]??'badge-gold';
                    ?>"><?php echo ucfirst($o['status']); ?></span></td>
                    <td style="font-size:12px;color:var(--text-muted)"><?php echo date('d M Y',strtotime($o['created_at'])); ?></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- LOW STOCK -->
    <div>
        <div class="flex-between" style="margin-bottom:14px">
            <h3>Low stock alert</h3>
            <a href="products.php" class="btn btn-outline btn-sm">Manage</a>
        </div>
        <div class="card" style="overflow:hidden">
            <?php $c=0; while($p=mysqli_fetch_assoc($low_stock)): $c++; ?>
            <div style="padding:12px 16px;border-bottom:0.5px solid var(--border);display:flex;justify-content:space-between;align-items:center">
                <div>
                    <div style="font-size:13px;font-weight:600"><?php echo e($p['name']); ?></div>
                    <div style="font-size:11px;color:var(--text-muted)"><?php echo e($p['cat_name']); ?></div>
                </div>
                <span class="badge <?php echo $p['stock_qty']==0?'badge-danger':'badge-warn'; ?>">
                    <?php echo $p['stock_qty']; ?> left
                </span>
            </div>
            <?php endwhile; if(!$c): ?>
            <div style="padding:24px;text-align:center;color:var(--text-muted);font-size:13px"><i class="fas fa-check-circle" style="color:var(--success)"></i> All products well stocked</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
