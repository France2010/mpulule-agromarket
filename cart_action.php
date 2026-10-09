<?php
// cart_action.php — AJAX endpoint for cart management
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();
header('Content-Type: application/json');

$action     = $_POST['action']     ?? '';
$product_id = (int)($_POST['product_id'] ?? 0);
$qty        = max(1,(int)($_POST['qty']  ?? 1));

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

function cart_count(): int {
    $n = 0;
    foreach ($_SESSION['cart'] as $item) $n += (int)$item['qty'];
    return $n;
}

if ($action === 'add') {
    // Verify product exists and is in stock
    $pid = $product_id;
    $res = mysqli_query($conn,"SELECT id,name,price,unit,stock_qty FROM products WHERE id=$pid AND active=1 LIMIT 1");
    if (!$res || mysqli_num_rows($res)===0) {
        echo json_encode(['success'=>false,'message'=>'Product not found']);exit;
    }
    $p = mysqli_fetch_assoc($res);
    if ($p['stock_qty']<=0) {
        echo json_encode(['success'=>false,'message'=>'Out of stock']);exit;
    }
    if (isset($_SESSION['cart'][$pid])) {
        $_SESSION['cart'][$pid]['qty'] += $qty;
    } else {
        $_SESSION['cart'][$pid] = ['id'=>$pid,'name'=>$p['name'],'price'=>$p['price'],'unit'=>$p['unit'],'qty'=>$qty];
    }
    echo json_encode(['success'=>true,'cart_count'=>cart_count()]);exit;
}

if ($action === 'update') {
    $pid = $product_id;
    if ($qty <= 0) {
        unset($_SESSION['cart'][$pid]);
    } else {
        if (isset($_SESSION['cart'][$pid])) $_SESSION['cart'][$pid]['qty'] = $qty;
    }
    $total = array_sum(array_map(fn($i)=>$i['price']*$i['qty'], $_SESSION['cart']));
    echo json_encode(['success'=>true,'cart_count'=>cart_count(),'total'=>fmt($total)]);exit;
}

if ($action === 'remove') {
    $pid = $product_id;
    unset($_SESSION['cart'][$pid]);
    $total = array_sum(array_map(fn($i)=>$i['price']*$i['qty'], $_SESSION['cart']));
    echo json_encode(['success'=>true,'cart_count'=>cart_count(),'total'=>fmt($total)]);exit;
}

echo json_encode(['success'=>false,'message'=>'Unknown action']);
