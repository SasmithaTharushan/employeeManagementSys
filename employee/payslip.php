<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/payslip_view.php';
$me = require_employee();

$slip = load_payslip((int) input('id'));
// Employees can only see their own, finalized payslips.
if (!$slip || (int) $slip['employee_id'] !== (int) $me['id'] || $slip['run_status'] !== 'finalized') {
    flash('danger', 'That payslip is not available.');
    redirect('employee/payslips.php');
}

render_header('Payslip', 'employee', 'payslips', period_label($slip['period']));
?>
<div class="d-flex justify-content-between mb-3 no-print">
    <a class="btn btn-link px-0" href="<?= e(url('employee/payslips.php')) ?>"><i class="bi bi-arrow-left"></i> All payslips</a>
    <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Print / save as PDF</button>
</div>
<?php render_payslip($slip); ?>
<?php render_footer(); ?>
