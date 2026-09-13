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
<?php
// controllers/manager_controller.php

require_role('manager');

$action = isset($_GET['action']) ? $_GET['action'] : 'dashboard';

$manager_id = $_SESSION['user']['user_id'];

if ($action == 'dashboard') {
    require_once __DIR__ . '/../models/maintenance_model.php';
    require_once __DIR__ . '/../models/part_model.php';
    require_once __DIR__ . '/../models/service_model.php';

    $busesNeedingService = get_buses_needing_service($conn);
    $openRequests        = get_open_requests($conn, $manager_id);
    $lowStockParts       = get_low_stock_parts($conn);
    $costReport          = get_maintenance_cost_report($conn, $manager_id);

    $pageTitle = 'Manager Dashboard';
    require __DIR__ . '/../views/manager/dashboard.php';
}
elseif ($action == 'parts') {
    require_once __DIR__ . '/../models/part_model.php';

    $editing = null;
    if (isset($_GET['edit'])) {
        $editing = get_part($conn, (int) $_GET['edit']);
    }

    $q = clean_input($_GET['q'] ?? '');
    $partList = is_blank($q) ? get_all_parts($conn) : search_parts($conn, $q);

    $pageTitle = 'Spare Parts';
    require __DIR__ . '/../views/manager/parts.php';
}
elseif ($action == 'addpart') {
    require_once __DIR__ . '/../models/part_model.php';
    csrf_check();

    $name  = clean_input($_POST['part_name'] ?? '');
    $qty   = (int) ($_POST['stock_quantity'] ?? 0);
    $price = (float) ($_POST['unit_price'] ?? 0);

    if (is_blank($name)) {
        set_old($_POST);
        set_flash('error', 'Part name is required.');
    } elseif ($qty < 0 || $price < 0) {
        set_old($_POST);
        set_flash('error', 'Quantity and price must not be negative.');
    } else {
        add_part($conn, $name, $qty, $price);
        clear_old();
        set_flash('success', 'Part added.');
    }
    redirect('index.php?page=manager&action=parts');
}
elseif ($action == 'updatepart') {
    require_once __DIR__ . '/../models/part_model.php';
    csrf_check();

    $id    = (int) ($_GET['id'] ?? 0);
    $name  = clean_input($_POST['part_name'] ?? '');
    $qty   = (int) ($_POST['stock_quantity'] ?? 0);
    $price = (float) ($_POST['unit_price'] ?? 0);

    if (is_blank($name)) {
        set_flash('error', 'Part name is required.');
        redirect('index.php?page=manager&action=parts&edit=' . $id);
    } elseif ($qty < 0 || $price < 0) {
        set_flash('error', 'Quantity and price must not be negative.');
        redirect('index.php?page=manager&action=parts&edit=' . $id);
    } else {
        update_part($conn, $id, $name, $qty, $price);
        set_flash('success', 'Part updated.');
        redirect('index.php?page=manager&action=parts');
    }
}
elseif ($action == 'deletepart') {
    require_once __DIR__ . '/../models/part_model.php';
    csrf_check();

    $id = (int) ($_GET['id'] ?? 0);
    delete_part($conn, $id);
    set_flash('success', 'Part deleted.');
    redirect('index.php?page=manager&action=parts');
}
elseif ($action == 'requests') {
    require_once __DIR__ . '/../models/maintenance_model.php';

    $q = clean_input($_GET['q'] ?? '');
    $requestList = is_blank($q)
        ? get_manager_requests($conn, $manager_id)
        : search_manager_requests($conn, $manager_id, $q);

    $busList = get_buses_for_dropdown($conn);

    $pageTitle = 'Maintenance Requests';
    require __DIR__ . '/../views/manager/requests.php';
}
elseif ($action == 'addrequest') {
    require_once __DIR__ . '/../models/maintenance_model.php';
    csrf_check();

    $bus_id = (int) ($_POST['bus_id'] ?? 0);
    $issue  = clean_input($_POST['issue'] ?? '');

    if ($bus_id <= 0) {
        set_old($_POST);
        set_flash('error', 'Please select a bus.');
    } elseif (is_blank($issue)) {
        set_old($_POST);
        set_flash('error', 'Please describe the issue.');
    } else {
        add_request($conn, $bus_id, $manager_id, $issue);
        clear_old();
        set_flash('success', 'Maintenance request submitted.');
    }
    redirect('index.php?page=manager&action=requests');
}
elseif ($action == 'updaterequest') {
    require_once __DIR__ . '/../models/maintenance_model.php';
    csrf_check();

    $id     = (int) ($_GET['id'] ?? 0);
    $status = $_POST['status'] ?? '';

    if (!in_array($status, ['in_progress', 'done'], true)) {
        set_flash('error', 'Invalid status change.');
    } else {
        update_request_status($conn, $id, $manager_id, $status);
        set_flash('success', 'Request updated.');
    }
    redirect('index.php?page=manager&action=requests');
}
elseif ($action == 'cancelrequest') {
    require_once __DIR__ . '/../models/maintenance_model.php';
    csrf_check();

    $id = (int) ($_GET['id'] ?? 0);
    cancel_request($conn, $id, $manager_id);
    set_flash('success', 'Request cancelled.');
    redirect('index.php?page=manager&action=requests');
}
elseif ($action == 'services') {
    require_once __DIR__ . '/../models/service_model.php';
    require_once __DIR__ . '/../models/maintenance_model.php';
    require_once __DIR__ . '/../models/part_model.php';

    $editing = null;
    if (isset($_GET['edit'])) {
        $editing = get_service($conn, (int) $_GET['edit'], $manager_id);
    }

    $q = clean_input($_GET['q'] ?? '');
    $serviceList = is_blank($q)
        ? get_manager_services($conn, $manager_id)
        : search_manager_services($conn, $manager_id, $q);

    foreach ($serviceList as &$s) {
        $s['parts_used'] = get_parts_for_service($conn, $s['service_id']);
    }
    unset($s);

    $busList  = get_buses_for_dropdown($conn);
    $partList = get_all_parts($conn);

    $pageTitle = 'Service History';
    require __DIR__ . '/../views/manager/services.php';
}
elseif ($action == 'addservice') {
    require_once __DIR__ . '/../models/service_model.php';
    csrf_check();

    $bus_id       = (int) ($_POST['bus_id'] ?? 0);
    $service_date = $_POST['service_date'] ?? '';
    $work_done    = clean_input($_POST['work_done'] ?? '');
    $cost         = (float) ($_POST['cost'] ?? 0);

    if ($bus_id <= 0) {
        set_old($_POST);
        set_flash('error', 'Please select a bus.');
        redirect('index.php?page=manager&action=services');
    }
    if (is_blank($service_date) || !valid_date($service_date)) {
        set_old($_POST);
        set_flash('error', 'Please enter a valid service date.');
        redirect('index.php?page=manager&action=services');
    }
    if (is_blank($work_done)) {
        set_old($_POST);
        set_flash('error', 'Please describe the work done.');
        redirect('index.php?page=manager&action=services');
    }
    if ($cost < 0) {
        set_old($_POST);
        set_flash('error', 'Cost must not be negative.');
        redirect('index.php?page=manager&action=services');
    }

    $service_id = add_service($conn, $bus_id, $manager_id, $service_date, $work_done, $cost);

    $partIds  = $_POST['part_id'] ?? [];
    $partQtys = $_POST['part_qty'] ?? [];

    foreach ($partIds as $i => $partId) {
        $partId = (int) $partId;
        $qty    = (int) ($partQtys[$i] ?? 0);

        if ($partId > 0 && $qty > 0) {
            add_service_part($conn, $service_id, $partId, $qty);
            decrement_part_stock($conn, $partId, $qty);
        }
    }

    clear_old();
    set_flash('success', 'Service record added.');
    redirect('index.php?page=manager&action=services');
}
elseif ($action == 'updateservice') {
    require_once __DIR__ . '/../models/service_model.php';
    csrf_check();

    $id           = (int) ($_GET['id'] ?? 0);
    $bus_id       = (int) ($_POST['bus_id'] ?? 0);
    $service_date = $_POST['service_date'] ?? '';
    $work_done    = clean_input($_POST['work_done'] ?? '');
    $cost         = (float) ($_POST['cost'] ?? 0);

    if ($bus_id <= 0 || is_blank($service_date) || !valid_date($service_date) || is_blank($work_done) || $cost < 0) {
        set_flash('error', 'Please fill all fields correctly.');
        redirect('index.php?page=manager&action=services&edit=' . $id);
    } else {
        update_service($conn, $id, $manager_id, $bus_id, $service_date, $work_done, $cost);
        set_flash('success', 'Service record updated.');
        redirect('index.php?page=manager&action=services');
    }
}
else {
    header("Location: index.php?page=manager");
    exit();
}
