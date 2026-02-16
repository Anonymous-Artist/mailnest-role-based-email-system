<?php
require_once __DIR__ . '/../config/bootstrap.php';
requireAdmin();

$db  = getDB();
$id  = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM email_templates WHERE id = :id");
$stmt->execute(['id' => $id]);
$tpl  = $stmt->fetch();
if (!$tpl) { $_SESSION['flash']=['type'=>'danger','msg'=>'Template not found.']; redirect(baseUrl('admin/templates.php')); }

$errors = [];
$values = $tpl;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf($_POST['csrf_token'] ?? '')) { $errors[] = 'Invalid CSRF token.'; }
    else {
        $title   = trim($_POST['title']   ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $body    = $_POST['body']         ?? '';
        $values  = compact('title','subject','body') + ['id' => $id];

        if (!$title)   $errors[] = 'Title required.';
        if (!$subject) $errors[] = 'Subject required.';
        if (!$body)    $errors[] = 'Body required.';

        if (empty($errors)) {
            $db->prepare("UPDATE email_templates SET title=:title,subject=:subject,body=:body WHERE id=:id") ->execute(['title'=>$title,'subject'=>$subject,'body'=>$body,'id'=>$id]);
            $_SESSION['flash'] = ['type'=>'success','msg'=>'Template updated.'];
            redirect(baseUrl('admin/templates.php'));
        }
    }
}

$pageTitle = 'Edit Template';
require_once __DIR__ . '/partials/layout.php';
?>

<div class="card">
    <div class="card-header">
        <h3>Edit Template — <?= e($tpl['title']) ?></h3>
        <a href="<?= baseUrl('admin/templates.php') ?>" class="btn btn-outline btn-sm">← Back</a>
    </div>
    <div class="card-body">
        <?php foreach ($errors as $err): ?>
            <div class="alert alert-danger"><?= e($err) ?></div>
        <?php endforeach; ?>

        <div class="alert alert-info">
            Placeholders: <code>{{name}}</code> <code>{{email}}</code> <code>{{date}}</code> <code>{{subscription}}</code>
        </div>

        <form method="POST">
            <?= csrfField() ?>
            <div class="form-group">
                <label>Template Title</label>
                <input type="text" name="title" class="form-control" value="<?= e($values['title']) ?>" required>
            </div>
            <div class="form-group">
                <label>Email Subject</label>
                <input type="text" name="subject" class="form-control" value="<?= e($values['subject']) ?>" required>
            </div>
            <div class="form-group">
                <label>Email Body (HTML supported)</label>
                <textarea name="body" class="form-control" rows="14" required><?= e($values['body']) ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Update Template</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/partials/layout_end.php'; ?>
