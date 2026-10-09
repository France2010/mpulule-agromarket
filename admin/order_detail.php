<?php
session_start();
$root = '../';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();
$page_title = 'Order Detail';
require_once __DIR__ . '/includes/admin_header.php';

$id = (int)($_GET['id'] ?? 0);
$order = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM orders WHERE id=$id LIMIT 1"));
if (!$order) { echo '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i><span>Order not found.</span></div>'; require_once __DIR__ . '/includes/admin_footer.php'; exit; }

$items = mysqli_query($conn,"SELECT oi.*,p.name prod_name,p.unit,c.name cat_name FROM order_items oi JOIN products p ON p.id=oi.product_id JOIN categories c ON c.id=p.category_id WHERE oi.order_id=$id");
?>
<div style="margin-bottom:16px"><a href="orders.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back to orders</a></div>

<div style="display:grid;grid-template-columns:1fr 300px;gap:20px">
<div>
    <div class="card card-body" style="margin-bottom:16px">
        <div class="flex-between" style="margin-bottom:16px">
            <h3 style="color:var(--gold-dark)"><?php echo e($order['order_ref']); ?></h3>
            <span class="badge <?php
                $sc=['pending'=>'badge-warn','approved'=>'badge-success','rejected'=>'badge-danger','processing'=>'badge-info','shipped'=>'badge-info','delivered'=>'badge-success'];
                echo $sc[$order['status']]??'badge-gold';
            ?>" style="font-size:13px;padding:5px 12px"><?php echo ucfirst($order['status']); ?></span>
        </div>
        <div class="grid-2" style="font-size:13px;gap:10px">
            <div><span class="text-muted">Customer:</span> <strong><?php echo e($order['customer_name']); ?></strong></div>
            <div><span class="text-muted">Region:</span> <?php echo e($order['region']); ?></div>
            <div><span class="text-muted">Email:</span> <a href="mailto:<?php echo e($order['email']); ?>"><?php echo e($order['email']); ?></a></div>
            <div><span class="text-muted">Phone:</span> <a href="tel:<?php echo e($order['phone']); ?>"><?php echo e($order['phone']); ?></a></div>
            <div><span class="text-muted">Date:</span> <?php echo date('d M Y H:i',strtotime($order['created_at'])); ?></div>
            <div><span class="text-muted">Total:</span> <strong style="color:var(--gold-dark)"><?php echo fmt((float)$order['total_amount']); ?></strong></div>
        </div>
        <?php if($order['delivery_notes']): ?>
        <div style="margin-top:12px;padding:10px;background:var(--gold-pale);border-radius:var(--radius);font-size:13px">
            <strong>Delivery notes:</strong> <?php echo e($order['delivery_notes']); ?>
        </div>
        <?php endif; ?>
        <?php if($order['admin_note']): ?>
        <div style="margin-top:10px;padding:10px;background:var(--info-bg);border-radius:var(--radius);font-size:13px">
            <strong>Admin note:</strong> <?php echo e($order['admin_note']); ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="card" style="overflow:hidden">
        <div style="padding:14px 16px;background:var(--gold-pale);border-bottom:1px solid var(--border)"><h3>Order items</h3></div>
        <table class="table">
            <thead><tr><th>Product</th><th>Category</th><th>Unit</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th></tr></thead>
            <tbody>
            <?php while($item=mysqli_fetch_assoc($items)): ?>
            <tr>
                <td><strong><?php echo e($item['prod_name']); ?></strong></td>
                <td><?php echo e($item['cat_name']); ?></td>
                <td><?php echo e($item['unit']); ?></td>
                <td><?php echo $item['qty']; ?></td>
                <td><?php echo fmt((float)$item['unit_price']); ?></td>
                <td><strong><?php echo fmt($item['unit_price']*$item['qty']); ?></strong></td>
            </tr>
            <?php endwhile; ?>
            <tr style="background:var(--gold-pale)">
                <td colspan="5" style="text-align:right;font-weight:700">Order Total</td>
                <td><strong style="font-size:16px;color:var(--gold-dark)"><?php echo fmt((float)$order['total_amount']); ?></strong></td>
            </tr>
            </tbody>
        </table>
    </div>
</div>

<div>
    <div class="card card-body">
        <h4 style="margin-bottom:14px">Update status</h4>
        <form method="POST" action="orders.php">
            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
            <div class="form-group"><label>Status</label>
                <select name="status" class="form-control">
                    <?php foreach(['pending','approved','processing','shipped','delivered','rejected'] as $s): ?>
                    <option value="<?php echo $s; ?>" <?php echo $order['status']===$s?'selected':''; ?>><?php echo ucfirst($s); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Note</label>
                <textarea name="admin_note" class="form-control" rows="2"><?php echo e($order['admin_note']??''); ?></textarea>
            </div>
            <button type="submit" name="update_status" class="btn btn-gold btn-block"><i class="fas fa-save"></i> Update</button>
        </form>

        <div style="margin-top:16px;padding-top:14px;border-top:1px solid var(--border)">
            <a href="mailto:<?php echo e($order['email']); ?>?subject=Order <?php echo e($order['order_ref']); ?>" class="btn btn-outline btn-block" style="margin-bottom:8px"><i class="fas fa-envelope"></i> Email customer</a>
            <a href="https://wa.me/<?php echo preg_replace('/\D/','',$order['phone']); ?>?text=Hello, regarding your order <?php echo e($order['order_ref']); ?>" target="_blank" class="btn btn-outline btn-block" style="border-color:#25d366;color:#25d366"><i class="fab fa-whatsapp"></i> WhatsApp customer</a>
        </div>
    </div>
</div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
