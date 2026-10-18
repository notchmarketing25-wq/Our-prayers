<?php
require __DIR__ . '/../lib/bootstrap.php'; require __DIR__ . '/../lib/admin_layout.php'; require_admin();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    csrf_check(); q('DELETE FROM invitations WHERE id=?', [(int)$_POST['delete']]); flash('تم الحذف.'); redirect(url('admin/'));
}
$rows = q('SELECT i.*, (SELECT COUNT(*) FROM guests g WHERE g.invitation_id=i.id) guests,
  (SELECT COUNT(*) FROM rsvps r WHERE r.invitation_id=i.id AND r.attending=1) yes,
  (SELECT COUNT(*) FROM rsvps r WHERE r.invitation_id=i.id AND r.attending=0) no,
  (SELECT COALESCE(SUM(party_size),0) FROM rsvps r WHERE r.invitation_id=i.id AND r.attending=1) heads,
  (SELECT MIN(event_at) FROM events e WHERE e.invitation_id=i.id) first_at
  FROM invitations i ORDER BY i.id DESC')->fetchAll();
$tot = ['inv' => count($rows), 'guests' => array_sum(array_column($rows, 'guests')), 'heads' => array_sum(array_column($rows, 'heads')), 'no' => array_sum(array_column($rows, 'no'))];
$recent = q('SELECT r.*, i.groom, i.bride FROM rsvps r JOIN invitations i ON i.id=r.invitation_id ORDER BY r.id DESC LIMIT 8')->fetchAll();
$link = $_SESSION['panel_link'] ?? null; unset($_SESSION['panel_link']);
admin_header('الدعوات'); ?>
<div class="page-head"><h1>لوحة التحكم</h1><a class="btn gold" href="<?= url('admin/edit.php') ?>">+ دعوة جديدة</a></div>
<?php if ($link): ?><div class="card linkbox"><h3 style="margin-top:0">رابط الدخول المباشر — <?= e($link['name']) ?></h3>
<p class="muted">أي حد معاه الرابط ده يفتح لوحة العروسين ويشوف الردود من غير باسورد. ابعته للعروسين فقط.</p>
<div class="row"><input id="plink" readonly value="<?= e($link['url']) ?>" onclick="this.select()"><button class="btn" type="button" onclick="navigator.clipboard.writeText(document.getElementById('plink').value).then(()=>this.textContent='تم النسخ ✓')">نسخ</button></div></div><?php endif; ?>
<div class="kpis">
<div class="kpi" style="--tint:#2f6b4f1f"><b><?= $tot['inv'] ?></b><small>دعوة</small></div>
<div class="kpi" style="--tint:#b08a3e22;animation-delay:.06s"><b><?= $tot['guests'] ?></b><small>مدعو</small></div>
<div class="kpi" style="--tint:#2f8f5b22;animation-delay:.12s"><b><?= $tot['heads'] ?></b><small>فرد حاضر</small></div>
<div class="kpi" style="--tint:#c0555f22;animation-delay:.18s"><b><?= $tot['no'] ?></b><small>معتذر</small></div>
</div>
<?php if (!$rows): ?><div class="card"><p class="muted">لسه مفيش دعوات. <a href="<?= url('admin/edit.php') ?>">اعمل أول دعوة</a></p></div><?php else: ?>
<div class="inv-grid">
<?php foreach ($rows as $i => $r): ?>
<article class="inv" style="animation-delay:<?= min($i, 8) * .05 ?>s">
<div style="display:flex;justify-content:space-between;gap:8px;align-items:flex-start"><div><h3><?= e($r['groom']) ?> & <?= e($r['bride']) ?></h3>
<div class="meta"><a href="<?= url($r['slug']) ?>" target="_blank">/<?= e($r['slug']) ?> ↗</a><span><?= e($r['first_at'] ? substr($r['first_at'], 0, 10) : '') ?></span></div></div>
<span class="badge <?= $r['is_active'] ? 'on' : 'off' ?>"><?= $r['is_active'] ? 'مفعّلة' : 'متوقفة' ?></span></div>
<div class="stats"><span><b><?= (int)$r['guests'] ?></b>مدعو</span><span><b><?= (int)$r['heads'] ?></b>فرد حاضر</span><span><b><?= (int)$r['no'] ?></b>معتذر</span></div>
<div class="acts"><a class="btn sm" href="<?= url('admin/edit.php?id=' . $r['id']) ?>">تعديل</a>
<a class="btn sm sec" href="<?= url('admin/guests.php?id=' . $r['id']) ?>">الضيوف</a><a class="btn sm sec" href="<?= url('admin/rsvps.php?id=' . $r['id']) ?>">الردود</a>
<form method="post" action="<?= url('admin/panel-link.php') ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn sm gold" title="يولّد رابط يفتح لوحة العروسين مباشرة">رابط دخول العروسين</button></form>
<?php if ($r['panel_token']): ?><form method="post" action="<?= url('admin/panel-link.php') ?>" onsubmit="return confirm('إلغاء رابط الدخول المباشر الحالي؟')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn sm sec" name="revoke" value="1">إلغاء الرابط</button></form><?php endif; ?>
<form method="post" onsubmit="return confirm('حذف الدعوة نهائيًا؟')"><?= csrf_field() ?><button class="btn sm danger" name="delete" value="<?= $r['id'] ?>">حذف</button></form></div>
<?= $r['owner_email'] ? '<small class="muted">👤 دخول بالإيميل: ' . e($r['owner_email']) . '</small>' : '' ?>
</article>
<?php endforeach; ?></div>
<?php if ($recent): ?><div class="card"><h2>آخر الردود</h2><div class="feed">
<?php foreach ($recent as $r): ?><div class="it"><div class="av <?= $r['attending'] ? '' : 'no' ?>"><?= e(mb_substr($r['name'], 0, 1)) ?></div>
<div style="flex:1"><b><?= e($r['name']) ?></b> <small>— <?= e($r['groom']) ?> & <?= e($r['bride']) ?></small><br><small><?= e($r['created_at']) ?><?= $r['note'] ? ' · “' . e(mb_substr($r['note'], 0, 60)) . '”' : '' ?></small></div>
<span class="badge <?= $r['attending'] ? 'on' : 'off' ?>"><?= $r['attending'] ? 'حاضر · ' . (int)$r['party_size'] : 'معتذر' ?></span></div><?php endforeach; ?></div></div><?php endif; ?>
<?php endif; ?>
<?php admin_footer();
