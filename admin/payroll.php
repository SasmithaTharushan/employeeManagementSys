<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$admin = require_admin();

$period = input('period', date('Y-m'));
$error = '';

if (is_post()) {
    verify_csrf();
    if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $period)) {
        $error = 'Choose a valid month.';
    } elseif (scalar('SELECT 1 FROM payroll_runs WHERE period = ?', [$period])) {
        $error = 'Payroll for ' . period_label($period) . ' already exists.';
    } elseif (!(int) scalar("SELECT COUNT(*) FROM employees WHERE status = 'active'")) {
        $error = 'Add at least one active employee first.';
    } else {
        $runId = create_payroll_run($period, (int) $admin['id']);
        flash('success', 'Draft payroll for ' . period_label($period) . ' is ready. Add overtime or deductions, then finalize.');
        redirect('admin/payroll-run.php?id=' . $runId);
    }
}

$runs = all('SELECT * FROM payroll_runs ORDER BY period DESC');
$rates = payroll_rates();

render_header('Payroll', 'admin', 'payroll', 'Run monthly payroll with EPF, ETF and APIT calculated for you.');
?>
<div class="row g-3">
    <div class="col-xl-8">
        <div class="card-app">
            <div class="card-header-app"><h2>Payroll runs</h2></div>
            <?php if ($runs): ?>
                <div class="table-responsive">
                    <table class="table table-app">
                        <thead><tr><th>Month</th><th>Status</th><th class="num">Employees</th><th class="num">Gross pay</th><th class="num">Net pay</th><th class="num">Employer cost</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($runs as $r): $t = run_totals((int) $r['id']); ?>
                            <tr>
                                <td class="fw-semibold"><?= e(period_label($r['period'])) ?></td>
                                <td><?= status_badge($r['status']) ?></td>
                                <td class="num"><?= (int) $t['employees'] ?></td>
                                <td class="num"><?= money($t['gross_pay']) ?></td>
                                <td class="num"><?= money($t['net_pay']) ?></td>
                                <td class="num"><?= money($t['gross_pay'] + $t['epf_employer'] + $t['etf_employer']) ?></td>
                                <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?= e(url('admin/payroll-run.php?id=' . (int) $r['id'])) ?>"><?= $r['status'] === 'draft' ? 'Continue' : 'Open' ?></a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state"><i class="bi bi-cash-stack"></i>No payroll has been run yet.</div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-xl-4 d-grid gap-3 align-content-start">
        <div class="card-app">
            <div class="card-header-app"><h2>Run payroll</h2></div>
            <form method="post" class="card-body-app">
                <?= csrf_field() ?>
                <?php if ($error): ?><div class="alert alert-danger py-2"><?= e($error) ?></div><?php endif; ?>
                <label class="form-label" for="period">Month</label>
                <input class="form-control mb-3" type="month" id="period" name="period" value="<?= e($period) ?>" required>
                <p class="small text-body-secondary">Creates a draft payslip for every active employee, including any approved no-pay leave in that month. Nothing is final until you finalize it.</p>
                <button class="btn btn-primary w-100"><i class="bi bi-play-fill"></i> Create draft payroll</button>
            </form>
        </div>
        <div class="card-app">
            <div class="card-header-app"><h2>Rates in use</h2><span class="small text-body-secondary">Y/A <?= e($rates['tax_year']) ?></span></div>
            <div class="card-body-app small">
                <table class="table table-sm mb-2">
                    <tr><td>EPF employee</td><td class="num"><?= $rates['epf_employee'] * 100 ?>%</td></tr>
                    <tr><td>EPF employer</td><td class="num"><?= $rates['epf_employer'] * 100 ?>%</td></tr>
                    <tr><td>ETF employer</td><td class="num"><?= $rates['etf_employer'] * 100 ?>%</td></tr>
                    <tr><td>Overtime</td><td class="num"><?= $rates['ot_multiplier'] ?>× (basic ÷ <?= $rates['ot_divisor'] ?>)</td></tr>
                    <tr><td>No-pay per day</td><td class="num">(basic + EPF allowance) ÷ <?= $rates['nopay_divisor'] ?></td></tr>
                    <tr><td>APIT tax-free</td><td class="num"><?= money($rates['apit']['relief']) ?> / month</td></tr>
                </table>
                <div class="text-body-secondary">
                    APIT bands after relief:
                    <?php
                    $parts = [];
                    foreach ($rates['apit']['bands'] as [$w, $r]) {
                        $parts[] = ($w === null ? 'balance' : 'next ' . number_format($w)) . ' @ ' . ($r * 100) . '%';
                    }
                    echo e(implode(', ', $parts));
                    ?>.
                    Change them in <code>includes/payroll.php</code> when the IRD publishes new tables.
                </div>
            </div>
        </div>
    </div>
</div>
<?php render_footer(); ?>
