<?php

$token = $_GET['token'] ?? '';

$pageTitle   = 'Forgot Password';
$pageHeading = $token ? 'Set a New Password' : 'Forgot Password';
$pageSub     = $token ? 'Choose a new password for your account' : 'Enter your email to get a reset link';

require __DIR__ . '/../partials/header.php';
?>

<section class="card card-narrow">

    <?php if ($token): ?>

        <h3 class="card-title">Set a New Password</h3>

        <form method="post" action="index.php?page=forgot" novalidate>
            <?php csrf_field(); ?>
            <input type="hidden" name="token" value="<?= esc($token) ?>">

            <div class="form-group">
                <label for="newPassword">New Password</label>
                <input id="newPassword" name="password" type="password" placeholder="At least 6 characters" required>
            </div>

            <div class="form-group">
                <label for="confirmNewPassword">Confirm New Password</label>
                <input id="confirmNewPassword" name="confirm_password" type="password" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Update Password</button>

        </form>

    <?php else: ?>

        <h3 class="card-title">Forgot Password</h3>
        <p class="card-note">Enter the email on your account and we'll help you reset your password.</p>

        <form method="post" action="index.php?page=forgot" novalidate>
            <?php csrf_field(); ?>

            <div class="form-group">
                <label for="forgotEmail">Email</label>
                <input id="forgotEmail" name="email" type="email" placeholder="you@example.com" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Send Reset Link</button>

        </form>

    <?php endif; ?>

    <p class="form-footnote">
        <a href="index.php?page=login">Back to login</a>
    </p>

</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
