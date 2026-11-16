<?php
/**
 * SESSION + LOGIN HELPERS
 *
 * This file only remembers WHO is logged in, using $_SESSION.
 * It never touches the database, so it is safe to include on every page.
 *
 * Customer login  -> $_SESSION['user_id']  / ['user_name'] / ['user_email']
 * Admin login     -> $_SESSION['admin_id'] / ['admin_name'] / ['admin_email']
 *
 * They are separate groups of keys on purpose: an admin account lives in the
 * "admins" table, a customer lives in "users", and neither can log in for
 * the other. Admin pages check is_admin_logged_in(), shop pages check
 * is_user_logged_in().
 */

if (session_status() === PHP_SESSION_NONE) {
    // "Remember me" keeps the session cookie for 30 days.
    // Without it the cookie is deleted when the browser closes.
    $lifetime = !empty($_COOKIE['azulis_remember']) ? 60 * 60 * 24 * 30 : 0;

    session_set_cookie_params([
        'lifetime' => $lifetime,
        'path'     => '/',
        'httponly' => true,   // JavaScript cannot read the cookie
        'samesite' => 'Lax',
    ]);

    session_start();
}

/* Is a customer logged in? */
function is_user_logged_in(): bool
{
    return isset($_SESSION['user_id']);
}

/* Customer name for greetings, or an empty string. */
function current_user_name(): string
{
    return $_SESSION['user_name'] ?? '';
}

/* Is an admin logged in? */
function is_admin_logged_in(): bool
{
    return isset($_SESSION['admin_id']);
}

/**
 * One-time messages that survive a redirect (Post -> Redirect -> Get).
 * set_flash('error', '...') on one page, get_flash() on the next one.
 */
function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}
