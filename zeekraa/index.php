<?php
require __DIR__ . '/lib/bootstrap.php';
$name = e(cfg('site_name'));
?><!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $name ?> — دعوات زفاف رقمية</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Amiri:wght@700&family=Cairo:wght@400;600&display=swap">
<style>body{margin:0;font-family:Cairo,sans-serif;background:#f7f1e6;color:#3a2f20;text-align:center;line-height:1.9}
main{max-width:640px;margin:0 auto;padding:12vh 20px}h1{font-family:Amiri,serif;font-size:3rem;margin:0;color:#b08a3e}
a.b{display:inline-block;margin-top:20px;background:#b08a3e;color:#fff;padding:11px 28px;border-radius:999px;text-decoration:none}</style></head>
<body><main><h1><?= $name ?></h1><p>دعوة زفاف رقمية فاخرة: لينك واحد فيه المواعيد والخريطة وتأكيد الحضور.</p>
<a class="b" href="<?= url('admin/') ?>">لوحة التحكم</a></main></body></html>
