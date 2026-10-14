<?php
require __DIR__ . '/lib/bootstrap.php';
$slug = $_GET['slug'] ?? '';
$inv = q('SELECT * FROM invitations WHERE slug=? AND is_active=1', [$slug])->fetch();
if (!$inv) { http_response_code(404); exit('<!doctype html><meta charset="utf-8"><body dir="rtl" style="font-family:sans-serif;text-align:center;padding:4rem">الدعوة غير موجودة.'); }
header('X-Robots-Tag: noindex, nofollow');

// بوابة كلمة السر
$gate = false;
if ($inv['access_password']) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['access'])) {
        csrf_check();
        if (password_verify($_POST['access'], $inv['access_password'])) $_SESSION['unlocked'][$inv['id']] = true; else flash('كلمة السر غلط.');
        redirect($_SERVER['REQUEST_URI']);
    }
    $gate = empty($_SESSION['unlocked'][$inv['id']]);
}
$names = e($inv['groom'] . ' & ' . $inv['bride']);
$cover = $inv['cover_image'] ? url('uploads/' . $inv['cover_image']) : '';
?><!doctype html>
<html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title><?= $names ?></title>
<meta property="og:title" content="<?= $names ?>"><meta property="og:type" content="website">
<meta property="og:description" content="<?= e(mb_substr($gate ? 'دعوة خاصة' : (string)$inv['message'], 0, 150)) ?>">
<?php if ($cover && !$gate): ?><meta property="og:image" content="<?= e($cover) ?>"><?php endif; ?>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Cairo:wght@400;600;700&family=Aref+Ruqaa:wght@400;700&display=swap">
<link rel="stylesheet" href="<?= url('assets/invite.css') ?>">
</head><body class="t-<?= e($inv['template']) ?><?= $gate ? '' : ' locked' ?>">
<?php if ($gate): ?>
<section class="hero"><div class="hero-in"><p class="kicker">دعوة خاصة</p><h1><?= $names ?></h1>
<form method="post" class="gate"><?= csrf_field() ?><?php if ($m = flash()): ?><p class="flash"><?= e($m) ?></p><?php endif; ?>
<input type="password" name="access" placeholder="اكتب كلمة السر" required autofocus><button class="btn-i">دخول</button></form></div></section>
</body></html><?php exit; endif;

