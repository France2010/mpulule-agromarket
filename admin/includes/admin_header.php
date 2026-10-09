<?php
// admin/includes/admin_header.php
if (!isset($conn)) require_once __DIR__ . '/../../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Auth already checked by require_admin() in each page

$site_name      = get_setting('site_name');
$admin_page     = basename($_SERVER['PHP_SELF']);

$unread         = (int)mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM contact_messages WHERE is_read=0"))['c'];
$pending_orders = (int)mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM orders WHERE status='pending'"))['c'];
$pending_loans  = (int)mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM loan_applications WHERE status='pending'"))['c'];
$notif_unsent   = (int)mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM notifications WHERE sent_email=0"))['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? e($page_title).' | ' : ''; ?>Admin — <?php echo e($site_name); ?></title>
    <link rel="stylesheet" href="/agro_project/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
    /* ── MOBILE SIDEBAR TOGGLE ── */
    .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:98}
    .sidebar-overlay.show{display:block}
    .admin-sidebar{transition:transform .25s ease}
    @media(max-width:768px){
        .admin-sidebar{position:fixed;top:0;left:0;height:100vh;z-index:99;transform:translateX(-100%)}
        .admin-sidebar.open{transform:translateX(0)}
        .admin-main{margin-left:0!important}
        .admin-topbar{padding:12px 16px}
        .admin-content{padding:16px}
        .grid-4{grid-template-columns:repeat(2,1fr)!important}
        .grid-3{grid-template-columns:repeat(2,1fr)!important}
        .grid-2{grid-template-columns:1fr!important}
        .table-wrap{font-size:12px}
        .table th,.table td{padding:8px 10px!important}
        .hamburger{display:flex!important}
    }
    /* ============================================================
   MODERN DARK THEME FOR ADMIN PANEL - Like UDOM IOT System
   ============================================================ */

/* Override variables for admin panel */
:root {
    --admin-dark: #0a0f1c;
    --admin-card-dark: #111827;
    --admin-border-dark: #1f2937;
    --admin-gold: #f59e0b;
    --admin-gold-dark: #d97706;
    --admin-gold-light: #fbbf24;
    --admin-success: #10b981;
    --admin-danger: #ef4444;
    --admin-warning: #f59e0b;
    --admin-text: #e5e7eb;
    --admin-text-muted: #9ca3af;
}

