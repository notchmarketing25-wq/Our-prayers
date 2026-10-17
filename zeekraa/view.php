<?php
require __DIR__ . '/lib/bootstrap.php'; require __DIR__ . '/lib/themes.php'; require __DIR__ . '/lib/music.php'; require __DIR__ . '/lib/i18n.php'; require __DIR__ . '/lib/covers.php';
$slug = $_GET['slug'] ?? '';
$inv = q('SELECT * FROM invitations WHERE slug=? AND is_active=1', [$slug])->fetch();
if (!$inv) { http_response_code(404); exit('<!doctype html><meta charset="utf-8"><body style="font-family:sans-serif;text-align:center;padding:4rem">الدعوة غير موجودة · Invitation not found'); }
$L = inv_lang($inv); $dir = $L === 'en' ? 'ltr' : 'rtl';
$T = fn(string $k, ...$a) => tr($k, $L, ...$a);
header('X-Robots-Tag: noindex, nofollow');

// بوابة كلمة السر
$gate = false;
if ($inv['access_password']) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['access'])) {
        csrf_check();
        if (password_verify($_POST['access'], $inv['access_password'])) $_SESSION['unlocked'][$inv['id']] = true; else flash($T('wrong_pw'));
        redirect($_SERVER['REQUEST_URI']);
    }
    $gate = empty($_SESSION['unlocked'][$inv['id']]);
}
$names = e($inv['groom'] . ' & ' . $inv['bride']);
$pageTitle = !empty($inv['page_title']) ? e($inv['page_title']) : $names;
$bodyStyle = !empty($inv['accent_color']) ? ' style="--accent:' . e($inv['accent_color']) . ';--on:' . contrast_on($inv['accent_color']) . '"' : '';
$cover = cover_url($inv);
?><!doctype html>
<html lang="<?= $L ?>" dir="<?= $dir ?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title><?= $pageTitle ?></title>
<meta property="og:title" content="<?= $pageTitle ?>"><meta property="og:type" content="website">
<meta property="og:description" content="<?= e(mb_substr($gate ? $T('private') : (string)(!empty($inv['page_desc']) ? $inv['page_desc'] : $inv['message']), 0, 200)) ?>">
<?php if ($cover && !$gate): ?><meta property="og:image" content="<?= e($cover) ?>"><?php endif; ?>
<link rel="stylesheet" href="<?= $L === 'en' ? 'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,700;1,500&family=Montserrat:wght@400;500;600&family=Anton&display=swap' : 'https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Cairo:wght@400;600;700;900&family=Anton&family=Aref+Ruqaa:wght@400;700&display=swap' ?>">
<link rel="stylesheet" href="<?= asset('assets/invite.css') ?>">
</head><body class="t-<?= e($inv['template']) ?> l-<?= $L ?><?= $gate ? '' : ' locked' ?>"<?= $bodyStyle ?>>
<?php if ($gate): ?>
<section class="hero"><div class="hero-in"><p class="kicker"><?= e($T('private')) ?></p><h1><?= $names ?></h1>
<form method="post" class="gate"><?= csrf_field() ?><?php if ($m = flash()): ?><p class="flash"><?= e($m) ?></p><?php endif; ?>
<input type="password" name="access" placeholder="<?= e($T('pw_ph')) ?>" required autofocus><button class="btn-i"><?= e($T('enter')) ?></button></form></div></section>
</body></html><?php exit; endif;

$events = q('SELECT * FROM events WHERE invitation_id=? ORDER BY event_at', [$inv['id']])->fetchAll();
$gallery = json_decode($inv['gallery'] ?? '[]', true) ?: [];
$guest = !empty($_GET['g']) ? (q('SELECT * FROM guests WHERE token=? AND invitation_id=?', [$_GET['g'], $inv['id']])->fetch() ?: null) : null;
$mine = $guest ? q('SELECT * FROM rsvps WHERE guest_id=? ORDER BY id DESC LIMIT 1', [$guest['id']])->fetch() : null;
$next = null; foreach ($events as $ev) if (strtotime($ev['event_at']) > time()) { $next = $ev; break; }
$next ??= $events[0] ?? null;
$ar = fn($ts) => fmt_dt($ts, $L);
?>
<noscript><style>.intro{display:none!important}.photo-frame{clip-path:none!important}body.locked{overflow:auto!important}body.locked .hero-in>*{opacity:1!important}.gal-g img{opacity:1!important;transform:none!important}</style></noscript>
<div class="progress" id="prog"></div><div class="fx" id="fx" aria-hidden="true"></div>
<div class="intro" id="intro" role="button" tabindex="0" aria-label="<?= e($T('open_aria')) ?>">
  <div class="env"><div class="flap"></div><div class="seal">♡</div></div>
  <p class="in-names"><?= !empty($inv['intro_title']) ? e($inv['intro_title']) : $names ?></p><?php if (!empty($inv['intro_text'])): ?><p class="in-text"><?= e($inv['intro_text']) ?></p><?php endif; ?><p class="in-hint"><?= e($T('tap')) ?></p>
