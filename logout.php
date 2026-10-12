<?php
// logout.php - Session Destroy & Logout
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

$db = getDb();
logout_user($db);
flash('ok', 'Signed out');
header('Location: index.php');
exit;
