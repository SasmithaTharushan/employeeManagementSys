<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$me = require_employee();
$id = (int) $me['id'];

$entitlements = payroll_rates()['leave_entitlements'];
$form = ['leave_type' => 'annual', 'start_date' => '', 'end_date' => '', 'reason' => ''];
$error = '';

if (is_post()) {
    verify_csrf();

    if (input('action') === 'cancel') {
        $leave = one("SELECT id FROM leaves WHERE id = ? AND employee_id = ? AND status = 'pending'", [(int) input('id'), $id]);
        if ($leave) {
            update('leaves', ['status' => 'cancelled'], (int) $leave['id']);
            flash('success', 'Your leave request was cancelled.');
        }
        redirect('employee/leave.php');
    }

    foreach ($form as $k => $_) {
        $form[$k] = input($k);
    }
    if ($form['end_date'] === '') {
        $form['end_date'] = $form['start_date'];
    }

    if (!array_key_exists($form['leave_type'], leave_types())) {
        $error = 'Choose a leave type.';
    } elseif (!valid_date($form['start_date']) || !valid_date($form['end_date'])) {
        $error = 'Choose valid dates.';
    } elseif ($form['end_date'] < $form['start_date']) {
        $error = 'The last day cannot be before the first day.';
    } elseif ($form['start_date'] < date('Y-m-d', strtotime('-30 days'))) {
        $error = 'Leave can only be applied for up to 30 days back.';
    } elseif (substr($form['start_date'], 0, 4) !== substr($form['end_date'], 0, 4)) {
        $error = 'Split leave that crosses the new year into two requests.';
    } elseif ($form['reason'] === '' || mb_strlen($form['reason']) > 500) {
        $error = 'Give a short reason (up to 500 characters).';
    } else {
        $days = days_between($form['start_date'], $form['end_date']);
        $overlap = scalar(
            "SELECT 1 FROM leaves WHERE employee_id = ? AND status IN ('pending', 'approved')
               AND start_date <= ? AND end_date >= ?",
            [$id, $form['end_date'], $form['start_date']]
        );
        $type = $form['leave_type'];
        $usedNow = leave_used($id, (int) substr($form['start_date'], 0, 4));
        if ($overlap) {
            $error = 'You already have leave on some of these days.';
        } elseif (isset($entitlements[$type]) && $usedNow[$type] + $days > $entitlements[$type]) {
            $left = max(0, $entitlements[$type] - $usedNow[$type]);
            $error = "You only have $left " . strtolower(leave_types()[$type]) . " leave day(s) left this year. Apply for no-pay leave for the rest.";
        } else {
            insert('leaves', [
                'employee_id' => $id,
                'leave_type' => $type,
                'start_date' => $form['start_date'],
                'end_date' => $form['end_date'],
                'days' => $days,
                'reason' => $form['reason'],
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            flash('success', "Leave request sent for $days day(s). You'll see the status here once it's reviewed.");
            redirect('employee/leave.php');
        }
    }
}

$used = leave_used($id, (int) date('Y'));
$history = all('SELECT * FROM leaves WHERE employee_id = ? ORDER BY start_date DESC', [$id]);

render_header('Leave', 'employee', 'leave', 'Apply for leave and track your requests.');
?>
<div class="row g-3">
    <div class="col-xl-4 d-grid gap-3 align-content-start">
        <div class="card-app">
            <div class="card-header-app"><h2>Apply for leave</h2></div>
            <form method="post" class="card-body-app" novalidate>
                <?= csrf_field() ?>
                <?php if ($error): ?><div class="alert alert-danger py-2"><?= e($error) ?></div><?php endif; ?>
                <label class="form-label">Type</label>
                <select class="form-select mb-3" name="leave_type">
                    <?php foreach (leave_types() as $key => $label): ?>
                        <option value="<?= $key ?>" <?= $form['leave_type'] === $key ? 'selected' : '' ?>>
                            <?= e($label) ?><?= isset($entitlements[$key]) ? ' (' . max(0, $entitlements[$key] - $used[$key]) . ' left)' : ' (deducted from salary)' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label">First day</label>
                        <input class="form-control" type="date" name="start_date" value="<?= e($form['start_date']) ?>" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Last day</label>
                        <input class="form-control" type="date" name="end_date" value="<?= e($form['end_date']) ?>">
                    </div>
                </div>
                <label class="form-label">Reason</label>
                <textarea class="form-control mb-3" name="reason" rows="3" maxlength="500" required><?= e($form['reason']) ?></textarea>
                <button class="btn btn-primary w-100">Send request</button>
            </form>
        </div>
        <div class="card-app">
            <div class="card-header-app"><h2>Balance <?= date('Y') ?></h2></div>
            <div class="card-body-app">
                <?php foreach ($entitlements as $type => $total): $left = max(0, $total - $used[$type]); ?>
                    <div class="balance">
                        <div class="balance-top"><span><?= e(leave_types()[$type]) ?></span><span class="fw-semibold"><?= $left ?> / <?= $total ?></span></div>
                        <div class="progress"><div class="progress-bar" style="width: <?= $total ? round($left / $total * 100) : 0 ?>%"></div></div>
                    </div>
                <?php endforeach; ?>
                <p class="small text-body-secondary mb-0">Pending requests count against your balance until they're decided.</p>
            </div>
        </div>
    </div>
    <div class="col-xl-8">
        <div class="card-app">
            <div class="card-header-app"><h2>My requests</h2></div>
            <?php if ($history): ?>
                <div class="table-responsive">
                    <table class="table table-app">
                        <thead><tr><th>Type</th><th>Dates</th><th class="num">Days</th><th>Reason</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($history as $l): ?>
                            <tr>
                                <td><?= e(leave_types()[$l['leave_type']] ?? $l['leave_type']) ?></td>
                                <td class="text-nowrap"><?= e(fmt_date($l['start_date'])) ?><?= $l['end_date'] !== $l['start_date'] ? ' – ' . e(fmt_date($l['end_date'])) : '' ?></td>
                                <td class="num"><?= (float) $l['days'] ?></td>
                                <td><?= e($l['reason']) ?></td>
                                <td><?= status_badge($l['status']) ?></td>
                                <td class="text-end">
                                    <?php if ($l['status'] === 'pending'): ?>
                                        <form method="post" data-confirm="Cancel this leave request?">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int) $l['id'] ?>">
                                            <button class="btn btn-sm btn-outline-danger" name="action" value="cancel">Cancel</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state"><i class="bi bi-calendar2"></i>You haven't applied for any leave yet.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php render_footer(); ?>
