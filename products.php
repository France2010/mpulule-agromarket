<?php
session_start();
require_once __DIR__ . '/config.php';
$page_title = 'Products';

$cat_filter = isset($_GET['cat']) ? mysqli_real_escape_string($conn, $_GET['cat']) : '';
$search     = isset($_GET['q'])   ? mysqli_real_escape_string($conn, trim($_GET['q'])) : '';
$page       = max(1, (int)($_GET['page'] ?? 1));
$per_page   = 12;
$offset     = ($page - 1) * $per_page;

$where = "p.active=1";
if ($cat_filter) $where .= " AND c.slug='$cat_filter'";
if ($search)     $where .= " AND (p.name LIKE '%$search%' OR p.description LIKE '%$search%')";

$total_rows  = (int)mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM products p JOIN categories c ON c.id=p.category_id WHERE $where"))['c'];
$total_pages = max(1, (int)ceil($total_rows / $per_page));

$products   = mysqli_query($conn,"SELECT p.*,c.name cat_name,c.slug cat_slug,c.icon cat_icon FROM products p JOIN categories c ON c.id=p.category_id WHERE $where ORDER BY p.featured DESC,p.id ASC LIMIT $per_page OFFSET $offset");
$categories = mysqli_query($conn,"SELECT * FROM categories ORDER BY sort_order");

require_once __DIR__ . '/includes/header.php';
?>

