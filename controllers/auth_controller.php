<?php
require_once __DIR__ . '/../models/user_model.php';
$page = $_GET['page'] ?? '';
/*
LOGOUT
*/
if ($page === 'logout') {
    $_SESSION = [];
    session_destroy();
    session_start();
    set_flash('success', 'You have been signed out.');
    redirect('index.php?page=login');
}

/*
LOGIN
*/
if ($page === 'login') {
    if (is_logged_in()) {
        redirect('index.php?page=passenger');
    }
    if (is_post()) {
        csrf_check();
        $email = clean_input($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        if (is_blank($email) || is_blank($password)) {
            set_flash('error', 'Email and password are required.');
            redirect('index.php?page=login');
        }
        $user = check_login($email, $password);
        if (!$user) {
            // Same message for "no such email" and "wrong password" — no username guessing
            set_flash('error', 'Invalid email or password.');
            redirect('index.php?page=login');
        }
        session_regenerate_id(true);
        unset($user['password'], $user['reset_token'], );
        $_SESSION['user'] = $user;
        $_SESSION['last_activity'] = time();
        if ($remember) {
            if ($remember) {
                setcookie("remember_email", $email, time() + (86400 * 30), "/");
            } else {
                setcookie("remember_email", "", time() - 3600, "/");
            }
        }
        set_flash('success', 'Welcome back, ' . $user['name'] . '!');
        redirect('index.php?page=passenger');
    }
    require __DIR__ . '/../views/auth/login.php';
    exit;
}
/*
REGISTER
*/
if ($page === 'register') {
    if (is_logged_in()) {
        redirect('index.php?page=passenger');
    }
    if (is_post()) {

        csrf_check();
        $name = clean_input($_POST['name'] ?? '');
        $email = clean_input($_POST['email'] ?? '');
        $phone = clean_input($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        set_old(['name' => $name, 'email' => $email, 'phone' => $phone]);

        if (is_blank($name) || is_blank($email) || is_blank($phone) || is_blank($password)) {
            set_flash('error', 'All fields are required.');
            redirect('index.php?page=register');
        }
        if (!valid_email($email)) {
            set_flash('error', 'Please enter a valid email address.');
            redirect('index.php?page=register');
        }
        if (!valid_phone($phone)) {
            set_flash('error', 'Phone number must be exactly 11 digits.');
            redirect('index.php?page=register');
        }
        if (!valid_password($password)) {
            set_flash('error', 'Password must be at least 6 characters.');
            redirect('index.php?page=register');
        }
        if ($password !== $confirm) {
            set_flash('error', 'Passwords do not match.');
            redirect('index.php?page=register');
        }
        if (find_user_by_email_or_phone($email, $phone)) {
            set_flash('error', 'That email or phone number is already registered.');
            redirect('index.php?page=register');
        }
        // Registration only ever creates Passenger accounts in this build.
        // Driver / Admin / Manager registration belongs to a separate module.
        $userId = create_user($name, $email, $phone, $password, 'passenger');

        if ($userId) {
            clear_old();
            set_flash('success', 'Registration successful. Please log in.');
            redirect('index.php?page=login');
        }

        set_flash('error', 'Registration failed. Please try again.');
        redirect('index.php?page=register');
    }

    require __DIR__ . '/../views/auth/register.php';
    exit;
}
/*
FORGOT / RESET PASSWORD
*/

if ($page === 'forgot') {
    if (is_post()) {
        csrf_check();
        $token = $_POST['token'] ?? '';
        if (!is_blank($token)) {
            // Step 2: set a new password
            $password = $_POST['password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';

            if (!valid_password($password)) {
                set_flash('error', 'Password must be at least 6 characters.');
                redirect('index.php?page=forgot&token=' . urlencode($token));
            }
            if ($password !== $confirm) {
                set_flash('error', 'Passwords do not match.');
                redirect('index.php?page=forgot&token=' . urlencode($token));
            }

            if (reset_password_with_token($token, $password)) {
                set_flash('success', 'Password updated. Please log in with your new password.');
                redirect('index.php?page=login');
            }
            set_flash('error', 'That reset link is invalid or has expired.');
            redirect('index.php?page=forgot');
        }
        $email = clean_input($_POST['email'] ?? '');
        if (is_blank($email) || !valid_email($email)) {
            set_flash('error', 'Please enter a valid email address.');
            redirect('index.php?page=forgot');
        }
        $user = find_user_by_email($email);
        if ($user) {
            $resetToken = bin2hex(random_bytes(32));
            set_reset_token($email, $resetToken);
            set_flash('info', 'Demo mode: no mail server is configured, so we\'ve taken you straight to the reset screen.');
            redirect('index.php?page=forgot&token=' . urlencode($resetToken));
        }
        set_flash('success', 'If that email is registered, a reset link has been generated.');
        redirect('index.php?page=forgot');
    }
    require __DIR__ . '/../views/auth/forgot.php';
    exit;
}
