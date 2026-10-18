<?php
// دخول مباشر للعروسين برابط خاص (بدون إيميل وكلمة سر). التوكن متخزن مشفّر (sha256) ويتجدد من الأدمن.
require __DIR__ . '/../lib/bootstrap.php'; require __DIR__ . '/../lib/migrate.php'; ensure_schema();
$_SESSION['etries'] = ($_SESSION['etries'] ?? 0) + 1;
$k = (string)($_GET['k'] ?? '');
if ($_SESSION['etries'] <= 20 && preg_match('/^[a-f0-9]{32}$/', $k)) {
    $r = q('SELECT id FROM invitations WHERE panel_token=? AND is_active=1', [hash('sha256', $k)])->fetch();
    if ($r) { session_regenerate_id(true); $_SESSION['client'] = (int)$r['id']; $_SESSION['etries'] = 0; redirect(url('panel/')); }
}
http_response_code(403);
?><!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><body style="font-family:Cairo,Tahoma,sans-serif;text-align:center;padding:4rem 1rem;background:#faf7f2;color:#333" dir="rtl">
<h2>الرابط غير صالح أو انتهت صلاحيته</h2><p>اطلب من المسؤول رابط دخول جديد، أو <a href="<?= e(url('panel/login.php')) ?>">سجّل دخول بالإيميل وكلمة السر</a>.</p>
