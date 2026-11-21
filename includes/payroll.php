<?php
declare(strict_types=1);

/**
 * Sri Lankan statutory payroll rules.
 *
 * - EPF (Employees' Provident Fund Act): employee 8%, employer 12% of total earnings.
 * - ETF (Employees' Trust Fund Act): employer 3% of total earnings.
 * - APIT (Advance Personal Income Tax), Tax Table 01 for monthly regular profits,
 *   Y/A 2025/26: Rs. 150,000 monthly relief, then 6% / 18% / 24% / 30% / 36%.
 *
 * Tax bands change with each budget. Check the latest IRD APIT tables before
 * using this for real payroll, and update the values below.
 */
function payroll_rates(): array
{
    return [
        'tax_year'      => '2025/26',
        'epf_employee'  => 0.08,
        'epf_employer'  => 0.12,
        'etf_employer'  => 0.03,
        // Overtime: 1.5x the normal hourly rate, hourly rate = monthly basic / 240
        'ot_divisor'    => 240,
        'ot_multiplier' => 1.5,
        // No-pay deduction per day = (basic + fixed allowance) / 30
        'nopay_divisor' => 30,
        'apit' => [
            'relief' => 150000.00,
            // [width of band (null = no upper limit), rate]
            'bands' => [
                [83333.33, 0.06],
                [41666.67, 0.18],
                [41666.67, 0.24],
                [41666.67, 0.30],
                [null,     0.36],
            ],
        ],
        // Shop and Office Employees Act leave entitlements (days per calendar year)
        'leave_entitlements' => ['annual' => 14, 'casual' => 7, 'sick' => 7],
    ];
}

/** Monthly APIT on regular profits using the progressive bands. */
function calculate_apit(float $monthlyTaxable, ?array $rates = null): float
{
    $rates = $rates ?? payroll_rates();
    $remaining = $monthlyTaxable - $rates['apit']['relief'];
    $tax = 0.0;
    foreach ($rates['apit']['bands'] as [$width, $rate]) {
        if ($remaining <= 0) {
            break;
        }
        $slice = $width === null ? $remaining : min($remaining, $width);
        $tax += $slice * $rate;
        $remaining -= $slice;
    }
    return round($tax, 2);
}

/**
 * Calculate one employee's payslip for a month.
 *
 * @param array $in basic, fixed_allowance, other_allowance, ot_hours, nopay_days, other_deductions
 */
function calculate_payslip(array $in, ?array $rates = null): array
{
    $rates = $rates ?? payroll_rates();

    $basic     = (float) ($in['basic'] ?? 0);
    $fixed     = (float) ($in['fixed_allowance'] ?? 0);   // EPF-liable (e.g. budgetary relief allowance)
    $other     = (float) ($in['other_allowance'] ?? 0);   // not EPF-liable (e.g. travel, fuel)
    $otHours   = max(0.0, (float) ($in['ot_hours'] ?? 0));
    $nopayDays = max(0.0, (float) ($in['nopay_days'] ?? 0));
    $otherDed  = max(0.0, (float) ($in['other_deductions'] ?? 0));

    $otPay = round($basic / $rates['ot_divisor'] * $rates['ot_multiplier'] * $otHours, 2);
    $nopay = round(min($basic + $fixed, ($basic + $fixed) / $rates['nopay_divisor'] * $nopayDays), 2);

    $epfBase = max(0.0, $basic + $fixed - $nopay);
    $gross   = round($basic + $fixed + $other + $otPay - $nopay, 2);

    $epfEmployee = round($epfBase * $rates['epf_employee'], 2);
    $epfEmployer = round($epfBase * $rates['epf_employer'], 2);
    $etf         = round($epfBase * $rates['etf_employer'], 2);
    $apit        = calculate_apit($gross, $rates);

    $totalDeductions = round($epfEmployee + $apit + $otherDed, 2);

    return [
        'basic'            => round($basic, 2),
        'fixed_allowance'  => round($fixed, 2),
        'other_allowance'  => round($other, 2),
        'ot_hours'         => $otHours,
        'ot_pay'           => $otPay,
        'nopay_days'       => $nopayDays,
        'nopay_deduction'  => $nopay,
        'gross_pay'        => $gross,
        'epf_base'         => round($epfBase, 2),
        'epf_employee'     => $epfEmployee,
        'epf_employer'     => $epfEmployer,
        'etf_employer'     => $etf,
        'apit'             => $apit,
        'other_deductions' => round($otherDed, 2),
        'total_deductions' => $totalDeductions,
        'net_pay'          => round($gross - $totalDeductions, 2),
        'employer_cost'    => round($gross + $epfEmployer + $etf, 2),
    ];
}

