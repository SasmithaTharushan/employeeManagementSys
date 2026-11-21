<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$me = require_employee();

$slips = all(
    "SELECT p.*, r.period FROM payslips p JOIN payroll_runs r ON r.id = p.run_id
      WHERE p.employee_id = ? AND r.status = 'finalized' ORDER BY r.period DESC",
    [(int) $me['id']]
);

render_header('My payslips', 'employee', 'payslips', 'Open any month to see the full breakdown or save it as a PDF.');
?>
<div class="card-app">
    <?php if ($slips): ?>
        <div class="table-responsive">
            <table class="table table-app">
                <thead><tr><th>Month</th><th class="num">Gross pay</th><th class="num">EPF 8%</th><th class="num">APIT</th><th class="num">Net pay</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($slips as $s): ?>
                    <tr>
                        <td class="fw-semibold"><?= e(period_label($s['period'])) ?></td>
                        <td class="num"><?= money($s['gross_pay']) ?></td>
                        <td class="num"><?= money($s['epf_employee']) ?></td>
                        <td class="num"><?= money($s['apit']) ?></td>
                        <td class="num fw-bold"><?= money($s['net_pay']) ?></td>
                        <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?= e(url('employee/payslip.php?id=' . (int) $s['id'])) ?>"><i class="bi bi-receipt"></i> View</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state"><i class="bi bi-receipt"></i>No payslips yet. They appear here once payroll is finalized.</div>
    <?php endif; ?>
</div>
<?php render_footer(); ?>
