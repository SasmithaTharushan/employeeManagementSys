<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/payslip_view.php';
require_admin();

$slip = load_payslip((int) input('id'));
if (!$slip) {
    flash('danger', 'That payslip does not exist.');
    redirect('admin/payroll.php');
}

render_header('Payslip', 'admin', 'payroll', $slip['name'] . ' · ' . period_label($slip['period']));
?>
<div class="d-flex justify-content-between mb-3 no-print">
    <a class="btn btn-link px-0" href="<?= e(url('admin/payroll-run.php?id=' . (int) $slip['run_id'])) ?>"><i class="bi bi-arrow-left"></i> Back to payroll</a>
    <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Print / save as PDF</button>
</div>
<?php render_payslip($slip); ?>
<?php render_footer(); ?>
