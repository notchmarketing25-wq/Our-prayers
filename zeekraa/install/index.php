<?php
// معالج التثبيت السريع — خطوة واحدة: بيانات MySQL + حساب الأدمن
session_start();
$root = dirname(__DIR__);
if (is_file($root . '/install.lock') && is_file($root . '/config.php')) { http_response_code(403); exit('<meta charset="utf-8"><p dir="rtl">النظام متثبت بالفعل. امسح فولدر install للأمان.</p>'); }
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$checks = [
    'PHP 8.0 أو أحدث (الحالي ' . PHP_VERSION . ')' => version_compare(PHP_VERSION, '8.0.0', '>='),
    'إضافة pdo_mysql' => extension_loaded('pdo_mysql'),
    'إضافة mbstring' => extension_loaded('mbstring'),
    'إضافة fileinfo (رفع الصور)' => extension_loaded('fileinfo'),
    'فولدر الجذر قابل للكتابة (config.php)' => is_writable($root),
    'فولدر uploads قابل للكتابة' => is_writable($root . '/uploads'),
];
$ok = !in_array(false, $checks, true);

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$base = rtrim(preg_replace('#/install(/index\.php)?/?$#', '', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)), '/');
$f = ['db_host' => '127.0.0.1', 'db_port' => '3306', 'db_name' => '', 'db_user' => '', 'db_pass' => '', 'email' => '', 'site_name' => 'دعواتنا'];
$err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $ok) {
    foreach ($f as $k => $_) $f[$k] = trim($_POST[$k] ?? '');
    $pass = $_POST['password'] ?? '';
    try {
        if (!filter_var($f['email'], FILTER_VALIDATE_EMAIL)) throw new Exception('الإيميل غير صحيح.');
        if (strlen($pass) < 8) throw new Exception('كلمة سر الأدمن لازم 8 حروف على الأقل.');
        if ($f['db_name'] === '' || $f['db_user'] === '') throw new Exception('اسم قاعدة البيانات والمستخدم مطلوبين.');
        $dsn = sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $f['db_host'], (int)$f['db_port']);
        try {
            $pdo = new PDO($dsn . ';dbname=' . $f['db_name'], $f['db_user'], $f['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        } catch (PDOException $e) {
            // لو الداتابيز مش موجودة جرّب تعملها (على السيرفرات اللي بتسمح)
            $pdo = new PDO($dsn, $f['db_user'], $f['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '', $f['db_name']) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            $pdo->exec('USE `' . str_replace('`', '', $f['db_name']) . '`');
        }
        foreach (array_filter(array_map('trim', explode(";\n", file_get_contents($root . '/schema.sql')))) as $sql) $pdo->exec($sql);
        $pdo->prepare('DELETE FROM admins')->execute();
        $pdo->prepare('INSERT INTO admins (email, password_hash) VALUES (?, ?)')->execute([$f['email'], password_hash($pass, PASSWORD_DEFAULT)]);
        $config = [
            'db_host' => $f['db_host'], 'db_port' => (int)$f['db_port'], 'db_name' => $f['db_name'],
            'db_user' => $f['db_user'], 'db_pass' => $f['db_pass'],
            'base_url' => $scheme . '://' . $_SERVER['HTTP_HOST'] . $base,
            'site_name' => $f['site_name'] ?: 'دعواتنا',
        ];
        if (file_put_contents($root . '/config.php', "<?php\nreturn " . var_export($config, true) . ";\n", LOCK_EX) === false) throw new Exception('معرفتش أكتب config.php');
        @chmod($root . '/config.php', 0640);
        file_put_contents($root . '/install.lock', date('c'));
        header('Location: ' . $base . '/admin/login.php?installed=1');
        exit;
    } catch (Throwable $e) {
        $err = ($e instanceof PDOException) ? 'مشكلة في الاتصال بقاعدة البيانات: ' . $e->getMessage() : $e->getMessage();
    }
}
?><!doctype html>
<html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>تثبيت النظام</title>
<link rel="stylesheet" href="../assets/admin.css"></head><body class="auth">
<main class="card">
  <h1>تثبيت سريع</h1>
  <p class="muted">خطوة واحدة وتخلص: بيانات MySQL وحساب الأدمن.</p>
  <ul class="checks"><?php foreach ($checks as $l => $v): ?><li class="<?= $v ? 'ok' : 'bad' ?>"><?= $v ? '✓' : '✗' ?> <?= h($l) ?></li><?php endforeach; ?></ul>
  <?php if ($err): ?><div class="alert"><?= h($err) ?></div><?php endif; ?>
  <?php if ($ok): ?>
  <form method="post">
    <h3>قاعدة البيانات (MySQL)</h3>
    <label>السيرفر <input name="db_host" value="<?= h($f['db_host']) ?>" required></label>
    <label>البورت <input name="db_port" value="<?= h($f['db_port']) ?>" required></label>
    <label>اسم القاعدة <input name="db_name" value="<?= h($f['db_name']) ?>" required></label>
    <label>المستخدم <input name="db_user" value="<?= h($f['db_user']) ?>" required></label>
    <label>كلمة السر <input name="db_pass" type="password" value="<?= h($f['db_pass']) ?>"></label>
    <h3>الموقع والأدمن</h3>
    <label>اسم الموقع <input name="site_name" value="<?= h($f['site_name']) ?>"></label>
    <label>إيميل الأدمن <input name="email" type="email" value="<?= h($f['email']) ?>" required></label>
    <label>كلمة سر الأدمن (8+ حروف) <input name="password" type="password" minlength="8" required></label>
    <button class="btn">ثبّت النظام</button>
  </form>
  <?php else: ?><div class="alert">صلّح المتطلبات الأحمر وحدّث الصفحة.</div><?php endif; ?>
</main></body></html>
