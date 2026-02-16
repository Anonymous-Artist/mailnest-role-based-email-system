<?php
require_once __DIR__ . '/../config/bootstrap.php';
requireAdmin();

$errors = [];
$values = ['title' => '', 'subject' => '', 'body' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $title   = trim($_POST['title']   ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $body    = $_POST['body']         ?? '';
        $values  = compact('title', 'subject', 'body');

        if (!$title)   $errors[] = 'Title is required.';
        if (!$subject) $errors[] = 'Subject is required.';
        if (!$body)    $errors[] = 'Body is required.';

        if (empty($errors)) {
            $db = getDB();
            $db->prepare("INSERT INTO email_templates (title, subject, body, created_by) VALUES (:title,:subject,:body,:created_by)") ->execute(['title'=>$title,'subject'=>$subject,'body'=>$body,'created_by'=>currentUser()['id']]);
            $_SESSION['flash'] = ['type'=>'success','msg'=>'Template created successfully.'];
            redirect(baseUrl('admin/templates.php'));
        }
    }
}

$pageTitle = 'New Template';
require_once __DIR__ . '/partials/layout.php';
?>

<div class="card">
    <div class="card-header">
        <h3>New Email Template</h3>
        <a href="<?= baseUrl('admin/templates.php') ?>" class="btn btn-outline btn-sm">← Back</a>
    </div>
    <div class="card-body">
        <?php foreach ($errors as $err): ?>
            <div class="alert alert-danger"><?= e($err) ?></div>
        <?php endforeach; ?>

        <div class="alert alert-info" style="margin-bottom:20px">
            Available placeholders: <code>{{name}}</code> <code>{{email}}</code> <code>{{date}}</code> <code>{{subscription}}</code>
        </div>

        <form method="POST">
            <?= csrfField() ?>
            <div class="form-group">
                <label>Template Title <span class="text-muted">(internal name)</span></label>
                <input type="text" name="title" class="form-control" value="<?= e($values['title']) ?>" required>
            </div>
            <div class="form-group">
                <label>Email Subject</label>
                <input type="text" name="subject" class="form-control" value="<?= e($values['subject']) ?>"
                    placeholder="Hello {{name}}, your subscription reminder" required>
            </div>
            <div class="form-group">
                <label>Email Body <span class="text-muted">(HTML supported)</span></label>
                <textarea name="body" class="form-control" rows="12" required><?= e($values['body']) ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Save Template</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/partials/layout_end.php'; ?>
