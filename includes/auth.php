<?php
/**
 * includes/auth.php
 */

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($conn)) require_once __DIR__ . '/../config.php';

function is_logged_in(): bool {
    return !empty($_SESSION['user_id']) && !empty($_SESSION['loggedin']);
}

function is_admin(): bool {
    return is_logged_in() && ($_SESSION['user_role'] ?? '') === 'admin';
}

function current_user(): ?array {
    global $conn;
    if (!is_logged_in()) return null;
    $uid = (int)$_SESSION['user_id'];
    $res = mysqli_query($conn,
        "SELECT id,firstname,lastname,email,phone,region,user_role,created_at
         FROM users WHERE id=$uid LIMIT 1");
    return ($res && mysqli_num_rows($res) > 0) ? mysqli_fetch_assoc($res) : null;
}

function require_login(): void {
    if (!is_logged_in()) {
        $back = urlencode($_SERVER['REQUEST_URI']);
        header('Location: /agro_project/login.php?next=' . $back);
        exit;
    }
}

function require_admin(): void {
    if (!is_logged_in()) {
        $back = urlencode($_SERVER['REQUEST_URI']);
        header('Location: /agro_project/login.php?next=' . $back);
        exit;
    }
    if (!is_admin()) {
        header('Location: /agro_project/index.php?error=unauthorized');
        exit;
    }
}

function require_guest(): void {
    if (is_logged_in()) {
        $dest = is_admin() ? '/agro_project/admin/index.php' : '/agro_project/index.php';
        header('Location: ' . $dest);
        exit;
    }
}

function login_user(array $user): void {
    $_SESSION['user_id']    = (int)$user['id'];
    $_SESSION['user_name']  = trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''));
    $_SESSION['user_email'] = $user['email'] ?? '';
    $_SESSION['user_role']  = $user['user_role'] ?? 'user';
    $_SESSION['loggedin']   = true;
    $_SESSION['firstname']  = $user['firstname'] ?? '';
    $_SESSION['lastname']   = $user['lastname']  ?? '';
    session_regenerate_id(true);
}

function logout_user(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]);
    }
    session_destroy();
}

/* ============================================================
   API-FRIENDLY AUTH — return JSON, not redirects
   ============================================================ */

function api_require_login(): void {
    if (!is_logged_in()) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authentication required']);
        exit;
    }
}

function api_require_admin(): void {
    api_require_login();
    if (!is_admin()) {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        exit;
    }
}