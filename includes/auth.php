<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/db.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    if (isAdmin()) return $_SESSION['admin'] ?? null;
    return $_SESSION['user'] ?? null;
}

function getRole() {
    return $_SESSION['role'] ?? 'guest';
}

function hasRole($roles) {
    if (!is_array($roles)) $roles = [$roles];
    return in_array(getRole(), $roles);
}

function requireLogin($redirect = '/login.php') {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

function requireRole($roles, $redirect = null) {
    requireLogin();
    if (!hasRole($roles)) {
        header('Location: ' . BASE_URL . 'dashboard.php?error=acces_refuse');
        exit;
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

function loginMembre($email, $password) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM membre WHERE email = ? AND statut = 'actif'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['mot_de_passe'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['user'] = $user;
        $_SESSION['is_admin'] = false;
        return true;
    }
    return false;
}

function loginAdmin($email, $password) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM administrateur WHERE email = ? AND statut = 'actif'");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();
    if ($admin && password_verify($password, $admin['mot_de_passe'])) {
        $_SESSION['user_id'] = $admin['id'];
        $_SESSION['role'] = 'admin';
        $_SESSION['admin'] = $admin;
        $_SESSION['is_admin'] = true;
        return true;
    }
    return false;
}

function logout() {
    session_destroy();
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

function flash($msg, $type = 'success') {
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    $base = rtrim(str_replace('\\', '/', $scriptDir), '/');
    $parts = explode('/', trim($base, '/'));
    $projectFolder = $parts[0] ?? '';
    define('BASE_URL', $protocol . '://' . $host . '/' . $projectFolder . '/');
}
