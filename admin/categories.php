<?php
session_start();
$root = '../';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();
$page_title = 'Categories';
require_once __DIR__ . '/includes/admin_header.php';

$msg = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $id   = (int)($_POST['id']??0);
    $name = mysqli_real_escape_string($conn,trim($_POST['name']));
    $slug = mysqli_real_escape_string($conn,strtolower(preg_replace('/[^a-z0-9]+/i','-',trim($_POST['slug']))));
    $icon = mysqli_real_escape_string($conn,trim($_POST['icon']??'fa-leaf'));
    $desc = mysqli_real_escape_string($conn,trim($_POST['description']??''));
    $sort = (int)($_POST['sort_order']??0);
    if ($id) {
        mysqli_query($conn,"UPDATE categories SET name='$name',slug='$slug',icon='$icon',description='$desc',sort_order=$sort WHERE id=$id");
        $msg = 'Category updated.';
    } else {
        mysqli_query($conn,"INSERT INTO categories (name,slug,icon,description,sort_order) VALUES('$name','$slug','$icon','$desc',$sort)");
        $msg = 'Category added.';
    }
}

$edit = isset($_GET['edit']) ? mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM categories WHERE id=".(int)$_GET['edit']." LIMIT 1")) : null;
$cats = mysqli_query($conn,"SELECT c.*,COUNT(p.id) prod_count FROM categories c LEFT JOIN products p ON p.category_id=c.id AND p.active=1 GROUP BY c.id ORDER BY c.sort_order");
?>
<?php if($msg): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?php echo e($msg); ?></span></div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 320px;gap:24px">
<div class="table-wrap">
<table class="table">
<thead><tr><th>Name</th><th>Slug</th><th>Icon</th><th>Products</th><th>Order</th><th></th></tr></thead>
<tbody>
<?php while($c=mysqli_fetch_assoc($cats)): ?>
<tr>
    <td><strong><?php echo e($c['name']); ?></strong><br><span style="font-size:12px;color:var(--text-muted)"><?php echo e($c['description']); ?></span></td>
    <td><code><?php echo e($c['slug']); ?></code></td>
    <td><i class="fas <?php echo e($c['icon']); ?>" style="color:var(--gold)"></i> <span style="font-size:12px"><?php echo e($c['icon']); ?></span></td>
    <td><?php echo $c['prod_count']; ?></td>
    <td><?php echo $c['sort_order']; ?></td>
    <td><a href="?edit=<?php echo $c['id']; ?>" class="btn btn-sm btn-dark"><i class="fas fa-edit"></i></a></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>

<div class="card card-body" style="position:sticky;top:80px">
    <h3 style="margin-bottom:16px"><?php echo $edit?'Edit category':'Add category'; ?></h3>
    <form method="POST">
        <?php if($edit): ?><input type="hidden" name="id" value="<?php echo $edit['id']; ?>"><?php endif; ?>
        <div class="form-group"><label>Name *</label>
            <input type="text" name="name" class="form-control" required value="<?php echo e($edit['name']??''); ?>" oninput="autoSlug(this.value)">
        </div>
        <div class="form-group"><label>Slug *</label>
            <input type="text" name="slug" id="slugInput" class="form-control" required value="<?php echo e($edit['slug']??''); ?>">
        </div>
        <div class="form-group"><label>FA Icon class</label>
            <input type="text" name="icon" class="form-control" value="<?php echo e($edit['icon']??'fa-leaf'); ?>" placeholder="fa-seedling">
        </div>
        <div class="form-group"><label>Description</label>
            <textarea name="description" class="form-control" rows="2"><?php echo e($edit['description']??''); ?></textarea>
        </div>
        <div class="form-group"><label>Sort order</label>
            <input type="number" name="sort_order" class="form-control" value="<?php echo $edit['sort_order']??0; ?>">
        </div>
        <button type="submit" class="btn btn-gold btn-block"><i class="fas fa-save"></i> <?php echo $edit?'Update':'Add'; ?></button>
        <?php if($edit): ?><a href="categories.php" class="btn btn-outline btn-block" style="margin-top:8px">Cancel</a><?php endif; ?>
    </form>
</div>
</div>

<script>
function autoSlug(v){
    if(document.getElementById('slugInput').value===''){
        document.getElementById('slugInput').value=v.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'');
    }
}
</script>
<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
