<?php
require_once __DIR__ . '/../config/bootstrap.php';
requireAdmin();

$db      = getDB();
$perPage = 15;
$page    = max(1, (int)($_GET['page'] ?? 1));
$search  = trim($_GET['q'] ?? '');
$offset  = ($page - 1) * $perPage;

$where  = $search ? "WHERE u.name LIKE :q OR u.email LIKE :q" : '';
$params = $search ? ['q' => "%{$search}%"] : [];

$total = (int)$db->prepare("SELECT COUNT(*) FROM users u {$where}") ->execute($params) ? $db->prepare("SELECT COUNT(*) FROM users u {$where}") : 0;
$countStmt = $db->prepare("SELECT COUNT(*) FROM users u {$where}");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));

$stmt = $db->prepare("
    SELECT u.*, (SELECT COUNT(*) FROM subscriptions s WHERE s.user_id = u.id AND s.status='active') AS active_subs
    FROM users u
    {$where}
    ORDER BY u.created_at DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
if ($search) $stmt->bindValue(':q', "%{$search}%");
$stmt->execute();
$users = $stmt->fetchAll();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$pageTitle = 'Manage Users';
require_once __DIR__ . '/partials/layout.php';
?>

<?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3>Users <span class="badge badge-primary"><?= $total ?></span></h3>
        <a href="<?= baseUrl('admin/add_user.php') ?>" class="btn btn-primary btn-sm">+ Add User</a>
    </div>
    <div class="card-body" style="padding-bottom:0">
        <form method="GET" class="search-bar">
            <input type="text" name="q" class="form-control live-search"
                placeholder="Search by name or email…"
                value="<?= e($search) ?>">
            <button type="submit" class="btn btn-outline btn-sm">Search</button>
            <?php if ($search): ?>
                <a href="<?= baseUrl('admin/users.php') ?>" class="btn btn-outline btn-sm">Clear</a>
            <?php endif; ?>
        </form>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Reminders</th>
                    <th>Active Subs</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr><td colspan="8" style="text-align:center;padding:30px;color:var(--muted)">No users found.</td></tr>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= $u['id'] ?></td>
                        <td><?= e($u['name']) ?></td>
                        <td><?= e($u['email']) ?></td>
                        <td>
                            <?php if ($u['role'] === 'admin'): ?>
                                <span class="badge badge-primary">Admin</span>
                            <?php else: ?>
                                <span class="badge badge-muted">User</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($u['reminder_enabled']): ?>
                                <span class="badge badge-success">On</span>
                            <?php else: ?>
                                <span class="badge badge-muted">Off</span>
                            <?php endif; ?>
                        </td>
                        <td><?= (int)$u['active_subs'] ?></td>
                        <td><?= e(date('M d, Y', strtotime($u['created_at']))) ?></td>
                        <td>
                            <div class="btn-group">
                                <a href="<?= baseUrl('admin/edit_user.php?id=' . $u['id']) ?>" class="btn btn-outline btn-sm">Edit</a>
                                <?php if ($u['id'] !== currentUser()['id']): ?>
                                <form method="POST" action="<?= baseUrl('admin/delete_user.php') ?>" style="display:inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm"
                                        data-confirm="Delete <?= e($u['name']) ?>? This cannot be undone.">Delete</button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pages > 1): ?>
    <div class="card-body">
        <div class="pagination">
            <?php for ($i = 1; $i <= $pages; $i++): ?>
                <?php $url = baseUrl('admin/users.php?page=' . $i . ($search ? '&q=' . urlencode($search) : '')); ?>
                <?php if ($i === $page): ?>
                    <span class="active"><?= $i ?></span>
                <?php else: ?>
                    <a href="<?= e($url) ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/partials/layout_end.php'; ?>
