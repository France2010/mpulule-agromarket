<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();
$page_title = 'Contact Us';

$success = $error = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!$name||!$email||!$subject||!$message) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email,FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $n = mysqli_real_escape_string($conn,$name);
        $e = mysqli_real_escape_string($conn,$email);
        $p = mysqli_real_escape_string($conn,$phone);
        $s = mysqli_real_escape_string($conn,$subject);
        $m = mysqli_real_escape_string($conn,$message);
        mysqli_query($conn,"INSERT INTO contact_messages (name,email,phone,subject,message) VALUES('$n','$e','$p','$s','$m')");
        $success = 'Thank you! Your message has been sent. We\'ll reply within 24 hours.';
    }
}

$contact_email = get_setting('contact_email','mpululefransisco@gmail.com');
$whatsapp      = get_setting('whatsapp_number','+255700000000');
$address       = get_setting('contact_address','Tanzania');
$hours         = get_setting('working_hours','Mon–Sat 7AM–6PM EAT');

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <div class="breadcrumb"><a href="index.php">Home</a><span>›</span> Contact</div>
        <h1>Contact Us</h1>
        <p>We're here to help farmers and agribusinesses succeed</p>
    </div>
</div>

<section class="section">
    <div class="container">
        <div class="grid-4" style="margin-bottom:36px">
            <div class="card card-body" style="text-align:center">
                <i class="fas fa-envelope" style="font-size:1.8rem;color:var(--gold);margin-bottom:10px;display:block"></i>
                <div style="font-weight:700;margin-bottom:4px">Email us</div>
                <a href="mailto:<?php echo e($contact_email); ?>" style="font-size:13px;color:var(--gold-dark);word-break:break-all"><?php echo e($contact_email); ?></a>
                <div style="font-size:11px;color:var(--text-muted);margin-top:4px">Reply within 24 hours</div>
            </div>
            <div class="card card-body" style="text-align:center">
                <i class="fab fa-whatsapp" style="font-size:1.8rem;color:var(--gold);margin-bottom:10px;display:block"></i>
                <div style="font-weight:700;margin-bottom:4px">WhatsApp</div>
                <a href="https://wa.me/<?php echo preg_replace('/\D/','',$whatsapp); ?>" target="_blank" style="font-size:13px;color:var(--gold-dark)"><?php echo e($whatsapp); ?></a>
                <div style="font-size:11px;color:var(--text-muted);margin-top:4px">Orders, inquiries, loan support</div>
            </div>
            <div class="card card-body" style="text-align:center">
                <i class="fas fa-map-marker-alt" style="font-size:1.8rem;color:var(--gold);margin-bottom:10px;display:block"></i>
                <div style="font-weight:700;margin-bottom:4px">Location</div>
                <div style="font-size:13px"><?php echo e($address); ?></div>
                <div style="font-size:11px;color:var(--text-muted);margin-top:4px">Serving all regions</div>
            </div>
            <div class="card card-body" style="text-align:center">
                <i class="fas fa-clock" style="font-size:1.8rem;color:var(--gold);margin-bottom:10px;display:block"></i>
                <div style="font-weight:700;margin-bottom:4px">Working hours</div>
                <div style="font-size:13px"><?php echo e($hours); ?></div>
            </div>
        </div>

        <div style="max-width:640px;margin:0 auto">
            <?php if($success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?php echo e($success); ?></span></div>
            <?php else: ?>
            <?php if($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i><span><?php echo e($error); ?></span></div><?php endif; ?>
            <div class="card card-body">
                <h3 style="margin-bottom:20px;color:var(--dark)"><i class="fas fa-paper-plane" style="color:var(--gold)"></i> Send us a message</h3>
                <form method="POST">
                    <div class="form-row-2">
                        <div class="form-group"><label>Name *</label><input type="text" name="name" class="form-control" required placeholder="Your name"></div>
                        <div class="form-group"><label>Phone</label><input type="tel" name="phone" class="form-control" placeholder="+255..."></div>
                    </div>
                    <div class="form-group"><label>Email *</label><input type="email" name="email" class="form-control" required placeholder="your@email.com"></div>
                    <div class="form-group"><label>Subject *</label>
                        <select name="subject" class="form-control" required>
                            <option value="">Select subject...</option>
                            <option>Product inquiry</option>
                            <option>Order support</option>
                            <option>Loan application</option>
                            <option>Delivery inquiry</option>
                            <option>Other</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Message *</label>
                        <textarea name="message" class="form-control" rows="4" required placeholder="How can we help you?"></textarea>
                    </div>
                    <button type="submit" class="btn btn-gold btn-block"><i class="fas fa-paper-plane"></i> Send Message</button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
