<?php
require __DIR__ . '/../lib/bootstrap.php'; require __DIR__ . '/../lib/themes.php'; require __DIR__ . '/../lib/i18n.php';
if (empty($_SESSION['client'])) redirect(url('panel/login.php'));
$id = (int)$_SESSION['client'];
$inv = q('SELECT * FROM invitations WHERE id=? AND is_active=1', [$id])->fetch();
if (!$inv) { unset($_SESSION['client']); redirect(url('panel/login.php')); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (isset($_POST['del'])) { q('DELETE FROM guests WHERE id=? AND invitation_id=?', [(int)$_POST['del'], $id]); flash('تم حذف الضيف.'); }
    elseif (isset($_POST['bulk'])) {
        $n = 0;
        foreach (preg_split('/\R/u', trim($_POST['bulk'])) as $line) {
            // الصيغة: الاسم | الموبايل | عدد الأفراد
            $p = array_map('trim', explode('|', $line)); if ($p[0] === '') continue;
            q('INSERT INTO guests (invitation_id,name,phone,max_guests,token) VALUES (?,?,?,?,?)', [$id, mb_substr($p[0], 0, 120), ($p[1] ?? '') ?: null, max(1, min(20, (int)($p[2] ?? 1))), bin2hex(random_bytes(8))]); $n++;
        }
        flash("اتضاف $n ضيف ✓");
    }
    redirect(url('panel/') . '#' . ($_POST['tab'] ?? 'guests'));
}

