<?php
// ================================================================
// MODEL: DRIVER AVAILABILITY MANAGEMENT
// Architecture: MVC, Procedural PHP, Prepared Statements
// SQL Schema: driver_availability (availability_id, driver_id, date, status, note)
// ================================================================

/**
 * ড্রাইভারের অ্যাভেইল্যাবিলিটি/ছুটি এন্ট্রি দেওয়া
 */
function create_driver_availability($conn, $driver_id, $date, $status, $note = null)
{
    $sql = "INSERT INTO driver_availability (driver_id, date, status, note) 
            VALUES (?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, "isss", $driver_id, $date, $status, $note);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $result;
}

/**
 * ড্রাইভারের নিজস্ব অ্যাভেইল্যাবিলিটি তালিকা নিয়ে আসা
 */
function get_availability_by_driver($conn, $driver_id)
{
    $sql = "SELECT * FROM driver_availability 
            WHERE driver_id = ? 
            ORDER BY date DESC";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return [];
    }

    mysqli_stmt_bind_param($stmt, "i", $driver_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $list = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);

    return $list;
}

/**
 * অ্যাভেইল্যাবিলিটি স্ট্যাটাস ও নোট আপডেট করা
 */
function update_driver_availability($conn, $availability_id, $driver_id, $status, $note = null)
{
    $sql = "UPDATE driver_availability 
            SET status = ?, note = ? 
            WHERE availability_id = ? AND driver_id = ?";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, "ssii", $status, $note, $availability_id, $driver_id);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $result;
}

/**
 * অ্যাভেইল্যাবিলিটি ডিলেট করা
 */
function delete_driver_availability($conn, $availability_id, $driver_id)
{
    $sql = "DELETE FROM driver_availability WHERE availability_id = ? AND driver_id = ?";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, "ii", $availability_id, $driver_id);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $result;
}
?>