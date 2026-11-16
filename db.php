<?php
/**
 * AZULIS DATABASE CONNECTION
 *
 * Every page that needs the database includes this file:
 *
 *     require_once __DIR__ . '/db.php';
 *
 * It gives you one ready-to-use mysqli object called $conn.
 *
 * On XAMPP the default MySQL account is "root" with an empty password,
 * and the database name must match the one created by database.sql.
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'azulis_db');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die(
        'Database connection failed: ' . $conn->connect_error .
        '<br>Open phpMyAdmin and import database.sql first.'
    );
}

// Store names and special characters correctly (₱, em dashes, etc.).
$conn->set_charset('utf8mb4');
