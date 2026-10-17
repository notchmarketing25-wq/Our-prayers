<?php
/** ترقية قاعدة بيانات قديمة: بيضيف الأعمدة الجديدة لو ناقصة (آمن يتكرر) */
function ensure_schema(): void {
    $has = fn($c) => (bool)q("SHOW COLUMNS FROM invitations LIKE '$c'")->fetch();
    if (!$has('accent_color')) db()->exec('ALTER TABLE invitations ADD accent_color CHAR(7) NULL');
    if (!$has('owner_email')) db()->exec('ALTER TABLE invitations ADD owner_email VARCHAR(190) NULL, ADD UNIQUE (owner_email)');
    if (!$has('owner_password')) db()->exec('ALTER TABLE invitations ADD owner_password VARCHAR(255) NULL');
}
