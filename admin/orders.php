<?php
session_start();
$root = '../';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/../includes/notify.php';

// ── Handle status update + send notification ──────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $oid        = (int)$_POST['order_id'];
    $new_status = mysqli_real_escape_string($conn, $_POST['status']);
    $note       = mysqli_real_escape_string($conn, trim($_POST['admin_note'] ?? ''));

    // Fetch order BEFORE update so we have customer details
    $order = mysqli_fetch_assoc(
        mysqli_query($conn, "SELECT * FROM orders WHERE id=$oid LIMIT 1"));

    if ($order) {
        // Update DB
        mysqli_query($conn,
            "UPDATE orders SET status='$new_status', admin_note='$note' WHERE id=$oid");

        // Send notification to customer
        $subject  = "Order {$order['order_ref']} — Status: " . ucfirst($new_status);
        $html     = order_notification_body($order, $new_status, stripslashes($note));
        notify_user($conn,
            $order['user_id'] ?: null,
            $order['email'],
            $order['phone'],
            $order['customer_name'],
            $subject,
            $html,
            'order'
        );
    }
    header('Location: /agro_project/admin/orders.php?msg=updated'); 
    exit;
}

// NOW include the header AFTER all redirects are handled
$page_title = 'Orders';
require_once __DIR__ . '/includes/admin_header.php';

// ── Filters ───────────────────────────────────────────────
$filter = $_GET['filter'] ?? 'all';
$where  = $filter !== 'all'
    ? "WHERE o.status='" . mysqli_real_escape_string($conn, $filter) . "'"
    : '';