/** Columns stored in the payslips table (calculate_payslip() output minus derived totals). */
function payslip_columns(): array
{
    return [
        'basic', 'fixed_allowance', 'other_allowance', 'ot_hours', 'ot_pay', 'nopay_days', 'nopay_deduction',
        'gross_pay', 'epf_base', 'epf_employee', 'epf_employer', 'etf_employer', 'apit', 'other_deductions', 'net_pay',
    ];
}

/** Approved no-pay leave days for an employee that fall inside a pay period (Y-m). */
function nopay_days_in_period(int $employeeId, string $period): float
{
    $start = $period . '-01';
    $end = date('Y-m-t', strtotime($start));
    $rows = all(
        "SELECT start_date, end_date FROM leaves
          WHERE employee_id = ? AND leave_type = 'nopay' AND status = 'approved'
            AND start_date <= ? AND end_date >= ?",
        [$employeeId, $end, $start]
    );
    $days = 0;
    foreach ($rows as $r) {
        $days += days_between(max($r['start_date'], $start), min($r['end_date'], $end));
    }
    return (float) $days;
}

/** Create a draft payroll run with a payslip for every active employee. Returns the run id. */
function create_payroll_run(string $period, int $adminId): int
{
    $monthEnd = date('Y-m-t', strtotime($period . '-01'));
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $runId = insert('payroll_runs', [
            'period' => $period,
            'status' => 'draft',
            'created_by' => $adminId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $employees = all(
            "SELECT * FROM employees WHERE status = 'active' AND (join_date IS NULL OR join_date <= ?)",
            [$monthEnd]
        );
        foreach ($employees as $emp) {
            save_payslip($runId, $emp, [
                'ot_hours' => 0,
                'nopay_days' => nopay_days_in_period((int) $emp['id'], $period),
                'other_deductions' => 0,
            ]);
        }
        $pdo->commit();
        return $runId;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/** Calculate and insert (or replace) a payslip for an employee in a run. */
function save_payslip(int $runId, array $employee, array $inputs): void
{
    $slip = calculate_payslip([
        'basic' => $employee['basic_salary'],
        'fixed_allowance' => $employee['fixed_allowance'],
        'other_allowance' => $employee['other_allowance'],
    ] + $inputs);

    $row = ['run_id' => $runId, 'employee_id' => (int) $employee['id']];
    foreach (payslip_columns() as $col) {
        $row[$col] = $slip[$col];
    }

    q('DELETE FROM payslips WHERE run_id = ? AND employee_id = ?', [$runId, $employee['id']]);
    insert('payslips', $row);
}

/** Recalculate an existing payslip after the admin edits OT, no-pay days or deductions. */
function recalculate_payslip(array $slip, array $inputs): void
{
    $calc = calculate_payslip([
        'basic' => $slip['basic'],
        'fixed_allowance' => $slip['fixed_allowance'],
        'other_allowance' => $slip['other_allowance'],
    ] + $inputs);

    $data = [];
    foreach (payslip_columns() as $col) {
        $data[$col] = $calc[$col];
    }
    update('payslips', $data, (int) $slip['id']);
}

/** Totals for a run, used by the dashboard, run page and EPF report. */
function run_totals(int $runId): array
{
    return one(
        'SELECT COUNT(*) AS employees,
                COALESCE(SUM(gross_pay), 0) AS gross_pay,
                COALESCE(SUM(net_pay), 0) AS net_pay,
                COALESCE(SUM(epf_base), 0) AS epf_base,
                COALESCE(SUM(epf_employee), 0) AS epf_employee,
                COALESCE(SUM(epf_employer), 0) AS epf_employer,
                COALESCE(SUM(etf_employer), 0) AS etf_employer,
                COALESCE(SUM(apit), 0) AS apit,
                COALESCE(SUM(ot_pay), 0) AS ot_pay
           FROM payslips WHERE run_id = ?',
        [$runId]
    ) ?? [];
}

/** Leave days used (approved + pending) per type in a calendar year. */
function leave_used(int $employeeId, int $year): array
{
    $used = array_fill_keys(array_keys(leave_types()), 0);
    $rows = all(
        "SELECT leave_type, days FROM leaves
          WHERE employee_id = ? AND status IN ('approved', 'pending') AND start_date >= ? AND start_date <= ?",
        [$employeeId, "$year-01-01", "$year-12-31"]
    );
    foreach ($rows as $r) {
        $used[$r['leave_type']] = ($used[$r['leave_type']] ?? 0) + (float) $r['days'];
    }
    return $used;
}
