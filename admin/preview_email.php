<?php
require_once __DIR__ . '/../config/bootstrap.php';
requireAdmin();
require_once BASE_PATH . '/mail/mailer.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validateCsrf($_POST['csrf_token'] ?? '')) {
    jsonResponse(false, 'Unauthorized');
}

$db          = getDB();
$templateId  = (int)($_POST['template_id']  ?? 0);
$recipientId = (int)($_POST['recipient_id'] ?? 0);

$tplStmt = $db->prepare("SELECT * FROM email_templates WHERE id=:id");
$tplStmt->execute(['id' => $templateId]);
$template = $tplStmt->fetch();
if (!$template) jsonResponse(false, 'Template not found.');

$userStmt = $db->prepare("SELECT id, name, email FROM users WHERE id=:id");
$userStmt->execute(['id' => $recipientId]);
$user = $userStmt->fetch();
if (!$user) jsonResponse(false, 'User not found.');

$subStmt = $db->prepare("SELECT service_name FROM subscriptions WHERE user_id=:uid AND status='active' LIMIT 1");
$subStmt->execute(['uid' => $user['id']]);
$subName = $subStmt->fetchColumn() ?: 'Example Service';

$vars = [
    'name'         => $user['name'],
    'email'        => $user['email'],
    'date'         => date('F j, Y'),
    'subscription' => $subName,
];

$subject = replacePlaceholders($template['subject'], $vars);
$body    = replacePlaceholders($template['body'],    $vars);

$html = <<<HTML
<!DOCTYPE html><html><head>
<meta charset="UTF-8">
<style>
body{font-family:Arial,sans-serif;background:#f4f4f5;margin:0;padding:20px}
.email-wrap{max-width:600px;margin:0 auto;background:#fff;border-radius:8px;padding:36px;box-shadow:0 1px 4px rgba(0,0,0,.08)}
</style></head><body>
<div class="email-wrap">{$body}</div>
</body></html>
HTML;

jsonResponse(true, 'OK', ['subject' => $subject, 'html' => $html]);
