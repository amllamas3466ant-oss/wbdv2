<?php
/*
 * ADMIN ACCOUNT - admin only.
 * The page first SHOWS the login details (read only).
 * Editing is a separate step, opened with the "Edit Details" button,
 * so nothing is asked for until you actually want to change something.
 */
require_once __DIR__ . '/require_admin.php';
require_once __DIR__ . '/db.php';

$pageTitle = 'Azulis | Admin Account';

$adminId = (int) $_SESSION['admin_id'];
$errors  = [];

/* ---------- 1. Load the row this admin logged in with ---------- */
$stmt = $conn->prepare('SELECT full_name, email, password FROM admins WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $adminId);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$admin) {
    // The account was removed while this session was open.
    unset($_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_email']);
    header('Location: admin_login.php');
    exit;
}

$fullName = $admin['full_name'];
$email    = $admin['email'];

/* ---------- 2. View mode vs. edit mode ---------- */
// The form only appears after clicking "Edit Details" (or when a save fails).
$editing = isset($_GET['edit']);

/* ---------- 3. Save the form ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $editing = true;

    $fullName        = trim($_POST['full_name'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword     = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Saving changes always requires the current password.
    if (!password_verify($currentPassword, $admin['password'])) {
        $errors[] = 'Your current password is incorrect.';
    }

    if ($fullName === '') {
        $errors[] = 'Full name cannot be empty.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    // The email is the username, so it must stay unique.
    if (!$errors) {
        $check = $conn->prepare('SELECT id FROM admins WHERE email = ? AND id <> ? LIMIT 1');
        $check->bind_param('si', $email, $adminId);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            $errors[] = 'That email is already used by another admin.';
        }

        $check->close();
    }

    // Changing the password is optional: blank means "keep the current one".
    $changePassword = ($newPassword !== '' || $confirmPassword !== '');

    if ($changePassword) {
        if (strlen($newPassword) < 8) {
            $errors[] = 'The new password must be at least 8 characters long.';
        }

        if ($newPassword !== $confirmPassword) {
            $errors[] = 'The new passwords do not match.';
        }
    }

    if (!$errors) {
        $storedPassword = $changePassword
            ? password_hash($newPassword, PASSWORD_DEFAULT)
            : $admin['password'];

        $stmt = $conn->prepare(
            'UPDATE admins SET full_name = ?, email = ?, password = ? WHERE id = ?'
        );
        $stmt->bind_param('sssi', $fullName, $email, $storedPassword, $adminId);
        $stmt->execute();
        $stmt->close();

        $_SESSION['admin_name']  = $fullName;
        $_SESSION['admin_email'] = $email;

        set_flash(
            'success',
            $changePassword
                ? 'Login email and password updated. Use the new details next time.'
                : 'Admin details updated. Your password was not changed.'
        );

        // Back to the read-only view.
        header('Location: admin_account.php');
        exit;
    }
}

$flash = get_flash();
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
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="admin_account.php" class="active">Account</a>
            <a href="admin_logout.php">Log Out</a>
        </nav>
        <div class="icons">
            <span class="user-greeting"><?= htmlspecialchars($_SESSION['admin_name'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    </header>

    <main class="admin-main">

        <div class="admin-heading">
            <p class="eyebrow">Admin Area</p>
            <h1>Admin Account</h1>
            <p>This is the email and password you use on the admin log-in page.</p>
        </div>

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

        <section class="admin-panel">
            <h2>Login Details</h2>

            <?php if (!$editing): ?>

                <!-- ===== VIEW MODE: just show the details ===== -->
                <dl class="admin-details">
                    <div>
                        <dt>Full Name</dt>
                        <dd><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?></dd>
                    </div>

                    <div>
                        <dt>Login Email (username)</dt>
                        <dd><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></dd>
                    </div>

                    <div>
                        <dt>Password</dt>
                        <dd>
                            ••••••••
                            <span class="admin-muted">stored as a bcrypt hash</span>
                        </dd>
                    </div>
                </dl>

                <a href="admin_account.php?edit=1" class="admin-edit-btn">Edit Details</a>

            <?php else: ?>

                <!-- ===== EDIT MODE: only opened on purpose ===== -->
                <form class="admin-form" method="post" action="admin_account.php">

                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name"
                           value="<?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>" required>

                    <label for="email">Login Email (this is the username)</label>
                    <input type="email" id="email" name="email"
                           value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" required>
                    <p class="admin-hint">You type this email on <strong>admin_login.php</strong>.</p>

                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password"
                           placeholder="Confirm it is you" required>
                    <p class="admin-hint">Needed before anything can be saved.</p>

                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password"
                           placeholder="Leave blank to keep the current password" minlength="8">
                    <p class="admin-hint">Optional. At least 8 characters if you do change it.</p>

                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password"
                           placeholder="Re-enter the new password" minlength="8">

                    <div class="admin-actions">
                        <button type="submit">Save Changes</button>
                        <a href="admin_account.php" class="admin-cancel">Cancel</a>
                    </div>
                </form>

            <?php endif; ?>
        </section>

    </main>

    <?php include __DIR__ . '/footer.php'; ?>

</body>
</html>
