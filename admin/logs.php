<?php
require_once __DIR__ . '/../config/bootstrap.php';
requireAdmin();

$db      = getDB();
$perPage = 20;
$page    = max(1, (int)($_GET['page']   ?? 1));
$status  = in_array($_GET['status'] ?? '', ['sent','failed','']) ? ($_GET['status'] ?? '') : '';
$offset  = ($page - 1) * $perPage;

$where  = $status ? "WHERE el.status = :status" : '';
$params = $status ? ['status' => $status] : [];

$countStmt = $db->prepare("SELECT COUNT(*) FROM email_logs el {$where}");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));

$stmt = $db->prepare("
    SELECT el.*, u.name AS admin_name
    FROM email_logs el
    JOIN users u ON u.id = el.admin_id
    {$where}
    ORDER BY el.sent_at DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
if ($status) $stmt->bindValue(':status', $status);
$stmt->execute();
$logs = $stmt->fetchAll();

$pageTitle = 'Email Logs';
require_once __DIR__ . '/partials/layout.php';
?>

<div class="card">
    <div class="card-header">
        <h3>Email Logs <span class="badge badge-primary"><?= $total ?></span></h3>
        <form method="GET" style="display:flex;gap:8px;align-items:center">
            <select name="status" class="form-control" style="width:160px" onchange="this.form.submit()">
                <option value="">All Status</option>
                <option value="sent"   <?= $status==='sent'   ? 'selected':'' ?>>Sent</option>
                <option value="failed" <?= $status==='failed' ? 'selected':'' ?>>Failed</option>
            </select>
            <?php if ($status): ?>
                <a href="<?= baseUrl('admin/logs.php') ?>" class="btn btn-outline btn-sm">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Recipient</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Sent By</th>
                    <th>Error</th>
                    <th>Date & Time</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="7" style="text-align:center;padding:30px;color:var(--muted)">No logs found.</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= $log['id'] ?></td>
                        <td><?= e($log['recipient_email']) ?></td>
                        <td><?= e(mb_strimwidth($log['subject'], 0, 55, '…')) ?></td>
                        <td>
                            <?php if ($log['status'] === 'sent'): ?>
                                <span class="badge badge-success">Sent</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Failed</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e($log['admin_name']) ?></td>
                        <td>
                            <?php if ($log['error_message']): ?>
                                <span class="text-danger" title="<?= e($log['error_message']) ?>" style="cursor:help;font-size:12px">
                                    <?= e(mb_strimwidth($log['error_message'], 0, 40, '…')) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e(date('M d, Y H:i', strtotime($log['sent_at']))) ?></td>
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
                <?php $url = baseUrl('admin/logs.php?page=' . $i . ($status ? '&status=' . urlencode($status) : '')); ?>
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
