<?php
session_start();
$root = '../';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();
$page_title = 'Users';
require_once __DIR__ . '/includes/admin_header.php';

$users = mysqli_query($conn,
    "SELECT u.*,COUNT(o.id) total_orders,COALESCE(SUM(o.total_amount),0) total_spent
     FROM users u LEFT JOIN orders o ON o.user_id=u.id
     GROUP BY u.id ORDER BY u.created_at DESC");
?>
<div class="table-wrap">
<table class="table">
<thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Region</th><th>Role</th><th>Orders</th><th>Spent</th><th>Joined</th></tr></thead>
<tbody>
<?php while($u=mysqli_fetch_assoc($users)): ?>
<tr>
    <td><strong><?php echo e($u['firstname'].' '.$u['lastname']); ?></strong></td>
    <td><a href="mailto:<?php echo e($u['email']); ?>"><?php echo e($u['email']); ?></a></td>
    <td><?php echo e($u['phone']??'—'); ?></td>
    <td><?php echo e($u['region']??'—'); ?></td>
    <td><span class="badge <?php echo $u['user_role']==='admin'?'badge-gold':'badge-info'; ?>"><?php echo ucfirst($u['user_role']); ?></span></td>
    <td><?php echo $u['total_orders']; ?></td>
    <td><?php echo fmt((float)$u['total_spent']); ?></td>
    <td style="font-size:12px;color:var(--text-muted)"><?php echo date('d M Y',strtotime($u['created_at'])); ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
