<?php
// ---- Edit these to match your MySQL / MariaDB server ----
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'student_management');

define('APP_NAME', 'Student Management System');

// Make mysqli behave the same on every PHP version (no exceptions, we check return values).
mysqli_report(MYSQLI_REPORT_OFF);
