<?php
/** صور الغلاف الجاهزة اتشالت (الهيدر دلوقتي بيتصمم بألوان السمة — شوف lib/header.php). */
const COVER_PRESETS = [];
function cover_url(array $inv): string { return !empty($inv['cover_image']) ? url('uploads/' . $inv['cover_image']) : ''; }
