<?php
/** يحفظ صورة مرفوعة بعد التحقق من نوعها الحقيقي ويرجّع اسم الملف أو null */
function save_image(array $file): ?string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || $file['size'] > 6 * 1024 * 1024) return null;
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
    if (!$ext || !getimagesize($file['tmp_name'])) return null;
    $name = bin2hex(random_bytes(10)) . '.' . $ext;
    return move_uploaded_file($file['tmp_name'], APP_ROOT . '/uploads/' . $name) ? $name : null;
}
function delete_upload(?string $n): void { if ($n && preg_match('/^[a-f0-9]{20}\.(jpg|png|webp|mp3|m4a|ogg|wav|aac)$/', $n)) @unlink(APP_ROOT . '/uploads/' . $n); }
function reorder_files(array $f): array {
    $o = []; foreach ($f['name'] ?? [] as $i => $_) $o[] = ['name'=>$f['name'][$i],'tmp_name'=>$f['tmp_name'][$i],'size'=>$f['size'][$i],'error'=>$f['error'][$i]]; return $o;
}

/** يحفظ أغنية مرفوعة. بيرجّع [اسم الملف, null] أو [null, سبب الفشل بالعربي] */
function save_audio(array $f): array {
    $err = $f['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) return [null, 'حجم الأغنية أكبر من المسموح في إعدادات الاستضافة (upload_max_filesize = ' . ini_get('upload_max_filesize') . '). صغّر الملف أو استخدم رابط.'];
    if ($err !== UPLOAD_ERR_OK) return [null, 'فشل رفع الأغنية (كود ' . $err . ').'];
    if ($f['size'] > 20 * 1024 * 1024) return [null, 'الأغنية أكبر من 20 ميجا.'];
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $okMime = ['audio/mpeg', 'audio/mp3', 'audio/mp4', 'audio/x-m4a', 'audio/m4a', 'audio/aac', 'audio/x-aac', 'audio/ogg', 'application/ogg', 'audio/wav', 'audio/x-wav', 'audio/wave', 'video/mp4', 'application/octet-stream'];
    if (!in_array($ext, ['mp3', 'm4a', 'aac', 'ogg', 'wav'], true) || !in_array($mime, $okMime, true))
        return [null, 'صيغة الأغنية غير مدعومة. المسموح: mp3 / m4a / aac / ogg / wav.'];
    // نتأكد إنه مش سكريبت متخفي: لازم يكون ملف صوت فعلًا (ID3/MPEG/ftyp/Ogg/RIFF/ADTS)
    $h = (string)file_get_contents($f['tmp_name'], false, null, 0, 12);
    $isAudio = str_starts_with($h, 'ID3') || str_starts_with($h, 'OggS') || str_starts_with($h, 'RIFF') || substr($h, 4, 4) === 'ftyp'
        || (strlen($h) > 1 && (ord($h[0]) === 0xFF && (ord($h[1]) & 0xE0) === 0xE0));
    if (!$isAudio) return [null, 'الملف مش ملف صوت صالح.'];
    $name = bin2hex(random_bytes(10)) . '.' . $ext;
    return move_uploaded_file($f['tmp_name'], APP_ROOT . '/uploads/' . $name) ? [$name, null] : [null, 'تعذّر حفظ الأغنية على السيرفر.'];
}
