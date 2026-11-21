<?php
declare(strict_types=1);

/**
 * Database schema + demo data.
 *
 * Used three ways:
 *  - automatically on first request when DB_DRIVER=sqlite
 *  - from the browser via install.php (MySQL / shared hosting)
 *  - from the command line:  php database/setup.php [--no-demo]
 */

function schema_statements(string $driver): array
{
    $pk = $driver === 'sqlite' ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT AUTO_INCREMENT PRIMARY KEY';
    $tail = $driver === 'sqlite' ? '' : ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
    $money = 'DECIMAL(12,2) NOT NULL DEFAULT 0';

    return [
        "CREATE TABLE departments (
            id $pk,
            name VARCHAR(100) NOT NULL UNIQUE
        )$tail",

        "CREATE TABLE admins (
            id $pk,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(190) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL
        )$tail",

        "CREATE TABLE employees (
            id $pk,
            emp_no VARCHAR(20) NOT NULL UNIQUE,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(190) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            nic VARCHAR(12) NULL,
            epf_no VARCHAR(20) NULL,
            gender VARCHAR(10) NULL,
            dob DATE NULL,
            phone VARCHAR(20) NULL,
            department_id INT NULL,
            designation VARCHAR(120) NULL,
            join_date DATE NULL,
            basic_salary $money,
            fixed_allowance $money,
            other_allowance $money,
            bank_name VARCHAR(80) NULL,
            bank_account VARCHAR(30) NULL,
            status VARCHAR(10) NOT NULL DEFAULT 'active',
            created_at DATETIME NOT NULL,
            FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
        )$tail",

        "CREATE TABLE leaves (
            id $pk,
            employee_id INT NOT NULL,
            leave_type VARCHAR(10) NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            days DECIMAL(4,1) NOT NULL,
            reason VARCHAR(500) NOT NULL,
            status VARCHAR(10) NOT NULL DEFAULT 'pending',
            created_at DATETIME NOT NULL,
            FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
        )$tail",

        "CREATE TABLE payroll_runs (
            id $pk,
            period CHAR(7) NOT NULL UNIQUE,
            status VARCHAR(10) NOT NULL DEFAULT 'draft',
            created_by INT NULL,
            created_at DATETIME NOT NULL,
            finalized_at DATETIME NULL
        )$tail",

        "CREATE TABLE payslips (
            id $pk,
            run_id INT NOT NULL,
            employee_id INT NOT NULL,
            basic $money,
            fixed_allowance $money,
            other_allowance $money,
            ot_hours DECIMAL(6,2) NOT NULL DEFAULT 0,
            ot_pay $money,
            nopay_days DECIMAL(4,1) NOT NULL DEFAULT 0,
            nopay_deduction $money,
            gross_pay $money,
            epf_base $money,
            epf_employee $money,
            epf_employer $money,
            etf_employer $money,
            apit $money,
            other_deductions $money,
            net_pay $money,
            UNIQUE (run_id, employee_id),
            FOREIGN KEY (run_id) REFERENCES payroll_runs(id) ON DELETE CASCADE,
            FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
        )$tail",
    ];
}

function install_database(PDO $pdo, bool $withDemo = true): void
{
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    foreach (schema_statements($driver) as $sql) {
        $pdo->exec($sql);
    }
    if ($withDemo) {
        seed_demo_data($pdo);
    }
}

