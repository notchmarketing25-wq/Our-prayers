<?php
/** صور غلاف جاهزة (assets/covers/<key>.jpg) يقدر الأدمن يختار منها بدل ما يرفع صورة */
const COVER_PRESETS = [
    'gold-arch'      => 'قوس ذهبي',
    'blush-floral'   => 'ورد وردي',
    'emerald-leaves' => 'أوراق زمردية',
    'navy-night'     => 'ليل وقمر',
    'lavender-dusk'  => 'غروب لافندر',
    'rings-minimal'  => 'خواتم مينيمال',
];
/** رابط صورة الغلاف: المرفوعة أولًا، وبعدها الجاهزة، وإلا فاضي */
function cover_url(array $inv): string {
    if (!empty($inv['cover_image'])) return url('uploads/' . $inv['cover_image']);
    if (!empty($inv['cover_preset']) && isset(COVER_PRESETS[$inv['cover_preset']])) return url('assets/covers/' . $inv['cover_preset'] . '.jpg');
    return '';
}
