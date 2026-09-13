<?php
/**
 * Database Connection
 * -----------------------------------------------------------
 * Uses MySQLi to connect to the MySQL database created by
 * database/classroom_availability.sql
 *
 * Default XAMPP settings are used below:
 *   host     = localhost
 *   username = root
 *   password = "" (empty by default in XAMPP)
 *
 * If you set a MySQL root password in XAMPP, update DB_PASS below.
 * -----------------------------------------------------------
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'classroom_availability_db');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

// Ensure proper character encoding
$conn->set_charset('utf8mb4');
