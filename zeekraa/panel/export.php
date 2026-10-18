<?php
require __DIR__ . '/../lib/bootstrap.php';
if (empty($_SESSION['client'])) redirect(url('panel/login.php'));
$inv = q('SELECT slug FROM invitations WHERE id=?', [$_SESSION['client']])->fetch() or exit;
$rows = q('SELECT * FROM rsvps WHERE invitation_id=? ORDER BY id DESC', [$_SESSION['client']])->fetchAll();
header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="rsvps-' . $inv['slug'] . '.csv"');
echo "\xEF\xBB\xBF"; $o = fopen('php://output', 'w'); fputcsv($o, ['الاسم', 'الموبايل', 'الحضور', 'عدد الأفراد', 'ملاحظة', 'التاريخ']);
$s = fn($x) => preg_match('/^[=+\-@\t\r]/', (string)$x) ? "'" . $x : $x;
foreach ($rows as $r) fputcsv($o, [$s($r['name']), $s($r['phone']), $r['attending'] ? 'حاضر' : 'معتذر', $r['party_size'], $s($r['note']), $r['created_at']]);
