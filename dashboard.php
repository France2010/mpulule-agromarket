<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();
$page_title = 'My Dashboard';

$uid = (int)$_SESSION['user_id'];

// ============ LOAD USER ============
$user = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT id, firstname, lastname, email, phone, region, user_role, created_at
     FROM users WHERE id=$uid LIMIT 1"));

if (!$user) { logout_user(); header('Location: /agro_project/login.php'); exit; }

$fullname = trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''));
$initial  = strtoupper(substr($user['firstname'] ?: 'U', 0, 1));

// ============ STATS ============
// Total orders
$total_orders = (int)mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) c FROM orders WHERE user_id=$uid"))['c'];

// Total spent (only paid orders)
$total_spent = (float)(mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(total_amount), 0) t FROM orders
     WHERE user_id=$uid AND payment_status='paid'"))['t'] ?? 0);

// Active orders (not delivered / rejected)
$active_orders = (int)mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) c FROM orders
     WHERE user_id=$uid AND status IN ('pending','approved','processing','shipped')"))['c'];

// Total loans
$total_loans = (int)mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) c FROM loan_applications WHERE user_id=$uid"))['c'];

// Total loan amount approved
$loans_approved_amount = (float)(mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(requested_amount), 0) t FROM loan_applications
     WHERE user_id=$uid AND status IN ('approved','disbursed')"))['t'] ?? 0);

// Pending loans
$pending_loans = (int)mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) c FROM loan_applications
     WHERE user_id=$uid AND status='pending'"))['c'];

// Unread notifications
$unread_notifs = (int)mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) c FROM notifications
     WHERE user_id=$uid AND is_read=0"))['c'];