$events = q('SELECT * FROM events WHERE invitation_id=? ORDER BY event_at', [$inv['id']])->fetchAll();
$gallery = json_decode($inv['gallery'] ?? '[]', true) ?: [];
$guest = !empty($_GET['g']) ? (q('SELECT * FROM guests WHERE token=? AND invitation_id=?', [$_GET['g'], $inv['id']])->fetch() ?: null) : null;
$mine = $guest ? q('SELECT * FROM rsvps WHERE guest_id=? ORDER BY id DESC LIMIT 1', [$guest['id']])->fetch() : null;
$next = null; foreach ($events as $ev) if (strtotime($ev['event_at']) > time()) { $next = $ev; break; }
$next ??= $events[0] ?? null;
$ar = fn($ts) => (function($ts){
    $days = ['الأحد','الإثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت'];
    $mon = ['','يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
    $h = (int)date('g', $ts); $ap = date('A', $ts) === 'AM' ? 'صباحًا' : 'مساءً';
    return [$days[(int)date('w', $ts)] . ' ' . date('j', $ts) . ' ' . $mon[(int)date('n', $ts)] . ' ' . date('Y', $ts), $h . ':' . date('i', $ts) . ' ' . $ap];
})($ts);
?>
<noscript><style>.intro{display:none!important}body.locked{overflow:auto!important}body.locked .hero-in>*{opacity:1!important}.gal-g img{opacity:1!important;transform:none!important}</style></noscript>
<div class="progress" id="prog"></div><div class="fx" id="fx" aria-hidden="true"></div>
<div class="intro" id="intro" role="button" tabindex="0" aria-label="افتح الدعوة">
  <div class="env"><div class="flap"></div><div class="seal">♡</div></div>
  <p class="in-names"><?= $names ?></p><p class="in-hint">اضغط لفتح الدعوة</p>
</div>
<section class="hero" <?= $cover ? 'style="--cover:url(' . e($cover) . ')"' : '' ?>><div class="hero-in">
<?php if ($guest): ?><p class="kicker">أهلًا <?= e($guest['name']) ?></p><?php else: ?><p class="kicker">نتشرف بدعوتكم</p><?php endif; ?>
<h1><span><?= e($inv['groom']) ?></span><i>&amp;</i><span><?= e($inv['bride']) ?></span></h1>
<?php if ($inv['message']): ?><p class="msg"><?= nl2br(e($inv['message'])) ?></p><?php endif; ?>
<?php if ($next): [$d] = $ar(strtotime($next['event_at'])); ?><p class="date"><?= e($d) ?></p><?php endif; ?>
<a href="#events" class="btn-i shine">التفاصيل</a>
<span class="cue" aria-hidden="true">⌄</span></div></section>

<?php if ($next): ?><section class="count"><div id="cd" data-ts="<?= (int)(new DateTime($next['event_at'], new DateTimeZone('Africa/Cairo')))->getTimestamp() ?>">
<div><b id="cd-d">0</b><small>يوم</small></div><div><b id="cd-h">0</b><small>ساعة</small></div><div><b id="cd-m">0</b><small>دقيقة</small></div><div><b id="cd-s">0</b><small>ثانية</small></div></div></section><?php endif; ?>

<section id="events" class="events"><h2>المناسبات</h2>
<?php foreach ($events as $ev): [$d, $t] = $ar(strtotime($ev['event_at'])); ?>
<article class="ev"><h3><?= e($ev['title']) ?></h3><p class="when"><?= e($d) ?> · <?= e($t) ?></p>
<?php if ($ev['venue']): ?><p class="where"><?= e($ev['venue']) ?><?= $ev['address'] ? ' — ' . e($ev['address']) : '' ?></p><?php endif; ?>
<p class="acts"><?php if ($ev['map_url']): ?><a class="btn-i sm" target="_blank" rel="noopener" href="<?= e($ev['map_url']) ?>">افتح الخريطة</a><?php endif; ?>
<a class="btn-i sm ghost" href="<?= url('ics.php?e=' . $ev['id']) ?>">أضف للتقويم</a></p></article>
<?php endforeach; ?></section>

<?php if ($gallery): ?><section class="gal"><h2>لحظاتنا</h2><div class="gal-g"><?php foreach ($gallery as $g): ?><img loading="lazy" src="<?= e(url('uploads/' . $g)) ?>" alt=""><?php endforeach; ?></div></section><?php endif; ?>

<section id="rsvp" class="rsvp"><h2>تأكيد الحضور</h2>
<?php if ($m = flash()): ?><p class="flash"><?= e($m) ?></p><?php endif; ?>
<?php if ($mine): ?><p class="flash">ردك الحالي: <?= $mine['attending'] ? 'حاضر (' . (int)$mine['party_size'] . ')' : 'معتذر' ?> — تقدر تغيّره من تحت.</p><?php endif; ?>
<form method="post" action="<?= url('rsvp.php') ?>"><?= csrf_field() ?>
<input type="hidden" name="slug" value="<?= e($inv['slug']) ?>"><input type="hidden" name="g" value="<?= e($guest['token'] ?? '') ?>">
<?php if (!$guest): ?><input name="name" placeholder="الاسم" required maxlength="120"><input name="phone" placeholder="رقم الموبايل (اختياري)" inputmode="tel" maxlength="30"><?php endif; ?>
<div class="choice"><label><input type="radio" name="attending" value="1" checked> هحضر إن شاء الله</label><label><input type="radio" name="attending" value="0"> أعتذر</label></div>
<label class="ps">عدد الأفراد <input type="number" name="party_size" min="1" max="<?= $guest ? (int)$guest['max_guests'] : 10 ?>" value="<?= $mine ? max(1, (int)$mine['party_size']) : 1 ?>"></label>
<input name="note" placeholder="رسالة للعروسين (اختياري)" maxlength="500">
<button class="btn-i">إرسال</button></form></section>

<footer><p><?= $names ?></p><p class="credit">اعمل دعوتك من <a href="<?= e(url()) ?>"><?= e(cfg('site_name')) ?></a></p></footer>
<?php if ($safe = safe_url($inv['music_url'])): ?><audio id="bg" src="<?= e($safe) ?>" loop preload="none"></audio><button id="mus" class="mus" aria-label="موسيقى">♪</button><?php endif; ?>
<script src="<?= url('assets/invite.js') ?>"></script>
</body></html>
