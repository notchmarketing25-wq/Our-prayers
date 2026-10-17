<?php
/** ترقية قاعدة بيانات قديمة: بيضيف الأعمدة الجديدة لو ناقصة (آمن يتكرر) */
function ensure_schema(): void {
    $has = fn($c) => (bool)q("SHOW COLUMNS FROM invitations LIKE '$c'")->fetch();
    if (!$has('lang')) db()->exec("ALTER TABLE invitations ADD lang CHAR(2) NOT NULL DEFAULT 'ar'");
    if (!$has('cover_preset')) db()->exec('ALTER TABLE invitations ADD cover_preset VARCHAR(30) NULL');
    foreach (['intro_title' => 'VARCHAR(160)', 'intro_text' => 'VARCHAR(255)', 'photo_caption' => 'VARCHAR(255)', 'caption_pos' => 'VARCHAR(8)'] as $c => $t)
        if (!$has($c)) db()->exec("ALTER TABLE invitations ADD $c $t NULL");
    if (!$has('music_file')) db()->exec('ALTER TABLE invitations ADD music_file VARCHAR(255) NULL');
    if (!$has('accent_color')) db()->exec('ALTER TABLE invitations ADD accent_color CHAR(7) NULL');
    if (!$has('owner_email')) db()->exec('ALTER TABLE invitations ADD owner_email VARCHAR(190) NULL, ADD UNIQUE (owner_email)');
    if (!$has('owner_password')) db()->exec('ALTER TABLE invitations ADD owner_password VARCHAR(255) NULL');
}
