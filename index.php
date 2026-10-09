<?php
session_start();
require_once __DIR__ . '/config.php';
$page_title = 'Home';

if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    header('Location: /agro_project/admin/index.php'); 
    exit;
}

// All stats from DB
$total_products   = (int)mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM products WHERE active=1"))['c'];
$total_users      = (int)mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM users WHERE user_role='user'"))['c'];
$total_orders     = (int)mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM orders"))['c'];
$total_categories = (int)mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM categories"))['c'];

// Categories with live product counts from DB
$cats = mysqli_query($conn,
    "SELECT c.*, COUNT(p.id) prod_count
     FROM categories c
     LEFT JOIN products p ON p.category_id = c.id AND p.active = 1
     GROUP BY c.id ORDER BY c.sort_order");

// Featured products from DB
$featured = mysqli_query($conn,
    "SELECT p.*, c.name cat_name, c.slug cat_slug, c.icon cat_icon
     FROM products p
     JOIN categories c ON c.id = p.category_id
     WHERE p.featured = 1 AND p.active = 1
     ORDER BY p.id ASC LIMIT 8");

// Site text from settings table
$hero_title    = get_setting('hero_title');
$hero_subtitle = get_setting('hero_subtitle');
$site_name     = get_setting('site_name');
$tagline       = get_setting('site_tagline');

require_once __DIR__ . '/includes/header.php';

// Show access-denied notice
if (isset($_GET['error']) && $_GET['error'] === 'unauthorized'):
?>
<div class="alert-danger" style="background:rgba(239,68,68,0.1);border-left:4px solid #ef4444;color:#f87171;padding:14px 20px;text-align:center;margin:0">
    <i class="fas fa-shield-halved"></i>
    <strong>Access denied.</strong> You do not have permission to access that page.
</div>
<?php endif; ?>

<style>
/* ============================================================
   HOME PAGE - DARK THEME (mobile-optimized)
   ============================================================ */

.hero {
    background: linear-gradient(135deg, #0a0f1c 0%, #0c1122 100%);
    position: relative;
    overflow: hidden;
}
.hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse 70% 60% at 50% 0%, rgba(245, 158, 11, 0.08) 0%, transparent 70%);
    pointer-events: none;
}
.hero h1 { color: white; }
.hero h1 span {
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}
.hero p { color: #9ca3af; }

/* Hero stats — responsive grid */
.hero-stat .num {
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}

.hero-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
}
@media (max-width: 768px) {
    .hero-stats { grid-template-columns: repeat(2, 1fr); gap: 12px; }
}
@media (max-width: 400px) {
    .hero-stats { grid-template-columns: repeat(2, 1fr); }
}

/* Category grid — responsive */
.cat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 14px;
}
@media (max-width: 500px) {
    .cat-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
}

