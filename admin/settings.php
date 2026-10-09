<?php
session_start();
$root = '../';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();
$page_title = 'Settings';
require_once __DIR__ . '/includes/admin_header.php';

$msg = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $keys = ['site_name','site_tagline','contact_email','whatsapp_number','contact_address','working_hours',
             'currency','loan_rate_small','loan_rate_medium','loan_rate_large',
             'loan_max_small','loan_max_medium','loan_max_large'];
    foreach ($keys as $k) {
        if (isset($_POST[$k])) {
            $v = mysqli_real_escape_string($conn, trim($_POST[$k]));
            mysqli_query($conn,"INSERT INTO settings (`key`,`value`) VALUES('$k','$v') ON DUPLICATE KEY UPDATE `value`='$v'");
        }
    }
    $msg = 'Settings saved successfully.';
}

// Reload settings
$all = [];
$res = mysqli_query($conn,"SELECT * FROM settings");
while($r=mysqli_fetch_assoc($res)) $all[$r['key']] = $r['value'];
function sv($all,$k){ return htmlspecialchars($all[$k]??'',ENT_QUOTES,'UTF-8'); }
?>
<?php if($msg): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?php echo e($msg); ?></span></div><?php endif; ?>

<form method="POST" style="max-width:700px">
    <div class="card card-body" style="margin-bottom:20px">
        <h3 style="margin-bottom:16px;color:var(--dark)"><i class="fas fa-globe" style="color:var(--gold)"></i> Site settings</h3>
        <div class="form-group"><label>Site name</label><input type="text" name="site_name" class="form-control" value="<?php echo sv($all,'site_name'); ?>"></div>
        <div class="form-group"><label>Tagline</label><input type="text" name="site_tagline" class="form-control" value="<?php echo sv($all,'site_tagline'); ?>"></div>
        <div class="form-group"><label>Currency symbol</label><input type="text" name="currency" class="form-control" value="<?php echo sv($all,'currency'); ?>" style="max-width:120px"></div>
    </div>

    <div class="card card-body" style="margin-bottom:20px">
        <h3 style="margin-bottom:16px;color:var(--dark)"><i class="fas fa-envelope" style="color:var(--gold)"></i> Contact settings</h3>
        <div class="form-group"><label>Contact email</label><input type="email" name="contact_email" class="form-control" value="<?php echo sv($all,'contact_email'); ?>"></div>
        <div class="form-group"><label>WhatsApp number</label><input type="text" name="whatsapp_number" class="form-control" value="<?php echo sv($all,'whatsapp_number'); ?>" placeholder="+255700000000"></div>
        <div class="form-group"><label>Address</label><input type="text" name="contact_address" class="form-control" value="<?php echo sv($all,'contact_address'); ?>"></div>
        <div class="form-group"><label>Working hours</label><input type="text" name="working_hours" class="form-control" value="<?php echo sv($all,'working_hours'); ?>"></div>
    </div>

    <div class="card card-body" style="margin-bottom:20px">
        <h3 style="margin-bottom:16px;color:var(--dark)"><i class="fas fa-hand-holding-dollar" style="color:var(--gold)"></i> Loan settings</h3>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
            <div>
                <p style="font-weight:700;margin-bottom:10px;color:var(--success)">Small farm (≤2 acres)</p>
                <div class="form-group"><label>Max loan (TSh)</label><input type="number" name="loan_max_small" class="form-control" value="<?php echo sv($all,'loan_max_small'); ?>"></div>
                <div class="form-group"><label>Interest rate (%)</label><input type="number" name="loan_rate_small" class="form-control" step="0.1" value="<?php echo sv($all,'loan_rate_small'); ?>"></div>
            </div>
            <div>
                <p style="font-weight:700;margin-bottom:10px;color:var(--warn)">Medium farm (2–10 acres)</p>
                <div class="form-group"><label>Max loan (TSh)</label><input type="number" name="loan_max_medium" class="form-control" value="<?php echo sv($all,'loan_max_medium'); ?>"></div>
                <div class="form-group"><label>Interest rate (%)</label><input type="number" name="loan_rate_medium" class="form-control" step="0.1" value="<?php echo sv($all,'loan_rate_medium'); ?>"></div>
            </div>
            <div>
                <p style="font-weight:700;margin-bottom:10px;color:var(--info)">Large farm (10+ acres)</p>
                <div class="form-group"><label>Max loan (TSh)</label><input type="number" name="loan_max_large" class="form-control" value="<?php echo sv($all,'loan_max_large'); ?>"></div>
                <div class="form-group"><label>Interest rate (%)</label><input type="number" name="loan_rate_large" class="form-control" step="0.1" value="<?php echo sv($all,'loan_rate_large'); ?>"></div>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-gold" style="padding:12px 32px"><i class="fas fa-save"></i> Save all settings</button>
</form>
<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
