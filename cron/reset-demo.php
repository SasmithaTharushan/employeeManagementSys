<?php
/**
 * Restores the demo data. Called every night by Vercel Cron (see vercel.json),
 * so visitors to the live demo always start from clean data.
 *
 * Safety:
 *  - Only works when DEMO_MODE is on (never on a real installation).
 *  - If a CRON_SECRET env variable is set, the request must carry it
 *    (Vercel Cron sends it automatically as "Authorization: Bearer <secret>").
 *  - Without a secret, it refuses to run more than once an hour, so it
 *    can't be abused to keep wiping the demo.
 */
define('NO_SESSION', true);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once APP_ROOT . '/database/setup.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

function respond(int $status, array $body): void
{
    http_response_code($status);
    echo json_encode($body);
    exit;
}

if (!config('demo_mode')) {
    respond(403, ['ok' => false, 'error' => 'Demo mode is off, so the data is never reset.']);
}

$secret = getenv('CRON_SECRET');
if ($secret) {
    $sent = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!hash_equals('Bearer ' . $secret, $sent)) {
        respond(401, ['ok' => false, 'error' => 'Unauthorized']);
    }
} else {
    // The demo admin is re-created on every reset, so its creation time is the last reset time.
    $lastReset = null;
    try {
        $lastReset = scalar('SELECT MIN(created_at) FROM admins');
    } catch (PDOException $e) {
        // Tables missing: a reset will (re)create them.
    }
    if ($lastReset && strtotime($lastReset) > time() - 3600) {
        respond(429, ['ok' => false, 'error' => 'Already reset within the last hour.', 'last_reset' => $lastReset]);
    }
}

$started = microtime(true);
reset_demo_data(db());

respond(200, [
    'ok' => true,
    'message' => 'Demo data restored.',
    'seconds' => round(microtime(true) - $started, 2),
    'reset_at' => date('Y-m-d H:i:s'),
]);
