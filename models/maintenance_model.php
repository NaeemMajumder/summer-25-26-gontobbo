<?php
// ================================================================
// MODEL: maintenance_requests
// ================================================================

function create_maintenance_request($conn, $bus_id, $manager_id, $issue)
{
    $sql = "INSERT INTO maintenance_requests (bus_id, manager_id, issue, status, created_at) 
            VALUES (?, ?, ?, 'pending', NOW())";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'iis', $bus_id, $manager_id, $issue);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

function get_all_maintenance_requests($conn)
{
    $sql = "SELECT mr.*, b.bus_number, b.name AS bus_name, u.name AS manager_name 
            FROM maintenance_requests mr
            INNER JOIN buses b ON mr.bus_id = b.bus_id
            LEFT JOIN users u ON mr.manager_id = u.user_id
            ORDER BY mr.request_id DESC";
    $res = mysqli_query($conn, $sql);
    return mysqli_fetch_all($res, MYSQLI_ASSOC);
}

function get_maintenance_request_by_id($conn, $request_id)
{
    $sql = "SELECT mr.*, b.bus_number, b.name AS bus_name, u.name AS manager_name 
            FROM maintenance_requests mr
            INNER JOIN buses b ON mr.bus_id = b.bus_id
            LEFT JOIN users u ON mr.manager_id = u.user_id
            WHERE mr.request_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $request_id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return $row;
}

function update_maintenance_request_status($conn, $request_id, $status)
{
    $sql = "UPDATE maintenance_requests SET status = ? WHERE request_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'si', $status, $request_id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

function cancel_maintenance_request($conn, $request_id)
{
    $sql = "UPDATE maintenance_requests SET status = 'cancelled' WHERE request_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $request_id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}
?>