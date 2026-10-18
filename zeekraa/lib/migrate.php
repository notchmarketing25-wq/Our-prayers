<?php
/** ترقية قاعدة بيانات قديمة: بيضيف الأعمدة الجديدة لو ناقصة (آمن يتكرر) */
function ensure_schema(): void {
    $has = fn($c) => (bool)q("SHOW COLUMNS FROM invitations LIKE '$c'")->fetch();
    if (!$has('lang')) db()->exec("ALTER TABLE invitations ADD lang CHAR(2) NOT NULL DEFAULT 'ar'");
    if (!$has('cover_preset')) db()->exec('ALTER TABLE invitations ADD cover_preset VARCHAR(30) NULL');
    foreach (['intro_title' => 'VARCHAR(160)', 'intro_text' => 'VARCHAR(255)', 'photo_caption' => 'VARCHAR(255)', 'caption_pos' => 'VARCHAR(8)'] as $c => $t)
        if (!$has($c)) db()->exec("ALTER TABLE invitations ADD $c $t NULL");
    foreach (['titles' => 'TEXT', 'caption_style' => 'VARCHAR(10)', 'caption_color' => 'VARCHAR(10)', 'caption_size' => 'VARCHAR(4)', 'page_title' => 'VARCHAR(160)', 'page_desc' => 'VARCHAR(255)'] as $c => $t)
        if (!$has($c)) db()->exec("ALTER TABLE invitations ADD $c $t NULL");
    foreach (['header_style' => 'VARCHAR(10)', 'header_decor' => 'VARCHAR(10)', 'header_color' => 'CHAR(7)', 'hero_hide' => 'VARCHAR(80)'] as $c => $t)
        if (!$has($c)) db()->exec("ALTER TABLE invitations ADD $c $t NULL");
    if (!$has('show_party')) db()->exec('ALTER TABLE invitations ADD show_party TINYINT NULL');
    if (!$has('panel_token')) db()->exec('ALTER TABLE invitations ADD panel_token CHAR(64) NULL');
    if (!$has('caption_frame')) db()->exec('ALTER TABLE invitations ADD caption_frame TINYINT NULL');
    if (!$has('events_title')) db()->exec('ALTER TABLE invitations ADD events_title VARCHAR(80) NULL');
    if (!$has('notes')) db()->exec('ALTER TABLE invitations ADD notes TEXT NULL');
    if (!q("SHOW COLUMNS FROM events LIKE 'note'")->fetch()) db()->exec('ALTER TABLE events ADD note VARCHAR(255) NULL');
    if (!$has('music_file')) db()->exec('ALTER TABLE invitations ADD music_file VARCHAR(255) NULL');
    if (!$has('accent_color')) db()->exec('ALTER TABLE invitations ADD accent_color CHAR(7) NULL');
    if (!$has('owner_email')) db()->exec('ALTER TABLE invitations ADD owner_email VARCHAR(190) NULL, ADD UNIQUE (owner_email)');
    if (!$has('owner_password')) db()->exec('ALTER TABLE invitations ADD owner_password VARCHAR(255) NULL');
}
