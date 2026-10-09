<?php
// ============================================
// API - GET LOAN DETAILS (admin only)
// File: /agro_project/api/get_loan.php
// ============================================

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

// --- API-friendly auth checks (JSON errors, not HTML redirects) ---

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required'
    ]);
    exit;
}

if (!is_admin()) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Admin access required'
    ]);
    exit;
}

// --- Validate input ---

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid loan ID'
    ]);
    exit;
}

// --- Fetch loan ---

$query = "SELECT l.*, u.firstname, u.lastname, u.email, u.phone
          FROM loan_applications l
          LEFT JOIN users u ON l.user_id = u.id
          WHERE l.id = $id
          LIMIT 1";

$result = mysqli_query($conn, $query);

if (!$result) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . mysqli_error($conn)
    ]);
    exit;
}

$loan = mysqli_fetch_assoc($result);

if (!$loan) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'Loan not found'
    ]);
    exit;
}

// --- Success ---

echo json_encode([
    'success' => true,
    'loan'    => $loan
]);