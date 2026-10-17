<?php
/** الثيمات المتاحة للدعوة (ألوانها في assets/invite.css تحت .t-<key>) */
const THEMES = [
    'royal'    => 'رويال — كريمي وذهبي',
    'emerald'  => 'زمردي — أخضر وذهبي',
    'minimal'  => 'مينيمال — أبيض وأسود',
    'rose'     => 'روز — وردي هادي',
    'navy'     => 'نيفي — كحلي وذهبي',
    'lavender' => 'لافندر — بنفسجي ناعم',
    'sage'     => 'سيج — أخضر زيتي',
    'burgundy' => 'بورجندي — عنابي فخم',
];
/** يرجّع لون نص مناسب (أبيض/داكن) فوق خلفية بلون hex */
function contrast_on(string $hex): string {
    $h = ltrim($hex, '#'); [$r, $g, $b] = array_map('hexdec', str_split($h, 2));
    return (0.299 * $r + 0.587 * $g + 0.114 * $b) > 160 ? '#1c1a17' : '#ffffff';
}
/** اللون الأساسي لكل ثيم (بتستخدمه لوحة العميل عشان تلبس نفس ألوان الدعوة) */
const THEME_ACCENTS = ['royal' => '#b08a3e', 'emerald' => '#2f6b4f', 'minimal' => '#222222', 'rose' => '#c27a84',
    'navy' => '#2b4a73', 'lavender' => '#8b6fc0', 'sage' => '#6b8a5a', 'burgundy' => '#8c2f3f'];
