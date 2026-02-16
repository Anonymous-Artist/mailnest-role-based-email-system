<?php
require_once __DIR__ . '/../config/bootstrap.php';
requireAdmin();

$db     = getDB();
$userId = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $userId]);
$target = $stmt->fetch();

if (!$target) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'User not found.'];
    redirect(baseUrl('admin/users.php'));
}

$errors = [];
$values = $target;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $name     = trim($_POST['name']  ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password']   ?? '';
        $role     = in_array($_POST['role'] ?? '', ['admin','user']) ? $_POST['role'] : 'user';
        $reminder = isset($_POST['reminder_enabled']) ? 1 : 0;

        $values = compact('name','email','role') + ['reminder_enabled' => $reminder, 'id' => $userId];

        if (!$name) $errors[] = 'Name required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';
        if ($password && strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';

        if (empty($errors)) {
            // Check email unique (excluding self)
            $dup = $db->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
            $dup->execute(['email' => $email, 'id' => $userId]);
            if ($dup->fetchColumn()) {
                $errors[] = 'Email already in use by another account.';
            } else {
                if ($password) {
                    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                    $db->prepare("UPDATE users SET name=:name,email=:email,password=:pw,role=:role,reminder_enabled=:r WHERE id=:id")->execute(['name'=>$name,'email'=>$email,'pw'=>$hash,'role'=>$role,'r'=>$reminder,'id'=>$userId]);
                } else {
                    $db->prepare("UPDATE users SET name=:name,email=:email,role=:role,reminder_enabled=:r WHERE id=:id") ->execute(['name'=>$name,'email'=>$email,'role'=>$role,'r'=>$reminder,'id'=>$userId]);
                }
                $_SESSION['flash'] = ['type' => 'success', 'msg' => 'User updated successfully.'];
                redirect(baseUrl('admin/users.php'));
            }
        }
    }
}

$pageTitle = 'Edit User';
require_once __DIR__ . '/partials/layout.php';
?>

<div class="card" style="max-width:560px">
    <div class="card-header">
        <h3>Edit User — <?= e($target['name']) ?></h3>
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
                <label>New Password <span class="text-muted">(leave blank to keep current)</span></label>
                <input type="password" name="password" class="form-control" minlength="8">
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
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/partials/layout_end.php'; ?>
