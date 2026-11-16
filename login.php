<?php
require_once __DIR__ . '/db.php';     // $conn (database connection)
require_once __DIR__ . '/auth.php';   // session + helpers

$pageTitle = "Azulis | Log In";

// A guard (require_user.php) sends visitors here as login.php?next=checkout.php.
// Only pages from this list are accepted, so "next" cannot be abused.
$allowedNext = ['Index.php', 'shop.php', 'checkout.php'];
$next = $_GET['next'] ?? 'Index.php';

if (!in_array($next, $allowedNext, true)) {
    $next = 'Index.php';
}

// Already logged in? Skip the form.
if (is_user_logged_in()) {
    header('Location: ' . $next);
    exit;
}

$errors = [];
$email  = '';

/*
 * The form posts back to this same file.
 * $_SERVER['REQUEST_METHOD'] tells us whether the page was just opened (GET)
 * or the Log In button was pressed (POST).
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $next = in_array($_POST['next'] ?? '', $allowedNext, true) ? $_POST['next'] : 'Index.php';

    if ($email === '' || $password === '') {
        $errors[] = 'Please enter both your email address and password.';
    } else {
        // A prepared statement keeps the typed email out of the SQL text.
        $stmt = $conn->prepare('SELECT id, full_name, email, password_hash FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // password_verify() compares the typed password with the stored hash.
        if ($user && password_verify($password, $user['password_hash'])) {
            // Fresh session id after login (protects against session fixation).
            session_regenerate_id(true);

            $_SESSION['user_id']    = (int) $user['id'];
            $_SESSION['user_name']  = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];

            if (!empty($_POST['remember'])) {
                setcookie('azulis_remember', '1', time() + 60 * 60 * 24 * 30, '/');
            }

            header('Location: ' . $next);
            exit;
        }

        $errors[] = 'Incorrect email or password.';
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

    <!-- Connect CSS File -->
        <link rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="login.css">
    <link rel="stylesheet" href="styles.css">
</head>

<body>

<main class="auth-page">

    <section class="auth-content">

        <!-- Logo and Home Link -->
        <div class="auth-header">
    <a href="index.php" class="logo">
        <img src="Logo.png" alt="Azulis">
    </a>
    <a href="index.php" class="back-home">&larr; Back to Home</a>
</div>


        <!-- Login Title -->
        <h1 class="auth-title">Welcome Back</h1>

        <p class="auth-subtitle">
            Sign in to discover your everyday elegance.
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

        <!-- Login Form -->
        <form class="auth-form" method="post" action="login.php">

            <input type="hidden" name="next" value="<?= htmlspecialchars($next, ENT_QUOTES, 'UTF-8') ?>">

            <label for="email">Email Address</label>

            <input
                type="email"
                id="email"
                name="email"
                placeholder="you@example.com"
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

            <!-- Remember Me and Forgot Password -->
            <div class="form-options">

                <label>
                    <input type="checkbox" id="remember" name="remember" value="1">
                    Remember me
                </label>

                <a href="#" id="forgotPassword">
                    Forgot password?
                </a>

            </div>

            <button type="submit" class="auth-button">
                Log In
            </button>

        </form>

        <!-- Google Sign-in -->
        <div class="divider-text">or continue with</div>

        <a href="#" class="google-button" id="googleLogin">
            🌐 &nbsp; Continue with Google
        </a>

        <!-- Register Page Link -->
        <p class="auth-footer">
            New to Azulis?
            <a href="register.php">Create an account</a>
        </p>

    </section>

</main>

<?php include __DIR__ . '/footer.php'; ?>

<!-- JavaScript -->
<script>
    // Show or hide password
    document.getElementById("password").insertAdjacentHTML(
        "afterend",
        '<button type="button" id="togglePassword" ' +
        'style="margin-top:8px; background:none; border:none; ' +
        'cursor:pointer; font-family:Georgia;">Show password</button>'
    );

    document.getElementById("togglePassword").addEventListener(
        "click", function() {

            const password = document.getElementById("password");
            const showing = password.type === "text";

            password.type = showing ? "password" : "text";

            this.textContent = showing
                ? "Show password"
                : "Hide password";
        }
    );

    // Forgot password message
    document.getElementById("forgotPassword").addEventListener(
        "click", function(event) {

            event.preventDefault();

            alert(
                "Please contact Azulis support to reset your password."
            );
        }
    );

    // Google sign-in demonstration
    document.getElementById("googleLogin").addEventListener(
        "click", function(event) {

            event.preventDefault();

            alert(
                "Google sign-in needs Google authentication setup."
            );
        }
    );
</script>

</body>
</html>
