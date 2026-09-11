<?php
/*
models/booking_model.php
*/
function create_booking(
    $user_id,
    $trip_id,
    $seats,
    $boarding_stop_id,
    $dropping_stop_id,
    $wheelchair,
    $promo_id,
    $total_amount,
    $payment_method
) {
    global $conn;

    $sql = "
        INSERT INTO bookings
            (user_id, trip_id, seats_booked, boarding_stop_id, dropping_stop_id,
             wheelchair, promo_id, total_amount, payment_method, payment_status, booking_status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'confirmed')
    ";

    $stmt = mysqli_prepare($conn, $sql);

    if (empty($promo_id)) {
        $promo_id = null;
    }

    mysqli_stmt_bind_param(
        $stmt,
        "iiiiiiids",
        $user_id,
        $trip_id,
        $seats,
        $boarding_stop_id,
        $dropping_stop_id,
        $wheelchair,
        $promo_id,
        $total_amount,
        $payment_method
    );

    if (mysqli_stmt_execute($stmt)) {
        return mysqli_insert_id($conn);
    }

    return false;
}


/*
| Read
*/

// $user_id is required unless $forOwnerCheck is explicitly disabled — this is the
// fix for the URL-tampering bug (a passenger must never load someone else's booking).
function get_booking_by_id($booking_id, $user_id)
{
    global $conn;

    $sql = "
        SELECT
            bk.*,
            b.bus_id, b.name AS bus_name, b.bus_number, b.type AS bus_type,
            t.trip_date, t.departure_time, t.arrival_time, t.fare, t.status AS trip_status,
            r.origin, r.destination,
            bs.stop_name AS boarding_name,
            ds.stop_name AS dropping_name,
            p.code AS promo_code
        FROM bookings bk
        INNER JOIN trips t   ON bk.trip_id = t.trip_id
        INNER JOIN buses b   ON t.bus_id = b.bus_id
        INNER JOIN routes r  ON t.route_id = r.route_id
        LEFT JOIN route_stops bs ON bk.boarding_stop_id = bs.stop_id
        LEFT JOIN route_stops ds ON bk.dropping_stop_id = ds.stop_id
        LEFT JOIN promo_codes p  ON bk.promo_id = p.promo_id
        WHERE bk.booking_id = ? AND bk.user_id = ?
        LIMIT 1
    ";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $booking_id, $user_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result) ?: null;
}


function get_user_bookings($user_id, $search = '', $status = '')
{
    global $conn;

    $sql = "
        SELECT
            bk.booking_id, bk.seats_booked, bk.total_amount, bk.payment_status,
            bk.payment_method, bk.booking_status, bk.wheelchair, bk.created_at,
            b.name AS bus_name, b.type AS bus_type,
            t.trip_id, t.trip_date, t.departure_time, t.status AS trip_status,
            r.origin, r.destination
        FROM bookings bk
        INNER JOIN trips t  ON bk.trip_id = t.trip_id
        INNER JOIN buses b  ON t.bus_id = b.bus_id
        INNER JOIN routes r ON t.route_id = r.route_id
        WHERE bk.user_id = ?
    ";

    $types = "i";
    $params = [$user_id];

    if (!is_blank($search)) {
        $sql .= " AND (r.origin LIKE ? OR r.destination LIKE ? OR b.name LIKE ?) ";
        $like = '%' . $search . '%';
        $types .= "sss";
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    if ($status === 'confirmed' || $status === 'cancelled') {
        $sql .= " AND bk.booking_status = ? ";
        $types .= "s";
        $params[] = $status;
    }

    $sql .= " ORDER BY bk.created_at DESC ";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $tickets = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $tickets[] = $row;
    }

    return $tickets;
}


function get_recent_bookings($user_id, $limit = 5)
{
    global $conn;

    $sql = "
        SELECT bk.booking_status, bk.created_at, b.name AS bus_name, r.origin, r.destination
        FROM bookings bk
        INNER JOIN trips t  ON bk.trip_id = t.trip_id
        INNER JOIN buses b  ON t.bus_id = b.bus_id
        INNER JOIN routes r ON t.route_id = r.route_id
        WHERE bk.user_id = ?
        ORDER BY bk.created_at DESC
        LIMIT ?
    ";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $limit);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }

    return $data;
}


function get_booking_summary($user_id)
{
    global $conn;

    $sql = "
        SELECT
            COUNT(*) AS total_trips,
            SUM(CASE WHEN booking_status = 'confirmed' THEN 1 ELSE 0 END) AS confirmed_bookings,
            SUM(CASE WHEN payment_status = 'pending' AND booking_status = 'confirmed' THEN 1 ELSE 0 END) AS pending_payments,
            SUM(CASE WHEN booking_status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_bookings
        FROM bookings
        WHERE user_id = ?
    ";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    return mysqli_fetch_assoc($result) ?: [
        'total_trips' => 0,
        'confirmed_bookings' => 0,
        'pending_payments' => 0,
        'cancelled_bookings' => 0,
    ];
}


/*
| Cancel
*/

