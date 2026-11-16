<?php
/**
 * ADMIN LOGOUT
 * Clears only the admin keys, so a customer login stays logged in.
 */

require_once __DIR__ . '/auth.php';

unset($_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_email']);

set_flash('success', 'Admin session ended.');

header('Location: admin_login.php');
exit;
