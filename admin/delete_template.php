<?php
require_once __DIR__ . '/../config/bootstrap.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validateCsrf($_POST['csrf_token'] ?? '')) {
    redirect(baseUrl('admin/templates.php'));
}

$id = (int)($_POST['template_id'] ?? 0);
if ($id) {
    getDB()->prepare("DELETE FROM email_templates WHERE id=:id")->execute(['id'=>$id]);
    $_SESSION['flash'] = ['type'=>'success','msg'=>'Template deleted.'];
} else {
    $_SESSION['flash'] = ['type'=>'danger','msg'=>'Invalid template.'];
}
redirect(baseUrl('admin/templates.php'));
