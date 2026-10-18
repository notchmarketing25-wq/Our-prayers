<?php
require __DIR__ . '/../lib/bootstrap.php';
$_SESSION = []; session_destroy();
redirect(url('admin/login.php'));
