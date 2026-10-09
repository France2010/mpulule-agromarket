<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_guest(); // already logged-in users are redirected away

$page_title = 'Create Account';
$error      = '';
$fields     = array('firstname'=>'','lastname'=>'','email'=>'','phone'=>'','region'=>'');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname  = trim($_POST['lastname']  ?? '');
    $email     = trim($_POST['email']     ?? '');
    $phone     = trim($_POST['phone']     ?? '');
    $region    = trim($_POST['region']    ?? '');
    $password  =      $_POST['password'] ?? '';
    $confirm   =      $_POST['confirm']  ?? '';

    $fields = array('firstname'=>$firstname,'lastname'=>$lastname,
                    'email'=>$email,'phone'=>$phone,'region'=>$region);

    if (!$firstname || !$lastname || !$email || !$password || !$confirm) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $e_safe = mysqli_real_escape_string($conn, $email);
        $chk    = mysqli_query($conn, "SELECT id FROM users WHERE email='$e_safe' LIMIT 1");

        if ($chk && mysqli_num_rows($chk) > 0) {
            $error = 'This email is already registered. Please <a href="/agro_project/login.php">sign in</a>.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $fn   = mysqli_real_escape_string($conn, $firstname);
            $ln   = mysqli_real_escape_string($conn, $lastname);
            $ph   = mysqli_real_escape_string($conn, $phone);
            $rg   = mysqli_real_escape_string($conn, $region);

            $ok = mysqli_query($conn,
                "INSERT INTO users (firstname,lastname,email,phone,region,password,user_role)
                 VALUES('$fn','$ln','$e_safe','$ph','$rg','$hash','user')");

            if (!$ok) {
                $error = 'Registration failed. Please try again.';
            } else {
                // Fetch new row from DB and auto-login
                $new_id  = (int)mysqli_insert_id($conn);
                $new_row = mysqli_fetch_assoc(
                    mysqli_query($conn,
                        "SELECT id,firstname,lastname,email,user_role
                         FROM users WHERE id=$new_id LIMIT 1"));
                login_user($new_row);
                // Regular users go to home; admins go to admin panel
              header('Location: /agro_project/dashboard.php?welcome=1');
exit;
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<style>
/* Register page specific dark theme */
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
    max-width: 520px;
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
    width: 90px;
    height: 90px;
    border-radius: 50%;
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    border: 2px solid rgba(245, 158, 11, 0.3);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
    box-shadow: 0 0 20px rgba(245, 158, 11, 0.2);
    overflow: hidden;
    position: relative;
}

/* Cycling logo container - fills entire circle */
.logo-cycle-container {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.logo-cycle-item {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.6s ease-in-out;
    border-radius: 50%;
    overflow: hidden;
}

.logo-cycle-item.active {
    opacity: 1;
}

/* Image fills the entire circle */
.logo-cycle-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}

/* Icons fill the circle */
.logo-cycle-item i {
    font-size: 3.2rem;
    color: #f59e0b;
}

/* Text logo fills the entire circle */
.text-logo-cycle {
    text-align: center;
    line-height: 1.3;
    padding: 12px;
    width: 100%;
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.text-logo-cycle .agrumule {
    font-size: 14px;
    font-weight: 900;
    color: #0f172a;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    display: block;
}

.text-logo-cycle .agromarket {
    font-size: 10px;
    font-weight: 700;
    color: #0f172a;
    letter-spacing: 1px;
    display: block;
    margin-top: 4px;
}

/* Alternative text logo style with gradient text on dark bg */
.text-logo-cycle-alt {
    text-align: center;
    line-height: 1.3;
    padding: 12px;
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: #1e293b;
}

.text-logo-cycle-alt .agrumule {
    font-size: 14px;
    font-weight: 900;
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    display: block;
}

.text-logo-cycle-alt .agromarket {
    font-size: 10px;
    font-weight: 700;
    color: #9ca3af;
    letter-spacing: 1px;
    display: block;
    margin-top: 4px;
}

/* Full circle icon backgrounds */
.logo-cycle-item .fa-leaf,
.logo-cycle-item .fa-tractor,
.logo-cycle-item .fa-seedling {
    font-size: 3.5rem;
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

.login-card-box .form-control {
    background: #1e293b;
    border: 1px solid #334155;
    color: #e5e7eb;
    border-radius: 12px;
    padding: 12px 16px;
    transition: all 0.3s ease;
}

.login-card-box .form-control:focus {
    border-color: #f59e0b;
    background: #1e293b;
    box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2);
    outline: none;
}

.login-card-box .form-control::placeholder {
    color: #64748b;
}

.login-card-box .input-icon-wrap .form-control {
    padding-left: 40px;
}

.login-card-box .input-icon-wrap .icon {
    color: #f59e0b;
    left: 14px;
}

/* Form row for two columns */
.form-row-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 0;
}

@media (max-width: 480px) {
    .form-row-2 {
        grid-template-columns: 1fr;
        gap: 0;
    }
}

/* Password match message */
#pwdMsg {
    font-size: 11px;
    margin-top: -8px;
    margin-bottom: 16px;
    padding-left: 4px;
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
}

.login-card-box .alert-danger {
    background: rgba(239, 68, 68, 0.1);
    border-left-color: #ef4444;
    color: #f87171;
}

.login-card-box .alert-danger a {
    color: #fbbf24;
    text-decoration: none;
}

.login-card-box .alert-danger a:hover {
    text-decoration: underline;
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
        width: 75px;
        height: 75px;
    }
    
    .logo-cycle-item i {
        font-size: 2.5rem;
    }
    
    .text-logo-cycle .agrumule {
        font-size: 11px;
    }
    
    .text-logo-cycle .agromarket {
        font-size: 8px;
    }
}

/* Remove number input spinners */
input[type=number]::-webkit-inner-spin-button, 
input[type=number]::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
</style>

<div class="login-page-wrap">
<div class="login-card-box">
    <div style="text-align:center;margin-bottom:28px">
        <div class="logo-wrapper">
            <div class="logo-cycle-container" id="logoCycle">
                <?php 
                $logo_path = '/agro_project/uploads/products/logo.png';
                $logo_full_path = $_SERVER['DOCUMENT_ROOT'] . '/agro_project/uploads/products/logo.png';
                
                if (file_exists($logo_full_path)): 
                ?>
                    <!-- Logo image item - fills entire circle -->
                    <div class="logo-cycle-item active">
                        <img src="<?php echo $logo_path; ?>" alt="Logo">
                    </div>
                    <!-- Text logo item - fills entire circle -->
                    <div class="logo-cycle-item">
                        <div class="text-logo-cycle">
                            <span class="agrumule">AGRUMULE</span>
                            <span class="agromarket">AGROMARKET</span>
                        </div>
                    </div>
                    <!-- Leaf icon - fills entire circle -->
                    <div class="logo-cycle-item">
                        <i class="fas fa-leaf"></i>
                    </div>
                <?php else: ?>
                    <!-- No logo file exists - cycle through text and icons that fill the circle -->
                    <div class="logo-cycle-item active">
                        <div class="text-logo-cycle">
                            <span class="agrumule">AGRUMULE</span>
                            <span class="agromarket">AGROMARKET</span>
                        </div>
                    </div>
                    <div class="logo-cycle-item">
                        <div class="text-logo-cycle-alt">
                            <span class="agrumule">AGRUMULE</span>
                            <span class="agromarket">AGROMARKET</span>
                        </div>
                    </div>
                    <div class="logo-cycle-item">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <div class="logo-cycle-item">
                        <i class="fas fa-tractor"></i>
                    </div>
                    <div class="logo-cycle-item">
                        <i class="fas fa-seedling"></i>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <h2>Create Account</h2>
        <p class="subtitle">Join <?php echo e(get_setting('site_name')); ?> today</p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i>
        <span><?php echo $error; ?></span>
    </div>
    <?php endif; ?>

    <form method="POST" novalidate>
        <div class="form-row-2">
            <div class="form-group">
                <label>First name</label>
                <input type="text" name="firstname" class="form-control" required
                       placeholder="First name" value="<?php echo e($fields['firstname']); ?>">
            </div>
            <div class="form-group">
                <label>Last name</label>
                <input type="text" name="lastname" class="form-control" required
                       placeholder="Last name" value="<?php echo e($fields['lastname']); ?>">
            </div>
        </div>

        <div class="form-group">
            <label>Email address</label>
            <div class="input-icon-wrap">
                <i class="fas fa-envelope icon"></i>
                <input type="email" name="email" class="form-control" required
                       placeholder="you@example.com" value="<?php echo e($fields['email']); ?>">
            </div>
        </div>

        <div class="form-row-2">
            <div class="form-group">
                <label>Phone / WhatsApp</label>
                <input type="tel" name="phone" class="form-control"
                       placeholder="+255 7XX XXX XXX" value="<?php echo e($fields['phone']); ?>">
            </div>
            <div class="form-group">
                <label>Region</label>
                <input type="text" name="region" class="form-control"
                       placeholder="e.g. Mbeya" value="<?php echo e($fields['region']); ?>">
            </div>
        </div>

        <div class="form-row-2">
            <div class="form-group">
                <label>Password</label>
                <div class="input-icon-wrap">
                    <i class="fas fa-lock icon"></i>
                    <input type="password" name="password" id="pwd1" class="form-control"
                           required placeholder="Min 6 characters">
                </div>
            </div>
            <div class="form-group">
                <label>Confirm password</label>
                <div class="input-icon-wrap">
                    <i class="fas fa-lock icon"></i>
                    <input type="password" name="confirm" id="pwd2" class="form-control"
                           required placeholder="Repeat password">
                </div>
            </div>
        </div>
        <p id="pwdMsg" style="font-size:12px;margin-top:-8px;margin-bottom:16px;display:none"></p>

        <button type="submit" class="btn btn-gold btn-block">
            <i class="fas fa-user-plus"></i> Create Account &amp; Sign In
        </button>
    </form>

    <div class="divider">Already have an account?</div>
    <a href="/agro_project/login.php" class="btn btn-outline btn-block">
        <i class="fas fa-sign-in-alt"></i> Sign In
    </a>
</div>
</div>

<script>
var p1 = document.getElementById('pwd1');
var p2 = document.getElementById('pwd2');
var msg = document.getElementById('pwdMsg');
function checkPwd() {
    if (!p2.value) { msg.style.display = 'none'; return; }
    msg.style.display = 'block';
    if (p1.value === p2.value) {
        msg.textContent = '✓ Passwords match';
        msg.style.color = '#10b981';
    } else {
        msg.textContent = '✗ Passwords do not match';
        msg.style.color = '#ef4444';
    }
}
p1.addEventListener('input', checkPwd);
p2.addEventListener('input', checkPwd);

// Logo cycling animation - each item fills the entire circle
const logoItems = document.querySelectorAll('.logo-cycle-item');
let currentIndex = 0;

if (logoItems.length > 1) {
    function cycleLogo() {
        logoItems[currentIndex].classList.remove('active');
        currentIndex = (currentIndex + 1) % logoItems.length;
        logoItems[currentIndex].classList.add('active');
    }
    // Change logo every 3 seconds
    setInterval(cycleLogo, 3000);
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>