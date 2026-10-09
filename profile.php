<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();
$page_title = 'My Account';

if (!isset($_SESSION['user_id'])) {
    header('Location: /agro_project/login.php?next=profile.php');
    exit;
}

$uid     = (int)$_SESSION['user_id'];
$msg_ok  = $msg_err = '';

// ── Save profile updates ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    $fn = mysqli_real_escape_string($conn, trim($_POST['firstname'] ?? ''));
    $ln = mysqli_real_escape_string($conn, trim($_POST['lastname']  ?? ''));
    $ph = mysqli_real_escape_string($conn, trim($_POST['phone']     ?? ''));
    $rg = mysqli_real_escape_string($conn, trim($_POST['region']    ?? ''));

    if (!$fn || !$ln) {
        $msg_err = 'First name and last name are required.';
    } else {
        mysqli_query($conn,
            "UPDATE users SET firstname='$fn', lastname='$ln', phone='$ph', region='$rg' WHERE id=$uid");
        $_SESSION['firstname'] = trim($_POST['firstname']);
        $_SESSION['lastname']  = trim($_POST['lastname']);
        $_SESSION['user_name'] = trim($_POST['firstname'] . ' ' . $_POST['lastname']);
        $msg_ok = 'Profile updated successfully.';
    }
}

// ── Change password ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $cur  = $_POST['current_password'] ?? '';
    $new  = $_POST['new_password']     ?? '';
    $conf = $_POST['confirm_password'] ?? '';
    $row  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT password FROM users WHERE id=$uid LIMIT 1"));

    if (!$row || !password_verify($cur, $row['password'] ?? '')) {
        $msg_err = 'Current password is incorrect.';
    } elseif (strlen($new) < 6) {
        $msg_err = 'New password must be at least 6 characters.';
    } elseif ($new !== $conf) {
        $msg_err = 'New passwords do not match.';
    } else {
        $hash = password_hash($new, PASSWORD_BCRYPT);
        mysqli_query($conn, "UPDATE users SET password='$hash' WHERE id=$uid");
        $msg_ok = 'Password changed successfully.';
    }
}

// ── Fetch user from DB (always fresh) ─────────────────────
$user = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT id, firstname, lastname, email, phone, region, user_role, created_at FROM users WHERE id=$uid LIMIT 1"));

if (!$user) { logout_user(); header('Location: /agro_project/login.php'); exit; }

