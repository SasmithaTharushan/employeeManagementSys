<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

if (is_post()) {
    verify_csrf();
    $id = (int) input('id');
    $emp = one('SELECT id, name FROM employees WHERE id = ?', [$id]);
    if ($emp && input('action') === 'remove') {
        $hasPayroll = (int) scalar(
            "SELECT COUNT(*) FROM payslips p JOIN payroll_runs r ON r.id = p.run_id
              WHERE p.employee_id = ? AND r.status = 'finalized'",
            [$id]
        );
        if ($hasPayroll) {
            // Keep payroll history intact: mark as resigned instead of deleting.
            update('employees', ['status' => 'resigned'], $id);
            q("DELETE FROM payslips WHERE employee_id = ? AND run_id IN (SELECT id FROM payroll_runs WHERE status = 'draft')", [$id]);
            flash('success', "{$emp['name']} was marked as resigned. Their past payslips are kept.");
        } else {
            q('DELETE FROM employees WHERE id = ?', [$id]);
            flash('success', "{$emp['name']} was deleted.");
        }
    } elseif ($emp && input('action') === 'reactivate') {
        update('employees', ['status' => 'active'], $id);
        flash('success', "{$emp['name']} is active again.");
    }
    redirect('admin/employees.php');
}

$status = in_array(input('status'), ['active', 'resigned', 'all'], true) ? input('status') : 'active';
$where = $status === 'all' ? '' : 'WHERE e.status = ?';
$employees = all(
    "SELECT e.*, d.name AS department FROM employees e
       LEFT JOIN departments d ON d.id = e.department_id
       $where ORDER BY e.emp_no",
    $status === 'all' ? [] : [$status]
);
$counts = [
    'active' => (int) scalar("SELECT COUNT(*) FROM employees WHERE status = 'active'"),
    'resigned' => (int) scalar("SELECT COUNT(*) FROM employees WHERE status = 'resigned'"),
];
$counts['all'] = $counts['active'] + $counts['resigned'];

render_header('Employees', 'admin', 'employees', 'Everyone on the payroll, with their salary structure.');
?>
<div class="card-app">
    <div class="card-header-app">
        <ul class="nav nav-pills small">
            <?php foreach (['active' => 'Active', 'resigned' => 'Resigned', 'all' => 'All'] as $key => $label): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $status === $key ? 'active' : '' ?>" href="?status=<?= $key ?>"><?= $label ?> <span class="opacity-75">(<?= $counts[$key] ?>)</span></a>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="d-flex gap-2">
            <input class="form-control" type="search" placeholder="Search name, NIC, department…" data-filter="#empTable" style="min-width:240px">
            <a class="btn btn-primary text-nowrap" href="<?= e(url('admin/employee-form.php')) ?>"><i class="bi bi-plus-lg"></i> Add employee</a>
        </div>
    </div>
    <?php if ($employees): ?>
        <div class="table-responsive">
            <table class="table table-app" id="empTable">
                <thead>
                <tr><th>Employee</th><th>Emp. no</th><th>Department</th><th>NIC</th><th class="num">Basic salary</th><th>Status</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                <?php foreach ($employees as $emp): ?>
                    <tr>
                        <td><div class="person"><span class="avatar"><?= e(initials($emp['name'])) ?></span><div><?= e($emp['name']) ?><small><?= e($emp['designation']) ?> · <?= e($emp['email']) ?></small></div></div></td>
                        <td><?= e($emp['emp_no']) ?></td>
                        <td><?= e($emp['department'] ?? '—') ?></td>
                        <td><?= e($emp['nic'] ?: '—') ?></td>
                        <td class="num"><?= money($emp['basic_salary']) ?></td>
                        <td><?= status_badge($emp['status']) ?></td>
                        <td class="text-end text-nowrap">
                            <a class="btn btn-sm btn-outline-primary" href="<?= e(url('admin/employee-form.php?id=' . (int) $emp['id'])) ?>"><i class="bi bi-pencil"></i> Edit</a>
                            <form method="post" class="d-inline" <?= $emp['status'] === 'active' ? 'data-confirm="Remove ' . e($emp['name']) . '? Employees with finalized payslips are marked as resigned instead of deleted."' : '' ?>>
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $emp['id'] ?>">
                                <?php if ($emp['status'] === 'active'): ?>
                                    <button class="btn btn-sm btn-outline-danger" name="action" value="remove" aria-label="Remove"><i class="bi bi-trash"></i></button>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-outline-secondary" name="action" value="reactivate">Reactivate</button>
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state"><i class="bi bi-people"></i>No employees here yet.</div>
    <?php endif; ?>
</div>
<?php render_footer(); ?>