</div>
<script>/* احتياطي: يفتح الظرف حتى لو ملف invite.js ماتحملش */
(function(){var i=document.getElementById('intro'),b=document.body;if(!i)return;
function o(){if(i.className.indexOf('open')>-1)return;i.className+=' open';b.classList.remove('locked');b.classList.add('go');
 var a=document.getElementById('bg');try{a&&a.play&&a.play()}catch(e){}}
i.onclick=o;i.onkeydown=function(e){if(e.key==='Enter'||e.key===' ')o()};
setTimeout(function(){if(i.className.indexOf('open')<0&&!window.__inviteReady){i.style.opacity='.0';i.style.pointerEvents='none';b.classList.remove('locked');b.classList.add('go')}},9000)})();</script>
<?php if ($cover): ?>
<header class="photo" id="photo"><div class="photo-frame"><img class="photo-img" id="photoImg" src="<?= e($cover) ?>" alt="<?= $names ?>" fetchpriority="high"></div>
<?php if (!empty($inv['photo_caption'])): ?><div class="photo-cap pos-<?= e($inv['caption_pos'] ?: 'bottom') ?> cs-<?= e($inv['caption_style'] ?: 'pill') ?> cc-<?= e($inv['caption_color'] ?: 'light') ?> sz-<?= e($inv['caption_size'] ?: 'md') ?>"><p><?= nl2br(e($inv['photo_caption'])) ?></p></div><?php endif; ?>
<span class="cue" aria-hidden="true">⌄</span></header>
<?php endif; ?>
<section class="hero<?= $cover ? ' below' : '' ?>"><div class="hero-in">
<?php if ($guest): ?><p class="kicker"><?= e($T('dear', $guest['name'])) ?></p><?php else: ?><p class="kicker"><?= e($T('invited')) ?></p><?php endif; ?>
<h1><span><?= e($inv['groom']) ?></span><i>&amp;</i><span><?= e($inv['bride']) ?></span></h1>
<svg class="orn" viewBox="0 0 200 20" aria-hidden="true"><path d="M2 10h72M126 10h72" /><path d="M100 3c4 4 4 10 0 14-4-4-4-10 0-14z"/><circle cx="86" cy="10" r="2"/><circle cx="114" cy="10" r="2"/></svg>
<?php if ($inv['message']): ?><p class="msg"><?= nl2br(e($inv['message'])) ?></p><?php endif; ?>
<?php if ($next): [$d] = $ar(strtotime($next['event_at'])); ?><p class="date"><?= e($d) ?></p><?php endif; ?>
<a href="#events" class="btn-i shine"><?= e($T('details')) ?></a>
<?php if (!$cover): ?><span class="cue" aria-hidden="true">⌄</span><?php endif; ?></div></section>

<?php if ($next): ?><section class="count"><div id="cd" data-ts="<?= (int)(new DateTime($next['event_at'], new DateTimeZone('Africa/Cairo')))->getTimestamp() ?>">
<div><b id="cd-d">0</b><small><?= e($T('d')) ?></small></div><div><b id="cd-h">0</b><small><?= e($T('h')) ?></small></div><div><b id="cd-m">0</b><small><?= e($T('m')) ?></small></div><div><b id="cd-s">0</b><small><?= e($T('s')) ?></small></div></div></section><?php endif; ?>

<section id="events" class="events"><?php $evT = sec_title($inv, 'events', $T('events')); if ($evT !== ''): ?><h2><?= e($evT) ?></h2><?php endif; ?>
<?php foreach ($events as $ev): [$d, $t] = $ar(strtotime($ev['event_at'])); ?>
<article class="ev"><h3><?= e($ev['title']) ?></h3><p class="when"><?= e($d) ?> · <?= e($t) ?></p>
<?php if ($ev['venue']): ?><p class="where"><?= e($ev['venue']) ?><?= $ev['address'] ? ' — ' . e($ev['address']) : '' ?></p><?php endif; ?>
<?php if (!empty($ev['note'])): ?><p class="ev-note"><?= e($ev['note']) ?></p><?php endif; ?>
<p class="acts"><?php if ($ev['map_url']): ?><a class="btn-i sm" target="_blank" rel="noopener" href="<?= e($ev['map_url']) ?>"><?= e($T('map')) ?></a><?php endif; ?>
<a class="btn-i sm ghost" href="<?= url('ics.php?e=' . $ev['id']) ?>"><?= e($T('cal')) ?></a></p></article>
<?php endforeach; ?></section>

