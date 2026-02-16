<?php
require_once __DIR__ . '/../config/bootstrap.php';
requireAdmin();

$errors = [];
$values = ['name' => '', 'email' => '', 'role' => 'user', 'reminder_enabled' => 1];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $name     = trim($_POST['name']     ?? '');
        $email    = trim($_POST['email']    ?? '');
        $password = $_POST['password']      ?? '';
        $role     = in_array($_POST['role'] ?? '', ['admin','user']) ? $_POST['role'] : 'user';
        $reminder = isset($_POST['reminder_enabled']) ? 1 : 0;

        $values = compact('name','email','role') + ['reminder_enabled' => $reminder];

        if (!$name)                   $errors[] = 'Name is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';
        if (strlen($password) < 8)    $errors[] = 'Password must be at least 8 characters.';

        if (empty($errors)) {
            $db = getDB();
            $exists = $db->prepare("SELECT id FROM users WHERE email = :email");
            $exists->execute(['email' => $email]);
            if ($exists->fetchColumn()) {
                $errors[] = 'Email address already in use.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $stmt = $db->prepare("INSERT INTO users (name, email, password, role, reminder_enabled) VALUES (:name,:email,:password,:role,:reminder)");
                $stmt->execute(['name' => $name, 'email' => $email, 'password' => $hash, 'role' => $role, 'reminder' => $reminder]);
                $_SESSION['flash'] = ['type' => 'success', 'msg' => "User {$name} created successfully."];
                redirect(baseUrl('admin/users.php'));
            }
        }
    }
}

$pageTitle = 'Add User';
require_once __DIR__ . '/partials/layout.php';
?>

<div class="card" style="max-width:560px">
    <div class="card-header">
        <h3>Add New User</h3>
        <a href="<?= baseUrl('admin/users.php') ?>" class="btn btn-outline btn-sm">← Back</a>
    </div>
    <div class="card-body">
        <?php foreach ($errors as $err): ?>
            <div class="alert alert-danger"><?= e($err) ?></div>
        <?php endforeach; ?>

        <form method="POST">
            <?= csrfField() ?>
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" class="form-control" value="<?= e($values['name']) ?>" required>
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" class="form-control" value="<?= e($values['email']) ?>" required>
            </div>
            <div class="form-group">
                <label>Password <span class="text-muted">(min. 8 chars)</span></label>
                <input type="password" name="password" class="form-control" minlength="8" required>
            </div>
            <div class="form-group">
                <label>Role</label>
                <select name="role" class="form-control">
                    <option value="user"  <?= $values['role']==='user'  ? 'selected':'' ?>>User</option>
                    <option value="admin" <?= $values['role']==='admin' ? 'selected':'' ?>>Admin</option>
                </select>
            </div>
            <div class="form-group">
                <label style="display:flex;gap:8px;align-items:center;cursor:pointer">
                    <input type="checkbox" name="reminder_enabled" value="1"
                        <?= $values['reminder_enabled'] ? 'checked' : '' ?>>
                    Enable email reminders
                </label>
            </div>
            <button type="submit" class="btn btn-primary">Create User</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/partials/layout_end.php'; ?>
