<?php
// includes/header.php — responsive navbar with mobile menu

if (!isset($conn)) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/agro_project/config.php';
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('is_logged_in')) {
    $auth_file = $_SERVER['DOCUMENT_ROOT'] . '/agro_project/includes/auth.php';
    if (file_exists($auth_file)) require_once $auth_file;
}

$site_name  = function_exists('get_setting') ? get_setting('site_name') : 'Mpulule AgroMarket';

$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += (int)($item['qty'] ?? 0);
    }
}

$is_logged = function_exists('is_logged_in')
    ? is_logged_in()
    : (!empty($_SESSION['user_id']) && !empty($_SESSION['loggedin']));

$is_admin = $is_logged && (($_SESSION['user_role'] ?? '') === 'admin');

$display_name = $_SESSION['firstname']
    ?? (isset($_SESSION['user_name']) ? explode(' ', $_SESSION['user_name'])[0] : '')
    ?: 'Account';

$cur_page = basename($_SERVER['PHP_SELF']);
$base     = $root ?? '';

$nav_links = [
    ['index.php',    'Home',      'fa-home'],
    ['products.php', 'Products',  'fa-seedling'],
    ['loan.php',     'Agri Loan', 'fa-hand-holding-dollar'],
    ['contact.php',  'Contact',   'fa-envelope'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo (isset($page_title) && $page_title !== '') ? e($page_title) . ' | ' : ''; echo e($site_name); ?></title>
    <link rel="stylesheet" href="/agro_project/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<div id="toast"><i class="fas fa-check-circle"></i><span id="toast-msg"></span></div>

<?php if (isset($_GET['welcome']) && $is_logged): ?>
<script>
window.addEventListener('DOMContentLoaded', function() {
    if (typeof showToast === 'function') {
        showToast('Welcome, <?php echo e(addslashes($display_name)); ?>! Your account has been created.');
    }
});
</script>
<?php endif; ?>

<!-- MOBILE MENU OVERLAY -->
<div class="mobile-menu" id="mobileMenu">
    <div class="mobile-menu-header">
        <span style="color:var(--gold);font-size:1.2rem;font-weight:800">
            <i class="fas fa-leaf"></i> <?php echo e($site_name); ?>
        </span>
        <span onclick="closeMobileMenu()" style="color:rgba(255,255,255,.7);font-size:24px;cursor:pointer">&times;</span>
    </div>
    <?php foreach ($nav_links as [$href, $label, $icon]): ?>
    <a href="/agro_project/<?php echo $href; ?>" class="<?php echo $cur_page === $href ? 'active' : ''; ?>">
        <i class="fas <?php echo $icon; ?>" style="width:20px"></i> <?php echo $label; ?>
    </a>
    <?php endforeach; ?>
    <?php if ($is_admin): ?>
    <a href="/agro_project/admin/index.php">
        <i class="fas fa-shield-halved" style="width:20px"></i> Admin Panel
    </a>
    <?php endif; ?>
    <div class="mobile-menu-footer">
        <a href="/agro_project/cart.php" class="btn btn-gold" style="flex:1;justify-content:center">
            <i class="fas fa-cart-shopping"></i> Cart <span id="mob-cart-count"><?php echo $cart_count; ?></span>
        </a>
       <?php if ($is_logged): ?>
<a href="/agro_project/dashboard.php" class="nav-user-btn" title="My Dashboard">
    <i class="fas fa-th-large"></i> <span class="hide-xs">Dashboard</span>
</a>
<a href="/agro_project/profile.php" class="nav-user-btn">
    <i class="fas fa-user"></i> <span class="hide-xs"><?php echo e($display_name); ?></span>
</a>
        <a href="/agro_project/logout.php" class="btn" style="background:rgba(248,113,113,.2);color:#fca5a5">
            <i class="fas fa-right-from-bracket"></i>
        </a>
        <?php else: ?>
        <a href="/agro_project/login.php" class="btn btn-outline-white" style="flex:1;justify-content:center">Login</a>
        <a href="/agro_project/register.php" class="btn btn-gold" style="flex:1;justify-content:center">Register</a>
        <?php endif; ?>
    </div>
</div>

<!-- DESKTOP NAV -->
<nav class="navbar">
    <div class="navbar-inner">
        <a href="/agro_project/index.php" class="navbar-brand">
            <i class="fas fa-leaf"></i>
            <span><?php echo e($site_name); ?></span>
        </a>
        <ul class="navbar-nav">
            <?php foreach ($nav_links as [$href, $label, $icon]): ?>
            <li>
                <a href="/agro_project/<?php echo $href; ?>" class="<?php echo $cur_page === $href ? 'active' : ''; ?>">
                    <?php echo $label; ?>
                </a>
            </li>
            <?php endforeach; ?>
            <?php if ($is_admin): ?>
            <li>
                <a href="/agro_project/admin/index.php" style="color:var(--gold-light)">
                    <i class="fas fa-shield-halved"></i> Admin
                </a>
            </li>
            <?php endif; ?>
        </ul>
        <div class="navbar-right">
            <a href="/agro_project/cart.php" class="nav-cart-btn">
                <i class="fas fa-cart-shopping"></i>
                <span class="hide-xs">Cart</span>
                <span class="badge" id="nav-cart-count"><?php echo $cart_count; ?></span>
            </a>
           <?php if ($is_logged): ?>
<a href="/agro_project/dashboard.php" class="btn btn-gold" style="flex:1;justify-content:center">
    <i class="fas fa-th-large"></i> Dashboard
</a>
<a href="/agro_project/profile.php" class="btn btn-outline-white" style="flex:1;justify-content:center">
    <i class="fas fa-user"></i> Profile
</a>
            <a href="/agro_project/logout.php" class="nav-user-btn" style="color:#f87171" title="Logout">
                <i class="fas fa-right-from-bracket"></i>
            </a>
            <?php else: ?>
            <a href="/agro_project/login.php"    class="nav-user-btn hide-mobile">Login</a>
            <a href="/agro_project/register.php" class="nav-user-btn hide-mobile" style="background:rgba(201,168,76,.25)">Register</a>
            <?php endif; ?>
            <div class="nav-hamburger" onclick="openMobileMenu()">
                <i class="fas fa-bars"></i>
            </div>
        </div>
    </div>
</nav>

<script>
function openMobileMenu() {
    document.getElementById('mobileMenu').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeMobileMenu() {
    document.getElementById('mobileMenu').classList.remove('open');
    document.body.style.overflow = '';
}
</script>