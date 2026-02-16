<?php

declare(strict_types=1);
function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        $sessionName = $_ENV['SESSION_NAME'] ?? 'mailnest_session';
        session_name($sessionName);
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function requireAuth(): void {
    startSession();
    if (empty($_SESSION['user_id'])) {
        header('Location: /mailnest/auth/login.php');
        exit;
    }
}

function requireAdmin(): void {
    requireAuth();
    if (($_SESSION['role'] ?? '') !== 'admin') {
        header('Location: /mailnest/user/dashboard.php');
        exit;
    }
}

function requireUser(): void {
    requireAuth();
    if (($_SESSION['role'] ?? '') !== 'user') {
        header('Location: /mailnest/admin/dashboard.php');
        exit;
    }
}

function isLoggedIn(): bool {
    startSession();
    return !empty($_SESSION['user_id']);
}

function isAdmin(): bool {
    startSession();
    return ($_SESSION['role'] ?? '') === 'admin';
}

// Generate CSRF token
function csrfToken(): string {
    startSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Validate CSRF token
function validateCsrf(string $token): bool {
    startSession();
    $stored = $_SESSION['csrf_token'] ?? '';
    return hash_equals($stored, $token);
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}

function currentUser(): array {
    startSession();
    return [
        'id'    => $_SESSION['user_id']    ?? null,
        'name'  => $_SESSION['user_name']  ?? '',
        'email' => $_SESSION['user_email'] ?? '',
        'role'  => $_SESSION['role']       ?? '',
    ];
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

// JSON response helper
function jsonResponse(bool $success, string $message, array $data = []): void {
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}