/* Admin Layout */
.admin-wrap {
    background: linear-gradient(135deg, #0a0f1c 0%, #0c1122 100%);
}

/* Sidebar - Dark Modern */
.admin-sidebar {
    background: linear-gradient(180deg, #0f172a 0%, #0a0f1c 100%);
    border-right: 1px solid rgba(245, 158, 11, 0.15);
}

.admin-sidebar .brand {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    border-bottom: 1px solid rgba(245, 158, 11, 0.2);
    color: #f59e0b;
    font-weight: 800;
    font-size: 1.1rem;
    padding: 16px 20px;
}

.admin-sidebar .brand i {
    color: #f59e0b;
    margin-right: 10px;
}

.admin-nav a {
    color: #94a3b8;
    margin: 6px 12px;
    border-radius: 12px;
    padding: 10px 16px;
    font-size: 13px;
    transition: all 0.25s ease;
}

.admin-nav a:hover {
    background: linear-gradient(90deg, rgba(245, 158, 11, 0.15) 0%, transparent 100%);
    color: #fbbf24;
    transform: translateX(4px);
}

.admin-nav a.active {
    background: linear-gradient(90deg, rgba(245, 158, 11, 0.2) 0%, transparent 100%);
    color: #f59e0b;
    border-left: 3px solid #f59e0b;
    font-weight: 600;
}

.nav-section {
    color: #f59e0b;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 2px;
    padding: 16px 20px 6px;
    text-transform: uppercase;
    opacity: 0.7;
}

/* Topbar - Modern */
.admin-topbar {
    background: linear-gradient(135deg, #111827 0%, #0f172a 100%);
    border-bottom: 1px solid rgba(245, 158, 11, 0.15);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.admin-topbar h2 {
    color: #f59e0b;
    font-weight: 700;
    font-size: 1.2rem;
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}

/* Stat Cards - Vibrant */
.stat-card {
    background: linear-gradient(135deg, #111827 0%, #0f172a 100%);
    border: 1px solid rgba(245, 158, 11, 0.15);
    border-radius: 20px;
    padding: 20px;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 3px;
    background: linear-gradient(90deg, #f59e0b, #fbbf24);
}

.stat-card:hover {
    transform: translateY(-4px);
    border-color: rgba(245, 158, 11, 0.3);
    box-shadow: 0 10px 30px rgba(245, 158, 11, 0.1);
}

.stat-card .icon {
    font-size: 2rem;
    color: #f59e0b;
    margin-bottom: 12px;
}

.stat-card .num {
    font-size: 1.8rem;
    font-weight: 800;
    color: #fbbf24;
}

.stat-card .lbl {
    font-size: 0.75rem;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 600;
}

/* Table - Modern */
.table-wrap {
    border-radius: 16px;
    overflow: hidden;
    border: 1px solid rgba(245, 158, 11, 0.15);
    background: #111827;
}

.table {
    background: #111827;
}

.table th {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    color: #fbbf24;
    font-weight: 700;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    padding: 14px 16px;
    border-bottom: 1px solid #1f2937;
}

.table td {
    background: #111827;
    color: #e5e7eb;
    padding: 12px 16px;
    border-bottom: 1px solid #1f2937;
}

.table tbody tr:hover td {
    background: #1a2535;
}

/* Card - Modern */
.card {
    background: linear-gradient(135deg, #111827 0%, #0f172a 100%);
    border: 1px solid rgba(245, 158, 11, 0.15);
    border-radius: 20px;
    overflow: hidden;
}

.card-body {
    padding: 24px;
}

.card-body h3 {
    color: #fbbf24;
    font-weight: 700;
    margin-bottom: 20px;
}

/* Buttons - Modern */
.btn-gold {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    border: none;
    color: #fff;
    font-weight: 700;
    padding: 12px 24px;
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(245, 158, 11, 0.3);
    transition: all 0.3s ease;
}

.btn-gold:hover {
    background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(245, 158, 11, 0.4);
}

.btn-dark {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    border: 1px solid rgba(245, 158, 11, 0.3);
    color: #fbbf24;
}

.btn-dark:hover {
    background: linear-gradient(135deg, #2d3a4e 0%, #1e293b 100%);
    border-color: #f59e0b;
    color: #fbbf24;
}

.btn-outline {
    background: transparent;
    border: 1px solid rgba(245, 158, 11, 0.4);
    color: #fbbf24;
}

.btn-outline:hover {
    background: rgba(245, 158, 11, 0.1);
    border-color: #f59e0b;
}

/* Form Controls - Modern */
.form-group label {
    color: #fbbf24;
    font-weight: 600;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.form-control {
    background: #1e293b;
    border: 1px solid #334155;
    color: #e5e7eb;
    border-radius: 12px;
    padding: 12px 16px;
    transition: all 0.3s ease;
}

.form-control:focus {
    background: #1e293b;
    border-color: #f59e0b;
    box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2);
    color: #fff;
}

.form-control::placeholder {
    color: #64748b;
}

/* Badges - Modern */
.badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.7rem;
}

.badge-success {
    background: rgba(16, 185, 129, 0.15);
    color: #34d399;
    border: 1px solid rgba(16, 185, 129, 0.3);
}

.badge-danger {
    background: rgba(239, 68, 68, 0.15);
    color: #f87171;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.badge-warn {
    background: rgba(245, 158, 11, 0.15);
    color: #fbbf24;
    border: 1px solid rgba(245, 158, 11, 0.3);
}

.badge-info {
    background: rgba(59, 130, 246, 0.15);
    color: #60a5fa;
    border: 1px solid rgba(59, 130, 246, 0.3);
}

/* Alerts - Modern */
.alert {
    border-radius: 12px;
    border-left: 4px solid;
    padding: 14px 18px;
}

.alert-success {
    background: rgba(16, 185, 129, 0.1);
    border-color: #10b981;
    color: #34d399;
}

.alert-danger {
    background: rgba(239, 68, 68, 0.1);
    border-color: #ef4444;
    color: #f87171;
}

.alert-warning {
    background: rgba(245, 158, 11, 0.1);
    border-color: #f59e0b;
    color: #fbbf24;
}

/* Modal - Modern */
.modal-box {
    background: linear-gradient(135deg, #111827 0%, #0f172a 100%);
    border: 1px solid rgba(245, 158, 11, 0.2);
    border-radius: 24px;
}

.modal-close {
    color: #9ca3af;
}

.modal-close:hover {
    color: #fbbf24;
}

/* Select Dropdown */
select.form-control {
    background-color: #1e293b;
    cursor: pointer;
}

select.form-control option {
    background-color: #1e293b;
    color: #e5e7eb;
}

/* Scrollbar */
::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

::-webkit-scrollbar-track {
    background: #0f172a;
    border-radius: 4px;
}

::-webkit-scrollbar-thumb {
    background: #f59e0b;
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: #d97706;
}

/* Image Preview */
.current-image-preview {
    background: #1e293b;
    border-color: #334155;
}

.new-image-preview {
    background: rgba(245, 158, 11, 0.1);
    border-color: #f59e0b;
}/* Product Image Thumbnail */
.product-thumb {
    width: 45px;
    height: 45px;
    object-fit: cover;
    border-radius: 10px;
    background: #1e293b;
    border: 1px solid rgba(245, 158, 11, 0.3);
    transition: all 0.2s ease;
}

.product-thumb:hover {
    transform: scale(1.1);
    border-color: #f59e0b;
    box-shadow: 0 0 10px rgba(245, 158, 11, 0.4);
}

.no-image-icon {
    width: 45px;
    height: 45px;
    background: #1e293b;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #f59e0b;
    font-size: 20px;
    border: 1px solid #334155;
}
    .hamburger{display:none;align-items:center;justify-content:center;width:38px;height:38px;background:rgba(201,168,76,.15);border:1px solid rgba(201,168,76,.3);border-radius:8px;cursor:pointer;color:var(--gold)}
    </style>
</head>
<body>
<div id="toast"><i class="fas fa-check-circle"></i><span id="toast-msg"></span></div>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<div class="admin-wrap">
<aside class="admin-sidebar" id="adminSidebar">
    <div class="admin-sidebar brand" style="display:flex;align-items:center;justify-content:space-between">
        <span><i class="fas fa-leaf"></i> Admin Panel</span>
        <span onclick="closeSidebar()" style="cursor:pointer;color:rgba(255,255,255,.5);font-size:18px;display:none" id="sidebarClose">&times;</span>
    </div>
    <nav class="admin-nav">
        <a href="<?php echo dirname($_SERVER['PHP_SELF']) === '/agro_project/admin' ? '' : '../'; ?>index.php"
           class="<?php echo $admin_page==='index.php'?'active':''; ?>">
            <i class="fas fa-chart-line" style="width:18px"></i> Dashboard
        </a>
        <div class="nav-section">Orders</div>
        <a href="orders.php" class="<?php echo $admin_page==='orders.php'?'active':''; ?>">
            <i class="fas fa-box" style="width:18px"></i> All Orders
            <?php if($pending_orders): ?><span style="background:var(--gold);color:var(--dark);border-radius:10px;padding:1px 7px;font-size:10px;font-weight:700;margin-left:auto"><?php echo $pending_orders; ?></span><?php endif; ?>
        </a>
        <a href="order_detail.php" class="<?php echo $admin_page==='order_detail.php'?'active':''; ?>" style="display:none"></a>
        <div class="nav-section">Products</div>
        <a href="products.php" class="<?php echo $admin_page==='products.php'?'active':''; ?>">
            <i class="fas fa-seedling" style="width:18px"></i> Products
        </a>
        <a href="categories.php" class="<?php echo $admin_page==='categories.php'?'active':''; ?>">
            <i class="fas fa-tags" style="width:18px"></i> Categories
        </a>
        <div class="nav-section">Finance</div>
        <a href="loans.php" class="<?php echo $admin_page==='loans.php'?'active':''; ?>">
            <i class="fas fa-hand-holding-dollar" style="width:18px"></i> Loan Applications
            <?php if($pending_loans): ?><span style="background:var(--gold);color:var(--dark);border-radius:10px;padding:1px 7px;font-size:10px;font-weight:700;margin-left:auto"><?php echo $pending_loans; ?></span><?php endif; ?>
        </a>
        <div class="nav-section">Users</div>
        <a href="users.php" class="<?php echo $admin_page==='users.php'?'active':''; ?>">
            <i class="fas fa-users" style="width:18px"></i> Users
        </a>
        <a href="messages.php" class="<?php echo $admin_page==='messages.php'?'active':''; ?>">
            <i class="fas fa-envelope" style="width:18px"></i> Messages
            <?php if($unread): ?><span style="background:var(--gold);color:var(--dark);border-radius:10px;padding:1px 7px;font-size:10px;font-weight:700;margin-left:auto"><?php echo $unread; ?></span><?php endif; ?>
        </a>
        <a href="notifications.php" class="<?php echo $admin_page==='notifications.php'?'active':''; ?>">
            <i class="fas fa-bell" style="width:18px"></i> Notifications
            <?php if($notif_unsent): ?><span style="background:var(--gold);color:var(--dark);border-radius:10px;padding:1px 7px;font-size:10px;font-weight:700;margin-left:auto"><?php echo $notif_unsent; ?></span><?php endif; ?>
        </a>
        <div class="nav-section">Config</div>
        <a href="settings.php" class="<?php echo $admin_page==='settings.php'?'active':''; ?>">
            <i class="fas fa-gear" style="width:18px"></i> Settings
        </a>
        <a href="/agro_project/index.php">
            <i class="fas fa-external-link-alt" style="width:18px"></i> View Site
        </a>
        <a href="/agro_project/logout.php" style="color:#f87171">
            <i class="fas fa-right-from-bracket" style="width:18px"></i> Logout
        </a>
    </nav>
</aside>

<div class="admin-main">
<div class="admin-topbar">
    <div style="display:flex;align-items:center;gap:12px">
        <div class="hamburger" id="hamburgerBtn" onclick="openSidebar()">
            <i class="fas fa-bars"></i>
        </div>
        <h2><?php echo isset($page_title) ? e($page_title) : 'Dashboard'; ?></h2>
    </div>
    <div style="display:flex;align-items:center;gap:12px">
        <div style="font-size:13px;color:var(--text-muted)">
            <i class="fas fa-user-shield" style="color:var(--gold)"></i>
            <span class="hide-xs"><?php echo e($_SESSION['firstname'].' '.$_SESSION['lastname']); ?></span>
        </div>
        <a href="/agro_project/logout.php" style="font-size:13px;color:#f87171;text-decoration:none"><i class="fas fa-right-from-bracket"></i></a>
    </div>
</div>
<div class="admin-content">
<script>
function openSidebar(){
    document.getElementById('adminSidebar').classList.add('open');
    document.getElementById('sidebarOverlay').classList.add('show');
    document.getElementById('sidebarClose').style.display='block';
}
function closeSidebar(){
    document.getElementById('adminSidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('show');
    document.getElementById('sidebarClose').style.display='none';
}
</script>
