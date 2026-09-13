<?php
// models/report_model.php
// Revenue is COMPUTED (no revenue table) — only counts paid bookings.


// Top summary numbers
function get_revenue_summary($conn)
{
    $sql = "SELECT
                COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END), 0) AS total_revenue,
                COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END), 0) AS paid_count,
                COALESCE(SUM(CASE WHEN payment_status = 'pending' THEN 1 ELSE 0 END), 0) AS pending_count,
                COALESCE(SUM(CASE WHEN payment_status = 'pending' THEN total_amount ELSE 0 END), 0) AS pending_amount,
                COUNT(*) AS total_bookings
            FROM bookings";
    $res = mysqli_query($conn, $sql);
    return mysqli_fetch_assoc($res);
}


// Revenue grouped by route (paid only) — for the bar chart + table
function get_revenue_by_route($conn)
{
    $sql = "SELECT r.origin, r.destination,
                   COALESCE(SUM(b.total_amount), 0) AS revenue,
                   COUNT(b.booking_id) AS bookings
            FROM bookings b
            JOIN trips t  ON b.trip_id = t.trip_id
            JOIN routes r ON t.route_id = r.route_id
            WHERE b.payment_status = 'paid'
            GROUP BY r.route_id
            ORDER BY revenue DESC";
    $res = mysqli_query($conn, $sql);
    return mysqli_fetch_all($res, MYSQLI_ASSOC);
}