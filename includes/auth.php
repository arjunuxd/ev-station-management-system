<?php
// ✅ Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

// ✅ Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// ✅ Check if admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// ✅ Require login (protect pages)
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit();
    }
}

// ✅ Require admin only
function requireAdmin() {
    requireLogin();

    if (!isAdmin()) {
        header('Location: ' . BASE_URL . '/user/index.php');
        exit();
    }
}

// ✅ Require normal user
function requireUser() {
    requireLogin();

    if (isAdmin()) {
        header('Location: ' . BASE_URL . '/admin/index.php');
        exit();
    }
}

// ✅ Get current user
function getCurrentUser() {
    if (!isLoggedIn()) return null;

    return [
        'id'    => $_SESSION['user_id'] ?? null,
        'name'  => $_SESSION['user_name'] ?? '',
        'email' => $_SESSION['user_email'] ?? '',
        'role'  => $_SESSION['role'] ?? ''
    ];
}

// ✅ Logout
function logout() {
    $_SESSION = [];
    session_destroy();

    header('Location: ' . BASE_URL . '/login.php');
    exit();
}