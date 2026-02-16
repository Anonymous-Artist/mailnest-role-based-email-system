<?php
require_once __DIR__ . '/../config/bootstrap.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validateCsrf($_POST['csrf_token'] ?? '')) {
    redirect(baseUrl('admin/users.php'));
}

$userId    = (int)($_POST['user_id'] ?? 0);
$currentId = (int)currentUser()['id'];

if ($userId && $userId !== $currentId) {
    $db = getDB();
    $db->prepare("DELETE FROM users WHERE id = :id")->execute(['id' => $userId]);
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'User deleted.'];
} else {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Cannot delete yourself or invalid user.'];
}

redirect(baseUrl('admin/users.php'));