function seed_demo_data(PDO $pdo): void
{
    mt_srand(42);
    $now = date('Y-m-d H:i:s');
    $ins = function (string $table, array $row) use ($pdo): int {
        $cols = array_keys($row);
        $stmt = $pdo->prepare(sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(',', $cols),
            implode(',', array_fill(0, count($cols), '?'))
        ));
        $stmt->execute(array_values($row));
        return (int) $pdo->lastInsertId();
    };

    $pdo->beginTransaction();

    $ins('admins', [
        'name' => 'Demo Admin',
        'email' => 'admin@company.test',
        'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
        'created_at' => $now,
    ]);

    $deptIds = [];
    foreach (['Engineering', 'Finance', 'Human Resources', 'Sales & Marketing', 'Operations'] as $d) {
        $deptIds[$d] = $ins('departments', ['name' => $d]);
    }

    // name, department, designation, basic, fixed allowance (EPF-liable), other allowance, gender, birth year
    $people = [
        ['Nimal Perera', 'Engineering', 'Engineering Manager', 420000, 25000, 30000, 'Male', 1984],
        ['Kasun Jayawardena', 'Engineering', 'Senior Software Engineer', 285000, 3500, 15000, 'Male', 1991],
        ['Dilini Fernando', 'Engineering', 'Software Engineer', 165000, 3500, 10000, 'Female', 1997],
        ['Tharindu Silva', 'Engineering', 'Associate QA Engineer', 95000, 3500, 7500, 'Male', 2000],
        ['Mohamed Rizwan', 'Engineering', 'DevOps Engineer', 210000, 3500, 12000, 'Male', 1993],
        ['Ishara Wickramasinghe', 'Finance', 'Finance Manager', 310000, 15000, 20000, 'Female', 1986],
        ['Ruwan Bandara', 'Finance', 'Accountant', 135000, 3500, 8000, 'Male', 1992],
        ['Priya Rajendran', 'Finance', 'Payroll Officer', 98000, 3500, 6000, 'Female', 1998],
        ['Sachini Rathnayake', 'Human Resources', 'HR Executive', 120000, 3500, 8000, 'Female', 1995],
        ['Chamara Gunasekara', 'Sales & Marketing', 'Sales Manager', 240000, 10000, 25000, 'Male', 1988],
        ['Nadeesha Herath', 'Sales & Marketing', 'Marketing Executive', 110000, 3500, 10000, 'Female', 1996],
        ['Pasindu Dissanayake', 'Operations', 'Operations Executive', 85000, 3500, 6000, 'Male', 1999],
        ['Hiruni Senanayake', 'Operations', 'Customer Support Associate', 65000, 3500, 5000, 'Female', 2001],
    ];
    $banks = ['Commercial Bank', 'Bank of Ceylon', "People's Bank", 'Sampath Bank', 'HNB'];
    $employeePassword = password_hash('employee123', PASSWORD_DEFAULT);
    $employees = [];

    foreach ($people as $i => [$name, $dept, $title, $basic, $fixed, $other, $gender, $year]) {
        $first = strtolower(explode(' ', $name)[0]);
        $dayOfYear = mt_rand(1, 365);
        $joinYear = mt_rand(2018, 2025);
        $id = $ins('employees', [
            'emp_no' => sprintf('EMP%03d', $i + 1),
            'name' => $name,
            'email' => "$first@company.test",
            'password_hash' => $employeePassword,
            // New-format NIC: birth year + day of year (+500 for women) + 5-digit serial
            'nic' => sprintf('%d%03d%05d', $year, $dayOfYear + ($gender === 'Female' ? 500 : 0), mt_rand(10000, 99999)),
            'epf_no' => (string) (1000 + $i + 1),
            'gender' => $gender,
            'dob' => date('Y-m-d', strtotime("$year-01-01 +" . ($dayOfYear - 1) . ' days')),
            'phone' => sprintf('07%d %03d %04d', mt_rand(0, 8), mt_rand(100, 999), mt_rand(1000, 9999)),
            'department_id' => $deptIds[$dept],
            'designation' => $title,
            'join_date' => sprintf('%d-%02d-%02d', $joinYear, mt_rand(1, 12), mt_rand(1, 28)),
            'basic_salary' => $basic,
            'fixed_allowance' => $fixed,
            'other_allowance' => $other,
            'bank_name' => $banks[$i % count($banks)],
            'bank_account' => (string) mt_rand(1000000000, 1999999999),
            'status' => 'active',
            'created_at' => $now,
        ]);
        $employees[] = ['id' => $id, 'basic_salary' => $basic, 'fixed_allowance' => $fixed,
                        'other_allowance' => $other, 'manager' => str_contains($title, 'Manager')];
    }

    // Leave requests around today so the dashboards have something to show.
    $d = fn(string $rel) => date('Y-m-d', strtotime($rel));
    $leaves = [
        [3, 'annual', $d('-40 days'), $d('-38 days'), 'Family trip to Ella', 'approved'],
        [7, 'sick', $d('-25 days'), $d('-24 days'), 'Fever', 'approved'],
        [12, 'nopay', date('Y-m-03'), date('Y-m-05'), 'Personal matter (annual leave used up)', 'approved'],
        [2, 'casual', $d('today'), $d('today'), 'Bank work', 'approved'],
        [9, 'annual', $d('+5 days'), $d('+7 days'), 'Sister\'s wedding in Kandy', 'pending'],
        [4, 'casual', $d('+2 days'), $d('+2 days'), 'Vehicle revenue licence renewal', 'pending'],
        [11, 'sick', $d('-10 days'), $d('-10 days'), 'Dental appointment', 'rejected'],
        [3, 'casual', $d('+12 days'), $d('+12 days'), 'House moving', 'pending'],
    ];
    foreach ($leaves as [$empIndex, $type, $start, $end, $reason, $status]) {
        $ins('leaves', [
            'employee_id' => $employees[$empIndex - 1]['id'],
            'leave_type' => $type,
            'start_date' => $start,
            'end_date' => $end,
            'days' => (new DateTime($start))->diff(new DateTime($end))->days + 1,
            'reason' => $reason,
            'status' => $status,
            'created_at' => $now,
        ]);
    }

    // Five finalized payroll runs for the previous months.
    for ($m = 5; $m >= 1; $m--) {
        $period = date('Y-m', strtotime("first day of -$m month"));
        $runId = $ins('payroll_runs', [
            'period' => $period,
            'status' => 'finalized',
            'created_by' => 1,
            'created_at' => date('Y-m-d H:i:s', strtotime("$period-25 10:00")),
            'finalized_at' => date('Y-m-d H:i:s', strtotime("$period-26 16:00")),
        ]);
        foreach ($employees as $emp) {
            $slip = calculate_payslip([
                'basic' => $emp['basic_salary'],
                'fixed_allowance' => $emp['fixed_allowance'],
                'other_allowance' => $emp['other_allowance'],
                'ot_hours' => $emp['manager'] ? 0 : mt_rand(0, 4) * 4,
                'nopay_days' => 0,
                'other_deductions' => mt_rand(0, 5) === 0 ? 5000 : 0, // occasional salary advance recovery
            ]);
            $row = ['run_id' => $runId, 'employee_id' => $emp['id']];
            foreach (payslip_columns() as $col) {
                $row[$col] = $slip[$col];
            }
            $ins('payslips', $row);
        }
    }

    $pdo->commit();
}

// Command-line entry point: php database/setup.php [--no-demo]
if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    require_once __DIR__ . '/../includes/bootstrap.php';
    $c = config('db');
    if ($c['driver'] === 'sqlite') {
        @unlink($c['sqlite_path']);
        $pdo = new PDO('sqlite:' . $c['sqlite_path'], null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec('PRAGMA foreign_keys = ON');
    } else {
        $pdo = db();
    }
    install_database($pdo, !in_array('--no-demo', $argv, true));
    echo "Database ready ({$c['driver']}).\n";
}
