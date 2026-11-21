<?php
/**
 * Payroll calculation tests. Run with:  php tests/payroll_test.php
 * Expected values are worked out by hand from the 2025/26 rates.
 */
declare(strict_types=1);

require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/payroll.php';

$failures = 0;
$count = 0;
function check(string $name, $expected, $actual): void
{
    global $failures, $count;
    $count++;
    $ok = is_float($expected) || is_int($expected)
        ? abs((float) $expected - (float) $actual) < 0.005
        : $expected === $actual;
    if (!$ok) {
        $failures++;
        echo "FAIL  $name: expected " . var_export($expected, true) . ', got ' . var_export($actual, true) . "\n";
    }
}

// APIT: monthly relief Rs. 150,000, then 6/18/24/30/36%
check('APIT below relief', 0.0, calculate_apit(120000));
check('APIT exactly at relief', 0.0, calculate_apit(150000));
check('APIT in 6% band', 3000.0, calculate_apit(200000));
check('APIT across 6% and 18% bands', 8000.0, calculate_apit(250000));
// Rs. 500,000/month = Rs. 6M/year -> annual tax 1,032,000 -> 86,000/month
check('APIT reaching 36% band', 86000.0, calculate_apit(500000));

// Full payslip with overtime and one no-pay day
$s = calculate_payslip([
    'basic' => 100000, 'fixed_allowance' => 3500, 'other_allowance' => 10000,
    'ot_hours' => 10, 'nopay_days' => 1, 'other_deductions' => 0,
]);
check('OT pay = 100000/240 x 1.5 x 10', 6250.0, $s['ot_pay']);
check('No-pay = 103500/30', 3450.0, $s['nopay_deduction']);
check('EPF base excludes other allowance and OT', 100050.0, $s['epf_base']);
check('Gross pay', 116300.0, $s['gross_pay']);
check('EPF employee 8%', 8004.0, $s['epf_employee']);
check('EPF employer 12%', 12006.0, $s['epf_employer']);
check('ETF 3%', 3001.5, $s['etf_employer']);
check('No APIT below threshold', 0.0, $s['apit']);
check('Net pay', 108296.0, $s['net_pay']);
check('Employer cost', 131307.5, $s['employer_cost']);

// Higher earner with a salary advance recovery
$s = calculate_payslip(['basic' => 300000, 'fixed_allowance' => 0, 'other_allowance' => 0, 'other_deductions' => 5000]);
check('High earner APIT', 18500.0, $s['apit']); // 5,000 + 7,500 (18%) + 25,000 x 24% = 18,500
check('High earner net', 300000 - 24000 - 18500 - 5000, $s['net_pay']);

// No-pay can never exceed the month's salary
$s = calculate_payslip(['basic' => 60000, 'fixed_allowance' => 0, 'nopay_days' => 45]);
check('No-pay capped at salary', 60000.0, $s['nopay_deduction']);
check('Gross never negative', 0.0, $s['gross_pay']);

check('Inclusive day count', 3, days_between('2026-01-01', '2026-01-03'));

echo $failures ? "\n$failures of $count checks failed.\n" : "All $count checks passed.\n";
exit($failures ? 1 : 0);
