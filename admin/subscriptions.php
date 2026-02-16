<?php
require_once __DIR__ . '/../config/bootstrap.php';
requireAdmin();

$db      = getDB();
$perPage = 20;
$page    = max(1, (int)($_GET['page']   ?? 1));
$status  = in_array($_GET['status'] ?? '', ['active','inactive','cancelled','']) ? ($_GET['status'] ?? '') : '';
$offset  = ($page - 1) * $perPage;

$where  = $status ? "WHERE s.status = :status" : '';
$params = $status ? ['status' => $status] : [];

$countStmt = $db->prepare("SELECT COUNT(*) FROM subscriptions s {$where}");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));

$stmt = $db->prepare("
    SELECT s.*, u.name AS user_name, u.email AS user_email
    FROM subscriptions s
    JOIN users u ON u.id = s.user_id
    {$where}
    ORDER BY s.billing_date ASC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
if ($status) $stmt->bindValue(':status', $status);
$stmt->execute();
$subs = $stmt->fetchAll();

$pageTitle = 'All Subscriptions';
require_once __DIR__ . '/partials/layout.php';
?>

<div class="card">
    <div class="card-header">
        <h3>Subscriptions <span class="badge badge-primary"><?= $total ?></span></h3>
        <form method="GET" style="display:flex;gap:8px">
            <select name="status" class="form-control" style="width:160px" onchange="this.form.submit()">
                <option value="">All Status</option>
                <option value="active"    <?= $status==='active'    ? 'selected':'' ?>>Active</option>
                <option value="inactive"  <?= $status==='inactive'  ? 'selected':'' ?>>Inactive</option>
                <option value="cancelled" <?= $status==='cancelled' ? 'selected':'' ?>>Cancelled</option>
            </select>
            <?php if ($status): ?>
                <a href="<?= baseUrl('admin/subscriptions.php') ?>" class="btn btn-outline btn-sm">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>User</th>
                    <th>Service</th>
                    <th>Amount</th>
                    <th>Billing Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($subs)): ?>
                    <tr><td colspan="6" style="text-align:center;padding:30px;color:var(--muted)">No subscriptions found.</td></tr>
                <?php else: ?>
                    <?php foreach ($subs as $s):
                        $due     = strtotime($s['billing_date']);
                        $today   = strtotime('today');
                        $soonCls = ($due <= $today + 3*86400 && $s['status']==='active') ? 'style="color:var(--warning);font-weight:600"' : '';
                    ?>
                    <tr>
                        <td><?= $s['id'] ?></td>
                        <td>
                            <div><?= e($s['user_name']) ?></div>
                            <div style="font-size:12px;color:var(--muted)"><?= e($s['user_email']) ?></div>
                        </td>
                        <td><?= e($s['service_name']) ?></td>
                        <td>$<?= number_format((float)$s['amount'], 2) ?></td>
                        <td <?= $soonCls ?>><?= e(date('M d, Y', strtotime($s['billing_date']))) ?></td>
                        <td>
                            <?php $cls = match($s['status']) {
                                'active'    => 'badge-success',
                                'inactive'  => 'badge-muted',
                                'cancelled' => 'badge-danger',
                                default     => 'badge-muted'
                            }; ?>
                            <span class="badge <?= $cls ?>"><?= ucfirst($s['status']) ?></span>
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
                <?php $url = baseUrl('admin/subscriptions.php?page=' . $i . ($status ? '&status=' . urlencode($status) : '')); ?>
                <<?= $i === $page ? 'span class="active"' : 'a href="' . e($url) . '"' ?>><?= $i ?></<?= $i === $page ? 'span' : 'a' ?>>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/partials/layout_end.php'; ?>