// ============ RECENT ORDERS (5) ============
$recent_orders = [];
$r = mysqli_query($conn,
    "SELECT id, order_ref, total_amount, status, payment_status, created_at
     FROM orders WHERE user_id=$uid
     ORDER BY created_at DESC LIMIT 5");
while ($row = mysqli_fetch_assoc($r)) $recent_orders[] = $row;

// ============ RECENT LOANS (5) ============
$recent_loans = [];
$r = mysqli_query($conn,
    "SELECT id, loan_ref, requested_amount, status, disbursement_status,
            monthly_payment, created_at
     FROM loan_applications WHERE user_id=$uid
     ORDER BY created_at DESC LIMIT 5");
while ($row = mysqli_fetch_assoc($r)) $recent_loans[] = $row;

// ============ RECENT NOTIFICATIONS (5) ============
$recent_notifs = [];
$r = mysqli_query($conn,
    "SELECT id, type, ref_code, message, new_status, is_read, created_at
     FROM notifications WHERE user_id=$uid
     ORDER BY created_at DESC LIMIT 5");
while ($row = mysqli_fetch_assoc($r)) $recent_notifs[] = $row;

require_once __DIR__ . '/includes/header.php';
?>

<style>
/* ============================================================
   USER DASHBOARD
   ============================================================ */
.dash-wrap {
    background: #0a0f1c;
    min-height: calc(100vh - 62px);
    padding: 24px 0 60px;
}

.dash-header {
    background: linear-gradient(135deg, #111827 0%, #0f172a 100%);
    border: 1px solid rgba(245, 158, 11, 0.15);
    border-radius: 20px;
    padding: 22px 24px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 18px;
    flex-wrap: wrap;
}
.dash-avatar {
    width: 68px; height: 68px; border-radius: 50%;
    background: linear-gradient(135deg, #f59e0b, #d97706);
    display: flex; align-items: center; justify-content: center;
    color: #0f172a; font-weight: 900; font-size: 28px;
    flex-shrink: 0;
    box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.15), 0 0 0 8px rgba(245, 158, 11, 0.05);
}
.dash-header-info { flex: 1; min-width: 200px; }
.dash-header-info h1 {
    color: #fbbf24; font-size: 22px; font-weight: 800; margin: 0 0 4px;
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    -webkit-background-clip: text; background-clip: text; color: transparent;
}
.dash-header-info p { color: #94a3b8; font-size: 13px; margin: 0; }
.dash-header-info small { color: #64748b; font-size: 11px; display: block; margin-top: 2px; }

.dash-header-actions { display: flex; gap: 8px; flex-wrap: wrap; }
.dash-btn {
    padding: 10px 16px; border-radius: 10px; font-size: 13px; font-weight: 700;
    text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
    transition: all 0.2s; border: none; cursor: pointer;
}
.dash-btn-gold {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: #fff;
}
.dash-btn-gold:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(245,158,11,0.3); }
.dash-btn-outline {
    background: transparent; color: #fbbf24;
    border: 1px solid rgba(245,158,11,0.3);
}
.dash-btn-outline:hover { background: rgba(245,158,11,0.1); }

/* ============ STATS ============ */
.dash-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin-bottom: 24px;
}
@media (max-width: 900px) { .dash-stats { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 480px) { .dash-stats { grid-template-columns: repeat(2, 1fr); gap: 10px; } }

.dash-stat {
    background: linear-gradient(135deg, #111827 0%, #0f172a 100%);
    border: 1px solid rgba(245, 158, 11, 0.15);
    border-radius: 16px;
    padding: 18px;
    transition: all 0.25s ease;
    position: relative;
    overflow: hidden;
}
.dash-stat:hover {
    transform: translateY(-3px);
    border-color: rgba(245,158,11,0.3);
}
.dash-stat::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px;
    background: linear-gradient(90deg, transparent, rgba(245,158,11,0.4), transparent);
}
.dash-stat .icon {
    width: 38px; height: 38px; border-radius: 10px;
    background: rgba(245, 158, 11, 0.12);
    color: #fbbf24; font-size: 16px;
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 12px;
}
.dash-stat .num {
    font-size: 24px; font-weight: 800;
    color: #fbbf24; line-height: 1.1;
    margin-bottom: 4px;
}
.dash-stat .lbl { color: #94a3b8; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; }

/* ============ QUICK ACTIONS ============ */
.dash-actions {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin-bottom: 24px;
}
@media (max-width: 900px) { .dash-actions { grid-template-columns: repeat(2, 1fr); } }

.dash-action {
    background: linear-gradient(135deg, #111827 0%, #0f172a 100%);
    border: 1px solid rgba(245, 158, 11, 0.15);
    border-radius: 16px;
    padding: 20px 16px;
    text-decoration: none;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 10px;
    transition: all 0.25s ease;
    cursor: pointer;
}
.dash-action:hover {
    transform: translateY(-4px);
    border-color: rgba(245,158,11,0.4);
    background: linear-gradient(135deg, #1a2235 0%, #131c30 100%);
}
.dash-action .ico {
    width: 48px; height: 48px; border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px;
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: #fff;
    box-shadow: 0 4px 12px rgba(245,158,11,0.25);
}
.dash-action .ico.green { background: linear-gradient(135deg, #10b981 0%, #059669 100%); box-shadow: 0 4px 12px rgba(16,185,129,0.25); }
.dash-action .ico.blue  { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); box-shadow: 0 4px 12px rgba(59,130,246,0.25); }
.dash-action .ico.purple{ background: linear-gradient(135deg, #a855f7 0%, #7e22ce 100%); box-shadow: 0 4px 12px rgba(168,85,247,0.25); }
.dash-action .label {
    color: #e5e7eb; font-size: 13px; font-weight: 700;
    letter-spacing: 0.3px;
}
.dash-action .sub { color: #64748b; font-size: 11px; }

/* ============ SECTIONS ============ */
.dash-grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 24px;
}
@media (max-width: 900px) { .dash-grid-2 { grid-template-columns: 1fr; } }

.dash-section {
    background: linear-gradient(135deg, #111827 0%, #0f172a 100%);
    border: 1px solid rgba(245, 158, 11, 0.15);
    border-radius: 18px;
    overflow: hidden;
}
.dash-section-head {
    padding: 16px 20px;
    border-bottom: 1px solid rgba(245,158,11,0.1);
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.dash-section-head h3 {
    color: #fbbf24; font-size: 14px; font-weight: 700; margin: 0;
    display: flex; align-items: center; gap: 8px;
}
.dash-section-head a {
    color: #94a3b8; font-size: 12px; text-decoration: none;
    display: flex; align-items: center; gap: 4px;
}
.dash-section-head a:hover { color: #fbbf24; }

.dash-section-body { padding: 8px 0; }

/* Empty state */
.dash-empty {
    padding: 40px 20px;
    text-align: center;
    color: #64748b;
}
.dash-empty i { font-size: 40px; color: rgba(245,158,11,0.15); display: block; margin-bottom: 10px; }
.dash-empty p { font-size: 13px; margin: 0; }

/* List items */
.dash-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 12px 20px;
    border-bottom: 1px solid rgba(245,158,11,0.05);
    text-decoration: none;
    transition: background 0.2s;
}
.dash-item:last-child { border-bottom: none; }
.dash-item:hover { background: rgba(245,158,11,0.04); }

.dash-item-icon {
    width: 38px; height: 38px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 15px; flex-shrink: 0;
    background: rgba(245, 158, 11, 0.1);
    color: #fbbf24;
}
.dash-item-icon.green  { background: rgba(16,185,129,0.1); color: #34d399; }
.dash-item-icon.blue   { background: rgba(59,130,246,0.1); color: #60a5fa; }
.dash-item-icon.red    { background: rgba(239,68,68,0.1);  color: #f87171; }
.dash-item-icon.purple { background: rgba(168,85,247,0.1); color: #c084fc; }

.dash-item-info { flex: 1; min-width: 0; }
.dash-item-info .t1 {
    color: #e5e7eb; font-size: 13px; font-weight: 600;
    display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.dash-item-info .t2 {
    color: #64748b; font-size: 11px; display: block; margin-top: 2px;
}
.dash-item-info .t2.gold { color: #fbbf24; font-weight: 700; }

.dash-item-right {
    text-align: right;
    flex-shrink: 0;
}

/* Status badges */
.ds-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 10px; font-weight: 700;
    letter-spacing: 0.3px;
    text-transform: uppercase;
}
.ds-badge.green  { background: rgba(16,185,129,0.15); color: #34d399; border: 1px solid rgba(16,185,129,0.3); }
.ds-badge.amber  { background: rgba(245,158,11,0.15); color: #fbbf24; border: 1px solid rgba(245,158,11,0.3); }
.ds-badge.red    { background: rgba(239,68,68,0.15);  color: #f87171; border: 1px solid rgba(239,68,68,0.3); }
.ds-badge.blue   { background: rgba(59,130,246,0.15); color: #60a5fa; border: 1px solid rgba(59,130,246,0.3); }
.ds-badge.gray   { background: rgba(100,116,139,0.15); color: #94a3b8; border: 1px solid rgba(100,116,139,0.3); }

/* Notification unread dot */
.unread-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: #f59e0b;
    display: inline-block;
    box-shadow: 0 0 0 3px rgba(245,158,11,0.2);
    flex-shrink: 0;
}

/* Alert banner */
.dash-alert {
    background: rgba(245, 158, 11, 0.08);
    border: 1px solid rgba(245, 158, 11, 0.25);
    border-radius: 14px;
    padding: 14px 18px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
}
.dash-alert i { color: #fbbf24; font-size: 20px; flex-shrink: 0; }
.dash-alert-body { flex: 1; color: #e5e7eb; font-size: 13px; }
.dash-alert-body strong { color: #fbbf24; }
.dash-alert a {
    color: #fbbf24; font-weight: 700; text-decoration: underline;
    font-size: 12px; white-space: nowrap;
}

@media (max-width: 640px) {
    .dash-header { padding: 18px; }
    .dash-avatar { width: 56px; height: 56px; font-size: 22px; }
    .dash-header-info h1 { font-size: 18px; }
    .dash-stat { padding: 14px; }
    .dash-stat .num { font-size: 20px; }
    .dash-stat .icon { width: 34px; height: 34px; font-size: 14px; margin-bottom: 8px; }
    .dash-action { padding: 16px 10px; }
    .dash-action .ico { width: 42px; height: 42px; font-size: 18px; }
    .dash-item { padding: 10px 14px; gap: 10px; }
}
</style>

<div class="dash-wrap">
    <div class="container">

        <!-- ========== HEADER ========== -->
        <div class="dash-header">
            <div class="dash-avatar"><?php echo e($initial); ?></div>
            <div class="dash-header-info">
                <h1>Karibu, <?php echo e($user['firstname']); ?>!</h1>
                <p><?php echo e($user['email']); ?></p>
                <small><i class="fas fa-map-marker-alt"></i> <?php echo e($user['region'] ?: 'No region set'); ?>
                    &nbsp;·&nbsp; <i class="fas fa-phone"></i> <?php echo e($user['phone'] ?: 'No phone set'); ?>
                    &nbsp;·&nbsp; Member since <?php echo date('M Y', strtotime($user['created_at'])); ?>
                </small>
            </div>
            <div class="dash-header-actions">
                <a href="products.php" class="dash-btn dash-btn-gold">
                    <i class="fas fa-store"></i> Shop
                </a>
                <a href="profile.php" class="dash-btn dash-btn-outline">
                    <i class="fas fa-user-cog"></i> Profile
                </a>
            </div>
        </div>

        <?php if (empty($user['phone']) || empty($user['region'])): ?>
        <!-- ========== COMPLETE PROFILE ALERT ========== -->
        <div class="dash-alert">
            <i class="fas fa-exclamation-circle"></i>
            <div class="dash-alert-body">
                <strong>Complete your profile</strong> — add your phone number and region so we can deliver your orders and disburse loans.
            </div>
            <a href="profile.php">Update now →</a>
        </div>
        <?php endif; ?>

        <!-- ========== STATS ========== -->
        <div class="dash-stats">
            <div class="dash-stat">
                <div class="icon"><i class="fas fa-shopping-bag"></i></div>
                <div class="num"><?php echo number_format($total_orders); ?></div>
                <div class="lbl">Total Orders</div>
            </div>
            <div class="dash-stat">
                <div class="icon"><i class="fas fa-coins"></i></div>
                <div class="num" style="font-size:18px;"><?php echo fmt($total_spent); ?></div>
                <div class="lbl">Total Spent</div>
            </div>
            <div class="dash-stat">
                <div class="icon"><i class="fas fa-hand-holding-usd"></i></div>
                <div class="num"><?php echo number_format($total_loans); ?></div>
                <div class="lbl">Loan Applications</div>
            </div>
            <div class="dash-stat">
                <div class="icon"><i class="fas fa-bell"></i></div>
                <div class="num">
                    <?php echo number_format($unread_notifs); ?>
                    <?php if ($unread_notifs > 0): ?><span style="font-size:10px;color:#f59e0b;vertical-align:top;margin-left:2px;">●</span><?php endif; ?>
                </div>
                <div class="lbl">Unread Alerts</div>
            </div>
        </div>

        <!-- ========== QUICK ACTIONS ========== -->
        <div class="dash-actions">
            <a href="products.php" class="dash-action">
                <div class="ico"><i class="fas fa-store"></i></div>
                <div class="label">Browse Products</div>
                <div class="sub">Shop for inputs</div>
            </a>
            <a href="cart.php" class="dash-action">
                <div class="ico green"><i class="fas fa-shopping-cart"></i></div>
                <div class="label">My Cart</div>
                <div class="sub">Complete checkout</div>
            </a>
            <a href="loan.php" class="dash-action">
                <div class="ico blue"><i class="fas fa-hand-holding-dollar"></i></div>
                <div class="label">Apply for Loan</div>
                <div class="sub">Farm financing</div>
            </a>
            <a href="profile.php" class="dash-action">
                <div class="ico purple"><i class="fas fa-user-cog"></i></div>
                <div class="label">My Profile</div>
                <div class="sub">Manage account</div>
            </a>
        </div>

        <!-- ========== RECENT ACTIVITY ========== -->
        <div class="dash-grid-2">

            <!-- RECENT ORDERS -->
            <div class="dash-section">
                <div class="dash-section-head">
                    <h3><i class="fas fa-shopping-bag"></i> Recent Orders</h3>
                    <a href="orders.php">View all <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="dash-section-body">
                    <?php if (empty($recent_orders)): ?>
                        <div class="dash-empty">
                            <i class="fas fa-shopping-bag"></i>
                            <p>You haven't placed any orders yet.</p>
                            <a href="products.php" class="dash-btn dash-btn-gold" style="margin-top:12px;">Browse products</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_orders as $o):
                            $st = $o['payment_status'] === 'paid' ? 'green'
                                : ($o['status'] === 'rejected' ? 'red'
                                : ($o['status'] === 'delivered' ? 'green'
                                : ($o['status'] === 'shipped' ? 'blue' : 'amber')));
                            $label = $o['payment_status'] === 'paid' ? 'Paid' : ucfirst($o['status']);
                        ?>
                        <a href="order_detail.php?ref=<?php echo urlencode($o['order_ref']); ?>" class="dash-item">
                            <div class="dash-item-icon"><i class="fas fa-receipt"></i></div>
                            <div class="dash-item-info">
                                <span class="t1"><?php echo e($o['order_ref']); ?></span>
                                <span class="t2">
                                    <?php echo date('d M Y', strtotime($o['created_at'])); ?> ·
                                    <span class="gold"><?php echo fmt((float)$o['total_amount']); ?></span>
                                </span>
                            </div>
                            <div class="dash-item-right">
                                <span class="ds-badge <?php echo $st; ?>"><?php echo $label; ?></span>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- RECENT LOANS -->
            <div class="dash-section">
                <div class="dash-section-head">
                    <h3><i class="fas fa-hand-holding-usd"></i> Recent Loans</h3>
                    <a href="my_loans.php">View all <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="dash-section-body">
                    <?php if (empty($recent_loans)): ?>
                        <div class="dash-empty">
                            <i class="fas fa-hand-holding-usd"></i>
                            <p>You haven't applied for a loan yet.</p>
                            <a href="loan.php" class="dash-btn dash-btn-gold" style="margin-top:12px;">Apply now</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_loans as $l):
                            $stMap = ['pending'=>'amber','approved'=>'blue','rejected'=>'red','disbursed'=>'green'];
                            $st = $stMap[$l['status']] ?? 'gray';
                        ?>
                        <a href="loan_detail.php?id=<?php echo (int)$l['id']; ?>" class="dash-item">
                            <div class="dash-item-icon purple"><i class="fas fa-hand-holding-usd"></i></div>
                            <div class="dash-item-info">
                                <span class="t1"><?php echo e($l['loan_ref']); ?></span>
                                <span class="t2">
                                    <?php echo date('d M Y', strtotime($l['created_at'])); ?> ·
                                    <span class="gold"><?php echo fmt((float)$l['requested_amount']); ?></span>
                                </span>
                            </div>
                            <div class="dash-item-right">
                                <span class="ds-badge <?php echo $st; ?>"><?php echo ucfirst($l['status']); ?></span>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ========== NOTIFICATIONS ========== -->
        <div class="dash-section">
            <div class="dash-section-head">
                <h3>
                    <i class="fas fa-bell"></i> Notifications
                    <?php if ($unread_notifs > 0): ?>
                        <span style="background:#f59e0b;color:#0f172a;font-size:10px;padding:2px 8px;border-radius:20px;margin-left:6px;"><?php echo $unread_notifs; ?> new</span>
                    <?php endif; ?>
                </h3>
                <a href="notifications.php">View all <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="dash-section-body">
                <?php if (empty($recent_notifs)): ?>
                    <div class="dash-empty">
                        <i class="fas fa-bell-slash"></i>
                        <p>No notifications yet.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recent_notifs as $n):
                        $typeIcon = $n['type'] === 'loan' ? 'fa-hand-holding-usd' : 'fa-shopping-bag';
                        $typeClass = $n['type'] === 'loan' ? 'purple' : '';
                    ?>
                    <a href="notifications.php?highlight=<?php echo (int)$n['id']; ?>" class="dash-item">
                        <?php if (!$n['is_read']): ?>
                            <span class="unread-dot"></span>
                        <?php endif; ?>
                        <div class="dash-item-icon <?php echo $typeClass; ?>">
                            <i class="fas <?php echo $typeIcon; ?>"></i>
                        </div>
                        <div class="dash-item-info">
                            <span class="t1" style="<?php echo $n['is_read'] ? 'opacity:0.7;' : ''; ?>">
                                <?php echo e($n['message'] ?: $n['ref_code']); ?>
                            </span>
                            <span class="t2"><?php echo time_ago($n['created_at']); ?></span>
                        </div>
                        <div class="dash-item-right">
                            <?php if ($n['new_status']): ?>
                                <span class="ds-badge gray"><?php echo ucfirst($n['new_status']); ?></span>
                            <?php endif; ?>
                        </div>
                    </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>