<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/Auth.php';

Auth::logout();
header('Location: ' . BASE_URL . '/auth/login.php?msg=saiu');
exit;
