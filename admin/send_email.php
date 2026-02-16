<?php
require_once __DIR__ . '/../config/bootstrap.php';
requireAdmin();
require_once BASE_PATH . '/mail/mailer.php';

$db         = getDB();
$templates  = $db->query("SELECT id, title FROM email_templates ORDER BY title")->fetchAll();
$users      = $db->query("SELECT id, name, email FROM users WHERE role='user' ORDER BY name")->fetchAll();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$pageTitle = 'Send Email';
require_once __DIR__ . '/partials/layout.php';
?>

<?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">

<div class="card">
    <div class="card-header"><h3>Compose & Send</h3></div>
    <div class="card-body">
        <?php if (empty($templates)): ?>
            <div class="alert alert-warning">No templates found. <a href="<?= baseUrl('admin/add_template.php') ?>">Create one first.</a></div>
        <?php else: ?>
        <form id="send-form" method="POST" action="<?= baseUrl('admin/send_email_process.php') ?>">
            <?= csrfField() ?>

            <div class="form-group">
                <label>Template</label>
                <select name="template_id" id="template_id" class="form-control" required>
                    <option value="">— Select template —</option>
                    <?php foreach ($templates as $t): ?>
                        <option value="<?= $t['id'] ?>"><?= e($t['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Recipient</label>
                <select name="recipient_type" id="recipient_type" class="form-control" required onchange="toggleRecipient()">
                    <option value="">— Select recipient type —</option>
                    <option value="single">Single User</option>
                    <option value="all">All Users</option>
                    <option value="active">Active Subscribers Only</option>
                </select>
            </div>

            <div class="form-group" id="single-user-group" style="display:none">
                <label>Select User</label>
                <select name="user_id" id="user_id" class="form-control">
                    <option value="">— Select user —</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= e($u['name']) ?> (<?= e($u['email']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="btn-group mt-3">
                <button type="button" class="btn btn-outline" onclick="previewSingle()">
                    👁 Preview
                </button>
                <button type="submit" class="btn btn-primary" id="send-btn">
                    ✉ Send
                </button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3>Bulk Send Info</h3></div>
    <div class="card-body">
        <p class="text-muted" style="margin-bottom:16px">
            When sending to "All Users" or "Active Subscribers", the system processes up to
            <strong><?= (int)($_ENV['BULK_LIMIT'] ?? 50) ?></strong> recipients per execution
            and handles failures individually.
        </p>
        <div style="background:#f8fafc;border-radius:8px;padding:16px;margin-bottom:16px">
            <div style="display:flex;justify-content:space-between;margin-bottom:8px">
                <span class="text-muted">Total Users</span>
                <strong><?= count($users) ?></strong>
            </div>
            <div style="display:flex;justify-content:space-between">
                <span class="text-muted">Active Templates</span>
                <strong><?= count($templates) ?></strong>
            </div>
        </div>
        <p style="font-size:12px;color:var(--muted)">
            Failed emails are logged individually. Use the
            <a href="<?= baseUrl('admin/logs.php') ?>">Logs page</a> to review delivery status.
        </p>
    </div>
</div>
</div>

<div class="modal-overlay" id="preview-modal">
    <div class="modal">
        <div class="modal-header">
            <div>
                <h3>Email Preview</h3>
                <p class="text-muted" id="preview-subject" style="font-size:13px"></p>
            </div>
            <button class="modal-close" onclick="closeModal('preview-modal')">×</button>
        </div>
        <div class="modal-body email-preview">
            <iframe id="preview-iframe" sandbox="allow-same-origin"></iframe>
        </div>
    </div>
</div>

<script>
function toggleRecipient() {
    const type = document.getElementById('recipient_type').value;
    document.getElementById('single-user-group').style.display = type === 'single' ? '' : 'none';
}

async function previewSingle() {
    const templateId = document.getElementById('template_id').value;
    if (!templateId) { showToast('Select a template first.', 'error'); return; }

    const recipientType = document.getElementById('recipient_type').value;
    if (!recipientType) { showToast('Select a recipient type.', 'error'); return; }

    let userId = null;
    if (recipientType === 'single') {
        userId = document.getElementById('user_id').value;
        if (!userId) { showToast('Select a user for preview.', 'error'); return; }
    } else {
        const sel = document.getElementById('user_id');
        userId = sel.options[1]?.value;
        if (!userId) { showToast('No users available for preview.', 'error'); return; }
    }

    try {
        const data = await ajax('<?= baseUrl('admin/preview_email.php') ?>', {
            template_id: templateId,
            recipient_id: userId,
            csrf_token: '<?= e(csrfToken()) ?>'
        });
        if (data.success) {
            document.getElementById('preview-iframe').srcdoc = data.html;
            document.getElementById('preview-subject').textContent = 'Subject: ' + data.subject;
            openModal('preview-modal');
        } else {
            showToast(data.message, 'error');
        }
    } catch (e) {
        showToast('Preview failed.', 'error');
    }
}

document.getElementById('send-form')?.addEventListener('submit', function(e) {
    const type   = document.getElementById('recipient_type').value;
    const tmpl   = document.getElementById('template_id').value;
    if (!tmpl || !type) { e.preventDefault(); showToast('Fill in all fields.', 'error'); return; }
    if (type === 'single' && !document.getElementById('user_id').value) {
        e.preventDefault(); showToast('Select a user.', 'error'); return;
    }
    setLoading(document.getElementById('send-btn'), true, 'Sending…');
});
</script>

<?php require_once __DIR__ . '/partials/layout_end.php'; ?>
