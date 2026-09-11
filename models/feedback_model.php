<?php

/*
==========================================================
models/feedback_model.php
==========================================================
*/


// $user_id may be null — guests can also leave feedback (schema allows it)
function add_feedback($user_id, $message)
{
    global $conn;

    $sql  = "INSERT INTO feedback (user_id, message) VALUES (?, ?)";
    $stmt = mysqli_prepare($conn, $sql);

    if (empty($user_id)) {
        $null = null;
        mysqli_stmt_bind_param($stmt, "is", $null, $message);
    } else {
        mysqli_stmt_bind_param($stmt, "is", $user_id, $message);
    }

    return mysqli_stmt_execute($stmt);
}
