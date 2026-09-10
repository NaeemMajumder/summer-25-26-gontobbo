<?php
/*
App Constants
*/
define('APP_NAME', 'GONTOBBO');
define('BASE_URL', ''); // set to a sub-folder path if the app is not hosted at the domain root, e.g. '/bus_management'
define('SERVICE_TRIP_LIMIT', 50);   // trips before a bus is due for service (Maintenance Manager module)
define('PARTS_LOW_STOCK', 5);       // spare-part stock warning threshold (Maintenance Manager module)
define('SEATS_PER_BOOKING', 4);     // max seats a passenger can book in a single booking
define('CURRENCY', '৳');
define('SESSION_TIMEOUT', 1800);    // idle auto sign-out, in seconds (30 minutes)

/*
Session (hardened cookie: httponly + samesite, per §11 Security)
*/
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
/*
Database Connection (MySQLi)
*/
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'bus_management_system';
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) {
    die('Database connection failed: ' . mysqli_connect_error());
}

mysqli_set_charset($conn, 'utf8mb4');
