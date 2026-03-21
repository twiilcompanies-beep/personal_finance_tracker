<?php
// ============================================================
// includes/db.php — MySQL database connection (mysqli)
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'finpulse_db');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    // Show a friendly error and stop execution
    die('<div style="font-family:sans-serif;padding:2rem;color:#c0392b;">
            <h2>Database Connection Failed</h2>
            <p>' . htmlspecialchars($conn->connect_error) . '</p>
            <p>Please ensure XAMPP (MySQL) is running and the database has been imported.</p>
         </div>');
}

$conn->set_charset('utf8mb4');