$orders = mysqli_query($conn,
    "SELECT o.*, COUNT(oi.id) item_count, SUM(oi.qty) total_qty
     FROM orders o
     LEFT JOIN order_items oi ON oi.order_id=o.id
     $where
     GROUP BY o.id ORDER BY o.created_at DESC");

// Status list from DB
$db_statuses_res = mysqli_query($conn, "SELECT slug,label,color FROM order_statuses ORDER BY FIELD(slug,'pending','approved','processing','shipped','delivered','rejected')");
$db_statuses = [];
while ($r = mysqli_fetch_assoc($db_statuses_res)) $db_statuses[$r['slug']] = $r;

// If no statuses in DB, use defaults
if (empty($db_statuses)) {
    $db_statuses = [
        'pending' => ['label' => 'Pending', 'color' => 'badge-warn'],
        'approved' => ['label' => 'Approved', 'color' => 'badge-success'],
        'processing' => ['label' => 'Processing', 'color' => 'badge-info'],
        'shipped' => ['label' => 'Shipped', 'color' => 'badge-info'],
        'delivered' => ['label' => 'Delivered', 'color' => 'badge-success'],
        'rejected' => ['label' => 'Rejected', 'color' => 'badge-danger']
    ];
}
?>

<?php if (isset($_GET['msg'])): ?>
<div class="alert alert-success"><i class="fas fa-check-circle"></i>
    <span>Order updated and customer notified by email &amp; WhatsApp link.</span>
</div>
<?php endif; ?>

<!-- Filter buttons -->
<div style="display:flex;gap:6px;margin-bottom:20px;flex-wrap:wrap">
    <a href="?filter=all" class="btn btn-sm <?php echo $filter==='all'?'btn-dark':'btn-outline'; ?>" style="border-radius:20px">All</a>
    <?php foreach ($db_statuses as $slug => $st): ?>
    <?php $cnt = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM orders WHERE status='$slug'"))['c']; ?>
    <a href="?filter=<?php echo $slug; ?>" class="btn btn-sm <?php echo $filter===$slug?'btn-dark':'btn-outline'; ?>" style="border-radius:20px">
        <?php echo e($st['label']); ?>
        <?php if ($cnt > 0): ?><span style="margin-left:5px;background:var(--gold);color:var(--dark);border-radius:8px;padding:1px 6px;font-size:10px"><?php echo $cnt; ?></span><?php endif; ?>
    </a>
    <?php endforeach; ?>
</div>

<div class="table-wrap">
<table class="table">
<thead>
    <tr>
        <th>Order Ref</th>
        <th>Customer</th>
        <th>Contact</th>
        <th>Region</th>
        <th>Items</th>
        <th>Total</th>
        <th>Status</th>
        <th>Notified</th>
        <th>Date</th>
        <th>Actions</th>
    </tr>
</thead>
<tbody>
<?php $count = 0; while ($o = mysqli_fetch_assoc($orders)): $count++;
    $st_info = $db_statuses[$o['status']] ?? ['label'=>ucfirst($o['status']),'color'=>'badge-gold'];
    // Check if a notification was sent for this order
    $notif = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT sent_email, whatsapp_link FROM notifications 
         WHERE type='order' AND email='".mysqli_real_escape_string($conn,$o['email'])."' 
         AND subject LIKE '%{$o['order_ref']}%' 
         ORDER BY id DESC LIMIT 1"));
?>
<tr>
    <td><strong style="color:var(--gold-dark)"><?php echo e($o['order_ref']); ?></strong></td>
    <td><?php echo e($o['customer_name']); ?></td>
    <td>
        <a href="mailto:<?php echo e($o['email']); ?>" style="font-size:12px;display:block"><?php echo e($o['email']); ?></a>
        <a href="tel:<?php echo e($o['phone']); ?>" style="font-size:12px;color:var(--text-muted)"><?php echo e($o['phone']); ?></a>
    </td>
    <td><?php echo e($o['region']); ?></td>
    <td style="text-align:center">
        <?php echo (int)$o['item_count']; ?> item(s)<br>
        <span style="font-size:11px;color:var(--text-muted)">qty: <?php echo (int)$o['total_qty']; ?></span>
    </td>
    <td><strong><?php echo fmt((float)$o['total_amount']); ?></strong></td>
    <td>
        <span class="badge <?php echo e($st_info['color']); ?>"><?php echo e($st_info['label']); ?></span>
    </td>
    <td style="font-size:12px">
        <?php if ($notif && ($notif['sent_email'] ?? 0) == 1): ?>
            <span style="color:var(--success)"><i class="fas fa-envelope"></i> Email sent</span><br>
            <?php if (!empty($notif['whatsapp_link'])): ?>
            <a href="<?php echo e($notif['whatsapp_link']); ?>" target="_blank" style="color:#25d366;font-size:11px">
                <i class="fab fa-whatsapp"></i> Send WA
            </a>
            <?php endif; ?>
        <?php else: ?>
            <span style="color:var(--text-muted)">—</span>
        <?php endif; ?>
    </td>
    <td style="font-size:12px;color:var(--text-muted);white-space:nowrap">
        <?php echo date('d M Y H:i', strtotime($o['created_at'])); ?>
    </td>
    <td style="white-space:nowrap">
        <button onclick="openModal(<?php echo $o['id']; ?>,'<?php echo e(addslashes($o['order_ref'])); ?>','<?php echo $o['status']; ?>','<?php echo e(addslashes($o['admin_note']??'')); ?>')"
            class="btn btn-sm btn-dark"><i class="fas fa-edit"></i></button>
        <a href="order_detail.php?id=<?php echo $o['id']; ?>" class="btn btn-sm btn-outline"><i class="fas fa-eye"></i></a>
    </td>
</tr>
<?php endwhile; ?>
<?php if (!$count): ?>
<tr>
    <td colspan="10" style="text-align:center;padding:32px;color:var(--text-muted)">No orders found</td>
</tr>
<?php endif; ?>
</tbody>
</table>
</div>

<!-- Update status modal -->
<div id="modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:200;align-items:center;justify-content:center">
<div class="card" style="width:100%;max-width:460px;padding:28px;position:relative">
    <button onclick="closeModal()" style="position:absolute;top:14px;right:14px;background:none;border:none;font-size:20px;cursor:pointer;color:var(--text-muted)">&times;</button>
    <h3 style="margin-bottom:6px;color:var(--dark)">Update Order</h3>
    <p style="font-size:13px;color:var(--gold-dark);font-weight:700;margin-bottom:16px" id="modal-ref"></p>
    <div class="alert alert-info" style="font-size:13px;margin-bottom:16px">
        <i class="fas fa-bell"></i>
        <span>The customer will be <strong>notified by email</strong> and a WhatsApp link will be generated automatically when you save.</span>
    </div>
    <form method="POST">
        <input type="hidden" name="order_id" id="modal-oid">
        <div class="form-group">
            <label>New status</label>
            <select name="status" id="modal-status" class="form-control">
                <?php foreach ($db_statuses as $slug => $st): ?>
                <option value="<?php echo $slug; ?>"><?php echo e($st['label']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Message / note to customer <span style="color:var(--text-muted);font-weight:400">(optional)</span></label>
            <textarea name="admin_note" id="modal-note" class="form-control" rows="3"
                placeholder="e.g. Your order will arrive Thursday by 2PM..."></textarea>
        </div>
        <button type="submit" name="update_status" class="btn btn-gold btn-block">
            <i class="fas fa-paper-plane"></i> Update &amp; Notify Customer
        </button>
    </form>
</div>
</div>

<script>
function openModal(id, ref, status, note) {
    document.getElementById('modal-oid').value  = id;
    document.getElementById('modal-ref').textContent = ref;
    document.getElementById('modal-status').value = status;
    document.getElementById('modal-note').value = note;
    document.getElementById('modal').style.display = 'flex';
}
function closeModal() { document.getElementById('modal').style.display = 'none'; }
document.getElementById('modal').addEventListener('click', function(e){ if(e.target===this) closeModal(); });
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>