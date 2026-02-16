<?php
require_once __DIR__ . '/../config/bootstrap.php';
requireAdmin();

$db     = getDB();
$flash  = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$templates = $db->query("
    SELECT t.*, u.name AS creator
    FROM email_templates t
    JOIN users u ON u.id = t.created_by
    ORDER BY t.created_at DESC
")->fetchAll();

$pageTitle = 'Email Templates';
require_once __DIR__ . '/partials/layout.php';
?>

<?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3>Templates <span class="badge badge-primary"><?= count($templates) ?></span></h3>
        <a href="<?= baseUrl('admin/add_template.php') ?>" class="btn btn-primary btn-sm">+ New Template</a>
    </div>

    <div class="card-body" style="padding-bottom:0">
        <div class="alert alert-info">
            Use placeholders: <code>{{name}}</code>, <code>{{email}}</code>, <code>{{date}}</code>, <code>{{subscription}}</code>
        </div>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Title</th>
                    <th>Subject</th>
                    <th>Created By</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($templates)): ?>
                    <tr><td colspan="6" style="text-align:center;padding:30px;color:var(--muted)">No templates yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($templates as $t): ?>
                    <tr>
                        <td><?= $t['id'] ?></td>
                        <td><?= e($t['title']) ?></td>
                        <td><?= e(mb_strimwidth($t['subject'], 0, 60, '…')) ?></td>
                        <td><?= e($t['creator']) ?></td>
                        <td><?= e(date('M d, Y', strtotime($t['created_at']))) ?></td>
                        <td>
                            <div class="btn-group">
                                <a href="<?= baseUrl('admin/edit_template.php?id=' . $t['id']) ?>" class="btn btn-outline btn-sm">Edit</a>
                                <form method="POST" action="<?= baseUrl('admin/delete_template.php') ?>" style="display:inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="template_id" value="<?= $t['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm"
                                        data-confirm="Delete template '<?= e($t['title']) ?>'?">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/partials/layout_end.php'; ?>
