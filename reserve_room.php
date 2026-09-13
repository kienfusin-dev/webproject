<?php
/**
 * Handles the "Reserve Room" / "Release Room" buttons on rooms.php.
 * Expects a POST with: room_id, action (reserve|release), status_filter (optional).
 */
require_once 'includes/auth_check.php';
require_once 'config/db.php';

$currentUserId = $_SESSION['user_id'];

$roomId       = isset($_POST['room_id']) ? (int)$_POST['room_id'] : 0;
$action       = $_POST['action'] ?? '';
$statusFilter = $_POST['status_filter'] ?? 'all';

// Build the redirect-back URL, preserving whichever filter tab the instructor was on
$redirectUrl = 'rooms.php';
if (in_array($statusFilter, ['available', 'unavailable'], true)) {
    $redirectUrl .= '?status=' . urlencode($statusFilter);
}
$separator = (strpos($redirectUrl, '?') !== false) ? '&' : '?';

if ($roomId <= 0 || !in_array($action, ['reserve', 'release'], true)) {
    header('Location: ' . $redirectUrl . $separator . 'error=' . urlencode('Invalid request.'));
    exit;
}

// Look up the room first so we can check its current state
$stmt = $conn->prepare('SELECT id, status, occupied_by FROM classrooms WHERE id = ?');
$stmt->bind_param('i', $roomId);
$stmt->execute();
$room = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$room) {
    header('Location: ' . $redirectUrl . $separator . 'error=' . urlencode('Room not found.'));
    exit;
}

if ($action === 'reserve') {
    // Only allow reserving a room that is currently available
    if ($room['status'] !== 'available') {
        header('Location: ' . $redirectUrl . $separator . 'error=' . urlencode('That room is no longer available.'));
        exit;
    }

    $stmt = $conn->prepare("UPDATE classrooms SET status = 'unavailable', occupied_by = ? WHERE id = ? AND status = 'available'");
    $stmt->bind_param('ii', $currentUserId, $roomId);
    $stmt->execute();
    $stmt->close();

    header('Location: ' . $redirectUrl . $separator . 'reserved=1');
    exit;
}

if ($action === 'release') {
    // Only the instructor who reserved the room can release it
    if ((int)$room['occupied_by'] !== $currentUserId) {
        header('Location: ' . $redirectUrl . $separator . 'error=' . urlencode('You can only release a room you reserved yourself.'));
        exit;
    }

    $stmt = $conn->prepare("UPDATE classrooms SET status = 'available', occupied_by = NULL WHERE id = ? AND occupied_by = ?");
    $stmt->bind_param('ii', $roomId, $currentUserId);
    $stmt->execute();
    $stmt->close();

    header('Location: ' . $redirectUrl . $separator . 'released=1');
    exit;
}
