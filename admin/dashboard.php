<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$admin = require_admin();

$today = date('Y-m-d');
$currentPeriod = date('Y-m');

$activeEmployees = (int) scalar("SELECT COUNT(*) FROM employees WHERE status = 'active'");
$pendingLeaves = (int) scalar("SELECT COUNT(*) FROM leaves WHERE status = 'pending'");
$onLeaveToday = all(
    "SELECT e.name, e.designation, l.leave_type, l.end_date
       FROM leaves l JOIN employees e ON e.id = l.employee_id
      WHERE l.status = 'approved' AND l.start_date <= ? AND l.end_date >= ?
      ORDER BY e.name",
    [$today, $today]
);
$currentRun = one('SELECT * FROM payroll_runs WHERE period = ?', [$currentPeriod]);

// Last six payroll runs for the cost chart (oldest first)
$runs = array_reverse(all('SELECT id, period, status FROM payroll_runs ORDER BY period DESC LIMIT 6'));
$chart = ['labels' => [], 'net' => [], 'epf8' => [], 'apit' => [], 'employer' => []];
foreach ($runs as $r) {
    $t = run_totals((int) $r['id']);
    $chart['labels'][] = date('M Y', strtotime($r['period'] . '-01'));
    $chart['net'][] = round((float) $t['net_pay']);
    $chart['epf8'][] = round((float) $t['epf_employee']);
    $chart['apit'][] = round((float) $t['apit']);
    $chart['employer'][] = round((float) $t['epf_employer'] + (float) $t['etf_employer']);
}
$lastFinal = one("SELECT id, period FROM payroll_runs WHERE status = 'finalized' ORDER BY period DESC LIMIT 1");
$lastTotals = $lastFinal ? run_totals((int) $lastFinal['id']) : null;

$byDept = all(
    "SELECT COALESCE(d.name, 'Unassigned') AS name, COUNT(e.id) AS total
       FROM employees e LEFT JOIN departments d ON d.id = e.department_id
      WHERE e.status = 'active'
      GROUP BY d.name ORDER BY total DESC"
);

$pending = all(
    "SELECT l.*, e.name, e.emp_no FROM leaves l JOIN employees e ON e.id = l.employee_id
      WHERE l.status = 'pending' ORDER BY l.start_date LIMIT 5"
);

render_header('Dashboard', 'admin', 'dashboard', 'Good ' . (date('H') < 12 ? 'morning' : (date('H') < 17 ? 'afternoon' : 'evening')) . ', ' . explode(' ', $admin['name'])[0] . '. Here is how things look today.');
?>

<?php if (!$currentRun): ?>
    <div class="card-app card-body-app mb-4 d-flex flex-wrap align-items-center gap-3">
        <span class="stat-icon tone-amber" style="background:rgba(217,119,6,.12);color:var(--app-accent)"><i class="bi bi-lightning-charge"></i></span>
        <div class="me-auto">
            <strong>Payroll for <?= e(period_label($currentPeriod)) ?> hasn't been run yet.</strong>
            <div class="text-body-secondary small">It takes one click. You can adjust overtime and deductions before finalizing.</div>
        </div>
        <a class="btn btn-primary" href="<?= e(url('admin/payroll.php')) ?>">Run payroll</a>
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3"><?= stat_tile('bi-people', 'Active employees', (string) $activeEmployees, 'primary') ?></div>
    <div class="col-sm-6 col-xl-3"><?= stat_tile('bi-hourglass-split', 'Pending leave', (string) $pendingLeaves, 'amber', $pendingLeaves ? 'Waiting for your approval' : 'All caught up') ?></div>
    <div class="col-sm-6 col-xl-3"><?= stat_tile('bi-cash-stack', 'Last net payroll', $lastTotals ? money($lastTotals['net_pay']) : '—', 'blue', $lastFinal ? period_label($lastFinal['period']) : 'No payroll yet') ?></div>
    <div class="col-sm-6 col-xl-3"><?= stat_tile('bi-bank', 'EPF + ETF due', $lastTotals ? money($lastTotals['epf_employee'] + $lastTotals['epf_employer'] + $lastTotals['etf_employer']) : '—', 'rose', 'For the last finalized month') ?></div>
</div>

