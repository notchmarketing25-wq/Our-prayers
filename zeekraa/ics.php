<?php
require __DIR__ . '/lib/bootstrap.php';
$e = q('SELECT e.*, i.groom, i.bride, i.slug FROM events e JOIN invitations i ON i.id=e.invitation_id WHERE e.id=? AND i.is_active=1', [(int)($_GET['e'] ?? 0)])->fetch() or exit('مش موجود');
$esc = fn($s) => str_replace(["\\", ";", ",", "\n"], ["\\\\", "\;", "\\,", "\\n"], (string)$s);
$start = new DateTime($e['event_at'], new DateTimeZone('Africa/Cairo')); $end = (clone $start)->modify('+4 hours');
$utc = new DateTimeZone('UTC');
header('Content-Type: text/calendar; charset=utf-8'); header('Content-Disposition: attachment; filename="event.ics"');
echo "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Invitations//AR\r\nBEGIN:VEVENT\r\nUID:ev{$e['id']}@" . ($_SERVER['HTTP_HOST'] ?? 'local') . "\r\n";
echo 'DTSTAMP:' . gmdate('Ymd\THis\Z') . "\r\nDTSTART:" . $start->setTimezone($utc)->format('Ymd\THis\Z') . "\r\nDTEND:" . $end->setTimezone($utc)->format('Ymd\THis\Z') . "\r\n";
echo 'SUMMARY:' . $esc((trim($e['title']) !== '' ? $e['title'] . ' - ' : '') . $e['groom'] . ' & ' . $e['bride']) . "\r\nLOCATION:" . $esc(trim($e['venue'] . ' ' . $e['address'])) . "\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
