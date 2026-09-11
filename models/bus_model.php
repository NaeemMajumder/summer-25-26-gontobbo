<?php

/*
==========================================================
models/bus_model.php
==========================================================
*/


function get_bus_by_id($bus_id)
{
    global $conn;

    $sql  = "SELECT * FROM buses WHERE bus_id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $bus_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result) ?: null;
}


function get_active_buses()
{
    global $conn;

    $sql    = "SELECT * FROM buses WHERE status = 'active' ORDER BY name ASC";
    $result = mysqli_query($conn, $sql);

    $buses = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $buses[] = $row;
    }

    return $buses;
}
