<?php
declare(strict_types=1);

const DB_HOST = '127.0.0.1';
const DB_NAME = 'iwms';
const DB_USER = 'root';
const DB_PASS = '';
const APP_NAME = 'Inventory & Warehouse Management System';

session_start();

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}

function json_response(bool $ok, $data = null, string $message = '', int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok'=>$ok,'data'=>$data,'message'=>$message], JSON_UNESCAPED_UNICODE);
    exit;
}

function body(): array {
    $raw = file_get_contents('php://input');
    if (!$raw) return $_POST ?: [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : ($_POST ?: []);
}

function require_login(): void {
    if (empty($_SESSION['user'])) json_response(false, null, 'Authentication required.', 401);
}

function require_admin(): void {
    require_login();
    if ($_SESSION['user']['role'] !== 'admin') json_response(false, null, 'Admin access required.', 403);
}

function user_id(): int { return (int)($_SESSION['user']['id'] ?? 0); }
