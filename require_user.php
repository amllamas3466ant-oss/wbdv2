<?php
/**
 * CUSTOMER GUARD
 *
 * Put this line at the very TOP of any page that only a logged-in customer
 * may open (checkout, order history, account page...):
 *
 *     require_once __DIR__ . '/require_user.php';
 *
 * If nobody is logged in, the page stops right here and the visitor is sent
 * to login.php instead. Nothing below this file ever runs.
 */

require_once __DIR__ . '/auth.php';

if (!is_user_logged_in()) {
    // Only these pages are allowed as a "go back to" target after logging in.
    $next = basename($_SERVER['PHP_SELF'] ?? 'shop.php');

    if (!in_array($next, ['shop.php', 'checkout.php'], true)) {
        $next = 'shop.php';
    }

    set_flash('error', 'Please log in first so you can place an order.');

    header('Location: login.php?next=' . urlencode($next));
    exit;
}
