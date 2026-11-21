<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$me = require_employee();

$error = '';
if (is_post()) {
    verify_csrf();
    $error = change_password('employees', (int) $me['id'], (string) ($_POST['current'] ?? ''), (string) ($_POST['new'] ?? ''), (string) ($_POST['confirm'] ?? '')) ?? '';
    if (!$error) {
        flash('success', 'Your password was changed.');
        redirect('employee/profile.php');
    }
}

$details = [
    'Employee no' => $me['emp_no'],
    'Email' => $me['email'],
    'NIC' => $me['nic'],
    'EPF member no' => $me['epf_no'],
    'Date of birth' => $me['dob'] ? fmt_date($me['dob']) : null,
    'Gender' => $me['gender'],
    'Phone' => $me['phone'],
    'Department' => $me['department'],
    'Designation' => $me['designation'],
    'Joined' => $me['join_date'] ? fmt_date($me['join_date']) : null,
    'Bank' => trim(($me['bank_name'] ?? '') . ' ' . ($me['bank_account'] ?? '')),
];

render_header('My profile', 'employee', 'profile', 'Ask HR if any of these details need to change.');
?>
<div class="row g-3">
    <div class="col-lg-7">
        <div class="card-app">
            <div class="card-body-app d-flex align-items-center gap-3 border-bottom">
                <span class="avatar avatar-lg"><?= e(initials($me['name'])) ?></span>
                <div>
                    <h2 class="h4 fw-bold mb-0"><?= e($me['name']) ?></h2>
                    <div class="text-body-secondary"><?= e($me['designation'] ?: 'Employee') ?></div>
                </div>
            </div>
            <dl class="row card-body-app m-0">
                <?php foreach ($details as $label => $value): ?>
                    <dt class="col-sm-4 text-body-secondary fw-semibold"><?= e($label) ?></dt>
                    <dd class="col-sm-8"><?= e($value ?: '—') ?></dd>
                <?php endforeach; ?>
            </dl>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card-app">
            <div class="card-header-app"><h2>Change password</h2></div>
            <form method="post" class="card-body-app">
                <?= csrf_field() ?>
                <?php if ($error): ?><div class="alert alert-danger py-2"><?= e($error) ?></div><?php endif; ?>
                <label class="form-label">Current password</label>
                <input class="form-control mb-3" type="password" name="current" autocomplete="current-password" required>
                <label class="form-label">New password</label>
                <input class="form-control mb-3" type="password" name="new" minlength="8" autocomplete="new-password" required>
                <label class="form-label">Confirm new password</label>
                <input class="form-control mb-3" type="password" name="confirm" minlength="8" autocomplete="new-password" required>
                <button class="btn btn-primary">Change password</button>
            </form>
        </div>
    </div>
</div>
<?php render_footer(); ?>
