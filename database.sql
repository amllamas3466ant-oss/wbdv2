-- ============================================================
--  AZULIS DATABASE  (azulis_db)
--  phpMyAdmin:  http://localhost/phpmyadmin
--  -> Import tab -> choose database.sql -> Go
--  Safe to run again: existing tables and accounts are kept.
-- ============================================================

CREATE DATABASE IF NOT EXISTS azulis_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE azulis_db;

-- ------------------------------------------------------------
-- 1. CUSTOMERS  (normal website visitors)
--    Passwords are stored as bcrypt hashes in `password_hash`.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id              INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name       VARCHAR(120) NOT NULL,
    email           VARCHAR(190) NOT NULL,
    contact_number  VARCHAR(30) NOT NULL DEFAULT '',
    username        VARCHAR(60) NOT NULL,
    password_hash   VARCHAR(255) NOT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY users_email_unique (email),
    UNIQUE KEY users_username_unique (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 2. ADMINS  (a separate table = a separate login)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS admins (
    id          INT(11) NOT NULL AUTO_INCREMENT,
    full_name   VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL,
    password    VARCHAR(255) NOT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 3. MESSAGES FROM THE CONTACT PAGE
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS contact_messages (
    id              INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name       VARCHAR(120) NOT NULL,
    email           VARCHAR(190) NOT NULL,
    contact_number  VARCHAR(30) NOT NULL DEFAULT '',
    subject         VARCHAR(40) NOT NULL,
    message         TEXT NOT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY contact_messages_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 4. ORDERS  (one row per checkout - only logged-in customers)
--    user_id is UNSIGNED because users.id is UNSIGNED; a foreign
--    key needs both columns to be exactly the same type.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS orders (
    id             INT(11) NOT NULL AUTO_INCREMENT,
    user_id        INT(10) UNSIGNED NOT NULL,
    customer_name  VARCHAR(120) NOT NULL,
    phone          VARCHAR(30) NOT NULL,
    address        TEXT NOT NULL,
    total          DECIMAL(10,2) NOT NULL,
    status         VARCHAR(20) NOT NULL DEFAULT 'Pending',
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY orders_user_id (user_id),
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 5. ORDER ITEMS  (the bags inside each order)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS order_items (
    id            INT(11) NOT NULL AUTO_INCREMENT,
    order_id      INT(11) NOT NULL,
    product_id    INT(11) NOT NULL,
    product_name  VARCHAR(150) NOT NULL,
    price         DECIMAL(10,2) NOT NULL,
    quantity      INT(11) NOT NULL,
    PRIMARY KEY (id),
    KEY order_items_order_id (order_id),
    CONSTRAINT fk_items_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 6. DEFAULT ACCOUNTS
--    Passwords are bcrypt hashes, never plain text.
--    INSERT IGNORE keeps these statements safe to run twice.
-- ------------------------------------------------------------

-- Admin login -> admin_login.php
-- email: admin@azulis.com      password: Admin@123
INSERT IGNORE INTO admins (full_name, email, password) VALUES
    ('Azulis Admin', 'admin@azulis.com', '$2y$10$26g8g9IQFanNKaX2hFqdzuBL0RIK2dcZw8K3VI6UKgt6pbFqPDWqm');

-- Demo customer -> login.php (you can delete this row any time)
-- email: demo@azulis.com       password: User@123
INSERT IGNORE INTO users (full_name, email, contact_number, username, password_hash) VALUES
    ('Demo Customer', 'demo@azulis.com', '09171234567', 'demo', '$2y$10$ujIkrkxnQkbUjLOTbstEzez04yBZzzvilpvisX5oY1rvGELQd5Sk6');
