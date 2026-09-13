<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';

$userId = $_SESSION['user_id'];
$errors = [];
$success = '';

// Fetch current user info
$stmt = $conn->prepare('SELECT full_name, username, department, contact_number, created_at FROM users WHERE id = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch the subjects assigned to this instructor (shown at the bottom of the profile panel)
$stmt = $conn->prepare('SELECT subject_code, subject_name FROM subjects WHERE instructor_id = ? ORDER BY subject_code ASC');
$stmt->bind_param('i', $userId);
$stmt->execute();
$mySubjects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $contactNumber = trim($_POST['contact_number'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';

    if ($fullName === '') {
        $errors[] = 'Full name cannot be empty.';
    }

    // Verify current password before allowing any change
    if (empty($errors)) {
        $stmt = $conn->prepare('SELECT password FROM users WHERE id = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!password_verify($currentPassword, $row['password'])) {
            $errors[] = 'Current password is incorrect.';
        }
    }

    if (empty($errors)) {
        if ($newPassword !== '') {
            if (strlen($newPassword) < 6) {
                $errors[] = 'New password must be at least 6 characters.';
            } else {
                $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $conn->prepare('UPDATE users SET full_name = ?, department = ?, contact_number = ?, password = ? WHERE id = ?');
                $stmt->bind_param('ssssi', $fullName, $department, $contactNumber, $hashed, $userId);
                $stmt->execute();
                $stmt->close();
            }
        } else {
            $stmt = $conn->prepare('UPDATE users SET full_name = ?, department = ?, contact_number = ? WHERE id = ?');
            $stmt->bind_param('sssi', $fullName, $department, $contactNumber, $userId);
            $stmt->execute();
            $stmt->close();
        }

        if (empty($errors)) {
            $_SESSION['full_name'] = $fullName;
            $user['full_name'] = $fullName;
            $user['department'] = $department;
            $user['contact_number'] = $contactNumber;
            $success = 'Profile updated successfully.';
        }
    }
}

$pageTitle = 'Profile';
require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<main class="page-container">
    <div class="page-header">
        <h1><i class="fa-solid fa-user"></i> My Profile</h1>
        <p>View your instructor details, assigned subjects, and update your account.</p>
    </div>

    <div class="profile-layout">

        <!-- ===================== Profile Panel ===================== -->
        <aside class="profile-panel">
            <div class="profile-panel-header">
                <div class="profile-panel-avatar">
                    <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                </div>
                <h3><?php echo htmlspecialchars($user['full_name']); ?></h3>
                <span class="profile-panel-role">Instructor</span>
            </div>

            <div class="profile-panel-fields">
                <div class="profile-field">
                    <i class="fa-solid fa-id-badge"></i>
                    <div>
                        <span class="field-label">Instructor ID</span>
                        <span class="field-value"><?php echo str_pad($userId, 5, '0', STR_PAD_LEFT); ?></span>
                    </div>
                </div>
                <div class="profile-field">
                    <i class="fa-solid fa-building-columns"></i>
                    <div>
                        <span class="field-label">Department</span>
                        <span class="field-value"><?php echo htmlspecialchars($user['department'] ?: '—'); ?></span>
                    </div>
                </div>
                <div class="profile-field">
                    <i class="fa-solid fa-at"></i>
                    <div>
                        <span class="field-label">Username</span>
                        <span class="field-value"><?php echo htmlspecialchars($user['username']); ?></span>
                    </div>
                </div>
                <div class="profile-field">
                    <i class="fa-solid fa-phone"></i>
                    <div>
                        <span class="field-label">Contact Number</span>
                        <span class="field-value"><?php echo htmlspecialchars($user['contact_number'] ?: '—'); ?></span>
                    </div>
                </div>
                <div class="profile-field">
                    <i class="fa-solid fa-calendar"></i>
                    <div>
                        <span class="field-label">Member Since</span>
                        <span class="field-value"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></span>
                    </div>
                </div>
            </div>

            <div class="profile-panel-subjects">
                <h4>Assigned Subjects (<?php echo count($mySubjects); ?>)</h4>
                <?php if (empty($mySubjects)): ?>
                    <p class="profile-subjects-empty">No subjects assigned yet.</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($mySubjects as $subject): ?>
                            <li>
                                <span class="subject-pill"><?php echo htmlspecialchars($subject['subject_code']); ?></span>
                                <?php echo htmlspecialchars($subject['subject_name']); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </aside>

        <!-- ===================== Edit Form ===================== -->
        <div class="profile-edit">
            <?php if ($success): ?>
                <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="profile.php" class="profile-form">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="department">Department</label>
                    <input type="text" id="department" name="department" value="<?php echo htmlspecialchars($user['department'] ?? ''); ?>" placeholder="e.g. BSIT Department">
                </div>

                <div class="form-group">
                    <label for="contact_number">Contact Number</label>
                    <input type="text" id="contact_number" name="contact_number" value="<?php echo htmlspecialchars($user['contact_number'] ?? ''); ?>" placeholder="e.g. 0912 345 6789">
                </div>

                <div class="form-group">
                    <label for="current_password">Current Password <span class="hint">(required to save changes)</span></label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>

                <div class="form-group">
                    <label for="new_password">New Password <span class="hint">(leave blank to keep current password)</span></label>
                    <input type="password" id="new_password" name="new_password" minlength="6">
                </div>

                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>
        </div>

    </div>
</main>

<?php require_once 'includes/footer.php'; ?>
