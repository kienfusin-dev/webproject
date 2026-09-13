<?php
/**
 * Shared top navigation bar for logged-in pages.
 * Expects session to already be started by auth_check.php
 * and $_SESSION['full_name'] to be set at login.
 */
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar">
    <div class="navbar-container">
        <a href="dashboard.php" class="navbar-brand">
            <i class="fa-solid fa-chalkboard"></i> ClassTrack
        </a>

        <button class="navbar-toggle" id="navbarToggle" aria-label="Toggle navigation">
            <i class="fa-solid fa-bars"></i>
        </button>

        <ul class="navbar-links" id="navbarLinks">
            <li>
                <a href="dashboard.php" class="<?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-gauge"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="rooms.php" class="<?php echo $currentPage === 'rooms.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-chalkboard-user"></i> Classrooms
                </a>
            </li>
            <li>
                <a href="schedule.php" class="<?php echo $currentPage === 'schedule.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-calendar-days"></i> Schedule
                </a>
            </li>
            <li>
                <a href="profile.php" class="<?php echo $currentPage === 'profile.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-user"></i> Profile
                </a>
            </li>
            <li>
                <a href="logout.php" class="logout-link">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </a>
            </li>
        </ul>
    </div>
</nav>
