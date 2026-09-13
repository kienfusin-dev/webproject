<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';

// ---- Summary counts ----
$totalRooms = $conn->query('SELECT COUNT(*) AS cnt FROM classrooms')->fetch_assoc()['cnt'];
$availableRooms = $conn->query("SELECT COUNT(*) AS cnt FROM classrooms WHERE status = 'available'")->fetch_assoc()['cnt'];
$unavailableRooms = $conn->query("SELECT COUNT(*) AS cnt FROM classrooms WHERE status = 'unavailable'")->fetch_assoc()['cnt'];

// ---- Recently added / most relevant rooms (limit 6) for a quick preview ----
$recentRooms = $conn->query('SELECT * FROM classrooms ORDER BY id DESC LIMIT 6')->fetch_all(MYSQLI_ASSOC);

// ---- This instructor's classes for today ----
$instructorId = $_SESSION['user_id'];
$today = date('l'); // e.g. "Monday"

$sql = "SELECT cs.start_time, cs.end_time, cs.section,
               s.subject_code, s.subject_name,
               c.room_number, c.building
        FROM class_schedules cs
        INNER JOIN subjects s ON cs.subject_id = s.id
        INNER JOIN classrooms c ON cs.classroom_id = c.id
        WHERE s.instructor_id = ? AND cs.day_of_week = ?
        ORDER BY cs.start_time ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('is', $instructorId, $today);
$stmt->execute();
$todayClasses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$pageTitle = 'Dashboard';
require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<main class="page-container">
    <div class="page-header">
        <h1><i class="fa-solid fa-gauge"></i> Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h1>
        <p>Here's a quick overview of the current classroom availability.</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <i class="fa-solid fa-chalkboard stat-icon"></i>
            <div>
                <h2><?php echo $totalRooms; ?></h2>
                <p>Total Classrooms</p>
            </div>
        </div>

        <div class="stat-card stat-available">
            <i class="fa-solid fa-circle-check stat-icon"></i>
            <div>
                <h2><?php echo $availableRooms; ?></h2>
                <p>Available Classrooms</p>
            </div>
        </div>

        <div class="stat-card stat-unavailable">
            <i class="fa-solid fa-circle-xmark stat-icon"></i>
            <div>
                <h2><?php echo $unavailableRooms; ?></h2>
                <p>Unavailable Classrooms</p>
            </div>
        </div>
    </div>

    <div class="section-heading">
        <h2>Today's Classes (<?php echo htmlspecialchars($today); ?>)</h2>
        <a href="schedule.php" class="btn btn-outline">View Full Schedule <i class="fa-solid fa-arrow-right"></i></a>
    </div>

    <?php if (empty($todayClasses)): ?>
        <p class="empty-state">You have no classes scheduled for today.</p>
    <?php else: ?>
        <div class="today-classes-list">
            <?php foreach ($todayClasses as $class): ?>
                <div class="today-class-item">
                    <div class="today-class-time">
                        <?php echo date('g:i A', strtotime($class['start_time'])); ?><br>
                        <span class="text-muted"><?php echo date('g:i A', strtotime($class['end_time'])); ?></span>
                    </div>
                    <div class="today-class-details">
                        <strong><?php echo htmlspecialchars($class['subject_code']); ?> — <?php echo htmlspecialchars($class['subject_name']); ?></strong>
                        <p>
                            Section <?php echo htmlspecialchars($class['section'] ?? '—'); ?>
                            &middot; Room <?php echo htmlspecialchars($class['room_number']); ?>
                            (<?php echo htmlspecialchars($class['building']); ?>)
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="section-heading">
        <h2>Classroom Snapshot</h2>
        <a href="rooms.php" class="btn btn-outline">View All Classrooms <i class="fa-solid fa-arrow-right"></i></a>
    </div>

    <div class="room-grid">
        <?php foreach ($recentRooms as $room): ?>
            <?php $isAvailable = $room['status'] === 'available'; ?>
            <div class="room-card <?php echo $isAvailable ? 'card-available' : 'card-unavailable'; ?>">
                <div class="room-card-header">
                    <h3>Room <?php echo htmlspecialchars($room['room_number']); ?></h3>
                    <span class="status-badge <?php echo $isAvailable ? 'badge-available' : 'badge-unavailable'; ?>">
                        <i class="fa-solid <?php echo $isAvailable ? 'fa-circle-check' : 'fa-circle-xmark'; ?>"></i>
                        <?php echo $isAvailable ? 'Available' : 'Unavailable'; ?>
                    </span>
                </div>
                <div class="room-card-body">
                    <p><i class="fa-solid fa-chalkboard"></i> <strong>Type:</strong> <?php echo htmlspecialchars($room['room_type']); ?></p>
                    <p><i class="fa-solid fa-building"></i> <strong>Building:</strong> <?php echo htmlspecialchars($room['building']); ?>, <?php echo htmlspecialchars($room['floor']); ?></p>
                    <p><i class="fa-solid fa-users"></i> <strong>Capacity:</strong> <?php echo (int)$room['capacity']; ?> student(s)</p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>
