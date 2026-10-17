<?php
require __DIR__ . '/../lib/bootstrap.php'; require __DIR__ . '/../lib/admin_layout.php'; require __DIR__ . '/../lib/upload.php'; require_admin();
require __DIR__ . '/../lib/themes.php'; require __DIR__ . '/../lib/music.php'; require __DIR__ . '/../lib/covers.php'; require __DIR__ . '/../lib/i18n.php'; require __DIR__ . '/../lib/header.php';
const RESERVED = ['admin', 'install', 'uploads', 'assets', 'lib', 'rsvp', 'ics', 'panel'];
$id = (int)($_GET['id'] ?? 0);
$inv = $id ? q('SELECT * FROM invitations WHERE id=?', [$id])->fetch() : null;
if ($id && !$inv) { http_response_code(404); exit('مش موجودة'); }
$events = $inv ? q('SELECT * FROM events WHERE invitation_id=? ORDER BY sort_order,event_at', [$id])->fetchAll() : [['title' => 'الفرح', 'event_at' => '', 'venue' => '', 'address' => '', 'map_url' => '', 'note' => '']];
$notesList = $inv ? (json_decode($inv['notes'] ?? '[]', true) ?: []) : [];
$gallery = $inv ? (json_decode($inv['gallery'] ?? '[]', true) ?: []) : [];
$err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $slug = strtolower(trim($_POST['slug'] ?? ''));
    $groom = trim($_POST['groom'] ?? ''); $bride = trim($_POST['bride'] ?? '');
    $tpl = array_key_exists($_POST['template'] ?? '', THEMES) ? $_POST['template'] : 'royal';
    $evs = [];
    foreach ($_POST['ev_title'] ?? [] as $i => $t) {
        $at = str_replace('T', ' ', trim($_POST['ev_at'][$i] ?? ''));
        if ($at === '') continue; // العنوان اختياري، الميعاد هو الإلزامي
        $evs[] = [trim($t), strlen($at) === 16 ? $at . ':00' : $at, trim($_POST['ev_venue'][$i] ?? ''), trim($_POST['ev_address'][$i] ?? ''), safe_url($_POST['ev_map'][$i] ?? ''), mb_substr(trim($_POST['ev_note'][$i] ?? ''), 0, 255)];
    }
    if (!preg_match('/^[a-z0-9-]{3,60}$/', $slug) || in_array($slug, RESERVED, true)) $err = 'الرابط لازم حروف إنجليزي صغيرة/أرقام/شرطة (3 حروف على الأقل) ومش كلمة محجوزة.';
    elseif ($groom === '' || $bride === '') $err = 'اسم العريس والعروسة مطلوبين.';
    elseif (!$evs) $err = 'ضيف مناسبة واحدة على الأقل (الميعاد مطلوب، والعنوان اختياري).';
    elseif (q('SELECT id FROM invitations WHERE slug=? AND id<>?', [$slug, $id])->fetch()) $err = 'الرابط ده مستخدم قبل كده.';
    $ownerEmail = strtolower(trim($_POST['owner_email'] ?? ''));
    if (!$err && $ownerEmail !== '' && !filter_var($ownerEmail, FILTER_VALIDATE_EMAIL)) $err = 'إيميل العميل غير صحيح.';
    if (!$err && $ownerEmail !== '' && q('SELECT id FROM invitations WHERE owner_email=? AND id<>?', [$ownerEmail, $id])->fetch()) $err = 'إيميل العميل مستخدم في دعوة تانية.';
    $ownerPw = $_POST['owner_password'] ?? '';
    if (!$err && $ownerPw !== '' && strlen($ownerPw) < 6) $err = 'كلمة سر لوحة العميل لازم 6 حروف على الأقل.';
    if (!$err && $ownerEmail !== '' && $ownerPw === '' && empty($inv['owner_password'])) $err = 'حدد كلمة سر للوحة العميل (اضغط توليد).';
    $accent = (!empty($_POST['use_accent']) && preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['accent_color'] ?? '')) ? strtolower($_POST['accent_color']) : null;
    $notes = [];
    foreach ($_POST['n_text'] ?? [] as $i => $tx) {
        $tx = mb_substr(trim($tx), 0, 400); if ($tx === '') continue;
        $notes[] = ['title' => mb_substr(trim($_POST['n_title'][$i] ?? ''), 0, 80), 'text' => $tx];
        if (count($notes) >= 12) break;
    }
    $titles = [];
    foreach (array_keys(['events' => 'المناسبات', 'notes' => 'ملاحظات وتعليمات', 'gallery' => 'لحظاتنا (المعرض)', 'rsvp' => 'تأكيد الحضور']) as $k) {
        if (!empty($_POST['h_' . $k])) $titles[$k] = '';
        elseif (($tt = mb_substr(trim($_POST['t_' . $k] ?? ''), 0, 80)) !== '') $titles[$k] = $tt;
    }
    $musicFile = $inv['music_file'] ?? null; $newMusic = null;
    if (!$err && !empty($_FILES['music']['name'])) { [$newMusic, $why] = save_audio($_FILES['music']); if ($why) $err = $why; }
    if (!$err) {
        $pdo = db(); $pdo->beginTransaction();
        try {
            $pw = $_POST['access_password'] ?? '';
            $accessHash = $inv['access_password'] ?? null;
            if (!empty($_POST['clear_password'])) $accessHash = null; elseif ($pw !== '') $accessHash = password_hash($pw, PASSWORD_DEFAULT);
            if ($newMusic) { delete_upload($musicFile); $musicFile = $newMusic; }
            elseif (!empty($_POST['remove_music'])) { delete_upload($musicFile); $musicFile = null; }
            $cover = $inv['cover_image'] ?? null; $warn = [];
            if (!empty($_POST['remove_cover'])) { delete_upload($cover); $cover = null; }
            if (!empty($_FILES['cover']['name'])) { [$n, $why] = save_image_r($_FILES['cover']); if ($n) { delete_upload($cover); $cover = $n; } else $warn[] = 'صورة الغلاف: ' . $why; }
            foreach ((array)($_POST['remove_img'] ?? []) as $r) { if (($k = array_search($r, $gallery, true)) !== false) { delete_upload($r); unset($gallery[$k]); } }
            foreach (reorder_files($_FILES['gallery'] ?? []) as $f) {
                if (!$f['name']) continue;
                if (count($gallery) >= 12) { $warn[] = $f['name'] . ': وصلت للحد الأقصى (12 صورة)'; continue; }
                [$n, $why] = save_image_r($f); if ($n) $gallery[] = $n; else $warn[] = $f['name'] . ': ' . $why;
            }
            $vals = [$slug, $tpl, $groom, $bride, trim($_POST['message'] ?? ''), safe_url($_POST['music_url'] ?? ''), $musicFile, $cover, json_encode(array_values($gallery)), $accessHash, empty($_POST['is_active']) ? 0 : 1, $accent, $ownerEmail ?: null,
                $ownerEmail === '' ? null : ($ownerPw !== '' ? password_hash($ownerPw, PASSWORD_DEFAULT) : ($inv['owner_password'] ?? null)),
                ($_POST['lang'] ?? 'ar') === 'en' ? 'en' : 'ar', null,
                mb_substr(trim(str_replace("\r\n", "\n", $_POST['intro_title'] ?? '')), 0, 160) ?: null, mb_substr(trim(str_replace("\r\n", "\n", $_POST['intro_text'] ?? '')), 0, 255) ?: null,
                mb_substr(trim(str_replace("\r\n", "\n", $_POST['photo_caption'] ?? '')), 0, 255) ?: null, in_array($_POST['caption_pos'] ?? '', ['top', 'center', 'bottom'], true) ? $_POST['caption_pos'] : 'bottom',
                $notes ? json_encode($notes, JSON_UNESCAPED_UNICODE) : null,
                null,
                $titles ? json_encode($titles, JSON_UNESCAPED_UNICODE) : null,
                in_array($_POST['caption_style'] ?? '', ['pill', 'poster', 'script', 'plain'], true) ? $_POST['caption_style'] : 'pill',
                in_array($_POST['caption_color'] ?? '', ['light', 'dark', 'accent'], true) ? $_POST['caption_color'] : 'light',
                in_array($_POST['caption_size'] ?? '', ['sm', 'md', 'lg'], true) ? $_POST['caption_size'] : 'md',
                mb_substr(trim($_POST['page_title'] ?? ''), 0, 160) ?: null, mb_substr(trim($_POST['page_desc'] ?? ''), 0, 255) ?: null,
                isset(HEADER_STYLES[$_POST['header_style'] ?? '']) ? $_POST['header_style'] : 'photo',
                isset(HEADER_DECORS[$_POST['header_decor'] ?? '']) ? $_POST['header_decor'] : 'floral',
                (!empty($_POST['use_header_color']) && preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['header_color'] ?? '')) ? strtolower($_POST['header_color']) : null,
                implode(',', array_values(array_intersect((array)($_POST['hide_part'] ?? []), array_keys(HERO_PARTS)))) ?: null,
                !empty($_POST['caption_frame']) ? 1 : 0];
            if ($inv) { q('UPDATE invitations SET slug=?,template=?,groom=?,bride=?,message=?,music_url=?,music_file=?,cover_image=?,gallery=?,access_password=?,is_active=?,accent_color=?,owner_email=?,owner_password=?,lang=?,cover_preset=?,intro_title=?,intro_text=?,photo_caption=?,caption_pos=?,notes=?,events_title=?,titles=?,caption_style=?,caption_color=?,caption_size=?,page_title=?,page_desc=?,header_style=?,header_decor=?,header_color=?,hero_hide=?,caption_frame=? WHERE id=?', [...$vals, $id]); }
            else { q('INSERT INTO invitations (slug,template,groom,bride,message,music_url,music_file,cover_image,gallery,access_password,is_active,accent_color,owner_email,owner_password,lang,cover_preset,intro_title,intro_text,photo_caption,caption_pos,notes,events_title,titles,caption_style,caption_color,caption_size,page_title,page_desc,header_style,header_decor,header_color,hero_hide,caption_frame) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', $vals); $id = (int)$pdo->lastInsertId(); }
            q('DELETE FROM events WHERE invitation_id=?', [$id]);
            foreach ($evs as $o => $ev) q('INSERT INTO events (invitation_id,title,event_at,venue,address,map_url,note,sort_order) VALUES (?,?,?,?,?,?,?,?)', [$id, $ev[0], $ev[1], $ev[2], $ev[3], $ev[4], $ev[5] ?: null, $o]);
            $pdo->commit();
            flash('تم الحفظ ✓' . ($warn ? ' — بس فيه صور ماترفعتش: ' . implode(' | ', $warn) : '')); redirect(url('admin/'));
        } catch (Throwable $t) { $pdo->rollBack(); $err = 'خطأ أثناء الحفظ: ' . $t->getMessage(); }
    }
    $inv = array_merge($inv ?? [], $_POST); $events = array_map(fn($e) => ['title' => $e[0], 'event_at' => $e[1], 'venue' => $e[2], 'address' => $e[3], 'map_url' => $e[4], 'note' => $e[5] ?? ''], $evs ?: $events);
}
$v = fn($k, $d = '') => e($inv[$k] ?? $d);
admin_header($id ? 'تعديل دعوة' : 'دعوة جديدة'); ?>
<form method="post" enctype="multipart/form-data" class="card"><?= csrf_field() ?>
<h2><?= $id ? 'تعديل الدعوة' : 'دعوة جديدة' ?></h2>
<?php if ($err): ?><div class="alert"><?= e($err) ?></div><?php endif; ?>
<div class="grid">
<label>اسم العريس <input name="groom" value="<?= $v('groom') ?>" required></label>
<label>اسم العروسة <input name="bride" value="<?= $v('bride') ?>" required></label>
<label>رابط الدعوة (إنجليزي) — <?= e(url('')) ?><b>ahmed-sara</b><input name="slug" value="<?= $v('slug') ?>" pattern="[a-z0-9-]{3,60}" required></label>
<label>لغة صفحة الدعوة <select name="lang" id="langSel"><option value="ar" <?= ($inv['lang'] ?? 'ar') === 'ar' ? 'selected' : '' ?>>العربية (RTL)</option><option value="en" <?= ($inv['lang'] ?? 'ar') === 'en' ? 'selected' : '' ?>>English (LTR)</option></select></label>
<label>التصميم <select name="template"><?php foreach (THEMES as $k => $l): ?><option value="<?= $k ?>" <?= ($inv['template'] ?? 'royal') === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
</div>
<label>كلمة الدعوة / رسالة ترحيب <textarea name="message" id="msgBox" rows="3"><?= $v('message', 'يتشرف أهل العروسين بدعوتكم لحضور حفل زفافهما') ?></textarea><small class="muted">اكتب الرسالة والأسماء والأماكن بلغة الدعوة اللي اخترتها (النصوص الثابتة زي الأزرار بتتترجم تلقائيًا).</small></label>
<h3>عناوين الأقسام وعنوان الرابط</h3>
<p class="muted" style="margin:4px 0">غيّر عنوان أي قسم أو اخفيه. لو سبت الخانة فاضية بيظهر العنوان الافتراضي حسب لغة الدعوة.</p>
<?php $tj = json_decode($inv['titles'] ?? '[]', true) ?: []; if (isset($inv['events_title']) && !array_key_exists('events', $tj)) $tj['events'] = $inv['events_title']; ?>
<div class="grid">
<?php foreach (['events' => 'المناسبات', 'notes' => 'ملاحظات وتعليمات', 'gallery' => 'لحظاتنا (المعرض)', 'rsvp' => 'تأكيد الحضور'] as $k => $l): $cur = $tj[$k] ?? null; ?>
<label>عنوان قسم "<?= e($l) ?>"<input name="t_<?= $k ?>" value="<?= $cur !== null && $cur !== '' ? e($cur) : '' ?>" maxlength="80" placeholder="الافتراضي">
<span style="font-weight:400;font-size:.85rem"><input type="checkbox" name="h_<?= $k ?>" value="1" style="display:inline;width:auto" <?= $cur === '' ? 'checked' : '' ?>> إخفاء العنوان</span></label>
<?php endforeach; ?>
</div>
<div class="grid">
<label>عنوان الرابط (بيظهر في تاب المتصفح وفي معاينة واتساب — فاضي = الأسماء)<input name="page_title" value="<?= $v('page_title') ?>" maxlength="160" placeholder="مثال: دعوة زفاف محمد ومريم 💍"></label>
<label>وصف الرابط في معاينة واتساب (فاضي = رسالة الترحيب)<input name="page_desc" value="<?= $v('page_desc') ?>" maxlength="255" placeholder="مثال: يسعدنا حضوركم"></label>
</div>
<h3>كتابة على الشاشة الافتتاحية (الظرف) وعلى الصورة</h3>
<p class="muted" style="margin:4px 0">اضغط <b>Enter</b> عشان تنزل لسطر جديد — كل سطر بيظهر في الدعوة تحت اللي قبله بالظبط.</p>
<div class="grid">
<label>جملة بدل الأسماء على الظرف (اختياري — لو فاضي بتظهر الأسماء)<textarea name="intro_title" rows="2" maxlength="160" placeholder="مثال: دعوة زفاف محمد ومريم"><?= $v('intro_title') ?></textarea></label>
<label>سطر تحت الاسم/الجملة على الظرف (اختياري)<textarea name="intro_text" rows="2" maxlength="255" placeholder="مثال: 27 مارس 2027 — بنتشرف بحضوركم"><?= $v('intro_text') ?></textarea></label>
<label>جملة على صورة الغلاف (اختياري)<textarea name="photo_caption" rows="3" maxlength="255" placeholder="مثال: وبدأت حكايتنا..."><?= $v('photo_caption') ?></textarea></label>
<label>مكان الجملة على الصورة<select name="caption_pos"><?php foreach (['bottom' => 'تحت (أنسب — الصورة تفضل ظاهرة)', 'center' => 'في النص', 'top' => 'فوق'] as $k => $l): ?><option value="<?= $k ?>" <?= ($inv['caption_pos'] ?? 'bottom') === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
<label>ستايل الجملة على الصورة<select name="caption_style"><?php foreach (['pill' => 'كبسولة شفافة (هادي)', 'poster' => 'بوستر — خط عريض كبير', 'script' => 'خط أنيق مائل', 'plain' => 'نص بسيط بدون خلفية'] as $k => $l): ?><option value="<?= $k ?>" <?= ($inv['caption_style'] ?? 'pill') === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
<label>لون الجملة<select name="caption_color"><?php foreach (['light' => 'تلقائي (يناسب التصميم)', 'dark' => 'أسود', 'accent' => 'لون الدعوة'] as $k => $l): ?><option value="<?= $k ?>" <?= ($inv['caption_color'] ?? 'light') === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
<label>&nbsp;<span><input type="checkbox" name="caption_frame" value="1" style="display:inline;width:auto" <?= !isset($inv['caption_frame']) || (int)$inv['caption_frame'] === 1 ? 'checked' : '' ?>> إطار بنفس لون الهيدر حوالين الجملة (مع القوس / البيضاوي / الإطار / المونوجرام — الجملة بتنزل تحت الأيقونة أو الصورة)</span></label>
<label>حجم الجملة<select name="caption_size"><?php foreach (['sm' => 'صغير', 'md' => 'متوسط', 'lg' => 'كبير'] as $k => $l): ?><option value="<?= $k ?>" <?= ($inv['caption_size'] ?? 'md') === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
</div>
<h3>المناسبات (كتب كتاب / حنة / فرح…)</h3>

