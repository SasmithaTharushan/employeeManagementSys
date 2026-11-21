<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (employee_user()) {
    redirect('employee/dashboard.php');
}

$error = '';
$email = '';
if (is_post()) {
    verify_csrf();
    $email = input('email');
    if (($wait = login_lock_remaining()) > 0) {
        $error = "Too many failed attempts. Try again in $wait seconds.";
    } elseif (!attempt_login('employee', $email, (string) ($_POST['password'] ?? ''))) {
        $error = 'The email or password is incorrect.';
    } else {
        redirect('employee/dashboard.php');
    }
}

render_login('employee', $error, $email);
