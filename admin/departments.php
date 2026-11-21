<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

if (is_post()) {
    verify_csrf();
    $action = input('action');
    $name = input('name');
    $id = (int) input('id');

    if (in_array($action, ['add', 'rename'], true)) {
        if ($name === '' || mb_strlen($name) > 100) {
            flash('danger', 'Enter a department name (up to 100 characters).');
        } elseif (scalar('SELECT 1 FROM departments WHERE name = ? AND id <> ?', [$name, $id])) {
            flash('danger', "There is already a department called \"$name\".");
        } elseif ($action === 'add') {
            insert('departments', ['name' => $name]);
            flash('success', "Added the $name department.");
        } else {
            update('departments', ['name' => $name], $id);
            flash('success', 'Department renamed.');
        }
    } elseif ($action === 'delete') {
        // Employees in the department become "Unassigned" (ON DELETE SET NULL).
        q('UPDATE employees SET department_id = NULL WHERE department_id = ?', [$id]);
        q('DELETE FROM departments WHERE id = ?', [$id]);
        flash('success', 'Department deleted. Its employees are now unassigned.');
    }
    redirect('admin/departments.php');
}

$departments = all(
    "SELECT d.id, d.name,
            COUNT(e.id) AS headcount,
            COALESCE(SUM(e.basic_salary + e.fixed_allowance + e.other_allowance), 0) AS monthly_gross
       FROM departments d
       LEFT JOIN employees e ON e.department_id = d.id AND e.status = 'active'
      GROUP BY d.id, d.name ORDER BY d.name"
);

render_header('Departments', 'admin', 'departments', 'Group employees and see the monthly salary cost of each team.');
?>
<div class="row g-3">
    <div class="col-lg-8">
        <div class="card-app">
            <?php if ($departments): ?>
                <div class="table-responsive">
                    <table class="table table-app">
                        <thead><tr><th>Department</th><th class="num">Employees</th><th class="num">Monthly gross</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($departments as $d): ?>
                            <tr>
                                <td>
                                    <form method="post" class="d-flex gap-2">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
                                        <input class="form-control form-control-sm" name="name" value="<?= e($d['name']) ?>" aria-label="Department name">
                                        <button class="btn btn-sm btn-outline-primary" name="action" value="rename">Rename</button>
                                    </form>
                                </td>
                                <td class="num"><?= (int) $d['headcount'] ?></td>
                                <td class="num"><?= money($d['monthly_gross']) ?></td>
                                <td class="text-end">
                                    <form method="post" data-confirm="Delete <?= e($d['name']) ?>? Its employees will become unassigned.">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" name="action" value="delete" aria-label="Delete"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state"><i class="bi bi-diagram-3"></i>No departments yet. Add your first one.</div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card-app">
            <div class="card-header-app"><h2>Add a department</h2></div>
            <form method="post" class="card-body-app">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add">
                <label class="form-label">Name</label>
                <input class="form-control mb-3" name="name" placeholder="e.g. Customer Success" required>
                <button class="btn btn-primary w-100">Add department</button>
            </form>
        </div>
    </div>
</div>
<?php render_footer(); ?>
