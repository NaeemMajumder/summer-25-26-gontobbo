<?php
// ==========================================
// MODEL: users (Admin Extra Oversight Feature)
// Read-only user list and ban/suspend functionality
// ==========================================

function get_users($conn, $role = '') {
    if ($role === '') {
        $sql = "SELECT user_id, name, email, phone, role, status, created_at 
                FROM users ORDER BY user_id DESC";
        $res = mysqli_query($conn, $sql);
        return mysqli_fetch_all($res, MYSQLI_ASSOC);
    } else {
        $sql = "SELECT user_id, name, email, phone, role, status, created_at 
                FROM users WHERE role = ? ORDER BY user_id DESC";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 's', $role);
        mysqli_stmt_execute($stmt);
        $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);
        return $rows;
    }
}

function search_users($conn, $term, $role = '') {
    $like = '%' . $term . '%';
    if ($role === '') {
        $sql = "SELECT user_id, name, email, phone, role, status, created_at 
                FROM users 
                WHERE name LIKE ? OR email LIKE ? OR phone LIKE ? 
                ORDER BY user_id DESC";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'sss', $like, $like, $like);
    } else {
        $sql = "SELECT user_id, name, email, phone, role, status, created_at 
                FROM users 
                WHERE role = ? AND (name LIKE ? OR email LIKE ? OR phone LIKE ?) 
                ORDER BY user_id DESC";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'ssss', $role, $like, $like, $like);
    }
    mysqli_stmt_execute($stmt);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $rows;
}

function set_user_status($conn, $id, $status) {
    $sql = "UPDATE users SET status = ? WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'si', $status, $id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}
