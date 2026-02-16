<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('CRON_MODE', true);

require_once BASE_PATH . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

require_once BASE_PATH . '/config/db.php';
require_once BASE_PATH . '/mail/mailer.php';

set_time_limit(0);

$db         = getDB();
$daysAhead  = 3;
$sent       = 0;
$failed     = 0;

$stmt = $db->prepare("
    SELECT
        s.id AS sub_id,
        s.service_name,
        s.amount,
        s.billing_date,
        u.id   AS user_id,
        u.name AS user_name,
        u.email AS user_email
    FROM subscriptions s
    JOIN users u ON u.id = s.user_id
    WHERE s.status = 'active' AND u.reminder_enabled = 1 AND s.billing_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)
    ORDER BY s.billing_date ASC
");
$stmt->execute(['days' => $daysAhead]);
$upcoming = $stmt->fetchAll();

if (empty($upcoming)) {
    echo "[" . date('Y-m-d H:i:s') . "] No upcoming billing reminders to send.\n";
    exit(0);
}

$tplStmt = $db->prepare("
    SELECT * FROM email_templates
    WHERE LOWER(title) LIKE '%reminder%'
    ORDER BY id ASC
    LIMIT 1
");
$tplStmt->execute();
$template = $tplStmt->fetch();
if (!$template) {
    $template = $db->query("SELECT * FROM email_templates ORDER BY id ASC LIMIT 1")->fetch();
}

if (!$template) {
    echo "[" . date('Y-m-d H:i:s') . "] ERROR: No email templates found. Create one in the admin panel.\n";
    exit(1);
}

$adminId = (int)$db->query("SELECT id FROM users WHERE role='admin' LIMIT 1")->fetchColumn();

echo "[" . date('Y-m-d H:i:s') . "] Processing " . count($upcoming) . " reminder(s)...\n";

foreach ($upcoming as $row) {
    $daysUntil = (int)ceil((strtotime($row['billing_date']) - time()) / 86400);

    $vars = [
        'name'         => $row['user_name'],
        'email'        => $row['user_email'],
        'date'         => date('F j, Y', strtotime($row['billing_date'])),
        'subscription' => $row['service_name'],
        'amount'       => number_format((float)$row['amount'], 2),
        'days'         => $daysUntil,
    ];

    $subject = replacePlaceholders($template['subject'], $vars);
    $body    = replacePlaceholders($template['body'],    $vars);

    $result = sendEmail($adminId, $row['user_email'], $row['user_name'], $subject, $body);

    if ($result['success']) {
        $sent++;
        echo "  ✓ Sent to {$row['user_email']} for {$row['service_name']} (due {$row['billing_date']})\n";
    } else {
        $failed++;
        echo "  ✗ Failed for {$row['user_email']}: {$result['error']}\n";
    }

    usleep(250000); // 250ms
}

echo "[" . date('Y-m-d H:i:s') . "] Done. Sent: {$sent}, Failed: {$failed}\n";
exit(0);
