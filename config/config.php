<?php
// config/db.php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "bus_management_system";

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die('Database connection failed. Details: '
        . mysqli_connect_error());
}
mysqli_set_charset($conn, 'utf8mb4');

?>