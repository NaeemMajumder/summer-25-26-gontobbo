<?php
// ================================================================
// MODEL: TRIP LOG MANAGEMENT
// Architecture: MVC, Procedural PHP, Prepared Statements
// SQL Schema: trip_logs (log_id, trip_id, driver_id, status, start_time, end_time, note)
// ================================================================

/**
 * ড্রাইভারের জন্য নতুন ট্রিপ লগ তৈরি করা (Start Trip)
 */
function create_trip_log($conn, $trip_id, $driver_id, $start_time, $note = null)
{
    $sql = "INSERT INTO trip_logs (trip_id, driver_id, status, start_time, note) 
            VALUES (?, ?, 'started', ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, "iiss", $trip_id, $driver_id, $start_time, $note);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $result;
}

/**
 * ট্রিপ সম্পন্ন হলে লগ আপডেট করা (End/Complete Trip)
 */
function complete_trip_log($conn, $log_id, $end_time, $note = null)
{
    $sql = "UPDATE trip_logs 
            SET status = 'completed', end_time = ?, note = COALESCE(?, note) 
            WHERE log_id = ?";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, "ssi", $end_time, $note, $log_id);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $result;
}

/**
 * নির্দিষ্ট ড্রাইভারের সকল ট্রিপ লগের তালিকা নিয়ে আসা
 */
function get_trip_logs_by_driver($conn, $driver_id)
{
    $sql = "SELECT tl.*, t.trip_date, t.departure_time, r.origin, r.destination 
            FROM trip_logs tl
            JOIN trips t ON tl.trip_id = t.trip_id
            LEFT JOIN routes r ON t.route_id = r.route_id
            WHERE tl.driver_id = ? 
            ORDER BY tl.log_id DESC";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return [];
    }

    mysqli_stmt_bind_param($stmt, "i", $driver_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $logs = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);

    return $logs;
}

/**
 * ট্রিপ লগ ডিলেট করা
 */
function delete_trip_log($conn, $log_id, $driver_id)
{
    $sql = "DELETE FROM trip_logs WHERE log_id = ? AND driver_id = ?";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, "ii", $log_id, $driver_id);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $result;
}
?>