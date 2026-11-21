<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$me = require_admin();

$error = '';
if (is_post()) {
    verify_csrf();
    if (input('action') === 'details') {
        $name = input('name');
        if ($name === '') {
            $error = 'Enter your name.';
        } else {
            update('admins', ['name' => $name], (int) $me['id']);
            flash('success', 'Your name was updated.');
            redirect('admin/profile.php');
        }
    } else {
        $error = change_password('admins', (int) $me['id'], (string) ($_POST['current'] ?? ''), (string) ($_POST['new'] ?? ''), (string) ($_POST['confirm'] ?? '')) ?? '';
        if (!$error) {
            flash('success', 'Your password was changed.');
            redirect('admin/profile.php');
        }
    }
}

render_header('My account', 'admin', 'profile');
?>
<div class="row g-3">
    <div class="col-lg-6">
        <div class="card-app">
            <div class="card-header-app"><h2>Details</h2></div>
            <form method="post" class="card-body-app">
                <?= csrf_field() ?>
                <label class="form-label">Name</label>
                <input class="form-control mb-3" name="name" value="<?= e($me['name']) ?>" required>
                <label class="form-label">Email</label>
                <input class="form-control mb-3" value="<?= e($me['email']) ?>" disabled>
                <button class="btn btn-primary" name="action" value="details">Save</button>
            </form>
        </div>
    </div>
    <div class="col-lg-6">
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
                <button class="btn btn-primary" name="action" value="password">Change password</button>
            </form>
        </div>
    </div>
</div>
<?php render_footer(); ?>
