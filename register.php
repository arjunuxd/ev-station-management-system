<?php
// ✅ Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/' . (isAdmin() ? 'admin' : 'user') . '/index.php');
    exit();
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    // ✅ Validation
    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Name, email and password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $error = 'An account with this email already exists.';
        } else {
            // ✅ STORE PLAIN PASSWORD (MATCHES LOGIN)
            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, phone, password, role) 
                VALUES (?, ?, ?, ?, 'user')
            ");
            $stmt->execute([$name, $email, $phone, $password]);

            $success = 'Account created! You can now sign in.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — EV Station</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="auth-page">
    <div class="auth-box" style="max-width: 460px;">
        <div class="auth-logo">
            <div class="brand-icon"><i class="fas fa-bolt"></i></div>
            <div class="brand-text">EV<span class="brand-accent">Station</span></div>
        </div>

        <h2 class="auth-heading">Create account</h2>
        <p class="auth-subheading">Join the smart charging network</p>

        <?php if ($error): ?>
            <div class="ev-alert ev-alert-error">
                <i class="fas fa-circle-exclamation"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="ev-alert ev-alert-success">
                <i class="fas fa-circle-check"></i>
                <?= htmlspecialchars($success) ?>
                <a href="<?= BASE_URL ?>/login.php" style="color:inherit;font-weight:600;text-decoration:underline;">Login now</a>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div style="margin-bottom: 1rem;">
                <label class="ev-label" for="name">Full Name</label>
                <input class="ev-input" type="text" id="name" name="name"
                    value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                    placeholder="John Doe" required>
            </div>

            <div style="margin-bottom: 1rem;">
                <label class="ev-label" for="email">Email Address</label>
                <input class="ev-input" type="email" id="email" name="email"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    placeholder="you@example.com" required>
            </div>

            <div style="margin-bottom: 1rem;">
                <label class="ev-label" for="phone">Phone Number <span style="color:var(--text-faint);">(optional)</span></label>
                <input class="ev-input" type="tel" id="phone" name="phone"
                    value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                    placeholder="+91 98765 43210">
            </div>

            <div style="margin-bottom: 1rem;">
                <label class="ev-label" for="password">Password</label>
                <input class="ev-input" type="password" id="password" name="password"
                    placeholder="Min. 6 characters" required>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label class="ev-label" for="confirm_password">Confirm Password</label>
                <input class="ev-input" type="password" id="confirm_password" name="confirm_password"
                    placeholder="Repeat password" required>
            </div>

            <button type="submit" class="btn-ev btn-ev-primary" style="width:100%; justify-content:center; padding:11px;">
                <i class="fas fa-user-plus"></i> Create Account
            </button>
        </form>

        <div class="auth-footer">
            Already have an account? <a href="<?= BASE_URL ?>/login.php">Sign in</a>
        </div>
    </div>
</div>
</body>
</html>