<?php $notes = json_decode($inv['notes'] ?? '[]', true) ?: []; if ($notes): ?>
<section class="notes"><?php if (($nT = sec_title($inv, 'notes', $T('notes'))) !== ''): ?><h2><?= e($nT) ?></h2><?php endif; ?><div class="notes-g">
<?php foreach ($notes as $i => $n): ?><article class="note" style="--d:<?= $i * .1 ?>s"><div><?php if (!empty($n['title'])): ?><h4><?= e($n['title']) ?></h4><?php endif; ?><p><?= nl2br(e($n['text'])) ?></p></div></article><?php endforeach; ?>
</div></section><?php endif; ?>

<?php if ($gallery): ?><section class="gal"><?php if (($gT = sec_title($inv, 'gallery', $T('moments'))) !== ''): ?><h2><?= e($gT) ?></h2><?php endif; ?><div class="gal-g"><?php foreach ($gallery as $g): ?><img loading="lazy" tabindex="0" role="button" aria-label="<?= e($T('view_img')) ?>" src="<?= e(url('uploads/' . $g)) ?>" alt=""><?php endforeach; ?></div></section><?php endif; ?>

<section id="rsvp" class="rsvp"><?php if (($rT = sec_title($inv, 'rsvp', $T('rsvp'))) !== ''): ?><h2><?= e($rT) ?></h2><?php endif; ?>
<?php $okFlash = !empty($_SESSION['flash_ok']); unset($_SESSION['flash_ok']); if ($m = flash()): ?><p class="flash"<?= $okFlash ? ' data-ok="1"' : '' ?>><?= e($m) ?></p><?php endif; ?>
<?php if ($mine): ?><p class="flash"><?= e($mine['attending'] ? $T('cur_yes', (int)$mine['party_size']) : $T('cur_no')) ?></p><?php endif; ?>
<form method="post" action="<?= url('rsvp.php') ?>"><?= csrf_field() ?>
<input type="hidden" name="slug" value="<?= e($inv['slug']) ?>"><input type="hidden" name="g" value="<?= e($guest['token'] ?? '') ?>">
<?php if (!$guest): ?><input name="name" placeholder="<?= e($T('name')) ?>" required maxlength="120"><input name="phone" placeholder="<?= e($T('phone')) ?>" inputmode="tel" maxlength="30"><?php endif; ?>
<div class="choice"><label><input type="radio" name="attending" value="1" checked> <?= e($T('attend')) ?></label><label><input type="radio" name="attending" value="0"> <?= e($T('decline')) ?></label></div>
<label class="ps"><?= e($T('party')) ?> <input type="number" name="party_size" min="1" max="<?= $guest ? (int)$guest['max_guests'] : 10 ?>" value="<?= $mine ? max(1, (int)$mine['party_size']) : 1 ?>"></label>
<input name="note" placeholder="<?= e($T('note')) ?>" maxlength="500">
<button class="btn-i"><?= e($T('send')) ?></button></form></section>

<div class="lb" id="lb" hidden><button class="lb-x" id="lbX" aria-label="<?= e($T('close')) ?>">✕</button><button class="lb-n" id="lbN" aria-label="<?= e($T('next')) ?>">‹</button><img id="lbImg" alt=""><button class="lb-p" id="lbP" aria-label="<?= e($T('prev')) ?>">›</button></div>
<footer><p><?= $names ?></p><p class="credit"><?= e($T('credit')) ?> <a href="<?= e(url()) ?>"><?= e(cfg('site_name')) ?></a></p></footer>
<?php if ($music = music_source($inv)): ?><?php if ($music['type'] === 'youtube'): ?><div id="yt" data-id="<?= e($music['id']) ?>" style="position:fixed;width:1px;height:1px;opacity:0;pointer-events:none;left:-10px;bottom:0;overflow:hidden"><div id="ytp"></div></div><?php else: ?><audio id="bg" src="<?= e($music['src']) ?>" loop preload="none"></audio><?php endif; ?><button id="mus" class="mus" aria-label="<?= e($T('music')) ?>">♪</button><?php endif; ?>
<script src="<?= asset('assets/invite.js') ?>"></script>
</body></html>
