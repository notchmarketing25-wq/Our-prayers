<?php
/** نصوص صفحة الدعوة بالعربي والإنجليزي. المحتوى اللي بيكتبه الأدمن (الأسماء/الرسالة/الأماكن) بيتعرض زي ما هو. */
const I18N = [
    'ar' => [
        'invited' => 'نتشرف بدعوتكم', 'dear' => 'أهلًا %s', 'details' => 'التفاصيل', 'd' => 'يوم', 'h' => 'ساعة', 'm' => 'دقيقة', 's' => 'ثانية',
        'events' => 'المناسبات', 'map' => 'افتح الخريطة', 'cal' => 'أضف للتقويم', 'moments' => 'لحظاتنا', 'view_img' => 'عرض الصورة',
        'rsvp' => 'تأكيد الحضور', 'name' => 'الاسم', 'phone' => 'رقم الموبايل (اختياري)', 'attend' => 'هحضر إن شاء الله', 'decline' => 'أعتذر',
        'party' => 'عدد الأفراد', 'note' => 'رسالة للعروسين (اختياري)', 'send' => 'إرسال',
        'cur_yes' => 'ردك الحالي: حاضر (%d) — تقدر تغيّره من تحت.', 'cur_no' => 'ردك الحالي: معتذر — تقدر تغيّره من تحت.',
        'credit' => 'اعمل دعوتك من', 'tap' => 'اضغط لفتح الدعوة', 'open_aria' => 'افتح الدعوة', 'private' => 'دعوة خاصة',
        'pw_ph' => 'اكتب كلمة السر', 'enter' => 'دخول', 'wrong_pw' => 'كلمة السر غلط.', 'not_found' => 'الدعوة غير موجودة.',
        'close' => 'إغلاق', 'next' => 'التالي', 'prev' => 'السابق', 'music' => 'موسيقى',
        'need_name' => 'اكتب اسمك من فضلك.', 'wait' => 'استنى لحظة وحاول تاني.',
        'ok_yes' => 'تم تأكيد حضورك، نتشرف بيك 🤍', 'ok_no' => 'وصلنا اعتذارك، شكرًا لتواصلك.',
        'am' => 'صباحًا', 'pm' => 'مساءً',
        'wa_invite' => "أهلًا %1\$s 🤍\nيشرفنا حضورك %2\$s %3\$s\nدعوتك الخاصة:\n%4\$s",
        'wa_thanks' => "أهلًا %1\$s 🤍\nشكرًا على ردك! تقدر تعدّله من هنا:\n%2\$s",
    ],
    'en' => [
        'invited' => 'You are cordially invited', 'dear' => 'Dear %s', 'details' => 'Details', 'd' => 'Days', 'h' => 'Hours', 'm' => 'Minutes', 's' => 'Seconds',
        'events' => 'The Events', 'map' => 'Open map', 'cal' => 'Add to calendar', 'moments' => 'Our moments', 'view_img' => 'View photo',
        'rsvp' => 'RSVP', 'name' => 'Your name', 'phone' => 'Phone number (optional)', 'attend' => 'Joyfully accepts', 'decline' => 'Regretfully declines',
        'party' => 'Number of guests', 'note' => 'A message for the couple (optional)', 'send' => 'Send',
        'cur_yes' => 'Your reply: attending (%d) — you can change it below.', 'cur_no' => 'Your reply: not attending — you can change it below.',
        'credit' => 'Create your invitation at', 'tap' => 'Tap to open the invitation', 'open_aria' => 'Open the invitation', 'private' => 'Private invitation',
        'pw_ph' => 'Enter password', 'enter' => 'Enter', 'wrong_pw' => 'Wrong password.', 'not_found' => 'Invitation not found.',
        'close' => 'Close', 'next' => 'Next', 'prev' => 'Previous', 'music' => 'Music',
        'need_name' => 'Please enter your name.', 'wait' => 'Please wait a moment and try again.',
        'ok_yes' => 'Your attendance is confirmed. We can\'t wait to see you! 🤍', 'ok_no' => 'We received your reply. Thank you for letting us know.',
        'am' => 'AM', 'pm' => 'PM',
        'wa_invite' => "Dear %1\$s 🤍\nWe would be honoured by your presence at %2\$s of %3\$s\nYour personal invitation:\n%4\$s",
        'wa_thanks' => "Dear %1\$s 🤍\nThank you for your reply! You can update it here:\n%2\$s",
    ],
];
function inv_lang(array $inv): string { return ($inv['lang'] ?? 'ar') === 'en' ? 'en' : 'ar'; }
function tr(string $k, string $lang = 'ar', ...$args): string {
    $s = I18N[$lang][$k] ?? I18N['ar'][$k] ?? $k;
    return $args ? vsprintf($s, $args) : $s;
}
/** [تاريخ منسّق, وقت منسّق] حسب اللغة */
function fmt_dt(int $ts, string $lang): array {
    if ($lang === 'en') return [date('l, F j, Y', $ts), date('g:i A', $ts)];
    $days = ['الأحد','الإثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت'];
    $mon = ['','يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
    return [$days[(int)date('w', $ts)] . ' ' . date('j', $ts) . ' ' . $mon[(int)date('n', $ts)] . ' ' . date('Y', $ts), (int)date('g', $ts) . ':' . date('i', $ts) . ' ' . (date('A', $ts) === 'AM' ? 'صباحًا' : 'مساءً')];
}
