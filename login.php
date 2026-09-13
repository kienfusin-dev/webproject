<?php
session_start();
require_once 'config/db.php';

// If already logged in, skip straight to the dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$usernameVal = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $usernameVal = $username;

    if ($username === '' || $password === '') {
        $errors[] = 'Please enter both username and password.';
    } else {
        $stmt = $conn->prepare('SELECT id, full_name, username, password FROM users WHERE username = ?');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            // Credentials are correct — start the session
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['username']  = $user['username'];

            header('Location: dashboard.php');
            exit;
        } else {
            $errors[] = 'Invalid username or password.';
        }
    }
}

$pageTitle = 'Login';
require_once 'includes/header.php';
?>

<div class="auth-page">
    <div class="auth-card">
        <div class="auth-brand">
            <i class="fa-solid fa-chalkboard"></i>
            <h1>ClassTrack</h1>
        </div>
        <h2>Welcome Back</h2>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" novalidate>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username"
                       value="<?php echo htmlspecialchars($usernameVal); ?>"
                       placeholder="e.g. jdelacruz" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                       placeholder="Your password" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Log In</button>
        </form>

        <p class="auth-switch">Don't have an account? <a href="register.php">Register here</a></p>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
