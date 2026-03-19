<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/Auth.php';

Auth::logout();
header('Location: /SysFAA/auth/login.php?msg=saiu');
exit;
