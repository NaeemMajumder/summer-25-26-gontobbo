<?php
// ==========================================
// MODEL: bookings (Admin Unique Feature: Revenue Report)
// Computes SUM(total_amount) for 'paid' bookings with date filters for charting
// ==========================================

function get_total_revenue($conn, $start_date = null, $end_date = null) {
    $sql = "SELECT SUM(total_amount) AS total_revenue 
            FROM bookings 
            WHERE payment_status = 'paid' AND booking_status = 'confirmed'";
            
    if ($start_date && $end_date) {
        $sql .= " AND DATE(created_at) BETWEEN ? AND ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'ss', $start_date, $end_date);
    } else {
        $stmt = mysqli_prepare($conn, $sql);
    }
    
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    
    // Return 0.00 if there is no revenue to prevent null outputs in the view
    return (float)($row['total_revenue'] ?? 0.00);
}

function get_revenue_chart_data($conn, $start_date = null, $end_date = null) {
    $sql = "SELECT DATE(created_at) AS revenue_date, SUM(total_amount) AS daily_revenue 
            FROM bookings 
            WHERE payment_status = 'paid' AND booking_status = 'confirmed'";
            
    if ($start_date && $end_date) {
        $sql .= " AND DATE(created_at) BETWEEN ? AND ?";
    }
    
    $sql .= " GROUP BY DATE(created_at) ORDER BY revenue_date ASC";
    
    if ($start_date && $end_date) {
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'ss', $start_date, $end_date);
    } else {
        $stmt = mysqli_prepare($conn, $sql);
    }
    
    mysqli_stmt_execute($stmt);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    
    return $rows;
}

// ==========================================
// MODEL: bookings (Admin Unique Feature: Revenue Report finished)
// Computes SUM(total_amount) for 'paid' bookings with date filters for charting
// ==========================================


/* ---------- Passenger dashboard helpers ---------- */

function get_recent_bookings($user_id, $limit = 5)
{
    global $conn;
    $sql = "SELECT b.booking_id, b.seats_booked, b.total_amount, b.booking_status,
                   b.payment_status, b.created_at,
                   t.trip_date, t.departure_time,
                   r.origin, r.destination,
                   bs.name AS bus_name
            FROM bookings b
            JOIN trips t   ON b.trip_id = t.trip_id
            JOIN routes r  ON t.route_id = r.route_id
            JOIN buses bs  ON t.bus_id = bs.bus_id
            WHERE b.user_id = ?
            ORDER BY b.created_at DESC
            LIMIT ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $limit);
    mysqli_stmt_execute($stmt);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $rows;
}

function get_booking_summary($user_id)
{
    global $conn;
    $sql = "SELECT
                COUNT(*) AS total,
                SUM(booking_status = 'confirmed') AS confirmed,
                SUM(payment_status = 'pending')   AS pending,
                SUM(booking_status = 'cancelled') AS cancelled
            FROM bookings
            WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    return [
        'total'     => (int) ($row['total'] ?? 0),
        'confirmed' => (int) ($row['confirmed'] ?? 0),
        'pending'   => (int) ($row['pending'] ?? 0),
        'cancelled' => (int) ($row['cancelled'] ?? 0),
    ];
}
