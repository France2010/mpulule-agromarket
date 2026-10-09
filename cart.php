<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();
$page_title = 'Cart & Checkout';

$success = $error = '';

// Handle checkout submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    $name   = trim($_POST['customer_name'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $phone  = trim($_POST['phone'] ?? '');
    $region = trim($_POST['region'] ?? '');
    $notes  = trim($_POST['delivery_notes'] ?? '');

    if (empty($_SESSION['cart'])) {
        $error = 'Your cart is empty.';
    } elseif (!$name || !$email || !$phone || !$region) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $total = array_sum(array_map(fn($i) => $i['price'] * $i['qty'], $_SESSION['cart']));
        $ref   = gen_ref('ORD');
        $uid   = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 'NULL';
        $n_e   = mysqli_real_escape_string($conn, $name);
        $e_e   = mysqli_real_escape_string($conn, $email);
        $ph_e  = mysqli_real_escape_string($conn, $phone);
        $r_e   = mysqli_real_escape_string($conn, $region);
        $no_e  = mysqli_real_escape_string($conn, $notes);

        mysqli_query($conn, "INSERT INTO orders (order_ref,user_id,customer_name,email,phone,region,delivery_notes,total_amount) 
            VALUES('$ref'," . ($uid === 'NULL' ? 'NULL' : $uid) . ",'$n_e','$e_e','$ph_e','$r_e','$no_e',$total)");
        $order_id = mysqli_insert_id($conn);

        foreach ($_SESSION['cart'] as $item) {
            $pid   = (int)$item['id'];
            $qty   = (int)$item['qty'];
            $price = (float)$item['price'];
            mysqli_query($conn, "INSERT INTO order_items (order_id,product_id,qty,unit_price) VALUES($order_id,$pid,$qty,$price)");
            mysqli_query($conn, "UPDATE products SET stock_qty=stock_qty-$qty WHERE id=$pid");
        }

        $_SESSION['cart'] = [];
        $success = "Order submitted successfully! Your reference is <strong>$ref</strong>. Our team will contact you within 24 hours.";
    }
}

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
$cart = $_SESSION['cart'];
$total = array_sum(array_map(fn($i) => $i['price'] * $i['qty'], $cart));

require_once __DIR__ . '/includes/header.php';
?>

