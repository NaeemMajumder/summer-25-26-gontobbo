<?php
// ================================================================
// CONTROLLER: DRIVER DASHBOARD (Trips, Logs, Incidents & Availability)
// Architecture: MVC, Procedural PHP, Security Checks
// ================================================================

require_once __DIR__ . '/../models/triplog_model.php';
require_once __DIR__ . '/../models/incident_model.php';
require_once __DIR__ . '/../models/availability_model.php';
require_once __DIR__ . '/../models/trip_model.php';

function driver_controller($conn)
{
    // ১. রোলের নিরাপত্তা নিশ্চিতকরণ (Role Guard)
    require_role('driver');

    $action = $_GET['action'] ?? 'list';
    $me = current_user();

    $error = '';
    $editing_incident = null;
    $editing_availability = null;

    /* ---------------- CREATE TRIP LOG (Start Trip) ---------------- */
    if ($action === 'start_triplog' && is_post()) {
        csrf_check();

        $trip_id = (int) ($_POST['trip_id'] ?? 0);
        $start_time = date('Y-m-d H:i:s');
        $note = trim($_POST['note'] ?? '');

        if ($trip_id <= 0) {
            $error = 'Please select a valid trip to start.';
        } else {
            if (create_trip_log($conn, $trip_id, (int) $me['id'], $start_time, $note)) {
                log_activity($conn, $me['id'], $me['username'], $me['role'], 'Started trip log for trip #' . $trip_id);
                set_flash('success', 'Trip started successfully.');
                redirect('index.php?page=driver');
            }
            $error = 'Could not start trip log.';
        }
    }

    /* ---------------- COMPLETE TRIP LOG (End Trip) ---------------- */
    if ($action === 'complete_triplog' && is_post()) {
        csrf_check();

        $log_id = (int) ($_POST['log_id'] ?? 0);
        $end_time = date('Y-m-d H:i:s');
        $note = trim($_POST['note'] ?? '');

        if ($log_id <= 0) {
            $error = 'Invalid trip log selected.';
        } else {
            if (complete_trip_log($conn, $log_id, $end_time, $note)) {
                log_activity($conn, $me['id'], $me['username'], $me['role'], 'Completed trip log #' . $log_id);
                set_flash('success', 'Trip marked as completed.');
                redirect('index.php?page=driver');
            }
            $error = 'Could not complete trip log.';
        }
    }

    /* ---------------- DELETE TRIP LOG ---------------- */
    if ($action === 'delete_triplog') {
        csrf_check();
        $log_id = (int) ($_GET['id'] ?? 0);
        if ($log_id > 0 && delete_trip_log($conn, $log_id, (int) $me['id'])) {
            log_activity($conn, $me['id'], $me['username'], $me['role'], 'Deleted trip log #' . $log_id);
            set_flash('success', 'Trip log deleted successfully.');
        } else {
            set_flash('error', 'Could not delete that trip log.');
        }
        redirect('index.php?page=driver');
    }

    /* ---------------- CREATE INCIDENT REPORT ---------------- */
    if ($action === 'create_incident' && is_post()) {
        csrf_check();

        $trip_id = (int) ($_POST['trip_id'] ?? 0);
        $bus_id = (int) ($_POST['bus_id'] ?? 0);
        $type = trim($_POST['type'] ?? 'incident');
        $description = trim($_POST['description'] ?? '');

        if ($bus_id <= 0 || is_blank($description)) {
            $error = 'Please select a bus and describe the incident.';
        } else {
            $trip_param = ($trip_id > 0) ? $trip_id : null;
            if (create_incident_report($conn, (int) $me['id'], $trip_param, $bus_id, $type, $description)) {
                log_activity($conn, $me['id'], $me['username'], $me['role'], 'Reported incident for bus #' . $bus_id);
                set_flash('success', 'Incident report submitted successfully.');
                redirect('index.php?page=driver');
            }
            $error = 'Could not submit incident report.';
        }
    }

    /* ---------------- EDIT & UPDATE INCIDENT REPORT ---------------- */
    if ($action === 'edit_incident') {
        $incident_id = (int) ($_GET['id'] ?? 0);
        $editing_incident = get_incident_by_id($conn, $incident_id, (int) $me['id']);
        if (!$editing_incident) {
            set_flash('error', 'That incident report does not exist or cannot be edited.');
            redirect('index.php?page=driver');
        }
    }

    if ($action === 'update_incident' && is_post()) {
        csrf_check();

        $incident_id = (int) ($_GET['id'] ?? 0);
        $description = trim($_POST['description'] ?? '');

        if ($incident_id <= 0 || is_blank($description)) {
            $error = 'Incident description cannot be empty.';
        } else {
            if (update_incident_report($conn, $incident_id, (int) $me['id'], $description)) {
                log_activity($conn, $me['id'], $me['username'], $me['role'], 'Updated incident report #' . $incident_id);
                set_flash('success', 'Incident report updated.');
                redirect('index.php?page=driver');
            }
            $error = 'Could not update incident report.';
        }
    }

    /* ---------------- DELETE INCIDENT REPORT ---------------- */
    if ($action === 'delete_incident') {
        csrf_check();
        $incident_id = (int) ($_GET['id'] ?? 0);
        if ($incident_id > 0 && delete_incident_report($conn, $incident_id, (int) $me['id'])) {
            log_activity($conn, $me['id'], $me['username'], $me['role'], 'Deleted incident report #' . $incident_id);
            set_flash('success', 'Incident report deleted.');
        } else {
            set_flash('error', 'Could not delete that incident report.');
        }
        redirect('index.php?page=driver');
    }

    /* ---------------- DRIVER AVAILABILITY CRUD ---------------- */
    if ($action === 'add_availability' && is_post()) {
        csrf_check();

        $date = trim($_POST['date'] ?? '');
        $status = trim($_POST['status'] ?? 'available');
        $note = trim($_POST['note'] ?? '');

        if (is_blank($date) || is_blank($status)) {
            $error = 'Please provide a valid date and status.';
        } else {
            if (create_driver_availability($conn, (int) $me['id'], $date, $status, $note)) {
                log_activity($conn, $me['id'], $me['username'], $me['role'], 'Added availability for date: ' . $date);
                set_flash('success', 'Availability schedule added.');
                redirect('index.php?page=driver');
            }
            $error = 'Could not add availability.';
        }
    }

    if ($action === 'edit_availability') {
        $availability_id = (int) ($_GET['id'] ?? 0);
        $editing_availability = get_availability_by_id($conn, $availability_id, (int) $me['id']);
        if (!$editing_availability) {
            set_flash('error', 'That availability record no longer exists.');
            redirect('index.php?page=driver');
        }
    }

    if ($action === 'update_availability' && is_post()) {
        csrf_check();

        $availability_id = (int) ($_GET['id'] ?? 0);
        $status = trim($_POST['status'] ?? 'available');
        $note = trim($_POST['note'] ?? '');

        if ($availability_id <= 0 || is_blank($status)) {
            $error = 'Status cannot be left empty.';
        } else {
            if (update_driver_availability($conn, $availability_id, (int) $me['id'], $status, $note)) {
                log_activity($conn, $me['id'], $me['username'], $me['role'], 'Updated availability #' . $availability_id);
                set_flash('success', 'Availability updated successfully.');
                redirect('index.php?page=driver');
            }
            $error = 'Update failed.';
        }
    }

    if ($action === 'delete_availability') {
        csrf_check();
        $availability_id = (int) ($_GET['id'] ?? 0);
        if ($availability_id > 0 && delete_driver_availability($conn, $availability_id, (int) $me['id'])) {
            log_activity($conn, $me['id'], $me['username'], $me['role'], 'Deleted availability #' . $availability_id);
            set_flash('success', 'Availability schedule deleted.');
        } else {
            set_flash('error', 'Could not delete availability schedule.');
        }
        redirect('index.php?page=driver');
    }

    /* ---------------- Data for the view ---------------- */
    $assignedTrips = get_assigned_trips_by_driver($conn, (int) $me['id']);
    $tripLogs = get_trip_logs_by_driver($conn, (int) $me['id']);
    $incidents = get_incidents_by_driver($conn, (int) $me['id']);
    $availabilities = get_availability_by_driver($conn, (int) $me['id']);

    require __DIR__ . '/../views/driver/dashboard.php';
}
?>