<?php
require_once __DIR__ . '/../config/bootstrap.php';
requireUser();

$db     = getDB();
$userId = (int)currentUser()['id'];

$stmt = $db->prepare("SELECT * FROM users WHERE id=:id");
$stmt->execute(['id' => $userId]);
$me = $stmt->fetch();

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $name    = trim($_POST['name']  ?? '');
        $email   = trim($_POST['email'] ?? '');
        $newPass = $_POST['new_password']    ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!$name) $errors[] = 'Name required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';
        if ($newPass && $newPass !== $confirm) $errors[] = 'Passwords do not match.';
        if ($newPass && strlen($newPass) < 8)  $errors[] = 'Password must be at least 8 characters.';

        if (empty($errors)) {
            $dup = $db->prepare("SELECT id FROM users WHERE email=:email AND id!=:id");
            $dup->execute(['email' => $email, 'id' => $userId]);
            if ($dup->fetchColumn()) {
                $errors[] = 'Email already used by another account.';
            } else {
                if ($newPass) {
                    $hash = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]);
                    $db->prepare("UPDATE users SET name=:name,email=:email,password=:pw WHERE id=:id")
                        ->execute(['name' => $name, 'email' => $email, 'pw' => $hash, 'id' => $userId]);
                } else {
                    $db->prepare("UPDATE users SET name=:name,email=:email WHERE id=:id")
                        ->execute(['name' => $name, 'email' => $email, 'id' => $userId]);
                }
                // Update session
                $_SESSION['user_name']  = $name;
                $_SESSION['user_email'] = $email;
                $me['name']  = $name;
                $me['email'] = $email;
                $success = 'Profile updated successfully.';
            }
        }
    }
}

$pageTitle = 'My Profile';
require_once __DIR__ . '/partials/layout.php';
?>

<div class="card" style="max-width:520px">
    <div class="card-header">
        <h3>Update Profile</h3>
    </div>
    <div class="card-body">
        <?php foreach ($errors as $err): ?>
            <div class="alert alert-danger"><?= e($err) ?></div>
        <?php endforeach; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>

        <form method="POST">
            <?= csrfField() ?>
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" class="form-control" value="<?= e($me['name']) ?>" required>
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" class="form-control" value="<?= e($me['email']) ?>" required>
            </div>
            <hr style="margin:20px 0;border-color:var(--border)">
            <p class="text-muted" style="font-size:13px;margin-bottom:14px">Change Password — leave blank to keep current</p>
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" class="form-control" minlength="8">
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/partials/layout_end.php'; ?>