<style>
/* Cart page dark theme */
.cart-empty {
    text-align: center;
    padding: 60px 20px;
    background: linear-gradient(135deg, #111827 0%, #0f172a 100%);
    border-radius: 24px;
    border: 1px solid rgba(245, 158, 11, 0.15);
}

.cart-empty i {
    color: #f59e0b !important;
}

.cart-empty h3 {
    color: #e5e7eb;
    margin-top: 16px;
}

.cart-empty p {
    color: #9ca3af;
}

.card {
    background: linear-gradient(135deg, #111827 0%, #0f172a 100%);
    border: 1px solid rgba(245, 158, 11, 0.15);
    border-radius: 16px;
    overflow: hidden;
}

.card h3 {
    color: #fbbf24;
    font-weight: 700;
}

.cart-table {
    width: 100%;
    border-collapse: collapse;
}

.cart-table th {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    color: #fbbf24;
    font-weight: 700;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 14px 16px;
}

.cart-table td {
    color: #e5e7eb;
    padding: 14px 16px;
    border-bottom: 1px solid #1f2937;
}

.cart-table tfoot td {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    border-top: 1px solid rgba(245, 158, 11, 0.2);
}

.cart-table .qty-btn {
    background: #1e293b;
    border: 1px solid #334155;
    width: 28px;
    height: 28px;
    border-radius: 8px;
    color: #fbbf24;
    cursor: pointer;
    transition: all 0.2s;
}

.cart-table .qty-btn:hover {
    background: rgba(245, 158, 11, 0.2);
    border-color: #f59e0b;
}

.cart-table .remove-btn {
    background: rgba(239, 68, 68, 0.15);
    color: #f87171;
    border: none;
    padding: 6px 10px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
}

.cart-table .remove-btn:hover {
    background: rgba(239, 68, 68, 0.3);
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
}

.form-control:focus {
    border-color: #f59e0b;
    box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2);
    outline: none;
}

.form-control::placeholder {
    color: #64748b;
}

textarea.form-control {
    resize: vertical;
}

/* Order summary box */
.order-summary {
    background: rgba(245, 158, 11, 0.05);
    border: 1px solid rgba(245, 158, 11, 0.2);
    border-radius: 12px;
    padding: 14px;
    margin-bottom: 16px;
}

.order-summary i {
    color: #f59e0b;
}

.order-summary strong {
    color: #fbbf24;
}

.btn-gold {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    border: none;
    color: white;
    font-weight: 700;
    padding: 14px;
    border-radius: 12px;
    transition: all 0.3s;
}

.btn-gold:hover {
    background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(245, 158, 11, 0.3);
}

/* Alerts */
.alert-success {
    background: rgba(16, 185, 129, 0.1);
    border-left-color: #10b981;
    color: #34d399;
    border-radius: 12px;
    padding: 16px;
}

.alert-danger {
    background: rgba(239, 68, 68, 0.1);
    border-left-color: #ef4444;
    color: #f87171;
    border-radius: 12px;
    padding: 16px;
}

/* Responsive */
@media (max-width: 768px) {
    .cart-table th,
    .cart-table td {
        padding: 10px 8px;
        font-size: 12px;
    }
    
    .qty-btn {
        width: 24px;
        height: 24px;
        font-size: 12px;
    }
}
</style>

<div class="page-header">
    <div class="container">
        <div class="breadcrumb"><a href="index.php">Home</a><span>›</span> Cart &amp; Checkout</div>
        <h1>Your Cart</h1>
    </div>
</div>

<section class="section">
    <div class="container">
        <?php if($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <div><?php echo $success; ?></div>
        </div>
        <div style="text-align:center;padding:20px">
            <a href="/agro_project/products.php" class="btn btn-gold"><i class="fas fa-store"></i> Continue Shopping</a>
        </div>
        <?php else: ?>

        <?php if($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo e($error); ?></span>
        </div>
        <?php endif; ?>

        <?php if(empty($cart)): ?>
        <div class="cart-empty">
            <i class="fas fa-cart-shopping" style="font-size:3rem"></i>
            <h3>Your cart is empty</h3>
            <p style="margin-top:8px">Browse our products and add items to get started</p>
            <a href="/agro_project/products.php" class="btn btn-gold" style="margin-top:16px;display:inline-block">Browse Products</a>
        </div>
        <?php else: ?>

        <div style="display:grid;grid-template-columns:1fr 380px;gap:24px;align-items:start">
            <!-- CART TABLE -->
            <div class="card" style="overflow:hidden">
                <div style="padding:16px 20px;border-bottom:1px solid rgba(245,158,11,0.15)">
                    <h3 style="margin:0"><i class="fas fa-cart-shopping" style="color:#f59e0b;margin-right:8px"></i> Cart items (<?php echo count($cart); ?>)</h3>
                </div>
                <table class="cart-table" style="width:100%;border-collapse:collapse">
                    <thead>
                        <tr>
                            <th style="padding:14px 16px;text-align:left">Product</th>
                            <th style="padding:14px;text-align:center">Qty</th>
                            <th style="padding:14px;text-align:right">Price</th>
                            <th style="padding:14px;text-align:right">Subtotal</th>
                            <th style="padding:14px;text-align:center"></th>
                        </tr>
                    </thead>
                    <tbody id="cart-tbody">
                    <?php foreach($cart as $item): ?>
                    <tr id="row-<?php echo $item['id']; ?>">
                        <td style="padding:14px 16px">
                            <div style="font-weight:600;color:#e5e7eb"><?php echo e($item['name']); ?></div>
                            <div style="font-size:11px;color:#9ca3af"><?php echo e($item['unit']); ?></div>
                         </td
                        <td style="padding:14px;text-align:center">
                            <div style="display:flex;align-items:center;gap:6px;justify-content:center">
                                <button onclick="updateQty(<?php echo $item['id']; ?>,<?php echo $item['qty']-1; ?>)" class="qty-btn">−</button>
                                <span id="qty-<?php echo $item['id']; ?>" style="font-weight:700;min-width:28px;text-align:center;color:#fbbf24"><?php echo $item['qty']; ?></span>
                                <button onclick="updateQty(<?php echo $item['id']; ?>,<?php echo $item['qty']+1; ?>)" class="qty-btn">+</button>
                            </div>
                         </td
                        <td style="padding:14px;text-align:right;color:#9ca3af"><?php echo fmt((float)$item['price']); ?></td
                        <td style="padding:14px;text-align:right;font-weight:700;color:#f59e0b" id="sub-<?php echo $item['id']; ?>"><?php echo fmt((float)$item['price'] * $item['qty']); ?></td
                        <td style="padding:14px;text-align:center">
                            <button onclick="removeItem(<?php echo $item['id']; ?>)" class="remove-btn"><i class="fas fa-trash-can"></i></button>
                        </td
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" style="padding:16px;text-align:right;font-weight:700;color:#e5e7eb">Order Total</td
                            <td style="padding:16px;text-align:right;font-weight:800;font-size:20px;color:#f59e0b" id="cart-total-cell"><?php echo fmt($total); ?></td
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- CHECKOUT FORM -->
            <div class="card card-body" style="padding:24px">
                <h3 style="margin-bottom:16px;color:#fbbf24"><i class="fas fa-file-invoice" style="color:#f59e0b;margin-right:8px"></i> Checkout details</h3>
                <form method="POST">
                    <div class="form-group" style="margin-bottom:16px">
                        <label>Full name</label>
                        <input type="text" name="customer_name" class="form-control" required
                            value="<?php echo isset($_SESSION['firstname']) ? e($_SESSION['firstname'] . ' ' . ($_SESSION['lastname'] ?? '')) : ''; ?>" 
                            placeholder="Your full name">
                    </div>
                    <div class="form-group" style="margin-bottom:16px">
                        <label>Email address</label>
                        <input type="email" name="email" class="form-control" required
                            value="<?php echo isset($_SESSION['email']) ? e($_SESSION['email']) : ''; ?>" 
                            placeholder="you@example.com">
                    </div>
                    <div class="form-group" style="margin-bottom:16px">
                        <label>Phone / WhatsApp</label>
                        <input type="tel" name="phone" class="form-control" required 
                            placeholder="+255 7XX XXX XXX">
                    </div>
                    <div class="form-group" style="margin-bottom:16px">
                        <label>Region / District</label>
                        <input type="text" name="region" class="form-control" required 
                            placeholder="e.g. Mbeya, Ruvuma...">
                    </div>
                    <div class="form-group" style="margin-bottom:16px">
                        <label>Delivery notes</label>
                        <textarea name="delivery_notes" class="form-control" rows="3" 
                            placeholder="Special instructions, delivery address..."></textarea>
                    </div>
                    <div class="order-summary">
                        <i class="fas fa-info-circle"></i>
                        <span>Order total: <strong><?php echo fmt($total); ?></strong></span><br>
                        <span style="color:#9ca3af;font-size:12px">Confirmation will be sent to your email &amp; WhatsApp within 24 hours.</span>
                    </div>
                    <button type="submit" name="checkout" class="btn btn-gold btn-block">
                        <i class="fas fa-paper-plane"></i> Submit Order
                    </button>
                </form>
            </div>
        </div>

        <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<script>
function updateQty(id, qty){
    fetch('/agro_project/cart_action.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=update&product_id=' + id + '&qty=' + qty
    })
    .then(r => r.json())
    .then(d => {
        if(d.success){
            document.getElementById('nav-cart-count').textContent = d.cart_count;
            if(qty <= 0){
                document.getElementById('row-' + id).remove();
            } else {
                document.getElementById('qty-' + id).textContent = qty;
                document.getElementById('sub-' + id).textContent = d.subtotal || formatPrice(d.subtotal);
            }
            document.getElementById('cart-total-cell').textContent = d.total;
            if(d.cart_count === 0) location.reload();
        }
    });
}

function removeItem(id){
    fetch('/agro_project/cart_action.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=remove&product_id=' + id
    })
    .then(r => r.json())
    .then(d => {
        if(d.success){
            document.getElementById('row-' + id).remove();
            document.getElementById('nav-cart-count').textContent = d.cart_count;
            document.getElementById('cart-total-cell').textContent = d.total;
            if(d.cart_count === 0) location.reload();
        }
    });
}

function formatPrice(price) {
    return 'TSh ' + Number(price).toLocaleString() + '/=';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>