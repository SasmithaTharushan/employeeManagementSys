<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$me = require_employee();
$id = (int) $me['id'];

$slips = all(
    "SELECT p.*, r.period FROM payslips p JOIN payroll_runs r ON r.id = p.run_id
      WHERE p.employee_id = ? AND r.status = 'finalized' ORDER BY r.period DESC",
    [$id]
);
$latest = $slips[0] ?? null;
$year = date('Y');
$ytdNet = 0.0;
$epfTotal = 0.0;
foreach ($slips as $s) {
    if (str_starts_with($s['period'], $year)) {
        $ytdNet += (float) $s['net_pay'];
    }
    $epfTotal += (float) $s['epf_employee'] + (float) $s['epf_employer'];
}

$entitlements = payroll_rates()['leave_entitlements'];
$used = leave_used($id, (int) $year);
$upcoming = all(
    "SELECT * FROM leaves WHERE employee_id = ? AND end_date >= ? AND status IN ('approved', 'pending') ORDER BY start_date LIMIT 4",
    [$id, date('Y-m-d')]
);

$chartSlips = array_reverse(array_slice($slips, 0, 6));
$chart = [
    'labels' => array_map(fn($s) => date('M', strtotime($s['period'] . '-01')), $chartSlips),
    'net' => array_map(fn($s) => (float) $s['net_pay'], $chartSlips),
    'deductions' => array_map(fn($s) => (float) $s['epf_employee'] + (float) $s['apit'] + (float) $s['other_deductions'], $chartSlips),
];

render_header('Hi, ' . explode(' ', $me['name'])[0] . ' 👋', 'employee', 'dashboard', ($me['designation'] ?: 'Employee') . ($me['department'] ? ' · ' . $me['department'] : ''));
?>
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3"><?= stat_tile('bi-wallet2', 'Last take-home', $latest ? money($latest['net_pay']) : '—', 'primary', $latest ? period_label($latest['period']) : 'No payslips yet') ?></div>
    <div class="col-sm-6 col-xl-3"><?= stat_tile('bi-graph-up-arrow', "Net pay in $year", money($ytdNet), 'blue') ?></div>
    <div class="col-sm-6 col-xl-3"><?= stat_tile('bi-piggy-bank', 'EPF credited', money($epfTotal), 'amber', 'Your 8% + employer 12%') ?></div>
    <div class="col-sm-6 col-xl-3"><?= stat_tile('bi-calendar2-heart', 'Annual leave left', max(0, $entitlements['annual'] - $used['annual']) . ' days', 'rose', 'of ' . $entitlements['annual'] . " for $year") ?></div>
</div>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="card-app h-100">
            <div class="card-header-app">
                <h2>My pay over time</h2>
                <a class="small" href="<?= e(url('employee/payslips.php')) ?>">All payslips →</a>
            </div>
            <div class="card-body-app">
                <?php if ($chartSlips): ?>
                    <div class="chart-box"><canvas id="payChart"></canvas></div>
                <?php else: ?>
                    <div class="empty-state"><i class="bi bi-receipt"></i>Your payslips will appear here after payroll is finalized.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-xl-4 d-grid gap-3">
        <div class="card-app">
            <div class="card-header-app"><h2>Leave balance <?= e($year) ?></h2><a class="small" href="<?= e(url('employee/leave.php')) ?>">Apply →</a></div>
            <div class="card-body-app">
                <?php foreach ($entitlements as $type => $total): $left = max(0, $total - $used[$type]); ?>
                    <div class="balance">
                        <div class="balance-top"><span><?= e(leave_types()[$type]) ?></span><span class="fw-semibold"><?= $left ?> / <?= $total ?> left</span></div>
                        <div class="progress" role="progressbar" aria-valuenow="<?= $left ?>" aria-valuemin="0" aria-valuemax="<?= $total ?>">
                            <div class="progress-bar" style="width: <?= $total ? round($left / $total * 100) : 0 ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="card-app">
            <div class="card-header-app"><h2>Upcoming leave</h2></div>
            <?php if ($upcoming): ?>
                <ul class="list-unstyled card-body-app m-0 d-grid gap-2">
                    <?php foreach ($upcoming as $l): ?>
                        <li class="d-flex justify-content-between gap-2">
                            <span><?= e(leave_types()[$l['leave_type']]) ?> · <?= e(fmt_date($l['start_date'])) ?><?= $l['end_date'] !== $l['start_date'] ? ' – ' . e(fmt_date($l['end_date'])) : '' ?></span>
                            <?= status_badge($l['status']) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="empty-state py-4"><i class="bi bi-sun"></i>No leave planned.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
(function () {
    var el = document.getElementById('payChart');
    if (!el || !window.Chart) { return; }
    var d = <?= json_encode($chart) ?>, chart;
    function css(n) { return getComputedStyle(document.documentElement).getPropertyValue(n).trim(); }
    function draw() {
        if (chart) { chart.destroy(); }
        Chart.defaults.color = css('--app-muted');
        Chart.defaults.borderColor = css('--app-border');
        Chart.defaults.font.family = css('--bs-body-font-family');
        chart = new Chart(el, {
            type: 'bar',
            data: { labels: d.labels, datasets: [
                { label: 'Take-home', data: d.net, backgroundColor: '#14b8a6', borderRadius: 6 },
                { label: 'EPF 8% + APIT + other', data: d.deductions, backgroundColor: '#f59e0b', borderRadius: 6 }
            ] },
            options: {
                maintainAspectRatio: false,
                scales: { x: { stacked: true, grid: { display: false } }, y: { stacked: true, ticks: { callback: function (v) { return 'Rs. ' + (v / 1000) + 'k'; } } } },
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } },
                    tooltip: { callbacks: { label: function (c) { return c.dataset.label + ': Rs. ' + c.raw.toLocaleString('en-LK', { minimumFractionDigits: 2 }); } } }
                }
            }
        });
    }
    draw();
    document.addEventListener('themechange', draw);
})();
</script>
<?php render_footer([CHART_JS], ob_get_clean()); ?>
