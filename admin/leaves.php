<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

if (is_post()) {
    verify_csrf();
    $leave = one("SELECT l.*, e.name FROM leaves l JOIN employees e ON e.id = l.employee_id WHERE l.id = ?", [(int) input('id')]);
    $action = input('action');
    if ($leave && $leave['status'] === 'pending' && in_array($action, ['approve', 'reject'], true)) {
        $status = $action === 'approve' ? 'approved' : 'rejected';
        update('leaves', ['status' => $status], (int) $leave['id']);
        $note = '';
        if ($status === 'approved' && $leave['leave_type'] === 'nopay') {
            $note = ' It will be deducted automatically in that month\'s payroll.';
        }
        flash('success', "{$leave['name']}'s leave was $status.$note");
    }
    $back = $_SERVER['HTTP_REFERER'] ?? '';
    if ($back && str_contains($back, '/admin/dashboard.php')) {
        redirect('admin/dashboard.php');
    }
    redirect('admin/leaves.php?status=' . urlencode(input('status', 'pending')));
}

$statuses = ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'cancelled' => 'Cancelled', 'all' => 'All'];
$status = array_key_exists(input('status'), $statuses) ? input('status') : 'pending';

$leaves = all(
    "SELECT l.*, e.name, e.emp_no, e.designation FROM leaves l
       JOIN employees e ON e.id = l.employee_id
      " . ($status === 'all' ? '' : 'WHERE l.status = ?') . "
      ORDER BY l.start_date DESC",
    $status === 'all' ? [] : [$status]
);

render_header('Leave requests', 'admin', 'leaves', 'Approved no-pay leave is deducted automatically when you run payroll.');
?>
<div class="card-app">
    <div class="card-header-app">
        <ul class="nav nav-pills small">
            <?php foreach ($statuses as $key => $label): ?>
                <li class="nav-item"><a class="nav-link <?= $status === $key ? 'active' : '' ?>" href="?status=<?= $key ?>"><?= $label ?></a></li>
            <?php endforeach; ?>
        </ul>
        <input class="form-control" type="search" placeholder="Search…" data-filter="#leaveTable" style="max-width:240px">
    </div>
    <?php if ($leaves): ?>
        <div class="table-responsive">
            <table class="table table-app" id="leaveTable">
                <thead><tr><th>Employee</th><th>Type</th><th>From</th><th>To</th><th class="num">Days</th><th>Reason</th><th>Status</th><th class="text-end">Action</th></tr></thead>
                <tbody>
                <?php foreach ($leaves as $l): ?>
                    <tr>
                        <td><div class="person"><span class="avatar"><?= e(initials($l['name'])) ?></span><div><?= e($l['name']) ?><small><?= e($l['emp_no']) ?> · <?= e($l['designation']) ?></small></div></div></td>
                        <td><?= e(leave_types()[$l['leave_type']] ?? $l['leave_type']) ?></td>
                        <td class="text-nowrap"><?= e(fmt_date($l['start_date'])) ?></td>
                        <td class="text-nowrap"><?= e(fmt_date($l['end_date'])) ?></td>
                        <td class="num"><?= (float) $l['days'] ?></td>
                        <td style="max-width:260px"><?= e($l['reason']) ?></td>
                        <td><?= status_badge($l['status']) ?></td>
                        <td class="text-end text-nowrap">
                            <?php if ($l['status'] === 'pending'): ?>
                                <form method="post" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $l['id'] ?>">
                                    <input type="hidden" name="status" value="<?= e($status) ?>">
                                    <button class="btn btn-sm btn-primary" name="action" value="approve">Approve</button>
                                    <button class="btn btn-sm btn-outline-danger" name="action" value="reject">Reject</button>
                                </form>
                            <?php else: ?>
                                <span class="text-body-secondary small">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state"><i class="bi bi-calendar2-check"></i>No <?= $status === 'all' ? '' : e($status) . ' ' ?>leave requests.</div>
    <?php endif; ?>
</div>
<?php render_footer(); ?>
