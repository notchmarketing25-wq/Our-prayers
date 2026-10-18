<?php
/** يطلع id فيديو يوتيوب من أي صيغة رابط (watch / youtu.be / shorts / embed / music.youtube) أو null */
function youtube_id(string $u): ?string {
    if (preg_match('~(?:youtube\.com/(?:watch\?(?:.*&)?v=|shorts/|embed/|live/)|music\.youtube\.com/watch\?(?:.*&)?v=|youtu\.be/)([A-Za-z0-9_-]{11})~i', $u, $m)) return $m[1];
    return null;
}
/** مصدر الموسيقى للدعوة: ملف مرفوع ← يوتيوب ← أي رابط صوت */
function music_source(array $inv): ?array {
    if (!empty($inv['music_file'])) return ['type' => 'audio', 'src' => url('uploads/' . $inv['music_file'])];
    $u = safe_url($inv['music_url'] ?? '');
    if ($u === '') return null;
    if ($id = youtube_id($u)) return ['type' => 'youtube', 'id' => $id];
    return ['type' => 'audio', 'src' => $u];
}
