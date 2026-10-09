<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();
$page_title = 'My Loans';

$uid = (int)$_SESSION['user_id'];

$loans = [];
$r = mysqli_query($conn,
    "SELECT * FROM loan_applications WHERE user_id=$uid ORDER BY created_at DESC");
while ($row = mysqli_fetch_assoc($r)) $loans[] = $row;

$total_approved = 0; $total_disbursed = 0; $pending = 0;
foreach ($loans as $l) {
    if ($l['status'] === 'approved')  $total_approved  += (float)$l['requested_amount'];
    if ($l['status'] === 'disbursed') $total_disbursed += (float)$l['requested_amount'];
    if ($l['status'] === 'pending')   $pending++;
}

require_once __DIR__ . '/includes/header.php';
?>

<style>
.loans-wrap { background: #0a0f1c; min-height: calc(100vh - 62px); padding: 30px 0 60px; }

.loans-head {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 22px; flex-wrap: wrap; gap: 12px;
}
.loans-head h1 {
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    -webkit-background-clip: text; background-clip: text; color: transparent;
    font-size: 26px; font-weight: 800; margin: 0;
}
.loans-head p { color: #94a3b8; font-size: 13px; margin: 4px 0 0; }

.loans-stats {
    display: grid; grid-template-columns: repeat(3, 1fr);
    gap: 14px; margin-bottom: 22px;
}
@media (max-width: 640px) { .loans-stats { grid-template-columns: 1fr; } }

.loans-stat {
    background: linear-gradient(135deg, #111827 0%, #0f172a 100%);
    border: 1px solid rgba(245,158,11,0.15);
    border-radius: 14px; padding: 16px;
}
.loans-stat .lbl {
    color: #64748b; font-size: 11px; text-transform: uppercase;
    font-weight: 600; letter-spacing: 0.5px; margin-bottom: 6px;
}
.loans-stat .val {
    color: #fbbf24; font-size: 20px; font-weight: 800;
}

.loan-list { display: flex; flex-direction: column; gap: 14px; }

.loan-card {
    background: linear-gradient(135deg, #111827 0%, #0f172a 100%);
    border: 1px solid rgba(245,158,11,0.15);
    border-radius: 16px; padding: 18px 20px;
    text-decoration: none; display: block;
    transition: all 0.25s ease;
}
.loan-card:hover { border-color: rgba(245,158,11,0.4); transform: translateY(-2px); }

.loan-card-top {
    display: flex; justify-content: space-between; align-items: flex-start;
    gap: 12px; margin-bottom: 14px; flex-wrap: wrap;
}
.loan-ref {
    color: #fbbf24; font-weight: 800; font-size: 14px;
    font-family: monospace; letter-spacing: 0.5px;
}
.loan-ref-sub { color: #64748b; font-size: 11px; margin-top: 4px; }

.loan-body {
    display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px;
    padding-top: 14px; border-top: 1px solid rgba(245,158,11,0.08);
}
@media (max-width: 640px) { .loan-body { grid-template-columns: 1fr 1fr; } }
@media (max-width: 400px) { .loan-body { grid-template-columns: 1fr; } }

.loan-field .lbl {
    color: #64748b; font-size: 10px; text-transform: uppercase;
    letter-spacing: 0.5px; font-weight: 600; display: block; margin-bottom: 4px;
}
.loan-field .val { color: #e5e7eb; font-size: 14px; font-weight: 600; }
.loan-field .val.gold { color: #fbbf24; font-size: 16px; font-weight: 800; }

.ds-badge.green  { background: rgba(16,185,129,0.15); color: #34d399; border: 1px solid rgba(16,185,129,0.3); }
.ds-badge.amber  { background: rgba(245,158,11,0.15); color: #fbbf24; border: 1px solid rgba(245,158,11,0.3); }
.ds-badge.red    { background: rgba(239,68,68,0.15);  color: #f87171; border: 1px solid rgba(239,68,68,0.3); }
.ds-badge.blue   { background: rgba(59,130,246,0.15); color: #60a5fa; border: 1px solid rgba(59,130,246,0.3); }
.ds-badge.gray   { background: rgba(100,116,139,0.15); color: #94a3b8; border: 1px solid rgba(100,116,139,0.3); }
.ds-badge {
    display: inline-block; padding: 4px 10px; border-radius: 20px;
    font-size: 10px; font-weight: 700; letter-spacing: 0.3px; text-transform: uppercase;
}

.empty-loans {
    text-align: center; padding: 70px 20px;
    background: linear-gradient(135deg, #111827 0%, #0f172a 100%);
    border: 1px solid rgba(245,158,11,0.15); border-radius: 16px;
}
.empty-loans i { font-size: 56px; color: rgba(245,158,11,0.2); display: block; margin-bottom: 16px; }
.empty-loans h3 { color: #e5e7eb; font-size: 18px; margin: 0 0 8px; }
.empty-loans p { color: #94a3b8; font-size: 13px; margin-bottom: 20px; }
</style>

<div class="loans-wrap">
    <div class="container">
        <div class="loans-head">
            <div>
                <h1><i class="fas fa-hand-holding-usd"></i> My Loans</h1>
                <p>Track your agricultural loan applications</p>
            </div>
            <a href="loan.php" class="btn btn-gold" style="padding:10px 20px;border-radius:10px;text-decoration:none;">
                <i class="fas fa-plus"></i> New Application
            </a>
        </div>

        <?php if (!empty($loans)): ?>
        <div class="loans-stats">
            <div class="loans-stat">
                <div class="lbl">Approved (awaiting)</div>
                <div class="val"><?php echo fmt($total_approved); ?></div>
            </div>
            <div class="loans-stat">
                <div class="lbl">Disbursed (received)</div>
                <div class="val" style="color:#34d399;"><?php echo fmt($total_disbursed); ?></div>
            </div>
            <div class="loans-stat">
                <div class="lbl">Pending Review</div>
                <div class="val" style="color:#fbbf24;"><?php echo $pending; ?></div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (empty($loans)): ?>
            <div class="empty-loans">
                <i class="fas fa-hand-holding-usd"></i>
                <h3>No loan applications yet</h3>
                <p>Apply for financing based on your farm size and production</p>
                <a href="loan.php" class="btn btn-gold" style="padding:12px 28px;border-radius:10px;text-decoration:none;">
                    <i class="fas fa-file-signature"></i> Apply for Loan
                </a>
            </div>
        <?php else: ?>
            <div class="loan-list">
                <?php foreach ($loans as $l):
                    $stMap = ['pending'=>['amber','Pending'],'approved'=>['blue','Approved'],
                              'rejected'=>['red','Rejected'],'disbursed'=>['green','Disbursed']];
                    [$c, $lbl] = $stMap[$l['status']] ?? ['gray', ucfirst($l['status'])];
                ?>
                <a href="loan_detail.php?id=<?php echo (int)$l['id']; ?>" class="loan-card">
                    <div class="loan-card-top">
                        <div>
                            <div class="loan-ref"><?php echo e($l['loan_ref']); ?></div>
                            <div class="loan-ref-sub">
                                <i class="fas fa-calendar"></i>
                                <?php echo date('d M Y', strtotime($l['created_at'])); ?>
                                · <?php echo e($l['primary_crop']); ?> · <?php echo e($l['farm_size_acres']); ?> acres
                            </div>
                        </div>
                        <span class="ds-badge <?php echo $c; ?>"><?php echo $lbl; ?></span>
                    </div>
                    <div class="loan-body">
                        <div class="loan-field">
                            <span class="lbl">Amount</span>
                            <span class="val gold"><?php echo fmt((float)$l['requested_amount']); ?></span>
                        </div>
                        <div class="loan-field">
                            <span class="lbl">Interest</span>
                            <span class="val"><?php echo $l['interest_rate']; ?>%</span>
                        </div>
                        <div class="loan-field">
                            <span class="lbl">Repayment</span>
                            <span class="val"><?php echo $l['repay_months']; ?> months</span>
                        </div>
                        <div class="loan-field">
                            <span class="lbl">Monthly</span>
                            <span class="val"><?php echo fmt((float)$l['monthly_payment']); ?></span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>