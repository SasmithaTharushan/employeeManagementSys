<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

$config = require APP_ROOT . '/config.php';
if (is_file(APP_ROOT . '/config.local.php')) {
    $config = array_replace_recursive($config, require APP_ROOT . '/config.local.php');
}
$GLOBALS['config'] = $config;

date_default_timezone_set('Asia/Colombo');

if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    // "None" lets the login work when the app is embedded in an iframe (e.g. a Hugging Face
    // Space page). Browsers only accept SameSite=None cookies over HTTPS, so it implies Secure.
    // Forms are still protected by CSRF tokens.
    $sameSite = getenv('SESSION_SAMESITE') === 'None' ? 'None' : 'Lax';
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => $sameSite,
        'cookie_secure'   => $sameSite === 'None' || (($_SERVER['HTTPS'] ?? '') === 'on'),
        'use_strict_mode' => true,
    ]);
}

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/payroll.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';
