<?php
/**
 * One-time web installer for MySQL hosting (XAMPP, InfinityFree, etc.).
 * Creates the tables and demo data. It refuses to run once the tables exist.
 */
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/database/setup.php';

$message = '';
$installed = false;
try {
    $pdo = db();
    try {
        $pdo->query('SELECT 1 FROM admins LIMIT 1');
        $installed = true;
    } catch (PDOException $e) {
        $installed = false; // tables not created yet
    }
} catch (Throwable $e) {
    $message = 'Could not connect to the database. Check the DB_* settings in config.php or your environment variables.';
}

if (!$installed && !$message && is_post()) {
    verify_csrf();
    install_database(db(), isset($_POST['demo']));
    $installed = true;
    $message = 'Done! The database is ready.';
}

html_head('Install');
?>
<body>
<div class="container py-5" style="max-width: 640px">
    <div class="card-app">
        <div class="card-body-app">
            <h1 class="h3 fw-bold mb-3">Install <?= e(config('app_name')) ?></h1>
            <?php if ($message): ?><div class="alert alert-info"><?= e($message) ?></div><?php endif; ?>
            <?php if ($installed): ?>
                <p>The database is installed. For safety, delete <code>install.php</code> from your server.</p>
                <a class="btn btn-primary" href="<?= e(url('admin/login.php')) ?>">Go to admin login</a>
            <?php elseif (!$message): ?>
                <p>This creates all tables in <code><?= e(config('db.name')) ?></code>.</p>
                <form method="post">
                    <?= csrf_field() ?>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="demo" id="demo" checked>
                        <label class="form-check-label" for="demo">Add demo data (13 employees, 5 months of payroll)</label>
                    </div>
                    <button class="btn btn-primary">Install</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
