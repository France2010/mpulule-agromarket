<?php
// ============================================
// API - DISBURSE LOAN TO USER'S MOBILE NUMBER
// ============================================

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../payment_gateways.php';

header('Content-Type: application/json');

api_require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'POST required']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) $input = $_POST;

$loan_id = (int)($input['loan_id'] ?? 0);
if ($loan_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid loan ID']);
    exit;
}

$loan = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM loan_applications WHERE id = $loan_id LIMIT 1"));

if (!$loan) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Loan not found']);
    exit;
}

if (!in_array($loan['status'], ['approved', 'pending'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Loan must be Approved before disbursement. Current: ' . $loan['status']]);
    exit;
}

if (($loan['disbursement_status'] ?? 'none') === 'success') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'This loan has already been disbursed (Ref: ' . ($loan['disbursement_ref'] ?? 'N/A') . ')']);
    exit;
}

$amount = (float)$loan['requested_amount'];
$phone  = trim($loan['phone'] ?? '');

if (empty($phone)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Applicant has no phone number on file']);
    exit;
}

$reference = $loan['loan_ref'] ?? ('LOAN-' . $loan_id);
$phone_esc = mysqli_real_escape_string($conn, $phone);

// Manual mode
if (!defined('AUTO_DISBURSEMENT') || AUTO_DISBURSEMENT !== true) {
    $note = mysqli_real_escape_string($conn, $input['admin_note'] ?? 'Manual disbursement recorded by admin');
    $ref  = mysqli_real_escape_string($conn, $input['manual_ref'] ?? ('MANUAL-' . date('YmdHis')));

    mysqli_query($conn, "
        UPDATE loan_applications
        SET status='disbursed',
            disbursement_status='success',
            disbursement_ref='$ref',
            disbursement_phone='$phone_esc',
            disbursed_at=NOW(),
            disbursement_note='$note'
        WHERE id=$loan_id
    ");

    echo json_encode([
        'success' => true,
        'mode'    => 'manual',
        'message' => 'Loan marked as disbursed (manual mode)',
        'ref'     => $ref,
        'amount'  => $amount,
        'phone'   => $phone,
    ]);
    exit;
}

// ===== LIVE AUTO DISBURSEMENT =====
try {
    mysqli_query($conn, "
        UPDATE loan_applications
        SET disbursement_status='pending',
            disbursement_phone='$phone_esc'
        WHERE id=$loan_id
    ");

  $gateway = new PaymentGateway(PAYMENT_GATEWAY);
$result  = $gateway->disburse([
    'reference'      => $reference,
    'phone'          => $phone,
    'amount'         => $amount,
    'recipient_name' => $loan['applicant_name'] ?? 'Loan Applicant',
]);

    $ref_esc = mysqli_real_escape_string($conn, $result['disbursement_ref']);

    mysqli_query($conn, "
        UPDATE loan_applications
        SET status='disbursed',
            disbursement_status='success',
            disbursement_ref='$ref_esc',
            disbursed_at=NOW(),
            disbursement_note='Auto-disbursed to mobile money'
        WHERE id=$loan_id
    ");

    if (!empty($loan['user_id'])) {
        $msg  = "Loan $reference of TSh " . number_format($amount) . " has been disbursed to $phone.";
        $subj = "Loan $reference — Disbursed";
        $msg_esc   = mysqli_real_escape_string($conn, $msg);
        $subj_esc  = mysqli_real_escape_string($conn, $subj);
        $email_esc = mysqli_real_escape_string($conn, $loan['email'] ?? '');
        $uid = (int)$loan['user_id'];

        mysqli_query($conn, "
            INSERT INTO notifications
                (user_id, email, phone, type, ref_id, ref_code, old_status, new_status,
                 message, subject, channel, created_at)
            VALUES
                ($uid, '$email_esc', '$phone_esc', 'loan', $loan_id, '$reference',
                 'approved', 'disbursed', '$msg_esc', '$subj_esc', 'both', NOW())
        ");
    }

    echo json_encode([
        'success'          => true,
        'mode'             => 'auto',
        'message'          => 'Disbursement sent successfully',
        'disbursement_ref' => $result['disbursement_ref'],
        'amount'           => $amount,
        'phone'            => $phone,
        'raw'              => $result['raw'] ?? null,
    ]);

} catch (Exception $e) {
    $err = mysqli_real_escape_string($conn, $e->getMessage());
    mysqli_query($conn, "
        UPDATE loan_applications
        SET disbursement_status='failed',
            disbursement_note='$err'
        WHERE id=$loan_id
    ");

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Disbursement failed: ' . $e->getMessage()
    ]);
}