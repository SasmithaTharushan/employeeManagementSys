<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

$run = one('SELECT * FROM payroll_runs WHERE id = ?', [(int) input('id')]);
if (!$run) {
    flash('danger', 'That payroll run does not exist.');
    redirect('admin/payroll.php');
}
$runId = (int) $run['id'];
$isDraft = $run['status'] === 'draft';

if (is_post()) {
    verify_csrf();
    $action = input('action');

    if (!$isDraft) {
        flash('danger', 'This payroll is finalized and can no longer be changed.');
        redirect('admin/payroll-run.php?id=' . $runId);
    }

    if ($action === 'delete') {
        q('DELETE FROM payroll_runs WHERE id = ?', [$runId]);
        flash('success', 'Draft payroll for ' . period_label($run['period']) . ' was deleted.');
        redirect('admin/payroll.php');
    }

    if ($action === 'refresh') {
        // Rebuild every payslip from current employee salaries, keeping the admin's adjustments.
        $old = [];
        foreach (all('SELECT employee_id, ot_hours, other_deductions FROM payslips WHERE run_id = ?', [$runId]) as $s) {
            $old[(int) $s['employee_id']] = $s;
        }
        q('DELETE FROM payslips WHERE run_id = ?', [$runId]);
        $monthEnd = date('Y-m-t', strtotime($run['period'] . '-01'));
        foreach (all("SELECT * FROM employees WHERE status = 'active' AND (join_date IS NULL OR join_date <= ?)", [$monthEnd]) as $emp) {
            save_payslip($runId, $emp, [
                'ot_hours' => $old[(int) $emp['id']]['ot_hours'] ?? 0,
                'other_deductions' => $old[(int) $emp['id']]['other_deductions'] ?? 0,
                'nopay_days' => nopay_days_in_period((int) $emp['id'], $run['period']),
            ]);
        }
        flash('success', 'Payslips were refreshed from the latest employee salaries and approved leave.');
        redirect('admin/payroll-run.php?id=' . $runId);
    }

    // "save" and "finalize" both apply the edited values first.
    $rows = $_POST['rows'] ?? [];
    if (is_array($rows)) {
        foreach ($rows as $slipId => $vals) {
            $slip = one('SELECT * FROM payslips WHERE id = ? AND run_id = ?', [(int) $slipId, $runId]);
            if (!$slip || !is_array($vals)) {
                continue;
            }
            $num = fn(string $k) => max(0.0, (float) ($vals[$k] ?? 0));
            recalculate_payslip($slip, [
                'ot_hours' => min($num('ot_hours'), 200),
                'nopay_days' => min($num('nopay_days'), 31),
                'other_deductions' => $num('other_deductions'),
            ]);
        }
    }

    if ($action === 'finalize') {
        update('payroll_runs', ['status' => 'finalized', 'finalized_at' => date('Y-m-d H:i:s')], $runId);
        flash('success', 'Payroll for ' . period_label($run['period']) . ' is finalized. Employees can now see their payslips.');
    } else {
        flash('success', 'Changes saved and payslips recalculated.');
    }
    redirect('admin/payroll-run.php?id=' . $runId);
}

$slips = all(
    'SELECT p.*, e.name, e.emp_no, e.designation FROM payslips p
       JOIN employees e ON e.id = p.employee_id
      WHERE p.run_id = ? ORDER BY e.emp_no',
    [$runId]
);
$t = run_totals($runId);

render_header(
    'Payroll · ' . period_label($run['period']),
    'admin',
    'payroll',
    $isDraft ? 'Draft: adjust overtime, no-pay days or deductions, save, then finalize.' : 'Finalized on ' . fmt_date($run['finalized_at']) . '. Employees can see these payslips.'
);
?>
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3"><?= stat_tile('bi-people', 'Employees paid', (string) $t['employees'], 'primary') ?></div>
    <div class="col-sm-6 col-xl-3"><?= stat_tile('bi-wallet2', 'Net pay to banks', money($t['net_pay']), 'blue', 'Gross ' . money($t['gross_pay'])) ?></div>
    <div class="col-sm-6 col-xl-3"><?= stat_tile('bi-bank', 'EPF (20%) + ETF (3%)', money($t['epf_employee'] + $t['epf_employer'] + $t['etf_employer']), 'rose', 'EPF ' . money($t['epf_employee'] + $t['epf_employer']) . ' · ETF ' . money($t['etf_employer'])) ?></div>
    <div class="col-sm-6 col-xl-3"><?= stat_tile('bi-percent', 'APIT to IRD', money($t['apit']), 'amber') ?></div>
</div>

