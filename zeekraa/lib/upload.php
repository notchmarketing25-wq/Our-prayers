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
function delete_upload(?string $n): void { if ($n && preg_match('/^[a-f0-9]{20}\.(jpg|png|webp)$/', $n)) @unlink(APP_ROOT . '/uploads/' . $n); }
function reorder_files(array $f): array {
    $o = []; foreach ($f['name'] ?? [] as $i => $_) $o[] = ['name'=>$f['name'][$i],'tmp_name'=>$f['tmp_name'][$i],'size'=>$f['size'][$i],'error'=>$f['error'][$i]]; return $o;
}
