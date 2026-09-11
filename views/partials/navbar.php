<?php

/*
==========================================================
views/partials/navbar.php

Included by header.php. Shows a different menu for guests
vs a logged-in passenger.
==========================================================
*/

$navUser    = current_user();
$loggedIn   = is_logged_in();
$activePage = $_GET['action'] ?? 'dashboard';

?>

<header class="navbar">
    <div class="navbar-inner">

        <a class="brand" href="index.php?page=passenger">
            <span class="brand-icon">&#128652;</span>
            <span><?= esc(APP_NAME) ?></span>
        </a>

        <nav class="nav-menu">

            <a href="index.php?page=passenger" class="<?= $activePage === 'dashboard' ? 'active' : '' ?>">Home</a>
            <a href="index.php?page=passenger&action=search" class="<?= $activePage === 'search' ? 'active' : '' ?>">Search Bus</a>
            <a href="index.php?page=passenger&action=busratings" class="<?= $activePage === 'busratings' ? 'active' : '' ?>">Bus Ratings</a>

            <?php if ($loggedIn): ?>

                <a href="index.php?page=passenger&action=mytickets" class="<?= $activePage === 'mytickets' ? 'active' : '' ?>">My Tickets</a>
                <a href="index.php?page=passenger&action=myreviews" class="<?= $activePage === 'myreviews' ? 'active' : '' ?>">Reviews</a>

            <?php endif; ?>

            <a href="index.php?page=passenger&action=feedback" class="<?= $activePage === 'feedback' ? 'active' : '' ?>">Feedback</a>

        </nav>

        <div class="nav-user">

            <?php if ($loggedIn): ?>

                <a href="index.php?page=passenger&action=profile" class="user-pill">
                    <span class="user-avatar"><?= esc(strtoupper(substr($navUser['name'] ?? 'P', 0, 1))) ?></span>
                    <span class="user-meta">
                        <span class="user-name"><?= esc($navUser['name'] ?? 'Passenger') ?></span>
                        <span class="user-role">Passenger</span>
                    </span>
                </a>

                <a href="index.php?page=logout" class="btn-logout">Sign out</a>

            <?php else: ?>

                <a href="index.php?page=login" class="btn btn-ghost">Login</a>
                <a href="index.php?page=register" class="btn btn-primary">Register</a>

            <?php endif; ?>

        </div>

    </div>
</header>
