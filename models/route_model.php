<?php

/*
==========================================================
models/route_model.php
==========================================================
*/


function get_route_by_id($route_id)
{
    global $conn;

    $sql  = "SELECT * FROM routes WHERE route_id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $route_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result) ?: null;
}


// Distinct origins/destinations, used to populate the Search form dropdowns
function get_distinct_origins()
{
    global $conn;

    $sql    = "SELECT DISTINCT origin FROM routes ORDER BY origin ASC";
    $result = mysqli_query($conn, $sql);

    $list = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $list[] = $row['origin'];
    }

    return $list;
}


function get_distinct_destinations()
{
    global $conn;

    $sql    = "SELECT DISTINCT destination FROM routes ORDER BY destination ASC";
    $result = mysqli_query($conn, $sql);

    $list = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $list[] = $row['destination'];
    }

    return $list;
}


// Boarding/dropping points for a route, in order
function get_stops_by_route($route_id)
{
    global $conn;

    $sql  = "SELECT * FROM route_stops WHERE route_id = ? ORDER BY stop_order ASC, stop_id ASC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $route_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $stops = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $stops[] = $row;
    }

    return $stops;
}


function get_stop_by_id($stop_id)
{
    global $conn;

    $sql  = "SELECT * FROM route_stops WHERE stop_id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $stop_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result) ?: null;
}
