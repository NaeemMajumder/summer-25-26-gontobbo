 <?php
    // ==========================================
    // CONTROLLER: ADMIN dashboard & modules
    // Handles Buses, Routes, Trips, Promos, and Unique Features
    // ==========================================

    function admin_controller($conn)
    {
        $action = $_GET['action'] ?? 'dashboard';
        $me = current_user();
        $error = '';
        $editing = null;

        // ==========================================
        // 1. BUS MANAGEMENT (CRUD)
        // ==========================================
        if ($action === 'addbus' && is_post()) {
            csrf_check();
            $bus_number = trim($_POST['bus_number'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $type = $_POST['type'] ?? 'Non-AC';
            $total_seats = trim($_POST['total_seats'] ?? '');
            $status = $_POST['status'] ?? 'active';
            $service_limit = trim($_POST['service_trip_limit'] ?? '');

            if (is_blank($bus_number) || is_blank($name) || is_blank($total_seats) || is_blank($service_limit)) {
                $error = 'All fields are required.';
            } elseif (!ctype_digit($total_seats) || (int)$total_seats <= 0) {
                $error = 'Total seats must be a positive whole number.';
            } elseif (!ctype_digit($service_limit) || (int)$service_limit <= 0) {
                $error = 'Service trip limit must be a positive whole number.';
            } elseif (bus_number_exists($conn, $bus_number)) {
                $error = 'A bus with this license number already exists.';
            } else {
                if (add_bus($conn, $bus_number, $name, $type, (int)$total_seats, $status, (int)$service_limit)) {
                    log_activity($conn, 'Added bus: ' . $bus_number);
                    set_flash('success', 'Bus added successfully.');
                    redirect('index.php?page=admin&action=buses');
                }
                $error = 'Could not add the bus.';
            }
        }

        if ($action === 'deletebus') {
            csrf_check();
            $id = (int)($_GET['id'] ?? 0);

            // Guardrail: Check if bus is assigned to any active trips before deletion
            $check = mysqli_query($conn, "SELECT trip_id FROM trips WHERE bus_id = $id LIMIT 1");
            if (mysqli_num_rows($check) > 0) {
                set_flash('error', 'Cannot delete this bus because it is assigned to existing trips. Mark it as inactive instead.');
            } elseif ($id > 0 && delete_bus($conn, $id)) {
                log_activity($conn, 'Deleted bus ID: ' . $id);
                set_flash('success', 'Bus deleted.');
            } else {
                set_flash('error', 'Could not delete bus.');
            }
            redirect('index.php?page=admin&action=buses');
        }

        // ==========================================
        // 2. ROUTE MANAGEMENT (Transactions & Stops)
        // ==========================================
        if ($action === 'addroute' && is_post()) {
            csrf_check();
            $origin = trim($_POST['origin'] ?? '');
            $destination = trim($_POST['destination'] ?? '');
            $distance = trim($_POST['distance_km'] ?? '');
            $duration = trim($_POST['duration'] ?? '');
            $stops = $_POST['stops'] ?? []; // Array of stop names from frontend

            if (is_blank($origin) || is_blank($destination) || is_blank($distance)) {
                $error = 'Origin, destination, and distance are required.';
            } elseif (!is_numeric($distance) || (float)$distance <= 0) {
                $error = 'Distance must be a positive number.';
            } else {
                if (add_route($conn, $origin, $destination, (float)$distance, $duration, $stops)) {
                    log_activity($conn, "Added route: $origin to $destination");
                    set_flash('success', 'Route and stops added successfully.');
                    redirect('index.php?page=admin&action=routes');
                }
                $error = 'Could not add the route due to a database error.';
            }
        }

        // ==========================================
        // 3. TRIP MANAGEMENT (The Core Schedule & Data Integrity Guard)
        // ==========================================
        if ($action === 'deletetrip') {
            csrf_check();
            $id = (int)($_GET['id'] ?? 0);

            // Critical Guardrail: Do not delete trips that have passenger bookings.
            $check = mysqli_query($conn, "SELECT booking_id FROM bookings WHERE trip_id = $id LIMIT 1");
            if (mysqli_num_rows($check) > 0) {
                set_flash('error', 'Tickets have been sold for this trip. You cannot delete it. Please update the status to "cancelled" instead.');
            } elseif ($id > 0 && delete_trip($conn, $id)) {
                log_activity($conn, 'Deleted trip ID: ' . $id);
                set_flash('success', 'Scheduled trip deleted.');
            } else {
                set_flash('error', 'Could not delete trip.');
            }
            redirect('index.php?page=admin&action=trips');
        }

        // ==========================================
        // 4. UNIQUE FEATURE: DAMAGE REPORTS (Resolution)
        // ==========================================
        if ($action === 'resolvedamage') {
            csrf_check();
            $id = (int)($_GET['id'] ?? 0);
            if (resolve_damage_report($conn, $id)) {
                log_activity($conn, 'Resolved damage report ID: ' . $id);
                set_flash('success', 'Damage report marked as resolved.');
            } else {
                set_flash('error', 'Could not resolve report. It may already be closed.');
            }
            redirect('index.php?page=admin&action=damage');
        }

        // ==========================================
        // 5. EXTRA OVERSIGHT: USER MANAGEMENT (Suspension)
        // ==========================================
        if ($action === 'statususer') {
            csrf_check();
            $id = (int)($_GET['id'] ?? 0);
            $status = $_GET['to'] ?? '';

            if ($id === (int)$me['id']) {
                set_flash('error', 'You cannot suspend your own admin account.');
            } elseif (in_array($status, ['active', 'suspended'], true) && set_user_status($conn, $id, $status)) {
                log_activity($conn, "Changed user ID $id status to $status");
                set_flash('success', "Account is now $status.");
            } else {
                set_flash('error', 'Could not change account status.');
            }
            redirect('index.php?page=admin&action=users');
        }

        // ==========================================
        // VIEW DATA ROUTING (Prepping data for the HTML)
        // ==========================================
        $viewData = [];

        if ($action === 'buses') {
            $viewData['buses'] = get_buses($conn);
        } elseif ($action === 'routes') {
            $viewData['routes'] = get_routes($conn);
        } elseif ($action === 'trips') {
            $viewData['trips'] = get_trips($conn);
        } elseif ($action === 'damage') {
            $viewData['reports'] = get_damage_reports($conn); // Unique feature 1
        } elseif ($action === 'feedback') {
            $viewData['feedback'] = get_all_feedback($conn);  // Unique feature 2
        } elseif ($action === 'users') {
            $viewData['users'] = get_users($conn);            // Oversight
        } elseif ($action === 'revenue') {
            // Unique Feature 3: Handled primarily via AJAX for charts, but initial page load here.
            $viewData['total_revenue'] = get_total_revenue($conn);
        }

        // Load the massive Admin Dashboard view (which uses $action to display the right section)
        require __DIR__ . '/../views/admin/dashboard.php';
    }
