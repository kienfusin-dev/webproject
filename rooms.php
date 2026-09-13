<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';

$currentUserId = $_SESSION['user_id'];

// Optional filter via ?status=available or ?status=unavailable
$statusFilter = $_GET['status'] ?? 'all';
$allowedFilters = ['all', 'available', 'unavailable'];
if (!in_array($statusFilter, $allowedFilters, true)) {
    $statusFilter = 'all';
}

// Join with users so we know WHO is currently occupying each room
$baseSql = "SELECT c.*, u.full_name AS occupant_name
            FROM classrooms c
            LEFT JOIN users u ON c.occupied_by = u.id";

if ($statusFilter === 'all') {
    $result = $conn->query($baseSql . ' ORDER BY c.room_number ASC');
} else {
    $stmt = $conn->prepare($baseSql . ' WHERE c.status = ? ORDER BY c.room_number ASC');
    $stmt->bind_param('s', $statusFilter);
    $stmt->execute();
    $result = $stmt->get_result();
}

$classrooms = $result->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Classrooms';
require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<main class="page-container">
    <div class="page-header">
        <h1><i class="fa-solid fa-chalkboard-user"></i> Classroom Availability</h1>
        <p>Browse all classrooms, check their status, and reserve one for your class.</p>
    </div>

    <?php if (isset($_GET['reserved'])): ?>
        <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> Room reserved — it's now marked unavailable to other instructors.</div>
    <?php elseif (isset($_GET['released'])): ?>
        <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> Room released — it's now available again.</div>
    <?php elseif (isset($_GET['error'])): ?>
        <div class="alert alert-error"><ul><li><?php echo htmlspecialchars($_GET['error']); ?></li></ul></div>
    <?php endif; ?>

    <div class="filter-bar">
        <a href="rooms.php?status=all" class="filter-btn <?php echo $statusFilter === 'all' ? 'active' : ''; ?>">
            All Classrooms
        </a>
        <a href="rooms.php?status=available" class="filter-btn filter-available <?php echo $statusFilter === 'available' ? 'active' : ''; ?>">
            <i class="fa-solid fa-circle-check"></i> Available
        </a>
        <a href="rooms.php?status=unavailable" class="filter-btn filter-unavailable <?php echo $statusFilter === 'unavailable' ? 'active' : ''; ?>">
            <i class="fa-solid fa-circle-xmark"></i> Unavailable
        </a>
    </div>

    <div class="room-grid">
        <?php if (empty($classrooms)): ?>
            <p class="empty-state">No classrooms found for this filter.</p>
        <?php endif; ?>

        <?php foreach ($classrooms as $room): ?>
            <?php
            $isAvailable  = $room['status'] === 'available';
            $isMine       = !$isAvailable && (int)$room['occupied_by'] === $currentUserId;
            ?>
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
                    <p><i class="fa-solid fa-building"></i> <strong>Building:</strong> <?php echo htmlspecialchars($room['building']); ?></p>
                    <p><i class="fa-solid fa-layer-group"></i> <strong>Floor:</strong> <?php echo htmlspecialchars($room['floor']); ?></p>
                    <p><i class="fa-solid fa-users"></i> <strong>Capacity:</strong> <?php echo (int)$room['capacity']; ?> student(s)</p>
                    <?php if (!empty($room['equipment'])): ?>
                        <p><i class="fa-solid fa-screwdriver-wrench"></i> <strong>Equipment:</strong> <?php echo htmlspecialchars($room['equipment']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($room['description'])): ?>
                        <p class="room-description"><?php echo htmlspecialchars($room['description']); ?></p>
                    <?php endif; ?>

                    <?php if (!$isAvailable && !empty($room['occupant_name'])): ?>
                        <p class="room-occupant <?php echo $isMine ? 'room-occupant-you' : ''; ?>">
                            <i class="fa-solid fa-circle-user"></i>
                            <?php echo $isMine ? 'Reserved by you' : 'In use by ' . htmlspecialchars($room['occupant_name']); ?>
                        </p>
                    <?php endif; ?>
                </div>

                <div class="room-card-footer">
                    <?php if ($isAvailable): ?>
                        <form method="POST" action="reserve_room.php">
                            <input type="hidden" name="room_id" value="<?php echo (int)$room['id']; ?>">
                            <input type="hidden" name="action" value="reserve">
                            <input type="hidden" name="status_filter" value="<?php echo htmlspecialchars($statusFilter); ?>">
                            <button type="submit" class="btn btn-success btn-block">Reserve Room</button>
                        </form>
                    <?php elseif ($isMine): ?>
                        <form method="POST" action="reserve_room.php">
                            <input type="hidden" name="room_id" value="<?php echo (int)$room['id']; ?>">
                            <input type="hidden" name="action" value="release">
                            <input type="hidden" name="status_filter" value="<?php echo htmlspecialchars($statusFilter); ?>">
                            <button type="submit" class="btn btn-warning btn-block">Release Room</button>
                        </form>
                    <?php else: ?>
                        <button class="btn btn-disabled btn-block" disabled>Not Available</button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>
