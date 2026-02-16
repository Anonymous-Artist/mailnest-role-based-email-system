<?php
require_once __DIR__ . '/../config/bootstrap.php';
requireAdmin();

$db = getDB();

// Stats
$totalUsers    = (int)$db->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
$totalEmails   = (int)$db->query("SELECT COUNT(*) FROM email_logs")->fetchColumn();
$sentEmails    = (int)$db->query("SELECT COUNT(*) FROM email_logs WHERE status='sent'")->fetchColumn();
$failedEmails  = (int)$db->query("SELECT COUNT(*) FROM email_logs WHERE status='failed'")->fetchColumn();
$totalSubs     = (int)$db->query("SELECT COUNT(*) FROM subscriptions WHERE status='active'")->fetchColumn();

$recentLogs = $db->query("
    SELECT el.*, u.name AS admin_name
    FROM email_logs el
    JOIN users u ON u.id = el.admin_id
    ORDER BY el.sent_at DESC
    LIMIT 8
")->fetchAll();

$pageTitle = 'Dashboard';
require_once __DIR__ . '/partials/layout.php';
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">
            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
        </div>
        <div class="stat-info">
            <h4><?= $totalUsers ?></h4>
            <p>Total Users</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon teal">
            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22,2 15,22 11,13 2,9 22,2"/></svg>
        </div>
        <div class="stat-info">
            <h4><?= $totalEmails ?></h4>
            <p>Emails Sent (Total)</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">
            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20,6 9,17 4,12"/></svg>
        </div>
        <div class="stat-info">
            <h4><?= $sentEmails ?></h4>
            <p>Delivered</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red">
            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        </div>
        <div class="stat-info">
            <h4><?= $failedEmails ?></h4>
            <p>Failed</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Recent Email Activity</h3>
        <a href="<?= baseUrl('admin/logs.php') ?>" class="btn btn-outline btn-sm">View All Logs</a>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Recipient</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Sent By</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentLogs)): ?>
                    <tr><td colspan="5" style="text-align:center;padding:30px;color:var(--muted)">No email activity yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($recentLogs as $log): ?>
                    <tr>
                        <td><?= e($log['recipient_email']) ?></td>
                        <td><?= e(mb_strimwidth($log['subject'], 0, 50, '…')) ?></td>
                        <td>
                            <?php if ($log['status'] === 'sent'): ?>
                                <span class="badge badge-success">Sent</span>
                            <?php else: ?>
                                <span class="badge badge-danger" title="<?= e($log['error_message'] ?? '') ?>">Failed</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e($log['admin_name']) ?></td>
                        <td><?= e(date('M d, Y H:i', strtotime($log['sent_at']))) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/partials/layout_end.php'; ?>
