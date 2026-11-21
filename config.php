<?php
/**
 * App configuration. Every value can be overridden with an environment
 * variable (for hosting) or a git-ignored config.local.php (for local dev).
 */
return [
    'app_name' => getenv('APP_NAME') ?: 'Ceylon HR & Payroll',
    'company'  => getenv('COMPANY_NAME') ?: 'Abcd Solutions (Pvt) Ltd',
    // Shows one-click demo logins on the login pages. Set DEMO_MODE=0 for real use.
    'demo_mode' => getenv('DEMO_MODE') !== '0',
    'db' => [
        // "mysql" (XAMPP / shared hosting) or "sqlite" (zero-setup demo)
        'driver'      => getenv('DB_DRIVER') ?: 'mysql',
        'host'        => getenv('DB_HOST') ?: '127.0.0.1',
        'port'        => getenv('DB_PORT') ?: '3306',
        'name'        => getenv('DB_NAME') ?: 'employee_management',
        'user'        => getenv('DB_USER') ?: 'root',
        'pass'        => getenv('DB_PASS') ?: '',
        'sqlite_path' => getenv('DB_SQLITE_PATH') ?: __DIR__ . '/database/payroll.sqlite',
    ],
];