$accent = $inv['accent_color'] ?: (THEME_ACCENTS[$inv['template']] ?? '#b08a3e');
$on = contrast_on($accent);
$first = q('SELECT * FROM events WHERE invitation_id=? ORDER BY event_at LIMIT 1', [$id])->fetch();
$guests = q('SELECT g.*, r.attending, r.party_size, r.created_at replied_at FROM guests g
  LEFT JOIN rsvps r ON r.guest_id=g.id AND r.id=(SELECT MAX(id) FROM rsvps WHERE guest_id=g.id)
  WHERE g.invitation_id=? ORDER BY g.id', [$id])->fetchAll();
$rsvps = q('SELECT * FROM rsvps WHERE invitation_id=? ORDER BY id DESC', [$id])->fetchAll();

$evTitle = trim((string)($first['title'] ?? '')) ?: (inv_lang($inv) === 'en' ? 'our celebration' : 'مناسبتنا');
$nGuests = count($guests);
$replied = count(array_filter($guests, fn($g) => $g['attending'] !== null));
$pending = $nGuests - $replied;
$heads = array_sum(array_map(fn($r) => $r['attending'] ? (int)$r['party_size'] : 0, $rsvps));
$yes = count(array_filter($rsvps, fn($r) => $r['attending']));
$no = count($rsvps) - $yes;
$rate = $nGuests ? round($replied / $nGuests * 100) : 0;
$days = ($first && $first['event_at']) ? max(0, (int)ceil((strtotime($first['event_at']) - time()) / 86400)) : null;
$link = url($inv['slug']);
$ago = function (string $t) { $d = time() - strtotime($t);
    return $d < 60 ? 'دلوقتي' : ($d < 3600 ? 'من ' . (int)($d / 60) . ' دقيقة' : ($d < 86400 ? 'من ' . (int)($d / 3600) . ' ساعة' : 'من ' . (int)($d / 86400) . ' يوم')); };
?><!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow"><title>لوحة <?= e($inv['groom'] . ' & ' . $inv['bride']) ?></title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Amiri:wght@700&family=Cairo:wght@400;600;700&display=swap">
<link rel="stylesheet" href="<?= asset('assets/panel.css') ?>"></head>
<body style="--accent:<?= e($accent) ?>;--on:<?= e($on) ?>">
<header class="top"><div class="wrap bar"><div class="brand"><span class="heart">♡</span><div><b><?= e($inv['groom']) ?> & <?= e($inv['bride']) ?></b><small>لوحة المدعوين</small></div></div>
<nav><a class="pill ghost" target="_blank" href="<?= e($link) ?>">شوف الدعوة ↗</a><a class="pill ghost" href="<?= url('panel/logout.php') ?>">خروج</a></nav></div></header>

<main class="wrap">
<?php if ($m = flash()): ?><div class="toast"><?= e($m) ?></div><?php endif; ?>

<section class="welcome rise"><div><h1>أهلًا يا <?= e($inv['groom']) ?> و<?= e($inv['bride']) ?> 🤍</h1>
<p><?= $days !== null ? ($days > 0 ? 'فاضل <b class="cnt" data-n="' . $days . '">0</b> يوم على ' . ($first['title'] !== '' ? e($first['title']) : 'الميعاد') : 'النهاردة ' . ($first['title'] !== '' ? e($first['title']) . '، ' : '') . 'مبروك! 🎉') : '' ?></p></div>
<div class="share"><input id="lnk" readonly dir="ltr" value="<?= e($link) ?>"><button class="pill" id="copy" type="button">نسخ الرابط</button>
<a class="pill wa" target="_blank" rel="noopener" href="https://wa.me/?text=<?= rawurlencode(inv_lang($inv) === 'en' ? "We would be honoured by your presence 🤍\n{$inv['groom']} & {$inv['bride']}\n$link" : "يشرفنا حضورك 🤍\n{$inv['groom']} & {$inv['bride']}\n$link") ?>">واتساب</a></div></section>

<section class="stats">
<div class="stat rise" style="--i:0"><span class="ico">✉️</span><b class="cnt" data-n="<?= $nGuests ?>">0</b><small>مدعو</small></div>
<div class="stat rise" style="--i:1"><span class="ico">✅</span><b class="cnt" data-n="<?= $heads ?>">0</b><small>فرد حاضر</small></div>
<div class="stat rise" style="--i:2"><span class="ico">🕊️</span><b class="cnt" data-n="<?= $no ?>">0</b><small>معتذر</small></div>
<div class="stat rise" style="--i:3"><span class="ico">⏳</span><b class="cnt" data-n="<?= $pending ?>">0</b><small>لسه ماردّش</small></div>
<div class="stat ring rise" style="--i:4"><svg viewBox="0 0 36 36"><circle class="bg" cx="18" cy="18" r="15.9"/><circle class="fg" cx="18" cy="18" r="15.9" style="--p:<?= $rate ?>"/></svg><div><b class="cnt" data-n="<?= $rate ?>" data-s="%">0</b><small>نسبة الرد</small></div></div>
</section>

<div class="tabs" role="tablist"><button class="tab on" data-t="replies">الردود <i><?= count($rsvps) ?></i></button><button class="tab" data-t="guests">المدعوين <i><?= $nGuests ?></i></button></div>

<section id="replies" class="pane on">
<div class="tools"><input id="q" type="search" placeholder="🔍 دوّر باسم..."><div class="chips"><button class="chip on" data-f="all">الكل</button><button class="chip" data-f="1">حاضر</button><button class="chip" data-f="0">معتذر</button></div>
<a class="pill ghost" href="<?= url('panel/export.php') ?>">تحميل Excel/CSV</a></div>
<div class="list">
<?php foreach ($rsvps as $i => $r): ?>
<article class="row rise" style="--i:<?= min($i, 12) ?>" data-s="<?= (int)$r['attending'] ?>" data-n="<?= e(mb_strtolower($r['name'])) ?>">
<div class="av <?= $r['attending'] ? 'yes' : 'no' ?>"><?= e(mb_substr($r['name'], 0, 1)) ?></div>
<div class="who"><b><?= e($r['name']) ?></b><small><?= e($r['phone'] ?: '') ?> <?= $ago($r['created_at']) ?></small>
<?php if ($r['note']): ?><p class="note">“<?= e($r['note']) ?>”</p><?php endif; ?></div>
<span class="tag <?= $r['attending'] ? 'yes' : 'no' ?>"><?= $r['attending'] ? 'حاضر · ' . (int)$r['party_size'] : 'معتذر' ?></span></article>
<?php endforeach; if (!$rsvps): ?><div class="empty"><div>💌</div><p>لسه محدش رد. شاركوا رابط الدعوة وهتظهر الردود هنا لحظة بلحظة.</p></div><?php endif; ?>
<div class="empty" id="none" hidden><p>مفيش نتايج.</p></div></div></section>

<section id="guests" class="pane">
<div class="card"><h3>إضافة مدعوين</h3><p class="muted">اكتب ضيف في كل سطر: <code>الاسم | الموبايل | عدد الأفراد</code> (الموبايل والعدد اختياريين) — كل ضيف بياخد لينك خاص باسمه.</p>
<form method="post"><?= csrf_field() ?><input type="hidden" name="tab" value="guests"><textarea name="bulk" rows="4" placeholder="خالو محمود | 01012345678 | 3&#10;طنط سلوى | 01198765432 | 2" required></textarea><button class="pill big">أضف</button></form></div>
<div class="list">
<?php foreach ($guests as $g):
    $gl = $link . '?g=' . $g['token'];
    $L = inv_lang($inv);
    $txt = $g['attending'] === null
        ? tr('wa_invite', $L, $g['name'], $evTitle, $inv['groom'] . ' & ' . $inv['bride'], $gl)
        : tr('wa_thanks', $L, $g['name'], $gl);
    $wa = 'https://wa.me/' . ($g['phone'] ? normalize_phone_eg($g['phone']) : '') . '?text=' . rawurlencode($txt); ?>
<article class="row rise" style="--i:0"><div class="av"><?= e(mb_substr($g['name'], 0, 1)) ?></div>
<div class="who"><b><?= e($g['name']) ?></b><small><?= e($g['phone'] ?: 'بدون رقم') ?> · لحد <?= (int)$g['max_guests'] ?> أفراد</small></div>
<span class="tag <?= $g['attending'] === null ? 'wait' : ($g['attending'] ? 'yes' : 'no') ?>"><?= $g['attending'] === null ? 'لم يرد' : ($g['attending'] ? 'حاضر · ' . (int)$g['party_size'] : 'معتذر') ?></span>
<div class="acts"><button class="pill sm copy" type="button" data-l="<?= e($gl) ?>">نسخ</button><a class="pill sm wa" target="_blank" rel="noopener" href="<?= e($wa) ?>"><?= $g['attending'] === null ? 'ابعت' : 'واتساب' ?></a>
<form method="post" onsubmit="return confirm('حذف <?= e(addslashes($g['name'])) ?>؟')"><?= csrf_field() ?><input type="hidden" name="tab" value="guests"><button class="pill sm danger" name="del" value="<?= $g['id'] ?>">✕</button></form></div></article>
<?php endforeach; if (!$guests): ?><div class="empty"><div>👥</div><p>ضيفوا المدعوين هنا عشان كل واحد ياخد لينك باسمه وتعرفوا مين ماردّش.</p></div><?php endif; ?>
</div></section>
</main>
<script src="<?= asset('assets/panel.js') ?>"></script></body></html>
