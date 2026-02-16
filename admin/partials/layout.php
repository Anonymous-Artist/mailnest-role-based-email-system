<?php

$user   = currentUser();
$token  = csrfToken();
$logout = baseUrl('auth/logout.php?token=' . urlencode($token));
$active = basename($_SERVER['PHP_SELF'], '.php');

function navLink(string $file, string $label, string $icon, string $active): string {
    $base = basename($_SERVER['PHP_SELF'], '.php');
    $cls  = ($base === $file) ? ' active' : '';
    $url  = baseUrl("admin/{$file}.php");
    return "<a href=\"{$url}\" class=\"{$cls}\">{$icon} {$label}</a>";
}

$initials = strtoupper(substr($user['name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Admin') ?> — MailNest</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= baseUrl('assets/css/app.css') ?>">
</head>
<body>
<div class="layout">

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo">
            <h2>✉ MailNest</h2>
            <span>Admin Panel</span>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-group-title">Overview</div>
            <?= navLink('dashboard',  'Dashboard',   '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>', $active) ?>

            <div class="nav-group-title">Users</div>
            <?= navLink('users',       'Manage Users', '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>', $active) ?>
            <?= navLink('add_user',    'Add User',     '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>', $active) ?>

            <div class="nav-group-title">Email</div>
            <?= navLink('templates',    'Templates',     '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14,2 14,8 20,8"/></svg>', $active) ?>
            <?= navLink('send_email',   'Send Email',    '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22,2 15,22 11,13 2,9 22,2"/></svg>', $active) ?>
            <?= navLink('logs',         'Email Logs',    '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>', $active) ?>

            <div class="nav-group-title">Subscriptions</div>
            <?= navLink('subscriptions', 'All Subscriptions', '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>', $active) ?>
        </nav>
        <div class="sidebar-footer">
            <a href="<?= e($logout) ?>" data-confirm="Are you sure you want to sign out?">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16,17 21,12 16,7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Sign Out
            </a>
        </div>
    </aside>

    <!-- Main -->
    <div class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <h2><?= e($pageTitle ?? 'Admin') ?></h2>
            </div>
            <div class="topbar-right">
                <div class="topbar-user">
                    <div class="avatar"><?= e($initials) ?></div>
                    <span><?= e($user['name']) ?></span>
                </div>
            </div>
        </header>
        <div class="page-body">
