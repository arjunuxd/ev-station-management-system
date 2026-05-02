<?php
// ✅ Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/' . (isAdmin() ? 'admin' : 'user') . '/index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // ✅ SIMPLE & WORKING LOGIN CHECK (PLAIN PASSWORD)
        if ($user && $password === $user['password']) {

            $_SESSION['user_id']    = $user['id'];
            $_SESSION['user_name']  = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['role']       = $user['role'];

            header('Location: ' . BASE_URL . '/' . ($user['role'] === 'admin' ? 'admin' : 'user') . '/index.php');
            exit();

        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — EV Station</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="auth-page">
    <div class="auth-box">
        <div class="auth-logo">
            <div class="brand-icon"><i class="fas fa-bolt"></i></div>
            <div class="brand-text">EV<span class="brand-accent">Station</span></div>
        </div>

        <h2 class="auth-heading">Welcome back</h2>
        <p class="auth-subheading">Sign in to manage your charging sessions</p>

        <?php if ($error): ?>
            <div class="ev-alert ev-alert-error">
                <i class="fas fa-circle-exclamation"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div style="margin-bottom: 1.1rem;">
                <label class="ev-label" for="email">Email Address</label>
                <input class="ev-input" type="email" id="email" name="email"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    placeholder="you@example.com" required autocomplete="email">
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label class="ev-label" for="password">Password</label>
                <input class="ev-input" type="password" id="password" name="password"
                    placeholder="••••••••" required autocomplete="current-password">
            </div>

            <button type="submit" class="btn-ev btn-ev-primary" style="width:100%; justify-content:center; padding:11px;">
                <i class="fas fa-right-to-bracket"></i> Sign In
            </button>
        </form>

        <div class="auth-footer">
            Don't have an account? <a href="<?= BASE_URL ?>/register.php">Create one</a>
        </div>

        <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border); font-size: 0.78rem; color: var(--text-faint); text-align: center; line-height: 1.8;">
            <strong style="color: var(--text-muted); font-family: var(--font-display);">Demo Credentials</strong><br>
            Admin: admin@ev.com / admin123<br>
            User: john@example.com / user123
        </div>
    </div>
</div>
</body>
</html>