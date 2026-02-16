<?php
// user/partials/layout.php

$user     = currentUser();
$token    = csrfToken();
$logout   = baseUrl('auth/logout.php?token=' . urlencode($token));
$initials = strtoupper(substr($user['name'], 0, 1));

function userNavLink(string $file, string $label, string $icon): string {
    $active = basename($_SERVER['PHP_SELF'], '.php') === $file ? ' active' : '';
    $url    = baseUrl("user/{$file}.php");
    return "<a href=\"{$url}\" class=\"{$active}\">{$icon} {$label}</a>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Dashboard') ?> — MailNest</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= baseUrl('assets/css/app.css') ?>">
</head>
<body>
<div class="layout">

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo">
            <h2>✉ MailNest</h2>
            <span>My Account</span>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-group-title">Menu</div>
            <?= userNavLink('dashboard',     'Dashboard',      '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>') ?>
            <?= userNavLink('subscriptions', 'My Subscriptions','<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>') ?>
            <?= userNavLink('profile',       'Profile',         '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>') ?>
        </nav>
        <div class="sidebar-footer">
            <a href="<?= e($logout) ?>" data-confirm="Sign out?">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16,17 21,12 16,7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Sign Out
            </a>
        </div>
    </aside>

    <div class="main-content">
        <header class="topbar">
            <div class="topbar-left"><h2><?= e($pageTitle ?? 'Dashboard') ?></h2></div>
            <div class="topbar-right">
                <div class="topbar-user">
                    <div class="avatar"><?= e($initials) ?></div>
                    <span><?= e($user['name']) ?></span>
                </div>
            </div>
        </header>
        <div class="page-body">
