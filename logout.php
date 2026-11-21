<?php
require_once __DIR__ . '/includes/bootstrap.php';

$wasAdmin = !empty($_SESSION['admin_id']);
if (is_post()) {
    verify_csrf();
    logout();
}
redirect($wasAdmin ? 'admin/login.php' : 'employee/login.php');