.cat-card {
    background: linear-gradient(135deg, #111827 0%, #0f172a 100%);
    border: 1px solid rgba(245, 158, 11, 0.15);
    transition: all 0.3s ease;
    padding: 20px 12px;
    text-align: center;
    text-decoration: none;
    display: block;
    border-radius: 14px;
    -webkit-tap-highlight-color: transparent;
}
.cat-card:active { transform: scale(0.97); }
.cat-card:hover {
    transform: translateY(-5px);
    border-color: rgba(245, 158, 11, 0.4);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
}
.cat-card i { color: #f59e0b; font-size: 2rem; margin-bottom: 10px; display: block; }
.cat-card .cat-name { color: #e5e7eb; font-weight: 600; font-size: 14px; margin-bottom: 4px; }
.cat-card .cat-count { color: #9ca3af; font-size: 11px; }

/* Featured products grid — responsive */
.featured-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 20px;
}
@media (max-width: 500px) {
    .featured-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
    .featured-grid .prod-img { height: 130px !important; }
    .featured-grid .prod-body { padding: 10px !important; }
    .featured-grid .prod-name { font-size: 12px !important; }
    .featured-grid .prod-price { font-size: 15px !important; }
}
@media (max-width: 360px) {
    .featured-grid { grid-template-columns: 1fr; }
}

/* Product card */
.prod-card {
    background: linear-gradient(135deg, #111827 0%, #0f172a 100%);
    border: 1px solid rgba(245, 158, 11, 0.15);
    border-radius: 16px;
    transition: all 0.3s ease;
    overflow: hidden;
    position: relative;
    display: flex;
    flex-direction: column;
    height: 100%;
}
.prod-card:active { transform: scale(0.98); }
.prod-card:hover {
    transform: translateY(-4px);
    border-color: rgba(245, 158, 11, 0.3);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
}
.prod-card.out-of-stock { opacity: 0.85; }

.prod-img {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    border-bottom: 1px solid rgba(245, 158, 11, 0.1);
    position: relative;
}
.prod-cat { color: #fbbf24; font-weight: 600; }
.prod-name { color: #e5e7eb; transition: color 0.2s; }
.prod-card:hover .prod-name { color: #f59e0b; }
.prod-price { color: #f59e0b; }

/* Sold-out overlay */
.sold-out-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 3;
    pointer-events: none;
}
.sold-out-badge {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: #fff;
    padding: 8px 18px;
    border-radius: 24px;
    font-weight: 800;
    font-size: 12px;
    letter-spacing: 1px;
    box-shadow: 0 4px 12px rgba(239,68,68,0.4);
    transform: rotate(-5deg);
}
.low-stock-ribbon {
    position: absolute;
    top: 8px;
    right: -28px;
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: #fff;
    padding: 3px 34px;
    font-size: 9px;
    font-weight: 700;
    transform: rotate(45deg);
    z-index: 4;
    box-shadow: 0 2px 6px rgba(245,158,11,0.4);
    white-space: nowrap;
}

/* Action buttons — bigger touch targets on mobile */
.prod-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 6px;
    margin-top: auto;
}
.btn-add-cart,
.btn-buy-now {
    min-height: 38px;
    padding: 8px 10px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    color: #fff;
    font-size: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    transition: all 0.2s ease;
    -webkit-tap-highlight-color: transparent;
}
.btn-add-cart {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}
.btn-add-cart:active { transform: scale(0.96); }
.btn-buy-now {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}
.btn-buy-now:active { transform: scale(0.96); }

.btn-view-only {
    grid-column: 1 / -1;
    min-height: 38px;
    padding: 8px;
    border-radius: 8px;
    background: transparent;
    border: 1px solid rgba(245,158,11,0.3);
    color: #fbbf24;
    font-size: 12px;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s;
}
.btn-view-only:active {
    background: rgba(245,158,11,0.1);
    transform: scale(0.97);
}

/* Badges */
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

/* Section titles */
.section-title {
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}
.section-subtitle { color: #9ca3af; }

/* Buttons */
.btn-gold {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    border: none;
    transition: all 0.3s ease;
}
.btn-gold:hover {
    background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(245, 158, 11, 0.3);
}
.btn-outline-white {
    border: 1px solid rgba(245, 158, 11, 0.4);
    background: transparent;
    transition: all 0.3s ease;
}
.btn-outline-white:hover {
    background: rgba(245, 158, 11, 0.1);
    border-color: #f59e0b;
    transform: translateY(-2px);
}
.btn-outline {
    border: 1px solid rgba(245, 158, 11, 0.3);
    color: #fbbf24;
    transition: all 0.3s ease;
}
.btn-outline:hover {
    background: rgba(245, 158, 11, 0.1);
    border-color: #f59e0b;
}

/* Hero buttons on mobile — full width */
@media (max-width: 500px) {
    .hero-btns {
        display: grid;
        grid-template-columns: 1fr;
        gap: 10px;
    }
    .hero-btns .btn {
        width: 100%;
        justify-content: center;
        padding: 14px;
        min-height: 48px;
    }
}

/* ============================================================
   MOBILE PAYMENT MODAL (reuse from products.php)
   ============================================================ */
.payment-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.8);
    z-index: 9999;
    align-items: flex-end;
    justify-content: center;
    backdrop-filter: blur(4px);
}
.payment-modal-overlay.active { display: flex; }

.payment-modal {
    background: linear-gradient(135deg, #111827 0%, #0f172a 100%);
    border: 1px solid rgba(245,158,11,0.2);
    border-radius: 20px 20px 0 0;
    width: 100%;
    max-width: 480px;
    max-height: 92vh;
    overflow-y: auto;
    animation: slideUp 0.3s ease;
    padding-bottom: env(safe-area-inset-bottom, 20px);
}
@keyframes slideUp {
    from { transform: translateY(100%); opacity: 0; }
    to   { transform: translateY(0);    opacity: 1; }
}
.payment-modal-header {
    padding: 18px 20px;
    border-bottom: 1px solid rgba(245,158,11,0.1);
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky;
    top: 0;
    background: inherit;
    z-index: 2;
}
.payment-modal-header h3 {
    color: #fbbf24;
    font-size: 16px;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}
.payment-modal-close {
    background: none;
    border: none;
    color: #64748b;
    font-size: 24px;
    cursor: pointer;
    padding: 0;
    line-height: 1;
}
.payment-modal-body { padding: 20px; }

.payment-product-summary {
    background: rgba(30,41,59,0.5);
    border: 1px solid rgba(245,158,11,0.1);
    border-radius: 12px;
    padding: 14px;
    margin-bottom: 18px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.payment-product-summary .name { color: #e5e7eb; font-weight: 600; font-size: 14px; }
.payment-product-summary .price { color: #f59e0b; font-weight: 700; font-size: 16px; }

.payment-methods { display: flex; flex-direction: column; gap: 10px; margin-bottom: 18px; }
.payment-method-option {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    background: #1e293b;
    border: 2px solid #334155;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
}
.payment-method-option:hover { border-color: rgba(245,158,11,0.4); }
.payment-method-option.selected { border-color: #f59e0b; background: rgba(245,158,11,0.08); }
.payment-method-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}
.payment-method-icon.mpesa    { background: rgba(16,185,129,0.15); color: #34d399; }
.payment-method-icon.airtel   { background: rgba(239,68,68,0.15);  color: #f87171; }
.payment-method-icon.tigo     { background: rgba(59,130,246,0.15); color: #60a5fa; }
.payment-method-icon.halopesa { background: rgba(168,85,247,0.15); color: #c084fc; }
.payment-method-info { flex: 1; }
.payment-method-info .label { color: #e5e7eb; font-weight: 600; font-size: 14px; display: block; }
.payment-method-info .sublabel { color: #64748b; font-size: 11px; display: block; margin-top: 2px; }
.payment-method-radio {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 2px solid #475569;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.payment-method-option.selected .payment-method-radio { border-color: #f59e0b; background: #f59e0b; }
.payment-method-option.selected .payment-method-radio::after {
    content: '';
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #0f172a;
}

.payment-input-group { margin-bottom: 16px; }
.payment-input-group label {
    display: block;
    color: #94a3b8;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 6px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.payment-input-group input {
    width: 100%;
    padding: 12px 14px;
    background: #1e293b;
    border: 1px solid #334155;
    border-radius: 10px;
    color: #e5e7eb;
    font-size: 15px;
}
.payment-input-group input:focus {
    outline: none;
    border-color: #f59e0b;
    box-shadow: 0 0 0 3px rgba(245,158,11,0.15);
}
.payment-input-group input::placeholder { color: #475569; }

.qty-selector {
    display: flex;
    align-items: center;
    gap: 12px;
    background: #1e293b;
    border: 1px solid #334155;
    border-radius: 10px;
    padding: 6px;
    width: fit-content;
}
.qty-selector button {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    border: none;
    background: #334155;
    color: #e5e7eb;
    font-size: 18px;
    font-weight: 700;
    cursor: pointer;
}
.qty-selector input {
    width: 50px;
    text-align: center;
    background: transparent;
    border: none;
    color: #e5e7eb;
    font-size: 16px;
    font-weight: 700;
}

.payment-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 16px;
    background: rgba(245,158,11,0.08);
    border: 1px solid rgba(245,158,11,0.2);
    border-radius: 12px;
    margin-bottom: 18px;
}
.payment-total .label { color: #94a3b8; font-size: 13px; font-weight: 600; }
.payment-total .amount { color: #fbbf24; font-size: 20px; font-weight: 800; }

.btn-pay-now {
    width: 100%;
    padding: 16px;
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    border: none;
    border-radius: 12px;
    color: white;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    position: sticky;
    bottom: 0;
}
.btn-pay-now:active { transform: scale(0.98); }

.payment-processing { display: none; text-align: center; padding: 20px; }
.payment-processing.active { display: block; }
.spinner {
    width: 48px;
    height: 48px;
    border: 4px solid rgba(245,158,11,0.2);
    border-top-color: #f59e0b;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin: 0 auto 16px;
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>

<!-- ============ HERO ============ -->
<section class="hero">
    <div class="hero-content">
        <h1>
            <?php if ($hero_title): ?>
                <?php echo e($hero_title); ?>
            <?php else: ?>
                <?php echo e($site_name); ?> — <span>Agricultural</span> Marketplace
            <?php endif; ?>
        </h1>
        <p><?php echo $hero_subtitle ? e($hero_subtitle) : e($tagline); ?></p>
        <div class="hero-btns">
            <a href="products.php" class="btn btn-gold"><i class="fas fa-store"></i> Browse Products</a>
            <a href="loan.php" class="btn btn-outline-white"><i class="fas fa-hand-holding-dollar"></i> Apply for Loan</a>
        </div>
    </div>
    <div class="hero-stats">
        <div class="hero-stat">
            <div class="num"><?php echo number_format($total_products); ?>+</div>
            <div class="lbl">Products</div>
        </div>
        <div class="hero-stat">
            <div class="num"><?php echo number_format($total_users); ?>+</div>
            <div class="lbl">Registered Farmers</div>
        </div>
        <div class="hero-stat">
            <div class="num"><?php echo number_format($total_orders); ?>+</div>
            <div class="lbl">Orders Processed</div>
        </div>
        <div class="hero-stat">
            <div class="num"><?php echo number_format($total_categories); ?></div>
            <div class="lbl">Categories</div>
        </div>
    </div>
</section>

<!-- ============ CATEGORIES ============ -->
<section class="section">
    <div class="container">
        <div class="section-title">Shop by category</div>
        <p class="section-subtitle">Find exactly what your farm needs</p>
        <?php if (mysqli_num_rows($cats) > 0): ?>
        <div class="cat-grid">
            <?php while ($cat = mysqli_fetch_assoc($cats)): ?>
            <a href="products.php?cat=<?php echo e($cat['slug']); ?>" class="cat-card">
                <i class="fas <?php echo e($cat['icon']); ?>"></i>
                <div class="cat-name"><?php echo e($cat['name']); ?></div>
                <div class="cat-count"><?php echo (int)$cat['prod_count']; ?> product<?php echo $cat['prod_count'] != 1 ? 's' : ''; ?></div>
            </a>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <p style="color:#9ca3af;text-align:center">No categories yet.</p>
        <?php endif; ?>
    </div>
</section>

<!-- ============ FEATURED PRODUCTS ============ -->
<section class="section" style="padding-top:0">
    <div class="container">
        <div class="flex-between" style="margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
            <div>
                <div class="section-title" style="text-align:left">Featured products</div>
                <p class="section-subtitle" style="text-align:left;margin-bottom:0">Best sellers this season</p>
            </div>
            <a href="products.php" class="btn btn-outline btn-sm">View all <i class="fas fa-arrow-right"></i></a>
        </div>

        <?php if (mysqli_num_rows($featured) > 0): ?>
        <div class="featured-grid">
            <?php while ($p = mysqli_fetch_assoc($featured)): 
                $stock_qty = (int)$p['stock_qty'];
                $is_out = $stock_qty <= 0;
                $is_low = $stock_qty > 0 && $stock_qty <= 10;
            ?>
            <div class="prod-card <?php echo $is_out ? 'out-of-stock' : ''; ?>">
                <?php if ($is_out): ?>
                    <div class="sold-out-overlay">
                        <span class="sold-out-badge"><i class="fas fa-ban"></i> SOLD OUT</span>
                    </div>
                <?php elseif ($is_low): ?>
                    <div class="low-stock-ribbon">
                        <span><i class="fas fa-exclamation-triangle"></i> Only <?php echo $stock_qty; ?> left</span>
                    </div>
                <?php endif; ?>

                <!-- Clickable image + name -->
                <a href="product_detail.php?id=<?php echo (int)$p['id']; ?>" style="text-decoration:none;display:block">
                    <div class="prod-img" style="height:160px;display:flex;align-items:center;justify-content:center;overflow:hidden">
                        <?php 
                        $img_path = '';
                        if (!empty($p['image'])) {
                            $full_path = __DIR__ . '/uploads/products/' . $p['image'];
                            if (file_exists($full_path)) {
                                $img_path = '/agro_project/uploads/products/' . $p['image'];
                            }
                        }
                        ?>
                        <?php if ($img_path): ?>
                            <img src="<?php echo $img_path; ?>" style="width:100%;height:100%;object-fit:cover;<?php echo $is_out ? 'filter:grayscale(0.7);opacity:0.6;' : ''; ?>" alt="<?php echo e($p['name']); ?>">
                        <?php else: ?>
                            <i class="fas <?php echo e($p['cat_icon']); ?>" style="font-size:3rem;color:#f59e0b;<?php echo $is_out ? 'opacity:0.4;' : ''; ?>"></i>
                        <?php endif; ?>
                    </div>
                </a>

                <div class="prod-body" style="padding:14px;display:flex;flex-direction:column;flex:1">
                    <div class="prod-cat" style="font-size:11px;margin-bottom:6px"><?php echo e($p['cat_name']); ?></div>
                    <a href="product_detail.php?id=<?php echo (int)$p['id']; ?>" style="text-decoration:none">
                        <div class="prod-name" style="font-size:14px;font-weight:600;margin-bottom:8px"><?php echo e($p['name']); ?></div>
                    </a>
                    <div class="prod-price" style="font-size:18px;font-weight:700;margin-bottom:10px">
                        <?php echo fmt((float)$p['price']); ?>
                        <span class="prod-unit" style="font-size:11px;color:#9ca3af">/ <?php echo e($p['unit']); ?></span>
                    </div>

                    <!-- Stock badge -->
                    <div style="margin-bottom:10px">
                        <?php if ($is_out): ?>
                            <span class="badge badge-danger" style="display:inline-block;padding:4px 10px;border-radius:20px;font-size:10px;font-weight:600">Out of Stock</span>
                        <?php elseif ($is_low): ?>
                            <span class="badge badge-warn" style="display:inline-block;padding:4px 10px;border-radius:20px;font-size:10px;font-weight:600">Low Stock (<?php echo $stock_qty; ?>)</span>
                        <?php else: ?>
                            <span class="badge badge-success" style="display:inline-block;padding:4px 10px;border-radius:20px;font-size:10px;font-weight:600">In Stock</span>
                        <?php endif; ?>
                    </div>

                    <!-- Actions -->
                    <div class="prod-actions">
                        <?php if (!$is_out): ?>
                            <button class="btn-add-cart"
                                    onclick="event.preventDefault();event.stopPropagation();addToCart(<?php echo (int)$p['id']; ?>,'<?php echo e(addslashes($p['name'])); ?>')">
                                <i class="fas fa-cart-plus"></i> Add
                            </button>
                            <button class="btn-buy-now"
                                    onclick="event.preventDefault();event.stopPropagation();buyNow(<?php echo (int)$p['id']; ?>,'<?php echo e(addslashes($p['name'])); ?>',<?php echo (float)$p['price']; ?>,'<?php echo e(addslashes($p['unit'])); ?>',<?php echo $stock_qty; ?>)">
                                <i class="fas fa-bolt"></i> Buy
                            </button>
                        <?php else: ?>
                            <a href="product_detail.php?id=<?php echo (int)$p['id']; ?>" class="btn-view-only">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <p style="color:#9ca3af;text-align:center">No featured products yet.</p>
        <?php endif; ?>
    </div>
</section>

<!-- ============ WHY US ============ -->
<?php
$why1_title = get_setting('why1_title'); $why1_text = get_setting('why1_text');
$why2_title = get_setting('why2_title'); $why2_text = get_setting('why2_text');
$why3_title = get_setting('why3_title'); $why3_text = get_setting('why3_text');
$why4_title = get_setting('why4_title'); $why4_text = get_setting('why4_text');

$features = [
    ['icon' => 'fa-shield-halved', 'title' => $why1_title, 'text' => $why1_text],
    ['icon' => 'fa-truck',         'title' => $why2_title, 'text' => $why2_text],
    ['icon' => 'fa-hand-holding-dollar', 'title' => $why3_title, 'text' => $why3_text],
    ['icon' => 'fa-headset',       'title' => $why4_title, 'text' => $why4_text],
];
$has_features = array_filter($features, fn($f) => $f['title'] !== '' || $f['text'] !== '');
?>

<?php if ($has_features): ?>
<section class="section" style="background:linear-gradient(135deg, #0f172a 0%, #0a0f1c 100%);padding:48px 0">
    <div class="container">
        <div class="section-title text-center" style="color:#fbbf24;margin-bottom:6px">
            <?php echo e(get_setting('why_section_title') ?: 'Why choose ' . get_setting('site_name')); ?>
        </div>
        <p class="section-subtitle text-center" style="color:#9ca3af">
            <?php echo e(get_setting('why_section_subtitle')); ?>
        </p>
        <div class="grid-4" style="margin-top:24px;display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:24px">
            <?php foreach ($features as $f): if (!$f['title'] && !$f['text']) continue; ?>
            <div style="text-align:center;color:#fff;padding:20px">
                <i class="fas <?php echo e($f['icon']); ?>" style="font-size:2rem;color:#f59e0b;margin-bottom:12px;display:block"></i>
                <h4 style="color:#fbbf24;margin-bottom:8px"><?php echo e($f['title']); ?></h4>
                <p style="font-size:13px;color:#9ca3af"><?php echo e($f['text']); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ============ PAYMENT MODAL ============ -->
<div class="payment-modal-overlay" id="paymentModal">
    <div class="payment-modal">
        <div class="payment-modal-header">
            <h3><i class="fas fa-lock"></i> Secure Checkout</h3>
            <button class="payment-modal-close" onclick="closePaymentModal()">&times;</button>
        </div>

        <div class="payment-modal-body">
            <div class="payment-product-summary">
                <span class="name" id="payProductName">Product</span>
                <span class="price" id="payProductPrice">TSh 0</span>
            </div>

            <div class="payment-input-group">
                <label>Quantity</label>
                <div class="qty-selector">
                    <button type="button" onclick="changeQty(-1)">−</button>
                    <input type="number" id="payQty" value="1" min="1" max="99" onchange="updatePaymentTotal()">
                    <button type="button" onclick="changeQty(1)">+</button>
                </div>
                <span id="payStockHint" style="font-size:11px;color:#64748b;display:block;margin-top:6px"></span>
            </div>

            <div class="payment-input-group">
                <label>Payment Method</label>
                <div class="payment-methods">
                    <label class="payment-method-option selected" onclick="selectPaymentMethod(this, 'mpesa')">
                        <div class="payment-method-icon mpesa"><i class="fas fa-mobile-alt"></i></div>
                        <div class="payment-method-info">
                            <span class="label">M-Pesa</span>
                            <span class="sublabel">Vodacom mobile money</span>
                        </div>
                        <div class="payment-method-radio"></div>
                    </label>
                    <label class="payment-method-option" onclick="selectPaymentMethod(this, 'airtel')">
                        <div class="payment-method-icon airtel"><i class="fas fa-mobile-alt"></i></div>
                        <div class="payment-method-info">
                            <span class="label">Airtel Money</span>
                            <span class="sublabel">Airtel mobile money</span>
                        </div>
                        <div class="payment-method-radio"></div>
                    </label>
                    <label class="payment-method-option" onclick="selectPaymentMethod(this, 'tigo')">
                        <div class="payment-method-icon tigo"><i class="fas fa-mobile-alt"></i></div>
                        <div class="payment-method-info">
                            <span class="label">Mixx by Yas (Tigo)</span>
                            <span class="sublabel">Tigo Pesa mobile money</span>
                        </div>
                        <div class="payment-method-radio"></div>
                    </label>
                    <label class="payment-method-option" onclick="selectPaymentMethod(this, 'halopesa')">
                        <div class="payment-method-icon halopesa"><i class="fas fa-mobile-alt"></i></div>
                        <div class="payment-method-info">
                            <span class="label">HaloPesa</span>
                            <span class="sublabel">Halotel mobile money</span>
                        </div>
                        <div class="payment-method-radio"></div>
                    </label>
                </div>
            </div>

            <div class="payment-input-group">
                <label>Phone Number</label>
                <input type="tel" id="payPhone" placeholder="+255 7XX XXX XXX" autocomplete="tel">
            </div>

            <div class="payment-input-group">
                <label>Delivery Region</label>
                <input type="text" id="payRegion" placeholder="e.g. Iringa, Dodoma, Kilimanjaro">
            </div>

            <div class="payment-input-group">
                <label>Delivery Notes (Optional)</label>
                <input type="text" id="payNotes" placeholder="Landmark, street, etc.">
            </div>

            <div class="payment-total">
                <span class="label">Total to pay</span>
                <span class="amount" id="payTotal">TSh 0</span>
            </div>

            <button class="btn-pay-now" id="payNowBtn" onclick="processPayment()">
                <i class="fas fa-lock"></i> Pay Now
            </button>

            <div class="payment-processing" id="paymentProcessing">
                <div class="spinner"></div>
                <p style="color:#fbbf24;font-weight:600" id="processingMsg">Processing payment...</p>
                <p style="color:#64748b;font-size:12px;margin-top:8px" id="processingSub">Check your phone for the payment prompt</p>
            </div>
        </div>
    </div>
</div>

<script>
/* ===== CART ===== */
function addToCart(id, name) {
    fetch('/agro_project/cart_action.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=add&product_id=' + id + '&qty=1'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const cartCount = document.getElementById('nav-cart-count');
            if (cartCount) cartCount.textContent = data.cart_count;
            showToast('Added: ' + name);
        } else {
            showToast(data.message || 'Could not add product.', 'error');
        }
    })
    .catch(() => showToast('Network error', 'error'));
}

function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    if (toast) {
        toast.innerHTML = '<i class="fas ' + (type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle') + '"></i><span>' + message + '</span>';
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3000);
    }
}

/* ===== PAYMENT MODAL ===== */
let currentPayment = {
    productId: null,
    productName: '',
    productPrice: 0,
    productUnit: 'kg',
    maxStock: 0,
    qty: 1,
    method: 'mpesa'
};

function buyNow(productId, productName, productPrice, productUnit, maxStock) {
    currentPayment = {
        productId: productId,
        productName: productName,
        productPrice: parseFloat(productPrice),
        productUnit: productUnit || 'kg',
        maxStock: parseInt(maxStock) || 1,
        qty: 1,
        method: 'mpesa'
    };

    document.getElementById('payProductName').textContent = productName;
    document.getElementById('payProductPrice').textContent = formatMoney(currentPayment.productPrice);
    document.getElementById('payQty').value = 1;
    document.getElementById('payQty').max = currentPayment.maxStock;
    document.getElementById('payStockHint').textContent = 'Available: ' + currentPayment.maxStock + ' ' + currentPayment.productUnit;

    document.querySelectorAll('.payment-method-option').forEach((opt, i) => {
        opt.classList.toggle('selected', i === 0);
    });
    document.getElementById('paymentProcessing').classList.remove('active');
    document.getElementById('payNowBtn').style.display = 'flex';

    updatePaymentTotal();
    document.getElementById('paymentModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closePaymentModal() {
    document.getElementById('paymentModal').classList.remove('active');
    document.body.style.overflow = '';
    document.getElementById('paymentProcessing').classList.remove('active');
    document.getElementById('payNowBtn').style.display = 'flex';
}

function formatMoney(amount) {
    return 'TSh ' + Number(amount).toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0});
}

function updatePaymentTotal() {
    const qty = Math.max(1, Math.min(currentPayment.maxStock, parseInt(document.getElementById('payQty').value) || 1));
    document.getElementById('payQty').value = qty;
    currentPayment.qty = qty;
    document.getElementById('payTotal').textContent = formatMoney(currentPayment.productPrice * qty);
}

function changeQty(delta) {
    const input = document.getElementById('payQty');
    let val = parseInt(input.value) || 1;
    val = Math.max(1, Math.min(currentPayment.maxStock, val + delta));
    input.value = val;
    updatePaymentTotal();
}

function selectPaymentMethod(el, method) {
    document.querySelectorAll('.payment-method-option').forEach(opt => opt.classList.remove('selected'));
    el.classList.add('selected');
    currentPayment.method = method;
}

document.getElementById('payPhone')?.addEventListener('input', function(e) {
    let val = e.target.value.replace(/\D/g, '');
    if (val.startsWith('255')) val = '+' + val;
    else if (val.startsWith('0')) val = '+255' + val.substring(1);
    else if (val.length > 0 && !val.startsWith('+')) val = '+255' + val;
    e.target.value = val;
});

function processPayment() {
    const region = document.getElementById('payRegion').value.trim();
    const notes  = document.getElementById('payNotes').value.trim();
    const phone  = document.getElementById('payPhone').value.trim();

    if (!phone || phone.length < 10) {
        showToast('Please enter a valid phone number', 'error');
        return;
    }
    if (!region) {
        showToast('Please enter your delivery region', 'error');
        return;
    }

    document.getElementById('payNowBtn').style.display = 'none';
    document.getElementById('paymentProcessing').classList.add('active');
    document.getElementById('processingMsg').textContent = 'Sending payment request...';
    document.getElementById('processingSub').textContent = 'Please wait';

    const payload = {
        action: 'mobile_payment',
        product_id: currentPayment.productId,
        quantity: currentPayment.qty,
        method: currentPayment.method,
        phone: phone,
        region: region,
        notes: notes,
        amount: currentPayment.productPrice * currentPayment.qty
    };

    fetch('/agro_project/payment_process.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(d => {
        if (!d.success) {
            document.getElementById('paymentProcessing').classList.remove('active');
            document.getElementById('payNowBtn').style.display = 'flex';
            showToast(d.message || 'Payment failed', 'error');
            return;
        }

        document.getElementById('processingMsg').textContent = 'Waiting for payment...';
        document.getElementById('processingSub').textContent = 'Enter your PIN on your phone to complete payment';

        pollPaymentStatus(d.order_ref);
    })
    .catch(() => {
        document.getElementById('paymentProcessing').classList.remove('active');
        document.getElementById('payNowBtn').style.display = 'flex';
        showToast('Network error. Please try again.', 'error');
    });
}

function pollPaymentStatus(orderRef) {
    let attempts = 0;
    const maxAttempts = 40;

    const interval = setInterval(() => {
        attempts++;
        fetch('/agro_project/payment_status.php?ref=' + encodeURIComponent(orderRef))
            .then(r => r.json())
            .then(d => {
                if (d.status === 'paid') {
                    clearInterval(interval);
                    document.getElementById('processingMsg').textContent = 'Payment confirmed!';
                    document.getElementById('processingSub').textContent = 'Redirecting...';
                    setTimeout(() => {
                        closePaymentModal();
                        window.location.href = '/agro_project/order_success.php?ref=' + encodeURIComponent(orderRef);
                    }, 1200);
                } else if (d.status === 'failed') {
                    clearInterval(interval);
                    document.getElementById('paymentProcessing').classList.remove('active');
                    document.getElementById('payNowBtn').style.display = 'flex';
                    showToast(d.message || 'Payment failed', 'error');
                } else if (attempts >= maxAttempts) {
                    clearInterval(interval);
                    document.getElementById('paymentProcessing').classList.remove('active');
                    document.getElementById('payNowBtn').style.display = 'flex';
                    showToast('Payment timeout. Please check your phone.', 'error');
                }
            })
            .catch(() => {});
    }, 3000);
}

document.getElementById('paymentModal')?.addEventListener('click', function(e) {
    if (e.target === this) closePaymentModal();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closePaymentModal();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>