<?php
require __DIR__ . '/../lib/bootstrap.php'; require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(url('admin/'));
csrf_check();
$id = (int)($_POST['id'] ?? 0);
$inv = q('SELECT id, groom, bride FROM invitations WHERE id=?', [$id])->fetch() or redirect(url('admin/'));
if (isset($_POST['revoke'])) { q('UPDATE invitations SET panel_token=NULL WHERE id=?', [$id]); flash('اتلغى رابط الدخول المباشر.'); redirect(url('admin/')); }
$k = bin2hex(random_bytes(16));
q('UPDATE invitations SET panel_token=? WHERE id=?', [hash('sha256', $k), $id]);
$_SESSION['panel_link'] = ['name' => $inv['groom'] . ' & ' . $inv['bride'], 'url' => url('panel/enter.php?k=' . $k)];
flash('اتولّد رابط الدخول المباشر — انسخه وابعته للعروسين (بيظهر مرة واحدة بس، ولو ضاع ولّد واحد جديد).');
redirect(url('admin/'));
