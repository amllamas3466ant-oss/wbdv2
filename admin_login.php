<?php
require_once __DIR__ . '/db.php';     // $conn (database)
require_once __DIR__ . '/auth.php';   // session + helpers

$pageTitle = "Azulis | Admin Log In";

// Already signed in as an admin? Go straight to the dashboard.
if (is_admin_logged_in()) {
    header('Location: admin_dashboard.php');
    exit;
}

$errors = [];
$email  = '';

/*
 * Admin login is a SEPARATE form that looks at the "admins" table only.
 * A customer account in "users" can never pass this check.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = 'Please enter your admin email and password.';
    } else {
        $stmt = $conn->prepare('SELECT id, full_name, email, password FROM admins WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $passwordIsValid = false;

        if ($admin) {
            // Normal case: the column holds a bcrypt hash.
            $passwordIsValid = password_verify($password, $admin['password']);

            // Safety net: if the column holds a plain password (this happens when
            // it is typed by hand in phpMyAdmin) accept it once and immediately
            // replace it with a real hash, so the plain text is never kept.
            if (!$passwordIsValid && hash_equals((string) $admin['password'], $password)) {
                $passwordIsValid = true;

                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $update  = $conn->prepare('UPDATE admins SET password = ? WHERE id = ?');
                $update->bind_param('si', $newHash, $admin['id']);
                $update->execute();
                $update->close();
            }
        }

        if ($passwordIsValid) {
            session_regenerate_id(true);

            $_SESSION['admin_id']    = (int) $admin['id'];
            $_SESSION['admin_name']  = $admin['full_name'];
            $_SESSION['admin_email'] = $admin['email'];

            header('Location: admin_dashboard.php');
            exit;
        }

        $errors[] = 'Incorrect admin email or password.';
    }
}

$flash = get_flash();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo $pageTitle; ?></title>

        <link rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="login.css">
    <link rel="stylesheet" href="styles.css">
</head>

<body>

<main class="auth-page admin-auth">

    <section class="auth-content">

        <!-- Logo and Home Link -->
        <div class="auth-header">
            <a href="index.php" class="logo">
                <img src="Logo.png" alt="Azulis">
            </a>
            <a href="index.php" class="back-home">&larr; Back to Store</a>
        </div>

        <!-- Title -->
        <h1 class="auth-title">Admin Access</h1>

        <p class="auth-subtitle">
            Staff only &mdash; manage customers and orders.
        </p>

        <?php if ($flash): ?>
            <div class="form-alert <?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php foreach ($errors as $error): ?>
            <div class="form-alert error">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endforeach; ?>

        <!-- Admin Login Form -->
        <form class="auth-form" method="post" action="admin_login.php">

            <label for="email">Admin Email</label>

            <input
                type="email"
                id="email"
                name="email"
                placeholder="Enter Username"
                value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                required
            >

            <label for="password">Password</label>

            <input
                type="password"
                id="password"
                name="password"
                placeholder="Enter your password"
                required
            >

            <div class="form-options">
                <span></span>
            </div>

            <button type="submit" class="auth-button">
                Enter Dashboard
            </button>

        </form>

        <p class="admin-note">
            Logged in already? Change the login email or password in
            <strong>Dashboard &rarr; Account</strong>.
        </p>

        <!-- Customer login link -->
        <p class="auth-footer">
            Shopping instead?
            <a href="login.php">Customer log in</a>
        </p>

    </section>

</main>

<?php include __DIR__ . '/footer.php'; ?>

</body>
</html>
