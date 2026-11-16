<?php
/*
 * ADMIN DASHBOARD - admin only.
 * This single require blocks every visitor who is not logged in through
 * admin_login.php, before any HTML or data is produced.
 */
require_once __DIR__ . '/require_admin.php';
require_once __DIR__ . '/db.php';

$pageTitle = 'Azulis | Admin Dashboard';

/* ---------- 1. Small summary numbers ---------- */
$customerCount = (int) $conn->query('SELECT COUNT(*) FROM users')->fetch_row()[0];
$orderCount    = (int) $conn->query('SELECT COUNT(*) FROM orders')->fetch_row()[0];

$revenueRow = $conn->query('SELECT COALESCE(SUM(total), 0) FROM orders')->fetch_row();
$revenue    = (float) $revenueRow[0];

$pendingCount = (int) $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'Pending'")->fetch_row()[0];

/* ---------- 2. Latest orders with the customer who placed them ---------- */
$orders = $conn->query(
    'SELECT o.id, o.customer_name, o.total, o.status, o.created_at, u.email
     FROM orders o
     JOIN users u ON u.id = o.user_id
     ORDER BY o.created_at DESC
     LIMIT 10'
)->fetch_all(MYSQLI_ASSOC);

/* ---------- 3. Registered customers ---------- */
$customers = $conn->query(
    'SELECT id, full_name, email, username, created_at
     FROM users
     ORDER BY created_at DESC
     LIMIT 10'
)->fetch_all(MYSQLI_ASSOC);

$flash = get_flash();

$money = static fn($amount) => '₱' . number_format((float) $amount, 2);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>

    <header>
        <a href="Index.php" class="logo"><img src="Logo.png" alt="Azulis Logo"></a>
        <nav>
            <a href="admin_dashboard.php" class="active">Dashboard</a>
            <a href="admin_account.php">Account</a>
            <a href="admin_logout.php">Log Out</a>
        </nav>
        <div class="icons">
            <span class="user-greeting"><?= htmlspecialchars($_SESSION['admin_name'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    </header>

    <main class="admin-main">

        <div class="admin-heading">
            <p class="eyebrow">Admin Area</p>
            <h1>Store Overview</h1>
        </div>

        <?php if ($flash): ?>
            <div class="form-alert <?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <!-- SUMMARY CARDS -->
        <section class="admin-stats">
            <article class="stat-card">
                <p class="stat-label">Customers</p>
                <p class="stat-value"><?= $customerCount ?></p>
            </article>

            <article class="stat-card">
                <p class="stat-label">Orders</p>
                <p class="stat-value"><?= $orderCount ?></p>
            </article>

            <article class="stat-card">
                <p class="stat-label">Pending</p>
                <p class="stat-value"><?= $pendingCount ?></p>
            </article>

            <article class="stat-card">
                <p class="stat-label">Total Sales</p>
                <p class="stat-value"><?= $money($revenue) ?></p>
            </article>
        </section>

        <!-- ORDERS TABLE -->
        <section class="admin-panel">
            <h2>Latest Orders</h2>

            <?php if (!$orders): ?>
                <p class="admin-empty">No orders yet. Orders appear here after a logged-in customer checks out.</p>
            <?php else: ?>
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Customer</th>
                                <th>Email</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>#<?= (int) $order['id'] ?></td>
                                    <td><?= htmlspecialchars($order['customer_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($order['email'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= $money($order['total']) ?></td>
                                    <td><span class="status-pill"><?= htmlspecialchars($order['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                    <td><?= date('M j, Y g:i A', strtotime($order['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <!-- CUSTOMERS TABLE -->
        <section class="admin-panel">
            <h2>Registered Customers</h2>

            <?php if (!$customers): ?>
                <p class="admin-empty">No customer accounts yet.</p>
            <?php else: ?>
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Username</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customers as $customer): ?>
                                <tr>
                                    <td><?= (int) $customer['id'] ?></td>
                                    <td><?= htmlspecialchars($customer['full_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($customer['email'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($customer['username'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= date('M j, Y', strtotime($customer['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

    </main>

    <?php include __DIR__ . '/footer.php'; ?>

</body>
</html>
