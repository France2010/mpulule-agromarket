<?php
session_start();
$root = '../';

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

// ================= DELETE =================
// This must be BEFORE any output
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "UPDATE products SET active=0 WHERE id=$id");
    header('Location: /agro_project/admin/products.php?msg=deleted');
    exit;
}

// ================= ADD / EDIT =================
// This must be BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id    = (int)($_POST['id'] ?? 0);
    $catid = (int)$_POST['category_id'];
    $name  = mysqli_real_escape_string($conn, trim($_POST['name']));
    $desc  = mysqli_real_escape_string($conn, trim($_POST['description'] ?? ''));
    $price = (float)$_POST['price'];
    $unit  = mysqli_real_escape_string($conn, trim($_POST['unit']));
    $stock = (int)$_POST['stock_qty'];
    $feat  = isset($_POST['featured']) ? 1 : 0;
    $act   = isset($_POST['active']) ? 1 : 0;

    $image_sql = '';
    $fname = '';

    // Ensure upload directory exists
    $upload_dir = $root . 'uploads/products/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // ===== IMAGE UPLOAD =====
    if (!empty($_FILES['image']['name'])) {

        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp','gif'];

        if (!in_array($ext, $allowed)) {
            $err = 'Invalid image format. Allowed: JPG, PNG, WEBP, GIF';
        } else {

            $fname = 'prod_' . time() . '_' . rand(100,999) . '.' . $ext;
            $dest  = $upload_dir . $fname;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {

                // Delete old image if updating
                if ($id) {
                    $old = mysqli_fetch_assoc(mysqli_query($conn, "SELECT image FROM products WHERE id=$id"));
                    if (!empty($old['image']) && file_exists($upload_dir . $old['image'])) {
                        unlink($upload_dir . $old['image']);
                    }
                }

                $image_sql = ", image='$fname'";
            } else {
                $err = 'Image upload failed. Check folder permissions.';
            }
        }
    }

    if (!$err) {
        if ($id) {
            mysqli_query($conn, "
                UPDATE products SET 
                category_id=$catid,
                name='$name',
                description='$desc',
                price=$price,
                unit='$unit',
                stock_qty=$stock,
                featured=$feat,
                active=$act
                $image_sql
                WHERE id=$id
            ");
            header('Location: /agro_project/admin/products.php?msg=updated');
            exit;
        } else {
            if (!empty($fname)) {
                mysqli_query($conn, "
                    INSERT INTO products 
                    (category_id,name,description,price,unit,stock_qty,featured,active,image) 
                    VALUES 
                    ($catid,'$name','$desc',$price,'$unit',$stock,$feat,$act,'$fname')
                ");
            } else {
                mysqli_query($conn, "
                    INSERT INTO products 
                    (category_id,name,description,price,unit,stock_qty,featured,active) 
                    VALUES 
                    ($catid,'$name','$desc',$price,'$unit',$stock,$feat,$act)
                ");
            }
            header('Location: /agro_project/admin/products.php?msg=added');
            exit;
        }
    }
}

// NOW include the header AFTER all redirects are handled
$page_title = 'Products';
require_once __DIR__ . '/includes/admin_header.php';

$msg = $err = '';

// ================= FETCH EDIT =================
$edit = null;
if (isset($_GET['edit'])) {
    $edit = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM products WHERE id=".(int)$_GET['edit']));
}

// ================= FETCH DATA =================
$cats = mysqli_query($conn, "SELECT id,name FROM categories ORDER BY sort_order");

$products = mysqli_query($conn, "
    SELECT p.*, c.name cat_name 
    FROM products p 
    JOIN categories c ON c.id=p.category_id 
    ORDER BY p.active DESC, c.sort_order, p.name
");
?>

<!-- ================= ALERTS ================= -->
<?php if(isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
<div class="alert alert-warning"><i class="fas fa-info-circle"></i><span>Product deactivated.</span></div>
<?php endif; ?>

<?php if(isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
<div class="alert alert-success"><i class="fas fa-check-circle"></i><span>Product updated successfully.</span></div>
<?php endif; ?>

<?php if(isset($_GET['msg']) && $_GET['msg'] === 'added'): ?>
<div class="alert alert-success"><i class="fas fa-check-circle"></i><span>Product added successfully.</span></div>
<?php endif; ?>

<?php if($msg): ?>
<div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?php echo e($msg); ?></span></div>
<?php endif; ?>

<?php if($err): ?>
<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i><span><?php echo e($err); ?></span></div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 380px;gap:24px;align-items:start">

<!-- ================= TABLE ================= -->
<div>
    <div class="flex-between" style="margin-bottom:14px">
        <h3><i class="fas fa-boxes"></i> Products List</h3>
        <a href="products.php" class="btn btn-outline btn-sm">Refresh</a>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Featured</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php while($p = mysqli_fetch_assoc($products)): ?>
            <tr style="<?php echo !$p['active'] ? 'opacity:.5' : ''; ?>">
                <td>
                    <?php if(!empty($p['image']) && file_exists($root . 'uploads/products/' . $p['image'])): ?>
                        <img src="<?php echo $root; ?>uploads/products/<?php echo e($p['image']); ?>" 
                             style="width:45px;height:45px;object-fit:cover;border-radius:6px;border:2px solid #e0e0e0"
                             onerror="this.style.display='none'">
                    <?php else: ?>
                        <div style="width:45px;height:45px;background:#f0f0f0;border-radius:6px;display:flex;align-items:center;justify-content:center">
                            <i class="fas fa-image" style="font-size:20px;color:#999"></i>
                        </div>
                    <?php endif; ?>
                </td>
                <td><strong><?php echo e($p['name']); ?></strong></td>
                <td><?php echo e($p['cat_name']); ?></td>
                <td><strong><?php echo fmt((float)$p['price']); ?></strong></td>
                <td>
                    <span class="badge <?php echo $p['stock_qty']==0?'badge-danger':($p['stock_qty']<=20?'badge-warn':'badge-success'); ?>">
                        <?php echo $p['stock_qty']; ?>
                    </span>
                </td>
                <td>
                    <?php echo $p['featured'] ? '<i class="fas fa-star" style="color:var(--gold);font-size:16px;"></i>' : '<span style="color:#999;">—</span>'; ?>
                </td>
                <td>
                    <span class="badge <?php echo $p['active'] ? 'badge-success' : 'badge-danger'; ?>">
                        <?php echo $p['active'] ? 'Active' : 'Inactive'; ?>
                    </span>
                </td>
                <td>
                    <a href="?edit=<?php echo $p['id']; ?>" class="btn btn-sm btn-dark" title="Edit">
                        <i class="fas fa-edit"></i>
                    </a>
                    <a href="?delete=<?php echo $p['id']; ?>" class="btn btn-sm btn-danger" 
                       onclick="return confirm('Deactivate this product?')" title="Delete">
                        <i class="fas fa-trash"></i>
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ================= FORM ================= -->
<div class="card card-body" style="position:sticky;top:80px">
    <h3 style="margin-bottom:16px;color:var(--dark)">
        <i class="fas <?php echo $edit ? 'fa-edit' : 'fa-plus-circle'; ?>"></i>
        <?php echo $edit ? 'Edit Product' : 'Add New Product'; ?>
    </h3>
    
    <form method="POST" enctype="multipart/form-data">
        <?php if($edit): ?>
            <input type="hidden" name="id" value="<?php echo $edit['id']; ?>">
        <?php endif; ?>
        
        <div class="form-group">
            <label>Product Name *</label>
            <input type="text" name="name" class="form-control" required 
                   value="<?php echo e($edit['name'] ?? ''); ?>" placeholder="Enter product name">
        </div>
        
        <div class="form-group">
            <label>Category *</label>
            <select name="category_id" class="form-control" required>
                <option value="">-- Select Category --</option>
                <?php mysqli_data_seek($cats, 0); while($c = mysqli_fetch_assoc($cats)): ?>
                <option value="<?php echo $c['id']; ?>" 
                    <?php echo (isset($edit) && $edit['category_id'] == $c['id']) ? 'selected' : ''; ?>>
                    <?php echo e($c['name']); ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" class="form-control" rows="3" 
                      placeholder="Product description..."><?php echo e($edit['description'] ?? ''); ?></textarea>
        </div>
        
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
            <div class="form-group">
                <label>Price (TSh) *</label>
                <input type="number" name="price" class="form-control" required 
                       min="0" step="100" value="<?php echo $edit['price'] ?? ''; ?>" placeholder="0">
            </div>
            <div class="form-group">
                <label>Unit *</label>
                <input type="text" name="unit" class="form-control" required 
                       value="<?php echo e($edit['unit'] ?? 'kg'); ?>" placeholder="kg, bag, pcs">
            </div>
        </div>
        
        <div class="form-group">
            <label>Stock Quantity</label>
            <input type="number" name="stock_qty" class="form-control" 
                   min="0" value="<?php echo $edit['stock_qty'] ?? 0; ?>" placeholder="0">
        </div>
        
        <div class="form-group">
            <label>Product Image</label>
            <input type="file" name="image" class="form-control" 
                   accept="image/jpeg,image/png,image/webp,image/gif">
            <small style="color: #6c757d; display: block; margin-top: 5px;">
                <i class="fas fa-info-circle"></i> Allowed: JPG, PNG, WEBP, GIF (Max 2MB)
            </small>
            
            <?php if(!empty($edit['image']) && file_exists($upload_dir . $edit['image'])): ?>
            <div style="margin-top:10px;padding:10px;background:#f8f9fa;border-radius:8px;display:flex;align-items:center;gap:10px">
                <img src="<?php echo $root; ?>uploads/products/<?php echo e($edit['image']); ?>" 
                     style="width:60px;height:60px;object-fit:cover;border-radius:6px;border:2px solid var(--gold)">
                <div>
                    <span style="font-size:12px;color:var(--text-muted)">Current: <?php echo e($edit['image']); ?></span><br>
                    <small style="color:#28a745;">Upload new image to replace</small>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div style="display:flex;gap:20px;margin:15px 0;padding:10px 0;">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                <input type="checkbox" name="featured" value="1" 
                       <?php echo (!empty($edit['featured'])) ? 'checked' : ''; ?>>
                <i class="fas fa-star" style="color:var(--gold);"></i>
                <span>Featured Product</span>
            </label>
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                <input type="checkbox" name="active" value="1" 
                       <?php echo (!isset($edit) || $edit['active']) ? 'checked' : ''; ?>>
                <i class="fas fa-toggle-on" style="color:#28a745;"></i>
                <span>Active</span>
            </label>
        </div>
        
        <button type="submit" class="btn btn-gold btn-block">
            <i class="fas fa-save"></i> <?php echo $edit ? 'Update Product' : 'Add Product'; ?>
        </button>
        
        <?php if($edit): ?>
            <a href="/agro_project/admin/products.php" class="btn btn-outline btn-block" style="margin-top:8px">
                <i class="fas fa-times"></i> Cancel
            </a>
        <?php endif; ?>
    </form>
</div>

</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>