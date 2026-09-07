<?php
// ================================================================
// MODEL: buses (Service Alerts)
// ================================================================

function get_buses_due_for_service($conn)
{
    $sql = "SELECT bus_id, bus_number, name, type, total_seats, status, service_trip_limit, trips_since_service 
            FROM buses 
            WHERE trips_since_service >= service_trip_limit OR status = 'maintenance' 
            ORDER BY trips_since_service DESC";
    $res = mysqli_query($conn, $sql);
    return mysqli_fetch_all($res, MYSQLI_ASSOC);
}
?>