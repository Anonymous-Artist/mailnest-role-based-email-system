<?php
require_once __DIR__ . '/../config/bootstrap.php';
requireAdmin();
require_once BASE_PATH . '/mail/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validateCsrf($_POST['csrf_token'] ?? '')) {
    redirect(baseUrl('admin/send_email.php'));
}

set_time_limit(0);

$db            = getDB();
$adminId       = (int)currentUser()['id'];
$templateId    = (int)($_POST['template_id']    ?? 0);
$recipientType = $_POST['recipient_type']        ?? '';
$userId        = (int)($_POST['user_id']         ?? 0);
$bulkLimit     = (int)($_ENV['BULK_LIMIT']       ?? 50);

$tpl = $db->prepare("SELECT * FROM email_templates WHERE id=:id");
$tpl->execute(['id' => $templateId]);
$template = $tpl->fetch();
if (!$template) {
    $_SESSION['flash'] = ['type'=>'danger','msg'=>'Template not found.'];
    redirect(baseUrl('admin/send_email.php'));
}

switch ($recipientType) {
    case 'single':
        if (!$userId) {
            $_SESSION['flash'] = ['type'=>'danger','msg'=>'No user selected.'];
            redirect(baseUrl('admin/send_email.php'));
        }
        $stmt = $db->prepare("SELECT id, name, email FROM users WHERE id=:id AND role='user'");
        $stmt->execute(['id' => $userId]);
        $recipients = $stmt->fetchAll();
        break;

    case 'all':
        $stmt = $db->prepare("SELECT id, name, email FROM users WHERE role='user' LIMIT :limit");
        $stmt->bindValue(':limit', $bulkLimit, PDO::PARAM_INT);
        $stmt->execute();
        $recipients = $stmt->fetchAll();
        break;

    case 'active':
        $stmt = $db->prepare("
            SELECT DISTINCT u.id, u.name, u.email
            FROM users u
            JOIN subscriptions s ON s.user_id = u.id
            WHERE u.role='user' AND s.status='active'
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $bulkLimit, PDO::PARAM_INT);
        $stmt->execute();
        $recipients = $stmt->fetchAll();
        break;

    default:
        $_SESSION['flash'] = ['type'=>'danger','msg'=>'Invalid recipient type.'];
        redirect(baseUrl('admin/send_email.php'));
}

if (empty($recipients)) {
    $_SESSION['flash'] = ['type'=>'warning','msg'=>'No recipients found.'];
    redirect(baseUrl('admin/send_email.php'));
}

$sent   = 0;
$failed = 0;

foreach ($recipients as $recipient) {

    $subStmt = $db->prepare("SELECT service_name FROM subscriptions WHERE user_id=:uid AND status='active' LIMIT 1");
    $subStmt->execute(['uid' => $recipient['id']]);
    $subName = $subStmt->fetchColumn() ?: 'N/A';

    $vars = [
        'name'         => $recipient['name'],
        'email'        => $recipient['email'],
        'date'         => date('F j, Y'),
        'subscription' => $subName,
    ];

    $subject = replacePlaceholders($template['subject'], $vars);
    $body    = replacePlaceholders($template['body'],    $vars);

    $result = sendEmail($adminId, $recipient['email'], $recipient['name'], $subject, $body);
    $result['success'] ? $sent++ : $failed++;
}

$msg = "Done! Sent: {$sent}" . ($failed > 0 ? ", Failed: {$failed}" : '.');
$_SESSION['flash'] = ['type' => $failed > 0 ? 'warning' : 'success', 'msg' => $msg];
redirect(baseUrl('admin/send_email.php'));
