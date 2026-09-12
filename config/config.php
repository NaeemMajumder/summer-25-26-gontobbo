<?php
// config/config.php

/* ---------- App constants ---------- */
define('APP_NAME', 'Gontobbo');
define('SEATS_PER_BOOKING', 4);
define('SESSION_TIMEOUT', 1800);   // 30 min idle logout
define('CURRENCY', '৳');
define('FINE_PER_DAY', 0);         // future use

/* ---------- Session ---------- */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ---------- Database connection ---------- */
$host   = "localhost";
$user   = "root";
$pass   = "";
$dbname = "bus_management_system";

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die('Database connection failed. Details: ' . mysqli_connect_error());
}

mysqli_set_charset($conn, 'utf8mb4');