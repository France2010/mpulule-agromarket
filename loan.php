<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();
$page_title = 'Agricultural Loan';

// ============================================
// LOAD USER DATA FROM DATABASE
// ============================================
$user_id = (int)$_SESSION['user_id'];
$u = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT id, firstname, lastname, email, phone, region
     FROM users WHERE id=$user_id LIMIT 1"
));

if (!$u) {
    // Safety: session references non-existent user
    logout_user();
    header('Location: /agro_project/login.php');
    exit;
}

// Build full name
$user_fullname = trim(($u['firstname'] ?? '') . ' ' . ($u['lastname'] ?? ''));
$user_email    = $u['email']  ?? '';
$user_phone    = $u['phone']  ?? '';
$user_region   = $u['region'] ?? '';

// Check for missing required fields
$missing_fields = [];
if (empty($user_fullname)) $missing_fields[] = 'Full name';
if (empty($user_email))    $missing_fields[] = 'Email';
if (empty($user_phone))    $missing_fields[] = 'Phone number';

$success = $error = '';

// ============================================
// HANDLE FORM SUBMISSION
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // These come from the database — don't trust POST for these
    $name  = $user_fullname;
    $email = $user_email;
    $phone = $user_phone;

    // These come from the form
    $size    = (float)($_POST['farm_size_acres'] ?? 0);
    $crop    = trim($_POST['primary_crop'] ?? '');
    $prod    = (float)($_POST['annual_prod_kg'] ?? 0);
    $purpose = trim($_POST['loan_purpose'] ?? '');
    $months  = (int)($_POST['repay_months'] ?? 12);
    $amount  = (float)($_POST['requested_amount'] ?? 0);
    $rate    = (float)($_POST['interest_rate'] ?? 0);
    $monthly = (float)($_POST['monthly_payment'] ?? 0);

    // Validation
    if (!empty($missing_fields)) {
        $error = 'Please update your profile first. Missing: ' . implode(', ', $missing_fields) . '. <a href="profile.php" style="color:#fbbf24;text-decoration:underline;">Go to Profile →</a>';
    } elseif (!$size || !$crop || !$purpose || !$amount) {
        $error = 'Please fill in all required fields.';
    } elseif ($amount < 1000) {
        $error = 'Requested amount is too small.';
    } else {
        $ref = gen_ref('LOAN');
        $n_e  = mysqli_real_escape_string($conn, $name);
        $e_e  = mysqli_real_escape_string($conn, $email);
        $ph   = mysqli_real_escape_string($conn, $phone);
        $cr   = mysqli_real_escape_string($conn, $crop);
        $pu   = mysqli_real_escape_string($conn, $purpose);

        $insert = mysqli_query($conn, "INSERT INTO loan_applications 
            (loan_ref, user_id, applicant_name, email, phone, farm_size_acres, primary_crop, annual_prod_kg, loan_purpose, repay_months, requested_amount, interest_rate, monthly_payment)
            VALUES('$ref', $user_id, '$n_e', '$e_e', '$ph', $size, '$cr', $prod, '$pu', $months, $amount, $rate, $monthly)");

        if ($insert) {
            $success = "Your loan application <strong>$ref</strong> has been submitted! Our team will review and contact you within 48 hours.";
        } else {
            $error = 'Could not save application: ' . mysqli_error($conn);
        }
    }
}

// Rates from settings
$rate_small  = (float)get_setting('loan_rate_small',  '12');
$rate_medium = (float)get_setting('loan_rate_medium', '10');
$rate_large  = (float)get_setting('loan_rate_large',  '8');
$max_small   = (float)get_setting('loan_max_small',   '500000');
$max_medium  = (float)get_setting('loan_max_medium',  '2000000');
$max_large   = (float)get_setting('loan_max_large',   '10000000');

require_once __DIR__ . '/includes/header.php';
?>

