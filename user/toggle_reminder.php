<?php
require_once __DIR__ . '/../config/bootstrap.php';
requireUser();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validateCsrf($_POST['csrf_token'] ?? '')) {
    jsonResponse(false, 'Unauthorized.');
}

$enabled = (int)(bool)($_POST['enabled'] ?? 0);
$userId  = (int)currentUser()['id'];

getDB()->prepare("UPDATE users SET reminder_enabled=:enabled WHERE id=:id") ->execute(['enabled' => $enabled, 'id' => $userId]);

jsonResponse(true, $enabled ? 'Reminders enabled.' : 'Reminders disabled.');
