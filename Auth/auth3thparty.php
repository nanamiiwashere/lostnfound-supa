<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
 
require_once __DIR__ . '/../connect.php';

define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: '');
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: '');
define('GOOGLE_REDIRECT_URI', getenv('GOOGLE_REDIRECT_URI') ?: '');

define('DISCORD_CLIENT_ID', getenv('DISCORD_CLIENT_ID') ?: '');
define('DISCORD_CLIENT_SECRET', getenv('DISCORD_CLIENT_SECRET') ?: '');
define('DISCORD_REDIRECT_URL', getenv('DISCORD_REDIRECT_URL') ?: '');

define('APP_URL', rtrim(getenv('APP_URL') ?: 'http://localhost:8000', '/' . '/'));

function isLoggedIn():bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin():void {
    if (!isLoggedIn()) {
        header('Location: '. APP_URL . '/login.php');
        exit();
    }
}

function currentUser(): ?array{
    if (!isLoggedIn()) return null;
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'email' => $_SESSION['email'] ?? null,
        'name' => $_SESSION['name'] ?? null,
        'role' => $_SESSION['role'] ?? null,
        'provider' => $_SESSION['provider'] ?? 'email',
        'avatar' => $_SESSION['avatar'] ?? null,
    ];
}

function loginUser(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id_user'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['name'] = $user['nama'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['provider'] = $user['oauth_provider'] ?? 'email';
    $av = $user['avatar'] ?? null;
    $_SESSION['avatar'] = $av && !str_starts_with($av, 'http') ? '../uploads/' . $av : $av;
    $_SESSION['logged_at'] = time();
}

function logoutUser(): void{
    session_unset();
    session_destroy();
    header('Location: '. APP_URL . '/dashboard/index.php');
    exit;
}


function requireStaff(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['id_user'])) {
        header('Location: ' . APP_URL . '/login.php');
        exit();
    }
    if ($_SESSION['role'] !== 'staff') {
        header('Location: ' . APP_URL . '/dashboard/index.php');
        exit();
    }
}