<?php
require __DIR__ . '/../lib/bootstrap.php'; require __DIR__ . '/../lib/admin_layout.php'; require_admin();
$id = (int)($_GET['id'] ?? 0);
$inv = q('SELECT * FROM invitations WHERE id=?', [$id])->fetch() or exit('مش موجودة');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (isset($_POST['del'])) q('DELETE FROM guests WHERE id=? AND invitation_id=?', [(int)$_POST['del'], $id]);
    elseif (isset($_POST['bulk'])) {
        $n = 0;
        foreach (preg_split('/\R/u', trim($_POST['bulk'])) as $line) {
            // الصيغة: الاسم | الموبايل | عدد الأفراد (الموبايل والعدد اختياريين)
            $p = array_map('trim', explode('|', $line)); if ($p[0] === '') continue;
            q('INSERT INTO guests (invitation_id,name,phone,max_guests,token) VALUES (?,?,?,?,?)', [$id, mb_substr($p[0], 0, 120), $p[1] ?? null ?: null, max(1, min(20, (int)($p[2] ?? 1))), bin2hex(random_bytes(8))]); $n++;
        }
        flash("اتضاف $n ضيف.");
    }
    redirect(url('admin/guests.php?id=' . $id));
}
$guests = q('SELECT g.*, r.attending, r.party_size FROM guests g LEFT JOIN rsvps r ON r.guest_id=g.id AND r.id=(SELECT MAX(id) FROM rsvps WHERE guest_id=g.id) WHERE g.invitation_id=? ORDER BY g.id', [$id])->fetchAll();
$first = q('SELECT title, event_at FROM events WHERE invitation_id=? ORDER BY event_at LIMIT 1', [$id])->fetch();
admin_header('الضيوف'); ?>
<div class="card"><h2>ضيوف: <?= e($inv['groom']) ?> & <?= e($inv['bride']) ?></h2>
<form method="post"><?= csrf_field() ?>
<label>ضيف في كل سطر: <code>الاسم | الموبايل | عدد الأفراد</code> (الموبايل والعدد اختياريين)
<textarea name="bulk" rows="4" placeholder="خالو محمود | 01012345678 | 3&#10;طنط سلوى | 01198765432 | 2"></textarea></label>
<button class="btn">أضف</button></form></div>
<div class="card"><table><tr><th>الضيف</th><th>الأفراد</th><th>الحالة</th><th>اللينك الخاص</th><th></th></tr>
<?php foreach ($guests as $g):
    $link = url($inv['slug']) . '?g=' . $g['token'];
    $text = "أهلًا {$g['name']} 🤍\nيشرفنا حضورك " . ($first['title'] ?? 'مناسبتنا') . " {$inv['groom']} & {$inv['bride']}\nدعوتك الخاصة:\n$link";
    $wa = 'https://wa.me/' . ($g['phone'] ? normalize_phone_eg($g['phone']) : '') . '?text=' . rawurlencode($text); ?>
<tr><td><?= e($g['name']) ?><br><small class="muted"><?= e($g['phone']) ?></small></td><td><?= (int)$g['max_guests'] ?></td>
<td><?= $g['attending'] === null ? '— لم يرد' : ($g['attending'] ? '✓ حاضر (' . (int)$g['party_size'] . ')' : '✗ معتذر') ?></td>
<td><input readonly value="<?= e($link) ?>" onclick="this.select()" style="min-width:200px"></td>
<td style="white-space:nowrap"><a class="btn sm" target="_blank" href="<?= e($wa) ?>">واتساب</a>
<form method="post" style="display:inline"><?= csrf_field() ?><button class="btn sm danger" name="del" value="<?= $g['id'] ?>">حذف</button></form></td></tr>
<?php endforeach; if (!$guests): ?><tr><td colspan="5" class="muted">مفيش ضيوف لسه.</td></tr><?php endif; ?></table></div>
<a class="btn sec" href="<?= url('admin/') ?>">رجوع</a>
<?php admin_footer();
