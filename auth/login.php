<?php
require_once __DIR__ . '/../config/bootstrap.php';
startSession();
if (isLoggedIn()) redirect(baseUrl(isAdmin() ? 'admin/dashboard.php' : 'user/dashboard.php'));

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email && $password) {
            $db   = getDB();
            $stmt = $db->prepare("SELECT id, name, email, password, role FROM users WHERE email = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();
            

            if ($user && password_verify($password, $user['password'])) {
                // Regenerate session on login (prevents session fixation)
                session_regenerate_id(true);
                $_SESSION['user_id']    = $user['id'];
                $_SESSION['user_name']  = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['role']       = $user['role'];

                redirect(baseUrl($user['role'] === 'admin' ? 'admin/dashboard.php' : 'user/dashboard.php'));
            } else {
                $error = 'Invalid email or password.';
            }
        } else {
            $error = 'Please fill in all fields.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — MailNest</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= baseUrl('assets/css/app.css') ?>">
</head>
<body>
<div class="auth-wrap">
    <div class="auth-card">
        <div class="auth-logo">
            <h1>✉ MailNest</h1>
            <p>Role-Based Email Management</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" id="login-form">
            <?= csrfField() ?>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control"
                    placeholder="you@example.com"
                    value="<?= e($_POST['email'] ?? '') ?>" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control"
                    placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block" id="login-btn">
                Sign In
            </button>
        </form>
    </div>
</div>
<script src="<?= baseUrl('assets/js/app.js') ?>"></script>
<script>
document.getElementById('login-form').addEventListener('submit', function() {
    setLoading(document.getElementById('login-btn'), true, 'Signing in...');
});
</script>
</body>
</html>