<form method="post" id="runForm">
    <?= csrf_field() ?>
    <div class="card-app">
        <div class="card-header-app">
            <h2><?= status_badge($run['status']) ?> <span class="ms-1">Payslips</span></h2>
            <div class="d-flex flex-wrap gap-2 no-print">
                <?php if ($isDraft): ?>
                    <button class="btn btn-outline-secondary" name="action" value="refresh" formnovalidate title="Reload salaries and no-pay leave from employee records"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
                    <button class="btn btn-outline-primary" name="action" value="save"><i class="bi bi-calculator"></i> Save &amp; recalculate</button>
                    <button class="btn btn-primary" name="action" value="finalize" data-confirm-click="Finalize payroll for <?= e(period_label($run['period'])) ?>? It can't be edited afterwards."><i class="bi bi-lock"></i> Finalize</button>
                <?php else: ?>
                    <a class="btn btn-outline-primary" href="<?= e(url('admin/epf-report.php?run=' . $runId)) ?>"><i class="bi bi-file-earmark-bar-graph"></i> EPF / ETF / APIT report</a>
                    <a class="btn btn-outline-primary" href="<?= e(url('admin/epf-report.php?run=' . $runId . '&format=bank')) ?>"><i class="bi bi-download"></i> Bank transfer CSV</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-app table-dense">
                <thead>
                <tr>
                    <th>Employee</th>
                    <th class="num">Basic + allow.</th>
                    <th class="num">OT hours</th>
                    <th class="num">OT pay</th>
                    <th class="num">No-pay days</th>
                    <th class="num">Gross</th>
                    <th class="num">EPF 8%</th>
                    <th class="num">APIT</th>
                    <th class="num">Other ded.</th>
                    <th class="num">Net pay</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($slips as $s): $sid = (int) $s['id']; ?>
                    <tr>
                        <td><div class="person"><span class="avatar"><?= e(initials($s['name'])) ?></span><div><?= e($s['name']) ?><small><?= e($s['emp_no']) ?></small></div></div></td>
                        <td class="num"><?= money($s['basic'] + $s['fixed_allowance'] + $s['other_allowance'], false) ?></td>
                        <?php if ($isDraft): ?>
                            <td class="num"><input class="form-control form-control-sm input-compact" type="number" min="0" max="200" step="0.5" name="rows[<?= $sid ?>][ot_hours]" value="<?= (float) $s['ot_hours'] ?>"></td>
                            <td class="num"><?= money($s['ot_pay'], false) ?></td>
                            <td class="num"><input class="form-control form-control-sm input-compact" type="number" min="0" max="31" step="0.5" name="rows[<?= $sid ?>][nopay_days]" value="<?= (float) $s['nopay_days'] ?>"></td>
                        <?php else: ?>
                            <td class="num"><?= (float) $s['ot_hours'] ?></td>
                            <td class="num"><?= money($s['ot_pay'], false) ?></td>
                            <td class="num"><?= (float) $s['nopay_days'] ?></td>
                        <?php endif; ?>
                        <td class="num"><?= money($s['gross_pay'], false) ?></td>
                        <td class="num"><?= money($s['epf_employee'], false) ?></td>
                        <td class="num"><?= money($s['apit'], false) ?></td>
                        <?php if ($isDraft): ?>
                            <td class="num"><input class="form-control form-control-sm input-compact" type="number" min="0" step="0.01" name="rows[<?= $sid ?>][other_deductions]" value="<?= (float) $s['other_deductions'] ?>"></td>
                        <?php else: ?>
                            <td class="num"><?= money($s['other_deductions'], false) ?></td>
                        <?php endif; ?>
                        <td class="num fw-bold"><?= money($s['net_pay'], false) ?></td>
                        <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?= e(url('admin/payslip.php?id=' . $sid)) ?>" title="View payslip"><i class="bi bi-receipt"></i></a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                <tr>
                    <td>Total</td>
                    <td></td><td></td>
                    <td class="num"><?= money($t['ot_pay'], false) ?></td>
                    <td></td>
                    <td class="num"><?= money($t['gross_pay'], false) ?></td>
                    <td class="num"><?= money($t['epf_employee'], false) ?></td>
                    <td class="num"><?= money($t['apit'], false) ?></td>
                    <td></td>
                    <td class="num"><?= money($t['net_pay'], false) ?></td>
                    <td></td>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
</form>

<?php if ($isDraft): ?>
    <form method="post" class="mt-3 text-end" data-confirm="Delete this draft payroll?">
        <?= csrf_field() ?>
        <button class="btn btn-link text-danger" name="action" value="delete"><i class="bi bi-trash"></i> Delete draft</button>
    </form>
<?php endif; ?>

<?php ob_start(); ?>
<script>
document.querySelectorAll('[data-confirm-click]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
        if (!confirm(btn.getAttribute('data-confirm-click'))) { e.preventDefault(); }
    });
});
</script>
<?php render_footer([], ob_get_clean()); ?>
