<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_guest(); // kick out already-logged-in users

$page_title = 'Sign In';
$error      = '';
$email_val  = '';

// ?next= destination after login
$next = isset($_GET['next']) ? trim($_GET['next']) : '';
// Whitelist next to only allow /agro_project/ paths
if (!preg_match('#^/agro_project/#', $next)) $next = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password =      $_POST['password'] ?? '';
    $email_val = $email;
    $next     = trim($_POST['next']     ?? '');
    if (!preg_match('#^/agro_project/#', $next)) $next = '';

    if (!$email || !$password) {
        $error = 'Please enter your email and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $e_safe = mysqli_real_escape_string($conn, $email);
        $res    = mysqli_query($conn,
            "SELECT id,firstname,lastname,email,password,user_role
             FROM users WHERE email='$e_safe' LIMIT 1");
        $row = ($res && mysqli_num_rows($res) > 0) ? mysqli_fetch_assoc($res) : null;

        if ($row && password_verify($password, $row['password'])) {
            login_user($row);

            // Role-based redirect
            if ($row['user_role'] === 'admin') {
                header('Location: /agro_project/admin/index.php');
            } elseif ($next !== '') {
                header('Location: ' . $next);
            } // Redirect based on role
if ($row['user_role'] === 'admin') {
    header('Location: /agro_project/admin/index.php');
} else {
    header('Location: /agro_project/dashboard.php');
}
exit;
        } else {
            $error = 'Incorrect email or password. Please try again.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<style>
/* Login page specific dark theme */
.login-page-wrap {
    background: linear-gradient(135deg, #0a0f1c 0%, #0c1122 100%);
    min-height: calc(100vh - 62px);
}

.login-card-box {
    background: linear-gradient(135deg, #111827 0%, #0f172a 100%);
    border: 1px solid rgba(245, 158, 11, 0.2);
    border-radius: 24px;
    padding: 38px 32px;
    width: 100%;
    max-width: 440px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    position: relative;
    overflow: hidden;
}

.login-card-box::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 3px;
    background: linear-gradient(90deg, #f59e0b, #fbbf24, #f59e0b);
}

.login-card-box .logo-wrapper {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    border: 2px solid rgba(245, 158, 11, 0.3);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
    box-shadow: 0 0 20px rgba(245, 158, 11, 0.2);
    overflow: hidden;
}

.login-card-box .logo-wrapper img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.login-card-box i.fa-leaf {
    font-size: 2rem;
    color: #f59e0b;
}

.login-card-box h2 {
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    font-weight: 800;
    font-size: 1.8rem;
    margin-bottom: 6px;
}

.login-card-box .subtitle {
    color: #9ca3af;
    font-size: 13px;
}

/* Form styling */
.login-card-box .form-group label {
    color: #fbbf24;
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

.login-card-box .input-icon-wrap .form-control {
    background: #1e293b;
    border: 1px solid #334155;
    color: #e5e7eb;
    border-radius: 12px;
    padding: 12px 16px 12px 40px;
    transition: all 0.3s ease;
}

.login-card-box .input-icon-wrap .form-control:focus {
    border-color: #f59e0b;
    background: #1e293b;
    box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2);
    outline: none;
}

.login-card-box .input-icon-wrap .form-control::placeholder {
    color: #64748b;
}

.login-card-box .input-icon-wrap .icon {
    color: #f59e0b;
    left: 14px;
}

/* Toggle password eye */
.login-card-box #togglePwd {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: #64748b;
}

.login-card-box #togglePwd:hover {
    color: #fbbf24;
}

/* Button styling */
.login-card-box .btn-gold {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    border: none;
    color: white;
    font-weight: 700;
    padding: 12px;
    border-radius: 12px;
    font-size: 14px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
    width: 100%;
    cursor: pointer;
}

.login-card-box .btn-gold:hover {
    background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(245, 158, 11, 0.4);
}

.login-card-box .btn-outline {
    background: transparent;
    border: 1px solid rgba(245, 158, 11, 0.4);
    color: #fbbf24;
    border-radius: 12px;
    padding: 12px;
    font-weight: 600;
    transition: all 0.3s ease;
    text-align: center;
    display: block;
    text-decoration: none;
}

.login-card-box .btn-outline:hover {
    background: rgba(245, 158, 11, 0.1);
    border-color: #f59e0b;
    transform: translateY(-2px);
}

/* Divider styling */
.divider {
    display: flex;
    align-items: center;
    text-align: center;
    color: #64748b;
    font-size: 12px;
    margin: 24px 0 20px;
}

.divider::before,
.divider::after {
    content: '';
    flex: 1;
    border-bottom: 1px solid #334155;
}

.divider::before {
    margin-right: 16px;
}

.divider::after {
    margin-left: 16px;
}

/* Alert styling */
.login-card-box .alert {
    border-radius: 12px;
    padding: 12px 16px;
    font-size: 13px;
    margin-bottom: 20px;
    border-left: 3px solid;
    display: flex;
    align-items: center;
    gap: 10px;
}

.login-card-box .alert-danger {
    background: rgba(239, 68, 68, 0.1);
    border-left-color: #ef4444;
    color: #f87171;
}

.login-card-box .alert-success {
    background: rgba(16, 185, 129, 0.1);
    border-left-color: #10b981;
    color: #34d399;
}

.login-card-box .alert-warn {
    background: rgba(245, 158, 11, 0.1);
    border-left-color: #f59e0b;
    color: #fbbf24;
}

.login-card-box .alert-info {
    background: rgba(59, 130, 246, 0.1);
    border-left-color: #3b82f6;
    color: #60a5fa;
}

/* Additional subtle details */
.login-card-box .form-group {
    margin-bottom: 20px;
    position: relative;
}

/* Responsive */
@media (max-width: 480px) {
    .login-card-box {
        padding: 28px 20px;
        margin: 0 16px;
    }
    
    .login-card-box h2 {
        font-size: 1.5rem;
    }
    
    .login-card-box .logo-wrapper {
        width: 65px;
        height: 65px;
    }
}
</style>

<div class="login-page-wrap">
<div class="login-card-box">
    <div style="text-align:center;margin-bottom:28px">
        <div class="logo-wrapper">
            <?php 
            $logo_path = '/agro_project/uploads/products/logo.png';
            $logo_full_path = $_SERVER['DOCUMENT_ROOT'] . '/agro_project/uploads/products/logo.png';
            if (file_exists($logo_full_path)): 
            ?>
                <img src="<?php echo $logo_path; ?>" alt="Logo">
            <?php else: ?>
                <i class="fas fa-leaf"></i>
            <?php endif; ?>
        </div>
        <h2>Welcome Back</h2>
        <p class="subtitle">Sign in to <?php echo e(get_setting('site_name')); ?></p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i>
        <span><?php echo e($error); ?></span>
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['registered'])): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <span>Account created! Please sign in below.</span>
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'unauthorized'): ?>
    <div class="alert alert-warn">
        <i class="fas fa-shield-halved"></i>
        <span>You do not have permission to access that page.</span>
    </div>
    <?php endif; ?>

    <?php if ($next !== ''): ?>
    <div class="alert alert-info">
        <i class="fas fa-lock"></i>
        <span>Please sign in to continue.</span>
    </div>
    <?php endif; ?>

    <form method="POST" novalidate>
        <input type="hidden" name="next" value="<?php echo e($next); ?>">

        <div class="form-group">
            <label>Email address</label>
            <div class="input-icon-wrap">
                <i class="fas fa-envelope icon"></i>
                <input type="email" name="email" class="form-control" required
                       placeholder="you@example.com"
                       value="<?php echo e($email_val); ?>" autofocus>
            </div>
        </div>

        <div class="form-group">
            <label>Password</label>
            <div class="input-icon-wrap">
                <i class="fas fa-lock icon"></i>
                <input type="password" name="password" id="pwd" class="form-control"
                       required placeholder="Your password">
                <span id="togglePwd">
                    <i class="fas fa-eye" id="eyeIcon"></i>
                </span>
            </div>
        </div>

        <button type="submit" class="btn-gold">
            <i class="fas fa-sign-in-alt"></i> Sign In
        </button>
    </form>

    <div class="divider">New to <?php echo e(get_setting('site_name')); ?>?</div>
    <a href="/agro_project/register.php" class="btn-outline">
        <i class="fas fa-user-plus"></i> Create a free account
    </a>
</div>
</div>

<script>
document.getElementById('togglePwd').addEventListener('click', function () {
    const p = document.getElementById('pwd');
    const i = document.getElementById('eyeIcon');
    if (p.type === 'password') {
        p.type = 'text';
        i.classList.remove('fa-eye');
        i.classList.add('fa-eye-slash');
    } else {
        p.type = 'password';
        i.classList.remove('fa-eye-slash');
        i.classList.add('fa-eye');
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>