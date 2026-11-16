<?php
/**
 * ADMIN GUARD
 *
 * Put this line at the very TOP of every admin-only page:
 *
 *     require_once __DIR__ . '/require_admin.php';
 *
 * Only an account found in the "admins" table can pass. A normal customer
 * session is rejected, because admin logins use different session keys.
 */

require_once __DIR__ . '/auth.php';

if (!is_admin_logged_in()) {
    set_flash('error', 'Please log in with an admin account to open the dashboard.');

    header('Location: admin_login.php');
    exit;
}
