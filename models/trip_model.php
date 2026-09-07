<?php
// ==========================================
// MODEL: trips (Admin CRUD module)
// Handles the core schedule joining buses, routes, and drivers
// ==========================================

function get_trips($conn) {
    $sql = "SELECT t.trip_id, t.trip_date, t.departure_time, t.arrival_time, t.fare, t.available_seats, t.status,
                   b.bus_number, b.name AS bus_name, 
                   r.origin, r.destination, 
                   u.name AS driver_name
            FROM trips t
            JOIN buses b ON t.bus_id = b.bus_id
            JOIN routes r ON t.route_id = r.route_id
            JOIN users u ON t.driver_id = u.user_id
            ORDER BY t.trip_date DESC, t.departure_time ASC";
    $res = mysqli_query($conn, $sql);
    return mysqli_fetch_all($res, MYSQLI_ASSOC);
}

function get_trip($conn, $id) {
    $sql = "SELECT trip_id, bus_id, route_id, driver_id, trip_date, departure_time, arrival_time, fare, available_seats, status 
            FROM trips WHERE trip_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return $row;
}

function search_trips_admin($conn, $term) {
    $like = '%' . $term . '%';
    $sql = "SELECT t.trip_id, t.trip_date, t.departure_time, t.arrival_time, t.fare, t.available_seats, t.status,
                   b.bus_number, b.name AS bus_name, 
                   r.origin, r.destination, 
                   u.name AS driver_name
            FROM trips t
            JOIN buses b ON t.bus_id = b.bus_id
            JOIN routes r ON t.route_id = r.route_id
            JOIN users u ON t.driver_id = u.user_id
            WHERE b.bus_number LIKE ? OR r.origin LIKE ? OR r.destination LIKE ? OR u.name LIKE ? OR t.status LIKE ?
            ORDER BY t.trip_date DESC, t.departure_time ASC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'sssss', $like, $like, $like, $like, $like);
    mysqli_stmt_execute($stmt);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $rows;
}

function add_trip($conn, $bus_id, $route_id, $driver_id, $trip_date, $departure_time, $arrival_time, $fare, $available_seats, $status) {
    $sql = "INSERT INTO trips (bus_id, route_id, driver_id, trip_date, departure_time, arrival_time, fare, available_seats, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    // Types: int, int, int, string(date), string(time), string(time), double(decimal), int, string(enum) -> 'iiisssdis'
    mysqli_stmt_bind_param($stmt, 'iiisssdis', $bus_id, $route_id, $driver_id, $trip_date, $departure_time, $arrival_time, $fare, $available_seats, $status);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

function update_trip($conn, $id, $bus_id, $route_id, $driver_id, $trip_date, $departure_time, $arrival_time, $fare, $available_seats, $status) {
    $sql = "UPDATE trips 
            SET bus_id = ?, route_id = ?, driver_id = ?, trip_date = ?, departure_time = ?, arrival_time = ?, fare = ?, available_seats = ?, status = ? 
            WHERE trip_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    // Types: int, int, int, string, string, string, double, int, string, int -> 'iiisssdisi'
    mysqli_stmt_bind_param($stmt, 'iiisssdisi', $bus_id, $route_id, $driver_id, $trip_date, $departure_time, $arrival_time, $fare, $available_seats, $status, $id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

function delete_trip($conn, $id) {
    $stmt = mysqli_prepare($conn, "DELETE FROM trips WHERE trip_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}