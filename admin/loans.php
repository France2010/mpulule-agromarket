<?php
// ============================================
// ADMIN - LOAN MANAGEMENT (mobile-optimized)
// ============================================

ob_start();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();

$page_title = 'Loan Applications';

// ============================================
// HANDLE POST ACTIONS
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $loanId     = isset($_POST['loan_id'])    ? (int)$_POST['loan_id'] : 0;
        $status     = isset($_POST['status'])     ? sanitize_input($_POST['status']) : '';
        $admin_note = isset($_POST['admin_note']) ? sanitize_input($_POST['admin_note']) : '';

        if ($loanId > 0 && !empty($status)) {
            $loanQuery = mysqli_query($conn,
                "SELECT user_id, applicant_name, email, phone, loan_ref
                 FROM loan_applications WHERE id = $loanId");
            $loan = mysqli_fetch_assoc($loanQuery);

            $stmt = mysqli_prepare($conn,
                "UPDATE loan_applications SET status = ?, admin_note = ? WHERE id = ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "ssi", $status, $admin_note, $loanId);
                mysqli_stmt_execute($stmt);

                if ($loan) {
                    $refCode = $loan['loan_ref'] ?? 'LOAN-' . strtoupper(substr(uniqid(), -8));
                    $message = "Your loan application has been " . strtoupper($status);
                    $subject = "Loan Application " . $refCode . " — Status: " . strtoupper($status);

                    $tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'notifications'");
                    if (mysqli_num_rows($tableCheck) > 0) {
                        $notifyQuery = "INSERT INTO notifications
                            (user_id, email, phone, type, ref_id, ref_code, new_status, message, subject, sent_email, sent_sms, is_read)
                            VALUES ({$loan['user_id']}, '{$loan['email']}', '{$loan['phone']}', 'loan', $loanId, '$refCode', '$status', '$message', '$subject', 0, 0, 0)";
                        @mysqli_query($conn, $notifyQuery);
                    }
                }
            }

            $_SESSION['success'] = "Loan status updated successfully!";
            ob_end_clean();
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    }

    if ($action === 'delete_loan') {
        $loanId = isset($_POST['loan_id']) ? (int)$_POST['loan_id'] : 0;
        if ($loanId > 0) {
            $stmt = mysqli_prepare($conn, "DELETE FROM loan_applications WHERE id = ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $loanId);
                mysqli_stmt_execute($stmt);
            }
            $_SESSION['success'] = "Loan application deleted!";
            ob_end_clean();
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    }
}

// ============================================
// GET DATA
// ============================================
$query = "
    SELECT l.*, u.firstname, u.lastname, u.email, u.phone, u.user_role
    FROM loan_applications l
    LEFT JOIN users u ON l.user_id = u.id
    ORDER BY l.created_at DESC
";
$loans_result = mysqli_query($conn, $query);
$loans = [];
while ($row = mysqli_fetch_assoc($loans_result)) {
    $loans[] = $row;
}

$stats = [];
$stats['total']        = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM loan_applications"))['count'] ?? 0;
$stats['pending']      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM loan_applications WHERE status = 'pending'"))['count'] ?? 0;
$stats['approved']     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM loan_applications WHERE status = 'approved'"))['count'] ?? 0;
$stats['rejected']     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM loan_applications WHERE status = 'rejected'"))['count'] ?? 0;
$stats['disbursed']    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM loan_applications WHERE status = 'disbursed'"))['count'] ?? 0;
$stats['total_amount'] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(requested_amount) as total FROM loan_applications WHERE status = 'approved' OR status = 'disbursed'"))['total'] ?? 0;

$pending_loans = $stats['pending'];

require_once __DIR__ . '/includes/admin_header.php';
?>

<style>
.loans-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}
@media (max-width: 992px) { .loans-stats-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 480px) { .loans-stats-grid { grid-template-columns: 1fr; gap: 12px; } }

.loans-table-wrap { display: block; overflow-x: auto; -webkit-overflow-scrolling: touch; }
.loans-cards { display: none; }
@media (max-width: 768px) {
    .loans-table-wrap { display: none; }
    .loans-cards { display: block; }
}

