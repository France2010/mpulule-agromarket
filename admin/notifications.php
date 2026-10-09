<?php
session_start();
$root = '../';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();
$page_title = 'Notification History';
require_once __DIR__ . '/includes/admin_header.php';

$notifs = mysqli_query($conn,
    "SELECT n.*, u.firstname, u.lastname FROM notifications n
     LEFT JOIN users u ON u.id=n.user_id
     ORDER BY n.created_at DESC LIMIT 200");
?>
<div class="table-wrap">
<table class="table">
<thead><tr><th>Type</th><th>Recipient</th><th>Subject</th><th>Email</th><th>Phone</th><th>WA Link</th><th>Sent at</th></tr></thead>
<tbody>
<?php $c=0; while($n=mysqli_fetch_assoc($notifs)): $c++; ?>
<tr>
    <td><span class="badge <?php echo $n['type']==='loan'?'badge-info':'badge-gold'; ?>"><?php echo ucfirst($n['type']); ?></span></td>
    <td><?php echo $n['firstname'] ? e($n['firstname'].' '.$n['lastname']) : '<span style="color:var(--text-muted)">Guest</span>'; ?></td>
    <td style="font-size:13px;max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?php echo e($n['subject']); ?></td>
    <td>
        <a href="mailto:<?php echo e($n['email']); ?>"><?php echo e($n['email']); ?></a><br>
        <?php if($n['sent_email']): ?><span style="color:var(--success);font-size:11px"><i class="fas fa-check"></i> Sent</span>
        <?php else: ?><span style="color:var(--danger);font-size:11px"><i class="fas fa-times"></i> Failed</span><?php endif; ?>
    </td>
    <td><?php echo e($n['phone']); ?></td>
    <td>
        <?php if($n['whatsapp_link']): ?>
        <a href="<?php echo e($n['whatsapp_link']); ?>" target="_blank" class="btn btn-sm" style="background:#25d366;color:#fff;border:none">
            <i class="fab fa-whatsapp"></i> Send WA
        </a>
        <?php else: ?>—<?php endif; ?>
    </td>
    <td style="font-size:12px;color:var(--text-muted);white-space:nowrap">
        <?php echo $n['sent_at'] ? date('d M Y H:i', strtotime($n['sent_at'])) : date('d M Y H:i', strtotime($n['created_at'])); ?>
    </td>
</tr>
<?php endwhile; if(!$c): ?>
<tr><td colspan="7" style="text-align:center;padding:32px;color:var(--text-muted)">No notifications sent yet</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
