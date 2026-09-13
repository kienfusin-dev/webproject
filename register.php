<?php
session_start();
require_once 'config/db.php';

$errors = [];
$success = false;

// ---------------------------------------------------------
// Every newly registered instructor is automatically assigned
// these 4 subjects. Edit this list to change what new instructors
// are assigned by default.
// ---------------------------------------------------------
$DEFAULT_SUBJECTS = [
    ['code' => 'IT 110',   'name' => 'Systems Integration and Architecture', 'units' => 3],
    ['code' => 'IT 106',   'name' => 'Application Development and Emerging Technologies', 'units' => 3],
    ['code' => 'STAT 12',  'name' => 'Probability and Statistics', 'units' => 3],
    ['code' => 'ETHICS 3', 'name' => 'Ethics', 'units' => 3],
];

// Pre-fill values so the user doesn't have to retype everything on error
$fullNameVal = '';
$usernameVal = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    $fullNameVal = $fullName;
    $usernameVal = $username;

    // ---------- Validation ----------
    if ($fullName === '') {
        $errors[] = 'Full name is required.';
    }

    if ($username === '') {
        $errors[] = 'Username is required.';
    } elseif (!preg_match('/^[a-zA-Z0-9_.]{3,50}$/', $username)) {
        $errors[] = 'Username must be 3-50 characters and can only contain letters, numbers, underscores, and periods.';
    }

    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }

    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    // Check if username is already taken
    if (empty($errors)) {
        $stmt = $conn->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = 'That username is already taken.';
        }
        $stmt->close();
    }

    // ---------- Insert new user ----------
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare('INSERT INTO users (full_name, username, password) VALUES (?, ?, ?)');
        $stmt->bind_param('sss', $fullName, $username, $hashedPassword);

        if ($stmt->execute()) {
            $newInstructorId = $conn->insert_id;
            $stmt->close();

            // Automatically assign the 4 default subjects to this new instructor
            $subjStmt = $conn->prepare('INSERT INTO subjects (subject_code, subject_name, units, instructor_id) VALUES (?, ?, ?, ?)');
            foreach ($DEFAULT_SUBJECTS as $subj) {
                $subjStmt->bind_param('ssii', $subj['code'], $subj['name'], $subj['units'], $newInstructorId);
                $subjStmt->execute();
            }
            $subjStmt->close();

            $success = true;
        } else {
            $errors[] = 'Something went wrong. Please try again.';
            $stmt->close();
        }
    }
}

$pageTitle = 'Register';
require_once 'includes/header.php';
?>

<div class="auth-page">
    <div class="auth-card">
        <div class="auth-brand">
            <i class="fa-solid fa-chalkboard"></i>
            <h1>ClassTrack</h1>
        </div>
        <h2>Create an Account</h2>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fa-solid fa-circle-check"></i>
                Registration successful! You've been assigned 4 default subjects.
            </div>
            <a href="login.php" class="btn btn-primary btn-block">Back to Login</a>
        <?php else: ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="register.php" novalidate>
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name"
                           value="<?php echo htmlspecialchars($fullNameVal); ?>"
                           placeholder="Juan Dela Cruz" required>
                </div>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username"
                           value="<?php echo htmlspecialchars($usernameVal); ?>"
                           placeholder="e.g. jdelacruz" required minlength="3">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password"
                           placeholder="At least 6 characters" required minlength="6">
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password"
                           placeholder="Re-enter your password" required minlength="6">
                </div>

                <button type="submit" class="btn btn-primary btn-block">Register</button>
            </form>

            <p class="auth-switch">Already have an account? <a href="login.php">Log in</a></p>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
