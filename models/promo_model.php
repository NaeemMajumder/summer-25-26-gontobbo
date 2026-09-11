<?php

/*
==========================================================
models/promo_model.php
==========================================================
*/


function get_promo_by_code($code)
{
    global $conn;

    if (is_blank($code)) {
        return null;
    }

    $sql = "
        SELECT *
        FROM promo_codes
        WHERE code = ?
          AND is_active = 1
          AND (expiry_date IS NULL OR expiry_date >= CURDATE())
        LIMIT 1
    ";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $code);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result) ?: null;
}


function get_promo_by_id($promo_id)
{
    global $conn;

    if (empty($promo_id)) {
        return null;
    }

    $sql  = "SELECT * FROM promo_codes WHERE promo_id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $promo_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result) ?: null;
}