<div id="events"><?php foreach ($events as $ev): ?>
<div class="event-row grid">
<label>العنوان (اختياري) <input name="ev_title[]" value="<?= e($ev['title']) ?>" placeholder="ممكن تسيبه فاضي"></label>
<label>الميعاد <input type="datetime-local" name="ev_at[]" value="<?= e(str_replace(' ', 'T', substr((string)$ev['event_at'], 0, 16))) ?>"></label>
<label>المكان <input name="ev_venue[]" value="<?= e($ev['venue']) ?>"></label>
<label>العنوان التفصيلي <input name="ev_address[]" value="<?= e($ev['address']) ?>"></label>
<label>رابط Google Maps <input name="ev_map[]" value="<?= e($ev['map_url']) ?>" placeholder="https://maps.app.goo.gl/..."></label>
<label>ملاحظة على المناسبة (اختياري) <input name="ev_note[]" value="<?= e($ev['note'] ?? '') ?>" maxlength="255" placeholder="مثال: كود اللبس رسمي — الحضور 7 مساءً"></label>
<label>&nbsp;<button type="button" class="btn sm danger" onclick="this.closest('.event-row').remove()">حذف المناسبة</button></label></div>
<?php endforeach; ?></div>
<button type="button" class="btn sec sm" id="addEv">+ مناسبة</button>
<h3>ملاحظات وتعليمات للضيوف</h3>
<p class="muted" style="margin:4px 0">بتظهر في قسم "ملاحظات وتعليمات" في الدعوة. اضغط على اقتراح عشان يتضاف جاهز وعدّله زي ما تحب:</p>
<div class="qchips" id="qchips"></div>
<div id="notes"><?php foreach ($notesList as $n): ?>
<div class="event-row grid note-row"><label>العنوان <input name="n_title[]" value="<?= e($n['title'] ?? '') ?>" maxlength="80"></label>
<label style="grid-column:1/-1">النص <textarea name="n_text[]" rows="2" maxlength="400"><?= e($n['text'] ?? '') ?></textarea></label>
<label>&nbsp;<button type="button" class="btn sm danger" onclick="this.closest('.note-row').remove()">حذف الملاحظة</button></label></div>
<?php endforeach; ?></div>
<button type="button" class="btn sec sm" id="addNote">+ ملاحظة فاضية</button>
<h3>تصميم الهيدر (الجزء اللي فوق النص)</h3>
<p class="muted" style="margin:4px 0">الهيدر بياخد ألوان التصميم اللي اخترته تلقائيًا، وتقدر تختار لون خاص بيه. الصورة بتتحط جواه بالشكل اللي تختاره، ولو مفيش صورة بيظهر مونوجرام بأول حرف من الاسمين.</p>
<div class="grid">
<label>شكل الهيدر / الصورة<select name="header_style"><?php foreach (HEADER_STYLES as $k => $l): ?><option value="<?= $k ?>" <?= ($inv['header_style'] ?? 'photo') === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
<label>زخرفة الخلفية<select name="header_decor"><?php foreach (HEADER_DECORS as $k => $l): ?><option value="<?= $k ?>" <?= ($inv['header_decor'] ?? 'floral') === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
<label>لون الهيدر الخاص (اختياري)<input type="color" name="header_color" value="<?= e($inv['header_color'] ?? '#b08a3e') ?>" style="height:44px;padding:3px"></label>
<label>&nbsp;<span><input type="checkbox" name="use_header_color" value="1" style="display:inline;width:auto" <?= !empty($inv['header_color']) ? 'checked' : '' ?>> استخدم اللون الخاص (وإلا لون التصميم)</span></label>
</div>
<p style="margin:10px 0 2px;font-weight:600;font-size:14px">إخفاء أجزاء من النص اللي تحت الهيدر:</p>
<div class="qchips"><?php $hid = array_filter(explode(',', (string)($inv['hero_hide'] ?? ''))); foreach (HERO_PARTS as $k => $l): ?><label class="qchip" style="font-weight:400"><input type="checkbox" name="hide_part[]" value="<?= $k ?>" style="display:inline;width:auto" <?= in_array($k, $hid, true) ? 'checked' : '' ?>> <?= e($l) ?></label><?php endforeach; ?></div>
<h3>الصور والموسيقى</h3>

