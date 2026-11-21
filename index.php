<?php
require_once __DIR__ . '/includes/bootstrap.php';

$rates = payroll_rates();
html_head('HR & payroll for Sri Lankan businesses');
?>
<body>
<div class="container">
    <nav class="landing-nav">
        <a class="brand" href="<?= e(url()) ?>">
            <span class="brand-mark"><i class="bi bi-wallet2"></i></span>
            <span class="brand-text"><?= e(config('app_name')) ?></span>
        </a>
        <div class="d-flex gap-2 align-items-center">
            <button class="btn btn-icon" type="button" data-theme-toggle aria-label="Toggle dark mode"><i class="bi bi-moon-stars"></i></button>
            <a class="btn btn-outline-primary" href="<?= e(url('employee/login.php')) ?>">Employee login</a>
            <a class="btn btn-primary d-none d-sm-inline-block" href="<?= e(url('admin/login.php')) ?>">Admin login</a>
        </div>
    </nav>

    <section class="hero">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="eyebrow"><i class="bi bi-geo-alt"></i> Built for Sri Lankan labour law</span>
                <h1 class="mt-3">HR and payroll with <span>EPF, ETF and APIT</span> done for you.</h1>
                <p class="lead mt-3">
                    Manage employees and leave, run monthly payroll in a few clicks and give every
                    employee a clear payslip. Statutory contributions and tax are calculated automatically.
                </p>
                <div class="d-flex flex-wrap gap-2 mt-4">
                    <a class="btn btn-primary btn-lg" href="<?= e(url('admin/login.php')) ?>"><i class="bi bi-speedometer2 me-1"></i> Try the admin demo</a>
                    <a class="btn btn-outline-primary btn-lg" href="<?= e(url('employee/login.php')) ?>">Try the employee demo</a>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card-app calc-demo" data-salary-preview data-rates='<?= e(json_encode($rates)) ?>'>
                    <h2 class="h5 fw-bold mb-1">Salary calculator</h2>
                    <p class="text-body-secondary small mb-3">Type a monthly salary to see the take-home pay (<?= e($rates['tax_year']) ?> rates).</p>
                    <div class="row g-2 mb-3">
                        <div class="col-sm-4">
                            <label class="form-label small">Basic salary</label>
                            <input class="form-control" type="number" name="basic_salary" value="250000" min="0" step="1000">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label small">EPF-liable allowance</label>
                            <input class="form-control" type="number" name="fixed_allowance" value="3500" min="0" step="500">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label small">Other allowances</label>
                            <input class="form-control" type="number" name="other_allowance" value="15000" min="0" step="500">
                        </div>
                    </div>
                    <table class="table table-sm mb-0">
                        <tr><td>Gross pay</td><td class="num" data-k="gross"></td></tr>
                        <tr><td>EPF (employee 8%)</td><td class="num text-danger" data-k="epf8"></td></tr>
                        <tr><td>APIT tax</td><td class="num text-danger" data-k="apit"></td></tr>
                        <tr class="fw-bold"><td>Take-home pay</td><td class="num text-success" data-k="net"></td></tr>
                        <tr class="small text-body-secondary"><td>Employer EPF 12%</td><td class="num" data-k="epf12"></td></tr>
                        <tr class="small text-body-secondary"><td>Employer ETF 3%</td><td class="num" data-k="etf"></td></tr>
                        <tr class="small text-body-secondary"><td>Total cost to employer</td><td class="num" data-k="cost"></td></tr>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <section class="pb-5">
        <div class="row g-3">
            <?php
            $features = [
                ['bi-cash-coin', 'One-click monthly payroll', 'Create a payroll run, adjust overtime or deductions, then finalize. Every payslip is recalculated instantly.'],
                ['bi-bank', 'EPF and ETF returns', 'Monthly contribution summary with EPF numbers and NICs, ready to download as CSV.'],
                ['bi-percent', 'APIT tax table', 'Progressive Advance Personal Income Tax on monthly pay, using the current relief and bands.'],
                ['bi-calendar2-x', 'No-pay leave deductions', 'Approved no-pay leave is pulled into payroll automatically, so nobody has to count days by hand.'],
                ['bi-receipt', 'Printable payslips', 'Employees see and print their own payslips. Save as PDF straight from the browser.'],
                ['bi-shield-check', 'Secure by default', 'Hashed passwords, prepared SQL statements, CSRF protection and login throttling.'],
            ];
            foreach ($features as [$icon, $title, $text]): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card-app feature">
                        <i class="bi <?= $icon ?>"></i>
                        <h3><?= e($title) ?></h3>
                        <p><?= e($text) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <footer class="app-footer pb-4">
        © <?= date('Y') ?> <?= e(config('app_name')) ?> · A portfolio project. Tax figures are for demonstration; always check the latest IRD tables.
    </footer>
</div>
<script src="<?= e(url('assets/js/app.js')) ?>"></script>
</body>
</html>
