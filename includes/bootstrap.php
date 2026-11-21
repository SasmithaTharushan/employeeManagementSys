<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

$config = require APP_ROOT . '/config.php';
if (is_file(APP_ROOT . '/config.local.php')) {
    $config = array_replace_recursive($config, require APP_ROOT . '/config.local.php');
}
$GLOBALS['config'] = $config;

date_default_timezone_set('Asia/Colombo');

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/payroll.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';

// Scripts like the cron endpoint define NO_SESSION so they don't create login sessions.
if (PHP_SAPI !== 'cli' && !defined('NO_SESSION') && session_status() === PHP_SESSION_NONE) {
    if (config('session_driver') === 'database') {
        session_set_save_handler(new DatabaseSessionHandler(), true);
    }
    // "None" lets the login work when the app is embedded in an iframe. Browsers only
    // accept SameSite=None cookies over HTTPS, so it implies Secure. Forms keep CSRF tokens.
    $sameSite = getenv('SESSION_SAMESITE') === 'None' ? 'None' : 'Lax';
    $https = ($_SERVER['HTTPS'] ?? '') === 'on' || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => $sameSite,
        'cookie_secure'   => $sameSite === 'None' || $https,
        'use_strict_mode' => true,
    ]);
}
