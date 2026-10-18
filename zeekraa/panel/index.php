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
$L = inv_lang($inv);
$first = q('SELECT * FROM events WHERE invitation_id=? ORDER BY event_at LIMIT 1', [$id])->fetch();
$evTitle = trim((string)($first['title'] ?? '')) ?: ($L === 'en' ? 'our celebration' : 'مناسبتنا');
$guests = q('SELECT g.*, r.attending, r.party_size, r.created_at replied_at FROM guests g
  LEFT JOIN rsvps r ON r.guest_id=g.id AND r.id=(SELECT MAX(id) FROM rsvps WHERE guest_id=g.id)
  WHERE g.invitation_id=? ORDER BY g.id', [$id])->fetchAll();
$rsvps = q('SELECT * FROM rsvps WHERE invitation_id=? ORDER BY id DESC', [$id])->fetchAll();
$daily = []; foreach (q('SELECT DATE(created_at) d, COUNT(*) n FROM rsvps WHERE invitation_id=? AND created_at >= (CURDATE() - INTERVAL 13 DAY) GROUP BY d', [$id])->fetchAll() as $r) $daily[$r['d']] = (int)$r['n'];

$nGuests = count($guests);
$replied = count(array_filter($guests, fn($g) => $g['attending'] !== null));
$pending = $nGuests - $replied;
$heads = array_sum(array_map(fn($r) => $r['attending'] ? (int)$r['party_size'] : 0, $rsvps));
$yes = count(array_filter($rsvps, fn($r) => $r['attending']));
$no = count($rsvps) - $yes;
$rate = $nGuests ? round($replied / $nGuests * 100) : 0;
$today = count(array_filter($rsvps, fn($r) => substr($r['created_at'], 0, 10) === date('Y-m-d')));
$days = ($first && $first['event_at']) ? max(0, (int)ceil((strtotime($first['event_at']) - time()) / 86400)) : null;
$link = url($inv['slug']);
$ago = function (string $t) { $d = time() - strtotime($t);
    return $d < 60 ? 'دلوقتي' : ($d < 3600 ? 'من ' . (int)($d / 60) . ' دقيقة' : ($d < 86400 ? 'من ' . (int)($d / 3600) . ' ساعة' : 'من ' . (int)($d / 86400) . ' يوم')); };

/** أيقونات SVG */
function ic(string $n): string {
    static $p = [
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="3"/><path d="m4 7 8 6 8-6"/>',
        'check' => '<circle cx="12" cy="12" r="9"/><path d="m8 12.5 2.8 2.8L16 9.5"/>',
        'x' => '<circle cx="12" cy="12" r="9"/><path d="m9 9 6 6m0-6-6 6"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'link' => '<path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/>',
        'copy' => '<rect x="9" y="9" width="11" height="11" rx="2.5"/><path d="M5 15V6a2 2 0 0 1 2-2h8"/>',
        'wa' => '<path d="M20 12a8 8 0 0 1-11.7 7L4 20l1.1-4.1A8 8 0 1 1 20 12Z"/><path d="M9 9c.3 2 2 4 4.5 4.8l1.2-1.2-1.6-.9-.7.6c-.8-.3-1.6-1.1-1.9-1.9l.6-.7-.9-1.6Z"/>',
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.9 4.9l1.4 1.4m11.4 11.4 1.4 1.4M2 12h2m16 0h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
        'moon' => '<path d="M20 14.5A8 8 0 0 1 9.5 4 8 8 0 1 0 20 14.5Z"/>',
        'out' => '<path d="M9 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h3"/><path d="m16 8 4 4-4 4M20 12H9"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'dl' => '<path d="M12 4v10m0 0-4-4m4 4 4-4M5 20h14"/>',
        'trash' => '<path d="M4 7h16M10 11v6m4-6v6M6 7l1 12h10l1-12M9 7V4h6v3"/>',
        'ext' => '<path d="M14 4h6v6M20 4l-9 9M10 6H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0M16 4.5a3.5 3.5 0 0 1 0 7M18 14.5a6.5 6.5 0 0 1 3.5 5.5"/>',
    ];
    return '<svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($p[$n] ?? '') . '</svg>';
}
// رسم بياني: ردود آخر 14 يوم
$bars = []; $max = 1;
for ($i = 13; $i >= 0; $i--) { $d = date('Y-m-d', strtotime("-$i day")); $bars[] = [$d, $daily[$d] ?? 0]; $max = max($max, $daily[$d] ?? 0); }
// دونات: حاضر / معتذر / لسه
$tot = max(1, $yes + $no + $pending);
$seg = ['yes' => round($yes / $tot * 100, 2), 'no' => round($no / $tot * 100, 2)]; $seg['wait'] = round(100 - $seg['yes'] - $seg['no'], 2);
?><!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow"><title>لوحة <?= e($inv['groom'] . ' & ' . $inv['bride']) ?></title>
<script>try{var t=localStorage.getItem('pt')||(matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light');document.documentElement.dataset.theme=t}catch(e){}</script>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Amiri:wght@700&family=Cairo:wght@400;600;700;800&display=swap">
<link rel="stylesheet" href="<?= asset('assets/panel.css') ?>"></head>
<body style="--accent:<?= e($accent) ?>;--on:<?= e($on) ?>">
<header class="top"><div class="wrap bar"><div class="brand"><span class="mark"><?= ic('mail') ?></span><div><b><?= e($inv['groom']) ?> & <?= e($inv['bride']) ?></b><small>لوحة المدعوين</small></div></div>
<nav><button class="icon-btn" id="theme" type="button" aria-label="الوضع الداكن"><span class="i-sun"><?= ic('sun') ?></span><span class="i-moon"><?= ic('moon') ?></span></button>
<a class="pill ghost" target="_blank" rel="noopener" href="<?= e($link) ?>"><?= ic('ext') ?><span class="hide-sm">شوف الدعوة</span></a>
<a class="pill ghost" href="<?= url('panel/logout.php') ?>"><?= ic('out') ?><span class="hide-sm">خروج</span></a></nav></div></header>

<main class="wrap">
<?php if ($m = flash()): ?><div class="toast"><?= e($m) ?></div><?php endif; ?>

<section class="hero rise"><div class="hero-txt"><span class="eyebrow"><?= $today ? "+$today النهاردة" : 'أهلًا بيكم' ?></span>
<h1><?= e($inv['groom']) ?> <i>&</i> <?= e($inv['bride']) ?></h1>
<p><?= $days !== null ? ($days > 0 ? 'فاضل <b class="cnt" data-n="' . $days . '">0</b> يوم على ' . ($first['title'] !== '' ? e($first['title']) : 'الميعاد') : 'النهاردة ' . ($first['title'] !== '' ? e($first['title']) . '، ' : '') . 'مبروك! 🎉') : '' ?></p></div>
<div class="share"><div class="linkbox"><?= ic('link') ?><input id="lnk" readonly dir="ltr" value="<?= e($link) ?>"></div>
<div class="share-btns"><button class="pill" id="copy" type="button"><?= ic('copy') ?>نسخ الرابط</button>
<a class="pill wa" target="_blank" rel="noopener" href="https://wa.me/?text=<?= rawurlencode($L === 'en' ? "We would be honoured by your presence 🤍\n{$inv['groom']} & {$inv['bride']}\n$link" : "يشرفنا حضورك 🤍\n{$inv['groom']} & {$inv['bride']}\n$link") ?>"><?= ic('wa') ?>واتساب</a></div></div></section>

<section class="kpis">
<div class="kpi rise" style="--i:0"><span class="k-ic"><?= ic('mail') ?></span><div><b class="cnt" data-n="<?= $nGuests ?>">0</b><small>مدعو</small></div></div>
<div class="kpi rise ok" style="--i:1"><span class="k-ic"><?= ic('check') ?></span><div><b class="cnt" data-n="<?= $heads ?>">0</b><small>فرد حاضر</small></div></div>
<div class="kpi rise no" style="--i:2"><span class="k-ic"><?= ic('x') ?></span><div><b class="cnt" data-n="<?= $no ?>">0</b><small>معتذر</small></div></div>
<div class="kpi rise wait" style="--i:3"><span class="k-ic"><?= ic('clock') ?></span><div><b class="cnt" data-n="<?= $pending ?>">0</b><small>لسه ماردّش</small></div></div>
</section>

<section class="charts">
<div class="panel rise" style="--i:4"><div class="p-head"><h3>الردود آخر 14 يوم</h3><span class="muted"><?= count($rsvps) ?> رد</span></div>
<svg class="bars" viewBox="0 0 420 150" preserveAspectRatio="none" role="img" aria-label="الردود اليومية">
<defs><linearGradient id="bg1" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="var(--accent)"/><stop offset="1" stop-color="var(--accent)" stop-opacity=".35"/></linearGradient></defs>
<?php foreach ($bars as $k => [$d, $n]): $h = $n ? max(8, round($n / $max * 104)) : 3; $x = 6 + $k * 30; ?>
<g class="bar<?= $d === date('Y-m-d') ? ' today' : '' ?>"><rect x="<?= $x ?>" y="<?= 120 - $h ?>" width="20" height="<?= $h ?>" rx="6" fill="<?= $n ? 'url(#bg1)' : 'var(--line)' ?>" style="--d:<?= $k * 40 ?>ms"><title><?= e($d) ?>: <?= $n ?></title></rect>
<text x="<?= $x + 10 ?>" y="142" text-anchor="middle"><?= (int)substr($d, 8, 2) ?></text></g>
<?php endforeach; ?></svg></div>
<div class="panel rise" style="--i:5"><div class="p-head"><h3>حالة المدعوين</h3><span class="muted">نسبة الرد <b><?= $rate ?>%</b></span></div>
<div class="donut-wrap"><svg class="donut" viewBox="0 0 36 36"><circle class="d-bg" cx="18" cy="18" r="15.9155"/>
<circle class="d-seg yes" cx="18" cy="18" r="15.9155" stroke-dasharray="<?= $seg['yes'] ?> <?= 100 - $seg['yes'] ?>" stroke-dashoffset="0"/>
<circle class="d-seg no" cx="18" cy="18" r="15.9155" stroke-dasharray="<?= $seg['no'] ?> <?= 100 - $seg['no'] ?>" stroke-dashoffset="-<?= $seg['yes'] ?>"/>
<circle class="d-seg wait" cx="18" cy="18" r="15.9155" stroke-dasharray="<?= $seg['wait'] ?> <?= 100 - $seg['wait'] ?>" stroke-dashoffset="-<?= $seg['yes'] + $seg['no'] ?>"/></svg>
<div class="d-center"><b class="cnt" data-n="<?= $rate ?>" data-s="%">0</b><small>ردّوا</small></div></div>
<ul class="legend"><li><i class="yes"></i>حاضر <b><?= $yes ?></b></li><li><i class="no"></i>معتذر <b><?= $no ?></b></li><li><i class="wait"></i>لسه <b><?= $pending ?></b></li></ul></div>
</section>

<div class="tabs" role="tablist"><button class="tab on" data-t="replies"><?= ic('check') ?>الردود <i><?= count($rsvps) ?></i></button><button class="tab" data-t="guests"><?= ic('users') ?>المدعوين <i><?= $nGuests ?></i></button></div>

<section id="replies" class="pane on">
<div class="tools"><div class="search"><?= ic('search') ?><input id="q" type="search" placeholder="دوّر باسم..."></div><div class="chips"><button class="chip on" data-f="all">الكل</button><button class="chip" data-f="1">حاضر</button><button class="chip" data-f="0">معتذر</button></div>
<a class="pill ghost" href="<?= url('panel/export.php') ?>"><?= ic('dl') ?>تحميل CSV</a></div>
<div class="list">
<?php foreach ($rsvps as $i => $r): ?>
<article class="row rise" style="--i:<?= min($i, 12) ?>" data-s="<?= (int)$r['attending'] ?>" data-n="<?= e(mb_strtolower($r['name'])) ?>">
<div class="av <?= $r['attending'] ? 'yes' : 'no' ?>"><?= e(mb_substr($r['name'], 0, 1)) ?></div>
<div class="who"><b><?= e($r['name']) ?></b><small><?= e($r['phone'] ?: '') ?> <?= $ago($r['created_at']) ?></small>
<?php if ($r['note']): ?><p class="note">“<?= e($r['note']) ?>”</p><?php endif; ?></div>
<span class="tag <?= $r['attending'] ? 'yes' : 'no' ?>"><?= $r['attending'] ? 'حاضر · ' . (int)$r['party_size'] : 'معتذر' ?></span></article>
<?php endforeach; if (!$rsvps): ?><div class="empty"><div class="e-ic"><?= ic('mail') ?></div><p>لسه محدش رد. شاركوا رابط الدعوة وهتظهر الردود هنا لحظة بلحظة.</p></div><?php endif; ?>
<div class="empty" id="none" hidden><p>مفيش نتايج.</p></div></div></section>

<section id="guests" class="pane">
<div class="panel"><div class="p-head"><h3>إضافة مدعوين</h3></div><p class="muted">اكتب ضيف في كل سطر: <code>الاسم | الموبايل | عدد الأفراد</code> (الموبايل والعدد اختياريين) — كل ضيف بياخد لينك خاص باسمه.</p>
<form method="post"><?= csrf_field() ?><input type="hidden" name="tab" value="guests"><textarea name="bulk" rows="4" placeholder="خالو محمود | 01012345678 | 3&#10;طنط سلوى | 01198765432 | 2" required></textarea><button class="pill big"><?= ic('plus') ?>أضف</button></form></div>
<div class="list">
<?php foreach ($guests as $g):
    $gl = $link . '?g=' . $g['token'];
    $txt = $g['attending'] === null
        ? tr('wa_invite', $L, $g['name'], $evTitle, $inv['groom'] . ' & ' . $inv['bride'], $gl)
        : tr('wa_thanks', $L, $g['name'], $gl);
    $wa = 'https://wa.me/' . ($g['phone'] ? normalize_phone_eg($g['phone']) : '') . '?text=' . rawurlencode($txt); ?>
<article class="row rise" style="--i:0"><div class="av"><?= e(mb_substr($g['name'], 0, 1)) ?></div>
<div class="who"><b><?= e($g['name']) ?></b><small><?= e($g['phone'] ?: 'بدون رقم') ?> · لحد <?= (int)$g['max_guests'] ?> أفراد</small></div>
<span class="tag <?= $g['attending'] === null ? 'wait' : ($g['attending'] ? 'yes' : 'no') ?>"><?= $g['attending'] === null ? 'لم يرد' : ($g['attending'] ? 'حاضر · ' . (int)$g['party_size'] : 'معتذر') ?></span>
<div class="acts"><button class="pill sm copy" type="button" data-l="<?= e($gl) ?>"><?= ic('copy') ?>نسخ</button><a class="pill sm wa" target="_blank" rel="noopener" href="<?= e($wa) ?>"><?= ic('wa') ?><?= $g['attending'] === null ? 'ابعت' : 'واتساب' ?></a>
<form method="post" onsubmit="return confirm('حذف <?= e(addslashes($g['name'])) ?>؟')"><?= csrf_field() ?><input type="hidden" name="tab" value="guests"><button class="pill sm danger" name="del" value="<?= $g['id'] ?>" aria-label="حذف"><?= ic('trash') ?></button></form></div></article>
<?php endforeach; if (!$guests): ?><div class="empty"><div class="e-ic"><?= ic('users') ?></div><p>ضيفوا المدعوين هنا عشان كل واحد ياخد لينك باسمه وتعرفوا مين ماردّش.</p></div><?php endif; ?>
</div></section>
</main>
<script src="<?= asset('assets/panel.js') ?>"></script></body></html>
