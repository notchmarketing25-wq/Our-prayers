<?php
require __DIR__ . '/../lib/bootstrap.php'; require __DIR__ . '/../lib/admin_layout.php'; require_admin();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    csrf_check(); q('DELETE FROM invitations WHERE id=?', [(int)$_POST['delete']]); flash('تم الحذف.'); redirect(url('admin/'));
}
$rows = q('SELECT i.*, (SELECT COUNT(*) FROM guests g WHERE g.invitation_id=i.id) guests,
  (SELECT COUNT(*) FROM rsvps r WHERE r.invitation_id=i.id AND r.attending=1) yes,
  (SELECT COALESCE(SUM(party_size),0) FROM rsvps r WHERE r.invitation_id=i.id AND r.attending=1) heads,
  (SELECT MIN(event_at) FROM events e WHERE e.invitation_id=i.id) first_at
  FROM invitations i ORDER BY i.id DESC')->fetchAll();
admin_header('الدعوات'); ?>
<div class="card"><h2>الدعوات (<?= count($rows) ?>)</h2>
<?php if (!$rows): ?><p class="muted">لسه مفيش دعوات. <a href="<?= url('admin/edit.php') ?>">اعمل أول دعوة</a></p><?php else: ?>
<table><tr><th>العروسين</th><th>الرابط</th><th>الميعاد</th><th>ضيوف</th><th>حاضرين</th><th></th></tr>
<?php foreach ($rows as $r): ?><tr>
<td><?= e($r['groom']) ?> & <?= e($r['bride']) ?></td>
<td><a href="<?= url($r['slug']) ?>" target="_blank">/<?= e($r['slug']) ?></a></td>
<td><?= e($r['first_at']) ?></td><td><?= (int)$r['guests'] ?></td><td><?= (int)$r['yes'] ?> (<?= (int)$r['heads'] ?> فرد)</td>
<td style="white-space:nowrap"><a class="btn sm sec" href="<?= url('admin/edit.php?id=' . $r['id']) ?>">تعديل</a>
<a class="btn sm sec" href="<?= url('admin/guests.php?id=' . $r['id']) ?>">الضيوف</a>
<a class="btn sm sec" href="<?= url('admin/rsvps.php?id=' . $r['id']) ?>">الردود</a>
<form method="post" style="display:inline" onsubmit="return confirm('حذف الدعوة نهائيًا؟')"><?= csrf_field() ?><button class="btn sm danger" name="delete" value="<?= $r['id'] ?>">حذف</button></form></td>
</tr><?php endforeach; ?></table><?php endif; ?></div>
<?php admin_footer();
