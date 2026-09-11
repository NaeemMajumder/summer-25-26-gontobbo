<?php
// ================================================================
// MODEL: spare_parts
// ================================================================

function get_all_spare_parts($conn)
{
    $sql = "SELECT part_id, part_name, stock_quantity, unit_price, updated_at 
            FROM spare_parts ORDER BY part_name ASC";
    $res = mysqli_query($conn, $sql);
    return mysqli_fetch_all($res, MYSQLI_ASSOC);
}

function get_spare_part_by_id($conn, $part_id)
{
    $sql = "SELECT part_id, part_name, stock_quantity, unit_price, updated_at 
            FROM spare_parts WHERE part_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $part_id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return $row;
}

function create_spare_part($conn, $part_name, $stock_quantity, $unit_price)
{
    $sql = "INSERT INTO spare_parts (part_name, stock_quantity, unit_price, updated_at) 
            VALUES (?, ?, ?, NOW())";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'sid', $part_name, $stock_quantity, $unit_price);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

function update_spare_part($conn, $part_id, $part_name, $stock_quantity, $unit_price)
{
    $sql = "UPDATE spare_parts 
            SET part_name = ?, stock_quantity = ?, unit_price = ?, updated_at = NOW() 
            WHERE part_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'sidi', $part_name, $stock_quantity, $unit_price, $part_id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

function delete_spare_part($conn, $part_id)
{
    $sql = "DELETE FROM spare_parts WHERE part_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $part_id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}
?>