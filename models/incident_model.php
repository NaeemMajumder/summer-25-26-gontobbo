<?php
// ==========================================
// MODEL: incident_reports (Admin Unique Feature: Damage Report)
// Admin views 'damage' type reports and marks them as resolved.
// ==========================================

function get_damage_reports($conn)
{
    // LEFT JOIN is used for buses and trips since they are nullable in the schema
    $sql = "SELECT i.incident_id, i.description, i.status, i.created_at,
                   u.name AS driver_name, 
                   b.bus_number, 
                   t.trip_date
            FROM incident_reports i
            JOIN users u ON i.driver_id = u.user_id
            LEFT JOIN buses b ON i.bus_id = b.bus_id
            LEFT JOIN trips t ON i.trip_id = t.trip_id
            WHERE i.type = 'damage'
            ORDER BY FIELD(i.status, 'open', 'reviewing', 'resolved'), i.incident_id DESC";

    $res = mysqli_query($conn, $sql);
    return mysqli_fetch_all($res, MYSQLI_ASSOC);
}

function search_damage_reports($conn, $term)
{
    $like = '%' . $term . '%';
    $sql = "SELECT i.incident_id, i.description, i.status, i.created_at,
                   u.name AS driver_name, 
                   b.bus_number, 
                   t.trip_date
            FROM incident_reports i
            JOIN users u ON i.driver_id = u.user_id
            LEFT JOIN buses b ON i.bus_id = b.bus_id
            LEFT JOIN trips t ON i.trip_id = t.trip_id
            WHERE i.type = 'damage' 
              AND (i.description LIKE ? OR u.name LIKE ? OR b.bus_number LIKE ?)
            ORDER BY FIELD(i.status, 'open', 'reviewing', 'resolved'), i.incident_id DESC";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'sss', $like, $like, $like);
    mysqli_stmt_execute($stmt);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);

    return $rows;
}

function get_incident($conn, $id)
{
    $sql = "SELECT incident_id, driver_id, trip_id, bus_id, type, description, status, created_at 
            FROM incident_reports WHERE incident_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return $row;
}

function resolve_damage_report($conn, $id)
{
    // Ensures the admin can only resolve reports that are specifically marked as 'damage'
    $sql = "UPDATE incident_reports 
            SET status = 'resolved' 
            WHERE incident_id = ? AND type = 'damage' AND status != 'resolved'";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    $ok = mysqli_stmt_execute($stmt);
    $changed = mysqli_stmt_affected_rows($stmt) > 0;
    mysqli_stmt_close($stmt);

    return $ok && $changed;

}
// ==========================================
// MODEL: incident_reports (Admin Unique Feature: Damage Report finished)
// Admin views 'damage' type reports and marks them as resolved.
// ==========================================