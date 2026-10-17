<?php
/** أنماط الهيدر (الجزء اللي فوق النص): شكل الصورة + زخرفة الخلفية. كل الألوان بتتبع لون السمة (--h-base). */
const HEADER_STYLES = [
    'photo' => 'صورة كاملة (الصورة تملا الهيدر)',
    'arch'  => 'قوس — الصورة جوه قوس',
    'oval'  => 'بيضاوي — إطار بيضاوي',
    'frame' => 'إطار ذهبي مزدوج',
    'none'  => 'بدون هيدر (النص بس)',
];
const HEADER_DECORS = [
    'floral' => 'ورد وأوراق',
    'rays'   => 'أشعة وهالات',
    'geo'    => 'زخرفة إسلامية',
    'bokeh'  => 'إضاءة ناعمة',
    'plain'  => 'ساده (تدرج لوني فقط)',
];
const HEADER_NONE = 'none';
const HERO_PARTS = ['kicker' => 'السطر التمهيدي (نتشرف بدعوتكم)', 'names' => 'الأسماء والزخرفة', 'message' => 'رسالة الترحيب', 'date' => 'التاريخ', 'button' => 'زر التفاصيل'];

function hero_hidden(array $inv): array { return array_filter(explode(',', (string)($inv['hero_hide'] ?? ''))); }

