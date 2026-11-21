<?php
declare(strict_types=1);

const BOOTSTRAP_CSS = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css';
const BOOTSTRAP_JS  = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js';
const ICONS_CSS     = 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css';
const CHART_JS      = 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js';

function nav_items(string $role): array
{
    if ($role === 'admin') {
        return [
            'dashboard'   => ['admin/dashboard.php', 'bi-grid-1x2', 'Dashboard'],
            'employees'   => ['admin/employees.php', 'bi-people', 'Employees'],
            'departments' => ['admin/departments.php', 'bi-diagram-3', 'Departments'],
            'leaves'      => ['admin/leaves.php', 'bi-calendar2-check', 'Leave requests'],
            'payroll'     => ['admin/payroll.php', 'bi-cash-stack', 'Payroll'],
            'reports'     => ['admin/epf-report.php', 'bi-file-earmark-bar-graph', 'EPF / ETF / APIT'],
            'admins'      => ['admin/admins.php', 'bi-shield-lock', 'Admins'],
            'profile'     => ['admin/profile.php', 'bi-person-gear', 'My account'],
        ];
    }
    return [
        'dashboard' => ['employee/dashboard.php', 'bi-grid-1x2', 'Dashboard'],
        'payslips'  => ['employee/payslips.php', 'bi-receipt', 'My payslips'],
        'leave'     => ['employee/leave.php', 'bi-calendar2-plus', 'Leave'],
        'profile'   => ['employee/profile.php', 'bi-person-vcard', 'My profile'],
    ];
}

