<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (admin_user()) {
    redirect('admin/dashboard.php');
}

$error = '';
$email = '';
if (is_post()) {
    verify_csrf();
    $email = input('email');
    if (($wait = login_lock_remaining()) > 0) {
        $error = "Too many failed attempts. Try again in $wait seconds.";
    } elseif (!attempt_login('admin', $email, (string) ($_POST['password'] ?? ''))) {
        $error = 'The email or password is incorrect.';
    } else {
        redirect('admin/dashboard.php');
    }
}

render_login('admin', $error, $email);
