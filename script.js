/**
 * ClassTrack — small front-end behaviors
 * Handles the mobile navbar toggle.
 */
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('navbarToggle');
    const navLinks = document.getElementById('navbarLinks');

    if (toggleBtn && navLinks) {
        toggleBtn.addEventListener('click', function() {
            navLinks.classList.toggle('open');
        });
    }
});