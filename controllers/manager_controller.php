<?php
// ================================================================
// CONTROLLER: MANAGER dashboard (Maintenance & Fleet Management)
// Architecture: MVC, Procedural PHP, Security Checks
// ================================================================

require_once __DIR__ . '/../models/maintenance_model.php';
require_once __DIR__ . '/../models/service_model.php';
require_once __DIR__ . '/../models/part_model.php';
require_once __DIR__ . '/../models/bus_model.php';

function manager_controller($conn)
{
    // ১. রোলের নিরাপত্তা নিশ্চিতকরণ (Role Guard)
    require_role('manager');

    $action = $_GET['action'] ?? 'list';
    $me = current_user();

    $error = '';
    $editing = null;

    /* ---------------- CREATE MAINTENANCE REQUEST ---------------- */
    if ($action === 'create_request' && is_post()) {
        csrf_check();

        $bus_id = (int) ($_POST['bus_id'] ?? 0);
        $issue = trim($_POST['issue'] ?? '');

        if ($bus_id <= 0 || is_blank($issue)) {
            $error = 'Please select a bus and describe the issue.';
        } else {
            if (create_maintenance_request($conn, $bus_id, (int) $me['id'], $issue)) {
                log_activity($conn, $me['id'], $me['username'], $me['role'], 'Created maintenance request for bus #' . $bus_id);
                set_flash('success', 'Maintenance request created successfully.');
                redirect('index.php?page=manager');
            }
            $error = 'Could not create maintenance request.';
        }
    }

    /* ---------------- UPDATE REQUEST STATUS ---------------- */
    if ($action === 'update_request_status' && is_post()) {
        csrf_check();

        $request_id = (int) ($_POST['request_id'] ?? 0);
        $status = trim($_POST['status'] ?? '');

        if ($request_id <= 0 || is_blank($status)) {
            $error = 'Invalid request or status.';
        } else {
            if (update_maintenance_request_status($conn, $request_id, $status)) {
                log_activity($conn, $me['id'], $me['username'], $me['role'], 'Updated maintenance request #' . $request_id . ' status to ' . $status);
                set_flash('success', 'Maintenance request status updated.');
                redirect('index.php?page=manager');
            }
            $error = 'Could not update request status.';
        }
    }

    /* ---------------- CREATE SERVICE RECORD ---------------- */
    if ($action === 'add_service' && is_post()) {
        csrf_check();

        $bus_id = (int) ($_POST['bus_id'] ?? 0);
        $service_date = trim($_POST['service_date'] ?? '');
        $work_done = trim($_POST['work_done'] ?? '');
        $cost = trim($_POST['cost'] ?? '');
        $parts_used = $_POST['parts'] ?? [];

        if ($bus_id <= 0 || is_blank($service_date) || is_blank($work_done) || is_blank($cost)) {
            $error = 'Fill in all required service fields.';
        } elseif (!is_numeric($cost) || (float) $cost < 0) {
            $error = 'Cost must be a valid number (0 or more).';
        } else {
            if (create_service_record($conn, $bus_id, (int) $me['id'], $service_date, $work_done, (float) $cost, $parts_used)) {
                log_activity($conn, $me['id'], $me['username'], $me['role'], 'Added service record for bus #' . $bus_id);
                set_flash('success', 'Service record added and inventory updated.');
                redirect('index.php?page=manager');
            }
            $error = 'Could not record the service entry.';
        }
    }

    /* ---------------- SPARE PARTS CRUD ---------------- */
    if ($action === 'add_part' && is_post()) {
        csrf_check();

        $part_name = trim($_POST['part_name'] ?? '');
        $stock_quantity = trim($_POST['stock_quantity'] ?? '');
        $unit_price = trim($_POST['unit_price'] ?? '');

        if (is_blank($part_name) || is_blank($stock_quantity) || is_blank($unit_price)) {
            $error = 'Fill in every field for the spare part.';
        } elseif (!filter_var($stock_quantity, FILTER_VALIDATE_INT, ["options" => ["min_range" => 0]])) {
            $error = 'Stock quantity must be a non-negative whole number.';
        } elseif (!is_numeric($unit_price) || (float) $unit_price < 0) {
            $error = 'Unit price must be a valid number.';
        } else {
            if (create_spare_part($conn, $part_name, (int) $stock_quantity, (float) $unit_price)) {
                log_activity($conn, $me['id'], $me['username'], $me['role'], 'Added new spare part: ' . $part_name);
                set_flash('success', 'Spare part added to inventory.');
                redirect('index.php?page=manager');
            }
            $error = 'Could not add the spare part.';
        }
    }

    // এডিট ফর্ম ডেটা লোড
    if ($action === 'edit_part') {
        $part_id = (int) ($_GET['id'] ?? 0);
        $editing = get_spare_part_by_id($conn, $part_id);
        if (!$editing) {
            set_flash('error', 'That spare part no longer exists.');
            redirect('index.php?page=manager');
        }
    }

    // এডিট ফর্ম আপডেট সাবমিট
    if ($action === 'update_part' && is_post()) {
        csrf_check();

        $part_id = (int) ($_GET['id'] ?? 0);
        $part_name = trim($_POST['part_name'] ?? '');
        $stock_quantity = trim($_POST['stock_quantity'] ?? '');
        $unit_price = trim($_POST['unit_price'] ?? '');

        $editing = [
            'id' => $part_id,
            'part_name' => $part_name,
            'stock_quantity' => $stock_quantity,
            'unit_price' => $unit_price
        ];

        if ($part_id <= 0 || is_blank($part_name) || is_blank($stock_quantity) || is_blank($unit_price)) {
            $error = 'No field can be left empty.';
        } elseif (!filter_var($stock_quantity, FILTER_VALIDATE_INT, ["options" => ["min_range" => 0]])) {
            $error = 'Stock quantity must be a non-negative whole number.';
        } elseif (!is_numeric($unit_price) || (float) $unit_price < 0) {
            $error = 'Unit price must be a valid number.';
        } else {
            if (update_spare_part($conn, $part_id, $part_name, (int) $stock_quantity, (float) $unit_price)) {
                log_activity($conn, $me['id'], $me['username'], $me['role'], 'Updated spare part #' . $part_id . ': ' . $part_name);
                set_flash('success', 'Spare part updated.');
                redirect('index.php?page=manager');
            }
            $error = 'Update failed.';
        }
    }

    // আইটেম ডিলিট
    if ($action === 'delete_part') {
        csrf_check();
        $part_id = (int) ($_GET['id'] ?? 0);
        if ($part_id > 0 && delete_spare_part($conn, $part_id)) {
            log_activity($conn, $me['id'], $me['username'], $me['role'], 'Deleted spare part #' . $part_id);
            set_flash('success', 'Spare part deleted.');
        } else {
            set_flash('error', 'Could not delete that spare part.');
        }
        redirect('index.php?page=manager');
    }

    /* ---------------- Data for the view ---------------- */
    $requests = get_all_maintenance_requests($conn);
    $parts = get_all_spare_parts($conn);
    $dueBuses = get_buses_due_for_service($conn);

    require __DIR__ . '/../views/manager/dashboard.php';
}
?>