<style>
.page-header { background: linear-gradient(135deg, #0f172a 0%, #0a0f1c 100%); border-bottom: 1px solid rgba(245, 158, 11, 0.15); }
.page-header h1 { background: linear-gradient(135deg, #fbbf24, #f59e0b); -webkit-background-clip: text; background-clip: text; color: transparent; }
.page-header p { color: #9ca3af; }
.breadcrumb a { color: #fbbf24; }
.breadcrumb a:hover { color: #f59e0b; }
.breadcrumb span { color: #64748b; }

.products-sidebar { background: linear-gradient(135deg, #111827 0%, #0f172a 100%); border: 1px solid rgba(245, 158, 11, 0.15); border-radius: 16px; }
.products-sidebar .sidebar-title { color: #fbbf24; font-weight: 700; }
.sidebar-item { color: #94a3b8; transition: all 0.25s ease; }
.sidebar-item:hover { background: rgba(245, 158, 11, 0.1); color: #fbbf24; padding-left: 20px; }
.sidebar-item.active { background: linear-gradient(90deg, rgba(245, 158, 11, 0.15) 0%, transparent 100%); color: #f59e0b; border-left: 3px solid #f59e0b; }

.prod-card-link { text-decoration: none; display: block; }
.prod-card { background: linear-gradient(135deg, #111827 0%, #0f172a 100%); border: 1px solid rgba(245, 158, 11, 0.15); border-radius: 16px; transition: all 0.3s ease; overflow: hidden; cursor: pointer; }
.prod-card-link:hover .prod-card { transform: translateY(-4px); border-color: rgba(245, 158, 11, 0.3); box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3); }
.prod-card-link:hover .prod-name { color: #f59e0b; }
.prod-img { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border-bottom: 1px solid rgba(245, 158, 11, 0.1); }
.prod-cat { color: #fbbf24; font-weight: 600; }
.prod-name { color: #e5e7eb; transition: color 0.2s; }
.prod-price { color: #f59e0b; }
.btn-add-cart { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); transition: all 0.3s ease; position: relative; z-index: 10; }
.btn-add-cart:hover { background: linear-gradient(135deg, #d97706 0%, #b45309 100%); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3); }

/* STOCK STATUS */
.prod-card.out-of-stock { opacity: 0.85; }
.sold-out-overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 5; pointer-events: none; }
.sold-out-badge { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; padding: 10px 24px; border-radius: 30px; font-weight: 800; font-size: 14px; letter-spacing: 1px; box-shadow: 0 4px 15px rgba(239,68,68,0.4); transform: rotate(-5deg); }
.low-stock-ribbon { position: absolute; top: 10px; right: -30px; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; padding: 4px 40px; font-size: 10px; font-weight: 700; transform: rotate(45deg); z-index: 6; box-shadow: 0 2px 8px rgba(245,158,11,0.4); white-space: nowrap; }
.stock-status-bar { padding: 0; }
.stock-indicator { display: flex; align-items: center; gap: 6px; padding: 6px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; }
.stock-indicator.in  { background: rgba(16,185,129,0.12); color: #34d399; border: 1px solid rgba(16,185,129,0.25); }
.stock-indicator.low { background: rgba(245,158,11,0.12); color: #fbbf24; border: 1px solid rgba(245,158,11,0.25); }
.stock-indicator.out { background: rgba(239,68,68,0.12); color: #f87171; border: 1px solid rgba(239,68,68,0.25); }
.btn-buy-now { background: linear-gradient(135deg, #10b981 0%, #059669 100%); transition: all 0.3s ease; }
.btn-buy-now:hover { background: linear-gradient(135deg, #059669 0%, #047857 100%); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(16,185,129,0.3); }
.btn-notify { transition: all 0.3s ease; }
.btn-notify:hover { background: rgba(245,158,11,0.1) !important; }

/* PAYMENT MODAL */
.payment-modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 9999; align-items: flex-end; justify-content: center; backdrop-filter: blur(4px); }
.payment-modal-overlay.active { display: flex; }
.payment-modal { background: linear-gradient(135deg, #111827 0%, #0f172a 100%); border: 1px solid rgba(245,158,11,0.2); border-radius: 20px 20px 0 0; width: 100%; max-width: 480px; max-height: 92vh; overflow-y: auto; animation: slideUp 0.3s ease; padding-bottom: env(safe-area-inset-bottom, 20px); }
@keyframes slideUp { from { transform: translateY(100%); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
.payment-modal-header { padding: 18px 20px; border-bottom: 1px solid rgba(245,158,11,0.1); display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; background: inherit; z-index: 2; }
.payment-modal-header h3 { color: #fbbf24; font-size: 16px; margin: 0; display: flex; align-items: center; gap: 8px; }
.payment-modal-close { background: none; border: none; color: #64748b; font-size: 24px; cursor: pointer; padding: 0; line-height: 1; }
.payment-modal-body { padding: 20px; }
.payment-product-summary { background: rgba(30,41,59,0.5); border: 1px solid rgba(245,158,11,0.1); border-radius: 12px; padding: 14px; margin-bottom: 18px; display: flex; justify-content: space-between; align-items: center; }
.payment-product-summary .name { color: #e5e7eb; font-weight: 600; font-size: 14px; }
.payment-product-summary .price { color: #f59e0b; font-weight: 700; font-size: 16px; }
.payment-methods { display: flex; flex-direction: column; gap: 10px; margin-bottom: 18px; }
.payment-method-option { display: flex; align-items: center; gap: 12px; padding: 14px 16px; background: #1e293b; border: 2px solid #334155; border-radius: 12px; cursor: pointer; transition: all 0.2s ease; }
.payment-method-option:hover { border-color: rgba(245,158,11,0.4); }
.payment-method-option.selected { border-color: #f59e0b; background: rgba(245,158,11,0.08); }
.payment-method-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
.payment-method-icon.mpesa    { background: rgba(16,185,129,0.15); color: #34d399; }
.payment-method-icon.airtel   { background: rgba(239,68,68,0.15);  color: #f87171; }
.payment-method-icon.tigo     { background: rgba(59,130,246,0.15); color: #60a5fa; }
.payment-method-icon.halopesa { background: rgba(168,85,247,0.15); color: #c084fc; }
.payment-method-icon.card     { background: rgba(245,158,11,0.15); color: #fbbf24; }
.payment-method-info { flex: 1; }
.payment-method-info .label { color: #e5e7eb; font-weight: 600; font-size: 14px; display: block; }
.payment-method-info .sublabel { color: #64748b; font-size: 11px; display: block; margin-top: 2px; }
.payment-method-radio { width: 20px; height: 20px; border-radius: 50%; border: 2px solid #475569; display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: all 0.2s; }
.payment-method-option.selected .payment-method-radio { border-color: #f59e0b; background: #f59e0b; }
.payment-method-option.selected .payment-method-radio::after { content: ''; width: 8px; height: 8px; border-radius: 50%; background: #0f172a; }
.payment-input-group { margin-bottom: 16px; }
.payment-input-group label { display: block; color: #94a3b8; font-size: 12px; font-weight: 600; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
.payment-input-group input { width: 100%; padding: 12px 14px; background: #1e293b; border: 1px solid #334155; border-radius: 10px; color: #e5e7eb; font-size: 15px; transition: border-color 0.2s; }
.payment-input-group input:focus { outline: none; border-color: #f59e0b; box-shadow: 0 0 0 3px rgba(245,158,11,0.15); }
.payment-input-group input::placeholder { color: #475569; }
.qty-selector { display: flex; align-items: center; gap: 12px; background: #1e293b; border: 1px solid #334155; border-radius: 10px; padding: 6px; width: fit-content; }
.qty-selector button { width: 36px; height: 36px; border-radius: 8px; border: none; background: #334155; color: #e5e7eb; font-size: 18px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.2s; }
.qty-selector button:hover { background: #475569; }
.qty-selector input { width: 50px; text-align: center; background: transparent; border: none; color: #e5e7eb; font-size: 16px; font-weight: 700; }
.payment-total { display: flex; justify-content: space-between; align-items: center; padding: 14px 16px; background: rgba(245,158,11,0.08); border: 1px solid rgba(245,158,11,0.2); border-radius: 12px; margin-bottom: 18px; }
.payment-total .label { color: #94a3b8; font-size: 13px; font-weight: 600; }
.payment-total .amount { color: #fbbf24; font-size: 20px; font-weight: 800; }
.btn-pay-now { width: 100%; padding: 16px; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border: none; border-radius: 12px; color: white; font-size: 16px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.3s ease; position: sticky; bottom: 0; }
.btn-pay-now:hover { background: linear-gradient(135deg, #d97706 0%, #b45309 100%); box-shadow: 0 4px 15px rgba(245,158,11,0.4); }
.btn-pay-now:disabled { opacity: 0.5; cursor: not-allowed; }
.payment-processing { display: none; text-align: center; padding: 20px; }
.payment-processing.active { display: block; }
.spinner { width: 48px; height: 48px; border: 4px solid rgba(245,158,11,0.2); border-top-color: #f59e0b; border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto 16px; }
@keyframes spin { to { transform: rotate(360deg); } }

@media (max-width: 768px) {
    .payment-modal { border-radius: 20px 20px 0 0; max-height: 95vh; }
    .payment-modal-body { padding: 16px; }
    .sold-out-badge { font-size: 12px; padding: 8px 18px; }
}
</style>

<div class="page-header">
    <div class="container">
        <div class="breadcrumb"><a href="/agro_project/index.php">Home</a><span>›</span> Products</div>
        <h1>Our Products</h1>
        <p>Quality agricultural inputs for every farmer and agribusiness</p>
    </div>
</div>

<div class="container" style="margin-top:16px">
    <button class="btn btn-outline filter-toggle-btn" onclick="toggleSidebar()" style="display:none;margin-bottom:0;background:linear-gradient(135deg,#1e293b 0%,#0f172a 100%);border:1px solid rgba(245,158,11,0.3);color:#fbbf24">
        <i class="fas fa-filter"></i> Filter / Search
    </button>
</div>

<section class="section" style="padding-top:16px">
    <div class="container">
        <div class="products-layout" style="display:grid;grid-template-columns:260px 1fr;gap:24px;align-items:start">

            <div class="products-sidebar card" style="overflow:hidden;position:sticky;top:calc(var(--nav-h) + 10px)" id="productSidebar">
                <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 18px;background:linear-gradient(135deg, #1e293b 0%, #0f172a 100%);border-bottom:1px solid rgba(245,158,11,0.15)">
                    <span class="sidebar-title" style="padding:0;border:none;background:none"><i class="fas fa-filter"></i> Filter Products</span>
                    <span onclick="toggleSidebar()" style="display:none;cursor:pointer;font-size:20px;color:#9ca3af" id="sidebarCloseBtn">&times;</span>
                </div>
                <a href="/agro_project/products.php<?php echo $search?"?q=".urlencode(stripslashes($search)):""; ?>"
                   class="sidebar-item <?php echo !$cat_filter?'active':''; ?>">
                    <i class="fas fa-th-large" style="width:18px;color:#f59e0b"></i> All categories
                </a>
                <?php mysqli_data_seek($categories,0); while($cat=mysqli_fetch_assoc($categories)): ?>
                <a href="/agro_project/products.php?cat=<?php echo e($cat['slug']); ?><?php echo $search?"&q=".urlencode(stripslashes($search)):""; ?>"
                   class="sidebar-item <?php echo $cat_filter===$cat['slug']?'active':''; ?>">
                    <i class="fas <?php echo e($cat['icon']); ?>" style="width:18px;color:#f59e0b"></i> <?php echo e($cat['name']); ?>
                </a>
                <?php endwhile; ?>
                <div style="padding:16px;border-top:1px solid rgba(245,158,11,0.1)">
                    <form method="GET" action="/agro_project/products.php">
                        <?php if($cat_filter): ?><input type="hidden" name="cat" value="<?php echo e($cat_filter); ?>"><?php endif; ?>
                        <div class="input-icon-wrap">
                            <i class="fas fa-search icon"></i>
                            <input type="text" name="q" class="form-control" placeholder="Search products..." value="<?php echo e(stripslashes($search)); ?>">
                        </div>
                        <button class="btn btn-gold btn-block" style="margin-top:10px;font-size:13px;padding:10px">Search</button>
                        <?php if($search || $cat_filter): ?>
                        <a href="/agro_project/products.php" class="btn btn-outline btn-block" style="margin-top:8px;font-size:12px;padding:8px">Clear filters</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <div>
                <div class="flex-between" style="margin-bottom:16px;display:flex;justify-content:space-between;align-items:center">
                    <p style="font-size:13px;color:#9ca3af">
                        <strong style="color:#fbbf24"><?php echo $total_rows; ?></strong> product<?php echo $total_rows!=1?'s':''; ?>
                        <?php if($search): ?> for "<strong style="color:#f59e0b"><?php echo e(stripslashes($search)); ?></strong>"<?php endif; ?>
                        <?php if($cat_filter): ?> in <strong style="color:#f59e0b"><?php echo e($cat_filter); ?></strong><?php endif; ?>
                    </p>
                    <span style="font-size:12px;color:#64748b">Page <?php echo $page; ?>/<?php echo $total_pages; ?></span>
                </div>

                <?php if(mysqli_num_rows($products)===0): ?>
                <div style="text-align:center;padding:60px 20px;color:#9ca3af">
                    <i class="fas fa-box-open" style="font-size:3rem;margin-bottom:16px;color:#f59e0b;display:block"></i>
                    <h3 style="color:#e5e7eb">No products found</h3>
                    <p style="margin-top:8px">Try a different filter or search term</p>
                    <a href="/agro_project/products.php" class="btn btn-gold" style="margin-top:16px">View all products</a>
                </div>
                <?php else: ?>
                <div class="grid-auto" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:20px">
                    <?php while($p=mysqli_fetch_assoc($products)): 
                        $stock_qty = (int)$p['stock_qty'];
                        $is_out_of_stock = $stock_qty <= 0;
                        $is_low_stock = $stock_qty > 0 && $stock_qty <= 10;
                    ?>
                    <div class="prod-card-link" style="position:relative">
                        <div class="prod-card <?php echo $is_out_of_stock ? 'out-of-stock' : ''; ?>">
                            <?php if($is_out_of_stock): ?>
                            <div class="sold-out-overlay">
                                <span class="sold-out-badge"><i class="fas fa-ban"></i> SOLD OUT</span>
                            </div>
                            <?php elseif($is_low_stock): ?>
                            <div class="low-stock-ribbon">
                                <span><i class="fas fa-exclamation-triangle"></i> Only <?php echo $stock_qty; ?> left</span>
                            </div>
                            <?php endif; ?>

                            <a href="/agro_project/product_detail.php?id=<?php echo $p['id']; ?>" style="text-decoration:none;display:block">
                                <div class="prod-img" style="height:160px;display:flex;align-items:center;justify-content:center;overflow:hidden;background:#1e293b;position:relative">
                                    <?php 
                                    $img_file = $p['image'] ?? '';
                                    $img_path = '';
                                    if(!empty($img_file)) {
                                        $full_path = __DIR__ . '/uploads/products/' . $img_file;
                                        if(file_exists($full_path)) {
                                            $img_path = '/agro_project/uploads/products/' . $img_file;
                                        }
                                    }
                                    ?>
                                    <?php if($img_path): ?>
                                        <img src="<?php echo $img_path; ?>" 
                                             style="width:100%;height:100%;object-fit:cover;<?php echo $is_out_of_stock ? 'filter:grayscale(0.7);opacity:0.6' : ''; ?>" 
                                             alt="<?php echo htmlspecialchars($p['name']); ?>">
                                    <?php else: ?>
                                        <i class="fas <?php echo e($p['cat_icon']); ?>" style="font-size:3rem;color:#f59e0b;<?php echo $is_out_of_stock ? 'opacity:0.4' : ''; ?>"></i>
                                    <?php endif; ?>
                                </div>
                            </a>

                            <div class="prod-body" style="padding:14px">
                                <div class="prod-cat" style="font-size:11px;margin-bottom:6px"><?php echo e($p['cat_name']); ?></div>
                                <a href="/agro_project/product_detail.php?id=<?php echo $p['id']; ?>" style="text-decoration:none">
                                    <div class="prod-name" style="font-size:14px;font-weight:600;margin-bottom:8px"><?php echo e($p['name']); ?></div>
                                </a>
                                <div class="prod-price" style="font-size:18px;font-weight:700;margin-bottom:12px">
                                    <?php echo fmt((float)$p['price']); ?>
                                    <span class="prod-unit" style="font-size:11px;color:#9ca3af"> / <?php echo e($p['unit']); ?></span>
                                </div>

                                <div class="stock-status-bar" style="margin-bottom:10px">
                                    <?php if($is_out_of_stock): ?>
                                        <div class="stock-indicator out"><i class="fas fa-times-circle"></i> Out of Stock</div>
                                    <?php elseif($is_low_stock): ?>
                                        <div class="stock-indicator low"><i class="fas fa-exclamation-circle"></i> Low Stock (<?php echo $stock_qty; ?> <?php echo e($p['unit']); ?>)</div>
                                    <?php else: ?>
                                        <div class="stock-indicator in"><i class="fas fa-check-circle"></i> In Stock (<?php echo $stock_qty; ?> <?php echo e($p['unit']); ?>)</div>
                                    <?php endif; ?>
                                </div>

                                <div class="prod-footer" style="display:flex;flex-direction:column;gap:8px">
                                    <?php if(!$is_out_of_stock): ?>
                                        <div style="display:flex;gap:8px">
                                            <button class="btn-add-cart" 
                                                    onclick="addToCart(<?php echo (int)$p['id']; ?>,'<?php echo e(addslashes($p['name'])); ?>')" 
                                                    style="flex:1;padding:10px;border:none;border-radius:8px;font-weight:600;cursor:pointer;color:white;font-size:13px">
                                                <i class="fas fa-cart-plus"></i> Add
                                            </button>
                                            <button class="btn-buy-now" 
                                                    onclick="buyNow(<?php echo (int)$p['id']; ?>,'<?php echo e(addslashes($p['name'])); ?>',<?php echo (float)$p['price']; ?>,'<?php echo e(addslashes($p['unit'])); ?>',<?php echo $stock_qty; ?>)" 
                                                    style="flex:1;padding:10px;border:none;border-radius:8px;font-weight:600;cursor:pointer;color:white;font-size:13px">
                                                <i class="fas fa-bolt"></i> Buy Now
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <button class="btn-notify" 
                                                onclick="notifyMe(<?php echo (int)$p['id']; ?>,'<?php echo e(addslashes($p['name'])); ?>')" 
                                                style="width:100%;padding:10px;border:1px solid #f59e0b;border-radius:8px;background:transparent;color:#f59e0b;font-weight:600;cursor:pointer;font-size:13px">
                                            <i class="fas fa-bell"></i> Notify When Available
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>

                <?php if($total_pages>1): ?>
                <div class="pagination" style="display:flex;gap:6px;justify-content:center;margin-top:32px;flex-wrap:wrap">
                    <?php if($page>1): ?><a href="?<?php echo http_build_query(array_merge($_GET,['page'=>$page-1])); ?>" style="padding:6px 12px;border-radius:8px;background:#1e293b;border:1px solid #334155;color:#94a3b8;text-decoration:none"><i class="fas fa-chevron-left"></i></a><?php endif; ?>
                    <?php for($i=max(1,$page-2);$i<=min($total_pages,$page+2);$i++): ?>
                    <?php if($i==$page): ?><span style="padding:6px 12px;border-radius:8px;background:linear-gradient(135deg, #f59e0b 0%, #d97706 100%);color:white;font-weight:700"><?php echo $i; ?></span>
                    <?php else: ?><a href="?<?php echo http_build_query(array_merge($_GET,['page'=>$i])); ?>" style="padding:6px 12px;border-radius:8px;background:#1e293b;border:1px solid #334155;color:#94a3b8;text-decoration:none"><?php echo $i; ?></a><?php endif; ?>
                    <?php endfor; ?>
                    <?php if($page<$total_pages): ?><a href="?<?php echo http_build_query(array_merge($_GET,['page'=>$page+1])); ?>" style="padding:6px 12px;border-radius:8px;background:#1e293b;border:1px solid #334155;color:#94a3b8;text-decoration:none"><i class="fas fa-chevron-right"></i></a><?php endif; ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- PAYMENT MODAL -->
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

            <div class="payment-input-group" id="phoneGroup">
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
    .then(d => {
        if (d.success) {
            const el = document.getElementById('nav-cart-count');
            if (el) el.textContent = d.cart_count;
            showToast('Added: ' + name);
        } else {
            showToast(d.message || 'Error', 'error');
        }
    })
    .catch(() => showToast('Network error', 'error'));
}

function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    if (toast) {
        toast.innerHTML = '<i class="fas ' + (type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle') + '"></i><span>' + message + '</span>';
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3500);
    } else {
        alert(message);
    }
}

function toggleSidebar(){
    const sb = document.getElementById('productSidebar');
    const closeBtn = document.getElementById('sidebarCloseBtn');
    sb.classList.toggle('open');
    if (closeBtn) closeBtn.style.display = sb.classList.contains('open') ? 'block' : 'none';
    document.body.style.overflow = sb.classList.contains('open') ? 'hidden' : '';
}

if (window.innerWidth <= 768) {
    const btn = document.querySelector('.filter-toggle-btn');
    if (btn) btn.style.display = 'flex';
    const closeBtn = document.getElementById('sidebarCloseBtn');
    if (closeBtn) closeBtn.style.display = 'block';
}
window.addEventListener('resize', function(){
    const btn = document.querySelector('.filter-toggle-btn');
    if (btn) btn.style.display = window.innerWidth <= 768 ? 'flex' : 'none';
});

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

    const phone = document.getElementById('payPhone').value.trim();
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
                    showToast('Payment timeout. Check your phone and try again.', 'error');
                }
            })
            .catch(() => {});
    }, 3000);
}

function notifyMe(productId, productName) {
    fetch('/agro_project/notify_request.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'product_id=' + productId
    })
    .then(r => r.json())
    .then(d => showToast(d.message || 'We will notify you when ' + productName + ' is back in stock'))
    .catch(() => showToast('We will notify you when available'));
}

document.getElementById('paymentModal')?.addEventListener('click', function(e) {
    if (e.target === this) closePaymentModal();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closePaymentModal();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>