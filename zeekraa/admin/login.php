<?php
require __DIR__ . '/../lib/bootstrap.php'; require __DIR__ . '/../lib/migrate.php'; ensure_schema();
$err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $_SESSION['tries'] = ($_SESSION['tries'] ?? 0) + 1;
    if ($_SESSION['tries'] > 10) { $err = 'محاولات كتير، استنى شوية.'; }
    else {
        $a = q('SELECT * FROM admins WHERE email=?', [trim($_POST['email'] ?? '')])->fetch();
        if ($a && password_verify($_POST['password'] ?? '', $a['password_hash'])) {
            session_regenerate_id(true); $_SESSION['admin'] = $a['id']; $_SESSION['tries'] = 0;
            redirect(url('admin/'));
        }
        $err = 'الإيميل أو كلمة السر غلط.';
    }
}
?><!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>دخول</title>
<link rel="stylesheet" href="<?= asset('assets/admin.css') ?>"></head><body class="auth"><main class="card">
<h1>دخول الأدمن</h1>
<?php if (isset($_GET['installed'])): ?><div class="ok-msg">تم التثبيت ✓ سجّل دخول. ولأمان أكتر امسح فولدر <code>install</code>.</div><?php endif; ?>
<?php if ($err): ?><div class="alert"><?= e($err) ?></div><?php endif; ?>
<form method="post"><?= csrf_field() ?>
<label>الإيميل <input type="email" name="email" required autofocus></label>
<label>كلمة السر <input type="password" name="password" required></label>
<button class="btn">دخول</button></form></main></body></html>
