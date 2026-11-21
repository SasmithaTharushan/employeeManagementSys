<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$me = require_admin();

$form = ['name' => '', 'email' => ''];
$error = '';

if (is_post()) {
    verify_csrf();
    if (input('action') === 'delete') {
        $id = (int) input('id');
        if ($id === (int) $me['id']) {
            flash('danger', 'You cannot delete your own account.');
        } elseif ((int) scalar('SELECT COUNT(*) FROM admins') <= 1) {
            flash('danger', 'There must be at least one admin.');
        } else {
            q('DELETE FROM admins WHERE id = ?', [$id]);
            flash('success', 'Admin removed.');
        }
        redirect('admin/admins.php');
    }

    $form['name'] = input('name');
    $form['email'] = mb_strtolower(input('email'));
    $password = (string) ($_POST['password'] ?? '');
    if ($form['name'] === '') {
        $error = 'Enter a name.';
    } elseif (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'The password must be at least 8 characters.';
    } elseif (scalar('SELECT 1 FROM admins WHERE email = ?', [$form['email']])) {
        $error = 'An admin with this email already exists.';
    } else {
        insert('admins', [
            'name' => $form['name'],
            'email' => $form['email'],
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        flash('success', "{$form['name']} can now sign in as an admin.");
        redirect('admin/admins.php');
    }
}

$admins = all('SELECT id, name, email, created_at FROM admins ORDER BY name');

render_header('Admins', 'admin', 'admins', 'People who can manage employees and run payroll.');
?>
<div class="row g-3">
    <div class="col-lg-8">
        <div class="card-app">
            <div class="table-responsive">
                <table class="table table-app">
                    <thead><tr><th>Admin</th><th>Added</th><th class="text-end">Action</th></tr></thead>
                    <tbody>
                    <?php foreach ($admins as $a): ?>
                        <tr>
                            <td><div class="person"><span class="avatar"><?= e(initials($a['name'])) ?></span><div><?= e($a['name']) ?><?= (int) $a['id'] === (int) $me['id'] ? ' <span class="badge badge-soft badge-success">You</span>' : '' ?><small><?= e($a['email']) ?></small></div></div></td>
                            <td><?= e(fmt_date($a['created_at'])) ?></td>
                            <td class="text-end">
                                <?php if ((int) $a['id'] !== (int) $me['id']): ?>
                                    <form method="post" data-confirm="Remove <?= e($a['name']) ?> as an admin?">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" name="action" value="delete" aria-label="Remove"><i class="bi bi-trash"></i></button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card-app">
            <div class="card-header-app"><h2>Add an admin</h2></div>
            <form method="post" class="card-body-app" novalidate>
                <?= csrf_field() ?>
                <?php if ($error): ?><div class="alert alert-danger py-2"><?= e($error) ?></div><?php endif; ?>
                <label class="form-label">Name</label>
                <input class="form-control mb-3" name="name" value="<?= e($form['name']) ?>" required>
                <label class="form-label">Email</label>
                <input class="form-control mb-3" type="email" name="email" value="<?= e($form['email']) ?>" required>
                <label class="form-label">Password</label>
                <input class="form-control mb-3" type="password" name="password" minlength="8" autocomplete="new-password" required>
                <button class="btn btn-primary w-100" name="action" value="add">Add admin</button>
            </form>
        </div>
    </div>
</div>
<?php render_footer(); ?>
