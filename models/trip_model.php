<?php
// ================================================================
// MODEL: TRIP MANAGEMENT (Driver Specific Functions)
// Architecture: MVC, Procedural PHP, Prepared Statements
// SQL Schema: trips, routes, buses
// ================================================================

/**
 * ড্রাইভারের নির্দিষ্ট তারিখের বা আজকের ট্রিপ অ্যাসাইনমেন্ট নিয়ে আসা
 */
function get_assigned_trips_by_driver($conn, $driver_id, $date = null)
{
    if ($date) {
        $sql = "SELECT t.*, r.origin, r.destination, b.bus_number, b.name AS bus_name 
                FROM trips t
                LEFT JOIN routes r ON t.route_id = r.route_id
                LEFT JOIN buses b ON t.bus_id = b.bus_id
                WHERE t.driver_id = ? AND t.trip_date = ?
                ORDER BY t.departure_time ASC";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "is", $driver_id, $date);
    } else {
        $sql = "SELECT t.*, r.origin, r.destination, b.bus_number, b.name AS bus_name 
                FROM trips t
                LEFT JOIN routes r ON t.route_id = r.route_id
                LEFT JOIN buses b ON t.bus_id = b.bus_id
                WHERE t.driver_id = ?
                ORDER BY t.trip_date DESC, t.departure_time ASC";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $driver_id);
    }

    if (!$stmt) {
        return [];
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $trips = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);

    return $trips;
}
?>