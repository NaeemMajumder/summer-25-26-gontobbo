<?php

$pageTitle = 'Login';
$pageHeading = 'Login to ' . esc(APP_NAME);
$pageSub = 'Access your passenger account';

require __DIR__ . '/../partials/header.php';
?>

<section class="card card-narrow">

    <h3 class="card-title">Login</h3>

    <form method="post" action="index.php?page=login" id="loginForm" novalidate>
        <?php csrf_field(); ?>

        <div class="form-group">
            <label for="loginEmail">Email</label>
            <input type="email" name="email" value="<?= $_COOKIE['remember_email'] ?? '' ?>"
                placeholder="Enter your email">
        </div>

        <div class="form-group">
            <label for="loginPassword">Password</label>
            <input type="password" id="loginPassword" name="password" placeholder="Enter your password" required>
        </div>

        <div class="form-row-inline">
            <label class="checkbox-inline">
                <input type="checkbox" name="remember" value="1">
                Remember me
            </label>

            <a href="index.php?page=forgot">Forgot password?</a>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Login</button>

    </form>

    <p class="form-footnote">
        Don't have an account?
        <a href="index.php?page=register">Register</a>
    </p>

</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>