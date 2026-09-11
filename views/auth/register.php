<?php

$pageTitle   = 'Register';
$pageHeading = 'Create Account';
$pageSub     = 'Register as a ' . esc(APP_NAME) . ' passenger';

require __DIR__ . '/../partials/header.php';
?>

<section class="card card-narrow">

    <h3 class="card-title">Passenger Registration</h3>

    <form method="post" action="index.php?page=register" id="registerForm" novalidate>
        <?php csrf_field(); ?>

        <div class="form-group">
            <label for="regName">Name</label>
            <input id="regName" name="name" type="text" value="<?= esc(old('name')) ?>" required>
        </div>

        <div class="form-group">
            <label for="regEmail">Email</label>
            <input id="regEmail" name="email" type="email" value="<?= esc(old('email')) ?>" required>
        </div>

        <div class="form-group">
            <label for="regPhone">Phone</label>
            <input id="regPhone" name="phone" type="text" placeholder="11-digit phone number" value="<?= esc(old('phone')) ?>" required>
        </div>

        <div class="form-group">
            <label for="regPassword">Password</label>
            <input id="regPassword" name="password" type="password" placeholder="At least 6 characters" required>
        </div>

        <div class="form-group">
            <label for="regConfirmPassword">Confirm Password</label>
            <input id="regConfirmPassword" name="confirm_password" type="password" required>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Register</button>

    </form>

    <p class="form-footnote">
        Already have an account?
        <a href="index.php?page=login">Login</a>
    </p>

</section>

<?php
clear_old();
require __DIR__ . '/../partials/footer.php';
