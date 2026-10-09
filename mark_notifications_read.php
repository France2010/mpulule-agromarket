<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();
if (isset($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    mysqli_query($conn, "UPDATE notifications SET is_read=1 WHERE user_id=$uid");
}
header('Location: /agro_project/profile.php');
exit;
