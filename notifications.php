<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();
$page_title = 'Notifications';

$uid = (int)$_SESSION['user_id'];

// Mark all as read on page load
if (isset($_GET['mark_all']) && $_GET['mark_all'] === '1') {
    mysqli_query($conn, "UPDATE notifications SET is_read=1 WHERE user_id=$uid");
    header('Location: notifications.php');
    exit;
}

$notifs = [];
$r = mysqli_query($conn,
    "SELECT * FROM notifications WHERE user_id=$uid ORDER BY created_at DESC LIMIT 100");
while ($row = mysqli_fetch_assoc($r)) $notifs[] = $row;

$unread = 0;
foreach ($notifs as $n) if (!$n['is_read']) $unread++;

require_once __DIR__ . '/includes/header.php';
?>

<style>
.notif-wrap { background: #0a0f1c; min-height: calc(100vh - 62px); padding: 30px 0 60px; }
.notif-head {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 22px; flex-wrap: wrap; gap: 12px;
}
.notif-head h1 {
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    -webkit-background-clip: text; background-clip: text; color: transparent;
    font-size: 26px; font-weight: 800; margin: 0;
}
.notif-head p { color: #94a3b8; font-size: 13px; margin: 4px 0 0; }

.notif-list {
    background: linear-gradient(135deg, #111827 0%, #0f172a 100%);
    border: 1px solid rgba(245,158,11,0.15);
    border-radius: 16px; overflow: hidden;
}
.notif-item {
    display: flex; gap: 14px; padding: 16px 20px;
    border-bottom: 1px solid rgba(245,158,11,0.05);
    text-decoration: none;
    transition: background 0.2s;
    position: relative;
}
.notif-item:last-child { border-bottom: none; }
.notif-item:hover { background: rgba(245,158,11,0.04); }
.notif-item.unread { background: rgba(245,158,11,0.03); }
.notif-item.unread::before {
    content: ''; position: absolute; left: 0; top: 0; bottom: 0;
    width: 3px; background: #f59e0b;
}

.notif-icon {
    width: 42px; height: 42px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; flex-shrink: 0;
}
.notif-icon.order { background: rgba(59,130,246,0.12); color: #60a5fa; }
.notif-icon.loan  { background: rgba(168,85,247,0.12); color: #c084fc; }

.notif-body { flex: 1; min-width: 0; }
.notif-body .msg {
    color: #e5e7eb; font-size: 14px; font-weight: 600;
    margin-bottom: 4px; word-wrap: break-word;
}
.notif-body .time { color: #64748b; font-size: 11px; }

.notif-meta {
    display: flex; flex-direction: column; align-items: flex-end;
    gap: 6px; flex-shrink: 0;
}
.notif-meta .ref {
    color: #fbbf24; font-size: 11px; font-family: monospace; font-weight: 700;
}

.ds-badge {
    display: inline-block; padding: 3px 10px; border-radius: 20px;
    font-size: 10px; font-weight: 700; letter-spacing: 0.3px; text-transform: uppercase;
}
.ds-badge.green  { background: rgba(16,185,129,0.15); color: #34d399; border: 1px solid rgba(16,185,129,0.3); }
.ds-badge.amber  { background: rgba(245,158,11,0.15); color: #fbbf24; border: 1px solid rgba(245,158,11,0.3); }
.ds-badge.red    { background: rgba(239,68,68,0.15);  color: #f87171; border: 1px solid rgba(239,68,68,0.3); }
.ds-badge.blue   { background: rgba(59,130,246,0.15); color: #60a5fa; border: 1px solid rgba(59,130,246,0.3); }
.ds-badge.gray   { background: rgba(100,116,139,0.15); color: #94a3b8; border: 1px solid rgba(100,116,139,0.3); }

.empty-notif {
    text-align: center; padding: 70px 20px;
}
.empty-notif i { font-size: 56px; color: rgba(245,158,11,0.15); display: block; margin-bottom: 16px; }
.empty-notif h3 { color: #e5e7eb; font-size: 18px; margin: 0 0 8px; }
.empty-notif p { color: #94a3b8; font-size: 13px; }
</style>

<div class="notif-wrap">
    <div class="container">
        <div class="notif-head">
            <div>
                <h1><i class="fas fa-bell"></i> Notifications</h1>
                <p><?php echo $unread > 0 ? "$unread unread" : 'All caught up'; ?></p>
            </div>
            <?php if ($unread > 0): ?>
                <a href="?mark_all=1" class="btn btn-outline" style="padding:10px 18px;border-radius:10px;text-decoration:none;border:1px solid rgba(245,158,11,0.3);color:#fbbf24;background:transparent;">
                    <i class="fas fa-check-double"></i> Mark all read
                </a>
            <?php endif; ?>
        </div>

        <?php if (empty($notifs)): ?>
            <div class="empty-notif">
                <i class="fas fa-bell-slash"></i>
                <h3>No notifications</h3>
                <p>Updates about your orders and loans will appear here</p>
            </div>
        <?php else: ?>
            <div class="notif-list">
                <?php foreach ($notifs as $n):
                    $stMap = ['approved'=>'green','pending'=>'amber','rejected'=>'red',
                              'processing'=>'blue','shipped'=>'blue','delivered'=>'green',
                              'disbursed'=>'green','paid'=>'green'];
                    $sc = $stMap[$n['new_status']] ?? 'gray';
                ?>
                <a href="notifications.php" class="notif-item <?php echo !$n['is_read'] ? 'unread' : ''; ?>">
                    <div class="notif-icon <?php echo $n['type']; ?>">
                        <i class="fas <?php echo $n['type'] === 'loan' ? 'fa-hand-holding-usd' : 'fa-shopping-bag'; ?>"></i>
                    </div>
                    <div class="notif-body">
                        <div class="msg"><?php echo e($n['message'] ?: 'Notification'); ?></div>
                        <div class="time">
                            <i class="fas fa-clock"></i> <?php echo time_ago($n['created_at']); ?>
                        </div>
                    </div>
                    <div class="notif-meta">
                        <?php if ($n['ref_code']): ?>
                            <span class="ref"><?php echo e($n['ref_code']); ?></span>
                        <?php endif; ?>
                        <?php if ($n['new_status']): ?>
                            <span class="ds-badge <?php echo $sc; ?>"><?php echo ucfirst($n['new_status']); ?></span>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>