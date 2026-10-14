<?php
function admin_header(string $title): void { ?>
<!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title) ?> — <?= e(cfg('site_name')) ?></title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap">
<link rel="stylesheet" href="<?= url('assets/admin.css') ?>"></head><body>
<header class="top"><div class="wrap"><strong><?= e(cfg('site_name')) ?></strong>
<nav><a href="<?= url('admin/') ?>">الدعوات</a><a href="<?= url('admin/edit.php') ?>">+ دعوة جديدة</a><a href="<?= url('admin/logout.php') ?>">خروج</a></nav></div></header>
<main class="wrap"><?php if ($m = flash()): ?><div class="ok-msg"><?= e($m) ?></div><?php endif;
}
function admin_footer(): void { echo '</main></body></html>'; }
