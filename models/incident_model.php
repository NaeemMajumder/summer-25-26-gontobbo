<?php
// ================================================================
// MODEL: INCIDENT REPORTING MANAGEMENT
// Architecture: MVC, Procedural PHP, Prepared Statements
// SQL Schema: incident_reports (incident_id, driver_id, trip_id, bus_id, type, description, status, created_at)
// ================================================================

/**
 * ইনসিডেন্ট/ড্যামেজ রিপোর্ট সাবমিট করা
 */
function create_incident_report($conn, $driver_id, $trip_id, $bus_id, $type, $description)
{
    $sql = "INSERT INTO incident_reports (driver_id, trip_id, bus_id, type, description, status, created_at) 
            VALUES (?, ?, ?, ?, ?, 'open', NOW())";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, "iiiss", $driver_id, $trip_id, $bus_id, $type, $description);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $result;
}

/**
 * ড্রাইভারের সাবমিট করা ইনসিডেন্ট রিপোর্টসমূহ দেখা
 */
function get_incidents_by_driver($conn, $driver_id)
{
    $sql = "SELECT ir.*, b.bus_number, b.name AS bus_name 
            FROM incident_reports ir
            LEFT JOIN buses b ON ir.bus_id = b.bus_id
            WHERE ir.driver_id = ? 
            ORDER BY ir.incident_id DESC";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return [];
    }

    mysqli_stmt_bind_param($stmt, "i", $driver_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $incidents = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);

    return $incidents;
}

/**
 * ইনসিডেন্ট রিপোর্ট আপডেট করা
 */
function update_incident_report($conn, $incident_id, $driver_id, $description)
{
    $sql = "UPDATE incident_reports 
            SET description = ? 
            WHERE incident_id = ? AND driver_id = ? AND status = 'open'";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, "sii", $description, $incident_id, $driver_id);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $result;
}

/**
 * ইনসিডেন্ট রিপোর্ট মুছে ফেলা
 */
function delete_incident_report($conn, $incident_id, $driver_id)
{
    $sql = "DELETE FROM incident_reports WHERE incident_id = ? AND driver_id = ?";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, "ii", $incident_id, $driver_id);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $result;
}
?>