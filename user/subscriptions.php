<?php
require_once __DIR__ . '/../config/bootstrap.php';
requireUser();

$db     = getDB();
$userId = (int)currentUser()['id'];
$flash  = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash'] = ['type'=>'danger','msg'=>'Invalid CSRF token.'];
        redirect(baseUrl('user/subscriptions.php'));
    }

    $action      = $_POST['action'] ?? '';
    $service     = trim($_POST['service_name'] ?? '');
    $amount      = (float)($_POST['amount']       ?? 0);
    $billingDate = $_POST['billing_date']          ?? '';
    $status      = in_array($_POST['status']??'', ['active','inactive','cancelled']) ? $_POST['status'] : 'active';

    if ($action === 'add') {
        if (!$service || !$billingDate) {
            $_SESSION['flash'] = ['type'=>'danger','msg'=>'Service name and billing date are required.'];
        } else {
            $db->prepare("INSERT INTO subscriptions (user_id, service_name, amount, billing_date, status) VALUES (:uid,:svc,:amt,:bd,:st)") ->execute(['uid'=>$userId,'svc'=>$service,'amt'=>$amount,'bd'=>$billingDate,'st'=>$status]);
            $_SESSION['flash'] = ['type'=>'success','msg'=>'Subscription added.'];
        }
        redirect(baseUrl('user/subscriptions.php'));
    }

    if ($action === 'edit') {
        $subId = (int)($_POST['sub_id'] ?? 0);
        // Ownership check
        $own = $db->prepare("SELECT id FROM subscriptions WHERE id=:id AND user_id=:uid");
        $own->execute(['id'=>$subId,'uid'=>$userId]);
        if ($own->fetch()) {
            $db->prepare("UPDATE subscriptions SET service_name=:svc,amount=:amt,billing_date=:bd,status=:st WHERE id=:id AND user_id=:uid") ->execute(['svc'=>$service,'amt'=>$amount,'bd'=>$billingDate,'st'=>$status,'id'=>$subId,'uid'=>$userId]);
            $_SESSION['flash'] = ['type'=>'success','msg'=>'Subscription updated.'];
        } else {
            $_SESSION['flash'] = ['type'=>'danger','msg'=>'Subscription not found.'];
        }
        redirect(baseUrl('user/subscriptions.php'));
    }

    if ($action === 'delete') {
        $subId = (int)($_POST['sub_id'] ?? 0);
        $db->prepare("DELETE FROM subscriptions WHERE id=:id AND user_id=:uid")->execute(['id'=>$subId,'uid'=>$userId]);
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Subscription deleted.'];
        redirect(baseUrl('user/subscriptions.php'));
    }
}

// Fetch user's subs
$subs = $db->prepare("SELECT * FROM subscriptions WHERE user_id=:uid ORDER BY billing_date ASC");
$subs->execute(['uid' => $userId]);
$subscriptions = $subs->fetchAll();

// Edit mode
$editSub = null;
if (isset($_GET['edit'])) {
    $editId  = (int)$_GET['edit'];
    $editStmt = $db->prepare("SELECT * FROM subscriptions WHERE id=:id AND user_id=:uid");
    $editStmt->execute(['id'=>$editId,'uid'=>$userId]);
    $editSub = $editStmt->fetch();
}

$pageTitle = 'My Subscriptions';
require_once __DIR__ . '/partials/layout.php';
?>

<?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 360px;gap:24px">

<!-- Subscriptions Table -->
<div class="card">
    <div class="card-header"><h3>My Subscriptions</h3></div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr><th>Service</th><th>Amount/mo</th><th>Billing Date</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php if (empty($subscriptions)): ?>
                    <tr><td colspan="5" style="text-align:center;padding:30px;color:var(--muted)">No subscriptions yet. Add one!</td></tr>
                <?php else: ?>
                    <?php foreach ($subscriptions as $s):
                        $due    = strtotime($s['billing_date']);
                        $today  = strtotime('today');
                        $rowCls = ($due <= $today + 3*86400 && $s['status']==='active') ? 'style="background:#fffbeb"' : '';
                    ?>
                    <tr <?= $rowCls ?>>
                        <td><?= e($s['service_name']) ?></td>
                        <td>$<?= number_format((float)$s['amount'], 2) ?></td>
                        <td><?= e(date('M d, Y', strtotime($s['billing_date']))) ?></td>
                        <td>
                            <?php $cls = match($s['status']) {
                                'active'    => 'badge-success',
                                'inactive'  => 'badge-muted',
                                'cancelled' => 'badge-danger',
                                default     => 'badge-muted'
                            }; ?>
                            <span class="badge <?= $cls ?>"><?= ucfirst($s['status']) ?></span>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="?edit=<?= $s['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
                                <form method="POST" style="display:inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action"  value="delete">
                                    <input type="hidden" name="sub_id" value="<?= $s['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm"
                                        data-confirm="Delete '<?= e($s['service_name']) ?>'?">Delete</button>
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

<!-- Add / Edit Form -->
<div class="card">
    <div class="card-header">
        <h3><?= $editSub ? 'Edit Subscription' : 'Add Subscription' ?></h3>
        <?php if ($editSub): ?>
            <a href="<?= baseUrl('user/subscriptions.php') ?>" class="btn btn-outline btn-sm">Cancel</a>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="action"  value="<?= $editSub ? 'edit' : 'add' ?>">
            <?php if ($editSub): ?>
                <input type="hidden" name="sub_id" value="<?= $editSub['id'] ?>">
            <?php endif; ?>

            <div class="form-group">
                <label>Service Name</label>
                <input type="text" name="service_name" class="form-control"
                    value="<?= e($editSub['service_name'] ?? '') ?>"
                    placeholder="Netflix, Spotify…" required>
            </div>
            <div class="form-group">
                <label>Monthly Amount ($)</label>
                <input type="number" name="amount" class="form-control" min="0" step="0.01"
                    value="<?= e($editSub['amount'] ?? '0.00') ?>">
            </div>
            <div class="form-group">
                <label>Next Billing Date</label>
                <input type="date" name="billing_date" class="form-control"
                    value="<?= e($editSub['billing_date'] ?? date('Y-m-d')) ?>" required>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="active"    <?= ($editSub['status']??'active')==='active'    ? 'selected':'' ?>>Active</option>
                    <option value="inactive"  <?= ($editSub['status']??'')==='inactive'        ? 'selected':'' ?>>Inactive</option>
                    <option value="cancelled" <?= ($editSub['status']??'')==='cancelled'       ? 'selected':'' ?>>Cancelled</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-block">
                <?= $editSub ? 'Update' : 'Add Subscription' ?>
            </button>
        </form>
    </div>
</div>

</div>

<?php require_once __DIR__ . '/partials/layout_end.php'; ?>
