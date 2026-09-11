<?php

/*
models/user_model.php
*/
function find_user_by_id($user_id)
{
    global $conn;
    $sql = "SELECT * FROM users WHERE user_id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result) ?: null;
}


function find_user_by_email($email)
{
    global $conn;
    $sql = "SELECT * FROM users WHERE email = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result) ?: null;
}
/*
Check Duplicate Email / Phone (used at registration)
*/
function find_user_by_email_or_phone($email, $phone)
{
    global $conn;
    $sql = "SELECT user_id, email, phone FROM users WHERE email = ? OR phone = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $email, $phone);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result) ?: null;
}
function create_user($name, $email, $phone, $password, $role = 'passenger')
{
    global $conn;
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssss", $name, $email, $phone, $hashed, $role);
    if (mysqli_stmt_execute($stmt)) {
        return mysqli_insert_id($conn);
    }
    return false;
}
function check_login($email, $password)
{
    $user = find_user_by_email($email);
    if ($user && password_verify($password, $user['password'])) {
        return $user;
    }
    return false;
}
function update_profile($user_id, $name, $phone)
{
    global $conn;
    $sql = "UPDATE users SET name = ?, phone = ? WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssi", $name, $phone, $user_id);
    return mysqli_stmt_execute($stmt);
}
function verify_password($user_id, $plain_password)
{
    $user = find_user_by_id($user_id);
    return $user && password_verify($plain_password, $user['password']);
}
function update_password($user_id, $new_password)
{
    global $conn;
    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
    $sql = "UPDATE users SET password = ? WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "si", $hashed, $user_id);
    return mysqli_stmt_execute($stmt);
}
/*
| Forgot / Reset Password
*/
function set_reset_token($email, $token)
{
    global $conn;
    $sql = "UPDATE users SET reset_token = ? WHERE email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $token, $email);
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_affected_rows($stmt) > 0;
}
function find_user_by_reset_token($token)
{
    global $conn;
    if (is_blank($token)) {
        return null;
    }
    $sql = "SELECT * FROM users WHERE reset_token = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result) ?: null;
}
function reset_password_with_token($token, $new_password)
{
    global $conn;
    $user = find_user_by_reset_token($token);
    if (!$user) {
        return false;
    }
    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
    $sql = "UPDATE users SET password = ?, reset_token = NULL WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "si", $hashed, $user['user_id']);
    return mysqli_stmt_execute($stmt);
}
/*
Delete Account
*/
function delete_account($user_id)
{
    global $conn;
    mysqli_begin_transaction($conn);
    try {
        $sql = "SELECT trip_id, seats_booked FROM bookings WHERE user_id = ? AND booking_status = 'confirmed'";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $upd = mysqli_prepare($conn, "UPDATE trips SET available_seats = available_seats + ? WHERE trip_id = ?");
            mysqli_stmt_bind_param($upd, "ii", $row['seats_booked'], $row['trip_id']);
            mysqli_stmt_execute($upd);
        }
        $tables = ['reviews', 'bookings', 'feedback'];
        foreach ($tables as $table) {
            $del = mysqli_prepare($conn, "DELETE FROM {$table} WHERE user_id = ?");
            mysqli_stmt_bind_param($del, "i", $user_id);
            mysqli_stmt_execute($del);
        }
        $delUser = mysqli_prepare($conn, "DELETE FROM users WHERE user_id = ?");
        mysqli_stmt_bind_param($delUser, "i", $user_id);
        mysqli_stmt_execute($delUser);

        mysqli_commit($conn);
        return true;
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        return false;

    }
}