.loan-card {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    border: 1px solid rgba(245,158,11,0.15);
    border-radius: 14px;
    padding: 16px;
    margin-bottom: 12px;
}
.loan-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 12px;
    padding-bottom: 12px;
    border-bottom: 1px solid rgba(245,158,11,0.1);
}
.loan-card-title { flex: 1; min-width: 0; }
.loan-card-title .name { color: #e5e7eb; font-weight: 700; font-size: 15px; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.loan-card-title .email { color: #94a3b8; font-size: 12px; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 2px; }

.loan-card-body { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 12px; margin-bottom: 14px; }
.loan-card-body .field { min-width: 0; }
.loan-card-body .field-label { color: #64748b; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; display: block; margin-bottom: 3px; }
.loan-card-body .field-value { color: #e5e7eb; font-size: 13px; font-weight: 600; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.loan-card-body .field-value.gold { color: #fbbf24; font-weight: 800; }

.loan-card-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(70px, 1fr));
    gap: 8px;
    padding-top: 12px;
    border-top: 1px solid rgba(245,158,11,0.1);
}
.loan-card-actions .btn-action {
    min-height: 44px;
    padding: 10px 12px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    cursor: pointer;
    border: none;
    white-space: nowrap;
}
.btn-action-view    { background: #334155; color: #e5e7eb; }
.btn-action-approve { background: #10b981; color: #fff; }
.btn-action-reject  { background: #ef4444; color: #fff; }
.btn-action-disburse{ background: #f59e0b; color: #0a0f1c; }
.btn-action-delete  { background: transparent; color: #ef4444; border: 1px solid #ef4444 !important; }

/* Disbursement status badge */
.disb-badge {
    grid-column: 1 / -1;
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 6px;
}
.disb-success { background: rgba(16,185,129,0.15); color: #34d399; border: 1px solid rgba(16,185,129,0.3); }
.disb-failed  { background: rgba(239,68,68,0.15); color: #f87171; border: 1px solid rgba(239,68,68,0.3); }
.disb-pending { background: rgba(245,158,11,0.15); color: #fbbf24; border: 1px solid rgba(245,158,11,0.3); }

#loanModal .modal-box {
    background: linear-gradient(135deg, #111827 0%, #0f172a 100%);
    border: 1px solid rgba(245,158,11,0.2);
    border-radius: 18px;
    width: 92%;
    max-width: 620px;
    max-height: 92vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    animation: slideUpModal 0.25s ease;
}
@keyframes slideUpModal {
    from { transform: translateY(30px); opacity: 0; }
    to   { transform: translateY(0);    opacity: 1; }
}
#loanModal .modal-header-sticky {
    position: sticky;
    top: 0;
    background: inherit;
    padding: 18px 20px;
    border-bottom: 1px solid rgba(245,158,11,0.15);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    z-index: 5;
}
#loanModal .modal-body-scroll { overflow-y: auto; padding: 20px; -webkit-overflow-scrolling: touch; }
#loanModal .modal-close {
    background: none;
    border: none;
    font-size: 26px;
    color: #94a3b8;
    cursor: pointer;
    padding: 6px 10px;
    min-width: 40px;
    min-height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
}

@media (max-width: 480px) {
    #loanModal .modal-box {
        width: 100%;
        max-width: 100%;
        max-height: 100vh;
        border-radius: 18px 18px 0 0;
        height: 100%;
    }
}

.action-sheet-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.75);
    z-index: 10000;
    align-items: flex-end;
    justify-content: center;
    backdrop-filter: blur(4px);
}
.action-sheet-overlay.active { display: flex; }
.action-sheet {
    background: linear-gradient(135deg, #111827 0%, #0f172a 100%);
    border: 1px solid rgba(245,158,11,0.2);
    border-radius: 20px 20px 0 0;
    width: 100%;
    max-width: 520px;
    padding: 22px 20px calc(22px + env(safe-area-inset-bottom, 0px));
    animation: slideUpModal 0.25s ease;
}
.action-sheet h4 { color: #fbbf24; margin: 0 0 6px; font-size: 17px; display: flex; align-items: center; gap: 8px; }
.action-sheet p  { color: #94a3b8; font-size: 13px; margin: 0 0 16px; }
.action-sheet textarea {
    width: 100%;
    padding: 14px;
    background: #1e293b;
    border: 1px solid #334155;
    border-radius: 12px;
    color: #e5e7eb;
    font-size: 15px;
    font-family: inherit;
    resize: vertical;
    min-height: 90px;
    margin-bottom: 14px;
}
.action-sheet textarea:focus {
    outline: none;
    border-color: #f59e0b;
    box-shadow: 0 0 0 3px rgba(245,158,11,0.15);
}
.action-sheet-buttons {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}
.action-sheet-buttons button {
    min-height: 50px;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    border: none;
}
.action-sheet-buttons .btn-cancel { background: #334155; color: #e5e7eb; }
.action-sheet-buttons .btn-confirm { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: #fff; }
.action-sheet-buttons .btn-confirm.danger { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }

.detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px 16px; }
@media (max-width: 480px) { .detail-grid { grid-template-columns: 1fr; } }
.detail-grid .detail-item { min-width: 0; }
.detail-grid .detail-item.full { grid-column: 1 / -1; }
.detail-grid .detail-label {
    color: #64748b;
    font-size: 11px;
    text-transform: uppercase;
    font-weight: 600;
    letter-spacing: 0.5px;
    display: block;
    margin-bottom: 4px;
}
.detail-grid .detail-value {
    color: #e5e7eb;
    font-size: 14px;
    font-weight: 600;
    word-break: break-word;
}
.detail-grid .detail-value.gold { color: #fbbf24; font-size: 16px; font-weight: 800; }

.modal-action-bar {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    padding: 16px 20px;
    border-top: 1px solid rgba(245,158,11,0.15);
    background: inherit;
    position: sticky;
    bottom: 0;
}
.modal-action-bar button {
    min-height: 48px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}
.modal-action-bar .btn-approve { background: #10b981; color: #fff; }
.modal-action-bar .btn-reject  { background: #ef4444; color: #fff; }
.modal-action-bar .btn-disburse { background: #f59e0b; color: #0a0f1c; }
.modal-action-bar .btn-close-m { background: #334155; color: #e5e7eb; grid-column: 1 / -1; }
</style>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible">
        <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="close" onclick="this.parentElement.remove()">&times;</button>
    </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 style="color: var(--admin-gold, #f59e0b);">
            <i class="fas fa-hand-holding-usd"></i> Loan Applications
        </h2>
        <p class="text-muted mb-0">Manage farmer loan applications and approvals</p>
    </div>
    <span class="badge" style="background: var(--admin-gold, #f59e0b); color: #0a0f1c; padding: 8px 16px;">
        <i class="fas fa-calendar-alt"></i> <?php echo date('d M Y'); ?>
    </span>
</div>

<div class="loans-stats-grid">
    <div class="stat-card">
        <div class="icon"><i class="fas fa-hand-holding-usd"></i></div>
        <div class="num"><?php echo number_format($stats['total']); ?></div>
        <div class="lbl">Total Applications</div>
    </div>
    <div class="stat-card">
        <div class="icon"><i class="fas fa-clock" style="color: var(--admin-warning, #f59e0b);"></i></div>
        <div class="num" style="color: var(--admin-warning, #f59e0b);"><?php echo number_format($stats['pending']); ?></div>
        <div class="lbl">Pending Review</div>
    </div>
    <div class="stat-card">
        <div class="icon"><i class="fas fa-check-circle" style="color: var(--admin-success, #10b981);"></i></div>
        <div class="num" style="color: var(--admin-success, #10b981);"><?php echo number_format($stats['approved']); ?></div>
        <div class="lbl">Approved</div>
    </div>
    <div class="stat-card">
        <div class="icon"><i class="fas fa-money-bill-wave" style="color: var(--admin-gold-light, #fbbf24);"></i></div>
        <div class="num" style="color: var(--admin-gold-light, #fbbf24); font-size: 20px;"><?php echo number_format($stats['total_amount']); ?> TZS</div>
        <div class="lbl">Total Amount</div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3><i class="fas fa-list"></i> All Applications</h3>
            <span class="text-muted">Total: <?php echo count($loans); ?></span>
        </div>

        <!-- DESKTOP TABLE -->
        <div class="loans-table-wrap">
            <table class="table" id="loansTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Applicant</th>
                        <th>Amount</th>
                        <th>Purpose</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th style="min-width:150px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($loans)): ?>
                        <?php foreach ($loans as $loan): ?>
                            <tr>
                                <td><?php echo $loan['id']; ?></td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($loan['applicant_name'] ?? ($loan['firstname'] . ' ' . $loan['lastname'])); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($loan['email']); ?></small>
                                    </div>
                                </td>
                                <td><strong style="color: var(--admin-gold, #f59e0b);"><?php echo number_format($loan['requested_amount']); ?> TZS</strong></td>
                                <td><?php echo htmlspecialchars(substr($loan['loan_purpose'] ?? 'N/A', 0, 50)); ?></td>
                                <td>
                                    <?php
                                    $statusClasses = ['pending'=>'badge-warn','approved'=>'badge-success','rejected'=>'badge-danger','disbursed'=>'badge-info'];
                                    $class = $statusClasses[$loan['status']] ?? 'badge-info';
                                    ?>
                                    <span class="badge <?php echo $class; ?>"><?php echo ucfirst($loan['status']); ?></span>
                                    <?php if (($loan['disbursement_status'] ?? 'none') === 'success'): ?>
                                        <br><small style="color:#34d399;font-size:10px;"><i class="fas fa-check"></i> Paid</small>
                                    <?php elseif (($loan['disbursement_status'] ?? 'none') === 'failed'): ?>
                                        <br><small style="color:#f87171;font-size:10px;"><i class="fas fa-times"></i> Payout failed</small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d M Y', strtotime($loan['created_at'])); ?></td>
                                <td>
                                    <div class="d-flex gap-1 flex-wrap">
                                        <button onclick="viewLoan(<?php echo $loan['id']; ?>)" class="btn btn-sm btn-outline" title="View">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($loan['status'] === 'pending'): ?>
                                            <button onclick="approveLoan(<?php echo $loan['id']; ?>)" class="btn btn-sm" style="background:#10b981;color:#fff;" title="Approve">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button onclick="rejectLoan(<?php echo $loan['id']; ?>)" class="btn btn-sm" style="background:#ef4444;color:#fff;" title="Reject">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($loan['status'] === 'approved' && ($loan['disbursement_status'] ?? 'none') !== 'success'): ?>
                                            <button onclick="disburseLoan(<?php echo $loan['id']; ?>)" class="btn btn-sm" style="background:#f59e0b;color:#fff;" title="Send Money">
                                                <i class="fas fa-paper-plane"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button onclick="deleteLoan(<?php echo $loan['id']; ?>)" class="btn btn-sm btn-outline" style="color:#ef4444;" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="fas fa-hand-holding-usd" style="font-size:48px; color: rgba(245,158,11,0.15);"></i>
                                <p class="text-muted mt-3">No loan applications found</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- MOBILE CARDS -->
        <div class="loans-cards">
            <?php if (!empty($loans)): ?>
                <?php foreach ($loans as $loan): 
                    $statusClasses = ['pending'=>'badge-warn','approved'=>'badge-success','rejected'=>'badge-danger','disbursed'=>'badge-info'];
                    $class = $statusClasses[$loan['status']] ?? 'badge-info';
                    $displayName = $loan['applicant_name'] ?? trim(($loan['firstname'] ?? '') . ' ' . ($loan['lastname'] ?? ''));
                    $disbStatus = $loan['disbursement_status'] ?? 'none';
                ?>
                    <div class="loan-card">
                        <div class="loan-card-header">
                            <div class="loan-card-title">
                                <span class="name"><?php echo htmlspecialchars($displayName); ?></span>
                                <span class="email"><?php echo htmlspecialchars($loan['email']); ?></span>
                            </div>
                            <span class="badge <?php echo $class; ?>"><?php echo ucfirst($loan['status']); ?></span>
                        </div>

                        <div class="loan-card-body">
                            <div class="field">
                                <span class="field-label">Amount</span>
                                <span class="field-value gold"><?php echo number_format($loan['requested_amount']); ?> TZS</span>
                            </div>
                            <div class="field">
                                <span class="field-label">Phone</span>
                                <span class="field-value"><?php echo htmlspecialchars($loan['phone'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="field" style="grid-column: 1 / -1;">
                                <span class="field-label">Purpose</span>
                                <span class="field-value"><?php echo htmlspecialchars($loan['loan_purpose'] ?? 'N/A'); ?></span>
                            </div>
                        </div>

                        <div class="loan-card-actions">
                            <?php if ($disbStatus === 'success'): ?>
                                <div class="disb-badge disb-success">
                                    <i class="fas fa-check-circle"></i> Disbursed
                                    <?php if (!empty($loan['disbursement_ref'])): ?>
                                        — <?php echo htmlspecialchars(substr($loan['disbursement_ref'], 0, 20)); ?>
                                    <?php endif; ?>
                                </div>
                            <?php elseif ($disbStatus === 'failed'): ?>
                                <div class="disb-badge disb-failed">
                                    <i class="fas fa-exclamation-circle"></i> Payout failed — retry or verify phone
                                </div>
                            <?php elseif ($disbStatus === 'pending'): ?>
                                <div class="disb-badge disb-pending">
                                    <i class="fas fa-clock"></i> Disbursement pending...
                                </div>
                            <?php endif; ?>

                            <button onclick="viewLoan(<?php echo $loan['id']; ?>)" class="btn-action btn-action-view">
                                <i class="fas fa-eye"></i> View
                            </button>
                            <?php if ($loan['status'] === 'pending'): ?>
                                <button onclick="approveLoan(<?php echo $loan['id']; ?>)" class="btn-action btn-action-approve">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                                <button onclick="rejectLoan(<?php echo $loan['id']; ?>)" class="btn-action btn-action-reject">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                            <?php elseif ($loan['status'] === 'approved' && $disbStatus !== 'success'): ?>
                                <button onclick="disburseLoan(<?php echo $loan['id']; ?>)" class="btn-action btn-action-disburse">
                                    <i class="fas fa-paper-plane"></i> Send Money
                                </button>
                            <?php endif; ?>
                            <button onclick="deleteLoan(<?php echo $loan['id']; ?>)" class="btn-action btn-action-delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align:center; padding:40px 20px;">
                    <i class="fas fa-hand-holding-usd" style="font-size:48px; color: rgba(245,158,11,0.15);"></i>
                    <p class="text-muted mt-3">No loan applications found</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- MODAL -->
<div id="loanModal" style="display:none; position:fixed; inset:0; z-index:999; background: rgba(0,0,0,0.75); align-items:center; justify-content:center; backdrop-filter: blur(4px);">
    <div class="modal-box">
        <div class="modal-header-sticky">
            <h3 style="color: #fbbf24; font-size: 16px; margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-file-invoice"></i> Loan Details
            </h3>
            <button onclick="closeModal()" class="modal-close">&times;</button>
        </div>
        <div class="modal-body-scroll" id="modalContent">
            <div style="text-align:center; padding:30px;">
                <i class="fas fa-spinner fa-spin" style="font-size:30px; color:#f59e0b;"></i>
                <p style="color:#9ca3af; margin-top:10px;">Loading...</p>
            </div>
        </div>
    </div>
</div>

<!-- ACTION SHEET -->
<div class="action-sheet-overlay" id="actionSheet">
    <div class="action-sheet">
        <h4 id="actionSheetTitle"><i class="fas fa-edit"></i> Enter note</h4>
        <p id="actionSheetDesc">Optional note for the applicant</p>
        <textarea id="actionSheetNote" placeholder="Type a note..."></textarea>
        <div class="action-sheet-buttons">
            <button class="btn-cancel" onclick="closeActionSheet()">Cancel</button>
            <button class="btn-confirm" id="actionSheetConfirm">Confirm</button>
        </div>
    </div>
</div>

<script>
function openModal() {
    document.getElementById('loanModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeModal() {
    document.getElementById('loanModal').style.display = 'none';
    document.body.style.overflow = '';
}

function viewLoan(id) {
    document.getElementById('modalContent').innerHTML = `
        <div style="text-align:center; padding:30px;">
            <i class="fas fa-spinner fa-spin" style="font-size:30px; color:#f59e0b;"></i>
            <p style="color:#9ca3af; margin-top:10px;">Loading loan details...</p>
        </div>`;
    openModal();

    fetch('/agro_project/api/get_loan.php?id=' + id)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const loan = data.loan;
                const statusClasses = {pending:'badge-warn',approved:'badge-success',rejected:'badge-danger',disbursed:'badge-info'};
                const statusBadge = statusClasses[loan.status] || 'badge-info';
                const applicant = loan.applicant_name || (loan.firstname + ' ' + (loan.lastname || ''));
                const disbStatus = loan.disbursement_status || 'none';

                let actionsHTML = '';
                if (loan.status === 'pending') {
                    actionsHTML = `
                        <button onclick="closeModal(); approveLoan(${loan.id});" class="btn-approve">
                            <i class="fas fa-check"></i> Approve
                        </button>
                        <button onclick="closeModal(); rejectLoan(${loan.id});" class="btn-reject">
                            <i class="fas fa-times"></i> Reject
                        </button>
                        <button onclick="closeModal()" class="btn-close-m">Close</button>
                    `;
                } else if (loan.status === 'approved' && disbStatus !== 'success') {
                    actionsHTML = `
                        <button onclick="closeModal(); disburseLoan(${loan.id});" class="btn-disburse" style="grid-column: 1 / -1;">
                            <i class="fas fa-paper-plane"></i> Send Money to ${loan.phone || 'Applicant'}
                        </button>
                        <button onclick="closeModal()" class="btn-close-m">Close</button>
                    `;
                } else {
                    actionsHTML = `<button onclick="closeModal()" class="btn-close-m">Close</button>`;
                }

                let disbInfoHTML = '';
                if (disbStatus === 'success') {
                    disbInfoHTML = `
                        <div class="detail-item full" style="background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.3);border-radius:10px;padding:12px;">
                            <span class="detail-label" style="color:#34d399;">✓ Disbursed</span>
                            <span class="detail-value" style="color:#34d399;font-size:12px;">
                                Ref: ${loan.disbursement_ref || 'N/A'}<br>
                                ${loan.disbursed_at ? new Date(loan.disbursed_at).toLocaleString() : ''}
                            </span>
                        </div>
                    `;
                } else if (disbStatus === 'failed') {
                    disbInfoHTML = `
                        <div class="detail-item full" style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:10px;padding:12px;">
                            <span class="detail-label" style="color:#f87171;">✗ Payout failed</span>
                            <span class="detail-value" style="color:#f87171;font-size:12px;">${loan.disbursement_note || 'Unknown error'}</span>
                        </div>
                    `;
                }

                document.getElementById('modalContent').innerHTML = `
                    <div style="margin-bottom: 16px;">
                        <span class="badge ${statusBadge}" style="font-size: 12px; padding: 6px 14px;">${loan.status.toUpperCase()}</span>
                        <span style="color: #64748b; font-size: 13px; margin-left: 8px;">Ref #${loan.id}</span>
                    </div>
                    <div class="detail-grid">
                        <div class="detail-item full">
                            <span class="detail-label">Applicant</span>
                            <span class="detail-value">${applicant}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Email</span>
                            <span class="detail-value" style="font-size:13px;">${loan.email || 'N/A'}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Phone</span>
                            <span class="detail-value">${loan.phone || 'N/A'}</span>
                        </div>
                        <div class="detail-item full">
                            <span class="detail-label">Amount Requested</span>
                            <span class="detail-value gold">${Number(loan.requested_amount).toLocaleString()} TZS</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Monthly Payment</span>
                            <span class="detail-value">${Number(loan.monthly_payment || 0).toLocaleString()} TZS</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Interest Rate</span>
                            <span class="detail-value">${loan.interest_rate || 0}%</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Repayment</span>
                            <span class="detail-value">${loan.repay_months || 0} months</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Farm Size</span>
                            <span class="detail-value">${loan.farm_size_acres || 'N/A'} acres</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Primary Crop</span>
                            <span class="detail-value">${loan.primary_crop || 'N/A'}</span>
                        </div>
                        <div class="detail-item full">
                            <span class="detail-label">Purpose</span>
                            <span class="detail-value">${loan.loan_purpose || 'N/A'}</span>
                        </div>
                        ${loan.admin_note ? `
                            <div class="detail-item full">
                                <span class="detail-label">Admin Note</span>
                                <span class="detail-value" style="color:#fbbf24;">${loan.admin_note}</span>
                            </div>
                        ` : ''}
                        ${disbInfoHTML}
                        <div class="detail-item full">
                            <span class="detail-label">Applied On</span>
                            <span class="detail-value">${new Date(loan.created_at).toLocaleString()}</span>
                        </div>
                    </div>
                    <div class="modal-action-bar" style="margin: 20px -20px -20px;">
                        ${actionsHTML}
                    </div>
                `;
            } else {
                document.getElementById('modalContent').innerHTML = `
                    <div style="text-align:center; padding:30px;">
                        <i class="fas fa-exclamation-circle" style="font-size:30px; color:#ef4444;"></i>
                        <p style="color:#9ca3af; margin-top:10px;">${data.message || 'Failed to load loan details'}</p>
                        <button onclick="closeModal()" style="margin-top:16px;padding:12px 24px;border-radius:12px;background:#334155;color:#e5e7eb;border:none;cursor:pointer;">Close</button>
                    </div>`;
            }
        })
        .catch(err => {
            console.error(err);
            document.getElementById('modalContent').innerHTML = `
                <div style="text-align:center; padding:30px;">
                    <i class="fas fa-exclamation-triangle" style="font-size:30px; color:#ef4444;"></i>
                    <p style="color:#9ca3af; margin-top:10px;">Failed to load loan details</p>
                    <button onclick="closeModal()" style="margin-top:16px;padding:12px 24px;border-radius:12px;background:#334155;color:#e5e7eb;border:none;cursor:pointer;">Close</button>
                </div>`;
        });
}

/* ---------- Action sheet ---------- */
let actionSheetCallback = null;

function openActionSheet({title, desc, placeholder, confirmText, danger, callback}) {
    document.getElementById('actionSheetTitle').innerHTML = `<i class="fas fa-edit"></i> ${title}`;
    document.getElementById('actionSheetDesc').textContent = desc || '';
    document.getElementById('actionSheetNote').placeholder = placeholder || 'Type a note...';
    document.getElementById('actionSheetNote').value = '';
    const btn = document.getElementById('actionSheetConfirm');
    btn.textContent = confirmText || 'Confirm';
    btn.classList.toggle('danger', !!danger);
    actionSheetCallback = callback;
    document.getElementById('actionSheet').classList.add('active');
    setTimeout(() => document.getElementById('actionSheetNote').focus(), 200);
}

function closeActionSheet() {
    document.getElementById('actionSheet').classList.remove('active');
    actionSheetCallback = null;
}

document.getElementById('actionSheetConfirm').addEventListener('click', function() {
    const val = document.getElementById('actionSheetNote').value.trim();
    if (actionSheetCallback) actionSheetCallback(val);
    closeActionSheet();
});

/* ---------- Submit helper ---------- */
function submitLoanAction(loanId, status, note) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="loan_id" value="${loanId}">
        <input type="hidden" name="status" value="${status}">
        <input type="hidden" name="admin_note" value="${(note || '').replace(/"/g, '&quot;')}">
    `;
    document.body.appendChild(form);
    form.submit();
}

/* ---------- Public actions ---------- */
function approveLoan(id) {
    openActionSheet({
        title: 'Approve Loan',
        desc: 'Add an optional note for the applicant',
        placeholder: 'e.g. Approved — disbursement within 48 hrs',
        confirmText: 'Approve',
        danger: false,
        callback: (note) => submitLoanAction(id, 'approved', note)
    });
}

function rejectLoan(id) {
    openActionSheet({
        title: 'Reject Loan',
        desc: 'Please provide a reason (shown to the applicant)',
        placeholder: 'e.g. Insufficient documentation',
        confirmText: 'Reject',
        danger: true,
        callback: (note) => submitLoanAction(id, 'rejected', note)
    });
}

/* ---------- ⚡ DISBURSE — sends money via API ---------- */
function disburseLoan(id) {
    openActionSheet({
        title: 'Send Money to Applicant',
        desc: 'The loan amount will be sent to the applicant\'s mobile money account',
        placeholder: 'Optional note (e.g. "Disbursed via M-Pesa")',
        confirmText: 'Send Money',
        danger: false,
        callback: (note) => {
            document.getElementById('modalContent').innerHTML = `
                <div style="text-align:center; padding:40px 20px;">
                    <i class="fas fa-spinner fa-spin" style="font-size:40px; color:#f59e0b;"></i>
                    <p style="color:#fbbf24; margin-top:16px; font-weight:600;">Sending money to applicant...</p>
                    <p style="color:#64748b; font-size:12px; margin-top:8px;">Please do not close this window</p>
                </div>
            `;
            openModal();

            fetch('/agro_project/api/loan_disburse.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({loan_id: id, admin_note: note})
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('modalContent').innerHTML = `
                        <div style="text-align:center; padding:40px 20px;">
                            <i class="fas fa-check-circle" style="font-size:60px; color:#10b981;"></i>
                            <h3 style="color:#10b981; margin-top:16px;">Sent Successfully!</h3>
                            <div style="background:rgba(16,185,129,0.1); border:1px solid rgba(16,185,129,0.3); border-radius:12px; padding:16px; margin-top:20px; text-align:left;">
                                <div style="color:#94a3b8; font-size:11px; text-transform:uppercase; margin-bottom:4px;">Amount Sent</div>
                                <div style="color:#fbbf24; font-size:22px; font-weight:800; margin-bottom:14px;">TSh ${Number(data.amount).toLocaleString()}</div>
                                <div style="color:#94a3b8; font-size:11px; text-transform:uppercase; margin-bottom:4px;">Recipient</div>
                                <div style="color:#e5e7eb; font-size:14px; font-weight:600; margin-bottom:14px;">${data.phone}</div>
                                <div style="color:#94a3b8; font-size:11px; text-transform:uppercase; margin-bottom:4px;">Reference</div>
                                <div style="color:#e5e7eb; font-size:13px; font-family:monospace; word-break:break-all;">${data.disbursement_ref || data.ref}</div>
                            </div>
                            <button onclick="location.reload()" style="margin-top:20px; padding:14px 32px; border-radius:12px; border:none; background:linear-gradient(135deg,#f59e0b,#d97706); color:#fff; font-weight:700; cursor:pointer; font-size:15px;">Refresh Page</button>
                        </div>
                    `;
                } else {
                    document.getElementById('modalContent').innerHTML = `
                        <div style="text-align:center; padding:40px 20px;">
                            <i class="fas fa-times-circle" style="font-size:60px; color:#ef4444;"></i>
                            <h3 style="color:#ef4444; margin-top:16px;">Disbursement Failed</h3>
                            <p style="color:#9ca3af; margin-top:12px; font-size:14px;">${data.message || 'Unknown error'}</p>
                            <button onclick="location.reload()" style="margin-top:20px; padding:14px 32px; border-radius:12px; border:none; background:#334155; color:#e5e7eb; font-weight:700; cursor:pointer; font-size:15px;">Close & Retry</button>
                        </div>
                    `;
                }
            })
            .catch(err => {
                console.error(err);
                document.getElementById('modalContent').innerHTML = `
                    <div style="text-align:center; padding:40px 20px;">
                        <i class="fas fa-exclamation-triangle" style="font-size:60px; color:#ef4444;"></i>
                        <h3 style="color:#ef4444; margin-top:16px;">Network Error</h3>
                        <p style="color:#9ca3af; margin-top:12px;">Could not reach the server.</p>
                        <button onclick="location.reload()" style="margin-top:20px; padding:14px 32px; border-radius:12px; border:none; background:#334155; color:#e5e7eb; font-weight:700; cursor:pointer; font-size:15px;">Close</button>
                    </div>
                `;
            });
        }
    });
}

function deleteLoan(id) {
    openActionSheet({
        title: 'Delete Loan Application',
        desc: 'This action cannot be undone.',
        placeholder: 'Optional note (not saved)',
        confirmText: 'Delete',
        danger: true,
        callback: () => {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete_loan">
                <input type="hidden" name="loan_id" value="${id}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });
}

/* ---------- Modal backdrop / escape ---------- */
document.getElementById('loanModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
document.getElementById('actionSheet').addEventListener('click', function(e) {
    if (e.target === this) closeActionSheet();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
        closeActionSheet();
    }
});
</script>

<?php
require_once __DIR__ . '/includes/admin_footer.php';
ob_end_flush();
?>