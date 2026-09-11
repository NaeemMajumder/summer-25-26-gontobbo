<?php
// ================================================================
// MODEL: service_history & service_parts
// ================================================================

function create_service_record($conn, $bus_id, $manager_id, $service_date, $work_done, $cost, $parts_used = [])
{
    mysqli_begin_transaction($conn);

    try {
        $sql = "INSERT INTO service_history (bus_id, manager_id, service_date, work_done, cost, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'iissd', $bus_id, $manager_id, $service_date, $work_done, $cost);
        mysqli_stmt_execute($stmt);
        $service_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

        if (!empty($parts_used) && is_array($parts_used)) {
            $part_sql = "INSERT INTO service_parts (service_id, part_id, quantity_used) VALUES (?, ?, ?)";
            $part_stmt = mysqli_prepare($conn, $part_sql);

            $stock_sql = "UPDATE spare_parts SET stock_quantity = stock_quantity - ?, updated_at = NOW() WHERE part_id = ?";
            $stock_stmt = mysqli_prepare($conn, $stock_sql);

            foreach ($parts_used as $item) {
                $part_id = $item['part_id'];
                $qty = $item['quantity_used'];

                mysqli_stmt_bind_param($part_stmt, 'iii', $service_id, $part_id, $qty);
                mysqli_stmt_execute($part_stmt);

                mysqli_stmt_bind_param($stock_stmt, 'ii', $qty, $part_id);
                mysqli_stmt_execute($stock_stmt);
            }
            mysqli_stmt_close($part_stmt);
            mysqli_stmt_close($stock_stmt);
        }

        $bus_sql = "UPDATE buses SET trips_since_service = 0, status = 'active' WHERE bus_id = ?";
        $bus_stmt = mysqli_prepare($conn, $bus_sql);
        mysqli_stmt_bind_param($bus_stmt, 'i', $bus_id);
        mysqli_stmt_execute($bus_stmt);
        mysqli_stmt_close($bus_stmt);

        mysqli_commit($conn);
        return true;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        return false;
    }
}

function get_service_history_by_bus($conn, $bus_id)
{
    $sql = "SELECT sh.*, u.name AS manager_name 
            FROM service_history sh
            LEFT JOIN users u ON sh.manager_id = u.user_id
            WHERE sh.bus_id = ?
            ORDER BY sh.service_date DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $bus_id);
    mysqli_stmt_execute($stmt);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $rows;
}

function update_service_record($conn, $service_id, $work_done, $cost)
{
    $sql = "UPDATE service_history SET work_done = ?, cost = ? WHERE service_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'sdi', $work_done, $cost, $service_id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}
?>