function cancel_booking($booking_id, $user_id)
{
    global $conn;

    mysqli_begin_transaction($conn);

    try {

        $sql = "
            SELECT bk.trip_id, bk.seats_booked
            FROM bookings bk
            INNER JOIN trips t ON bk.trip_id = t.trip_id
            WHERE bk.booking_id = ? AND bk.user_id = ? AND bk.booking_status = 'confirmed' AND t.status = 'scheduled'
            FOR UPDATE
        ";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $booking_id, $user_id);
        mysqli_stmt_execute($stmt);
        $booking = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if (!$booking) {
            mysqli_rollback($conn);
            return false;
        }

        $upd = mysqli_prepare($conn, "UPDATE bookings SET booking_status = 'cancelled' WHERE booking_id = ?");
        mysqli_stmt_bind_param($upd, "i", $booking_id);
        mysqli_stmt_execute($upd);

        increment_trip_seats($booking['trip_id'], $booking['seats_booked']);

        mysqli_commit($conn);
        return true;

    } catch (Throwable $e) {
        mysqli_rollback($conn);
        return false;
    }
}
/*
| Modify (seats / boarding / dropping / wheelchair)
*/
function modify_booking($booking_id, $user_id, $boarding_stop_id, $dropping_stop_id, $seats, $wheelchair)
{
    global $conn;

    mysqli_begin_transaction($conn);

    try {

        $sql = "
            SELECT bk.*, t.fare, t.available_seats, t.trip_id
            FROM bookings bk
            INNER JOIN trips t ON bk.trip_id = t.trip_id
            WHERE bk.booking_id = ? AND bk.user_id = ? AND bk.booking_status = 'confirmed' AND t.status = 'scheduled'
            FOR UPDATE
        ";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $booking_id, $user_id);
        mysqli_stmt_execute($stmt);
        $booking = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if (!$booking) {
            mysqli_rollback($conn);
            return ['ok' => false, 'message' => 'Booking not found.'];
        }

        $seatDelta = $seats - (int) $booking['seats_booked'];
        // If asking for more seats, make sure the trip actually has that many spare
        if ($seatDelta > 0 && $booking['available_seats'] < $seatDelta) {
            mysqli_rollback($conn);
            return ['ok' => false, 'message' => 'Not enough seats available for that change.'];
        }
        if ($seatDelta !== 0) {
            if ($seatDelta > 0) {
                decrement_trip_seats($booking['trip_id'], $seatDelta);
            } else {
                increment_trip_seats($booking['trip_id'], abs($seatDelta));
            }
        }
        $promo = !empty($booking['promo_id']) ? get_promo_by_id($booking['promo_id']) : null;
        $totals = calculate_total($booking['fare'], $seats, $promo);

        $upd = mysqli_prepare($conn, "
            UPDATE bookings
            SET seats_booked = ?, boarding_stop_id = ?, dropping_stop_id = ?, wheelchair = ?, total_amount = ?
            WHERE booking_id = ?
        ");
        mysqli_stmt_bind_param(
            $upd,
            "iiiidi",
            $seats,
            $boarding_stop_id,
            $dropping_stop_id,
            $wheelchair,
            $totals['total'],
            $booking_id
        );
        mysqli_stmt_execute($upd);

        mysqli_commit($conn);
        return ['ok' => true];

    } catch (Throwable $e) {
        mysqli_rollback($conn);
        return ['ok' => false, 'message' => 'Something went wrong. Please try again.'];
    }
}
/*
| Payment (Wheelchair Special Request lives on the booking row itself —
| set at creation/modify time above. This handles COD / Counter.)
*/
function update_payment($booking_id, $user_id, $payment_method = null, $markPaid = false)
{
    global $conn;
    $booking = get_booking_by_id($booking_id, $user_id);
    if (!$booking || $booking['booking_status'] !== 'confirmed') {
        return false;
    }
    $newMethod = $payment_method ?: $booking['payment_method'];
    // A passenger may only self-mark a COD (cash on boarding) booking as paid.
    // Counter payments are confirmed by Admin once the cash is received there.
    $newStatus = $booking['payment_status'];
    if ($markPaid && $newMethod === 'COD') {
        $newStatus = 'paid';
    }
    $sql = "UPDATE bookings SET payment_method = ?, payment_status = ? WHERE booking_id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssii", $newMethod, $newStatus, $booking_id, $user_id);

    return mysqli_stmt_execute($stmt);
}
/*
| Reviews eligibility
*/
function get_completed_bookings_for_review($user_id)
{
    global $conn;
    $sql = "
        SELECT bk.booking_id, bk.trip_id, b.bus_id, b.name AS bus_name, t.trip_date, r.origin, r.destination
        FROM bookings bk
        INNER JOIN trips t  ON bk.trip_id = t.trip_id
        INNER JOIN buses b  ON t.bus_id = b.bus_id
        INNER JOIN routes r ON t.route_id = r.route_id
        WHERE bk.user_id = ?
          AND bk.booking_status = 'confirmed'
          AND t.status = 'completed'
          AND bk.booking_id NOT IN (
              SELECT booking_id FROM reviews WHERE booking_id IS NOT NULL
          )
        ORDER BY t.trip_date DESC
    ";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}
