<?php
/**
 * App configuration. Every value can be overridden with an environment
 * variable (for hosting) or a git-ignored config.local.php (for local dev).
 *
 * On hosts like Vercel, set a single DATABASE_URL, e.g.
 *   mysql://USER:PASSWORD@HOST:4000/DBNAME
 * It overrides the individual DB_* values, turns on TLS and stores
 * login sessions in the database so they work across instances.
 */
$url = getenv('DATABASE_URL') ? parse_url(getenv('DATABASE_URL')) : null;

return [
    'app_name' => getenv('APP_NAME') ?: 'Ceylon HR & Payroll',
    'company'  => getenv('COMPANY_NAME') ?: 'Abcd Solutions (Pvt) Ltd',
    // Shows one-click demo logins on the login pages. Set DEMO_MODE=0 for real use.
    'demo_mode' => getenv('DEMO_MODE') !== '0',
    // "files" (default) or "database" (needed when the app runs on several instances)
    'session_driver' => getenv('SESSION_DRIVER') ?: ($url ? 'database' : 'files'),
    'db' => [
        // "mysql" (XAMPP / shared hosting / TiDB) or "sqlite" (zero-setup demo)
        'driver'      => $url ? 'mysql' : (getenv('DB_DRIVER') ?: 'mysql'),
        'host'        => $url['host'] ?? (getenv('DB_HOST') ?: '127.0.0.1'),
        'port'        => (string) ($url['port'] ?? (getenv('DB_PORT') ?: '3306')),
        'name'        => isset($url['path']) && trim($url['path'], '/') !== '' ? trim($url['path'], '/') : (getenv('DB_NAME') ?: 'employee_management'),
        'user'        => isset($url['user']) ? rawurldecode($url['user']) : (getenv('DB_USER') ?: 'root'),
        'pass'        => isset($url['pass']) ? rawurldecode($url['pass']) : (getenv('DB_PASS') ?: ''),
        // Encrypted connection (required by cloud databases such as TiDB). DB_SSL=0 turns it off.
        'ssl'         => getenv('DB_SSL') !== false ? getenv('DB_SSL') === '1' : (bool) $url,
        'ssl_ca'      => getenv('DB_SSL_CA') ?: null,
        'sqlite_path' => getenv('DB_SQLITE_PATH') ?: __DIR__ . '/database/payroll.sqlite',
    ],
];
