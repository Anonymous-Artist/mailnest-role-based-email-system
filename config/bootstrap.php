<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

require_once BASE_PATH . '/config/db.php';
require_once BASE_PATH . '/config/auth.php';
ini_set('display_errors', $_ENV['APP_ENV'] === 'development' ? '1' : '0');
error_reporting(E_ALL);

function baseUrl(string $path = ''): string {
    $base = rtrim($_ENV['APP_URL'] ?? '', '/');
    return $base . '/' . ltrim($path, '/');
}

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
