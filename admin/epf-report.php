<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

$runs = all("SELECT id, period FROM payroll_runs WHERE status = 'finalized' ORDER BY period DESC");
$runId = (int) input('run', $runs ? (string) $runs[0]['id'] : '0');
$run = one("SELECT * FROM payroll_runs WHERE id = ? AND status = 'finalized'", [$runId]);

$rows = $run ? all(
    'SELECT p.*, e.name, e.emp_no, e.nic, e.epf_no, e.bank_name, e.bank_account
       FROM payslips p JOIN employees e ON e.id = p.employee_id
      WHERE p.run_id = ? ORDER BY e.epf_no, e.emp_no',
    [$runId]
) : [];

/** Stream rows as a CSV download. Cells that look like formulas are escaped. */
function send_csv(string $filename, array $header, array $lines): void
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel opens it correctly
    fputcsv($out, $header);
    foreach ($lines as $line) {
        fputcsv($out, array_map(fn($v) => is_string($v) && preg_match('/^[=+\-@]/', $v) ? "'" . $v : $v, $line));
    }
    fclose($out);
    exit;
}

$format = input('format');
if ($run && $format === 'epf') {
    send_csv("epf-etf-{$run['period']}.csv",
        ['EPF No', 'Employee No', 'Name', 'NIC', 'Total Earnings', 'Employee 8%', 'Employer 12%', 'Total EPF 20%', 'ETF 3%'],
        array_map(fn($r) => [$r['epf_no'], $r['emp_no'], $r['name'], $r['nic'], $r['epf_base'], $r['epf_employee'], $r['epf_employer'],
            round($r['epf_employee'] + $r['epf_employer'], 2), $r['etf_employer']], $rows));
}
if ($run && $format === 'apit') {
    send_csv("apit-{$run['period']}.csv",
        ['Employee No', 'Name', 'NIC', 'Gross Pay', 'APIT'],
        array_map(fn($r) => [$r['emp_no'], $r['name'], $r['nic'], $r['gross_pay'], $r['apit']], $rows));
}
if ($run && $format === 'bank') {
    send_csv("salary-transfers-{$run['period']}.csv",
        ['Employee No', 'Name', 'Bank', 'Account No', 'Net Pay'],
        array_map(fn($r) => [$r['emp_no'], $r['name'], $r['bank_name'], $r['bank_account'], $r['net_pay']], $rows));
}

$t = $run ? run_totals($runId) : null;

render_header('EPF / ETF / APIT', 'admin', 'reports', 'Monthly statutory figures to pay to the Central Bank (EPF), ETF Board and Inland Revenue (APIT).');
?>
<?php if (!$runs): ?>
    <div class="card-app empty-state"><i class="bi bi-file-earmark-bar-graph"></i>Finalize a payroll run to see statutory reports.</div>
<?php else: ?>
    <form class="d-flex flex-wrap gap-2 align-items-center mb-3 no-print" method="get">
        <label for="run" class="fw-semibold">Month</label>
        <select class="form-select w-auto" id="run" name="run" onchange="this.form.submit()">
            <?php foreach ($runs as $r): ?>
                <option value="<?= (int) $r['id'] ?>" <?= (int) $r['id'] === $runId ? 'selected' : '' ?>><?= e(period_label($r['period'])) ?></option>
            <?php endforeach; ?>
        </select>
        <div class="ms-auto d-flex flex-wrap gap-2">
            <a class="btn btn-outline-primary" href="?run=<?= $runId ?>&format=epf"><i class="bi bi-download"></i> EPF/ETF CSV</a>
            <a class="btn btn-outline-primary" href="?run=<?= $runId ?>&format=apit"><i class="bi bi-download"></i> APIT CSV</a>
            <a class="btn btn-outline-primary" href="?run=<?= $runId ?>&format=bank"><i class="bi bi-download"></i> Bank CSV</a>
            <button class="btn btn-primary" type="button" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
        </div>
    </form>

    <?php if ($run): ?>
        <div class="row g-3 mb-4">
            <div class="col-md-4"><?= stat_tile('bi-bank', 'EPF to pay (20%)', money($t['epf_employee'] + $t['epf_employer']), 'primary', '8% ' . money($t['epf_employee']) . ' + 12% ' . money($t['epf_employer'])) ?></div>
            <div class="col-md-4"><?= stat_tile('bi-piggy-bank', 'ETF to pay (3%)', money($t['etf_employer']), 'blue') ?></div>
            <div class="col-md-4"><?= stat_tile('bi-percent', 'APIT to pay', money($t['apit']), 'amber', 'Due to the IRD by the 15th of next month') ?></div>
        </div>

        <div class="card-app mb-3">
            <div class="card-header-app"><h2>EPF and ETF contributions · <?= e(period_label($run['period'])) ?></h2></div>
            <div class="table-responsive">
                <table class="table table-app table-dense">
                    <thead><tr><th>EPF no</th><th>Name</th><th>NIC</th><th class="num">Total earnings</th><th class="num">Employee 8%</th><th class="num">Employer 12%</th><th class="num">Total 20%</th><th class="num">ETF 3%</th></tr></thead>
                    <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= e($r['epf_no'] ?: '—') ?></td>
                            <td><?= e($r['name']) ?></td>
                            <td><?= e($r['nic'] ?: '—') ?></td>
                            <td class="num"><?= money($r['epf_base'], false) ?></td>
                            <td class="num"><?= money($r['epf_employee'], false) ?></td>
                            <td class="num"><?= money($r['epf_employer'], false) ?></td>
                            <td class="num fw-semibold"><?= money($r['epf_employee'] + $r['epf_employer'], false) ?></td>
                            <td class="num"><?= money($r['etf_employer'], false) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                    <tr>
                        <td colspan="3">Total (<?= count($rows) ?> members)</td>
                        <td class="num"><?= money($t['epf_base'], false) ?></td>
                        <td class="num"><?= money($t['epf_employee'], false) ?></td>
                        <td class="num"><?= money($t['epf_employer'], false) ?></td>
                        <td class="num"><?= money($t['epf_employee'] + $t['epf_employer'], false) ?></td>
                        <td class="num"><?= money($t['etf_employer'], false) ?></td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="card-app">
            <div class="card-header-app"><h2>APIT deducted · <?= e(period_label($run['period'])) ?></h2></div>
            <div class="table-responsive">
                <table class="table table-app table-dense">
                    <thead><tr><th>Employee no</th><th>Name</th><th>NIC</th><th class="num">Gross pay</th><th class="num">APIT</th></tr></thead>
                    <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= e($r['emp_no']) ?></td>
                            <td><?= e($r['name']) ?></td>
                            <td><?= e($r['nic'] ?: '—') ?></td>
                            <td class="num"><?= money($r['gross_pay'], false) ?></td>
                            <td class="num"><?= (float) $r['apit'] > 0 ? money($r['apit'], false) : '<span class="text-body-secondary">Below threshold</span>' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot><tr><td colspan="3">Total</td><td class="num"><?= money($t['gross_pay'], false) ?></td><td class="num"><?= money($t['apit'], false) ?></td></tr></tfoot>
                </table>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>
<?php render_footer(); ?>
