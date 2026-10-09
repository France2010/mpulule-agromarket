<?php
session_start();
$root = '../';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();
$page_title = 'Contact Messages';
require_once __DIR__ . '/includes/admin_header.php';

// Mark as read
if (isset($_GET['read'])) {
    $id = (int)$_GET['read'];
    mysqli_query($conn,"UPDATE contact_messages SET is_read=1 WHERE id=$id");
    header('Location: /agro_project/admin/messages.php'); exit;
}
// Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn,"DELETE FROM contact_messages WHERE id=$id");
    header('Location: /agro_project/admin/messages.php?msg=deleted'); exit;
}

$msgs = mysqli_query($conn,"SELECT * FROM contact_messages ORDER BY is_read ASC,created_at DESC");
?>
<?php if(isset($_GET['msg'])): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i><span>Message deleted.</span></div><?php endif; ?>

<div class="table-wrap">
<table class="table">
<thead><tr><th></th><th>Name</th><th>Email</th><th>Phone</th><th>Subject</th><th>Message</th><th>Date</th><th>Actions</th></tr></thead>
<tbody>
<?php $c=0; while($m=mysqli_fetch_assoc($msgs)): $c++; ?>
<tr style="<?php echo !$m['is_read']?'background:var(--gold-pale);font-weight:600':''; ?>">
    <td><?php echo !$m['is_read']?'<span class="badge badge-gold">New</span>':''; ?></td>
    <td><?php echo e($m['name']); ?></td>
    <td><a href="mailto:<?php echo e($m['email']); ?>"><?php echo e($m['email']); ?></a></td>
    <td><?php echo e($m['phone']??'—'); ?></td>
    <td><?php echo e($m['subject']); ?></td>
    <td style="font-size:13px;max-width:220px"><?php echo e(mb_substr($m['message'],0,80)).(strlen($m['message'])>80?'…':''); ?></td>
    <td style="font-size:12px;color:var(--text-muted);white-space:nowrap"><?php echo date('d M Y H:i',strtotime($m['created_at'])); ?></td>
    <td style="white-space:nowrap">
        <?php if(!$m['is_read']): ?><a href="?read=<?php echo $m['id']; ?>" class="btn btn-sm btn-outline" title="Mark read"><i class="fas fa-check"></i></a><?php endif; ?>
        <a href="mailto:<?php echo e($m['email']); ?>?subject=Re: <?php echo e(urlencode($m['subject'])); ?>" class="btn btn-sm btn-dark"><i class="fas fa-reply"></i></a>
        <a href="?delete=<?php echo $m['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this message?')"><i class="fas fa-trash"></i></a>
    </td>
</tr>
<?php endwhile; if(!$c): ?><tr><td colspan="8" style="text-align:center;padding:32px;color:var(--text-muted)">No messages yet</td></tr><?php endif; ?>
</tbody>
</table>
</div>
<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
