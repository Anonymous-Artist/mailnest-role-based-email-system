<?php
require_once __DIR__ . '/../config/bootstrap.php';
requireUser();

$db     = getDB();
$userId = (int)currentUser()['id'];

$user   = $db->prepare("SELECT * FROM users WHERE id=:id");
$user->execute(['id' => $userId]);
$me     = $user->fetch();

$activeSubs = (int)$db->prepare("SELECT COUNT(*) FROM subscriptions WHERE user_id=:id AND status='active'")
    ->execute(['id' => $userId]) ? $db->prepare("SELECT COUNT(*) FROM subscriptions WHERE user_id=:id AND status='active'") : 0;

$cntStmt = $db->prepare("SELECT COUNT(*) FROM subscriptions WHERE user_id=:id AND status='active'");
$cntStmt->execute(['id' => $userId]);
$activeSubs = (int)$cntStmt->fetchColumn();

$totalSpend = $db->prepare("SELECT COALESCE(SUM(amount),0) FROM subscriptions WHERE user_id=:id AND status='active'");
$totalSpend->execute(['id' => $userId]);
$monthlyTotal = (float)$totalSpend->fetchColumn();

// Upcoming billing (next 7 days)
$upcoming = $db->prepare("
    SELECT * FROM subscriptions
    WHERE user_id=:id AND status='active' AND billing_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 2 DAY)
    ORDER BY billing_date ASC
");
$upcoming->execute(['id' => $userId]);
$upcomingList = $upcoming->fetchAll();

$csrf    = csrfToken();
$pageTitle = 'Dashboard';
require_once __DIR__ . '/partials/layout.php';
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">
            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <rect x="1" y="4" width="22" height="16" rx="2" ry="2" />
                <line x1="1" y1="10" x2="23" y2="10" />
            </svg>
        </div>
        <div class="stat-info">
            <h4><?= $activeSubs ?></h4>
            <p>Active Subscriptions</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">
            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <line x1="12" y1="1" x2="12" y2="23" />
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
            </svg>
        </div>
        <div class="stat-info">
            <h4>$<?= number_format($monthlyTotal, 2) ?></h4>
            <p>Monthly Spend</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon <?= $me['reminder_enabled'] ? 'teal' : 'red' ?>">
            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                <path d="M13.73 21a2 2 0 0 1-3.46 0" />
            </svg>
        </div>
        <div class="stat-info">
            <h4><?= $me['reminder_enabled'] ? 'On' : 'Off' ?></h4>
            <p>Reminders</p>
        </div>
    </div>
</div>

<!-- Reminder Toggle -->
<div class="card" style="margin-bottom:24px">
    <div class="card-body" style="display:flex;align-items:center;justify-content:space-between">
        <div>
            <strong>Email Reminders</strong>
            <p class="text-muted" style="font-size:13px;margin-top:2px">Receive billing reminders before subscriptions renew.</p>
        </div>
        <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
            <span class="text-muted" style="font-size:13px"><?= $me['reminder_enabled'] ? 'Enabled' : 'Disabled' ?></span>
            <input type="checkbox" id="reminder-toggle" style="width:18px;height:18px;cursor:pointer"
                <?= $me['reminder_enabled'] ? 'checked' : '' ?>
                data-url="<?= e(baseUrl('user/toggle_reminder.php')) ?>"
                onchange="toggleReminder(this, '<?= e($csrf) ?>')">
        </label>
    </div>
</div>

<!-- Upcoming Billing -->
<?php if (!empty($upcomingList)): ?>
    <div class="card">
        <div class="card-header">
            <h3>⏰ Upcoming Billing (Next 7 Days)</h3>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Service</th>
                        <th>Amount</th>
                        <th>Billing Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($upcomingList as $s): ?>
                        <tr>
                            <td><?= e($s['service_name']) ?></td>
                            <td>$<?= number_format((float)$s['amount'], 2) ?></td>
                            <td style="color:var(--warning);font-weight:600"><?= e(date('M d, Y', strtotime($s['billing_date']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<script>
    // Reminder toggle AJAX
    document.getElementById('reminder-toggle')?.addEventListener('change', async function() {
        await toggleReminder(this, '<?= e($csrf) ?>');
    });
</script>

<?php require_once __DIR__ . '/partials/layout_end.php'; ?>