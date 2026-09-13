<?php
/**
 * Auth Guard
 * -----------------------------------------------------------
 * Include this file at the very top of any page that should
 * only be visible to logged-in users. It must run before any
 * HTML output because it may redirect the browser.
 * -----------------------------------------------------------
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
