<?php
require __DIR__ . '/../lib/bootstrap.php';
unset($_SESSION['client']); session_regenerate_id(true);
redirect(url('panel/login.php'));
