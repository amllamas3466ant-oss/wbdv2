<?php
/**
 * CUSTOMER LOGOUT
 * Clears the customer keys from the session and sends the visitor home.
 * An admin login in the same browser is left untouched.
 */

require_once __DIR__ . '/auth.php';

unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email']);

// Also remove the 30-day "remember me" cookie.
setcookie('azulis_remember', '', time() - 3600, '/');

set_flash('success', 'You have been logged out.');

header('Location: Index.php');
exit;
