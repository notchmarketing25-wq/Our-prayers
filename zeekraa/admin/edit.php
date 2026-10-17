<?php
require __DIR__ . '/../lib/bootstrap.php'; require __DIR__ . '/../lib/admin_layout.php'; require __DIR__ . '/../lib/upload.php'; require_admin();
require __DIR__ . '/../lib/themes.php'; require __DIR__ . '/../lib/music.php';
const RESERVED = ['admin', 'install', 'uploads', 'assets', 'lib', 'rsvp', 'ics', 'panel'];
$id = (int)($_GET['id'] ?? 0);
$inv = $id ? q('SELECT * FROM invitations WHERE id=?', [$id])->fetch() : null;
if ($id && !$inv) { http_response_code(404); exit('مش موجودة'); }
$events = $inv ? q('SELECT * FROM events WHERE invitation_id=? ORDER BY sort_order,event_at', [$id])->fetchAll() : [['title' => 'الفرح', 'event_at' => '', 'venue' => '', 'address' => '', 'map_url' => '']];
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
        if (trim($t) === '' || $at === '') continue;
        $evs[] = [trim($t), strlen($at) === 16 ? $at . ':00' : $at, trim($_POST['ev_venue'][$i] ?? ''), trim($_POST['ev_address'][$i] ?? ''), safe_url($_POST['ev_map'][$i] ?? '')];
    }
    if (!preg_match('/^[a-z0-9-]{3,60}$/', $slug) || in_array($slug, RESERVED, true)) $err = 'الرابط لازم حروف إنجليزي صغيرة/أرقام/شرطة (3 حروف على الأقل) ومش كلمة محجوزة.';
    elseif ($groom === '' || $bride === '') $err = 'اسم العريس والعروسة مطلوبين.';
    elseif (!$evs) $err = 'ضيف مناسبة واحدة على الأقل (عنوان + ميعاد).';
    elseif (q('SELECT id FROM invitations WHERE slug=? AND id<>?', [$slug, $id])->fetch()) $err = 'الرابط ده مستخدم قبل كده.';
    $ownerEmail = strtolower(trim($_POST['owner_email'] ?? ''));
    if (!$err && $ownerEmail !== '' && !filter_var($ownerEmail, FILTER_VALIDATE_EMAIL)) $err = 'إيميل العميل غير صحيح.';
    if (!$err && $ownerEmail !== '' && q('SELECT id FROM invitations WHERE owner_email=? AND id<>?', [$ownerEmail, $id])->fetch()) $err = 'إيميل العميل مستخدم في دعوة تانية.';
    $ownerPw = $_POST['owner_password'] ?? '';
    if (!$err && $ownerPw !== '' && strlen($ownerPw) < 6) $err = 'كلمة سر لوحة العميل لازم 6 حروف على الأقل.';
    if (!$err && $ownerEmail !== '' && $ownerPw === '' && empty($inv['owner_password'])) $err = 'حدد كلمة سر للوحة العميل (اضغط توليد).';
    $accent = (!empty($_POST['use_accent']) && preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['accent_color'] ?? '')) ? strtolower($_POST['accent_color']) : null;
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
            $cover = $inv['cover_image'] ?? null;
            if (!empty($_FILES['cover']['name']) && ($n = save_image($_FILES['cover']))) { delete_upload($cover); $cover = $n; }
            foreach ((array)($_POST['remove_img'] ?? []) as $r) { if (($k = array_search($r, $gallery, true)) !== false) { delete_upload($r); unset($gallery[$k]); } }
            foreach (reorder_files($_FILES['gallery'] ?? []) as $f) if ($f['name'] && count($gallery) < 12 && ($n = save_image($f))) $gallery[] = $n;
            $vals = [$slug, $tpl, $groom, $bride, trim($_POST['message'] ?? ''), safe_url($_POST['music_url'] ?? ''), $musicFile, $cover, json_encode(array_values($gallery)), $accessHash, empty($_POST['is_active']) ? 0 : 1, $accent, $ownerEmail ?: null,
                $ownerEmail === '' ? null : ($ownerPw !== '' ? password_hash($ownerPw, PASSWORD_DEFAULT) : ($inv['owner_password'] ?? null))];
            if ($inv) { q('UPDATE invitations SET slug=?,template=?,groom=?,bride=?,message=?,music_url=?,music_file=?,cover_image=?,gallery=?,access_password=?,is_active=?,accent_color=?,owner_email=?,owner_password=? WHERE id=?', [...$vals, $id]); }
            else { q('INSERT INTO invitations (slug,template,groom,bride,message,music_url,music_file,cover_image,gallery,access_password,is_active,accent_color,owner_email,owner_password) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)', $vals); $id = (int)$pdo->lastInsertId(); }
            q('DELETE FROM events WHERE invitation_id=?', [$id]);
            foreach ($evs as $o => $ev) q('INSERT INTO events (invitation_id,title,event_at,venue,address,map_url,sort_order) VALUES (?,?,?,?,?,?,?)', [$id, $ev[0], $ev[1], $ev[2], $ev[3], $ev[4], $o]);
            $pdo->commit();
            flash('تم الحفظ ✓'); redirect(url('admin/'));
        } catch (Throwable $t) { $pdo->rollBack(); $err = 'خطأ أثناء الحفظ: ' . $t->getMessage(); }
    }
    $inv = array_merge($inv ?? [], $_POST); $events = array_map(fn($e) => ['title' => $e[0], 'event_at' => $e[1], 'venue' => $e[2], 'address' => $e[3], 'map_url' => $e[4]], $evs ?: $events);
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
<label>التصميم <select name="template"><?php foreach (THEMES as $k => $l): ?><option value="<?= $k ?>" <?= ($inv['template'] ?? 'royal') === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
</div>
<label>كلمة الدعوة / رسالة ترحيب <textarea name="message" rows="3"><?= $v('message', 'يتشرف أهل العروسين بدعوتكم لحضور حفل زفافهما') ?></textarea></label>
<h3>المناسبات (كتب كتاب / حنة / فرح…)</h3>
<div id="events"><?php foreach ($events as $ev): ?>
<div class="event-row grid">
<label>العنوان <input name="ev_title[]" value="<?= e($ev['title']) ?>"></label>
<label>الميعاد <input type="datetime-local" name="ev_at[]" value="<?= e(str_replace(' ', 'T', substr((string)$ev['event_at'], 0, 16))) ?>"></label>
<label>المكان <input name="ev_venue[]" value="<?= e($ev['venue']) ?>"></label>
<label>العنوان التفصيلي <input name="ev_address[]" value="<?= e($ev['address']) ?>"></label>
<label>رابط Google Maps <input name="ev_map[]" value="<?= e($ev['map_url']) ?>" placeholder="https://maps.app.goo.gl/..."></label>
<label>&nbsp;<button type="button" class="btn sm danger" onclick="this.closest('.event-row').remove()">حذف المناسبة</button></label></div>
<?php endforeach; ?></div>
<button type="button" class="btn sec sm" id="addEv">+ مناسبة</button>
<h3>الصور والموسيقى</h3>
<div class="grid">
<label>صورة الغلاف <input type="file" name="cover" accept="image/jpeg,image/png,image/webp"></label>
<label>رابط موسيقى (YouTube أو أي رابط mp3 مباشر) <input name="music_url" value="<?= $v('music_url') ?>" placeholder="https://youtu.be/... أو https://.../song.mp3"></label></div>
<div class="card" style="background:#faf6ee;box-shadow:none;border:1px dashed #cbbf9f;margin:8px 0">
<label>أو ارفع الأغنية كملف (mp3 / m4a / aac / ogg / wav — لحد 20 ميجا، وبتغلب على الرابط)
<input type="file" name="music" accept="audio/*,.mp3,.m4a,.aac,.ogg,.wav"></label>
<?php if (!empty($inv['music_file'])): ?><audio controls preload="none" src="<?= e(url('uploads/' . $inv['music_file'])) ?>" style="width:100%;margin-top:8px"></audio>
<label style="font-weight:400"><input type="checkbox" name="remove_music" value="1" style="display:inline;width:auto"> حذف الأغنية المرفوعة</label><?php endif; ?>
<small class="muted">لو الأغنية كبيرة وماترفعتش: زوّد <code>upload_max_filesize</code> و<code>post_max_size</code> من إعدادات PHP في الاستضافة، أو استخدم رابط.</small></div>
<?php if (!empty($inv['cover_image'])): ?><div class="thumbs"><img src="<?= url('uploads/' . $inv['cover_image']) ?>" alt=""></div><?php endif; ?>
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
<label>العنوان <input name="ev_title[]"></label><label>الميعاد <input type="datetime-local" name="ev_at[]"></label>
<label>المكان <input name="ev_venue[]"></label><label>العنوان التفصيلي <input name="ev_address[]"></label>
<label>رابط Google Maps <input name="ev_map[]"></label>
<label>&nbsp;<button type="button" class="btn sm danger" onclick="this.closest('.event-row').remove()">حذف المناسبة</button></label></div></template>
<script>document.getElementById('addEv').onclick=()=>document.getElementById('events').append(document.getElementById('evTpl').content.cloneNode(true));</script>
<?php admin_footer();
