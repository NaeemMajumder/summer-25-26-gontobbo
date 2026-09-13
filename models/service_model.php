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
<?php
// models/service_model.php

// All service records filed by this manager (across all buses), newest first
function get_manager_services($conn, $manager_id)
{
    $sql = "SELECT s.service_id, s.bus_id, s.service_date, s.work_done, s.cost, s.created_at,
                   b.name AS bus_name, b.bus_number
            FROM service_history s
            JOIN buses b ON s.bus_id = b.bus_id
            WHERE s.manager_id = ?
            ORDER BY s.service_id DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $manager_id);
    mysqli_stmt_execute($stmt);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $rows;
}

function search_manager_services($conn, $manager_id, $term)
{
    $like = '%' . $term . '%';
    $sql = "SELECT s.service_id, s.bus_id, s.service_date, s.work_done, s.cost, s.created_at,
                   b.name AS bus_name, b.bus_number
            FROM service_history s
            JOIN buses b ON s.bus_id = b.bus_id
            WHERE s.manager_id = ? AND (s.work_done LIKE ? OR b.bus_number LIKE ?)
            ORDER BY s.service_id DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'iss', $manager_id, $like, $like);
    mysqli_stmt_execute($stmt);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $rows;
}

// One service record (ownership-checked) — for edit-prefill
function get_service($conn, $service_id, $manager_id)
{
    $sql = "SELECT * FROM service_history WHERE service_id = ? AND manager_id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ii', $service_id, $manager_id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return $row;
}

// Which parts (and how many) were used on a given service record — for display
function get_parts_for_service($conn, $service_id)
{
    $sql = "SELECT sp.quantity_used, p.part_name
            FROM service_parts sp
            JOIN spare_parts p ON sp.part_id = p.part_id
            WHERE sp.service_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $service_id);
    mysqli_stmt_execute($stmt);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $rows;
}

// Creates the service_history row; returns the new service_id
function add_service($conn, $bus_id, $manager_id, $service_date, $work_done, $cost)
{
    $sql = "INSERT INTO service_history (bus_id, manager_id, service_date, work_done, cost, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'iissd', $bus_id, $manager_id, $service_date, $work_done, $cost);
    $ok = mysqli_stmt_execute($stmt);
    $id = $ok ? mysqli_insert_id($conn) : false;
    mysqli_stmt_close($stmt);
    return $id;
}

// Records one part consumed on a service (the service_parts junction row)
function add_service_part($conn, $service_id, $part_id, $quantity_used)
{
    $sql = "INSERT INTO service_parts (service_id, part_id, quantity_used) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'iii', $service_id, $part_id, $quantity_used);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

// Reduces spare_parts stock — GREATEST(...,0) so it never goes negative
// even if someone tries to consume more than what's in stock
function decrement_part_stock($conn, $part_id, $quantity_used)
{
    $sql = "UPDATE spare_parts
            SET stock_quantity = GREATEST(stock_quantity - ?, 0), updated_at = NOW()
            WHERE part_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ii', $quantity_used, $part_id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

// Ownership-checked update — bus/date/work/cost only, parts are locked after creation
function update_service($conn, $service_id, $manager_id, $bus_id, $service_date, $work_done, $cost)
{
    $sql = "UPDATE service_history
            SET bus_id = ?, service_date = ?, work_done = ?, cost = ?
            WHERE service_id = ? AND manager_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'issdii', $bus_id, $service_date, $work_done, $cost, $service_id, $manager_id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

// Total maintenance cost for this manager: labor/service cost + parts consumed
function get_maintenance_cost_report($conn, $manager_id)
{
    // labor/misc cost recorded directly on each service record
    $sql1 = "SELECT COALESCE(SUM(cost), 0) AS labor_cost FROM service_history WHERE manager_id = ?";
    $stmt = mysqli_prepare($conn, $sql1);
    mysqli_stmt_bind_param($stmt, 'i', $manager_id);
    mysqli_stmt_execute($stmt);
    $row1 = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    // cost of parts consumed across this manager's service records
    $sql2 = "SELECT COALESCE(SUM(sp.quantity_used * p.unit_price), 0) AS parts_cost
             FROM service_parts sp
             JOIN spare_parts p      ON sp.part_id = p.part_id
             JOIN service_history s ON sp.service_id = s.service_id
             WHERE s.manager_id = ?";
    $stmt = mysqli_prepare($conn, $sql2);
    mysqli_stmt_bind_param($stmt, 'i', $manager_id);
    mysqli_stmt_execute($stmt);
    $row2 = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    $labor = (float) $row1['labor_cost'];
    $parts = (float) $row2['parts_cost'];

    return [
        'labor_cost' => $labor,
        'parts_cost' => $parts,
        'total_cost' => $labor + $parts,
    ];
}
