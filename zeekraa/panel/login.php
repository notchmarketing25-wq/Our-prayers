<?php
require __DIR__ . '/../lib/bootstrap.php'; require __DIR__ . '/../lib/migrate.php'; ensure_schema();
if (!empty($_SESSION['client'])) redirect(url('panel/'));
$err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $_SESSION['ctries'] = ($_SESSION['ctries'] ?? 0) + 1;
    if ($_SESSION['ctries'] > 10) $err = 'محاولات كتير، استنى شوية وحاول تاني.';
    else {
        $r = q('SELECT id, owner_password FROM invitations WHERE owner_email=? AND is_active=1', [strtolower(trim($_POST['email'] ?? ''))])->fetch();
        // نعمل verify دايمًا (حتى لو الإيميل غلط) عشان نقلل الفرق في الوقت
        $ok = password_verify($_POST['password'] ?? '', $r['owner_password'] ?? '$2y$10$usesomesillystringforsalt$');
        if ($r && $ok) { session_regenerate_id(true); $_SESSION['client'] = (int)$r['id']; $_SESSION['ctries'] = 0; redirect(url('panel/')); }
        $err = 'الإيميل أو كلمة السر غلط.';
    }
}
?><!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>لوحة العروسين — <?= e(cfg('site_name')) ?></title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Amiri:wght@700&family=Cairo:wght@400;600;700&display=swap">
<link rel="stylesheet" href="<?= asset('assets/panel.css') ?>"></head>
<body class="login"><div class="blob b1"></div><div class="blob b2"></div>
<main class="login-card"><div class="logo">♡</div><h1>أهلًا بيكم</h1><p class="sub">ادخلوا لوحتكم وشوفوا مين رد على دعوتكم</p>
<?php if ($err): ?><div class="alert"><?= e($err) ?></div><?php endif; ?>
<form method="post"><?= csrf_field() ?>
<label>الإيميل<input type="email" name="email" required autofocus autocomplete="username"></label>
<label>كلمة السر<input type="password" name="password" required autocomplete="current-password"></label>
<button class="btn">دخول</button></form></main></body></html>
