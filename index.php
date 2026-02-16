<?php
require_once __DIR__ . '/config/bootstrap.php';

startSession();

if (isLoggedIn()) {
    if (isAdmin()) {
        redirect(baseUrl('admin/dashboard.php'));
    } else {
        redirect(baseUrl('user/dashboard.php'));
    }
}

redirect(baseUrl('auth/login.php'));
