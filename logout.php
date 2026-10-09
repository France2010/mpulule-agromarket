<?php
session_start();
$_SESSION = [];
session_destroy();
header('Location: /agro_project/login.php');
exit;
