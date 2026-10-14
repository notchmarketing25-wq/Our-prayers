<?php
require __DIR__ . '/lib/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(url());
csrf_check();
$slug = $_POST['slug'] ?? '';
$inv = q('SELECT * FROM invitations WHERE slug=? AND is_active=1', [$slug])->fetch() or exit('مش موجودة');
$guest = null;
if (!empty($_POST['g'])) $guest = q('SELECT * FROM guests WHERE token=? AND invitation_id=?', [$_POST['g'], $inv['id']])->fetch() ?: null;
$name = trim($_POST['name'] ?? ''); $att = ($_POST['attending'] ?? '') === '1' ? 1 : 0;
$max = $guest ? (int)$guest['max_guests'] : 10;
$size = $att ? max(1, min($max, (int)($_POST['party_size'] ?? 1))) : 0;
if ($guest) $name = $guest['name'];
$back = url($slug) . ($guest ? '?g=' . $guest['token'] : '');
if ($name === '') { flash('اكتب اسمك من فضلك.'); redirect($back . '#rsvp'); }
// منع التكرار والإغراق: آخر رد من نفس الجلسة
if (($_SESSION['last_rsvp'] ?? 0) > time() - 5) { flash('استنى لحظة وحاول تاني.'); redirect($back . '#rsvp'); }
$_SESSION['last_rsvp'] = time();
if ($guest) q('DELETE FROM rsvps WHERE guest_id=?', [$guest['id']]);
q('INSERT INTO rsvps (invitation_id,guest_id,name,phone,attending,party_size,note) VALUES (?,?,?,?,?,?,?)',
  [$inv['id'], $guest['id'] ?? null, mb_substr($name, 0, 120), mb_substr(trim($_POST['phone'] ?? ''), 0, 30) ?: ($guest['phone'] ?? null), $att, $size, mb_substr(trim($_POST['note'] ?? ''), 0, 500) ?: null]);
flash($att ? 'تم تأكيد حضورك، نتشرف بيك 🤍' : 'وصلنا اعتذارك، شكرًا لتواصلك.');
redirect($back . '#rsvp');
