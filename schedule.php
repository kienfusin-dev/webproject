<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';

$instructorId = $_SESSION['user_id'];

// ---- All subjects taught by this instructor ----
$stmt = $conn->prepare('SELECT id, subject_code, subject_name, units FROM subjects WHERE instructor_id = ? ORDER BY subject_code ASC');
$stmt->bind_param('i', $instructorId);
$stmt->execute();
$subjects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ---- Full weekly schedule, joined with subject + classroom info ----
$sql = "SELECT cs.day_of_week, cs.start_time, cs.end_time, cs.section,
               s.subject_code, s.subject_name,
               c.room_number, c.building, c.floor
        FROM class_schedules cs
        INNER JOIN subjects s ON cs.subject_id = s.id
        INNER JOIN classrooms c ON cs.classroom_id = c.id
        WHERE s.instructor_id = ?
        ORDER BY FIELD(cs.day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'),
                 cs.start_time ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $instructorId);
$stmt->execute();
$scheduleRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Group the flat rows by day so we can render one table per day
$scheduleByDay = [];
$dayOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
foreach ($dayOrder as $day) {
    $scheduleByDay[$day] = [];
}
foreach ($scheduleRows as $row) {
    $scheduleByDay[$row['day_of_week']][] = $row;
}

$pageTitle = 'Schedule';
require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<main class="page-container">
    <div class="page-header">
        <h1><i class="fa-solid fa-calendar-days"></i> My Schedule</h1>
        <p>Your assigned subjects and weekly class schedule.</p>
    </div>

    <!-- ===================== My Subjects ===================== -->
    <div class="section-heading">
        <h2>My Subjects</h2>
    </div>

    <?php if (empty($subjects)): ?>
        <p class="empty-state">No subjects have been assigned to your account yet.</p>
    <?php else: ?>
        <div class="subject-grid">
            <?php foreach ($subjects as $subject): ?>
                <div class="subject-card">
                    <div class="subject-code"><?php echo htmlspecialchars($subject['subject_code']); ?></div>
                    <h3><?php echo htmlspecialchars($subject['subject_name']); ?></h3>
                    <p><i class="fa-solid fa-graduation-cap"></i> <?php echo (int)$subject['units']; ?> units</p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- ===================== Weekly Schedule ===================== -->
    <div class="section-heading">
        <h2>Weekly Class Schedule</h2>
    </div>

    <?php if (empty($scheduleRows)): ?>
        <p class="empty-state">No class schedule found for your account yet.</p>
    <?php else: ?>
        <?php foreach ($dayOrder as $day): ?>
            <?php if (empty($scheduleByDay[$day])) continue; ?>
            <div class="schedule-day-block">
                <h3 class="schedule-day-title"><?php echo $day; ?></h3>
                <div class="schedule-table-wrapper">
                    <table class="schedule-table">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Subject</th>
                                <th>Section</th>
                                <th>Room</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($scheduleByDay[$day] as $class): ?>
                                <tr>
                                    <td>
                                        <?php
                                        echo date('g:i A', strtotime($class['start_time'])) . ' - ' .
                                             date('g:i A', strtotime($class['end_time']));
                                        ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($class['subject_code']); ?></strong><br>
                                        <span class="text-muted"><?php echo htmlspecialchars($class['subject_name']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($class['section'] ?? '—'); ?></td>
                                    <td>
                                        Room <?php echo htmlspecialchars($class['room_number']); ?><br>
                                        <span class="text-muted"><?php echo htmlspecialchars($class['building']); ?>, <?php echo htmlspecialchars($class['floor']); ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</main>

<?php require_once 'includes/footer.php'; ?>