/** SVG الزخرفة (بيملا الهيدر، لونه currentColor = لون الهيدر) */
function header_decor(string $d): string {
    $o = '<svg class="hd-decor" viewBox="0 0 400 640" preserveAspectRatio="xMidYMid slice" aria-hidden="true" fill="currentColor">';
    $flower = function (float $x, float $y, float $r, int $n, float $rot, float $op) {
        $s = "<g transform=\"translate($x $y) rotate($rot)\">";
        for ($i = 0; $i < $n; $i++) $s .= '<ellipse cx="0" cy="' . round(-$r * .52, 1) . '" rx="' . round($r * .26, 1) . '" ry="' . round($r * .52, 1) . '" opacity="' . $op . '" transform="rotate(' . round($i * 360 / $n, 1) . ')"/>';
        for ($i = 0; $i < $n; $i++) $s .= '<ellipse cx="0" cy="' . round(-$r * .3, 1) . '" rx="' . round($r * .13, 1) . '" ry="' . round($r * .3, 1) . '" fill="#fff" opacity=".35" transform="rotate(' . round($i * 360 / $n + 180 / $n, 1) . ')"/>';
        return $s . '<circle r="' . round($r * .13, 1) . '" fill="#fff" opacity=".7"/></g>';
    };
    $leaf = fn(float $x, float $y, float $l, float $a, float $op) => "<path transform=\"translate($x $y) rotate($a)\" opacity=\"$op\" d=\"M0 0C" . round($l * .3) . ' ' . round(-$l * .3) . ' ' . round($l * .3) . ' ' . round(-$l * .72) . ' 0 ' . round(-$l) . 'C' . round(-$l * .3) . ' ' . round(-$l * .72) . ' ' . round(-$l * .3) . ' ' . round(-$l * .3) . ' 0 0Z"/>';
    if ($d === 'floral') {
        // رشّة ورد من الركن الأعلى والأسفل (متناظرة بالعكس) بدرجات شفافية مختلفة
        $spray = function (bool $flip) use ($flower, $leaf) {
            $g = $flip ? '<g transform="translate(400 640) rotate(180)">' : '<g>';
            foreach ([[20, 150, 120, 38, .16], [60, 95, 130, 62, .22], [-6, 210, 110, 20, .14], [90, 40, 100, 85, .18], [4, 60, 120, 100, .15]] as [$x, $y, $l, $a, $op]) $g .= $leaf($x, $y, $l, $a, $op);
            foreach ([[48, 62, 46, 8, 20, .34], [112, 112, 34, 7, 50, .26], [28, 176, 30, 7, 0, .22], [150, 40, 26, 6, 30, .2], [88, 186, 22, 6, 70, .18]] as [$x, $y, $r, $n, $rot, $op]) $g .= $flower($x, $y, $r, $n, $rot, $op);
            return $g . '</g>';
        };
        $o .= $spray(false) . $spray(true);
        $o .= '<g transform="translate(400 0) scale(-1 1)" opacity=".55">' . $leaf(30, 70, 90, 40, .14) . $flower(40, 70, 26, 7, 0, .22) . '</g>';
        $o .= '<g transform="translate(0 640) scale(1 -1)" opacity=".55">' . $leaf(30, 70, 90, 40, .14) . $flower(40, 70, 26, 7, 0, .22) . '</g>';
    } elseif ($d === 'rays') {
        for ($i = 0; $i < 24; $i++) { $a = deg2rad($i * 15); $b = deg2rad($i * 15 + 7.5); $o .= '<path opacity="' . ($i % 2 ? '.035' : '.07') . '" d="M200 300L' . round(200 + 700 * cos($a)) . ' ' . round(300 + 700 * sin($a)) . 'L' . round(200 + 700 * cos($b)) . ' ' . round(300 + 700 * sin($b)) . 'Z"/>'; }
        $o .= '<g fill="none" stroke="currentColor" stroke-width="1.2"><circle cx="200" cy="300" r="150" opacity=".35"/><circle cx="200" cy="300" r="190" opacity=".22"/><circle cx="200" cy="300" r="235" opacity=".14"/></g>';
    } elseif ($d === 'geo') {
        $star = 'M30 4l6.4 14.1 15.3-5.8-5.8 15.3L60 30l-14.1 6.4 5.8 15.3-15.3-5.8L30 60l-6.4-14.1-15.3 5.8 5.8-15.3L0 30l14.1-6.4-5.8-15.3 15.3 5.8z';
        $o .= '<defs><pattern id="gp" width="60" height="60" patternUnits="userSpaceOnUse"><path d="' . $star . '" fill="none" stroke="currentColor" stroke-width="1" opacity=".22"/><circle cx="30" cy="30" r="3" opacity=".18"/><path d="M0 0h2v2H0zM58 0h2v2h-2zM0 58h2v2H0zM58 58h2v2h-2z" opacity=".2"/></pattern>'
            . '<radialGradient id="gf" cx="50%" cy="46%" r="62%"><stop offset="0" stop-color="#fff" stop-opacity="0"/><stop offset=".55" stop-color="#fff" stop-opacity=".0"/><stop offset="1" stop-color="#fff" stop-opacity="1"/></radialGradient>'
            . '<mask id="gm"><rect width="400" height="640" fill="#fff"/><rect width="400" height="640" fill="url(#gf)" style="mix-blend-mode:normal"/></mask></defs>'
            . '<rect width="400" height="640" fill="url(#gp)" mask="url(#gm)" opacity=".9"/>'
            . '<g fill="none" stroke="currentColor" stroke-width="1.4" opacity=".35"><rect x="14" y="14" width="372" height="612" rx="6"/><rect x="22" y="22" width="356" height="596" rx="4" opacity=".6"/></g>';
    } elseif ($d === 'bokeh') {
        mt_srand(7); $o .= '<defs><filter id="bk"><feGaussianBlur stdDeviation="9"/></filter></defs><g filter="url(#bk)">';
        for ($i = 0; $i < 22; $i++) $o .= '<circle cx="' . mt_rand(0, 400) . '" cy="' . mt_rand(0, 640) . '" r="' . mt_rand(10, 48) . '" opacity="' . (mt_rand(8, 26) / 100) . '"/>';
        $o .= '</g><g opacity=".7">';
        for ($i = 0; $i < 16; $i++) { $x = mt_rand(10, 390); $y = mt_rand(10, 630); $s = mt_rand(3, 7); $o .= "<path d=\"M$x " . ($y - $s) . "Q$x $y " . ($x + $s) . " $y Q$x $y $x " . ($y + $s) . "Q$x $y " . ($x - $s) . " {$y}Q$x $y $x " . ($y - $s) . 'Z" opacity=".5"/>'; }
        $o .= '</g>';
    }
    return $o . '</svg>';
}
