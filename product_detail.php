<?php
session_start();
require_once __DIR__ . '/config.php';
$page_title = 'Product Details';

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    header('Location: /agro_project/products.php');
    exit;
}

// Fetch product details from database
$query = "SELECT p.*, c.name cat_name, c.slug cat_slug, c.icon cat_icon 
          FROM products p 
          JOIN categories c ON c.id = p.category_id 
          WHERE p.id = $product_id AND p.active = 1 
          LIMIT 1";

$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    header('Location: /agro_project/products.php?error=notfound');
    exit;
}

$product = mysqli_fetch_assoc($result);

// Fetch related products (same category, excluding current product)
$related_query = "SELECT p.*, c.name cat_name 
                  FROM products p 
                  JOIN categories c ON c.id = p.category_id 
                  WHERE p.category_id = {$product['category_id']} 
                  AND p.id != $product_id 
                  AND p.active = 1 
                  ORDER BY p.featured DESC 
                  LIMIT 4";
$related_products = mysqli_query($conn, $related_query);

require_once __DIR__ . '/includes/header.php';
?>

<style>
/* Product detail page dark theme */
.product-detail {
    background: linear-gradient(135deg, #0a0f1c 0%, #0c1122 100%);
    min-height: calc(100vh - 62px);
}

.product-detail-card {
    background: linear-gradient(135deg, #111827 0%, #0f172a 100%);
    border: 1px solid rgba(245, 158, 11, 0.15);
    border-radius: 24px;
    overflow: hidden;
    margin-bottom: 40px;
}

.product-image {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    border-radius: 16px;
    overflow: hidden;
    text-align: center;
}

.product-image img {
    width: 100%;
    max-height: 400px;
    object-fit: contain;
}

.product-info {
    padding: 30px;
}

.product-category {
    display: inline-block;
    background: rgba(245, 158, 11, 0.15);
    color: #fbbf24;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 16px;
}

.product-title {
    font-size: 28px;
    font-weight: 800;
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    margin-bottom: 16px;
}

.product-price {
    font-size: 32px;
    font-weight: 800;
    color: #f59e0b;
    margin-bottom: 16px;
}

.product-unit {
    font-size: 14px;
    color: #9ca3af;
    font-weight: 400;
}

.product-stock {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 20px;
}

.stock-in {
    background: rgba(16, 185, 129, 0.15);
    color: #34d399;
    border: 1px solid rgba(16, 185, 129, 0.3);
}

.stock-low {
    background: rgba(245, 158, 11, 0.15);
    color: #fbbf24;
    border: 1px solid rgba(245, 158, 11, 0.3);
}

.stock-out {
    background: rgba(239, 68, 68, 0.15);
    color: #f87171;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.product-description {
    color: #e5e7eb;
    font-size: 15px;
    line-height: 1.6;
    margin-bottom: 24px;
    padding: 16px;
    background: rgba(30, 41, 59, 0.5);
    border-radius: 12px;
}

.product-meta {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
    padding: 16px;
    background: rgba(30, 41, 59, 0.3);
    border-radius: 12px;
}

.meta-item {
    text-align: center;
}

.meta-label {
    font-size: 11px;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 4px;
}

.meta-value {
    font-size: 16px;
    font-weight: 700;
    color: #fbbf24;
}

/* Quantity selector */
.qty-selector {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
}

.qty-label {
    color: #fbbf24;
    font-weight: 600;
}

.qty-btn {
    background: #1e293b;
    border: 1px solid #334155;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    color: #fbbf24;
    font-size: 18px;
    cursor: pointer;
    transition: all 0.2s;
}

.qty-btn:hover {
    background: rgba(245, 158, 11, 0.2);
    border-color: #f59e0b;
}

.qty-value {
    font-size: 18px;
    font-weight: 700;
    color: #e5e7eb;
    min-width: 40px;
    text-align: center;
}

.btn-add-cart-large {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    border: none;
    color: white;
    font-weight: 700;
    padding: 14px 28px;
    border-radius: 12px;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.3s;
    width: 100%;
}

.btn-add-cart-large:hover {
    background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(245, 158, 11, 0.3);
}

/* Related products */
.related-section {
    margin-top: 40px;
}

.related-title {
    font-size: 22px;
    font-weight: 700;
    color: #fbbf24;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 1px solid rgba(245, 158, 11, 0.2);
}

.related-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.related-card {
    background: linear-gradient(135deg, #111827 0%, #0f172a 100%);
    border: 1px solid rgba(245, 158, 11, 0.15);
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s;
    text-decoration: none;
}

.related-card:hover {
    transform: translateY(-4px);
    border-color: rgba(245, 158, 11, 0.4);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
}

.related-img {
    height: 140px;
    background: #1e293b;
    display: flex;
    align-items: center;
    justify-content: center;
}

.related-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.related-img i {
    font-size: 2.5rem;
    color: #f59e0b;
}

.related-info {
    padding: 12px;
}

.related-name {
    font-size: 14px;
    font-weight: 600;
    color: #e5e7eb;
    margin-bottom: 4px;
}

.related-price {
    font-size: 16px;
    font-weight: 700;
    color: #f59e0b;
}

/* Back button */
.back-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #1e293b;
    border: 1px solid #334155;
    color: #fbbf24;
    padding: 10px 20px;
    border-radius: 8px;
    text-decoration: none;
    margin-bottom: 20px;
    transition: all 0.2s;
}

.back-btn:hover {
    background: rgba(245, 158, 11, 0.1);
    border-color: #f59e0b;
}

@media (max-width: 768px) {
    .product-info {
        padding: 20px;
    }
    
    .product-title {
        font-size: 22px;
    }
    
    .product-price {
        font-size: 26px;
    }
    
    .related-grid {
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    }
}
</style>

<div class="product-detail">
    <div class="container" style="padding: 30px 20px">
        
        <!-- Back Button -->
        <a href="/agro_project/products.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Products
        </a>
        
        <!-- Main Product Card -->
        <div class="product-detail-card">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0">
                
                <!-- Product Image -->
                <div class="product-image">
                    <?php 
                    $image_path = '';
                    if (!empty($product['image'])) {
                        $full_path = __DIR__ . '/uploads/products/' . $product['image'];
                        if (file_exists($full_path)) {
                            $image_path = '/agro_project/uploads/products/' . $product['image'];
                        }
                    }
                    ?>
                    <?php if ($image_path): ?>
                        <img src="<?php echo $image_path; ?>" alt="<?php echo e($product['name']); ?>">
                    <?php else: ?>
                        <div style="padding: 60px 20px; text-align: center;">
                            <i class="fas <?php echo e($product['cat_icon']); ?>" style="font-size: 5rem; color: #f59e0b; margin-bottom: 16px; display: block;"></i>
                            <p style="color: #9ca3af;">No image available</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Product Info -->
                <div class="product-info">
                    <span class="product-category">
                        <i class="fas <?php echo e($product['cat_icon']); ?>"></i> <?php echo e($product['cat_name']); ?>
                    </span>
                    
                    <h1 class="product-title"><?php echo e($product['name']); ?></h1>
                    
                    <div class="product-price">
                        <?php echo fmt((float)$product['price']); ?>
                        <span class="product-unit">/ <?php echo e($product['unit']); ?></span>
                    </div>
                    
                    <div>
                        <?php if ($product['stock_qty'] > 20): ?>
                            <span class="product-stock stock-in">
                                <i class="fas fa-check-circle"></i> In Stock (<?php echo $product['stock_qty']; ?> units)
                            </span>
                        <?php elseif ($product['stock_qty'] > 0): ?>
                            <span class="product-stock stock-low">
                                <i class="fas fa-exclamation-triangle"></i> Low Stock (<?php echo $product['stock_qty']; ?> units left)
                            </span>
                        <?php else: ?>
                            <span class="product-stock stock-out">
                                <i class="fas fa-times-circle"></i> Out of Stock
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-description">
                        <?php echo nl2br(e($product['description'] ?: 'No description available for this product.')); ?>
                    </div>
                    
                    <div class="product-meta">
                        <div class="meta-item">
                            <div class="meta-label">Unit</div>
                            <div class="meta-value"><?php echo e($product['unit']); ?></div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">Category</div>
                            <div class="meta-value"><?php echo e($product['cat_name']); ?></div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">Stock Status</div>
                            <div class="meta-value">
                                <?php if ($product['stock_qty'] > 0): ?>
                                    Available
                                <?php else: ?>
                                    Sold Out
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($product['stock_qty'] > 0): ?>
                    <div class="qty-selector">
                        <span class="qty-label">Quantity:</span>
                        <button class="qty-btn" onclick="changeQty(-1)">−</button>
                        <span class="qty-value" id="qtyValue">1</span>
                        <button class="qty-btn" onclick="changeQty(1)">+</button>
                    </div>
                    
                    <button class="btn-add-cart-large" onclick="addToCart()">
                        <i class="fas fa-shopping-cart"></i> Add to Cart
                    </button>
                    <?php else: ?>
                    <button class="btn-add-cart-large" disabled style="opacity:0.5;cursor:not-allowed">
                        <i class="fas fa-ban"></i> Out of Stock
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Related Products -->
        <?php if (mysqli_num_rows($related_products) > 0): ?>
        <div class="related-section">
            <h2 class="related-title">
                <i class="fas fa-leaf" style="color: #f59e0b;"></i> Related Products
            </h2>
            <div class="related-grid">
                <?php while ($related = mysqli_fetch_assoc($related_products)): ?>
                <a href="/agro_project/product_detail.php?id=<?php echo $related['id']; ?>" class="related-card">
                    <div class="related-img">
                        <?php 
                        $rel_image = '';
                        if (!empty($related['image'])) {
                            $rel_full = __DIR__ . '/uploads/products/' . $related['image'];
                            if (file_exists($rel_full)) {
                                $rel_image = '/agro_project/uploads/products/' . $related['image'];
                            }
                        }
                        ?>
                        <?php if ($rel_image): ?>
                            <img src="<?php echo $rel_image; ?>" alt="<?php echo e($related['name']); ?>">
                        <?php else: ?>
                            <i class="fas <?php echo e($product['cat_icon']); ?>"></i>
                        <?php endif; ?>
                    </div>
                    <div class="related-info">
                        <div class="related-name"><?php echo e($related['name']); ?></div>
                        <div class="related-price"><?php echo fmt((float)$related['price']); ?></div>
                        <div style="font-size: 11px; color: #9ca3af; margin-top: 4px;">/ <?php echo e($related['unit']); ?></div>
                    </div>
                </a>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endif; ?>
        
    </div>
</div>

<script>
let currentQty = 1;
const maxStock = <?php echo $product['stock_qty']; ?>;

function changeQty(delta) {
    let newQty = currentQty + delta;
    if (newQty >= 1 && newQty <= maxStock) {
        currentQty = newQty;
        document.getElementById('qtyValue').textContent = currentQty;
    }
}

function addToCart() {
    const productId = <?php echo $product['id']; ?>;
    const productName = '<?php echo addslashes($product['name']); ?>';
    
    fetch('/agro_project/cart_action.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=add&product_id=' + productId + '&qty=' + currentQty
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const cartCount = document.getElementById('nav-cart-count');
            if (cartCount) cartCount.textContent = data.cart_count;
            showToast('Added ' + currentQty + ' × ' + productName);
        } else {
            showToast(data.message || 'Error adding to cart', 'error');
        }
    });
}

function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    if (toast) {
        toast.innerHTML = '<i class="fas ' + (type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle') + '"></i><span>' + message + '</span>';
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3000);
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>