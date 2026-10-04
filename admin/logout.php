<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
audit_log('admin.logout');
$_SESSION = [];
session_destroy();
header('Location: login.php');
exit;
