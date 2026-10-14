<?php
define('APP_ROOT', dirname(__DIR__));
if (!is_file(APP_ROOT . '/config.php')) {
    header('Location: ' . (dirname($_SERVER['SCRIPT_NAME']) === '/' ? '' : rtrim(preg_replace('#/(admin|install)$#', '', dirname($_SERVER['SCRIPT_NAME'])), '/')) . '/install/');
    exit;
}
$CONFIG = require APP_ROOT . '/config.php';
date_default_timezone_set('Africa/Cairo');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS'])]);
session_start();

function cfg(string $k, $d = null) { global $CONFIG; return $CONFIG[$k] ?? $d; }
function url(string $path = ''): string { return rtrim(cfg('base_url', ''), '/') . '/' . ltrim($path, '/'); }
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function redirect(string $to): void { header('Location: ' . $to); exit; }

function db(): PDO {
    static $pdo;
    if (!$pdo) {
        $pdo = new PDO(
            sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', cfg('db_host'), cfg('db_port', 3306), cfg('db_name')),
            cfg('db_user'), cfg('db_pass'),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]
        );
        $pdo->exec("SET time_zone = '+02:00'");
    }
    return $pdo;
}
function q(string $sql, array $p = []): PDOStatement { $s = db()->prepare($sql); $s->execute($p); return $s; }

function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(16)); }
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="' . csrf() . '">'; }
function csrf_check(): void {
    $t = $_SESSION['csrf'] ?? ''; if ($t === '' || !hash_equals($t, $_POST['_csrf'] ?? '')) { http_response_code(419); exit('الجلسة انتهت، ارجع حاول تاني.'); }
}
function flash(?string $msg = null) {
    if ($msg !== null) { $_SESSION['flash'] = $msg; return; }
    $m = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $m;
}
function require_admin(): void { if (empty($_SESSION['admin'])) redirect(url('admin/login.php')); }

function normalize_phone_eg(string $p): string {
    $d = preg_replace('/\D+/', '', $p);
    if (str_starts_with($d, '00')) $d = substr($d, 2);
    if (str_starts_with($d, '0')) $d = '20' . substr($d, 1);
    return $d;
}
function safe_url(?string $u): string {
    $u = trim((string)$u);
    return preg_match('#^https?://#i', $u) ? $u : '';
}