<div class="grid">
<label>صورة الغلاف / الصورة الرئيسية <input type="file" name="cover" accept="image/jpeg,image/png,image/webp"></label>
<label>رابط موسيقى (YouTube أو أي رابط mp3 مباشر) <input name="music_url" value="<?= $v('music_url') ?>" placeholder="https://youtu.be/... أو https://.../song.mp3"></label></div>
<div class="card" style="background:#faf6ee;box-shadow:none;border:1px dashed #cbbf9f;margin:8px 0">
<label>أو ارفع الأغنية كملف (mp3 / m4a / aac / ogg / wav — لحد 20 ميجا، وبتغلب على الرابط)
<input type="file" name="music" accept="audio/*,.mp3,.m4a,.aac,.ogg,.wav"></label>
<?php if (!empty($inv['music_file'])): ?><audio controls preload="none" src="<?= e(url('uploads/' . $inv['music_file'])) ?>" style="width:100%;margin-top:8px"></audio>
<label style="font-weight:400"><input type="checkbox" name="remove_music" value="1" style="display:inline;width:auto"> حذف الأغنية المرفوعة</label><?php endif; ?>
<small class="muted">لو الأغنية كبيرة وماترفعتش: زوّد <code>upload_max_filesize</code> و<code>post_max_size</code> من إعدادات PHP في الاستضافة، أو استخدم رابط.</small></div>
<?php if (!empty($inv['cover_image'])): ?><div class="thumbs"><label style="display:inline-block"><img src="<?= url('uploads/' . $inv['cover_image']) ?>" alt=""><br><input type="checkbox" name="remove_cover" value="1" style="display:inline;width:auto"> حذف صورة الغلاف</label></div><?php endif; ?>
<label>معرض الصور (لحد 12) <input type="file" name="gallery[]" multiple accept="image/jpeg,image/png,image/webp"></label>
<div class="thumbs"><?php foreach ($gallery as $g): ?><label style="display:inline-block"><img src="<?= url('uploads/' . $g) ?>" alt=""><br><input type="checkbox" name="remove_img[]" value="<?= e($g) ?>" style="display:inline;width:auto"> حذف</label><?php endforeach; ?></div>
<h3>لون الدعوة</h3>
<div class="grid"><label>اللون المخصص (بيغلب لون التصميم)
<input type="color" name="accent_color" value="<?= e($inv['accent_color'] ?? '#b08a3e') ?>" style="height:44px;padding:3px"></label>
<label>&nbsp;<span><input type="checkbox" name="use_accent" value="1" style="display:inline;width:auto" <?= !empty($inv['accent_color']) ? 'checked' : '' ?>> استخدم اللون المخصص</span></label></div>
<h3>لوحة العميل (العروسين)</h3>
<p class="muted">العروسين يدخلوا من <b><?= e(url('panel/')) ?></b> ويشوفوا اللي سجّل حضوره ويديروا ضيوفهم.</p>
<div class="grid"><label>إيميل العميل <input type="email" name="owner_email" value="<?= $v('owner_email') ?>" autocomplete="off"></label>
<label>كلمة سر اللوحة <input name="owner_password" id="opw" autocomplete="off" placeholder="<?= !empty($inv['owner_password']) ? 'محفوظة — اكتب جديدة للتغيير' : '6 حروف على الأقل' ?>">
<button type="button" class="btn sm sec" style="margin-top:6px" onclick="document.getElementById('opw').value=Math.random().toString(36).slice(2,6)+Math.random().toString(36).slice(2,6)">توليد كلمة سر</button></label></div>
<h3>الخصوصية</h3>
<div class="grid">
<label>كلمة سر للدعوة (اختياري) <input name="access_password" type="password" autocomplete="new-password" placeholder="<?= !empty($inv['access_password']) ? 'محفوظة — اكتب جديدة للتغيير' : '' ?>"></label>
<label><?php if (!empty($inv['access_password'])): ?><input type="checkbox" name="clear_password" value="1" style="display:inline;width:auto"> إلغاء كلمة السر<br><?php endif; ?>
<input type="checkbox" name="is_active" value="1" style="display:inline;width:auto" <?= ($inv['is_active'] ?? 1) ? 'checked' : '' ?>> الدعوة مفعّلة</label></div>
<button class="btn">حفظ</button> <a class="btn sec" href="<?= url('admin/') ?>">رجوع</a>
</form>
<template id="evTpl"><div class="event-row grid">
<label>العنوان (اختياري) <input name="ev_title[]" placeholder="ممكن تسيبه فاضي"></label><label>الميعاد <input type="datetime-local" name="ev_at[]"></label>
<label>المكان <input name="ev_venue[]"></label><label>العنوان التفصيلي <input name="ev_address[]"></label>
<label>رابط Google Maps <input name="ev_map[]"></label>
<label>ملاحظة على المناسبة (اختياري) <input name="ev_note[]" maxlength="255"></label>
<label>&nbsp;<button type="button" class="btn sm danger" onclick="this.closest('.event-row').remove()">حذف المناسبة</button></label></div></template>
<script>(function(){var s=document.getElementById('langSel'),m=document.getElementById('msgBox'),D={ar:'يتشرف أهل العروسين بدعوتكم لحضور حفل زفافهما',en:'Together with their families, they joyfully invite you to celebrate their wedding.'};
s.onchange=function(){var o=s.value==='en'?'ar':'en';if(!m.value.trim()||m.value.trim()===D[o])m.value=D[s.value]}})();</script>
<script>(function(){
var P={ar:[['👗','كود اللبس','الكود رسمي، نتشرف بأناقتكم.'],['🚗','ركن السيارات','يتوفر ركن للسيارات أمام القاعة.'],['🕖','ميعاد الحضور','برجاء الحضور قبل الميعاد بنصف ساعة.'],['🎁','الهدايا','وجودكم هو أجمل هدية.'],['👶','الأطفال','الحفل للكبار فقط — شاكرين تفهمكم.'],['📵','التصوير','نرجو الاستمتاع باللحظة وإيقاف التصوير أثناء المراسم.']],
en:[['👗','Dress code','Formal attire. We look forward to seeing you dressed to impress.'],['🚗','Parking','Free parking is available in front of the venue.'],['🕖','Arrival time','Kindly arrive 30 minutes before the ceremony.'],['🎁','Gifts','Your presence is the greatest gift of all.'],['👶','Children','This is an adults-only celebration. Thank you for understanding.'],['📵','Photography','We kindly ask you to enjoy the moment and keep phones away during the ceremony.']]};
var box=document.getElementById('notes'),chips=document.getElementById('qchips'),sel=document.getElementById('langSel');
function esc(s){return String(s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;')}
function add(i,t,x){var d=document.createElement('div');d.className='event-row grid note-row';
d.innerHTML='<label>العنوان <input name="n_title[]" value="'+esc(t)+'" maxlength="80"></label><label style="grid-column:1/-1">النص <textarea name="n_text[]" rows="2" maxlength="400">'+esc(x)+'</textarea></label><label>&nbsp;<button type="button" class="btn sm danger" onclick="this.closest(\'.note-row\').remove()">حذف الملاحظة</button></label>';box.appendChild(d)}
function draw(){chips.innerHTML='';P[sel.value].forEach(function(p){var b=document.createElement('button');b.type='button';b.className='qchip';b.textContent=p[1];b.onclick=function(){add(p[0],p[1],p[2])};chips.appendChild(b)})}
document.getElementById('addNote').onclick=function(){add('✨','','')};sel.addEventListener('change',draw);draw()})();</script>
<script>document.getElementById('addEv').onclick=()=>document.getElementById('events').append(document.getElementById('evTpl').content.cloneNode(true));</script>
<?php admin_footer();