<div class="row g-3 mb-4">
    <div class="col-xl-8">
        <div class="card-app h-100">
            <div class="card-header-app">
                <h2>Payroll cost by month</h2>
                <a class="small" href="<?= e(url('admin/payroll.php')) ?>">All payroll runs →</a>
            </div>
            <div class="card-body-app">
                <?php if ($runs): ?>
                    <div class="chart-box"><canvas id="costChart"></canvas></div>
                <?php else: ?>
                    <div class="empty-state"><i class="bi bi-bar-chart"></i>Run your first payroll to see costs here.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card-app h-100">
            <div class="card-header-app"><h2>Headcount by department</h2></div>
            <div class="card-body-app">
                <?php if ($byDept): ?>
                    <div class="chart-box"><canvas id="deptChart"></canvas></div>
                <?php else: ?>
                    <div class="empty-state"><i class="bi bi-diagram-3"></i>No employees yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-7">
        <div class="card-app h-100">
            <div class="card-header-app">
                <h2>Leave waiting for approval</h2>
                <a class="small" href="<?= e(url('admin/leaves.php')) ?>">All requests →</a>
            </div>
            <?php if ($pending): ?>
                <div class="table-responsive">
                    <table class="table table-app">
                        <thead><tr><th>Employee</th><th>Type</th><th>Dates</th><th class="text-end">Action</th></tr></thead>
                        <tbody>
                        <?php foreach ($pending as $l): ?>
                            <tr>
                                <td><div class="person"><span class="avatar"><?= e(initials($l['name'])) ?></span><div><?= e($l['name']) ?><small><?= e($l['reason']) ?></small></div></div></td>
                                <td><?= e(leave_types()[$l['leave_type']] ?? $l['leave_type']) ?></td>
                                <td class="text-nowrap"><?= e(fmt_date($l['start_date'])) ?><?= $l['end_date'] !== $l['start_date'] ? ' – ' . e(fmt_date($l['end_date'])) : '' ?><small class="d-block text-body-secondary"><?= (float) $l['days'] ?> day(s)</small></td>
                                <td class="text-end text-nowrap">
                                    <form method="post" action="<?= e(url('admin/leaves.php')) ?>" class="d-inline">
                                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $l['id'] ?>">
                                        <button class="btn btn-sm btn-primary" name="action" value="approve">Approve</button>
                                        <button class="btn btn-sm btn-outline-danger" name="action" value="reject">Reject</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state"><i class="bi bi-check2-all"></i>No pending requests.</div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="card-app h-100">
            <div class="card-header-app"><h2>On leave today</h2><span class="text-body-secondary small"><?= e(fmt_date($today)) ?></span></div>
            <?php if ($onLeaveToday): ?>
                <ul class="list-unstyled m-0 card-body-app d-grid gap-3">
                    <?php foreach ($onLeaveToday as $p): ?>
                        <li class="person">
                            <span class="avatar"><?= e(initials($p['name'])) ?></span>
                            <div class="me-auto"><?= e($p['name']) ?><small><?= e($p['designation']) ?></small></div>
                            <span class="small text-body-secondary"><?= e(leave_types()[$p['leave_type']] ?? '') ?> · back after <?= e(date('d M', strtotime($p['end_date']))) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="empty-state"><i class="bi bi-emoji-smile"></i>Everyone is in today.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
(function () {
    if (!window.Chart) { return; }
    var chartData = <?= json_encode($chart) ?>;
    var dept = <?= json_encode($byDept) ?>;
    var charts = [];

    function css(name) { return getComputedStyle(document.documentElement).getPropertyValue(name).trim(); }
    function rs(v) { return 'Rs. ' + Number(v).toLocaleString('en-LK'); }

    function draw() {
        charts.forEach(function (c) { c.destroy(); });
        charts = [];
        Chart.defaults.color = css('--app-muted');
        Chart.defaults.borderColor = css('--app-border');
        Chart.defaults.font.family = css('--bs-body-font-family');

        var cost = document.getElementById('costChart');
        if (cost) {
            charts.push(new Chart(cost, {
                type: 'bar',
                data: {
                    labels: chartData.labels,
                    datasets: [
                        { label: 'Net pay', data: chartData.net, backgroundColor: '#14b8a6', borderRadius: 4 },
                        { label: 'Employee EPF 8%', data: chartData.epf8, backgroundColor: '#3b82f6', borderRadius: 4 },
                        { label: 'APIT', data: chartData.apit, backgroundColor: '#f59e0b', borderRadius: 4 },
                        { label: 'Employer EPF 12% + ETF 3%', data: chartData.employer, backgroundColor: '#e11d48', borderRadius: 4 }
                    ]
                },
                options: {
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        x: { stacked: true, grid: { display: false } },
                        y: { stacked: true, ticks: { callback: function (v) { return 'Rs. ' + (v / 1000000).toFixed(1) + 'M'; } } }
                    },
                    plugins: {
                        legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } },
                        tooltip: { callbacks: { label: function (c) { return c.dataset.label + ': ' + rs(c.raw); } } }
                    }
                }
            }));
        }

        var d = document.getElementById('deptChart');
        if (d) {
            charts.push(new Chart(d, {
                type: 'doughnut',
                data: {
                    labels: dept.map(function (x) { return x.name; }),
                    datasets: [{
                        data: dept.map(function (x) { return x.total; }),
                        backgroundColor: ['#14b8a6', '#3b82f6', '#f59e0b', '#e11d48', '#8b5cf6', '#64748b'],
                        borderColor: css('--app-surface'), borderWidth: 3
                    }]
                },
                options: { maintainAspectRatio: false, cutout: '62%', plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } } } }
            }));
        }
    }
    draw();
    document.addEventListener('themechange', draw);
})();
</script>
<?php render_footer([CHART_JS], ob_get_clean()); ?>
