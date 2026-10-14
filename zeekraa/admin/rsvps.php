<?php
require __DIR__ . '/../lib/bootstrap.php'; require __DIR__ . '/../lib/admin_layout.php'; require_admin();
$id = (int)($_GET['id'] ?? 0);
$inv = q('SELECT * FROM invitations WHERE id=?', [$id])->fetch() or exit('مش موجودة');
$rows = q('SELECT * FROM rsvps WHERE invitation_id=? ORDER BY id DESC', [$id])->fetchAll();
if (isset($_GET['csv'])) {
    header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="rsvps-' . $inv['slug'] . '.csv"');
    echo "\xEF\xBB\xBF"; $o = fopen('php://output', 'w'); fputcsv($o, ['الاسم', 'الموبايل', 'الحضور', 'عدد الأفراد', 'ملاحظة', 'التاريخ']);
    // حماية من CSV injection عند الفتح في Excel
    $s = fn($x) => preg_match('/^[=+\-@\t\r]/', (string)$x) ? "'" . $x : $x;
    foreach ($rows as $r) fputcsv($o, [$s($r['name']), $s($r['phone']), $r['attending'] ? 'حاضر' : 'معتذر', $r['party_size'], $s($r['note']), $r['created_at']]);
    exit;
}
$yes = array_sum(array_map(fn($r) => $r['attending'] ? $r['party_size'] : 0, $rows));
admin_header('الردود'); ?>
<div class="card"><h2>ردود: <?= e($inv['groom']) ?> & <?= e($inv['bride']) ?></h2>
<p>إجمالي الحاضرين: <b><?= $yes ?></b> فرد · <a href="?id=<?= $id ?>&csv=1">تحميل CSV</a></p>
<table><tr><th>الاسم</th><th>الموبايل</th><th>الحضور</th><th>الأفراد</th><th>ملاحظة</th><th>التاريخ</th></tr>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['name']) ?></td><td><?= e($r['phone']) ?></td><td><?= $r['attending'] ? '✓ حاضر' : '✗ معتذر' ?></td><td><?= (int)$r['party_size'] ?></td><td><?= e($r['note']) ?></td><td><?= e($r['created_at']) ?></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="6" class="muted">لا ردود بعد.</td></tr><?php endif; ?></table></div>
<a class="btn sec" href="<?= url('admin/') ?>">رجوع</a>
<?php admin_footer();
