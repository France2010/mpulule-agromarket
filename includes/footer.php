<?php
if (!isset($conn)) require_once __DIR__ . '/../config.php';

$site_name  = get_setting('site_name');
$email      = get_setting('contact_email');
$whatsapp   = get_setting('whatsapp_number');
$address    = get_setting('contact_address');
$hours      = get_setting('working_hours');
$wa_clean   = preg_replace('/\D/', '', $whatsapp);
$footer_cats= mysqli_query($conn, "SELECT name,slug FROM categories ORDER BY sort_order");
?>
<footer class="footer">
    <div class="footer-grid">
        <div>
            <h4><i class="fas fa-leaf"></i> <?php echo e($site_name); ?></h4>
            <p style="font-size:13px;line-height:1.7;color:rgba(255,255,255,.65)"><?php echo e(get_setting('site_tagline')); ?></p>
            <div style="margin-top:14px;display:flex;gap:12px;flex-wrap:wrap">
                <?php if($email): ?><a href="mailto:<?php echo e($email); ?>" style="color:var(--gold);font-size:20px" title="Email"><i class="fas fa-envelope"></i></a><?php endif; ?>
                <?php if($wa_clean): ?><a href="https://wa.me/<?php echo e($wa_clean); ?>" target="_blank" style="color:var(--gold);font-size:20px" title="WhatsApp"><i class="fab fa-whatsapp"></i></a><?php endif; ?>
            </div>
        </div>
        <div>
            <h4>Quick links</h4>
            <ul>
                <li><a href="/agro_project/index.php">Home</a></li>
                <li><a href="/agro_project/products.php">Products</a></li>
                <li><a href="/agro_project/cart.php">My Cart</a></li>
                <li><a href="/agro_project/loan.php">Agri Loan</a></li>
                <li><a href="/agro_project/contact.php">Contact</a></li>
            </ul>
        </div>
        <div>
            <h4>Categories</h4>
            <ul>
                <?php if($footer_cats) while($fc=mysqli_fetch_assoc($footer_cats)): ?>
                <li><a href="/agro_project/products.php?cat=<?php echo e($fc['slug']); ?>"><?php echo e($fc['name']); ?></a></li>
                <?php endwhile; ?>
            </ul>
        </div>
        <div>
            <h4>Contact us</h4>
            <ul>
                <?php if($email): ?><li><i class="fas fa-envelope" style="color:var(--gold);width:16px"></i> <a href="mailto:<?php echo e($email); ?>" style="color:rgba(255,255,255,.7)"><?php echo e($email); ?></a></li><?php endif; ?>
                <?php if($whatsapp): ?><li style="margin-top:5px"><i class="fab fa-whatsapp" style="color:var(--gold);width:16px"></i> <a href="https://wa.me/<?php echo e($wa_clean); ?>" style="color:rgba(255,255,255,.7)"><?php echo e($whatsapp); ?></a></li><?php endif; ?>
                <?php if($address): ?><li style="margin-top:5px"><i class="fas fa-map-marker-alt" style="color:var(--gold);width:16px"></i> <?php echo e($address); ?></li><?php endif; ?>
                <?php if($hours): ?><li style="margin-top:5px"><i class="fas fa-clock" style="color:var(--gold);width:16px"></i> <?php echo e($hours); ?></li><?php endif; ?>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">&copy; <?php echo date('Y'); ?> <?php echo e($site_name); ?>. All rights reserved.</div>
</footer>

<script>
function showToast(msg,type){
    const t=document.getElementById('toast'),m=document.getElementById('toast-msg');
    if(!t||!m)return;
    m.textContent=msg;
    t.style.background=type==='error'?'#7f1d1d':type==='warn'?'#78350f':'var(--dark)';
    t.classList.add('show');
    setTimeout(()=>t.classList.remove('show'),3500);
}
</script>
</body></html>