// ── All user orders from DB ────────────────────────────────
$orders_res = mysqli_query($conn,
    "SELECT o.*, COUNT(oi.id) items,
            GROUP_CONCAT(p.name ORDER BY p.name SEPARATOR ', ') prod_names
     FROM orders o
     LEFT JOIN order_items oi ON oi.order_id = o.id
     LEFT JOIN products p    ON p.id = oi.product_id
     WHERE o.user_id = $uid
     GROUP BY o.id ORDER BY o.created_at DESC");

// ── All user loan applications from DB ─────────────────────
$loans_res = mysqli_query($conn,
    "SELECT * FROM loan_applications WHERE user_id = $uid ORDER BY created_at DESC");

// ── Unread notifications for this user ────────────────────
$notifs_res = mysqli_query($conn,
    "SELECT * FROM notifications
     WHERE user_id = $uid AND is_read = 0
     ORDER BY created_at DESC LIMIT 10");
$unread_count = $notifs_res ? mysqli_num_rows($notifs_res) : 0;

// ── Order status config from DB ───────────────────────────
$status_cfg = [];
$sc_res = mysqli_query($conn, "SELECT slug, label, color FROM order_statuses");
if ($sc_res) while ($r = mysqli_fetch_assoc($sc_res)) $status_cfg[$r['slug']] = $r;

require_once __DIR__ . '/includes/header.php';
?>

<style>
/* My Account page dark theme */
.page-header {
    background: linear-gradient(135deg, #0f172a 0%, #0a0f1c 100%);
    border-bottom: 1px solid rgba(245, 158, 11, 0.15);
}
.page-header h1 {
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}
.page-header p { color: #9ca3af; }
.breadcrumb a { color: #fbbf24; }
.breadcrumb a:hover { color: #f59e0b; }

.card {
    background: linear-gradient(135deg, #111827 0%, #0f172a 100%);
    border: 1px solid rgba(245, 158, 11, 0.15);
    border-radius: 16px;
    overflow: hidden;
}
.card-body { padding: 24px; }

/* Profile avatar */
.profile-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: rgba(245, 158, 11, 0.15);
    border: 2px solid #f59e0b;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: #f59e0b;
    font-weight: 700;
    margin-bottom: 12px;
}

/* Form styling */
.form-group label {
    color: #fbbf24;
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}
.form-control {
    background: #1e293b;
    border: 1px solid #334155;
    color: #e5e7eb;
    border-radius: 12px;
    padding: 12px 16px;
    transition: all 0.3s;
    width: 100%;
}
.form-control:focus {
    border-color: #f59e0b;
    box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2);
    outline: none;
}
.form-row-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}
@media (max-width: 640px) {
    .form-row-2 { grid-template-columns: 1fr; gap: 0; }
}

/* Badges */
.badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}
.badge-gold {
    background: rgba(245, 158, 11, 0.15);
    color: #fbbf24;
    border: 1px solid rgba(245, 158, 11, 0.3);
}
.badge-success {
    background: rgba(16, 185, 129, 0.15);
    color: #34d399;
    border: 1px solid rgba(16, 185, 129, 0.3);
}
.badge-warn {
    background: rgba(245, 158, 11, 0.15);
    color: #fbbf24;
    border: 1px solid rgba(245, 158, 11, 0.3);
}
.badge-danger {
    background: rgba(239, 68, 68, 0.15);
    color: #f87171;
    border: 1px solid rgba(239, 68, 68, 0.3);
}
.badge-info {
    background: rgba(59, 130, 246, 0.15);
    color: #60a5fa;
    border: 1px solid rgba(59, 130, 246, 0.3);
}

/* Buttons */
.btn-gold {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    border: none;
    color: white;
    font-weight: 700;
    padding: 12px;
    border-radius: 12px;
    transition: all 0.3s;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
.btn-gold:hover {
    background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(245, 158, 11, 0.3);
}
.btn-outline {
    background: transparent;
    border: 1px solid rgba(245, 158, 11, 0.4);
    color: #fbbf24;
    padding: 12px;
    border-radius: 12px;
    width: 100%;
    cursor: pointer;
    transition: all 0.3s;
}
.btn-outline:hover {
    background: rgba(245, 158, 11, 0.1);
    border-color: #f59e0b;
}
.btn-sm { padding: 8px 16px; font-size: 13px; }

/* Notification banner */
.notification-banner {
    background: rgba(245, 158, 11, 0.05);
    border: 1px solid rgba(245, 158, 11, 0.2);
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 24px;
}
.notification-item {
    padding: 14px;
    background: #1e293b;
    border-radius: 12px;
    margin-bottom: 10px;
    display: flex;
    gap: 14px;
    align-items: flex-start;
}
.notification-item:last-child { margin-bottom: 0; }

/* Tables */
.table-wrap {
    overflow-x: auto;
    border-radius: 12px;
    border: 1px solid rgba(245, 158, 11, 0.15);
}
.table {
    width: 100%;
    border-collapse: collapse;
    background: linear-gradient(135deg, #111827 0%, #0f172a 100%);
}
.table th {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    color: #fbbf24;
    font-weight: 700;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 14px;
    border-bottom: 1px solid #1f2937;
}
.table td {
    color: #e5e7eb;
    padding: 14px;
    border-bottom: 1px solid #1f2937;
}
.table tbody tr:last-child td { border-bottom: none; }
.table tbody tr:hover td { background: rgba(245, 158, 11, 0.05); }

/* Empty state */
.empty-state {
    text-align: center;
    padding: 50px 20px;
    background: linear-gradient(135deg, #111827 0%, #0f172a 100%);
    border: 1px solid rgba(245, 158, 11, 0.15);
    border-radius: 16px;
}
.empty-state i {
    font-size: 3rem;
    color: #f59e0b;
    margin-bottom: 16px;
    display: block;
}
.empty-state p { color: #9ca3af; }

/* Alerts */
.alert {
    padding: 14px 18px;
    border-radius: 12px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
}
.alert-success {
    background: rgba(16, 185, 129, 0.1);
    border-left: 4px solid #10b981;
    color: #34d399;
}
.alert-danger {
    background: rgba(239, 68, 68, 0.1);
    border-left: 4px solid #ef4444;
    color: #f87171;
}

/* Admin note */
.admin-note {
    font-size: 11px;
    margin-top: 6px;
    padding: 6px 10px;
    background: rgba(59, 130, 246, 0.1);
    border-radius: 8px;
    color: #60a5fa;
}

/* Responsive */
@media (max-width: 992px) {
    .profile-layout { grid-template-columns: 1fr !important; }
}
@media (max-width: 768px) {
    .card-body { padding: 16px; }
    .table th, .table td { padding: 10px 8px; font-size: 12px; }
}
@media (max-width: 480px) {
    .notification-item { flex-direction: column; }
}
</style>

<div class="page-header">
    <div class="container">
        <div class="breadcrumb"><a href="index.php">Home</a><span>›</span> My Account</div>
        <h1>My Account</h1>
        <p>Welcome back, <?php echo e(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? '')); ?></p>
    </div>
</div>

<section class="section">
<div class="container">

<?php if ($msg_ok): ?>
<div class="alert alert-success">
    <i class="fas fa-check-circle"></i>
    <span><?php echo e($msg_ok); ?></span>
</div>
<?php endif; ?>

<?php if ($msg_err): ?>
<div class="alert alert-danger">
    <i class="fas fa-exclamation-circle"></i>
    <span><?php echo e($msg_err); ?></span>
</div>
<?php endif; ?>

<!-- NOTIFICATIONS BANNER -->
<?php if ($unread_count > 0): ?>
<div class="notification-banner">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px">
        <h3 style="color:#fbbf24;margin:0">
            <i class="fas fa-bell" style="color:#f59e0b;margin-right:8px"></i> Notifications
            <span style="background:#f59e0b;color:#0f172a;border-radius:50%;width:24px;height:24px;font-size:12px;display:inline-flex;align-items:center;justify-content:center;font-weight:800;margin-left:8px"><?php echo $unread_count; ?></span>
        </h3>
        <a href="mark_notifications_read.php" class="btn-outline" style="padding:6px 14px;text-decoration:none;font-size:12px;width:auto">Mark all read</a>
    </div>
    <?php mysqli_data_seek($notifs_res, 0); while ($n = mysqli_fetch_assoc($notifs_res)):
        $icon = ($n['type'] ?? '') === 'loan' ? 'fa-hand-holding-dollar' : 'fa-box';
        // Safe extraction of preview text (never null)
        $preview_src = $n['body'] ?? $n['message'] ?? $n['subject'] ?? '';
        $preview = mb_substr(strip_tags((string)$preview_src), 0, 120);
    ?>
    <div class="notification-item">
        <i class="fas <?php echo $icon; ?>" style="color:#f59e0b;font-size:1.2rem;margin-top:2px"></i>
        <div style="flex:1">
            <div style="font-weight:700;margin-bottom:4px"><?php echo e($n['subject'] ?? 'Notification'); ?></div>
            <?php if ($preview !== ''): ?>
            <div style="font-size:12px;color:#9ca3af;line-height:1.5">
                <?php echo e($preview); ?>…
            </div>
            <?php endif; ?>
            <div style="font-size:11px;color:#64748b;margin-top:6px">
                <i class="fas fa-clock"></i> <?php echo date('d M Y H:i', strtotime($n['created_at'] ?? 'now')); ?>
                <?php if (!empty($n['sent_email'])): ?>
                &nbsp;· <i class="fas fa-envelope" style="color:#10b981"></i> Email sent
                <?php endif; ?>
                <?php if (!empty($n['whatsapp_link'])): ?>
                &nbsp;· <a href="<?php echo e($n['whatsapp_link']); ?>" target="_blank" style="color:#25d366"><i class="fab fa-whatsapp"></i> WhatsApp</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endwhile; ?>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:300px 1fr;gap:24px;align-items:start" class="profile-layout">

<!-- SIDEBAR: Profile + Password -->
<div>
    <!-- Profile info -->
    <div class="card card-body" style="margin-bottom:20px;text-align:center">
        <div class="profile-avatar">
            <?php echo strtoupper(mb_substr($user['firstname'] ?? 'U', 0, 1)); ?>
        </div>
        <div style="font-weight:700;font-size:16px;margin-bottom:4px"><?php echo e(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? '')); ?></div>
        <div style="font-size:12px;color:#9ca3af;margin-bottom:4px"><?php echo e($user['email'] ?? ''); ?></div>
        <span class="badge badge-gold" style="margin:8px 0"><?php echo ucfirst($user['user_role'] ?? 'user'); ?></span>
        <div style="font-size:11px;color:#64748b">
            Member since <?php echo !empty($user['created_at']) ? date('M Y', strtotime($user['created_at'])) : '—'; ?>
        </div>

        <form method="POST" style="margin-top:20px;text-align:left">
            <div class="form-row-2">
                <div class="form-group">
                    <label>First name</label>
                    <input type="text" name="firstname" class="form-control" required value="<?php echo e($user['firstname'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Last name</label>
                    <input type="text" name="lastname" class="form-control" required value="<?php echo e($user['lastname'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Phone / WhatsApp</label>
                <input type="tel" name="phone" class="form-control" value="<?php echo e($user['phone'] ?? ''); ?>" placeholder="+255...">
            </div>
            <div class="form-group">
                <label>Region</label>
                <input type="text" name="region" class="form-control" value="<?php echo e($user['region'] ?? ''); ?>" placeholder="e.g. Mbeya">
            </div>
            <button type="submit" name="save_profile" class="btn-gold" style="width:100%">
                <i class="fas fa-save"></i> Save Profile
            </button>
        </form>
    </div>

    <!-- Change password -->
    <div class="card card-body">
        <h4 style="margin-bottom:16px;color:#fbbf24">
            <i class="fas fa-lock" style="color:#f59e0b;margin-right:8px"></i> Change Password
        </h4>
        <form method="POST">
            <div class="form-group">
                <label>Current password</label>
                <input type="password" name="current_password" class="form-control" required placeholder="••••••">
            </div>
            <div class="form-group">
                <label>New password</label>
                <input type="password" name="new_password" class="form-control" required placeholder="Min 6 characters">
            </div>
            <div class="form-group">
                <label>Confirm new password</label>
                <input type="password" name="confirm_password" class="form-control" required placeholder="Repeat new password">
            </div>
            <button type="submit" name="change_password" class="btn-outline" style="width:100%">
                <i class="fas fa-key"></i> Change Password
            </button>
        </form>
    </div>
</div>

<!-- MAIN: Orders + Loans -->
<div>
    <!-- ── ORDERS ── -->
    <div style="margin-bottom:28px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px">
            <h3 style="margin:0;color:#fbbf24">
                <i class="fas fa-box" style="color:#f59e0b;margin-right:8px"></i> My Orders
                <span style="font-size:13px;font-weight:400;color:#9ca3af;margin-left:6px">
                    (<?php echo $orders_res ? mysqli_num_rows($orders_res) : 0; ?> total)
                </span>
            </h3>
            <a href="/agro_project/products.php" class="btn-gold btn-sm" style="text-decoration:none;padding:8px 16px">
                <i class="fas fa-plus"></i> New Order
            </a>
        </div>

        <?php if (!$orders_res || mysqli_num_rows($orders_res) === 0): ?>
        <div class="empty-state">
            <i class="fas fa-box-open"></i>
            <p>You haven't placed any orders yet.</p>
            <a href="/agro_project/products.php" class="btn-gold btn-sm" style="display:inline-block;margin-top:12px;text-decoration:none">Shop now</a>
        </div>
        <?php else: ?>
        <div class="table-wrap">
        <table class="table">
        <thead>
            <tr>
                <th>Order Ref</th>
                <th>Products</th>
                <th>Total</th>
                <th>Status</th>
                <th>Notification</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
        <?php mysqli_data_seek($orders_res, 0); while ($o = mysqli_fetch_assoc($orders_res)):
            $st = $status_cfg[$o['status']] ?? ['label' => ucfirst($o['status'] ?? 'pending'), 'color' => 'badge-gold'];

            // Fetch latest notification for this order (only if email matches)
            $onotif = null;
            if (!empty($o['email'])) {
                $email_esc = mysqli_real_escape_string($conn, $o['email']);
                $ref_esc   = mysqli_real_escape_string($conn, $o['order_ref']);
                $onotif = mysqli_fetch_assoc(mysqli_query($conn,
                    "SELECT sent_email, sent_at, whatsapp_link, subject FROM notifications
                     WHERE type='order' AND email='$email_esc'
                     AND subject LIKE '%$ref_esc%' ORDER BY id DESC LIMIT 1"));
            }
        ?>
        <tr>
            <td>
                <strong style="color:#f59e0b"><?php echo e($o['order_ref'] ?? '—'); ?></strong>
                <?php if (!empty($o['admin_note'])): ?>
                <div class="admin-note">
                    <i class="fas fa-comment-dots"></i> <?php echo e(mb_substr((string)$o['admin_note'], 0, 60)); ?>
                </div>
                <?php endif; ?>
            </td>
            <td style="font-size:12px">
                <?php echo (int)($o['items'] ?? 0); ?> item(s)<br>
                <span style="color:#9ca3af;font-size:11px"><?php echo e(mb_substr((string)($o['prod_names'] ?? ''), 0, 50)); ?></span>
            </td>
            <td><strong style="color:#f59e0b"><?php echo fmt((float)($o['total_amount'] ?? 0)); ?></strong></td>
            <td><span class="badge <?php echo e($st['color']); ?>"><?php echo e($st['label']); ?></span></td>
            <td style="font-size:12px">
                <?php if ($onotif && !empty($onotif['sent_email'])): ?>
                <span style="color:#34d399"><i class="fas fa-envelope"></i> Email sent</span>
                <?php if (!empty($onotif['whatsapp_link'])): ?>
                <br><a href="<?php echo e($onotif['whatsapp_link']); ?>" target="_blank" style="color:#25d366;font-size:11px">
                    <i class="fab fa-whatsapp"></i> WhatsApp
                </a>
                <?php endif; ?>
                <?php elseif (($o['status'] ?? '') === 'pending'): ?>
                <span style="color:#9ca3af"><i class="fas fa-hourglass-half"></i> Awaiting review</span>
                <?php else: ?>
                <span style="color:#64748b">—</span>
                <?php endif; ?>
            </td>
            <td style="font-size:12px;color:#64748b;white-space:nowrap">
                <?php echo !empty($o['created_at']) ? date('d M Y', strtotime($o['created_at'])) : '—'; ?>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── LOANS ── -->
    <div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px">
            <h3 style="margin:0;color:#fbbf24">
                <i class="fas fa-hand-holding-dollar" style="color:#f59e0b;margin-right:8px"></i> My Loan Applications
                <span style="font-size:13px;font-weight:400;color:#9ca3af;margin-left:6px">
                    (<?php echo $loans_res ? mysqli_num_rows($loans_res) : 0; ?> total)
                </span>
            </h3>
            <a href="/agro_project/loan.php" class="btn-gold btn-sm" style="text-decoration:none;padding:8px 16px">
                <i class="fas fa-plus"></i> New Application
            </a>
        </div>

        <?php if (!$loans_res || mysqli_num_rows($loans_res) === 0): ?>
        <div class="empty-state">
            <i class="fas fa-hand-holding-dollar"></i>
            <p>You haven't applied for a loan yet.</p>
            <a href="/agro_project/loan.php" class="btn-gold btn-sm" style="display:inline-block;margin-top:12px;text-decoration:none">Apply now</a>
        </div>
        <?php else: ?>
        <div class="table-wrap">
        <table class="table">
        <thead>
            <tr>
                <th>Loan Ref</th>
                <th>Farm / Crop</th>
                <th>Amount</th>
                <th>Monthly</th>
                <th>Period</th>
                <th>Status</th>
                <th>Notification</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
        <?php mysqli_data_seek($loans_res, 0); while ($l = mysqli_fetch_assoc($loans_res)):
            if ($l['status'] === 'approved' || $l['status'] === 'disbursed') { $lbadge = 'badge-success'; }
            elseif ($l['status'] === 'rejected') { $lbadge = 'badge-danger'; }
            elseif ($l['status'] === 'pending')  { $lbadge = 'badge-warn'; }
            else { $lbadge = 'badge-gold'; }

            $lnotif = null;
            if (!empty($l['email'])) {
                $email_esc = mysqli_real_escape_string($conn, $l['email']);
                $ref_esc   = mysqli_real_escape_string($conn, $l['loan_ref']);
                $lnotif = mysqli_fetch_assoc(mysqli_query($conn,
                    "SELECT sent_email, sent_at, whatsapp_link FROM notifications
                     WHERE type='loan' AND email='$email_esc'
                     AND subject LIKE '%$ref_esc%' ORDER BY id DESC LIMIT 1"));
            }
        ?>
        <tr>
            <td>
                <strong style="color:#f59e0b"><?php echo e($l['loan_ref'] ?? '—'); ?></strong>
                <?php if (!empty($l['admin_note'])): ?>
                <div class="admin-note">
                    <i class="fas fa-comment-dots"></i> <?php echo e(mb_substr((string)$l['admin_note'], 0, 60)); ?>
                </div>
                <?php endif; ?>
            </td>
            <td>
                <strong><?php echo e($l['farm_size_acres'] ?? '—'); ?> acres</strong><br>
                <span style="font-size:11px;color:#9ca3af">
                    <?php echo e($l['primary_crop'] ?? '—'); ?> — <?php echo e($l['loan_purpose'] ?? '—'); ?>
                </span>
            </td>
            <td>
                <strong><?php echo fmt((float)($l['requested_amount'] ?? 0)); ?></strong><br>
                <span style="font-size:10px;color:#64748b"><?php echo e($l['interest_rate'] ?? '0'); ?>% p.a.</span>
            </td>
            <td><?php echo fmt((float)($l['monthly_payment'] ?? 0)); ?>/mo</td>
            <td><?php echo (int)($l['repay_months'] ?? 0); ?> mo</td>
            <td><span class="badge <?php echo $lbadge; ?>"><?php echo ucfirst($l['status'] ?? 'pending'); ?></span></td>
            <td style="font-size:12px">
                <?php if ($lnotif && !empty($lnotif['sent_email'])): ?>
                <span style="color:#34d399"><i class="fas fa-envelope"></i> Email sent</span>
                <?php if (!empty($lnotif['whatsapp_link'])): ?>
                <br><a href="<?php echo e($lnotif['whatsapp_link']); ?>" target="_blank" style="color:#25d366;font-size:11px">
                    <i class="fab fa-whatsapp"></i> WhatsApp
                </a>
                <?php endif; ?>
                <?php elseif (($l['status'] ?? '') === 'pending'): ?>
                <span style="color:#9ca3af"><i class="fas fa-hourglass-half"></i> Awaiting review</span>
                <?php else: ?>
                <span style="color:#64748b">—</span>
                <?php endif; ?>
            </td>
            <td style="font-size:12px;color:#64748b;white-space:nowrap">
                <?php echo !empty($l['created_at']) ? date('d M Y', strtotime($l['created_at'])) : '—'; ?>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</div>
</div>
</div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>