function html_head(string $title): void
{
    $appName = config('app_name');
    ?>
<!doctype html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> · <?= e($appName) ?></title>
    <script>
        (function () {
            try {
                var t = localStorage.getItem('theme') ||
                    (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                document.documentElement.setAttribute('data-bs-theme', t);
            } catch (e) {}
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BOOTSTRAP_CSS ?>">
    <link rel="stylesheet" href="<?= ICONS_CSS ?>">
    <link rel="stylesheet" href="<?= e(url('assets/css/app.css')) ?>">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>💼</text></svg>">
</head>
    <?php
}

function render_header(string $title, string $role, string $active, ?string $subtitle = null): void
{
    $user = $role === 'admin' ? admin_user() : employee_user();
    html_head($title);
    ?>
<body class="app">
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <a class="brand" href="<?= e(url($role . '/dashboard.php')) ?>">
            <span class="brand-mark"><i class="bi bi-wallet2"></i></span>
            <span class="brand-text"><?= e(config('app_name')) ?><small><?= $role === 'admin' ? 'Admin console' : 'Employee portal' ?></small></span>
        </a>
        <nav class="nav flex-column">
            <?php foreach (nav_items($role) as $key => [$href, $icon, $label]): ?>
                <a class="nav-link <?= $key === $active ? 'active' : '' ?>" href="<?= e(url($href)) ?>">
                    <i class="bi <?= $icon ?>"></i><span><?= e($label) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <form method="post" action="<?= e(url('logout.php')) ?>" class="mt-auto">
            <?= csrf_field() ?>
            <button class="nav-link logout" type="submit"><i class="bi bi-box-arrow-left"></i><span>Log out</span></button>
        </form>
    </aside>
    <div class="sidebar-backdrop" data-sidebar-close></div>

    <main class="content">
        <header class="topbar">
            <button class="btn btn-icon d-lg-none" type="button" data-sidebar-toggle aria-label="Open menu"><i class="bi bi-list"></i></button>
            <div class="me-auto">
                <h1 class="page-title"><?= e($title) ?></h1>
                <?php if ($subtitle): ?><p class="page-subtitle"><?= e($subtitle) ?></p><?php endif; ?>
            </div>
            <button class="btn btn-icon" type="button" data-theme-toggle aria-label="Toggle dark mode"><i class="bi bi-moon-stars"></i></button>
            <?php if ($user): ?>
                <div class="user-chip">
                    <span class="avatar"><?= e(initials($user['name'])) ?></span>
                    <span class="d-none d-md-inline"><?= e($user['name']) ?></span>
                </div>
            <?php endif; ?>
        </header>

        <?php foreach (take_flashes() as $f): ?>
            <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show" role="alert">
                <?= e($f['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endforeach; ?>
    <?php
}

function render_footer(array $scripts = [], string $inlineScript = ''): void
{
    ?>
        <footer class="app-footer">
            © <?= date('Y') ?> <?= e(config('company')) ?> · EPF, ETF and APIT figures use the <?= e(payroll_rates()['tax_year']) ?> rates
        </footer>
    </main>
</div>
<script src="<?= BOOTSTRAP_JS ?>"></script>
<?php foreach ($scripts as $src): ?>
<script src="<?= e($src) ?>"></script>
<?php endforeach; ?>
<script src="<?= e(url('assets/js/app.js')) ?>"></script>
<?= $inlineScript ?>
</body>
</html>
    <?php
}

/** Simple stat tile used on dashboards. */
function stat_tile(string $icon, string $label, string $value, string $tone = 'primary', ?string $hint = null): string
{
    return '<div class="stat-tile tone-' . e($tone) . '">'
        . '<span class="stat-icon"><i class="bi ' . e($icon) . '"></i></span>'
        . '<div><div class="stat-label">' . e($label) . '</div>'
        . '<div class="stat-value">' . e($value) . '</div>'
        . ($hint ? '<div class="stat-hint">' . e($hint) . '</div>' : '')
        . '</div></div>';
}

/** Login page shared by admins and employees. */
function render_login(string $role, string $error, string $email): void
{
    $other = $role === 'admin' ? 'employee' : 'admin';
    $demo = $role === 'admin'
        ? ['admin@company.test', 'admin123']
        : ['dilini@company.test', 'employee123'];
    html_head(ucfirst($role) . ' login');
    ?>
<body class="auth-page">
<div class="auth-wrap">
    <div class="auth-side">
        <a href="<?= e(url('index.php')) ?>" class="brand brand-light">
            <span class="brand-mark"><i class="bi bi-wallet2"></i></span>
            <span class="brand-text"><?= e(config('app_name')) ?></span>
        </a>
        <h2>Payroll that already knows Sri Lankan law.</h2>
        <ul class="auth-points">
            <li><i class="bi bi-check2-circle"></i> EPF 8% + 12% and ETF 3% worked out on every payslip</li>
            <li><i class="bi bi-check2-circle"></i> APIT tax using the <?= e(payroll_rates()['tax_year']) ?> bands</li>
            <li><i class="bi bi-check2-circle"></i> No-pay leave deducted automatically</li>
        </ul>
    </div>
    <div class="auth-card">
        <h1><?= $role === 'admin' ? 'Admin sign in' : 'Employee sign in' ?></h1>
        <p class="text-body-secondary mb-4">
            <?= $role === 'admin' ? 'Manage employees, leave and monthly payroll.' : 'See your payslips and apply for leave.' ?>
        </p>
        <?php if ($error): ?><div class="alert alert-danger py-2"><?= e($error) ?></div><?php endif; ?>
        <form method="post" novalidate>
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label" for="email">Email</label>
                <input class="form-control form-control-lg" type="email" id="email" name="email" value="<?= e($email) ?>" required autofocus>
            </div>
            <div class="mb-4">
                <label class="form-label" for="password">Password</label>
                <input class="form-control form-control-lg" type="password" id="password" name="password" required>
            </div>
            <button class="btn btn-primary btn-lg w-100" type="submit">Sign in</button>
        </form>
        <?php if (config('demo_mode')): ?>
            <div class="demo-box">
                <div><strong>Demo account</strong><br><code><?= e($demo[0]) ?></code> / <code><?= e($demo[1]) ?></code></div>
                <button class="btn btn-outline-primary btn-sm" type="button"
                        data-demo-login data-email="<?= e($demo[0]) ?>" data-password="<?= e($demo[1]) ?>">Use demo</button>
            </div>
        <?php endif; ?>
        <p class="mt-4 mb-0 small text-body-secondary">
            Not an <?= $role ?>? <a href="<?= e(url($other . '/login.php')) ?>">Sign in as <?= $other === 'admin' ? 'an admin' : 'an employee' ?></a>
        </p>
    </div>
</div>
<script src="<?= e(url('assets/js/app.js')) ?>"></script>
</body>
</html>
    <?php
}