<style>
.page-header {
    background: linear-gradient(135deg, #0f172a 0%, #0a0f1c 100%);
    border-bottom: 1px solid rgba(245, 158, 11, 0.15);
}
.page-header h1 {
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}
.page-header p { color: #9ca3af; }
.breadcrumb a { color: #fbbf24; }
.breadcrumb a:hover { color: #f59e0b; }
.breadcrumb span { color: #64748b; }

.card {
    background: linear-gradient(135deg, #111827 0%, #0f172a 100%);
    border: 1px solid rgba(245, 158, 11, 0.15);
    border-radius: 16px;
    overflow: hidden;
}
.card-body { padding: 24px; }
.card h3, .card h4 { color: #fbbf24; font-weight: 700; }

.form-group label {
    color: #fbbf24;
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
    display: block;
}
.form-control {
    background: #1e293b;
    border: 1px solid #334155;
    color: #e5e7eb;
    border-radius: 12px;
    padding: 12px 16px;
    width: 100%;
    font-size: 14px;
    transition: all 0.3s;
}
.form-control:focus {
    border-color: #f59e0b;
    box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2);
    outline: none;
}
.form-control::placeholder { color: #64748b; }
select.form-control { cursor: pointer; }
select.form-control option { background: #1e293b; color: #e5e7eb; }

/* Locked (read-only) fields */
.form-control.locked {
    background: #0f172a;
    color: #94a3b8;
    border-color: #1e293b;
    cursor: not-allowed;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%2364748b' viewBox='0 0 16 16'%3E%3Cpath d='M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    background-size: 16px;
    padding-right: 40px;
}

.form-row-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 0;
}
@media (max-width: 640px) {
    .form-row-2 { grid-template-columns: 1fr; gap: 0; }
}

.loan-result {
    background: rgba(245, 158, 11, 0.05);
    border: 1px solid rgba(245, 158, 11, 0.2);
    border-radius: 12px;
    padding: 16px;
    margin-top: 16px;
}
.loan-result h4 { color: #f59e0b !important; margin-bottom: 12px; }
.loan-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #1f2937;
    font-size: 13px;
}
.loan-row:last-child {
    border-bottom: none;
    padding-top: 10px;
    margin-top: 6px;
    border-top: 1px solid rgba(245, 158, 11, 0.2);
    font-weight: 700;
}
.loan-row span:first-child { color: #9ca3af; }
.loan-row span:last-child { color: #fbbf24; font-weight: 600; }

.loan-tier { border-radius: 12px; padding: 14px; margin-bottom: 12px; }
.loan-tier.small  { background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.3); }
.loan-tier.medium { background: rgba(245,158,11,0.1); border: 1px solid rgba(245,158,11,0.3); }
.loan-tier.large  { background: rgba(59,130,246,0.1);  border: 1px solid rgba(59,130,246,0.3); }
.loan-tier .title { font-weight: 700; margin-bottom: 4px; }
.loan-tier.small .title  { color: #34d399; }
.loan-tier.medium .title { color: #fbbf24; }
.loan-tier.large .title  { color: #60a5fa; }
.loan-tier .details { font-size: 12px; color: #9ca3af; }

.required-docs { list-style: none; margin-top: 12px; padding: 0; }
.required-docs li {
    padding: 10px 0;
    border-bottom: 1px solid #1f2937;
    font-size: 13px;
    color: #e5e7eb;
}
.required-docs li:last-child { border-bottom: none; }
.required-docs li i { color: #10b981; margin-right: 10px; }

.btn-gold {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    border: none;
    color: white;
    font-weight: 700;
    padding: 14px;
    border-radius: 12px;
    transition: all 0.3s;
    width: 100%;
    cursor: pointer;
    font-size: 15px;
}
.btn-gold:hover {
    background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(245, 158, 11, 0.3);
}
.btn-gold:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

.alert-success {
    background: rgba(16, 185, 129, 0.1);
    border-left: 4px solid #10b981;
    color: #34d399;
    border-radius: 12px;
    padding: 16px;
}
.alert-danger {
    background: rgba(239, 68, 68, 0.1);
    border-left: 4px solid #ef4444;
    color: #f87171;
    border-radius: 12px;
    padding: 16px;
}

/* Info banner for prefilled fields */
.prefill-notice {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    background: rgba(59, 130, 246, 0.08);
    border: 1px solid rgba(59, 130, 246, 0.2);
    border-radius: 12px;
    padding: 14px 16px;
    margin-bottom: 20px;
    font-size: 13px;
    color: #93c5fd;
}
.prefill-notice i { color: #60a5fa; font-size: 18px; flex-shrink: 0; margin-top: 1px; }
.prefill-notice a { color: #fbbf24; text-decoration: underline; }

/* Missing fields warning */
.missing-warning {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    background: rgba(245, 158, 11, 0.1);
    border: 1px solid rgba(245, 158, 11, 0.3);
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 20px;
    color: #fbbf24;
}
.missing-warning i { font-size: 24px; flex-shrink: 0; }
.missing-warning a {
    color: #fbbf24;
    font-weight: 700;
    text-decoration: underline;
    display: inline-block;
    margin-top: 6px;
}

@media (max-width: 992px) {
    .products-layout { grid-template-columns: 1fr !important; }
}
@media (max-width: 768px) {
    .card-body { padding: 16px; }
}
@media (max-width: 480px) {
    .loan-result { padding: 12px; }
    .loan-row { font-size: 11px; }
}
</style>

<div class="page-header">
    <div class="container">
        <div class="breadcrumb"><a href="index.php">Home</a><span>›</span> Agricultural Loan</div>
        <h1>Agricultural Loan Application</h1>
        <p>Get financing based on your farm size and production capacity</p>
    </div>
</div>

<section class="section">
    <div class="container">
        <?php if ($success): ?>
            <div class="alert alert-success" style="margin-bottom:24px">
                <i class="fas fa-check-circle"></i>
                <div style="margin-top:8px"><?php echo $success; ?></div>
                <div style="margin-top:16px;display:flex;gap:10px;flex-wrap:wrap">
                    <a href="profile.php" class="btn btn-outline" style="padding:10px 20px;border-radius:10px;text-decoration:none;border:1px solid #f59e0b;color:#fbbf24">
                        <i class="fas fa-user"></i> My Profile
                    </a>
                    <a href="products.php" class="btn btn-gold" style="padding:10px 20px;border-radius:10px;text-decoration:none;color:#fff;background:linear-gradient(135deg,#f59e0b,#d97706);border:none">
                        <i class="fas fa-store"></i> Continue Shopping
                    </a>
                </div>
            </div>
        <?php else: ?>

            <?php if ($error): ?>
                <div class="alert alert-danger" style="margin-bottom:24px">
                    <i class="fas fa-exclamation-circle"></i>
                    <span style="margin-left:8px"><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($missing_fields)): ?>
                <!-- Missing profile info warning -->
                <div class="missing-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <strong>Your profile is incomplete.</strong>
                        <p style="margin:6px 0 0 0;font-size:13px;color:#fbbf24;">
                            Missing: <?php echo implode(', ', $missing_fields); ?>. Please update your profile before applying for a loan.
                        </p>
                        <a href="profile.php">Update my profile <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
            <?php endif; ?>

            <div style="display:grid;grid-template-columns:1fr 360px;gap:24px;align-items:start">

                <!-- APPLICATION FORM -->
                <div class="card card-body">
                    <h3 style="margin-bottom:20px">
                        <i class="fas fa-file-signature" style="color:#f59e0b;margin-right:8px"></i>
                        Loan Application Form
                    </h3>

                    <!-- Prefill notice -->
                    <div class="prefill-notice">
                        <i class="fas fa-info-circle"></i>
                        <div>
                            Your <strong>name, email, and phone</strong> were filled automatically from your account.
                            To change these, update your <a href="profile.php">profile</a>.
                        </div>
                    </div>

                    <form method="POST" id="loanForm">
                        <!-- AUTO-FILLED FROM DATABASE (read-only) -->
                        <div class="form-row-2">
                            <div class="form-group">
                                <label><i class="fas fa-user" style="margin-right:4px"></i> Full Name (from your account)</label>
                                <input type="text" name="applicant_name_display" class="form-control locked"
                                       value="<?php echo e($user_fullname); ?>" readonly tabindex="-1">
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-phone" style="margin-right:4px"></i> Phone Number (from your account)</label>
                                <input type="tel" name="phone_display" class="form-control locked"
                                       value="<?php echo e($user_phone ?: 'Not set in profile'); ?>" readonly tabindex="-1">
                            </div>
                        </div>

                        <div class="form-row-2">
                            <div class="form-group">
                                <label><i class="fas fa-envelope" style="margin-right:4px"></i> Email Address (from your account)</label>
                                <input type="email" name="email_display" class="form-control locked"
                                       value="<?php echo e($user_email); ?>" readonly tabindex="-1">
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-map-marker-alt" style="margin-right:4px"></i> Region (from your account)</label>
                                <input type="text" name="region_display" class="form-control locked"
                                       value="<?php echo e($user_region ?: 'Not set in profile'); ?>" readonly tabindex="-1">
                            </div>
                        </div>

                        <!-- Hidden fields carrying the actual values to POST -->
                        <input type="hidden" name="applicant_name" value="<?php echo e($user_fullname); ?>">
                        <input type="hidden" name="email"          value="<?php echo e($user_email); ?>">
                        <input type="hidden" name="phone"          value="<?php echo e($user_phone); ?>">

                        <!-- FARM DETAILS (user fills in) -->
                        <div style="margin-top:24px;padding-top:20px;border-top:1px solid rgba(245,158,11,0.15)">
                            <h4 style="font-size:14px;color:#f59e0b;margin-bottom:14px">
                                <i class="fas fa-seedling"></i> Farm Details
                            </h4>
                        </div>

                        <div class="form-row-2">
                            <div class="form-group">
                                <label>Farm size (acres) *</label>
                                <input type="number" name="farm_size_acres" id="farmSize" class="form-control" required
                                       min="0.5" step="0.5" placeholder="e.g. 5" oninput="calcLoan()">
                            </div>
                            <div class="form-group">
                                <label>Primary crop *</label>
                                <select name="primary_crop" id="cropType" class="form-control" required onchange="calcLoan()">
                                    <option value="">Select crop...</option>
                                    <option>Maize</option>
                                    <option>Rice</option>
                                    <option>Cassava</option>
                                    <option>Sunflower</option>
                                    <option>Tobacco</option>
                                    <option>Coffee</option>
                                    <option>Sugarcane</option>
                                    <option>Beans</option>
                                    <option>Soybeans</option>
                                    <option>Sweet Potato</option>
                                    <option>Vegetables</option>
                                    <option>Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Annual production (kg)</label>
                            <input type="number" name="annual_prod_kg" class="form-control"
                                   placeholder="Previous season harvest in kg">
                        </div>

                        <div class="form-group">
                            <label>Loan purpose *</label>
                            <select name="loan_purpose" class="form-control" required>
                                <option value="">Select purpose...</option>
                                <option>Buy seeds &amp; inputs</option>
                                <option>Purchase equipment</option>
                                <option>Land preparation &amp; ploughing</option>
                                <option>Irrigation setup</option>
                                <option>Post-harvest storage</option>
                                <option>Agro-processing machinery</option>
                                <option>Other</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Repayment period</label>
                            <select name="repay_months" id="repayPeriod" class="form-control" onchange="calcLoan()">
                                <option value="6">6 months</option>
                                <option value="12" selected>12 months</option>
                                <option value="18">18 months</option>
                                <option value="24">24 months</option>
                            </select>
                        </div>

                        <!-- Hidden calculated fields -->
                        <input type="hidden" name="requested_amount" id="h_amount" value="0">
                        <input type="hidden" name="interest_rate"    id="h_rate"   value="0">
                        <input type="hidden" name="monthly_payment"  id="h_monthly" value="0">

                        <div id="loanResult" class="loan-result" style="display:none">
                            <h4><i class="fas fa-calculator" style="color:#f59e0b;margin-right:8px"></i> Loan Estimate</h4>
                            <div class="loan-row">
                                <span>Eligible amount</span>
                                <span id="lr-amount">-</span>
                            </div>
                            <div class="loan-row">
                                <span>Interest rate</span>
                                <span id="lr-rate">-</span>
                            </div>
                            <div class="loan-row">
                                <span>Repayment period</span>
                                <span id="lr-period">-</span>
                            </div>
                            <div class="loan-row">
                                <span>Monthly repayment</span>
                                <span id="lr-monthly">-</span>
                            </div>
                            <div class="loan-row">
                                <span>Total repayable</span>
                                <span id="lr-total">-</span>
                            </div>
                        </div>

                        <button type="submit" class="btn-gold" style="margin-top:16px"
                                <?php echo !empty($missing_fields) ? 'disabled' : ''; ?>>
                            <i class="fas fa-paper-plane"></i>
                            <?php echo !empty($missing_fields) ? 'Complete your profile first' : 'Submit Loan Application'; ?>
                        </button>
                    </form>
                </div>

                <!-- SIDEBAR INFO -->
                <div>
                    <div class="card card-body" style="margin-bottom:20px">
                        <h4 style="margin-bottom:14px">
                            <i class="fas fa-layer-group" style="color:#f59e0b;margin-right:8px"></i>
                            Loan Tiers
                        </h4>
                        <div class="loan-tier small">
                            <div class="title">Small Farm (0.5–2 acres)</div>
                            <div class="details">Up to <?php echo fmt($max_small); ?> · <?php echo $rate_small; ?>% interest p.a.</div>
                        </div>
                        <div class="loan-tier medium">
                            <div class="title">Medium Farm (2–10 acres)</div>
                            <div class="details">Up to <?php echo fmt($max_medium); ?> · <?php echo $rate_medium; ?>% interest p.a.</div>
                        </div>
                        <div class="loan-tier large">
                            <div class="title">Large Farm (10+ acres)</div>
                            <div class="details">Up to <?php echo fmt($max_large); ?> · <?php echo $rate_large; ?>% interest p.a.</div>
                        </div>
                    </div>

                    <div class="card card-body">
                        <h4 style="margin-bottom:14px">
                            <i class="fas fa-file-circle-check" style="color:#f59e0b;margin-right:8px"></i>
                            Required Documents
                        </h4>
                        <ul class="required-docs">
                            <li><i class="fas fa-check"></i> National ID or passport</li>
                            <li><i class="fas fa-check"></i> Farm ownership or lease proof</li>
                            <li><i class="fas fa-check"></i> Previous season harvest records</li>
                            <li><i class="fas fa-check"></i> 2 guarantors (local leaders accepted)</li>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
const rates = {
    small:  <?php echo $rate_small; ?>,
    medium: <?php echo $rate_medium; ?>,
    large:  <?php echo $rate_large; ?>
};
const maxes = {
    small:  <?php echo $max_small; ?>,
    medium: <?php echo $max_medium; ?>,
    large:  <?php echo $max_large; ?>
};
const currency = 'TSh';

function fmtN(n) {
    return currency + ' ' + Math.round(n).toLocaleString();
}

function calcLoan() {
    const size   = parseFloat(document.getElementById('farmSize').value) || 0;
    const period = parseInt(document.getElementById('repayPeriod').value) || 12;

    if (!size) {
        document.getElementById('loanResult').style.display = 'none';
        return;
    }

    let maxAmt, rate;

    if (size <= 2) {
        maxAmt = maxes.small;
        rate   = rates.small / 100;
    } else if (size <= 10) {
        maxAmt = Math.min(maxes.medium, size * 200000);
        rate   = rates.medium / 100;
    } else {
        maxAmt = Math.min(maxes.large, size * 800000);
        rate   = rates.large / 100;
    }

    const totalInterest = maxAmt * rate * (period / 12);
    const totalRepay    = maxAmt + totalInterest;
    const monthly       = totalRepay / period;

    document.getElementById('lr-amount').textContent  = fmtN(maxAmt);
    document.getElementById('lr-rate').textContent    = (rate * 100).toFixed(0) + '% p.a.';
    document.getElementById('lr-period').textContent  = period + ' months';
    document.getElementById('lr-monthly').textContent = fmtN(monthly) + '/month';
    document.getElementById('lr-total').textContent   = fmtN(totalRepay);
    document.getElementById('loanResult').style.display = 'block';

    document.getElementById('h_amount').value  = Math.round(maxAmt);
    document.getElementById('h_rate').value    = (rate * 100).toFixed(2);
    document.getElementById('h_monthly').value = Math.round(monthly);
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>