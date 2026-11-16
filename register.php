<?php
require_once __DIR__ . '/db.php';     // $conn (database connection)
require_once __DIR__ . '/auth.php';   // session + helpers

$pageTitle = "Azulis | Create Account";

// A logged-in customer does not need the registration form.
if (is_user_logged_in()) {
    header('Location: Index.php');
    exit;
}

$errors = [];
$fullName = '';
$email    = '';
$username = '';

/*
 * 1. Did the form arrive, or is this just the first page load?
 * 2. Check every field.
 * 3. Save the new customer with a hashed password.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['fullName'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirmPassword'] ?? '';

    if ($fullName === '' || $email === '' || $username === '' || $password === '') {
        $errors[] = 'Please fill in every field.';
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if ($username !== '' && !preg_match('/^[A-Za-z0-9_]{3,20}$/', $username)) {
        $errors[] = 'Username must be 3-20 letters, numbers or underscores.';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }

    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    // Is that email or username already taken?
    if (!$errors) {
        $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1');
        $stmt->bind_param('ss', $email, $username);
        $stmt->execute();

        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = 'That email or username is already registered.';
        }

        $stmt->close();
    }

    // Save the account.
    if (!$errors) {
        // password_hash() turns the password into a one-way bcrypt hash.
        // The plain password is never written to the database.
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare(
            'INSERT INTO users (full_name, email, username, password_hash) VALUES (?, ?, ?, ?)'
        );
        $stmt->bind_param('ssss', $fullName, $email, $username, $hashedPassword);

        if ($stmt->execute()) {
            $stmt->close();

            set_flash('success', 'Account created successfully! You can log in now.');
            header('Location: login.php');
            exit;
        }

        $errors[] = 'We could not create your account. Please try again.';
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo $pageTitle; ?></title>

    <!-- Connect CSS File -->
        <link rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="login.css">
    <link rel="stylesheet" href="styles.css">
</head>

<body>

<main class="auth-page register-page">

    <section class="auth-content">

        <!-- Logo and Home Link -->
        <header class="auth-header">
            <a href="index.php" class="logo">— Azulis —</a>

            <a href="index.php" class="back-home">
                ← Back to Home
            </a>
        </header>

        <!-- Registration Title -->
        <h1 class="auth-title">Create Your Account</h1>

        <p class="auth-subtitle">
            Join Azulis for timeless everyday style.
        </p>

        <?php foreach ($errors as $error): ?>
            <div class="form-alert error">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endforeach; ?>

        <!-- Registration Form -->
        <form class="auth-form" method="post" action="register.php">

            <label for="fullName">Full Name</label>
            <input
                type="text"
                id="fullName"
                name="fullName"
                placeholder="Enter your full name"
                value="<?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>"
                required
            >

            <label for="email">Email Address</label>
            <input
                type="email"
                id="email"
                name="email"
                placeholder="you@example.com"
                value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                required
            >

            <label for="username">Username</label>
            <input
                type="text"
                id="username"
                name="username"
                placeholder="Choose a username"
                value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>"
                required
            >

            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                placeholder="Enter your password"
                minlength="8"
                required
            >

            <label for="confirmPassword">Confirm Password</label>
            <input
                type="password"
                id="confirmPassword"
                name="confirmPassword"
                placeholder="Re-enter your password"
                minlength="8"
                required
            >

            <!-- Terms and Privacy -->
            <div class="terms">
                <label>
                    <input type="checkbox" id="agreeTerms" required>

                    <span>
                        I agree to the
                        <a href="#" id="termsLink">
                            Terms & Conditions
                        </a>
                        and
                        <a href="#" id="privacyLink">
                            Privacy Policy
                        </a>
                    </span>
                </label>
            </div>

            <button type="submit" class="auth-button">
                Create Account
            </button>

        </form>

        <!-- Login Page Link -->
        <p class="auth-footer">
            Already have an account?
            <a href="login.php">Log in</a>
        </p>

    </section>

</main>

<?php include __DIR__ . '/footer.php'; ?>

<!-- JavaScript -->
<script>
    // Terms and Conditions
    document.getElementById("termsLink").addEventListener(
        "click", function(event) {
            event.preventDefault();
            alert("Add your Azulis Terms and Conditions here.");
        }
    );

    // Privacy Policy
    document.getElementById("privacyLink").addEventListener(
        "click", function(event) {
            event.preventDefault();
            alert("Add your Azulis Privacy Policy here.");
        }
    );
</script>

</body>
</html>
